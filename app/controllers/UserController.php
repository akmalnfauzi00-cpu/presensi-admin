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

  // sekarang tidak lagi membatasi superadmin
  private function ensureSuperadmin(Response $res): void {
    $this->ensureLoggedIn($res);
  }

  public function index(Request $req, Response $res): void {
    $this->ensureSuperadmin($res);

    $q = trim((string)$req->input('q', ''));
    $rows = User::all($q);

    $pageTitle = "Kelola User Admin";
    $contentFile = __DIR__ . '/../Views/users/index.php';
    include __DIR__ . '/../Views/layouts/admin.php';
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
      $username = trim((string)$req->input('username', ''));
      $password = trim((string)$req->input('password', ''));
      $nama = trim((string)$req->input('nama', ''));
      $role = trim((string)$req->input('role', 'admin'));
      $status_aktif = (int)$req->input('status_aktif', 1);

      if ($username === '' || $password === '' || $nama === '') {
        Session::flash('error', 'Username, password, dan nama wajib diisi.');
        $res->redirect('/users/create');
        return;
      }

      if (!in_array($role, ['superadmin', 'admin'], true)) {
        $role = 'admin';
      }

      $status_aktif = $status_aktif === 1 ? 1 : 0;

      if (User::existsUsername($username)) {
        Session::flash('error', 'Username sudah digunakan.');
        $res->redirect('/users/create');
        return;
      }

      $ok = User::create([
        'id_user' => $this->uuid(),
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
      Session::flash('error', 'Gagal tambah user admin: ' . $e->getMessage());
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
      $username = trim((string)$req->input('username', ''));
      $password = trim((string)$req->input('password', ''));
      $nama = trim((string)$req->input('nama', ''));
      $role = trim((string)$req->input('role', 'admin'));
      $status_aktif = (int)$req->input('status_aktif', 1);

      if ($id === '' || $username === '' || $nama === '') {
        Session::flash('error', 'Data wajib belum lengkap.');
        $res->redirect('/users');
        return;
      }

      $row = User::findById($id);
      if (!$row) {
        Session::flash('error', 'Data user admin tidak ditemukan.');
        $res->redirect('/users');
        return;
      }

      if (!in_array($role, ['superadmin', 'admin'], true)) {
        $role = 'admin';
      }

      $status_aktif = $status_aktif === 1 ? 1 : 0;

      if (User::existsUsername($username, $id)) {
        Session::flash('error', 'Username sudah digunakan user lain.');
        $res->redirect('/users/edit?id=' . urlencode($id));
        return;
      }

      $authUser = Auth::user();

      if (($authUser['id_user'] ?? '') === $id && $status_aktif !== 1) {
        Session::flash('error', 'Anda tidak boleh menonaktifkan akun yang sedang login.');
        $res->redirect('/users/edit?id=' . urlencode($id));
        return;
      }

      $passwordHash = null;
      if ($password !== '') {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
      }

      $ok = User::update($id, [
        'username' => $username,
        'nama' => $nama,
        'role' => $role,
        'status_aktif' => $status_aktif,
        'password_hash' => $passwordHash,
      ]);

      if (!$ok) {
        Session::flash('error', 'Gagal memperbarui user admin.');
        $res->redirect('/users/edit?id=' . urlencode($id));
        return;
      }

      if (($authUser['id_user'] ?? '') === $id) {
        $freshUser = User::findById($id);
        if ($freshUser) {
          Auth::login([
            'id_user' => $freshUser['id_user'],
            'username' => $freshUser['username'],
            'nama' => $freshUser['nama'],
            'role' => $freshUser['role'],
            'status_aktif' => (int)$freshUser['status_aktif'],
          ]);
        }
      }

      Session::flash('success', 'User admin berhasil diperbarui.');
      $res->redirect('/users');

    } catch (Throwable $e) {
      Session::flash('error', 'Gagal update user admin: ' . $e->getMessage());
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

      $row = User::findById($id);
      if (!$row) {
        Session::flash('error', 'Data user admin tidak ditemukan.');
        $res->redirect('/users');
        return;
      }

      $authUser = Auth::user();
      if (($authUser['id_user'] ?? '') === $id) {
        Session::flash('error', 'Anda tidak boleh menghapus akun yang sedang login.');
        $res->redirect('/users');
        return;
      }

      $ok = User::delete($id);

      if (!$ok) {
        Session::flash('error', 'Gagal menghapus user admin.');
        $res->redirect('/users');
        return;
      }

      Session::flash('success', 'User admin berhasil dihapus.');
      $res->redirect('/users');

    } catch (Throwable $e) {
      Session::flash('error', 'Gagal hapus user admin: ' . $e->getMessage());
      $res->redirect('/users');
    }
  }
}