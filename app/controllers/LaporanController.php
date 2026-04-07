<?php

class LaporanController
{
  private function base(): string
  {
    $b = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    return $b === '/' ? '' : $b;
  }

  private function parseDate($s): ?string
  {
    $s = trim((string)$s);
    if ($s === '') return null;

    // format: yyyy-mm-dd
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
      return $s;
    }

    // format: dd/mm/yyyy
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $s, $m)) {
      return $m[3] . '-' . $m[2] . '-' . $m[1];
    }

    return null;
  }

  private function getFilters(): array
  {
    $mulai   = $this->parseDate($_GET['mulai'] ?? '') ?? date('Y-m-d');
    $selesai = $this->parseDate($_GET['selesai'] ?? '') ?? date('Y-m-d');

    if ($selesai < $mulai) {
      $tmp = $mulai;
      $mulai = $selesai;
      $selesai = $tmp;
    }

    $idGuru = trim((string)($_GET['id_guru'] ?? ''));
    if ($idGuru === '0') $idGuru = '';

    return [$mulai, $selesai, $idGuru];
  }

  private function hitungJumlahHari(string $mulai, string $selesai): int
  {
    $start = strtotime($mulai);
    $end   = strtotime($selesai);

    if (!$start || !$end || $end < $start) {
      return 0;
    }

    return (int)(floor(($end - $start) / 86400) + 1);
  }

  private function sqlRekapPerGuru(string $whereGuru): string
  {
    return "
      SELECT
        g.id_guru,
        g.nama_guru,
        g.nip,

        -- Hadir hanya yang benar-benar hadir dan tidak terlambat
        SUM(
          CASE
            WHEN pd.status_kehadiran = 'HADIR'
             AND COALESCE(pd.is_terlambat, 0) = 0
            THEN 1 ELSE 0
          END
        ) AS hadir,

        -- Lambat hanya yang hadir tapi terlambat
        SUM(
          CASE
            WHEN pd.status_kehadiran = 'HADIR'
             AND COALESCE(pd.is_terlambat, 0) = 1
            THEN 1 ELSE 0
          END
        ) AS lambat,

        SUM(CASE WHEN pd.status_kehadiran = 'IZIN' THEN 1 ELSE 0 END) AS izin,
        SUM(CASE WHEN pd.status_kehadiran = 'SAKIT' THEN 1 ELSE 0 END) AS sakit

      FROM guru g
      LEFT JOIN presensi_detail pd
        ON pd.id_guru = g.id_guru
       AND DATE(COALESCE(pd.jam_masuk, pd.created_at)) BETWEEN :mulai AND :selesai
      $whereGuru
      GROUP BY g.id_guru, g.nama_guru, g.nip
      ORDER BY g.nama_guru ASC
    ";
  }

  private function fetchRows(): array
  {
    $pdo = Db::pdo();
    [$mulai, $selesai, $idGuru] = $this->getFilters();

    $whereGuru = "";
    $params = [
      ':mulai'   => $mulai,
      ':selesai' => $selesai,
    ];

    if ($idGuru !== '') {
      $whereGuru = "WHERE g.id_guru = :id_guru";
      $params[':id_guru'] = $idGuru;
    }

    $stmt = $pdo->prepare($this->sqlRekapPerGuru($whereGuru));
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $jumlahHari = $this->hitungJumlahHari($mulai, $selesai);

    foreach ($rows as &$r) {
      $hadir  = (int)($r['hadir'] ?? 0);
      $lambat = (int)($r['lambat'] ?? 0);
      $izin   = (int)($r['izin'] ?? 0);
      $sakit  = (int)($r['sakit'] ?? 0);

      // tidak hadir = total hari - (hadir + lambat + izin + sakit)
      $tidakHadir = $jumlahHari - ($hadir + $lambat + $izin + $sakit);
      if ($tidakHadir < 0) $tidakHadir = 0;

      $r['hadir'] = $hadir;
      $r['lambat'] = $lambat;
      $r['izin'] = $izin;
      $r['sakit'] = $sakit;
      $r['tidak_hadir'] = $tidakHadir;
    }
    unset($r);

    return [$mulai, $selesai, $idGuru, $rows, $jumlahHari];
  }

  public function index($req, $res)
  {
    $pdo = Db::pdo();
    $pageTitle = 'Laporan Kehadiran';

    [$mulai, $selesai, $idGuru, $rows, $jumlahHari] = $this->fetchRows();

    $guru = $pdo->query("
      SELECT id_guru, nama_guru, nip
      FROM guru
      ORDER BY nama_guru ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $contentFile = __DIR__ . '/../views/laporan/index.php';
    $layoutFile  = __DIR__ . '/../views/layouts/admin.php';

    require $layoutFile;
  }

  public function print($req, $res)
  {
    [$mulai, $selesai, $idGuru, $rows, $jumlahHari] = $this->fetchRows();
    require __DIR__ . '/../views/laporan/print.php';
  }

  public function pdf($req, $res)
  {
    [$mulai, $selesai, $idGuru, $rows, $jumlahHari] = $this->fetchRows();
    require __DIR__ . '/../views/laporan/pdf_nocomposer.php';
  }

  public function exportExcel($req, $res)
  {
    [$mulai, $selesai, $idGuru, $rows, $jumlahHari] = $this->fetchRows();

    $filename = "rekap_kehadiran_{$mulai}_sd_{$selesai}.csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');

    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($out, ['Nama Guru', 'NIP', 'Hadir', 'Lambat', 'Izin', 'Sakit', 'Tidak Hadir']);

    foreach ($rows as $r) {
      fputcsv($out, [
        $r['nama_guru'] ?? '',
        $r['nip'] ?? '',
        (int)($r['hadir'] ?? 0),
        (int)($r['lambat'] ?? 0),
        (int)($r['izin'] ?? 0),
        (int)($r['sakit'] ?? 0),
        (int)($r['tidak_hadir'] ?? 0),
      ]);
    }

    fclose($out);
    exit;
  }
}