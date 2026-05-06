<?php
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base === '/') $base = '';

$success = Session::pullFlash('success');
$error   = Session::pullFlash('error');

$q = $q ?? ($_GET['q'] ?? '');
$tab = $_GET['tab'] ?? 'aktif';

// --- FILTER DATA ---
$rowsAktif = [];
$rowsPending = [];

if (!empty($rows)) {
    foreach ($rows as $r) {
        $statusCurrent = strtoupper(trim($r['status_aktif'] ?? '')); 
        if ($statusCurrent === 'PENDING' || $statusCurrent === '') {
            $rowsPending[] = $r;
        } else {
            $rowsAktif[] = $r;
        }
    }
}
?>

<div class="guru-page">
    <div class="bg-shape shape-1"></div>
    <div class="bg-shape shape-2"></div>

    <div class="guru-container">
        <div class="guru-page-header">
            <div class="guru-title-area">
                <h1>Kelola Guru</h1>
                <p class="guru-subtitle">Pusat kontrol data tenaga pendidik SMP Muhammadiyah 2</p>
            </div>
            <a class="guru-btn-add" href="<?= $base ?>/guru/create">
                Tambah Guru Baru
            </a>
        </div>

        <?php if ($success): ?>
            <div class="guru-flash guru-success">
                <span class="flash-icon">✓</span> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="guru-flash guru-error">
                <span class="flash-icon">!</span> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="guru-nav-tabs">
            <a href="<?= $base ?>/guru?tab=aktif" class="nav-tab-item <?= $tab === 'aktif' ? 'is-active' : '' ?>">
                Guru Aktif
            </a>
            <a href="<?= $base ?>/guru?tab=pending" class="nav-tab-item <?= $tab === 'pending' ? 'is-active' : '' ?>">
                Verifikasi Baru
                <?php if (count($rowsPending) > 0): ?>
                    <span class="tab-badge"><?= count($rowsPending) ?></span>
                <?php endif; ?>
            </a>
        </div>

        <div class="guru-main-card">
            <div class="guru-control-bar">
                <div class="table-info">
                    <h3><?= $tab === 'aktif' ? 'Daftar Guru Terdaftar' : 'Permintaan Akun Guru' ?></h3>
                </div>

                <form class="guru-search-box" method="get" action="<?= $base ?>/guru">
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                    <div class="search-input-wrapper">
                        <input name="q" placeholder="Cari nama guru..." value="<?= htmlspecialchars($q) ?>">
                        <button type="submit">Cari</button>
                    </div>
                </form>
            </div>

            <div class="guru-table-container">
                <table class="guru-modern-table">
                    <thead>
                        <tr>
                            <th class="txt-center">No</th>
                            <th>Info Guru</th>
                            <th>KTA</th>
                            <?php if($tab === 'aktif'): ?>
                                <th>Mata Pelajaran</th>
                                <th>Gender</th>
                                <th>Status</th>
                                <th class="txt-center">Aksi</th>
                            <?php else: ?>
                                <th>Tgl Daftar</th>
                                <th>Status</th>
                                <th class="txt-center">Validasi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $displayRows = ($tab === 'pending') ? $rowsPending : $rowsAktif;
                        if (empty($displayRows)): 
                        ?>
                            <tr>
                                <td colspan="10" class="guru-empty-state">
                                    <p><?= $tab === 'aktif' ? 'Data guru masih kosong.' : 'Belum ada permintaan akun baru.' ?></p>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php $no=1; foreach ($displayRows as $r): ?>
                            <?php
                            $jk = $r['jenis_kelamin'] ?? '';
                            $jkClass = ($jk === 'Perempuan') ? 'badge-pink' : 'badge-cyan';
                            $stReal = strtoupper(trim($r['status_aktif'] ?? 'PENDING'));
                            if ($stReal === '') $stReal = 'PENDING';
                            ?>
                            <tr>
                                <td class="txt-center txt-muted"><?= $no++ ?></td>
                                <td>
                                    <div class="guru-info-cell">
                                        <div class="avatar-wrapper">
                                            <?php if (!empty($r['foto'])): ?>
                                                <img src="<?= $base ?>/uploads/guru/<?= htmlspecialchars($r['foto']) ?>" alt="foto">
                                            <?php else: ?>
                                                <div class="avatar-initials">
                                                    <?= strtoupper(substr($r['nama_guru'] ?? 'G', 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="name-wrapper">
                                            <span class="full-name"><?= htmlspecialchars($r['nama_guru'] ?? '-') ?></span>
                                            <span class="phone-sub"><?= htmlspecialchars($r['no_hp'] ?? '-') ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="txt-mono"><?= htmlspecialchars($r['nip'] ?? '-') ?></td>

                                <?php if($tab === 'aktif'): ?>
                                    <td><span class="subject-tag"><?= htmlspecialchars($r['mata_pelajaran'] ?? '-') ?></span></td>
                                    <td><span class="pill-badge <?= $jkClass ?>"><?= htmlspecialchars($jk ?: '-') ?></span></td>
                                    <td><span class="status-indicator <?= strtolower($stReal) ?>"><?= $stReal ?></span></td>
                                <?php else: ?>
                                    <td class="txt-small"><?= date('d/m/Y H:i', strtotime($r['created_at'] ?? 'now')) ?></td>
                                    <td><span class="pill-badge badge-orange">MENUNGGU</span></td>
                                <?php endif; ?>

                                <td>
                                    <div class="guru-action-group">
                                        <?php if($tab === 'aktif'): ?>
                                            <a class="btn-action btn-reset" href="<?= $base ?>/guru/reset-password/<?= urlencode($r['id_guru']) ?>" 
                                               title="Reset Password" onclick="return confirm('Reset password guru <?= htmlspecialchars($r['nama_guru']) ?> menjadi 123456?')">
                                               PW
                                            </a>
                                            <a class="btn-action btn-edit" href="<?= $base ?>/guru/edit?id=<?= urlencode($r['id_guru']) ?>" title="Edit">
                                               Edit
                                            </a>
                                            <form method="post" action="<?= $base ?>/guru/delete" onsubmit="return confirm('Hapus data guru ini?')">
                                                <input type="hidden" name="id_guru" value="<?= htmlspecialchars($r['id_guru']) ?>">
                                                <button type="submit" class="btn-action btn-delete">Hapus</button>
                                            </form>
                                        <?php else: ?>
                                            <a class="btn-approve" href="<?= $base ?>/guru/setujui?id=<?= urlencode($r['id_guru']) ?>">Setujui</a>
                                            <a class="btn-reject" href="<?= $base ?>/guru/tolak?id=<?= urlencode($r['id_guru']) ?>" onclick="return confirm('Tolak?')">Tolak</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    /* Global & Colors */
    :root {
        --primary-gradient: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
        --soft-bg: #f0f4f8;
        --card-white: rgba(255, 255, 255, 0.95);
        --text-dark: #1e293b;
        --text-gray: #64748b;
    }

    .guru-page { 
        position: relative;
        padding: 40px 20px; 
        background-color: var(--soft-bg);
        min-height: 100vh;
        overflow: hidden;
    }

    /* Decorative Background Shapes */
    .bg-shape {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        z-index: 0;
    }
    .shape-1 { width: 400px; height: 400px; background: rgba(59, 130, 246, 0.15); top: -100px; right: -100px; }
    .shape-2 { width: 300px; height: 300px; background: rgba(29, 78, 216, 0.1); bottom: -50px; left: -50px; }

    .guru-container { position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; }

    /* Header Section */
    .guru-page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
    .guru-title-area h1 { margin: 0; font-size: 32px; font-weight: 800; color: var(--text-dark); letter-spacing: -1px; }
    .guru-subtitle { margin: 5px 0 0; color: var(--text-gray); font-size: 15px; }
    
    .guru-btn-add { 
        background: var(--primary-gradient); 
        color: #fff; 
        padding: 14px 28px; 
        border-radius: 16px; 
        font-weight: 700; 
        text-decoration: none; 
        box-shadow: 0 10px 20px rgba(29, 78, 216, 0.2);
        transition: 0.3s;
    }
    .guru-btn-add:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(29, 78, 216, 0.3); }

    /* Navigasi Tab */
    .guru-nav-tabs { display: flex; gap: 10px; margin-bottom: 25px; }
    .nav-tab-item { 
        padding: 12px 24px; 
        text-decoration: none; 
        color: var(--text-gray); 
        font-weight: 700; 
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.5);
        transition: 0.3s;
    }
    .nav-tab-item.is-active { background: #fff; color: #1d4ed8; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .tab-badge { background: #ef4444; color: #fff; font-size: 11px; padding: 2px 8px; border-radius: 20px; margin-left: 8px; }

    /* Main Card UI (Glass Effect) */
    .guru-main-card { 
        background: var(--card-white); 
        border-radius: 24px; 
        box-shadow: 0 20px 40px rgba(0,0,0,0.04); 
        border: 1px solid rgba(255,255,255,0.8);
        overflow: hidden; 
    }
    .guru-control-bar { padding: 30px; display: flex; justify-content: space-between; align-items: center; background: rgba(248, 250, 252, 0.5); }
    .guru-control-bar h3 { margin: 0; font-size: 18px; color: var(--text-dark); font-weight: 700; }

    /* Search Box */
    .search-input-wrapper { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 6px; display: flex; gap: 5px; width: 380px; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
    .search-input-wrapper input { border: none; padding: 8px 15px; outline: none; width: 100%; font-size: 14px; color: var(--text-dark); }
    .search-input-wrapper button { background: #1d4ed8; color: #fff; border: none; padding: 8px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; }

    /* Modern Table Style */
    .guru-modern-table { width: 100%; border-collapse: collapse; }
    .guru-modern-table th { padding: 20px 25px; text-align: left; font-size: 12px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #f1f5f9; }
    .guru-modern-table td { padding: 20px 25px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: var(--text-dark); }
    .guru-modern-table tbody tr:hover { background: rgba(241, 245, 249, 0.5); }

    /* Cells Info */
    .guru-info-cell { display: flex; align-items: center; gap: 16px; }
    .avatar-wrapper { width: 48px; height: 48px; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    .avatar-wrapper img { width: 100%; height: 100%; object-fit: cover; }
    .avatar-initials { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-weight: 800; background: #dbeafe; color: #1d4ed8; font-size: 18px; }
    .full-name { font-weight: 700; color: var(--text-dark); display: block; font-size: 15px; }
    .phone-sub { font-size: 12px; color: var(--text-gray); }
    .subject-tag { background: #f1f5f9; padding: 4px 10px; border-radius: 8px; font-size: 13px; font-weight: 600; }
    
    .txt-mono { font-family: 'JetBrains Mono', monospace; font-size: 13px; color: var(--text-gray); }
    .txt-center { text-align: center; }

    /* Badges */
    .pill-badge { padding: 6px 14px; border-radius: 10px; font-size: 11px; font-weight: 800; }
    .badge-cyan { background: #e0f2fe; color: #0369a1; }
    .badge-pink { background: #fce7f3; color: #be185d; }
    .badge-orange { background: #fff7ed; color: #c2410c; }
    
    .status-indicator { font-size: 12px; font-weight: 800; display: flex; align-items: center; gap: 8px; }
    .status-indicator::before { content: ''; width: 8px; height: 8px; border-radius: 50%; }
    .status-indicator.aktif::before { background: #10b981; box-shadow: 0 0 8px rgba(16, 185, 129, 0.5); }
    .status-indicator.pending::before { background: #f59e0b; }

    /* Action Buttons */
    .guru-action-group { display: flex; gap: 8px; justify-content: center; }
    .btn-action { border: none; padding: 9px 15px; border-radius: 10px; font-size: 12px; font-weight: 700; cursor: pointer; text-decoration: none; transition: 0.2s; }
    .btn-reset { background: #eff6ff; color: #2563eb; }
    .btn-edit { background: #fffbeb; color: #d97706; }
    .btn-delete { background: #fef2f2; color: #ef4444; }
    .btn-action:hover { filter: brightness(0.95); transform: scale(1.05); }

    .btn-approve { background: #10b981; color: #fff; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 13px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); }
    .btn-reject { color: #ef4444; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 13px; }

    .btn-back { color: var(--text-gray); text-decoration: none; font-weight: 700; font-size: 14px; transition: 0.3s; }
    .btn-back:hover { color: #1d4ed8; padding-left: 5px; }

    @media (max-width: 768px) {
        .guru-page-header { flex-direction: column; align-items: flex-start; gap: 20px; }
        .guru-search-box { width: 100%; }
        .search-input-wrapper { width: 100%; }
    }
</style>