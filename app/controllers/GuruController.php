<?php

class GuruController {

  private function uuid(): string {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
  }

  private function uploadFoto(?array $file): ?string {
    if (!$file || !isset($file['error'])) return null;
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception("Upload foto gagal. Kode error: ".$file['error']);

    $tmp = $file['tmp_name'];
    $mime = mime_content_type($tmp);

    $allowed = ['image/jpeg','image/png','image/webp'];
    if (!in_array($mime, $allowed, true)) {
      throw new Exception("Format foto harus JPG/PNG/WebP.");
    }

    $ext = match($mime) {
      'image/jpeg' => 'jpg',
      'image/png'  => 'png',
      'image/webp' => 'webp',
      default => 'jpg'
    };

    $name = 'guru_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

    $dir = __DIR__ . '/../../public/uploads/guru';
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($tmp, $dest)) {
      throw new Exception("Gagal memindahkan file upload.");
    }

    return 'uploads/guru/' . $name;
  }

  private function deleteFotoFile(?string $path): void {
    if (!$path) return;
    $file = __DIR__ . '/../../public/' . $path;
    if (is_file($file)) @unlink($file);
  }

  private function randomPassword(int $len = 8): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $out = '';
    for ($i=0; $i<$len; $i++) $out .= $chars[random_int(0, strlen($chars)-1)];
    return $out;
  }

  /* =========================
     LIST GURU
  ========================== */
  public function index(Request $req, Response $res): void {
    if (!Auth::check()) $res->redirect('/login');

    $q = trim((string)$req->input('q',''));
    $pdo = Db::pdo();

    if ($q !== '') {
      $st = $pdo->prepare("
        SELECT * FROM guru
        WHERE nama_guru LIKE ?
           OR nip LIKE ?
           OR mata_pelajaran LIKE ?
        ORDER BY nama_guru ASC
      ");
      $st->execute(['%'.$q.'%','%'.$q.'%','%'.$q.'%']);
    } else {
      $st = $pdo->query("SELECT * FROM guru ORDER BY nama_guru ASC");
    }

    $rows = $st->fetchAll();

    $pageTitle = "Data Guru";
    $contentFile = __DIR__ . '/../Views/guru/index.php';
    include __DIR__ . '/../Views/layouts/admin.php';
  }

  /* =========================
     FORM TAMBAH
  ========================== */
  public function createForm(Request $req, Response $res): void {
    if (!Auth::check()) $res->redirect('/login');

    $pageTitle = "Tambah Guru";
    $contentFile = __DIR__ . '/../Views/guru/create.php';
    include __DIR__ . '/../Views/layouts/admin.php';
  }

  /* =========================
     SIMPAN TAMBAH
     -> AKUN APLIKASI DIBUAT (NIP+PASSWORD)
  ========================== */
  public function create(Request $req, Response $res): void {
    if (!Auth::check()) $res->redirect('/login');

    try {
      $nama = trim((string)$req->input('nama',''));
      $nip  = trim((string)$req->input('nip',''));
      $password = trim((string)$req->input('password',''));

      $jabatan = trim((string)$req->input('jabatan',''));
      $mapel   = trim((string)$req->input('mata_pelajaran',''));
      $jenis_kelamin = trim((string)$req->input('jenis_kelamin',''));
      $alamat = trim((string)$req->input('alamat',''));
      $no_hp  = trim((string)$req->input('no_hp',''));
      $email  = trim((string)$req->input('email',''));
      $status_aktif = (string)$req->input('status_aktif','AKTIF');

      if ($nama === '') {
        Session::flash('error','Nama guru wajib diisi.');
        $res->redirect('/guru/create'); return;
      }
      if ($nip === '') {
        Session::flash('error','NIP wajib diisi (untuk login aplikasi).');
        $res->redirect('/guru/create'); return;
      }
      if ($jenis_kelamin === '') {
        Session::flash('error','Jenis kelamin wajib dipilih.');
        $res->redirect('/guru/create'); return;
      }

      $allowedStatus = ['AKTIF','CUTI','NONAKTIF'];
      if (!in_array($status_aktif, $allowedStatus, true)) $status_aktif = 'AKTIF';

      // password kosong -> auto generate
      if ($password === '') $password = $this->randomPassword(8);
      $password_hash = password_hash($password, PASSWORD_DEFAULT);

      // upload foto (opsional)
      $files = $req->files();
      $fotoPath = $this->uploadFoto($files['foto'] ?? null);

      $pdo = Db::pdo();

      // cek NIP unik
      $cek = $pdo->prepare("SELECT id_guru FROM guru WHERE nip=? LIMIT 1");
      $cek->execute([$nip]);
      if ($cek->fetch()) {
        Session::flash('error','NIP sudah digunakan guru lain.');
        $res->redirect('/guru/create'); return;
      }

      $id = $this->uuid();

      $ins = $pdo->prepare("
        INSERT INTO guru
        (id_guru, nip, password_hash, nama_guru, jabatan, mata_pelajaran, jenis_kelamin, alamat, no_hp, email, foto, status_aktif, api_token)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NULL)
      ");

      $ins->execute([
        $id,
        $nip,
        $password_hash,
        $nama,
        $jabatan !== '' ? $jabatan : null,
        $mapel !== '' ? $mapel : null,
        $jenis_kelamin,
        $alamat !== '' ? $alamat : null,
        $no_hp !== '' ? $no_hp : null,
        $email !== '' ? $email : null,
        $fotoPath,
        $status_aktif
      ]);

      Session::flash('success', 'Guru berhasil ditambahkan. Akun aplikasi: NIP='.$nip.' | Password='.$password);
      $res->redirect('/guru');

    } catch (Throwable $e) {
      Session::flash('error','Gagal tambah guru: '.$e->getMessage());
      $res->redirect('/guru/create');
    }
  }

  /* =========================
     FORM EDIT
  ========================== */
  public function editForm(Request $req, Response $res): void {
    if (!Auth::check()) $res->redirect('/login');

    $id = (string)$req->input('id','');
    $pdo = Db::pdo();

    $st = $pdo->prepare("SELECT * FROM guru WHERE id_guru=? LIMIT 1");
    $st->execute([$id]);
    $row = $st->fetch();

    if (!$row) {
      Session::flash('error','Data guru tidak ditemukan.');
      $res->redirect('/guru'); return;
    }

    $pageTitle = "Edit Guru";
    $contentFile = __DIR__ . '/../Views/guru/edit.php';
    include __DIR__ . '/../Views/layouts/admin.php';
  }

  /* =========================
     UPDATE DATA + RESET PASSWORD (OPSIONAL)
     + RESET TOKEN kalau password diganti / akun jadi nonaktif
  ========================== */
  public function update(Request $req, Response $res): void {
    if (!Auth::check()) $res->redirect('/login');

    try {
      $id = (string)$req->input('id_guru','');

      $nama = trim((string)$req->input('nama',''));
      $nip  = trim((string)$req->input('nip',''));
      $password = trim((string)$req->input('password','')); // opsional

      $jabatan = trim((string)$req->input('jabatan',''));
      $mapel   = trim((string)$req->input('mata_pelajaran',''));

      $jenis_kelamin = trim((string)$req->input('jenis_kelamin',''));
      $alamat = trim((string)$req->input('alamat',''));
      $no_hp  = trim((string)$req->input('no_hp',''));
      $email  = trim((string)$req->input('email',''));
      $status_aktif = (string)$req->input('status_aktif','AKTIF');

      if ($id === '' || $nama === '') {
        Session::flash('error','Data tidak valid.');
        $res->redirect('/guru'); return;
      }
      if ($nip === '') {
        Session::flash('error','NIP wajib diisi (untuk login aplikasi).');
        $res->redirect('/guru/edit?id=' . urlencode($id)); return;
      }
      if ($jenis_kelamin === '') {
        Session::flash('error','Jenis kelamin wajib dipilih.');
        $res->redirect('/guru/edit?id=' . urlencode($id)); return;
      }

      $allowedStatus = ['AKTIF','CUTI','NONAKTIF'];
      if (!in_array($status_aktif, $allowedStatus, true)) $status_aktif = 'AKTIF';

      $pdo = Db::pdo();

      // cek nip unik (selain dirinya)
      $cek = $pdo->prepare("SELECT id_guru FROM guru WHERE nip=? AND id_guru<>? LIMIT 1");
      $cek->execute([$nip, $id]);
      if ($cek->fetch()) {
        Session::flash('error','NIP sudah digunakan guru lain.');
        $res->redirect('/guru/edit?id=' . urlencode($id)); return;
      }

      // ambil foto lama
      $oldSt = $pdo->prepare("SELECT foto FROM guru WHERE id_guru=? LIMIT 1");
      $oldSt->execute([$id]);
      $old = $oldSt->fetch();
      if (!$old) {
        Session::flash('error','Data guru tidak ditemukan.');
        $res->redirect('/guru'); return;
      }
      $fotoPath = $old['foto'] ?? null;

      // upload foto baru (opsional)
      $files = $req->files();
      $newFoto = $this->uploadFoto($files['foto'] ?? null);
      if ($newFoto) {
        $this->deleteFotoFile($fotoPath);
        $fotoPath = $newFoto;
      }

      // query update dinamis
      $sets = "
        nip=?,
        nama_guru=?,
        jabatan=?,
        mata_pelajaran=?,
        jenis_kelamin=?,
        alamat=?,
        no_hp=?,
        email=?,
        foto=?,
        status_aktif=?,
        updated_at=NOW()
      ";

      $params = [
        $nip,
        $nama,
        $jabatan !== '' ? $jabatan : null,
        $mapel !== '' ? $mapel : null,
        $jenis_kelamin,
        $alamat !== '' ? $alamat : null,
        $no_hp !== '' ? $no_hp : null,
        $email !== '' ? $email : null,
        $fotoPath,
        $status_aktif,
      ];

      // kalau password diganti -> update hash + reset token
      if ($password !== '') {
        $sets .= ", password_hash=?, api_token=NULL";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
      }

      // kalau status jadi CUTI/NONAKTIF -> reset token juga
      if ($status_aktif !== 'AKTIF') {
        $sets .= ", api_token=NULL";
      }

      $params[] = $id;

      $up = $pdo->prepare("UPDATE guru SET {$sets} WHERE id_guru=?");
      $up->execute($params);

      Session::flash('success','Guru berhasil diperbarui.');
      $res->redirect('/guru');

    } catch (Throwable $e) {
      Session::flash('error','Gagal update guru: '.$e->getMessage());
      $res->redirect('/guru');
    }
  }

  /* =========================
     HAPUS
  ========================== */
  public function delete(Request $req, Response $res): void {
    if (!Auth::check()) $res->redirect('/login');

    try {
      $id = (string)$req->input('id_guru','');
      $pdo = Db::pdo();

      $st = $pdo->prepare("SELECT foto FROM guru WHERE id_guru=? LIMIT 1");
      $st->execute([$id]);
      $row = $st->fetch();
      if ($row && !empty($row['foto'])) {
        $this->deleteFotoFile($row['foto']);
      }

      $del = $pdo->prepare("DELETE FROM guru WHERE id_guru=?");
      $del->execute([$id]);

      Session::flash('success','Guru berhasil dihapus.');
      $res->redirect('/guru');

    } catch (Throwable $e) {
      Session::flash('error','Gagal hapus guru: '.$e->getMessage());
      $res->redirect('/guru');
    }
  }
}