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
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;
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

  /**
   * PERBAIKAN: Menghitung hari kerja saja (Tanpa Libur & Minggu)
   */
  private function hitungJumlahHariKerja(string $mulai, string $selesai): int
  {
    $pdo = Db::pdo();
    
    // Ambil semua tanggal libur dari database dalam periode tersebut
    $st = $pdo->prepare("SELECT tanggal FROM hari_libur WHERE tanggal BETWEEN ? AND ?");
    $st->execute([$mulai, $selesai]);
    $listLibur = $st->fetchAll(PDO::FETCH_COLUMN);

    $start = strtotime($mulai);
    $end   = strtotime($selesai);
    $count = 0;

    // Iterasi setiap hari dalam periode
    for ($i = $start; $i <= $end; $i = strtotime("+1 day", $i)) {
      $tgl = date('Y-m-d', $i);
      $namaHari = date('D', $i); // Contoh: Sun, Mon, etc.

      // JANGAN hitung jika hari Minggu ATAU ada di tabel hari_libur
      if ($namaHari === 'Sun' || in_array($tgl, $listLibur)) {
        continue;
      }
      $count++;
    }

    return $count;
  }

  private function sqlRekapPerGuru(string $whereGuru): string
  {
    return "
      SELECT
        g.id_guru,
        g.nama_guru,
        g.nip,
        SUM(CASE WHEN pd.status_kehadiran = 'HADIR' AND COALESCE(pd.is_terlambat, 0) = 0 THEN 1 ELSE 0 END) AS hadir,
        SUM(CASE WHEN pd.status_kehadiran = 'HADIR' AND COALESCE(pd.is_terlambat, 0) = 1 THEN 1 ELSE 0 END) AS lambat,
        SUM(CASE WHEN pd.status_kehadiran = 'IZIN' THEN 1 ELSE 0 END) AS izin,
        SUM(CASE WHEN pd.status_kehadiran = 'SAKIT' THEN 1 ELSE 0 END) AS sakit
      FROM guru g
      LEFT JOIN (
          presensi_detail pd
          JOIN kehadiran k ON pd.id_presensi = k.id_presensi AND k.tanggal BETWEEN :mulai AND :selesai
      ) ON pd.id_guru = g.id_guru
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

    // Menggunakan fungsi hari kerja yang baru
    $jumlahHariKerja = $this->hitungJumlahHariKerja($mulai, $selesai);

    foreach ($rows as &$r) {
      $hadir  = (int)($r['hadir'] ?? 0);
      $lambat = (int)($r['lambat'] ?? 0);
      $izin   = (int)($r['izin'] ?? 0);
      $sakit  = (int)($r['sakit'] ?? 0);

      // Tidak Hadir = Total Hari Kerja - (Hadir + Lambat + Izin + Sakit)
      $tidakHadir = $jumlahHariKerja - ($hadir + $lambat + $izin + $sakit);
      if ($tidakHadir < 0) $tidakHadir = 0;

      $r['hadir'] = $hadir;
      $r['lambat'] = $lambat;
      $r['izin'] = $izin;
      $r['sakit'] = $sakit;
      $r['tidak_hadir'] = $tidakHadir;
    }
    unset($r);

    return [$mulai, $selesai, $idGuru, $rows, $jumlahHariKerja];
  }

  public function index($req, $res)
  {
    $pdo = Db::pdo();
    $pageTitle = 'Laporan Kehadiran';
    [$mulai, $selesai, $idGuru, $rows, $jumlahHari] = $this->fetchRows();
    $guru = $pdo->query("SELECT id_guru, nama_guru, nip FROM guru ORDER BY nama_guru ASC")->fetchAll(PDO::FETCH_ASSOC);
    $contentFile = __DIR__ . '/../views/laporan/index.php';
    $layoutFile  = __DIR__ . '/../views/layouts/admin.php';
    require $layoutFile;
  }

  public function exportPdf($req, $res)
  {
    [$mulai, $selesai, $idGuru, $rows, $jumlahHari] = $this->fetchRows();
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

    $options = new \Dompdf\Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new \Dompdf\Dompdf($options);

    $html = '
    <html>
    <head>
      <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; }
        h2 { text-align: center; margin-bottom: 5px; }
        .sub { text-align: center; color: #555; margin-top: 0; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 8px; }
        th { background-color: #f3f4f6; text-align: center; }
        .text-center { text-align: center; }
      </style>
    </head>
    <body>
      <h2>Laporan Rekap Kehadiran Guru</h2>
      <p class="sub">Periode: ' . date('d-m-Y', strtotime($mulai)) . ' s/d ' . date('d-m-Y', strtotime($selesai)) . ' (Total Hari Kerja: '.$jumlahHari.')</p>
      <table>
        <thead>
          <tr>
            <th style="width: 5%;">No</th>
            <th style="text-align: left;">Nama Guru</th>
            <th style="width: 10%;">Hadir</th>
            <th style="width: 10%;">Lambat</th>
            <th style="width: 10%;">Izin</th>
            <th style="width: 10%;">Sakit</th>
            <th style="width: 15%;">Tidak Hadir</th>
          </tr>
        </thead>
        <tbody>';

    if (empty($rows)) {
      $html .= '<tr><td colspan="7" class="text-center">Tidak ada data.</td></tr>';
    } else {
      $no = 1;
      foreach ($rows as $r) {
        $html .= '<tr>
          <td class="text-center">' . $no++ . '</td>
          <td><b>' . htmlspecialchars($r['nama_guru']) . '</b><br><small>KTA: ' . htmlspecialchars($r['nip']) . '</small></td>
          <td class="text-center">' . (int)$r['hadir'] . '</td>
          <td class="text-center">' . (int)$r['lambat'] . '</td>
          <td class="text-center">' . (int)$r['izin'] . '</td>
          <td class="text-center">' . (int)$r['sakit'] . '</td>
          <td class="text-center"><b>' . (int)$r['tidak_hadir'] . '</b></td>
        </tr>';
      }
    }

    $html .= '</tbody></table>
      <p style="margin-top:20px; text-align:right; color:#555;">Dicetak pada: ' . date('d-m-Y H:i') . '</p>
    </body></html>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("Rekap_Kehadiran.pdf", ["Attachment" => 1]);
  }
}