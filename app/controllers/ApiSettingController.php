<?php

require_once '../app/core/Db.php';

class ApiSettingController
{
    /**
     * Helper untuk mengirim response JSON yang bersih
     */
    private function json($data, int $code = 200): void {
        // MENGHAPUS output apapun yang tidak sengaja muncul sebelumnya (seperti PHP Notice)
        if (ob_get_length()) ob_clean(); 

        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Menangani pre-flight request untuk CORS
     */
    private function optionsOk(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            $this->json(['ok' => true], 200);
        }
    }

    /**
     * Endpoint utama untuk mengambil semua pengaturan sistem
     */
    public function index($req, $res): void {
        $this->optionsOk();

        try {
            $db = Db::pdo();
            
            // 1. AMBIL SETTINGAN TERBARU (Tabel presensi_master)
            $st = $db->query("SELECT * FROM presensi_master ORDER BY created_at DESC LIMIT 1");
            $cfg = $st->fetch(PDO::FETCH_ASSOC);

            // Jika tabel kosong, gunakan nilai default agar aplikasi mobile tidak crash
            if (!$cfg) {
                $cfg = [
                    'jam_masuk' => '07:00:00',
                    'jam_pulang' => '15:30:00',
                    'batas_terlambat' => '08:15:00',
                    'toleransi_terlambat' => 30,
                    'minimal_hadir_reward' => 22,
                    'minimal_tidak_hadir_sp' => 3,
                    'maksimal_terlambat_sp' => 3, // Default Fitur Baru
                    'latitude' => null,
                    'longitude' => null,
                    'radius_meter' => 30,
                ];
            }

            // 2. PENGECEKAN HARI LIBUR (DB + AKHIR PEKAN)
            $hariIni = date('Y-m-d');
            $namaHari = date('l', strtotime($hariIni));
            
            $isLibur = false;
            $keteranganLibur = "";

            // Cek apakah tanggal hari ini terdaftar di tabel hari_libur
            $stLibur = $db->prepare("SELECT keterangan FROM hari_libur WHERE tanggal = ? LIMIT 1");
            $stLibur->execute([$hariIni]);
            $liburDb = $stLibur->fetch();

            if ($liburDb) {
                $isLibur = true;
                $keteranganLibur = $liburDb['keterangan'];
            } elseif ($namaHari === 'Saturday' || $namaHari === 'Sunday') {
                // Deteksi otomatis Sabtu & Minggu sebagai libur
                $isLibur = true;
                $hariIndo = ($namaHari === 'Saturday') ? 'Sabtu' : 'Minggu';
                $keteranganLibur = "Hari Libur Akhir Pekan ($hariIndo)";
            }

            // 3. FORMAT PAYLOAD JAM (Format H:i untuk kemudahan tampilan di mobile)
            $payloadJam = [
                'jam_masuk' => date('H:i', strtotime($cfg['jam_masuk'])),
                'jam_pulang' => date('H:i', strtotime($cfg['jam_pulang'])),
                'batas_terlambat' => date('H:i', strtotime($cfg['batas_terlambat'])),
                'toleransi_terlambat' => (int)($cfg['toleransi_terlambat'] ?? 0),
            ];

            // 4. FORMAT PAYLOAD LOKASI SEKOLAH
            $payloadSekolah = [
                'nama' => 'SMP Muhammadiyah 2 Karanglewas',
                'lat' => isset($cfg['latitude']) ? (float)$cfg['latitude'] : null,
                'lng' => isset($cfg['longitude']) ? (float)$cfg['longitude'] : null,
                'radius_meter' => (int)($cfg['radius_meter'] ?? 30),
            ];

            // 5. FITUR SINKRONISASI REWARD & SP (DIPERBARUI)
            // Mengambil data hasil inputan Admin di tab 'Reward & SP'
            $payloadReward = [
                'min_hadir_reward' => (int)($cfg['minimal_hadir_reward'] ?? 22),
                'max_alpha_sp'     => (int)($cfg['minimal_tidak_hadir_sp'] ?? 3),
                'max_late_sp'      => (int)($cfg['maksimal_terlambat_sp'] ?? 3), // Sinkron dengan fitur terlambat
            ];

            // 6. RESPONSE AKHIR (Menggabungkan semua fitur)
            $finalResponse = [
                'jam' => $payloadJam,
                'sekolah' => $payloadSekolah,
                'reward_rules' => $payloadReward,
                'is_libur' => $isLibur,
                'keterangan_libur' => $keteranganLibur
            ];

            $this->json([
                'status' => 'success',
                'data'   => $finalResponse,
                // Shortcut agar mobile dev bisa akses langsung res.jam atau res.data.jam
                'jam'              => $payloadJam,
                'sekolah'          => $payloadSekolah,
                'reward_rules'     => $payloadReward,
                'is_libur'         => $isLibur,
                'keterangan_libur' => $keteranganLibur
            ]);

        } catch (Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }
}