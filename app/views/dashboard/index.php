<?php function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } ?>
<?php $today = date('Y-m-d'); ?>

<div class="card">
  <div class="card-head" style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
    <div>
      <div class="d-title">Dashboard</div>
      <div class="p">Ringkasan presensi hari ini</div>
    </div>
    <div class="p" style="white-space:nowrap;"><?= h(date('l, d F Y')) ?></div>
  </div>
</div>

<div style="height:12px;"></div>

<div class="d-cards">

  <a class="card d-stat d-link"
     href="<?= $base ?>/guru"
     style="text-decoration:none;color:inherit;display:block;">
    <div class="card-body">
      <div class="p">Guru Terdaftar</div>
      <div class="d-val"><?= (int)$totalGuru ?></div>
      <div class="d-sub">Klik untuk lihat data guru</div>
    </div>
  </a>

  <a class="card d-stat d-link"
     href="<?= $base ?>/laporan?mulai=<?= h($today) ?>&selesai=<?= h($today) ?>"
     style="text-decoration:none;color:inherit;display:block;">
    <div class="card-body">
      <div class="p">Hadir</div>
      <div class="d-val"><?= (int)$hadirHariIni ?></div>
      <div class="d-sub">Klik untuk lihat laporan hari ini</div>
    </div>
  </a>

  <a class="card d-stat d-link"
     href="<?= $base ?>/laporan?mulai=<?= h($today) ?>&selesai=<?= h($today) ?>"
     style="text-decoration:none;color:inherit;display:block;">
    <div class="card-body">
      <div class="p">Terlambat</div>
      <div class="d-val" style="color:#b45309;"><?= (int)$terlambatHariIni ?></div>
      <div class="d-sub">Klik untuk lihat laporan hari ini</div>
    </div>
  </a>

  <a class="card d-stat d-link"
     href="<?= $base ?>/laporan?mulai=<?= h($today) ?>&selesai=<?= h($today) ?>"
     style="text-decoration:none;color:inherit;display:block;">
    <div class="card-body">
      <div class="p">Izin / Sakit</div>
      <div class="d-val"><?= (int)$izinSakitHariIni ?></div>
      <div class="d-sub">Klik untuk lihat laporan hari ini</div>
    </div>
  </a>

  <a class="card d-stat d-link"
     href="<?= $base ?>/laporan?mulai=<?= h($today) ?>&selesai=<?= h($today) ?>"
     style="text-decoration:none;color:inherit;display:block;">
    <div class="card-body">
      <div class="p">Tidak Hadir</div>
      <div class="d-val" style="color:#7c3aed;"><?= (int)$tidakHadirHariIni ?></div>
      <div class="d-sub">Guru yang belum punya presensi hari ini</div>
    </div>
  </a>

  <a class="card d-stat d-link"
     href="<?= $base ?>/pengajuan"
     style="text-decoration:none;color:inherit;display:block;">
    <div class="card-body">
      <div class="p">Pengajuan Menunggu</div>
      <div class="d-val" style="color:#2563eb;"><?= (int)($pengajuanMenunggu ?? 0) ?></div>
      <div class="d-sub">Klik untuk verifikasi izin / sakit</div>
    </div>
  </a>

</div>

<div style="height:14px;"></div>

<div class="card d-full">
  <div class="card-head" style="display:flex;justify-content:space-between;align-items:center;">
    <div class="d-section">Tren Kehadiran (7 hari)</div>
    <div class="p">Hadir per hari</div>
  </div>

  <div class="card-body">
    <div class="d-trend">
      <?php foreach ($trend as $t):
        $hBar = $maxTrend > 0 ? (int)round(($t['value'] / $maxTrend) * 140) : 10;
        if ($hBar < 10) $hBar = 10;
      ?>
        <div class="d-trend-item">
          <div class="d-bar-wrap">
            <div class="d-bar" style="height:<?= $hBar ?>px;"></div>
          </div>
          <div class="d-day"><?= h(substr($t['label'],0,3)) ?></div>
          <div class="d-num"><?= (int)$t['value'] ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php
      $sum = 0;
      foreach ($trend as $t) $sum += (int)$t['value'];
      $avg = round($sum / max(1, count($trend)));
    ?>
    <div class="p" style="margin-top:10px;color:#64748b;">
      Rata-rata 7 hari: <?= (int)$avg ?>
    </div>
  </div>
