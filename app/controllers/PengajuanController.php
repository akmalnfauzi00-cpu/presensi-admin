<?php

class PengajuanController
{
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

  private function filters(): array
  {
    $status = strtoupper(trim((string)($_GET['status'] ?? '')));
    $jenis = strtoupper(trim((string)($_GET['jenis'] ?? '')));
    $q = trim((string)($_GET['q'] ?? ''));

    return [$status, $jenis, $q];
  }

  private function countMenunggu(PDO $pdo): int
  {
    $st = $pdo->query("SELECT COUNT(*) AS jml FROM pengajuan_presensi WHERE status_verifikasi = 'MENUNGGU'");
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return (int)($row['jml'] ?? 0);
  }

  public function index($req, $res)
  {
    $pdo = Db::pdo();
    $pageTitle = 'Pengajuan Izin / Sakit';

    [$status, $jenis, $q] = $this->filters();

    $where = [];
    $params = [];

    if ($status !== '') {
      $where[] = "p.status_verifikasi = :status";
      $params[':status'] = $status;
    }

    if ($jenis !== '') {
      $where[] = "p.jenis = :jenis";
      $params[':jenis'] = $jenis;
    }

    if ($q !== '') {
      $where[] = "(g.nama_guru LIKE :q OR g.nip LIKE :q OR p.alasan LIKE :q)";
      $params[':q'] = '%' . $q . '%';
    }

    $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $stmt = $pdo->prepare("
      SELECT
        p.id_pengajuan,
        p.id_guru,
        p.jenis,
        p.tanggal,
        p.alasan,
        p.lampiran_path,
        p.status_verifikasi,
        p.catatan_admin,
        p.created_at,
        p.updated_at,
        g.nama_guru,
        g.nip
      FROM pengajuan_presensi p
      JOIN guru g ON g.id_guru = p.id_guru
      $sqlWhere
      ORDER BY
        CASE p.status_verifikasi
          WHEN 'MENUNGGU' THEN 1
          WHEN 'DITOLAK' THEN 2
          WHEN 'DISETUJUI' THEN 3
          ELSE 4
        END,
        p.created_at DESC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $jumlahMenunggu = $this->countMenunggu($pdo);

    $contentFile = __DIR__ . '/../views/pengajuan/index.php';
    $layoutFile  = __DIR__ . '/../views/layouts/admin.php';
    require $layoutFile;
  }

  public function verifikasi($req, $res)
  {
    $pdo = Db::pdo();

    $idPengajuan = trim((string)($_POST['id_pengajuan'] ?? ''));
    $aksi = strtoupper(trim((string)($_POST['aksi'] ?? '')));
    $catatanAdmin = trim((string)($_POST['catatan_admin'] ?? ''));

    if ($idPengajuan === '') {
      $_SESSION['flash_error'] = 'ID pengajuan tidak valid.';
      header('Location: ' . $this->base() . '/pengajuan');
      exit;
    }

    if (!in_array($aksi, ['SETUJUI', 'TOLAK'], true)) {
      $_SESSION['flash_error'] = 'Aksi verifikasi tidak valid.';
      header('Location: ' . $this->base() . '/pengajuan');
      exit;
    }

    $stmt = $pdo->prepare("
      SELECT *
      FROM pengajuan_presensi
      WHERE id_pengajuan = ?
      LIMIT 1
    ");
    $stmt->execute([$idPengajuan]);
    $pengajuan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pengajuan) {
      $_SESSION['flash_error'] = 'Pengajuan tidak ditemukan.';
      header('Location: ' . $this->base() . '/pengajuan');
      exit;
    }

    if (($pengajuan['status_verifikasi'] ?? '') !== 'MENUNGGU') {
      $_SESSION['flash_error'] = 'Pengajuan ini sudah diverifikasi sebelumnya.';
      header('Location: ' . $this->base() . '/pengajuan');
      exit;
    }

    try {
      $pdo->beginTransaction();

      if ($aksi === 'TOLAK') {
        $up = $pdo->prepare("
          UPDATE pengajuan_presensi
          SET status_verifikasi = 'DITOLAK',
              catatan_admin = ?,
              updated_at = NOW()
          WHERE id_pengajuan = ?
        ");
        $up->execute([
          $catatanAdmin !== '' ? $catatanAdmin : null,
          $idPengajuan
        ]);

        $pdo->commit();
        $_SESSION['flash_success'] = 'Pengajuan berhasil ditolak.';
        header('Location: ' . $this->base() . '/pengajuan');
        exit;
      }

      $up = $pdo->prepare("
        UPDATE pengajuan_presensi
        SET status_verifikasi = 'DISETUJUI',
            catatan_admin = ?,
            updated_at = NOW()
        WHERE id_pengajuan = ?
      ");
      $up->execute([
        $catatanAdmin !== '' ? $catatanAdmin : null,
        $idPengajuan
      ]);

      $stHeader = $pdo->prepare("
        SELECT id_presensi
        FROM kehadiran
        WHERE tanggal = ?
        LIMIT 1
      ");
      $stHeader->execute([$pengajuan['tanggal']]);
      $header = $stHeader->fetch(PDO::FETCH_ASSOC);

      if ($header && !empty($header['id_presensi'])) {
        $idPresensi = $header['id_presensi'];
      } else {
        $idPresensi = $this->uuid();

        $insHeader = $pdo->prepare("
          INSERT INTO kehadiran (id_presensi, tanggal, lokasi, lat_sekolah, lng_sekolah)
          VALUES (?,?,?,?,?)
        ");
        $insHeader->execute([
          $idPresensi,
          $pengajuan['tanggal'],
          'Sekolah',
          null,
          null,
        ]);
      }

      $stDetail = $pdo->prepare("
        SELECT id_detail
        FROM presensi_detail
        WHERE id_presensi = ? AND id_guru = ?
        LIMIT 1
      ");
      $stDetail->execute([
        $idPresensi,
        $pengajuan['id_guru']
      ]);
      $detail = $stDetail->fetch(PDO::FETCH_ASSOC);

      if ($detail && !empty($detail['id_detail'])) {
        $upDetail = $pdo->prepare("
          UPDATE presensi_detail
          SET status_kehadiran = ?,
              is_terlambat = 0,
              jam_masuk = NULL,
              jam_keluar = NULL,
              lat_masuk = NULL,
              lng_masuk = NULL,
              lat_pulang = NULL,
              lng_pulang = NULL,
              foto_masuk_path = NULL,
              foto_pulang_path = NULL
          WHERE id_detail = ?
        ");
        $upDetail->execute([
          strtoupper((string)$pengajuan['jenis']),
          $detail['id_detail']
        ]);
      } else {
        $idDetail = $this->uuid();

        $insDetail = $pdo->prepare("
          INSERT INTO presensi_detail
          (
            id_detail,
            id_presensi,
            id_guru,
            jam_masuk,
            jam_keluar,
            status_kehadiran,
            is_terlambat,
            lat_masuk,
            lng_masuk,
            lat_pulang,
            lng_pulang,
            foto_masuk_path,
            foto_pulang_path
          )
          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $insDetail->execute([
          $idDetail,
          $idPresensi,
          $pengajuan['id_guru'],
          null,
          null,
          strtoupper((string)$pengajuan['jenis']),
          0,
          null,
          null,
          null,
          null,
          null,
          null,
        ]);
      }

      $pdo->commit();
      $_SESSION['flash_success'] = 'Pengajuan berhasil disetujui dan masuk ke rekap kehadiran.';
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      $_SESSION['flash_error'] = 'Gagal memverifikasi pengajuan: ' . $e->getMessage();
    }

    header('Location: ' . $this->base() . '/pengajuan');
    exit;
  }

  public function delete($request = null, $response = null)
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      header('Location: ' . $this->base() . '/pengajuan');
      exit;
    }

    $pdo = Db::pdo();
    $id = trim((string)($_POST['id_pengajuan'] ?? ''));

    if ($id === '') {
      $_SESSION['flash_error'] = 'ID pengajuan tidak valid.';
      header('Location: ' . $this->base() . '/pengajuan');
      exit;
    }

    try {
      $stmt = $pdo->prepare("
        SELECT lampiran_path
        FROM pengajuan_presensi
        WHERE id_pengajuan = ?
        LIMIT 1
      ");
      $stmt->execute([$id]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$row) {
        $_SESSION['flash_error'] = 'Data pengajuan tidak ditemukan.';
        header('Location: ' . $this->base() . '/pengajuan');
        exit;
      }

      $del = $pdo->prepare("DELETE FROM pengajuan_presensi WHERE id_pengajuan = ?");
      $ok = $del->execute([$id]);

      if ($ok) {
        if (!empty($row['lampiran_path'])) {
          $fullPath = dirname(__DIR__, 2) . '/public' . $row['lampiran_path'];
          if (is_file($fullPath)) {
            @unlink($fullPath);
          }
        }

        $_SESSION['flash_success'] = 'Pengajuan berhasil dihapus.';
      } else {
        $_SESSION['flash_error'] = 'Pengajuan gagal dihapus.';
      }
    } catch (Throwable $e) {
      $_SESSION['flash_error'] = 'Terjadi error: ' . $e->getMessage();
    }

    header('Location: ' . $this->base() . '/pengajuan');
    exit;
  }
}