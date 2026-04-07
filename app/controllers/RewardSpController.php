<?php
// Sertakan autoloader Composer untuk DOMPDF
require_once __DIR__ . '/../vendor/autoload.php';  // Sesuaikan dengan path autoloader Composer Anda

use Dompdf\Dompdf;
use Dompdf\Options;

class RewardSpController
{
    private string $table = 'reward_sp_dokumen';

    // Fungsi untuk membuat dokumen Reward/SP PDF menggunakan DOMPDF
    public function generatePDF($guru, $periode, $jenis, $deskripsi)
    {
        // Konfigurasi DOMPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        $dompdf = new Dompdf($options);

        // Data untuk surat
        $data = [
            'nama' => $guru['nama_guru'],  // Nama guru dari data yang diterima
            'nip' => $guru['nip'],  // NIP guru
            'jenis' => $jenis,  // Reward atau SP
            'periode' => $periode,  // Periode
            'deskripsi' => $deskripsi,  // Deskripsi dari input admin
            'tanggal' => date('d F Y')  // Tanggal surat
        ];

        // HTML untuk konten PDF
        $html = '
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; font-size: 12px; }
                    h1 { text-align: center; }
                    .content { margin-top: 20px; }
                    .footer { margin-top: 50px; text-align: center; }
                </style>
            </head>
            <body>
                <h1>SURAT PEMBERIAN ' . strtoupper($data['jenis']) . '</h1>
                <p>Nomor: 08.006/RJS/II/2024</p>
                <div class="content">
                    <p>Kepada:</p>
                    <p>Nama: ' . $data['nama'] . '</p>
                    <p>NIP: ' . $data['nip'] . '</p>
                    <p>Periode: ' . $data['periode'] . '</p>
                    <p>' . $data['deskripsi'] . '</p>
                    <p>Oleh karena itu, surat peringatan ini diberikan sebagai teguran keras kepada ' . $data['nama'] . '.</p>
                </div>
                <div class="footer">
                    <p>HRD Manager</p>
                    <p>( Toni Wijaya, M.H. )</p>
                </div>
            </body>
            </html>
        ';

        // Muat HTML ke DOMPDF
        $dompdf->loadHtml($html);

        // Set kertas dan orientasi
        $dompdf->setPaper('A4', 'portrait');

        // Render PDF
        $dompdf->render();

        // Output PDF ke browser (unduh)
        $dompdf->stream("surat_peringatan_" . $data['nama'] . ".pdf", array("Attachment" => 1));  // 1 berarti mengunduh file
    }

    // Fungsi untuk menangani rute /rewardsp/create (POST)
    public function create($req, $res): void
    {
        // Verifikasi login jika diperlukan
        if (class_exists('Auth') && !Auth::check()) {
            $res->redirect('/login');
            return;
        }

        $base = $this->base();
        $pdo  = Db::pdo();

        $id_guru = trim($_POST['guru_pick'] ?? '');
        if ($id_guru === '') {
            Session::flash('error', 'Silakan pilih guru terlebih dahulu.');
            header("Location: {$base}/rewardsp"); exit;
        }

        $cek = $pdo->prepare("SELECT 1 FROM guru WHERE id_guru = ? LIMIT 1");
        $cek->execute([$id_guru]);
        if (!$cek->fetchColumn()) {
            Session::flash('error', 'Guru tidak ditemukan.');
            header("Location: {$base}/rewardsp"); exit;
        }

        $jenis = strtoupper(trim($_POST['jenis'] ?? 'REWARD'));
        if (!in_array($jenis, ['REWARD', 'SP'], true)) {
            Session::flash('error', 'Jenis dokumen tidak valid.');
            header("Location: {$base}/rewardsp"); exit;
        }

        $deskripsi = trim($_POST['deskripsi'] ?? '');

        $periode = trim($_POST['periode'] ?? '');
        if ($periode === '') $periode = date('Y-m'); // default

        // Ambil data guru untuk membuat PDF
        $guru = $pdo->query("SELECT * FROM guru WHERE id_guru = ? LIMIT 1", [$id_guru])->fetch(PDO::FETCH_ASSOC);

        // Menghasilkan PDF menggunakan DOMPDF
        $this->generatePDF($guru, $periode, $jenis, $deskripsi);

        // Simpan dokumen di database jika perlu (tidak diubah di sini)
        // Tambahkan logika penyimpanan file PDF ke dalam database jika diperlukan
    }
}
?>