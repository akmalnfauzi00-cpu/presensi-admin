<?php

class ApiPresensiController
{
    private function json($data, int $code = 200): void
    {
        // Pastikan tidak ada output teks/error lain sebelum JSON
        if (ob_get_level()) ob_end_clean();
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function optionsOk(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            $this->json(['ok' => true], 200);
        }
    }

    private function getBearerToken(): ?string
    {
        $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
        if (!$hdr && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $hdr = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        if (!$hdr) return null;
        if (preg_match('/Bearer\s+(\S+)/i', $hdr, $m)) return $m[1];
        return null;
    }

    private function authGuru(): array
    {
        $token = $this->getBearerToken();
        if (!$token) {
            $this->json(['message' => 'Unauthorized'], 401);
        }

        $db = Db::pdo();
        $st = $db->prepare("
            SELECT id_guru, nip, nama_guru, status_aktif
            FROM guru
            WHERE api_token = ?
            LIMIT 1
        ");
        $st->execute([$token]);
        $guru = $st->fetch(PDO::FETCH_ASSOC);

        if (!$guru) {
            $this->json(['message' => 'Unauthorized'], 401);
        }

        if (($guru['status_aktif'] ?? 'AKTIF') !== 'AKTIF') {
            $this->json(['message' => 'Akun tidak aktif'], 403);
        }

        return $guru;
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function getMasterConfig(PDO $db): array
    {
        $st = $db->query("
            SELECT *
            FROM presensi_master
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $cfg = $st->fetch(PDO::FETCH_ASSOC);

        if (!$cfg) {
            return [
                'jam_masuk' => '07:00:00',
                'jam_pulang' => '15:30:00',
                'batas_terlambat' => '08:15:00',
                'toleransi_terlambat' => 0,
                'latitude' => null,
                'longitude' => null,
                'radius_meter' => 150,
            ];
        }

        return $cfg;
    }

    private function cekLibur(PDO $db, string $tanggal): ?string
    {
        $st = $db->prepare("SELECT keterangan FROM hari_libur WHERE tanggal = ? LIMIT 1");
        $st->execute([$tanggal]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        
        if ($row) return $row['keterangan'];

        if (date('l', strtotime($tanggal)) === 'Sunday') {
            return "Libur Akhir Pekan (Minggu)";
        }
        
        return null;
    }

    private function ensureDailyHeader(PDO $db, string $tanggal, array $cfg): string
    {
        $cek = $db->prepare("SELECT id_presensi FROM kehadiran WHERE tanggal = ? LIMIT 1");
        $cek->execute([$tanggal]);
        $row = $cek->fetch(PDO::FETCH_ASSOC);

        if ($row && !empty($row['id_presensi'])) {
            return $row['id_presensi'];
        }

        $id_presensi = $this->uuid();

        $ins = $db->prepare("
            INSERT INTO kehadiran (
                id_presensi,
                tanggal,
                lokasi,
                lat_sekolah,
                lng_sekolah,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $ins->execute([
            $id_presensi,
            $tanggal,
            'Sekolah',
            isset($cfg['latitude']) ? $cfg['latitude'] : null,
            isset($cfg['longitude']) ? $cfg['longitude'] : null,
        ]);

        return $id_presensi;
    }

    private function haversineMeter(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earth * $c;
    }

    private function validateGeofence(array $cfg, ?float $lat, ?float $lng): array
    {
        if ($lat === null || $lng === null) {
            return [
                'ok' => false,
                'message' => 'Latitude dan longitude wajib dikirim.',
                'distance_meter' => null,
                'radius_meter' => isset($cfg['radius_meter']) ? (float)$cfg['radius_meter'] : null,
            ];
        }

        $latSekolah = isset($cfg['latitude']) ? (float)$cfg['latitude'] : 0.0;
        $lngSekolah = isset($cfg['longitude']) ? (float)$cfg['longitude'] : 0.0;
        $radius = isset($cfg['radius_meter']) ? (float)$cfg['radius_meter'] : 0.0;

        if ($latSekolah == 0.0 || $lngSekolah == 0.0 || $radius <= 0) {
            return [
                'ok' => false,
                'message' => 'Lokasi sekolah atau radius geofence belum diatur di admin.',
                'distance_meter' => null,
                'radius_meter' => $radius,
            ];
        }

        $distance = $this->haversineMeter($latSekolah, $lngSekolah, $lat, $lng);
        $ok = $distance <= $radius;

        return [
            'ok' => $ok,
            'message' => $ok
                ? 'Lokasi valid'
                : 'Presensi ditolak karena berada di luar radius sekolah.',
            'distance_meter' => round($distance, 2),
            'radius_meter' => $radius,
        ];
    }

    private function validatePresensiPayload(?float $lat, ?float $lng, ?string $fotoPath): void
    {
        if ($lat === null || $lng === null) {
            $this->json(['message' => 'Koordinat lokasi wajib dikirim'], 422);
        }
        if ($fotoPath === null || $fotoPath === '') {
            $this->json(['message' => 'Foto selfie wajib dikirim'], 422);
        }
    }

    public function today($req, $res): void
    {
        $this->optionsOk();
        $guru = $this->authGuru();
        $db = Db::pdo();
        $today = date('Y-m-d');
        $keteranganLibur = $this->cekLibur($db, $today);

        $h = $db->prepare("SELECT id_presensi FROM kehadiran WHERE tanggal = ? LIMIT 1");
        $h->execute([$today]);
        $header = $h->fetch(PDO::FETCH_ASSOC);

        if (!$header) {
            $this->json([
                'tanggal' => $today, 
                'is_libur' => $keteranganLibur !== null,
                'keterangan_libur' => $keteranganLibur,
                'presensi' => null
            ]);
        }

        $id_presensi = $header['id_presensi'];
        $st = $db->prepare("
            SELECT id_detail, jam_masuk, jam_keluar, status_kehadiran, is_terlambat, foto_masuk_path, foto_pulang_path, lat_masuk, lng_masuk, lat_pulang, lng_pulang
            FROM presensi_detail
            WHERE id_presensi = ? AND id_guru = ?
            LIMIT 1
        ");
        $st->execute([$id_presensi, $guru['id_guru']]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        $this->json([
            'tanggal' => $today,
            'is_libur' => $keteranganLibur !== null,
            'keterangan_libur' => $keteranganLibur,
            'presensi' => $row ? [
                'id_detail' => $row['id_detail'],
                'tanggal' => $today,
                'jam_masuk' => $row['jam_masuk'],
                'jam_pulang' => $row['jam_keluar'],
                'status' => $row['status_kehadiran'],
                'is_terlambat' => (int)($row['is_terlambat'] ?? 0),
                'foto_masuk_path' => $row['foto_masuk_path'] ?? null,
                'foto_pulang_path' => $row['foto_pulang_path'] ?? null,
                'lat_masuk' => $row['lat_masuk'] ?? null,
                'lng_masuk' => $row['lng_masuk'] ?? null,
                'lat_pulang' => $row['lat_pulang'] ?? null,
                'lng_pulang' => $row['lng_pulang'] ?? null,
            ] : null
        ]);
    }

    public function masuk($req, $res): void
    {
        $this->optionsOk();
        $guru = $this->authGuru();
        $db = Db::pdo();
        $today = date('Y-m-d');
        $now = date('H:i:s');

        $keteranganLibur = $this->cekLibur($db, $today);
        if ($keteranganLibur) {
            $this->json(['message' => "Hari ini libur: $keteranganLibur. Anda tidak perlu absen."], 409);
        }

        // PERBAIKAN: Ambil data dari $_POST karena dikirim sebagai FormData dari mobile
        $lat = isset($_POST['lat']) && $_POST['lat'] !== '' ? (float)$_POST['lat'] : null;
        $lng = isset($_POST['lng']) && $_POST['lng'] !== '' ? (float)$_POST['lng'] : null;
        $fotoPath = isset($_POST['foto_path']) ? trim((string)$_POST['foto_path']) : null;

        $this->validatePresensiPayload($lat, $lng, $fotoPath);
        $cfg = $this->getMasterConfig($db);
        $batas = (string)($cfg['batas_terlambat'] ?? '08:15:00');

        if ($now > $batas) {
            $this->json(['message' => "Presensi masuk ditutup. Sudah melewati batas ($batas)."], 409);
        }

        $geo = $this->validateGeofence($cfg, $lat, $lng);
        if (!$geo['ok']) {
            $this->json(['message' => $geo['message'], 'geofence' => $geo], 422);
        }

        $id_presensi = $this->ensureDailyHeader($db, $today, $cfg);
        $isTerlambat = ($now > (string)($cfg['jam_masuk'] ?? '07:00:00')) ? 1 : 0;

        $cek = $db->prepare("SELECT id_detail, jam_masuk, jam_keluar FROM presensi_detail WHERE id_presensi = ? AND id_guru = ? LIMIT 1");
        $cek->execute([$id_presensi, $guru['id_guru']]);
        $row = $cek->fetch(PDO::FETCH_ASSOC);

        if ($row && !empty($row['jam_masuk']) && empty($row['jam_keluar'])) {
            $this->json(['message' => 'Sudah presensi masuk.'], 409);
        }

        if ($row) {
            $up = $db->prepare("UPDATE presensi_detail SET jam_masuk=?, status_kehadiran='HADIR', is_terlambat=?, lat_masuk=?, lng_masuk=?, foto_masuk_path=?, updated_at=NOW() WHERE id_presensi=? AND id_guru=?");
            $up->execute([$now, $isTerlambat, $lat, $lng, $fotoPath, $id_presensi, $guru['id_guru']]);
        } else {
            $id_detail = $this->uuid();
            $ins = $db->prepare("INSERT INTO presensi_detail (id_detail, id_presensi, id_guru, jam_masuk, status_kehadiran, is_terlambat, lat_masuk, lng_masuk, foto_masuk_path, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $ins->execute([$id_detail, $id_presensi, $guru['id_guru'], $now, 'HADIR', $isTerlambat, $lat, $lng, $fotoPath]);
        }

        $this->json(['message' => 'Presensi masuk berhasil', 'jam_masuk' => $now]);
    }

    public function pulang($req, $res): void
    {
        $this->optionsOk();
        $guru = $this->authGuru();
        $db = Db::pdo();
        $today = date('Y-m-d');
        $now = date('H:i:s');

        $keteranganLibur = $this->cekLibur($db, $today);
        if ($keteranganLibur) {
            $this->json(['message' => "Hari ini libur: $keteranganLibur."], 409);
        }

        // PERBAIKAN: Ambil data dari $_POST
        $lat = isset($_POST['lat']) && $_POST['lat'] !== '' ? (float)$_POST['lat'] : null;
        $lng = isset($_POST['lng']) && $_POST['lng'] !== '' ? (float)$_POST['lng'] : null;
        $fotoPath = isset($_POST['foto_path']) ? trim((string)$_POST['foto_path']) : null;

        $this->validatePresensiPayload($lat, $lng, $fotoPath);
        $cfg = $this->getMasterConfig($db);
        $jamPulang = (string)($cfg['jam_pulang'] ?? '15:30:00');

        if ($now < $jamPulang) {
            $this->json(['message' => "Presensi pulang belum dibuka. Mulai jam $jamPulang."], 409);
        }

        $geo = $this->validateGeofence($cfg, $lat, $lng);
        if (!$geo['ok']) {
            $this->json(['message' => $geo['message'], 'geofence' => $geo], 422);
        }

        $h = $db->prepare("SELECT id_presensi FROM kehadiran WHERE tanggal = ? LIMIT 1");
        $h->execute([$today]);
        $header = $h->fetch(PDO::FETCH_ASSOC);

        if (!$header) $this->json(['message' => 'Belum presensi masuk'], 409);

        $up = $db->prepare("UPDATE presensi_detail SET jam_keluar=?, lat_pulang=?, lng_pulang=?, foto_pulang_path=?, updated_at=NOW() WHERE id_presensi=? AND id_guru=?");
        $up->execute([$now, $lat, $lng, $fotoPath, $header['id_presensi'], $guru['id_guru']]);

        $this->json(['message' => 'Presensi pulang berhasil', 'jam_pulang' => $now]);
    }

    public function riwayat($req, $res): void
    {
        // FITUR RIWAYAT ANDA TETAP 100% SAMA
        $this->optionsOk();
        $guru = $this->authGuru();
        $db = Db::pdo();

        $mode = trim((string)($_GET['mode'] ?? 'bulanan'));
        $tanggal = trim((string)($_GET['tanggal'] ?? date('Y-m-d')));
        $bulan = str_pad((string)((int)($_GET['bulan'] ?? date('m'))), 2, '0', STR_PAD_LEFT);
        $tahun = trim((string)($_GET['tahun'] ?? date('Y')));

        if ($mode === 'harian') {
            $mulai = $tanggal;
            $selesai = $tanggal;
        } else {
            $mulai = $tahun . '-' . $bulan . '-01';
            $selesai = date('Y-m-t', strtotime($mulai));
        }

        $today = date('Y-m-d');
        $st = $db->prepare("
            SELECT k.tanggal, d.id_detail, d.jam_masuk, d.jam_keluar, d.status_kehadiran, d.is_terlambat, d.reward, d.sp
            FROM kehadiran k
            LEFT JOIN presensi_detail d ON d.id_presensi = k.id_presensi AND d.id_guru = :id_guru
            WHERE k.tanggal BETWEEN :mulai AND :selesai
            ORDER BY k.tanggal DESC
        ");
        $st->execute([':id_guru' => $guru['id_guru'], ':mulai' => $mulai, ':selesai' => $selesai]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $byDate = [];
        foreach ($rows as $r) { $byDate[$r['tanggal']] = $r; }

        $items = [];
        for ($ts = strtotime($mulai); $ts <= strtotime($selesai); $ts += 86400) {
            $tgl = date('Y-m-d', $ts);
            $r = $byDate[$tgl] ?? null;
            $ketLibur = $this->cekLibur($db, $tgl);

            if ($r && !empty($r['status_kehadiran'])) {
                $status = strtoupper((string)$r['status_kehadiran']);
            } elseif ($ketLibur) {
                $status = 'LIBUR';
            } else {
                $status = ($tgl > $today) ? 'BELUM_TERJADI' : 'TIDAK_HADIR';
            }

            $items[] = [
                'id_detail' => $r['id_detail'] ?? ('none-' . $tgl),
                'tanggal' => $tgl,
                'jam_masuk' => $r['jam_masuk'] ?? null,
                'jam_pulang' => $r['jam_keluar'] ?? null,
                'status' => $status,
                'is_terlambat' => (int)($r['is_terlambat'] ?? 0),
                'keterangan_libur' => $ketLibur,
            ];
        }

        usort($items, function ($a, $b) { return strcmp($b['tanggal'], $a['tanggal']); });

        $this->json([
            'mode' => $mode,
            'mulai' => $mulai,
            'selesai' => $selesai,
            'items' => $items,
        ]);
    }

    public function resetRiwayat($req, $res): void
    {
        $this->optionsOk();
        $guru = $this->authGuru();
        $db = Db::pdo();
        $del = $db->prepare("DELETE FROM presensi_detail WHERE id_guru = ?");
        $del->execute([$guru['id_guru']]);
        $this->json(['ok' => true, 'message' => 'Riwayat presensi berhasil direset.']);
    }
}