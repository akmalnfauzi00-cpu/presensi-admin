<?php

// Sertakan autoloader Composer untuk DOMPDF
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class RewardSpController
{
    private string $table = 'reward_sp_dokumen';

    private function base(): string
    {
        $b = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        return $b === '/' ? '' : $b;
    }

    private function uuid(): string 
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function index($req, $res): void
    {
        if (class_exists('Auth') && !Auth::check()) {
            $res->redirect('/login');
            return;
        }

        $pdo = Db::pdo();
        
        $stmtGuru = $pdo->query("SELECT id_guru, nama_guru, nip FROM guru ORDER BY nama_guru ASC");
        $guru = $stmtGuru->fetchAll(PDO::FETCH_ASSOC);

        $stmtDocs = $pdo->query("
            SELECT d.*, g.nama_guru, g.nip 
            FROM reward_sp_dokumen d
            JOIN guru g ON d.id_guru = g.id_guru
            ORDER BY d.dibuat_pada DESC 
            LIMIT 50
        ");
        $docs = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'Kelola Reward & SP';
        $contentFile = __DIR__ . '/../views/rewardsp/index.php';
        require __DIR__ . '/../views/layouts/admin.php';
    }

    public function create($req, $res): void
    {
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

        $cek = $pdo->prepare("SELECT * FROM guru WHERE id_guru = ? LIMIT 1");
        $cek->execute([$id_guru]);
        $guruData = $cek->fetch(PDO::FETCH_ASSOC);

        if (!$guruData) {
            Session::flash('error', 'Guru tidak ditemukan.');
            header("Location: {$base}/rewardsp"); exit;
        }

        $jenis = strtoupper(trim($_POST['jenis'] ?? 'REWARD'));
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $periode = trim($_POST['periode'] ?? date('Y-m'));

        $jenis_cetak = $jenis;
        if ($jenis === 'SP') {
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM reward_sp_dokumen WHERE id_guru = ? AND jenis = 'SP'");
            $stmtCount->execute([$id_guru]);
            $sp_level = (int)$stmtCount->fetchColumn() + 1;

            if ($sp_level == 1) $jenis_cetak = 'SP 1';
            elseif ($sp_level == 2) $jenis_cetak = 'SP 2';
            elseif ($sp_level == 3) $jenis_cetak = 'SP 3';
            else $jenis_cetak = 'PANGGILAN';

            $deskripsi = "[$jenis_cetak] " . $deskripsi;
        }

        $id_dokumen = $this->uuid();
        $fileName = 'surat_' . strtolower($jenis) . '_' . time() . '.pdf';
        $relativePath = '/uploads/rewardsp/' . $fileName;
        $absolutePath = __DIR__ . '/../../public' . $relativePath;

        if (!is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0777, true);
        }

        $pdfContent = $this->generatePDFContent($guruData, $periode, $jenis, $deskripsi, $jenis_cetak);
        file_put_contents($absolutePath, $pdfContent);

        $dibuat_oleh = $_SESSION['auth_user']['id_user'] ?? ''; 
        $stmt = $pdo->prepare("
            INSERT INTO reward_sp_dokumen 
            (id_dokumen, id_guru, periode, jenis, deskripsi, file_pdf_path, status_unduh, dibuat_oleh, dibuat_pada) 
            VALUES (?, ?, ?, ?, ?, ?, 'BELUM_DIUNDUH', ?, NOW())
        ");
        $stmt->execute([$id_dokumen, $id_guru, $periode, $jenis, $deskripsi, $relativePath, $dibuat_oleh]);

        Session::flash('success', "Dokumen {$jenis_cetak} berhasil diterbitkan.");
        header("Location: {$base}/rewardsp");
        exit;
    }

    private function generatePDFContent($guru, $periode, $jenis, $deskripsi, $jenis_cetak)
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true); 
        $dompdf = new Dompdf($options);

        // --- ENCODE IMAGES TO BASE64 ---
        $logoPath = __DIR__ . '/../../public/assets/img/logo-muh.png';
        $capPath  = __DIR__ . '/../../public/assets/img/cap-sekolah.png';
        $ttdPath  = __DIR__ . '/../../public/assets/img/ttd.png';

        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
        $capBase64  = file_exists($capPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($capPath)) : '';
        $ttdBase64  = file_exists($ttdPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($ttdPath)) : '';

        $namaSurat = ($jenis === 'REWARD') ? 'SURAT PENGHARGAAN' : "SURAT PERINGATAN ({$jenis_cetak})";
        $deskripsiTampil = str_replace(["[SP 1] ", "[SP 2] ", "[SP 3] ", "[PANGGILAN] "], "", $deskripsi);

        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.4; color: #000; margin: 0; padding: 0; }
                .kop-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
                .kop-logo { width: 85px; text-align: center; }
                .kop-text { text-align: center; }
                .kop-text .m-1 { font-size: 11pt; font-weight: bold; margin: 0; }
                .kop-text .m-2 { font-size: 11pt; font-weight: bold; margin: 0; }
                .kop-text .m-3 { font-size: 15pt; font-weight: 900; margin: 2px 0; }
                .kop-text .m-4 { font-size: 12pt; font-weight: bold; font-style: italic; margin: 0; }
                .kop-text .m-5 { font-size: 9pt; margin-top: 5px; }
                .line-double { border-top: 2px solid #000; border-bottom: 1px solid #000; height: 3px; margin-top: 5px; margin-bottom: 20px; }
                
                .content { padding: 0 40px; }
                .judul-surat { text-align: center; margin-bottom: 25px; }
                .judul-surat h2 { text-decoration: underline; font-size: 14pt; margin-bottom: 5px; text-transform: uppercase; }
                
                .identitas { margin: 20px 0; width: 100%; }
                .identitas td { padding: 3px 0; vertical-align: top; }
                
                .footer-table { width: 100%; margin-top: 50px; }
                .ttd-container { width: 250px; text-align: center; position: relative; }
                .cap-img { position: absolute; width: 115px; left: -15px; top: -10px; z-index: 1; opacity: 0.85; }
                .ttd-img { position: relative; width: 130px; z-index: 2; }
            </style>
        </head>
        <body>
            <!-- KOP SURAT SESUAI GAMBAR -->
            <table class="kop-table">
                <tr>
                    <td class="kop-logo"><img src="{$logoBase64}" width="130"></td>
                    <td class="kop-text">
                        <div class="m-1">MAJELIS PENDIDIKAN DASAR MENENGAH DAN PENDIDIKAN NONFORMAL</div>
                        <div class="m-2">PIMPINAN DAERAH MUHAMMADIYAH BANYUMAS</div>
                        <div class="m-3">SMP MUHAMMADIYAH 2 KARANGLEWAS</div>
                        <div class="m-4">Terakreditasi " A "</div>
                        <div class="m-5">
                            Jalan Jaya Diwangsa No. 43 Telp. (0281) 641264 Karanglewas Kode Pos 53161<br>
                            Email: <span style="color: blue; text-decoration: underline;">smpmuh2krlws@yahoo.co.id</span>
                        </div>
                    </td>
                </tr>
            </table>
            <div class="line-double"></div>

            <div class="content">
                <div class="judul-surat">
                    <h2>{$namaSurat}</h2>
                    <div>Nomor : 008/ST.TGS/SMP.M2/IV/2026</div>
                </div>

                <p>Kepada Yth,</p>
                <table class="identitas">
                    <tr><td width="120">Nama</td><td width="15">:</td><td><b>{$guru['nama_guru']}</b></td></tr>
                    <tr><td>KTA</td><td>:</td><td>{$guru['nip']}</td></tr>
                    <tr><td>Jabatan</td><td>:</td><td>Tenaga Pendidik (Guru)</td></tr>
                </table>

                <p>Berdasarkan hasil evaluasi kehadiran dan kedisiplinan kerja pada periode <b>{$periode}</b>, maka dengan ini Sekolah memberikan teguran tertulis berupa {$jenis_cetak} dikarenakan:</p>
                
                <div style="margin: 20px 0; padding: 15px; border: 1px solid #000; font-style: italic; text-align: center;">
                    "{$deskripsiTampil}"
                </div>

                <p>Demikian surat ini dibuat untuk dapat diperhatikan. Atas perhatian dan kerjasamanya, kami ucapkan terima kasih.</p>

                <table class="footer-table">
                    <tr>
                        <td width="60%"></td>
                        <td class="ttd-container">
                            <p>Karanglewas, 30 April 2026</p>
                            <p><b>Kepala Sekolah,</b></p>
                            
                            <div style="height: 90px; margin: 10px 0;">
                                <!-- CAP SEKOLAH[cite: 1] -->
                                <img src="{$capBase64}" class="cap-img">
                                <img src="{$ttdBase64}" class="ttd-img">
                            </div>

                            <p style="text-decoration: underline; font-weight: bold; margin-bottom: 0;">ELOK ASTIKA.S.Pd.</p>
                            <p style="margin-top: 0; font-size: 10pt;">NBM. 922573</p>
                        </td>
                    </tr>
                </table>
            </div>
        </body>
        </html>
HTML;

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    public function download($req, $res): void
    {
        if (class_exists('Auth') && !Auth::check()) {
            $res->redirect('/login');
            return;
        }

        $id = trim($_GET['id'] ?? '');
        $pdo = Db::pdo();
        $stmt = $pdo->prepare("SELECT * FROM reward_sp_dokumen WHERE id_dokumen = ? LIMIT 1");
        $stmt->execute([$id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc || !file_exists(__DIR__ . '/../../public' . $doc['file_pdf_path'])) {
            die("File tidak ditemukan.");
        }

        $path = __DIR__ . '/../../public' . $doc['file_pdf_path'];
        if (ob_get_contents()) ob_end_clean();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        readfile($path);
        exit;
    }

    public function reset($req, $res): void
    {
        if (class_exists('Auth') && !Auth::check()) {
            $res->redirect('/login');
            return;
        }

        $pdo = Db::pdo();
        $stmt = $pdo->query("SELECT file_pdf_path FROM reward_sp_dokumen");
        $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($docs as $doc) {
            $absolutePath = __DIR__ . '/../../public' . $doc['file_pdf_path'];
            if (is_file($absolutePath)) @unlink($absolutePath);
        }

        $pdo->exec("DELETE FROM reward_sp_dokumen");
        Session::flash('success', 'Riwayat berhasil direset.');
        header("Location: " . $this->base() . "/rewardsp");
        exit;
    }
}