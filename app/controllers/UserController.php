<?php

class UserController {

  private function uuid(): string {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
  }

  private function ensureLoggedIn(Response $res): void {
    if (!Auth::check()) {
      $res->redirect('/login');
      exit;
    }
  }

  private function ensureSuperadmin(Response $res): void {
    $this->ensureLoggedIn($res);
  }

  public function index(Request $req, Response $res): void {
    $this->ensureSuperadmin($res);

    $q = trim((string)$req->input('q', ''));
    $rows = User::all($q);

    $pageTitle = "Kelola User Admin";
    $contentFile = __DIR__ . '/../views/users/index.php';
    include __DIR__ . '/../views/layouts/admin.php';
  }

  public function createForm(Request $req, Response $res): void {
    $this->ensureSuperadmin($res);

    $pageTitle = "Tambah User Admin";
    $contentFile = __DIR__ . '/../Views/users/create.php';
    include __DIR__ . '/../Views/layouts/admin.php';
  }

  public function create(Request $req, Response $res): void {
    $this->ensureSuperadmin($res);

    try {
      $nip = trim((string)$req->input('nip', ''));
      $email = trim((string)$req->input('email', ''));
      $username = trim((string)$req->input('username', ''));
      $password = trim((string)$req->input('password', ''));
      $nama = trim((string)$req->input('nama', ''));
      $role = trim((string)$req->input('role', 'admin'));
      $status_aktif = (int)$req->input('status_aktif', 1);

      if ($username === '' || $password === '' || $nama === '' || $nip === '' || $email === '') {
        Session::flash('error', 'Semua kolom (NIP, Email, Nama, Username, Password) wajib diisi.');
        $res->redirect('/users/create');
        return;
      }

      // PERBAIKAN: Validasi apakah NIP sudah terdaftar
      $pdo = Db::pdo();
      $cekNip = $pdo->prepare("SELECT COUNT(*) FROM users WHERE nip = ?");
      $cekNip->execute([$nip]);
      if ((int)$cekNip->fetchColumn() > 0) {
        Session::flash('error', 'NIP sudah terdaftar untuk pengguna lain.');
        $res->redirect('/users/create');
        return;
      }

      if (User::existsUsername($username)) {
        Session::flash('error', 'Username sudah digunakan.');
        $res->redirect('/users/create');
        return;
      }

      if (!in_array($role, ['superadmin', 'admin'], true)) {
        $role = 'admin';
      }

      $status_aktif = $status_aktif === 1 ? 1 : 0;

      $ok = User::create([
        'id_user' => $this->uuid(),
        'nip' => $nip,
        'email' => $email,
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'nama' => $nama,
        'role' => $role,
        'status_aktif' => $status_aktif,
      ]);

      if (!$ok) {
        Session::flash('error', 'Gagal menambahkan user admin.');
        $res->redirect('/users/create');
        return;
      }

      Session::flash('success', 'User admin berhasil ditambahkan.');
      $res->redirect('/users');

    } catch (Throwable $e) {
      Session::flash('error', 'Gagal: ' . $e->getMessage());
      $res->redirect('/users/create');
    }
  }

  public function editForm(Request $req, Response $res): void {
    $this->ensureSuperadmin($res);

    $id = trim((string)$req->input('id', ''));
    if ($id === '') {
      Session::flash('error', 'ID user tidak valid.');
      $res->redirect('/users');
      return;
    }

    $row = User::findById($id);
    if (!$row) {
      Session::flash('error', 'Data user admin tidak ditemukan.');
      $res->redirect('/users');
      return;
    }

    $pageTitle = "Edit User Admin";
    $contentFile = __DIR__ . '/../Views/users/edit.php';
    include __DIR__ . '/../Views/layouts/admin.php';
  }

