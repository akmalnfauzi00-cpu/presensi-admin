<?php

class GuruController {

  /**
   * Helper untuk membuat ID unik (UUID)
   */
  private function uuid(): string {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
  }

  /**
   * Mengelola Upload Foto: Nama file otomatis menggunakan NIP
   */
  private function uploadFoto(?array $file, string $nip): ?string {
    if (!$file || !isset($file['error'])) return null;
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception("Upload foto gagal.");

    $tmp = $file['tmp_name'];
    $mime = mime_content_type($tmp);

    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowed, true)) {
      throw new Exception("Format foto harus JPG/PNG/WebP.");
    }

    $ext = match($mime) {
      'image/jpeg' => 'jpg',
      'image/png'  => 'png',
      'image/webp' => 'webp',
      default      => 'jpg'
    };

    $name = $nip . '.' . $ext;
    $dir = __DIR__ . '/../../public/uploads/guru';
    
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $dest = $dir . '/' . $name;
    
    if (file_exists($dest)) @unlink($dest);

    if (!move_uploaded_file($tmp, $dest)) {
      throw new Exception("Gagal memindahkan file upload ke folder.");
    }

    return $name; 
  }

  /**
   * Menghapus file foto fisik
   */
  private function deleteFotoFile(?string $name): void {
    if (!$name) return;
    $file = __DIR__ . '/../../public/uploads/guru/' . $name;
    if (is_file($file)) @unlink($file);
  }

  /* ============================================================
      1. TAMPILAN LIST GURU (DENGAN DUKUNGAN TAB VERIFIKASI)
  ============================================================ */
  public function index(Request $req, Response $res): void {
    if (!Auth::check()) $res->redirect('/login');
    
    $q = trim((string)$req->input('q', ''));
    $tab = $req->input('tab', 'aktif'); // Default tab aktif
    $pdo = Db::pdo();
    
    $sql = "SELECT * FROM guru WHERE 1=1";
    $params = [];

    if ($q !== '') {
      $sql .= " AND (nama_guru LIKE ? OR nip LIKE ?)";
      $params[] = '%' . $q . '%';
      $params[] = '%' . $q . '%';
    }

    $sql .= " ORDER BY created_at DESC";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    
    $rows = $st->fetchAll();
    
    $pageTitle = "Data Guru";
    $contentFile = __DIR__ . '/../Views/guru/index.php';
    include __DIR__ . '/../Views/layouts/admin.php';
  }

  /* ============================================================
      2. LOGIKA VERIFIKASI GURU (SETUJUI & TOLAK)
  ============================================================ */

  public function setujui(Request $req, Response $res): void {
    if (!Auth::check()) $res->redirect('/login');
    try {
      $id = (string)$req->input('id', '');
      $pdo = Db::pdo();

      $up = $pdo->prepare("UPDATE guru SET status_aktif = 'AKTIF', updated_at = NOW() WHERE id_guru = ?");
      $up->execute([$id]);

      Session::flash('success', 'Akun guru berhasil diverifikasi dan diaktifkan.');
    } catch (Throwable $e) {
      Session::flash('error', 'Gagal verifikasi: ' . $e->getMessage());
    }
    $res->redirect('/guru?tab=pending');
  }

  public function tolak(Request $req, Response $res): void {
    if (!Auth::check()) $res->redirect('/login');
    try {
      $id = (string)$req->input('id', '');
      $pdo = Db::pdo();

      $st = $pdo->prepare("SELECT foto FROM guru WHERE id_guru = ? AND status_aktif = 'PENDING'");
      $st->execute([$id]);
      $row = $st->fetch();
      if ($row) $this->deleteFotoFile($row['foto']);

      $del = $pdo->prepare("DELETE FROM guru WHERE id_guru = ? AND status_aktif = 'PENDING'");
      $del->execute([$id]);

      Session::flash('success', 'Pendaftaran berhasil ditolak dan dihapus.');
    } catch (Throwable $e) {
      Session::flash('error', 'Gagal menolak: ' . $e->getMessage());
    }
    $res->redirect('/guru?tab=pending');
  }

  /* ============================================================
      3. FITUR LUPA PASSWORD (RESET MANUAL OLEH ADMIN)
  ============================================================ */

  /**
   * Reset Password Guru ke Default (123456)
   */
  public function resetPasswordManual(Request $req, Response $res): void {
    if (!Auth::check()) $res->redirect('/login');
    try {
      $id = (string)$req->input('id', '');
      $pdo = Db::pdo();

      $defaultPass = password_hash('123456', PASSWORD_DEFAULT);
      
      // Reset password dan hapus token OTP (jika ada) agar tidak bisa digunakan lagi
      $up = $pdo->prepare("UPDATE guru SET password_hash = ?, reset_token = NULL, token_expiry = NULL, updated_at = NOW() WHERE id_guru = ?");
      $up->execute([$defaultPass, $id]);

      Session::flash('success', 'Password guru berhasil direset menjadi: 123456');
    } catch (Throwable $e) {
      Session::flash('error', 'Gagal reset password: ' . $e->getMessage());
    }
    $res->redirect('/guru');
  }

  /* ============================================================
      4. CRUD GURU (CREATE, EDIT, DELETE)
  ============================================================ */

  public function createForm(Request $req, Response $res): void {
    if (!Auth::check()) $res->redirect('/login');
    $pageTitle = "Tambah Guru Baru";
    $contentFile = __DIR__ . '/../Views/guru/create.php';
    include __DIR__ . '/../Views/layouts/admin.php';
  }

  public function create(Request $req, Response $res): void {
    if (!Auth::check()) $res->redirect('/login');
    try {
      $nama = trim((string)$req->input('nama', ''));
      $nip  = trim((string)$req->input('nip', ''));
      $pass = trim((string)$req->input('password', ''));
      
      if ($nama === '' || $nip === '') throw new Exception("Nama dan NIP wajib diisi.");

      $files = $req->files();
      $fotoPath = $this->uploadFoto($files['foto'] ?? null, $nip);

      $pdo = Db::pdo();
      $id = $this->uuid();
      $hash = password_hash(($pass !== '' ? $pass : '123456'), PASSWORD_DEFAULT);

      $ins = $pdo->prepare("INSERT INTO guru (id_guru, nip, password_hash, nama_guru, jabatan, mata_pelajaran, jenis_kelamin, alamat, no_hp, email, foto, status_aktif, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?, NOW())");
      $ins->execute([
        $id, $nip, $hash, $nama, 
        $req->input('jabatan'), $req->input('mata_pelajaran'), 
        $req->input('jenis_kelamin'), $req->input('alamat'),
        $req->input('no_hp'), $req->input('email'), 
        $fotoPath, $req->input('status_aktif', 'AKTIF')
      ]);

      Session::flash('success', 'Guru berhasil ditambahkan.');
      $res->redirect('/guru');
    } catch (Throwable $e) {
      Session::flash('error', $e->getMessage());
      $res->redirect('/guru/create');
    }
  }

  public function editForm(Request $req, Response $res): void {
    if (!Auth::check()) $res->redirect('/login');
    $id = (string)$req->input('id', '');
    $pdo = Db::pdo();
    $st = $pdo->prepare("SELECT * FROM guru WHERE id_guru=? LIMIT 1");
    $st->execute([$id]);
    $row = $st->fetch();

    if (!$row) {
      Session::flash('error', 'Data guru tidak ditemukan.');
      $res->redirect('/guru'); return;
    }

    $pageTitle = "Edit Data Guru";
    $contentFile = __DIR__ . '/../Views/guru/edit.php';
    include __DIR__ . '/../Views/layouts/admin.php';
  }

  public function update(Request $req, Response $res): void {
    if (!Auth::check()) $res->redirect('/login');
    try {
      $id = (string)$req->input('id_guru', '');
      $nip = trim((string)$req->input('nip', ''));
      $pdo = Db::pdo();
      $st = $pdo->prepare("SELECT foto FROM guru WHERE id_guru=?");
      $st->execute([$id]);
      $old = $st->fetch();
      $fotoName = $old['foto'] ?? null;

      $files = $req->files();
      $newFoto = $this->uploadFoto($files['foto'] ?? null, $nip);
      if ($newFoto) $fotoName = $newFoto;

      $up = $pdo->prepare("UPDATE guru SET nip=?, nama_guru=?, jabatan=?, mata_pelajaran=?, jenis_kelamin=?, alamat=?, no_hp=?, email=?, foto=?, status_aktif=?, updated_at=NOW() WHERE id_guru=?");
      $up->execute([
        $nip, $req->input('nama'), $req->input('jabatan'), 
        $req->input('mata_pelajaran'), $req->input('jenis_kelamin'),
        $req->input('alamat'), $req->input('no_hp'), $req->input('email'),
        $fotoName, $req->input('status_aktif'), $id
      ]);

      Session::flash('success', 'Data berhasil diperbarui.');
      $res->redirect('/guru');
    } catch (Throwable $e) {
      Session::flash('error', $e->getMessage());
      $res->redirect('/guru');
    }
  }

  public function delete(Request $req, Response $res): void {
    if (!Auth::check()) $res->redirect('/login');
    try {
      $id = (string)$req->input('id_guru', '');
      $pdo = Db::pdo();
      $st = $pdo->prepare("SELECT foto FROM guru WHERE id_guru=? LIMIT 1");
      $st->execute([$id]);
      $row = $st->fetch();
      if ($row && !empty($row['foto'])) {
        $this->deleteFotoFile($row['foto']);
      }
      $del = $pdo->prepare("DELETE FROM guru WHERE id_guru=?");
      $del->execute([$id]);
      Session::flash('success', 'Guru berhasil dihapus.');
      $res->redirect('/guru');
    } catch (Throwable $e) {
      Session::flash('error', 'Gagal hapus guru: ' . $e->getMessage());
      $res->redirect('/guru');
    }
  }
}