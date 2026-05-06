<?php

class AuthController {

    /**
     * Mengarahkan halaman utama berdasarkan status login
     */
    public function redirectHome(Request $req, Response $res): void {
        if (Auth::check()) {
            $res->redirect('/dashboard');
            return;
        }
        $res->redirect('/login');
    }

    /**
     * Menampilkan Form Login Admin
     */
    public function loginForm(Request $req, Response $res): void {
        if (Auth::check()) {
            $res->redirect('/dashboard');
            return;
        }
        $pageTitle = 'Login Admin';
        // Menggunakan realpath untuk memastikan path benar di Windows
        include realpath(__DIR__ . '/../Views/auth/login.php');
    }

    /**
     * Menampilkan Halaman Lupa Password (WEB ADMIN)
     */
    public function forgotPasswordView(Request $req, Response $res): void {
        if (Auth::check()) {
            $res->redirect('/dashboard');
            return;
        }
        $pageTitle = 'Lupa Password';
        
        $viewPath = realpath(__DIR__ . '/../Views/auth/forgot_password.php');
        
        if ($viewPath && file_exists($viewPath)) {
            include $viewPath;
        } else {
            die("Error: File view tidak ditemukan di: app/Views/auth/forgot_password.php. Pastikan Anda sudah membuat filenya.");
        }
    }

    /**
     * Proses Login Admin
     */
    public function login(Request $req, Response $res): void {
        $username = trim((string)$req->input('username', ''));
        $password = (string)$req->input('password', '');

        $pdo = Db::pdo();
        $st = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $st->execute([$username]);
        $user = $st->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Session::flash('error', 'Username atau password salah.');
            $res->redirect('/login');
            return;
        }

        if (isset($user['status_aktif']) && (int)$user['status_aktif'] !== 1) {
            Session::flash('error', 'Akun Anda sedang nonaktif.');
            $res->redirect('/login');
            return;
        }

        Auth::login([
            'id_user'      => $user['id_user'],
            'username'     => $user['username'],
            'nama'         => $user['nama'],
            'role'         => $user['role'] ?? 'admin',
            'status_aktif' => isset($user['status_aktif']) ? (int)$user['status_aktif'] : 1,
        ]);

        $res->redirect('/dashboard');
    }

    /**
     * Proses Logout Admin
     */
    public function logout(Request $req, Response $res): void {
        Auth::logout();
        $res->redirect('/login');
    }

    /**
     * Menampilkan Form Setup Admin
     */
    public function setupForm(Request $req, Response $res): void {
        include realpath(__DIR__ . '/../Views/auth/setup_admin.php');
    }

    /**
     * Proses Pembuatan Admin Pertama Kali
     */
    public function setupCreateAdmin(Request $req, Response $res): void {
        $nama     = trim((string)$req->input('nama', 'Admin Utama'));
        $username = trim((string)$req->input('username', ''));
        $password = (string)$req->input('password', '');

        if ($username === '' || $password === '') {
            Session::flash('error', 'Username & password wajib diisi.');
            $res->redirect('/setup/create-admin');
            return;
        }

        $pdo = Db::pdo();

        $cek = $pdo->prepare("SELECT COUNT(*) c FROM users WHERE username=?");
        $cek->execute([$username]);
        if ((int)$cek->fetch()['c'] > 0) {
            Session::flash('error', 'Username sudah dipakai.');
            $res->redirect('/setup/create-admin');
            return;
        }

        $id = $this->uuid();
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $ins = $pdo->prepare("
            INSERT INTO users
            (id_user, username, password_hash, nama, role, status_aktif)
            VALUES (?,?,?,?,?,?)
        ");
        $ins->execute([$id, $username, $hash, $nama, 'superadmin', 1]);

        Session::flash('success', 'Admin berhasil dibuat. Silakan login.');
        $res->redirect('/login');
    }

    /**
     * Helper untuk Generate UUID v4
     */
    private function uuid(): string {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}