  public function update(Request $req, Response $res): void {
    $this->ensureSuperadmin($res);

    try {
      $id = trim((string)$req->input('id_user', ''));
      $nip = trim((string)$req->input('nip', ''));
      $email = trim((string)$req->input('email', ''));
      $username = trim((string)$req->input('username', ''));
      $password = trim((string)$req->input('password', ''));
      $nama = trim((string)$req->input('nama', ''));
      $role = trim((string)$req->input('role', 'admin'));
      $status_aktif = (int)$req->input('status_aktif', 1);

      if ($id === '' || $username === '' || $nama === '' || $nip === '' || $email === '') {
        Session::flash('error', 'Data wajib (NIP, Email, Nama, Username) tidak boleh kosong.');
        $res->redirect('/users/edit?id=' . urlencode($id));
        return;
      }

      $row = User::findById($id);
      if (!$row) {
        Session::flash('error', 'Data tidak ditemukan.');
        $res->redirect('/users');
        return;
      }

      // PERBAIKAN: Cek duplikasi NIP saat update (kecuali milik sendiri)
      $pdo = Db::pdo();
      $cekNip = $pdo->prepare("SELECT COUNT(*) FROM users WHERE nip = ? AND id_user <> ?");
      $cekNip->execute([$nip, $id]);
      if ((int)$cekNip->fetchColumn() > 0) {
        Session::flash('error', 'NIP sudah digunakan oleh pengguna lain.');
        $res->redirect('/users/edit?id=' . urlencode($id));
        return;
      }

      if (User::existsUsername($username, $id)) {
        Session::flash('error', 'Username sudah digunakan user lain.');
        $res->redirect('/users/edit?id=' . urlencode($id));
        return;
      }

      $status_aktif = $status_aktif === 1 ? 1 : 0;
      $authUser = Auth::user();

      if (($authUser['id_user'] ?? '') === $id && $status_aktif !== 1) {
        Session::flash('error', 'Anda tidak boleh menonaktifkan akun sendiri.');
        $res->redirect('/users/edit?id=' . urlencode($id));
        return;
      }

      if (!in_array($role, ['superadmin', 'admin'], true)) {
        $role = 'admin';
      }

      $passwordHash = null;
      if ($password !== '') {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
      }

      $ok = User::update($id, [
        'nip' => $nip,
        'email' => $email,
        'username' => $username,
        'nama' => $nama,
        'role' => $role,
        'status_aktif' => $status_aktif,
        'password_hash' => $passwordHash,
      ]);

      if (!$ok) {
        Session::flash('error', 'Gagal memperbarui data.');
        $res->redirect('/users/edit?id=' . urlencode($id));
        return;
      }

      // Refresh session jika yang diupdate adalah diri sendiri
      if (($authUser['id_user'] ?? '') === $id) {
        $fresh = User::findById($id);
        Auth::login([
          'id_user' => $fresh['id_user'],
          'username' => $fresh['username'],
          'nama' => $fresh['nama'],
          'role' => $fresh['role'],
          'status_aktif' => (int)$fresh['status_aktif'],
        ]);
      }

      Session::flash('success', 'User admin berhasil diperbarui.');
      $res->redirect('/users');

    } catch (Throwable $e) {
      Session::flash('error', 'Gagal update: ' . $e->getMessage());
      $res->redirect('/users');
    }
  }

  public function delete(Request $req, Response $res): void {
    $this->ensureSuperadmin($res);

    try {
      $id = trim((string)$req->input('id_user', ''));
      if ($id === '') {
        Session::flash('error', 'ID user tidak valid.');
        $res->redirect('/users');
        return;
      }

      $authUser = Auth::user();
      if (($authUser['id_user'] ?? '') === $id) {
        Session::flash('error', 'Anda tidak boleh menghapus akun sendiri.');
        $res->redirect('/users');
        return;
      }

      $ok = User::delete($id);
      if (!$ok) {
        Session::flash('error', 'Gagal menghapus user.');
        $res->redirect('/users');
        return;
      }

      Session::flash('success', 'User admin berhasil dihapus.');
      $res->redirect('/users');

    } catch (Throwable $e) {
      Session::flash('error', 'Error: ' . $e->getMessage());
      $res->redirect('/users');
    }
  }
}