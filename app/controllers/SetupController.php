<?php

class SetupController
{
    /**
     * Menampilkan halaman form pendaftaran admin
     */
    public function index($req, $res)
    {
        $pdo = Db::pdo();
        
        // Cek apakah sudah ada user dengan role ADMIN di database
        $isAdminExists = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'ADMIN'")->fetchColumn() > 0;

        // Path menuju file view setup
        require_once __DIR__ . '/../views/auth/setup_admin.php';
    }

    /**
     * Memproses data dari form setup admin
     */
    public function createAdmin($req, $res)
    {
        $pdo = Db::pdo();

        // Mengambil data dari form POST
        $nama     = trim($_POST['nama'] ?? 'Admin Utama');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Validasi input kosong
        if (empty($username) || empty($password)) {
            Session::flash('error', 'Username dan Password tidak boleh kosong!');
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            // 1. Cek apakah username sudah digunakan
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmtCheck->execute([$username]);
            if ($stmtCheck->fetchColumn() > 0) {
                throw new Exception("Username '$username' sudah terdaftar.");
            }

            // 2. Persiapan data
            $id_user = "ADM-" . time();
            $hash    = password_hash($password, PASSWORD_DEFAULT);

            /**
             * 3. Query SQL sesuai struktur database kamu (image_1cc35b.png):
             * - Kolom password menggunakan: password_hash
             * - Kolom nama menggunakan: nama (bukan nama_lengkap)
             * - Role menggunakan: 'ADMIN' (huruf kapital sesuai Enum)
             */
            $sql = "INSERT INTO users (id_user, username, password_hash, nama, role, created_at) 
                    VALUES (?, ?, ?, ?, 'ADMIN', NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $id_user, 
                $username, 
                $hash, 
                $nama
            ]);

            // 4. Redirect ke login jika sukses
            Session::flash('success', 'Akun Admin berhasil dibuat! Silakan login.');
            header("Location: " . $this->base() . "/login");
            exit;

        } catch (Exception $e) {
            // Jika gagal, kirim pesan error ke halaman sebelumnya
            Session::flash('error', 'Gagal: ' . $e->getMessage());
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    /**
     * Helper untuk mendapatkan base URL aplikasi
     */
    private function base() {
        $b = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        return ($b === '/') ? '' : $b;
    }
}