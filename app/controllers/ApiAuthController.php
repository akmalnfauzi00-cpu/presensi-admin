<?php

/**
 * Memanggil file PHPMailer secara manual dari folder libs.
 * Pastikan folder 'phpmailer' ada di dalam folder 'libs' di root project Anda.
 */
require_once __DIR__ . '/../../libs/phpmailer/Exception.php';
require_once __DIR__ . '/../../libs/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../../libs/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class ApiAuthController
{
    /**
     * Helper untuk mengirim response JSON yang stabil untuk Mobile
     */
    private function json($data, int $code = 200): void
    {
        if (ob_get_length()) ob_clean();
        
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *'); 
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
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
        $token = null;
        $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
        if (!$hdr && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $hdr = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        if (!$hdr) return null;
        if (preg_match('/Bearer\s+(\S+)/i', $hdr, $m)) {
            $token = $m[1];
        } else {
            $token = $_REQUEST['api_token'] ?? null;
        }
        return $token;
    }

    /**
     * LOGIN GURU (API) - Diperbarui agar response sinkron dengan Mobile
     */
    public function login($req, $res): void
    {
        $this->optionsOk();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        
        // Menampung input NIP atau Username
        $nip  = trim((string)($body['nip'] ?? $body['username'] ?? ''));
        $pass = (string)($body['password'] ?? '');

        if ($nip === '' || $pass === '') {
            $this->json(['message' => 'NIP dan password wajib diisi'], 422);
        }

        $db = Db::pdo();
        // Pastikan nama kolom password_hash sesuai gambar database
        $st = $db->prepare("SELECT id_guru, nip, nama_guru, password_hash, status_aktif FROM guru WHERE nip=? LIMIT 1");
        $st->execute([$nip]);
        $guru = $st->fetch(PDO::FETCH_ASSOC);

        if (!$guru) {
            $this->json(['message' => 'NIP atau password salah'], 401);
        }

        // Cek Status (Harus AKTIF agar bisa masuk)
        $statusCurrent = strtoupper(trim($guru['status_aktif'] ?? ''));
        if ($statusCurrent === 'PENDING') {
            $this->json(['message' => 'Akun Anda belum diverifikasi oleh Admin.'], 403);
        }
        if ($statusCurrent === 'NONAKTIF') {
            $this->json(['message' => 'Akun Anda sedang dinonaktifkan.'], 403);
        }

        // Verifikasi Password Hash
        $hash = (string)($guru['password_hash'] ?? '');
        if ($hash === '' || !password_verify($pass, $hash)) {
            $this->json(['message' => 'NIP atau password salah'], 401);
        }

        // Generate API Token Baru
        $token = bin2hex(random_bytes(32));
        $up = $db->prepare("UPDATE guru SET api_token=?, updated_at=NOW() WHERE id_guru=?");
        $up->execute([$token, $guru['id_guru']]);

        // Memberikan response lengkap ke aplikasi mobile
        $this->json([
            'status'  => 'success',
            'message' => 'Login Berhasil',
            'token'   => $token,
            'guru'    => [
                'id_guru'   => $guru['id_guru'],
                'nip'       => $guru['nip'],
                'nama_guru' => $guru['nama_guru'],
            ]
        ]);
    }

    /**
     * REGISTER GURU (API)
     */
    public function register($req, $res): void
    {
        $this->optionsOk();
        $nama     = trim((string)($_POST['nama_guru'] ?? ''));
        $nip      = trim((string)($_POST['nip'] ?? ''));
        $pass     = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['confirm_password'] ?? ''); 
        $jabatan  = trim((string)($_POST['jabatan'] ?? 'GURU'));
        $mapel    = trim((string)($_POST['mata_pelajaran'] ?? ''));
        $jk       = trim((string)($_POST['jenis_kelamin'] ?? ''));
        $no_hp    = trim((string)($_POST['no_hp'] ?? ''));
        $email    = trim((string)($_POST['email'] ?? ''));
        $alamat   = trim((string)($_POST['alamat'] ?? ''));

        if ($nama === '' || $nip === '' || $pass === '' || $jk === '') {
            $this->json(['message' => 'Nama, NIP, Password, dan Jenis Kelamin wajib diisi'], 422);
        }
        if ($pass !== $confirm) {
            $this->json(['message' => 'Konfirmasi password tidak cocok!'], 422);
        }

        $db = Db::pdo();
        $cek = $db->prepare("SELECT id_guru FROM guru WHERE nip = ?");
        $cek->execute([$nip]);
        if ($cek->fetch()) {
            $this->json(['message' => 'NIP sudah digunakan'], 422);
        }

        $fotoName = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fotoName = $nip . '_' . time() . '.' . $ext;
            $dir = dirname(__DIR__, 2) . '/public/uploads/guru';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            move_uploaded_file($_FILES['foto']['tmp_name'], $dir . '/' . $fotoName);
        }

        try {
            $id_guru = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $sql = "INSERT INTO guru (id_guru, nip, password_hash, nama_guru, jabatan, mata_pelajaran, jenis_kelamin, alamat, no_hp, email, foto, status_aktif, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', NOW())";
            $ins = $db->prepare($sql);
            $ins->execute([$id_guru, $nip, $hash, $nama, $jabatan, $mapel, $jk, $alamat, $no_hp, $email, $fotoName]);

            $this->json(['status' => 'success', 'message' => 'Registrasi berhasil! Menunggu verifikasi Admin.']);
        } catch (Exception $e) {
            $this->json(['message' => 'Gagal simpan database: ' . $e->getMessage()], 500);
        }
    }

    /**
     * FORGOT PASSWORD
     */
    public function forgotPassword($req, $res): void
    {
        $this->optionsOk();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $nip  = trim((string)($body['nip'] ?? ''));

        if ($nip === '') {
            $this->json(['message' => 'NIP wajib diisi'], 422);
        }

        $db = Db::pdo();
        
        $st = $db->prepare("SELECT 'users' as sumber, id_user as id, email, nama FROM users WHERE nip = ? LIMIT 1");
        $st->execute([$nip]);
        $target = $st->fetch(PDO::FETCH_ASSOC);

        if (!$target) {
            $st = $db->prepare("SELECT 'guru' as sumber, id_guru as id, email, nama_guru as nama FROM guru WHERE nip = ? LIMIT 1");
            $st->execute([$nip]);
            $target = $st->fetch(PDO::FETCH_ASSOC);
        }

        if (!$target || empty($target['email'])) {
            $this->json(['message' => 'NIP tidak ditemukan atau email belum diatur.'], 404);
        }

        $otp = (string)rand(111111, 999999);
        $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        if ($target['sumber'] === 'users') {
            $up = $db->prepare("UPDATE users SET reset_token = ?, token_expiry = ? WHERE id_user = ?");
        } else {
            $up = $db->prepare("UPDATE guru SET reset_token = ?, token_expiry = ? WHERE id_guru = ?");
        }
        $up->execute([$otp, $expiry, $target['id']]);

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'akmalnfauzi00@gmail.com'; 
            $mail->Password   = 'idlbthttyqmdhgtv'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('admin@smpmuh2.sch.id', 'Sistem Presensi');
            $mail->addAddress($target['email'], $target['nama']);

            $mail->isHTML(true);
            $mail->Subject = 'Kode OTP Reset Password';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                    <h2 style='color: #2563eb;'>Verifikasi Reset Password</h2>
                    <p>Halo <b>{$target['nama']}</b>,</p>
                    <p>Gunakan kode OTP di bawah ini untuk mereset kata sandi Anda:</p>
                    <div style='background: #f3f4f6; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; color: #1d4ed8; letter-spacing: 8px; border-radius: 8px;'>
                        $otp
                    </div>
                    <p style='color: #ef4444; font-size: 13px; margin-top: 20px; font-weight: bold;'>
                        *Kode ini berlaku selama 15 menit.
                    </p>
                </div>
            ";

            $mail->send();
            $this->json(['status' => 'success', 'message' => 'Kode OTP telah dikirim ke email: ' . $target['email']]);

        } catch (Exception $e) {
            $this->json(['message' => 'Gagal mengirim email: ' . $mail->ErrorInfo], 500);
        }
    }

    /**
     * RESET PASSWORD
     */
    public function resetPassword($req, $res): void
    {
        $this->optionsOk();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $nip  = trim((string)($body['nip'] ?? ''));
        $otp  = trim((string)($body['otp'] ?? ''));
        $pass = (string)($body['password'] ?? '');

        if ($nip === '' || $otp === '' || $pass === '') {
            $this->json(['message' => 'NIP, OTP, dan Password baru wajib diisi'], 422);
        }

        $db = Db::pdo();
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $st = $db->prepare("SELECT id_user FROM users WHERE nip = ? AND reset_token = ? AND token_expiry > NOW() LIMIT 1");
        $st->execute([$nip, $otp]);
        $user = $st->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $up = $db->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, token_expiry = NULL, updated_at = NOW() WHERE id_user = ?");
            $up->execute([$hash, $user['id_user']]);
            $this->json(['status' => 'success', 'message' => 'Password Admin berhasil diperbarui.']);
        }

        $st = $db->prepare("SELECT id_guru FROM guru WHERE nip = ? AND reset_token = ? AND token_expiry > NOW() LIMIT 1");
        $st->execute([$nip, $otp]);
        $guru = $st->fetch(PDO::FETCH_ASSOC);

        if ($guru) {
            $up = $db->prepare("UPDATE guru SET password_hash = ?, reset_token = NULL, token_expiry = NULL, updated_at = NOW() WHERE id_guru = ?");
            $up->execute([$hash, $guru['id_guru']]);
            $this->json(['status' => 'success', 'message' => 'Password Guru berhasil diperbarui.']);
        }

        $this->json(['message' => 'Kode OTP salah atau telah kadaluarsa'], 400);
    }

    /**
     * ME (GURU) - Memeriksa sesi aktif
     */
    public function me($req, $res): void
    {
        $this->optionsOk();
        $token = $this->getBearerToken();
        if (!$token) $this->json(['message' => 'Unauthorized'], 401);

        $db = Db::pdo();
        $st = $db->prepare("SELECT id_guru, nip, nama_guru, jabatan, mata_pelajaran, jenis_kelamin, alamat, no_hp, email, foto, status_aktif FROM guru WHERE api_token=? LIMIT 1");
        $st->execute([$token]); 
        $guru = $st->fetch(PDO::FETCH_ASSOC);

        // Jika token tidak ditemukan, kirim pesan Sesi Berakhir
        if (!$guru) {
            $this->json(['message' => 'Sesi login berakhir. Silakan login kembali.'], 401);
        }

        $this->json(['status' => 'success', 'guru' => $guru]);
    }

    /**
     * LOGOUT (GURU)
     */
    public function logout($req, $res): void
    {
        $this->optionsOk();
        $token = $this->getBearerToken();
        if (!$token) $this->json(['status' => 'success'], 200);

        $db = Db::pdo();
        $st = $db->prepare("UPDATE guru SET api_token=NULL, updated_at=NOW() WHERE api_token=?");
        $st->execute([$token]); 
        $this->json(['status' => 'success', 'message' => 'Berhasil keluar'], 200);
    }
}