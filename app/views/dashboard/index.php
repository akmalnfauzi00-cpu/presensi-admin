<?php function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } ?>
<?php $today = date('Y-m-d'); ?>

<div class="db-header">
    <div class="db-welcome">
        <h1>Halo, Admin </h1>
        <p>Cek kondisi kehadiran guru SMP Muhammadiyah 2 hari ini.</p>
    </div>
    <div class="db-date-pill">
        <span><?= h(date('l, d F Y')) ?></span>
    </div>
</div>

<div class="bento-grid">
    <a class="bento-item stat-blue" href="<?= $base ?>/guru">
        <div class="bento-content">
            <span class="label">Total Guru</span>
            <span class="value"><?= (int)$totalGuru ?></span>
        </div>
        <div class="bento-footer">Database Guru →</div>
    </a>

    <a class="bento-item stat-green" href="<?= $base ?>/laporan?mulai=<?= h($today) ?>&selesai=<?= h($today) ?>">
        <div class="bento-content">
            <span class="label">Hadir Tepat Waktu</span>
            <span class="value"><?= (int)$hadirHariIni ?></span>
        </div>
        <div class="bento-footer">Lihat Detail</div>
    </a>

    <a class="bento-item stat-orange" href="<?= $base ?>/laporan?mulai=<?= h($today) ?>&selesai=<?= h($today) ?>">
        <div class="bento-content">
            <span class="label">Terlambat</span>
            <span class="value"><?= (int)$terlambatHariIni ?></span>
        </div>
        <div class="bento-footer">Cek Siapa Saja</div>
    </a>

    <a class="bento-item stat-cyan" href="<?= $base ?>/laporan?mulai=<?= h($today) ?>&selesai=<?= h($today) ?>">
        <div class="bento-content">
            <span class="label">Izin / Sakit</span>
            <span class="value"><?= (int)$izinSakitHariIni ?></span>
        </div>
        <div class="bento-footer">Lihat Berkas</div>
    </a>

    <a class="bento-item stat-red" href="<?= $base ?>/laporan?mulai=<?= h($today) ?>&selesai=<?= h($today) ?>">
        <div class="bento-content">
            <span class="label">Tanpa Keterangan</span>
            <span class="value"><?= (int)$tidakHadirHariIni ?></span>
        </div>
        <div class="bento-footer">Tindak Lanjut</div>
    </a>

    <a class="bento-item stat-purple" href="<?= $base ?>/pengajuan">
        <div class="bento-content">
            <span class="label">Butuh Verifikasi</span>
            <span class="value"><?= (int)($pengajuanMenunggu ?? 0) ?></span>
        </div>
        <div class="bento-footer">Buka Pengajuan</div>
    </a>
</div>

