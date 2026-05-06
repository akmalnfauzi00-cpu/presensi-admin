<?php function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } ?>
<?php
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base === '/') $base = '';

$totalMenunggu = 0;
$totalDisetujui = 0;
$totalDitolak = 0;

foreach ($rows as $x) {
    $st = strtoupper((string)($x['status_verifikasi'] ?? ''));
    if ($st === 'MENUNGGU') $totalMenunggu++;
    if ($st === 'DISETUJUI') $totalDisetujui++;
    if ($st === 'DITOLAK') $totalDitolak++;
}
?>

<div class="card">
    <div class="card-head" style="display:flex;justify-content:space-between;align-items:center;">
        <div>
            <div style="font-weight:700; font-size: 18px;">Pengajuan Izin / Sakit</div>
            <div class="p" style="font-size: 13px; color: #64748b;">Home / Pengajuan</div>
        </div>

        <div style="display:flex;align-items:center;gap:10px;">
            <?php if (!empty($totalMenunggu)): ?>
                <span style="background:#fef2f2;color:#b91c1c;padding:6px 14px;border-radius:999px;font-size:12px;font-weight:700;border:1px solid #fecaca;">
                    <?= (int)$totalMenunggu ?> Menunggu Verifikasi
                </span>
            <?php endif; ?>

            <a href="<?= $base ?>/laporan" class="btn primary">
                Buka Laporan
            </a>
        </div>
    </div>
</div>

