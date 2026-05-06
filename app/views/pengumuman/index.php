<?php 
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base === '/') $base = '';
?>

<div class="announcement-page">
    <div class="bg-shape shape-1"></div>
    <div class="bg-shape shape-2"></div>

    <div class="announcement-container">
        <div class="announcement-header">
            <div class="title-area">
                <h1>Kelola Pengumuman</h1>
                <p class="subtitle">Publikasikan informasi penting untuk seluruh guru dan staf</p>
            </div>
        </div>

        <?php if ($success = Session::flash('success')): ?>
            <div class="flash-msg success">
                <span class="flash-icon">✨</span>
                <div class="flash-text"><?= $success ?></div>
            </div>
        <?php endif; ?>

        <?php if ($error = Session::flash('error')): ?>
            <div class="flash-msg error">
                <span class="flash-icon">⚠️</span>
                <div class="flash-text"><?= $error ?></div>
            </div>
        <?php endif; ?>

        <div class="announcement-grid">
            <div class="side-form">
                <div class="glass-card sticky-card">
                    <div class="card-head">
                        <h3>Buat Baru</h3>
                    </div>
                    <div class="card-body">
                        <form action="<?= $base ?>/pengumuman/store" method="POST" class="modern-form">
                            <div class="form-group">
                                <label>Judul Pengumuman</label>
                                <input type="text" name="title" class="in-modern" required placeholder="Contoh: Jadwal Rapat Kurikulum">
                            </div>
                            <div class="form-group">
                                <label>Isi Informasi</label>
                                <textarea name="content" class="in-modern area-modern" required placeholder="Tulis rincian pengumuman di sini..."></textarea>
                            </div>
                            <button type="submit" class="btn-publish">
                                Terbitkan Sekarang
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="main-list">
                <div class="glass-card">
                    <div class="card-head split">
                        <h3>Riwayat Informasi</h3>
                        <span class="count-badge"><?= count($data) ?> Total</span>
                    </div>
                    <div class="card-body no-padding">
                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Detail Pengumuman</th>
                                        <th class="txt-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($data)): ?>
                                        <tr>
                                            <td colspan="2" class="empty-state">
                                                <div class="empty-icon">📭</div>
                                                <p>Belum ada pengumuman yang diterbitkan.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($data as $row): ?>
                                            <tr>
                                                <td class="info-cell">
                                                    <div class="info-wrapper">
                                                        <div class="info-title"><?= htmlspecialchars($row['title']) ?></div>
                                                        <div class="info-content">
                                                            <?= nl2br(htmlspecialchars($row['content'])) ?>
                                                        </div>
                                                        <div class="info-meta">
                                                            <span class="meta-item">
                                                                📅 <?= date('d M Y', strtotime($row['created_at'])) ?>
                                                            </span>
                                                            <span class="meta-dot"></span>
                                                            <span class="meta-item">
                                                                ⏰ <?= date('H:i', strtotime($row['created_at'])) ?> WIB
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="action-cell">
                                                    <form action="<?= $base ?>/pengumuman/delete" method="POST" onsubmit="return confirm('Hapus pengumuman ini?')">
                                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                        <button type="submit" class="btn-delete" title="Hapus">
                                                            Hapus
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Global & Estetika Dasar */
    :root {
        --primary: #1d4ed8;
        --soft-bg: #f8fafc;
        --text-main: #1e293b;
        --text-muted: #64748b;
        --card-bg: rgba(255, 255, 255, 0.95);
    }

    .announcement-page { position: relative; padding: 40px 20px; background: var(--soft-bg); min-height: 100vh; overflow: hidden; font-family: 'Plus Jakarta Sans', sans-serif; }
    
    /* Background Glow Shapes */
    .bg-shape { position: absolute; border-radius: 50%; filter: blur(80px); z-index: 0; opacity: 0.6; }
    .shape-1 { width: 400px; height: 400px; background: rgba(59, 130, 246, 0.1); top: -100px; right: -100px; }
    .shape-2 { width: 300px; height: 300px; background: rgba(139, 92, 246, 0.1); bottom: -50px; left: -50px; }

    .announcement-container { position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; }

    /* Header */
    .announcement-header { margin-bottom: 30px; }
    .title-area h1 { margin: 0; font-size: 32px; font-weight: 800; color: var(--text-main); letter-spacing: -1px; }
    .subtitle { margin: 5px 0 0; color: var(--text-muted); font-size: 15px; }

    /* Grid Layout */
    .announcement-grid { display: grid; grid-template-columns: 350px 1fr; gap: 24px; align-items: start; }

    /* Glass Card UI */
    .glass-card { background: var(--card-bg); border-radius: 24px; border: 1px solid rgba(255,255,255,0.8); box-shadow: 0 10px 30px rgba(0,0,0,0.03); overflow: hidden; backdrop-filter: blur(10px); }
    .sticky-card { position: sticky; top: 24px; }
    
    .card-head { padding: 24px 30px; border-bottom: 1px solid #f1f5f9; background: rgba(248, 250, 252, 0.5); }
    .card-head h3 { margin: 0; font-size: 18px; font-weight: 700; color: var(--text-main); }
    .card-head.split { display: flex; justify-content: space-between; align-items: center; }
    .count-badge { background: #eff6ff; color: #1d4ed8; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }

    .card-body { padding: 30px; }
    .card-body.no-padding { padding: 0; }

    /* Modern Form */
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
    .in-modern { width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; outline: none; transition: 0.2s; background: #fcfcfc; font-family: inherit; box-sizing: border-box; }
    .in-modern:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.08); }
    .area-modern { height: 150px; resize: none; }

    .btn-publish { width: 100%; padding: 14px; background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%); color: #fff; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; }
    .btn-publish:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(29, 78, 216, 0.25); }

    /* Flash Messages */
    .flash-msg { padding: 16px 24px; border-radius: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; font-weight: 600; border: 1px solid transparent; animation: slideDown 0.4s ease; }
    .flash-msg.success { background: #ecfdf5; color: #065f46; border-color: #bbf7d0; }
    .flash-msg.error { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

    /* Table Styles */
    .modern-table { width: 100%; border-collapse: collapse; }
    .modern-table th { text-align: left; padding: 16px 30px; font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #f1f5f9; background: #fafafa; }
    .modern-table td { padding: 25px 30px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
    .modern-table tr:last-child td { border-bottom: none; }

    .info-title { font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 6px; }
    .info-content { font-size: 14px; color: var(--text-muted); line-height: 1.6; word-break: break-word; }
    
    .info-meta { display: flex; align-items: center; gap: 10px; margin-top: 12px; }
    .meta-item { font-size: 12px; font-weight: 600; color: #94a3b8; }
    .meta-dot { width: 4px; height: 4px; background: #cbd5e1; border-radius: 50%; }

    .action-cell { width: 100px; text-align: center; }
    .btn-delete { background: #fff; border: 1.5px solid #ffe4e6; color: #ef4444; padding: 8px 16px; border-radius: 10px; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; }
    .btn-delete:hover { background: #ef4444; color: #fff; border-color: #ef4444; }

    .empty-state { text-align: center; padding: 60px !important; color: #94a3b8; }
    .empty-icon { font-size: 40px; margin-bottom: 10px; }

    /* Responsive */
    @media (max-width: 1000px) {
        .announcement-grid { grid-template-columns: 1fr; }
        .sticky-card { position: static; }
    }
</style>