</div>

<div style="height:14px;"></div>

<div class="card d-full">
  <div class="card-head" style="display:flex;justify-content:space-between;align-items:center;">
    <div class="d-section">Aktivitas Terkini</div>
    <div class="p">5 terakhir</div>
  </div>

  <div class="card-body" style="display:flex;flex-direction:column;gap:10px;">
    <?php if (empty($aktivitas)): ?>
      <div class="p" style="color:#64748b;">Belum ada aktivitas presensi.</div>
    <?php else: ?>
      <?php foreach ($aktivitas as $a):
        $status = strtoupper((string)($a['status_kehadiran'] ?? ''));
        $late = (int)($a['is_terlambat'] ?? 0);
        $time = !empty($a['created_at']) ? date('H:i', strtotime($a['created_at'])) : '-';
        $badgeStyle = "background:#e5e7eb;color:#334155;";
        if ($status === 'HADIR') $badgeStyle = "background:#dcfce7;color:#166534;";
        if ($status === 'IZIN' || $status === 'SAKIT') $badgeStyle = "background:#e0f2fe;color:#075985;";
      ?>
        <div class="d-act">
          <div>
            <div class="d-act-name"><?= h($a['nama_guru'] ?? '-') ?></div>
            <div class="p" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
              <span class="d-badge" style="<?= $badgeStyle ?>"><?= h($status) ?></span>
              <?php if ($status === 'HADIR' && $late === 1): ?>
                <span class="d-badge" style="background:#ffedd5;color:#9a3412;">Terlambat</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="d-act-time"><?= h($time) ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<style>
  .d-title, .d-section, .d-val, .d-rank-title, .d-rank-name, .d-day, .d-num,
  .d-act-name, .d-act-time, .d-sub, .d-badge { font-weight: 400 !important; }

  .d-title{font-size:18px;letter-spacing:.2px;color:#0f172a;}
  .d-section{color:#0f172a;}
  .d-sub{margin-top:10px;color:#64748b;font-size:12px;}
  .d-val{font-size:30px;line-height:1;margin-top:6px;color:#0f172a;}

  .d-cards{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;}
  .d-stat .card-body{padding:14px 16px;}
  .d-full{width:100%;}

  .d-link{cursor:pointer;transition:transform .12s ease, box-shadow .12s ease;}
  .d-link:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(15,23,42,.08);}

  .d-trend{display:grid;grid-template-columns:repeat(7,1fr);gap:10px;align-items:end;}
  .d-trend-item{text-align:center;}
  .d-bar-wrap{height:150px;display:flex;align-items:flex-end;justify-content:center;}
  .d-bar{width:100%;background:#dbeafe;border-radius:14px;}
  .d-day{margin-top:8px;font-size:12px;color:#64748b;}
  .d-num{font-size:12px;color:#0f172a;margin-top:2px;}

  .d-act{display:flex;justify-content:space-between;align-items:center;padding:12px;border:1px solid #eef2f7;border-radius:14px;gap:12px;}
  .d-badge{display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;}
  .d-act-name{color:#0f172a;}
  .d-act-time{color:#0f172a;white-space:nowrap;}

  @media (max-width:1400px){
    .d-cards{grid-template-columns:repeat(3,1fr);}
  }

  @media (max-width:900px){
    .d-cards{grid-template-columns:repeat(2,1fr);}
    .d-trend{grid-template-columns:repeat(4,1fr);}
  }

  @media (max-width:640px){
    .d-cards{grid-template-columns:1fr;}
    .d-trend{grid-template-columns:repeat(2,1fr);}
    .d-act{flex-direction:column;align-items:flex-start;}
    .d-act-time{white-space:normal;}
  }
</style>