<?php function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>PDF Laporan Kehadiran</title>
  <style>
    @page {
      size: A4 landscape;
      margin: 16mm;
    }

    * { box-sizing: border-box; }

    body {
      font-family: Arial, Helvetica, sans-serif;
      color: #111827;
      font-size: 12px;
      margin: 0;
      background: #fff;
    }

    .page {
      width: 100%;
    }

    .toolbar {
      margin-bottom: 16px;
    }

    .toolbar a,
    .toolbar button {
      display: inline-block;
      padding: 10px 14px;
      border: none;
      border-radius: 8px;
      background: #111827;
      color: #fff;
      text-decoration: none;
      cursor: pointer;
      margin-right: 8px;
      font-size: 13px;
    }

    .head {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
      border-bottom: 2px solid #111827;
      padding-bottom: 12px;
      margin-bottom: 16px;
    }

    .title {
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 4px;
    }

    .sub {
      color: #4b5563;
      line-height: 1.5;
      margin: 0;
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
      table-layout: fixed;
    }

    th, td {
      border: 1px solid #374151;
      padding: 8px 10px;
      word-wrap: break-word;
    }

    th {
      background: #f3f4f6;
      text-align: center;
      font-weight: 700;
    }

    td.center { text-align: center; }
    td.left { text-align: left; }

    .name {
      font-weight: 700;
      margin-bottom: 2px;
    }

    .nip {
      color: #4b5563;
      font-size: 11px;
    }

    .summary {
      margin-top: 16px;
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 10px;
    }

    .box {
      border: 1px solid #d1d5db;
      border-radius: 8px;
      padding: 10px;
      background: #fafafa;
    }

    .box .label {
      color: #6b7280;
      font-size: 11px;
      margin-bottom: 4px;
    }

    .box .value {
      font-size: 18px;
      font-weight: 700;
      color: #111827;
    }

    .foot-note {
      margin-top: 14px;
      color: #4b5563;
      line-height: 1.6;
    }

    @media print {
      .toolbar { display: none !important; }
      body { margin: 0; }
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="toolbar">
      <button onclick="window.print()">Print / Simpan PDF</button>
      <a href="javascript:window.close()">Tutup</a>
    </div>

    <div class="head">
      <div>
        <div class="title">Laporan Rekap Kehadiran Guru</div>
        <p class="sub">Periode: <?= h(date('d-m-Y', strtotime($mulai))) ?> s/d <?= h(date('d-m-Y', strtotime($selesai))) ?></p>
        <p class="sub">Total hari periode: <?= (int)$jumlahHari ?> hari</p>
      </div>
      <div class="meta">
        <div>Dicetak: <?= h(date('d-m-Y H:i')) ?></div>
        <div>Jumlah guru: <?= count($rows) ?></div>
      </div>
    </div>

    <?php
      $totalHadir = 0;
      $totalLambat = 0;
      $totalIzin = 0;
      $totalSakit = 0;
      $totalTidakHadir = 0;
      foreach ($rows as $r) {
        $totalHadir += (int)$r['hadir'];
        $totalLambat += (int)$r['lambat'];
        $totalIzin += (int)$r['izin'];
        $totalSakit += (int)$r['sakit'];
        $totalTidakHadir += (int)$r['tidak_hadir'];
      }
    ?>

    <table>
      <thead>
        <tr>
          <th style="width:44px;">No</th>
          <th>Nama Guru</th>
          <th style="width:80px;">Hadir</th>
          <th style="width:80px;">Lambat</th>
          <th style="width:80px;">Izin</th>
          <th style="width:80px;">Sakit</th>
          <th style="width:100px;">Tidak Hadir</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="7" class="center">Tidak ada data.</td>
          </tr>
        <?php else: ?>
          <?php $no = 1; ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td class="center"><?= $no++ ?></td>
              <td class="left">
                <div class="name"><?= h($r['nama_guru']) ?></div>
                <div class="nip">NIP: <?= h($r['nip']) ?></div>
              </td>
              <td class="center"><?= (int)$r['hadir'] ?></td>
              <td class="center"><?= (int)$r['lambat'] ?></td>
              <td class="center"><?= (int)$r['izin'] ?></td>
              <td class="center"><?= (int)$r['sakit'] ?></td>
              <td class="center"><?= (int)$r['tidak_hadir'] ?></td>
            </tr>
          <?php endforeach; ?>

          <tr>
            <th colspan="2">TOTAL</th>
            <th><?= $totalHadir ?></th>
            <th><?= $totalLambat ?></th>
            <th><?= $totalIzin ?></th>
            <th><?= $totalSakit ?></th>
            <th><?= $totalTidakHadir ?></th>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="summary">
      <div class="box">
        <div class="label">Total Hadir</div>
        <div class="value"><?= $totalHadir ?></div>
      </div>
      <div class="box">
        <div class="label">Total Lambat</div>
        <div class="value"><?= $totalLambat ?></div>
      </div>
      <div class="box">
        <div class="label">Total Izin</div>
        <div class="value"><?= $totalIzin ?></div>
      </div>
      <div class="box">
        <div class="label">Total Sakit</div>
        <div class="value"><?= $totalSakit ?></div>
      </div>
      <div class="box">
        <div class="label">Total Tidak Hadir</div>
        <div class="value"><?= $totalTidakHadir ?></div>
      </div>
    </div>

    <div class="foot-note">
      Tidak Hadir dihitung dengan rumus: <strong>jumlah hari periode - hadir - izin - sakit</strong>.
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