<div class="db-container">
    <div class="db-main card-glass">
        <?php
          // Perbaikan Error: Deklarasikan $avg di sini jika belum ada
          $sum = 0;
          foreach ($trend as $t) $sum += (int)$t['value'];
          $calculatedAvg = round($sum / max(1, count($trend)));
        ?>
        <div class="card-header">
            <h3>Tren Kehadiran</h3>
            <span class="avg-tag">Rata-rata: <?= (int)$calculatedAvg ?></span>
        </div>
        <div class="chart-area">
            <?php foreach ($trend as $t):
                $hBar = $maxTrend > 0 ? (int)round(($t['value'] / $maxTrend) * 100) : 5;
            ?>
                <div class="bar-item">
                    <div class="bar-track">
                        <div class="bar-fill" style="height:<?= max(5, $hBar) ?>%;">
                            <span class="bar-tooltip"><?= (int)$t['value'] ?></span>
                        </div>
                    </div>
                    <span class="bar-label"><?= h(substr($t['label'],0,3)) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="db-side card-glass">
        <div class="card-header">
            <h3>Aktivitas Baru</h3>
            <div class="live-dot"></div>
        </div>
        <div class="activity-feed">
            <?php if (empty($aktivitas)): ?>
                <p class="empty-text">Belum ada absen masuk hari ini.</p>
            <?php else: ?>
                <?php foreach ($aktivitas as $a):
                    $status = strtoupper((string)($a['status_kehadiran'] ?? ''));
                    $isLate = (int)($a['is_terlambat'] ?? 0);
                    // Ambil Tanggal dan Jam
                    $timestamp = strtotime($a['created_at']);
                    $dateFormatted = date('d M', $timestamp);
                    $timeFormatted = date('H:i', $timestamp);
                ?>
                    <div class="feed-item">
                        <div class="avatar"><?= h(substr($a['nama_guru'], 0, 1)) ?></div>
                        <div class="feed-info">
                            <div style="display:flex; justify-content: space-between; align-items: flex-start;">
                                <strong><?= h($a['nama_guru']) ?></strong>
                                <span class="feed-date-time"><?= h($dateFormatted) ?>, <?= h($timeFormatted) ?></span>
                            </div>
                            <div class="tags">
                                <span class="tag <?= strtolower($status) ?>"><?= h($status) ?></span>
                                <?php if ($isLate): ?><span class="tag late">Terlambat</span><?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    /* CSS Dasar Tetap Sama, Hanya Penyesuaian Style Baru */
    :root {
        --blue: #3b82f6; --green: #10b981; --orange: #f59e0b;
        --red: #ef4444; --purple: #8b5cf6; --text: #1e293b;
    }

    .db-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .db-welcome h1 { font-size: 24px; font-weight: 800; color: var(--text); margin: 0; }
    .db-welcome p { color: #64748b; margin: 5px 0 0 0; }
    .db-date-pill { background: #fff; padding: 10px 20px; border-radius: 99px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); font-weight: 600; border: 1px solid #f1f5f9; }

    .bento-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .bento-item { background: #fff; padding: 24px; border-radius: 24px; text-decoration: none; border: 1px solid #f1f5f9; transition: all 0.3s ease; display: flex; flex-direction: column; gap: 5px; }
    .bento-item:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05); }
    
    .bento-content .label { display: block; color: #64748b; font-size: 14px; font-weight: 600; }
    .bento-content .value { display: block; font-size: 32px; font-weight: 800; color: var(--text); margin-top: 5px; }
    .bento-footer { font-size: 12px; font-weight: 700; color: #94a3b8; border-top: 1px solid #f8fafc; padding-top: 12px; margin-top: 10px;}

    .stat-blue { background: #eff6ff; } .stat-blue .value { color: #1d4ed8; }
    .stat-green { background: #ecfdf5; } .stat-green .value { color: #059669; }
    .stat-orange { background: #fffbeb; } .stat-orange .value { color: #d97706; }
    .stat-cyan { background: #f0f9ff; } .stat-red { background: #fef2f2; } .stat-purple { background: #f5f3ff; }

    .db-container { display: flex; gap: 25px; }
    .db-main { flex: 2; } .db-side { flex: 1.2; } /* Side sedikit diperlebar untuk tanggal */
    .card-glass { background: rgba(255,255,255,0.8); backdrop-filter: blur(10px); border-radius: 28px; padding: 30px; border: 1px solid #fff; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); }
    .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .card-header h3 { margin: 0; font-size: 18px; font-weight: 800; }

    .avg-tag { background: var(--text); color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }

    .chart-area { display: flex; height: 200px; align-items: flex-end; justify-content: space-between; }
    .bar-track { width: 35px; height: 160px; background: #f1f5f9; border-radius: 12px; display: flex; align-items: flex-end; overflow: hidden; }
    .bar-fill { width: 100%; background: linear-gradient(to top, var(--blue), #60a5fa); border-radius: 12px; transition: height 1s ease; position: relative; }
    .bar-label { font-size: 12px; font-weight: 700; color: #94a3b8; margin-top: 10px; }

    .activity-feed { display: flex; flex-direction: column; gap: 20px; }
    .feed-item { display: flex; align-items: flex-start; gap: 15px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; }
    .avatar { width: 40px; height: 40px; background: #e2e8f0; border-radius: 12px; display: grid; place-items: center; font-weight: 800; flex-shrink: 0; }
    .feed-info { flex: 1; }
    .feed-date-time { font-size: 11px; color: #94a3b8; font-weight: 600; }
    .tag { font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 6px; text-transform: uppercase; margin-top: 5px; display: inline-block;}
    .tag.hadir { background: #d1fae5; color: #065f46; }
    .tag.izin, .tag.sakit { background: #e0f2fe; color: #075985; }
    .tag.late { background: #fee2e2; color: #991b1b; }

    .live-dot { width: 10px; height: 10px; background: #10b981; border-radius: 50%; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2); }

    @media (max-width: 1000px) { .db-container { flex-direction: column; } }
</style>