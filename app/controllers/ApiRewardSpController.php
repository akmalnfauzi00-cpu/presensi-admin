<?php

class ApiRewardSpController
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
        $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!$hdr && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $hdr = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        if (!$hdr) return null;
        if (preg_match('/Bearer\s+(\S+)/i', $hdr, $m)) return $m[1];
        return null;
    }

    private function authGuru(): array {
        $token = $this->getBearerToken() ?? trim((string)($_GET['token_preview'] ?? ''));
        if (!$token) $this->json(['message' => 'Unauthorized'], 401);
        
        $db = Db::pdo();
        $st = $db->prepare("SELECT id_guru, nip, nama_guru, status_aktif FROM guru WHERE api_token = ? LIMIT 1");
        $st->execute([$token]);
        $guru = $st->fetch(PDO::FETCH_ASSOC);
        
        if (!$guru) $this->json(['message' => 'Unauthorized'], 401);
        return $guru;
    }

    private function hitungBulanan(PDO $db, string $idGuru, string $periode): array {
        $mulai = $periode . '-01';
        $selesai = date('Y-m-t', strtotime($mulai));
        
        $st = $db->prepare("
            SELECT 
                SUM(CASE WHEN pd.status_kehadiran = 'HADIR' THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN pd.status_kehadiran = 'IZIN' THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN pd.status_kehadiran = 'SAKIT' THEN 1 ELSE 0 END) as sakit
            FROM presensi_detail pd
            JOIN kehadiran k ON k.id_presensi = pd.id_presensi
            WHERE pd.id_guru = ? AND k.tanggal BETWEEN ? AND ?
        ");
        $st->execute([$idGuru, $mulai, $selesai]);
        $res = $st->fetch(PDO::FETCH_ASSOC);

        return [
            'hadir' => (int)($res['hadir'] ?? 0),
            'izin' => (int)($res['izin'] ?? 0),
            'sakit' => (int)($res['sakit'] ?? 0),
            'tidak_hadir' => 0 
        ];
    }

    public function me($req, $res): void {
        $this->optionsOk();
        $guru = $this->authGuru();
        $db = Db::pdo();
        
        $periode = $_GET['periode'] ?? date('Y-m');
        $stat = $this->hitungBulanan($db, $guru['id_guru'], $periode);

        $st = $db->prepare("SELECT id_dokumen, periode, jenis, deskripsi, status_unduh FROM reward_sp_dokumen WHERE id_guru = ? AND periode = ? ORDER BY dibuat_pada DESC");
        $st->execute([$guru['id_guru'], $periode]);
        $docs = $st->fetchAll(PDO::FETCH_ASSOC);

        $rewardDocs = []; $spDocs = [];
        foreach ($docs as $d) {
            // Normalisasi teks jenis agar tidak sensitif huruf besar/kecil
            $jenis = strtoupper(trim($d['jenis']));
            if ($jenis === 'REWARD') $rewardDocs[] = $d;
            if ($jenis === 'SP') $spDocs[] = $d;
        }

        $this->json([
            'status' => 'success', // Tambahan agar frontend tahu response sukses
            'periode' => $periode,
            'statistik' => $stat,
            'reward' => ['dokumen' => $rewardDocs],
            'sp' => ['dokumen' => $spDocs]
        ]);
    }

    // --- TAMBAHKAN METHOD DOWNLOAD INI AGAR TIDAK ERROR FATAL ---
    public function download($req, $res): void {
        $this->optionsOk();
        $guru = $this->authGuru();
        $db = Db::pdo();
        $id = $_GET['id'] ?? '';
        
        $st = $db->prepare("SELECT * FROM reward_sp_dokumen WHERE id_dokumen = ? AND id_guru = ? LIMIT 1");
        $st->execute([$id, $guru['id_guru']]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            die('Dokumen tidak ditemukan.');
        }

        $filePath = __DIR__ . '/../../public' . $row['file_pdf_path'];
        
        if (!is_file($filePath)) {
            die('File fisik tidak ditemukan.');
        }

        // Update status unduh di database
        $db->prepare("UPDATE reward_sp_dokumen SET status_unduh='SUDAH_DIUNDUH' WHERE id_dokumen=?")->execute([$id]);

        header("Content-Type: application/pdf");
        header('Content-Disposition: attachment; filename="'.basename($row['file_pdf_path']).'"');
        readfile($filePath);
        exit;
    }
}