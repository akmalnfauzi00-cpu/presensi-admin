<?php

class ApiPengajuanController
{
    private function json($data, int $code = 200): void {
        if (ob_get_length()) ob_clean();
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function optionsOk(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            $this->json(['ok' => true], 200);
        }
    }

    private function getBearerToken(): ?string {
        $token = null;
        $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
        if (!$hdr && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $hdr = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        if (preg_match('/Bearer\s+(\S+)/i', $hdr, $m)) {
            $token = $m[1];
        } else {
            $token = $_REQUEST['api_token'] ?? null;
        }
        return $token;
    }

    private function authGuru(): array {
        $token = $this->getBearerToken();
        if (!$token) $this->json(['message' => 'Sesi login berakhir. Silakan login kembali.'], 401);
        $db = Db::pdo();
        $st = $db->prepare("SELECT id_guru FROM guru WHERE api_token=? LIMIT 1");
        $st->execute([$token]);
        $guru = $st->fetch(PDO::FETCH_ASSOC);
        if (!$guru) $this->json(['message' => 'Sesi login berakhir. Silakan login kembali.'], 401);
        return $guru;
    }

    public function index($req, $res): void {
        $this->optionsOk();
        $guru = $this->authGuru();
        $db = Db::pdo();
        $st = $db->prepare("SELECT id_pengajuan, jenis, tanggal_mulai, tanggal_selesai, alasan, lampiran_path, status_verifikasi, catatan_admin, created_at FROM pengajuan_presensi WHERE id_guru=? ORDER BY created_at DESC LIMIT 100");
        $st->execute([$guru['id_guru']]);
        $this->json(['items' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function store($req, $res): void {
        $this->optionsOk();
        $guru = $this->authGuru();
        $db = Db::pdo();

        // 1. Ambil Konfigurasi Batasan dari Database (presensi_master)
        $stConfig = $db->query("SELECT maks_izin, block_pending FROM presensi_master ORDER BY created_at DESC LIMIT 1");
        $cfg = $stConfig->fetch(PDO::FETCH_ASSOC);
        
        $maksIzin = (int)($cfg['maks_izin'] ?? 3);
        $shouldBlockPending = (int)($cfg['block_pending'] ?? 1);

        // 2. Cek Blokir Jika Masih Ada Status 'MENUNGGU'
        if ($shouldBlockPending === 1) {
            $stPending = $db->prepare("SELECT COUNT(*) FROM pengajuan_presensi WHERE id_guru = ? AND status_verifikasi = 'MENUNGGU'");
            $stPending->execute([$guru['id_guru']]);
            if ((int)$stPending->fetchColumn() > 0) {
                // Menggunakan 400 agar ditangkap oleh aplikasi mobile sebagai pesan kesalahan logika
                $this->json(['message' => 'Gagal! Anda masih memiliki pengajuan yang berstatus MENUNGGU.'], 400);
            }
        }

        // 3. Cek Batas Maksimal Pengajuan per Bulan Berjalan
        $bulanIni = date('Y-m');
        $stCount = $db->prepare("SELECT COUNT(*) FROM pengajuan_presensi WHERE id_guru = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?");
        $stCount->execute([$guru['id_guru'], $bulanIni]);

        if ((int)$stCount->fetchColumn() >= $maksIzin) {
            $this->json([
                'status' => 'error',
                'message' => "Maaf, Anda sudah mencapai batas maksimal pengajuan bulan ini ($maksIzin kali)."
            ], 400); 
        }

        // 4. Proses Data Input
        $jenis = strtoupper(trim($_POST['jenis'] ?? ''));
        $tglMulai = $_POST['tanggal_mulai'] ?? '';
        $tglSelesai = $_POST['tanggal_selesai'] ?? '';
        $alasan = trim($_POST['alasan'] ?? '');
        $lampiranPath = null;

        if (!$tglMulai || !$alasan || !$jenis) {
            $this->json(['message' => 'Data tidak lengkap. Jenis, tanggal, dan alasan wajib diisi.'], 422);
        }

        // 5. Proses Upload File Lampiran
        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['lampiran']['name'], PATHINFO_EXTENSION);
            $fileName = time() . '_' . uniqid() . '.' . $ext;
            $uploadDir = dirname(__DIR__, 2) . '/public/uploads/pengajuan/';
            
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            if (move_uploaded_file($_FILES['lampiran']['tmp_name'], $uploadDir . $fileName)) {
                $lampiranPath = '/uploads/pengajuan/' . $fileName;
            }
        }

        // 6. Simpan ke Database
        $id = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
        $sql = "INSERT INTO pengajuan_presensi 
                (id_pengajuan, id_guru, jenis, tanggal_mulai, tanggal_selesai, alasan, lampiran_path, status_verifikasi, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'MENUNGGU', NOW())";
        
        try {
            $ins = $db->prepare($sql);
            $ins->execute([
                $id, 
                $guru['id_guru'], 
                $jenis, 
                $tglMulai, 
                (!empty($tglSelesai) ? $tglSelesai : $tglMulai), 
                $alasan, 
                $lampiranPath
            ]);
            
            $this->json(['message' => 'Pengajuan berhasil dikirim', 'id' => $id], 201);
        } catch (PDOException $e) {
            $this->json(['message' => 'Gagal menyimpan data ke database. Error: ' . $e->getMessage()], 500);
        }
    }
}