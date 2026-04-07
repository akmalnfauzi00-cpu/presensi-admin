<?php function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Print Laporan Kehadiran</title>
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: Arial, Helvetica, sans-serif;
      color: #111827;
      margin: 24px;
      font-size: 12px;
    }
    .wrap {
      width: 100%;
      max-width: 1100px;
      margin: 0 auto;
    }
    .head {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
      margin-bottom: 16px;
      border-bottom: 2px solid #111827;
      padding-bottom: 12px;
    }
    .title {
      font-size: 20px;
      font-weight: 700;
      margin: 0 0 4px;
    }
    .sub {
      color: #4b5563;
      margin: 0;
      line-height: 1.5;
    }
    .meta {
      text-align: right;
      color: #374151;
      line-height: 1.6;
      white-space: nowrap;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 14px;
    }
    th, td {
      border: 1px solid #374151;
      padding: 8px 10px;
      vertical-align: middle;
    }
    th {
      background: #f3f4f6;
      text-align: center;
      font-weight: 700;
    }
    td.text-left { text-align: left; }
    td.text-center { text-align: center; }
    .name {
      font-weight: 700;
      margin-bottom: 2px;
    }
    .nip {
      color: #4b5563;
      font-size: 11px;
    }
    .footer {
      margin-top: 18px;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
    }
    .note {
      color: #4b5563;
      line-height: 1.5;
    }
    .sign {
      width: 240px;
      text-align: center;
    }
    .sign-space {
      height: 64px;
    }
    @media print {
      body { margin: 0; }
      .no-print { display: none !important; }
      .wrap { max-width: 100%; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="no-print" style="margin-bottom:16px;">
      <button onclick="window.print()" style="padding:10px 14px;border:none;border-radius:8px;background:#111827;color:#fff;cursor:pointer;">
        Print Sekarang
      </button>
    </div>

    <div class="head">
      <div>
        <div class="title">Laporan Rekap Kehadiran Guru</div>
        <p class="sub">Periode: <?= h(date('d-m-Y', strtotime($mulai))) ?> s/d <?= h(date('d-m-Y', strtotime($selesai))) ?></p>
        <p class="sub">Total hari periode: <?= (int)$jumlahHari ?> hari</p>
      </div>
      <div class="meta">
        <div>Tanggal cetak: <?= h(date('d-m-Y H:i')) ?></div>
        <div>Jumlah guru: <?= count($rows) ?></div>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th style="width:40px;">No</th>
          <th>Nama Guru</th>
          <th style="width:85px;">Hadir</th>
          <th style="width:85px;">Lambat</th>
          <th style="width:85px;">Izin</th>
          <th style="width:85px;">Sakit</th>
          <th style="width:100px;">Tidak Hadir</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="7" class="text-center">Tidak ada data.</td>
          </tr>
        <?php else: ?>
          <?php
            $no = 1;
            $totalHadir = 0;
            $totalLambat = 0;
            $totalIzin = 0;
            $totalSakit = 0;
            $totalTidakHadir = 0;
          ?>
          <?php foreach ($rows as $r): ?>
            <?php
              $totalHadir += (int)$r['hadir'];
              $totalLambat += (int)$r['lambat'];
              $totalIzin += (int)$r['izin'];
              $totalSakit += (int)$r['sakit'];
              $totalTidakHadir += (int)$r['tidak_hadir'];
            ?>
            <tr>
              <td class="text-center"><?= $no++ ?></td>
              <td class="text-left">
                <div class="name"><?= h($r['nama_guru']) ?></div>
                <div class="nip">NIP: <?= h($r['nip']) ?></div>
              </td>
              <td class="text-center"><?= (int)$r['hadir'] ?></td>
              <td class="text-center"><?= (int)$r['lambat'] ?></td>
              <td class="text-center"><?= (int)$r['izin'] ?></td>
              <td class="text-center"><?= (int)$r['sakit'] ?></td>
              <td class="text-center"><?= (int)$r['tidak_hadir'] ?></td>
            </tr>
          <?php endforeach; ?>

          <tr>
            <th colspan="2" style="text-align:center;">TOTAL</th>
            <th><?= $totalHadir ?></th>
            <th><?= $totalLambat ?></th>
            <th><?= $totalIzin ?></th>
            <th><?= $totalSakit ?></th>
            <th><?= $totalTidakHadir ?></th>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="footer">
      <div class="note">
        <div><strong>Keterangan:</strong></div>
        <div>Hadir = guru tercatat hadir pada tanggal yang dipilih.</div>
        <div>Lambat = bagian dari hadir dengan status terlambat.</div>
        <div>Tidak Hadir = total hari periode - hadir - izin - sakit.</div>
      </div>

      <div class="sign">
        <div><?= h(date('d F Y')) ?></div>
        <div>Kepala Sekolah / Admin</div>
        <div class="sign-space"></div>
        <div>________________________</div>
      </div>
    </div>
  </div>

  <script>
    window.onload = function() {
      setTimeout(function() {
        window.print();
      }, 300);
    };
  </script>
</body>
</html>