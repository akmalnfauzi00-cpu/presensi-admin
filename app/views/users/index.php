<?php
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base === '/') $base = '';

$success = Session::pullFlash('success');
$error   = Session::pullFlash('error');

$q = $q ?? ($_GET['q'] ?? '');
?>

<div class="guru-page">
    <div class="bg-shape shape-1"></div>
    <div class="bg-shape shape-2"></div>

    <div class="guru-container">
        <div class="guru-page-header">
            <div class="guru-title-area">
                <h1>Kelola User Admin</h1>
                <p class="guru-subtitle">Manajemen hak akses dan kredensial administrator sistem</p>
            </div>
            <a class="guru-btn-add" href="<?= $base ?>/users/create">
                + Tambah Admin
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

        <div class="guru-main-card">
            <div class="guru-control-bar">
                <div class="table-info">
                    <h3>Daftar Administrator</h3>
                </div>

                <form class="guru-search-box" method="get" action="<?= $base ?>/users">
                    <div class="search-input-wrapper">
                        <input name="q" placeholder="Cari admin atau role..." value="<?= htmlspecialchars($q) ?>">
                        <button type="submit">Cari</button>
                    </div>
                </form>
            </div>

            <div class="guru-table-container">
                <table class="guru-modern-table">
                    <thead>
                        <tr>
                            <th style="width:60px;" class="txt-center">No</th>
                            <th>Identitas Admin</th>
                            <th>Username</th>
                            <th>Akses Level</th>
                            <th>Status</th>
                            <th>Aktivitas</th>
                            <th class="txt-center">Navigasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="7" class="guru-empty-state">
                                    <p>Belum ada data user admin yang terdaftar.</p>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php $no = 1; foreach ($rows as $r): ?>
                            <?php
                                $isActive = (int)($r['status_aktif'] ?? 1) === 1;
                                $statusClass = $isActive ? 'aktif' : 'nonaktif';
                                
                                $role = strtolower((string)($r['role'] ?? 'admin'));
                                $roleClass = $role === 'superadmin' ? 'badge-purple' : 'badge-blue';
                            ?>
                            <tr>
                                <td class="txt-center txt-muted"><?= $no++ ?></td>
                                <td>
                                    <div class="guru-info-cell">
                                        <div class="avatar-wrapper">
                                            <div class="avatar-initials">
                                                <?= strtoupper(substr($r['nama'] ?? 'A', 0, 1)) ?>
                                            </div>
                                        </div>
                                        <div class="name-wrapper">
                                            <span class="full-name"><?= htmlspecialchars($r['nama'] ?? '-') ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="txt-mono"><?= htmlspecialchars($r['username'] ?? '-') ?></td>
                                <td>
                                    <span class="pill-badge <?= $roleClass ?>">
                                        <?= htmlspecialchars(ucfirst($role)) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-indicator <?= $statusClass ?>">
                                        <?= $isActive ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="txt-small">
                                        <span class="txt-muted">Dibuat:</span> <?= date('d/m/y', strtotime($r['created_at'] ?? 'now')) ?><br>
                                        <span class="txt-muted">Update:</span> <?= date('d/m/y', strtotime($r['updated_at'] ?? 'now')) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="guru-action-group">
                                        <a class="btn-action btn-edit" href="<?= $base ?>/users/edit?id=<?= urlencode($r['id_user']) ?>" title="Edit Admin">
                                            Edit
                                        </a>
                                        <form method="post" action="<?= $base ?>/users/delete" onsubmit="return confirm('Hapus user admin ini?')">
                                            <input type="hidden" name="id_user" value="<?= htmlspecialchars($r['id_user']) ?>">
                                            <button type="submit" class="btn-action btn-delete">Hapus</button>
                                        </form>
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
        --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #1d4ed8 100%);
        --soft-bg: #f8fafc;
        --card-white: rgba(255, 255, 255, 0.95);
        --text-dark: #0f172a;
        --text-gray: #64748b;
    }

    .guru-page { 
        position: relative;
        padding: 40px 20px; 
        background-color: var(--soft-bg);
        min-height: 100vh;
        overflow: hidden;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    /* Decorative Background Shapes */
    .bg-shape { position: absolute; border-radius: 50%; filter: blur(80px); z-index: 0; }
    .shape-1 { width: 400px; height: 400px; background: rgba(79, 70, 229, 0.15); top: -100px; right: -100px; }
    .shape-2 { width: 300px; height: 300px; background: rgba(30, 58, 138, 0.1); bottom: -50px; left: -50px; }

    .guru-container { position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; }

    /* Header Section */
    .guru-page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
    .guru-title-area h1 { margin: 0; font-size: 32px; font-weight: 800; color: var(--text-dark); letter-spacing: -1px; }
    .guru-subtitle { margin: 5px 0 0; color: var(--text-gray); font-size: 15px; }
    
    .guru-btn-add { 
        background: var(--primary-gradient); 
        color: #fff; padding: 14px 28px; border-radius: 16px; font-weight: 700; 
        text-decoration: none; box-shadow: 0 10px 20px rgba(79, 70, 229, 0.2); transition: 0.3s;
    }
    .guru-btn-add:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(79, 70, 229, 0.3); }

    /* Flash Messages */
    .guru-flash { padding: 15px 25px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; font-weight: 600; }
    .guru-success { background: #ecfdf5; color: #10b981; border-left: 5px solid #10b981; }
    .guru-error { background: #fef2f2; color: #ef4444; border-left: 5px solid #ef4444; }

    /* Main Card (Glass Effect) */
    .guru-main-card { 
        background: var(--card-white); border-radius: 24px; 
        box-shadow: 0 20px 40px rgba(0,0,0,0.04); border: 1px solid rgba(255,255,255,0.8); overflow: hidden; 
    }
    .guru-control-bar { padding: 30px; display: flex; justify-content: space-between; align-items: center; background: rgba(248, 250, 252, 0.5); }
    .guru-control-bar h3 { margin: 0; font-size: 18px; color: var(--text-dark); font-weight: 700; }

    /* Search Box */
    .search-input-wrapper { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 6px; display: flex; gap: 5px; width: 380px; }
    .search-input-wrapper input { border: none; padding: 8px 15px; outline: none; width: 100%; font-size: 14px; color: var(--text-dark); background: transparent; }
    .search-input-wrapper button { background: #4f46e5; color: #fff; border: none; padding: 8px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; }

    /* Modern Table Style */
    .guru-modern-table { width: 100%; border-collapse: collapse; }
    .guru-modern-table th { padding: 20px 25px; text-align: left; font-size: 12px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #f1f5f9; }
    .guru-modern-table td { padding: 20px 25px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: var(--text-dark); }
    .guru-modern-table tbody tr:hover { background: rgba(241, 245, 249, 0.5); }

    /* Cells Info */
    .guru-info-cell { display: flex; align-items: center; gap: 16px; }
    .avatar-wrapper { width: 44px; height: 44px; border-radius: 12px; overflow: hidden; background: #eef2ff; }
    .avatar-initials { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #4f46e5; font-size: 18px; }
    .full-name { font-weight: 700; color: var(--text-dark); display: block; font-size: 15px; }
    
    .txt-mono { font-family: 'JetBrains Mono', monospace; font-size: 13px; color: #6366f1; font-weight: 600; }
    .txt-small { font-size: 12px; line-height: 1.5; }
    .txt-muted { color: var(--text-gray); }
    .txt-center { text-align: center; }

    /* Badges & Status */
    .pill-badge { padding: 6px 14px; border-radius: 10px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
    .badge-blue { background: #e0f2fe; color: #0369a1; }
    .badge-purple { background: #f5f3ff; color: #6d28d9; }
    
    .status-indicator { font-size: 12px; font-weight: 800; display: flex; align-items: center; gap: 8px; }
    .status-indicator::before { content: ''; width: 8px; height: 8px; border-radius: 50%; }
    .status-indicator.aktif::before { background: #10b981; box-shadow: 0 0 8px rgba(16, 185, 129, 0.5); }
    .status-indicator.nonaktif::before { background: #94a3b8; }

    /* Action Group */
    .guru-action-group { display: flex; gap: 8px; justify-content: center; }
    .btn-action { border: none; padding: 9px 15px; border-radius: 10px; font-size: 12px; font-weight: 700; cursor: pointer; text-decoration: none; transition: 0.2s; }
    .btn-edit { background: #fefce8; color: #a16207; }
    .btn-delete { background: #fef2f2; color: #ef4444; }
    .btn-action:hover { filter: brightness(0.95); transform: scale(1.05); }

    .btn-back { color: var(--text-gray); text-decoration: none; font-weight: 700; font-size: 14px; transition: 0.3s; display: inline-block; margin-top: 25px;}
    .btn-back:hover { color: #4f46e5; transform: translateX(-5px); }

    @media (max-width: 768px) {
        .guru-page-header { flex-direction: column; align-items: flex-start; gap: 20px; }
        .search-input-wrapper { width: 100%; }
    }
</style>