<div style="height:12px;"></div>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="flash ok"><?= h($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="flash err"><?= h($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:14px;">
    <div class="sum-card" style="border-left:4px solid #0ea5e9;">
        <div class="sum-label">Menunggu</div>
        <div class="sum-value"><?= $totalMenunggu ?></div>
    </div>

    <div class="sum-card" style="border-left:4px solid #16a34a;">
        <div class="sum-label">Disetujui</div>
        <div class="sum-value"><?= $totalDisetujui ?></div>
    </div>

    <div class="sum-card" style="border-left:4px solid #dc2626;">
        <div class="sum-label">Ditolak</div>
        <div class="sum-value"><?= $totalDitolak ?></div>
    </div>
</div>

<div class="card" style="margin-bottom:14px;">
    <div class="card-head">Filter Pengajuan</div>
    <div class="card-body" style="padding: 16px;">
        <form method="GET" action="<?= $base ?>/pengajuan" style="display:grid;grid-template-columns:1fr 1fr 1.2fr auto;gap:12px;align-items:end;">
            <div>
                <label class="lbl">Status</label>
                <select class="in" name="status">
                    <option value="">Semua Status</option>
                    <option value="MENUNGGU" <?= (($_GET['status'] ?? '') === 'MENUNGGU') ? 'selected' : '' ?>>Menunggu</option>
                    <option value="DISETUJUI" <?= (($_GET['status'] ?? '') === 'DISETUJUI') ? 'selected' : '' ?>>Disetujui</option>
                    <option value="DITOLAK" <?= (($_GET['status'] ?? '') === 'DITOLAK') ? 'selected' : '' ?>>Ditolak</option>
                </select>
            </div>

            <div>
                <label class="lbl">Jenis</label>
                <select class="in" name="jenis">
                    <option value="">Semua Jenis</option>
                    <option value="IZIN" <?= (($_GET['jenis'] ?? '') === 'IZIN') ? 'selected' : '' ?>>Izin</option>
                    <option value="SAKIT" <?= (($_GET['jenis'] ?? '') === 'SAKIT') ? 'selected' : '' ?>>Sakit</option>
                </select>
            </div>

            <div>
                <label class="lbl">Cari Guru / NIP / Alasan</label>
                <input class="in" type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>" placeholder="Masukkan kata kunci">
            </div>

            <button class="btn primary" type="submit" style="height: 42px;">Terapkan Filter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-head">Daftar Pengajuan</div>
    <div class="card-body" style="padding:0; overflow-x: auto;">
        <table class="main-table">
            <thead>
                <tr>
                    <th style="text-align:left; width: 200px;">GURU</th>
                    <th style="width: 100px;">JENIS</th>
                    <th style="width: 150px;">TANGGAL</th>
                    <th style="text-align:left;">ALASAN</th>
                    <th style="width: 120px;">LAMPIRAN</th>
                    <th style="width: 120px;">STATUS</th>
                    <th style="width: 100px;">DIKIRIM</th>
                    <th style="text-align:center; width: 280px;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="8" style="padding:40px; color:#94a3b8; text-align: center; font-style: italic;">Belum ada pengajuan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <?php
                            $status = strtoupper((string)($r['status_verifikasi'] ?? 'MENUNGGU'));
                            $lampiranUrl = !empty($r['lampiran_path']) ? $base . $r['lampiran_path'] : '';
                        ?>
                        <tr>
                            <td class="td" style="text-align:left;">
                                <div style="font-weight:700; color: #1e293b;"><?= h($r['nama_guru']) ?></div>
                                <div style="font-size: 12px; color: #64748b;"><?= h($r['nip']) ?></div>
                            </td>

                            <td class="td">
                                <?php if (($r['jenis'] ?? '') === 'SAKIT'): ?>
                                    <span class="badge danger">SAKIT</span>
                                <?php else: ?>
                                    <span class="badge warn">IZIN</span>
                                <?php endif; ?>
                            </td>

                            <td class="td">
                                <div style="font-weight: 700; color: #2563eb;"><?= h($r['tanggal_mulai']) ?></div>
                                <?php if(!empty($r['tanggal_selesai']) && $r['tanggal_mulai'] !== $r['tanggal_selesai']): ?>
                                    <div style="margin: 2px 0; color: #94a3b8; font-size: 10px;">s/d</div>
                                    <div style="font-weight: 700; color: #2563eb;"><?= h($r['tanggal_selesai']) ?></div>
                                <?php else: ?>
                                    <small style="color: #64748b;">(1 Hari)</small>
                                <?php endif; ?>
                            </td>

                            <td class="td" style="text-align:left;">
                                <div style="line-height: 1.5;"><?= nl2br(h($r['alasan'] ?? '')) ?></div>
                                <?php if (!empty($r['catatan_admin'])): ?>
                                    <div class="admin-note">
                                        <b>Catatan admin:</b><br>
                                        <?= nl2br(h($r['catatan_admin'])) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td class="td">
                                <?php if ($lampiranUrl !== ''): ?>
                                    <?php 
                                        $ext = strtolower(pathinfo($lampiranUrl, PATHINFO_EXTENSION));
                                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): 
                                    ?>
                                        <a href="<?= h($lampiranUrl) ?>" target="_blank" class="lampiran-link">
                                            <img src="<?= h($lampiranUrl) ?>" alt="Lampiran">
                                            <span>Lihat Gambar</span>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= h($lampiranUrl) ?>" target="_blank" class="btn-pdf">
                                            Buka PDF
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:#cbd5e1; font-size: 12px;">-</span>
                                <?php endif; ?>
                            </td>

                            <td class="td">
                                <?php if ($status === 'DISETUJUI'): ?>
                                    <span class="badge ok">DISETUJUI</span>
                                <?php elseif ($status === 'DITOLAK'): ?>
                                    <span class="badge danger-soft">DITOLAK</span>
                                <?php else: ?>
                                    <span class="badge pending">MENUNGGU</span>
                                <?php endif; ?>
                            </td>

                            <td class="td" style="font-size: 12px; color: #64748b; line-height: 1.4;">
                                <b><?= h(date('d/m/y', strtotime($r['created_at'] ?? 'now'))) ?></b><br>
                                <?= h(date('H:i', strtotime($r['created_at'] ?? 'now'))) ?>
                            </td>

                            <td class="td">
                                <div class="aksi-container">
                                    <?php if ($status === 'MENUNGGU'): ?>
                                        <form method="POST" action="<?= $base ?>/pengajuan/verifikasi" class="verif-form">
                                            <input type="hidden" name="id_pengajuan" value="<?= h($r['id_pengajuan']) ?>">
                                            <textarea name="catatan_admin" class="in" placeholder="Tambahkan catatan..."></textarea>
                                            <div class="btn-group">
                                                <button class="btn success" type="submit" name="aksi" value="SETUJUI">Setujui</button>
                                                <button class="btn danger" type="submit" name="aksi" value="TOLAK" onclick="return confirm('Tolak pengajuan ini?')">Tolak</button>
                                            </div>
                                        </form>
                                        <form method="POST" action="<?= $base ?>/pengajuan/delete" onsubmit="return confirm('Hapus pengajuan?');">
                                            <input type="hidden" name="id_pengajuan" value="<?= h($r['id_pengajuan']) ?>">
                                            <button class="btn danger-soft-btn" type="submit">Hapus Pengajuan</button>
                                        </form>
                                    <?php else: ?>
                                        <div class="processed-info">
                                            <span>Telah Diproses</span>
                                            <form method="POST" action="<?= $base ?>/pengajuan/delete" onsubmit="return confirm('Hapus riwayat?');">
                                                <input type="hidden" name="id_pengajuan" value="<?= h($r['id_pengajuan']) ?>">
                                                <button class="btn danger-soft-btn" type="submit">Hapus Riwayat</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    /* Global Card & Table */
    .card { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .card-head { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; font-weight: 700; color: #1e293b; background: #fff; }
    
    .main-table { width: 100%; border-collapse: collapse; min-width: 1100px; }
    .main-table th { background: #f8fafc; padding: 16px 12px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; text-align: center; }
    .main-table td { padding: 16px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; text-align: center; font-size: 14px; }

    /* Inputs & Labels */
    .lbl { display:block; margin:0 0 6px; font-weight:700; font-size:13px; color:#475569; }
    .in { width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:10px; outline:none; background:#fff; font-size: 14px; }
    .in:focus { border-color: #2563eb; ring: 2px solid #dbeafe; }

    /* Flash Messages */
    .flash { padding:14px; border-radius:12px; margin-bottom:16px; font-weight:700; text-align: center; border: 1px solid transparent; }
    .flash.ok { background:#dcfce7; color:#166534; border-color:#bbf7d0; }
    .flash.err { background:#fee2e2; color:#991b1b; border-color:#fecaca; }

    /* Summary Cards */
    .sum-card { background:#fff; border-radius:16px; padding:20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .sum-label { color:#64748b; font-size:13px; font-weight:700; text-transform: uppercase; }
    .sum-value { margin-top:8px; font-size:32px; font-weight:800; color:#1e293b; }

    /* Badges */
    .badge { display:inline-flex; padding:6px 12px; border-radius:999px; font-weight:800; font-size:11px; white-space: nowrap; }
    .badge.ok { background:#dcfce7; color:#15803d; }
    .badge.warn { background:#fef3c7; color:#b45309; }
    .badge.pending { background:#e0f2fe; color:#0369a1; }
    .badge.danger { background:#fee2e2; color:#b91c1c; }
    .badge.danger-soft { background:#f1f5f9; color:#475569; border: 1px solid #e2e8f0; }

    /* Buttons */
    .btn { display:inline-flex; align-items:center; justify-content:center; padding:10px 16px; border:none; border-radius:10px; font-weight:700; cursor:pointer; text-decoration:none; transition: all 0.2s; font-size: 13px; gap: 8px; }
    .btn.primary { background:#2563eb; color:#fff; }
    .btn.success { background:#16a34a; color:#fff; }
    .btn.danger { background:#dc2626; color:#fff; }
    .btn.danger-soft-btn { background:#fff1f2; color:#e11d48; border:1px solid #fecdd3; margin-top: 5px; }
    .btn:hover { filter: brightness(0.9); transform: translateY(-1px); }

    /* Lampiran & Note */
    .lampiran-link { text-decoration: none; display: flex; flex-direction: column; align-items: center; gap: 4px; }
    .lampiran-link img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; }
    .lampiran-link span { font-size: 10px; color: #2563eb; font-weight: 700; }
    
    .btn-pdf { background: #f1f5f9; color: #475569; padding: 6px 12px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 11px; text-decoration: none; font-weight: 700; }
    
    .admin-note { margin-top:8px; padding:10px; background:#f8fafc; border-radius:8px; border-left: 3px solid #cbd5e1; color:#475569; font-size: 12px; text-align: left; }

    /* Aksi Container */
    .aksi-container { display: flex; flex-direction: column; gap: 8px; align-items: stretch; width: 100%; max-width: 250px; margin: 0 auto; }
    .verif-form { display: flex; flex-direction: column; gap: 8px; }
    .btn-group { display: flex; gap: 6px; }
    .btn-group .btn { flex: 1; }
    
    .processed-info { display: flex; flex-direction: column; gap: 6px; }
    .processed-info span { background: #f8fafc; color: #94a3b8; font-weight: 700; font-size: 12px; padding: 10px; border-radius: 10px; border: 1px dashed #cbd5e1; }
</style>