<?php function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title><?= h($title ?? 'Laporan') ?></title>
  <style>
    body{font-family:DejaVu Sans, Arial, sans-serif;font-size:12px;color:#111;}
    h2{margin:0 0 6px;text-align:center;}
    .meta{margin:0 0 12px;text-align:center;color:#444;}
    table{width:100%;border-collapse:collapse;}
    th,td{border:1px solid #000;padding:6px;}
    th{text-align:center;background:#f2f2f2;}
    td:nth-child(2),td:nth-child(3),td:nth-child(4){text-align:center;}
    .footer{margin-top:16px;font-size:11px;color:#555;}
  </style>
</head>
<body>
  <h2><?= h($title ?? 'Laporan Kehadiran Guru') ?></h2>
  <div class="meta">Periode: <?= h($mulai) ?> s/d <?= h($selesai) ?></div>

  <table>
    <thead>
      <tr>
        <th>Nama Guru</th>
        <th>Hadir</th>
        <th>Lambat</th>
        <th>Izin</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="4">Tidak ada data.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= h($r['nama_guru']) ?><br><small><?= h($r['nip']) ?></small></td>
            <td><?= (int)$r['hadir'] ?></td>
            <td><?= (int)$r['lambat'] ?></td>
            <td><?= (int)$r['izin'] ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="footer">Dicetak pada: <?= date('Y-m-d H:i') ?></div>
</body>
</html>