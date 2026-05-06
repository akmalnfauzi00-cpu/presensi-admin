<?php function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } ?>
<?php
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base === '/') $base = '';
?>

<div class="rsp-page">
    <div class="bg-shape shape-1"></div>
    <div class="bg-shape shape-2"></div>

    <div class="rsp-container">
        <div class="rsp-header">
            <div class="rsp-title-area">
                <h1>Reward & Surat Peringatan</h1>
                <p class="rsp-subtitle">Kelola apresiasi dan teguran dokumen PDF untuk tenaga pendidik</p>
            </div>
        </div>

        <div class="rsp-grid">
            <div class="rsp-side">
                <div class="rsp-card rsp-card-sticky">
                    <div class="rsp-card-head">
                        <h3>Kirim Dokumen Baru</h3>
                    </div>
                    <div class="rsp-card-body">
                        <form method="POST" action="<?= $base ?>/rewardsp/create" enctype="multipart/form-data">
                            <div class="form-group">
                                <label>Pilih Nama Guru</label>
                                <select name="guru_pick" required class="rsp-input">
                                    <option value="" disabled selected>— Pilih Guru —</option>
                                    <?php foreach ($guru as $g): ?>
                                        <option value="<?= h($g['id_guru']) ?>">
                                            <?= h($g['nama_guru']) ?> (<?= h($g['nip']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Periode (Bulan & Tahun)</label>
                                <div class="input-row">
                                    <select name="bulan" class="rsp-input" style="flex:2;">
                                        <?php 
                                        $m_now = date('m');
                                        for($i=1; $i<=12; $i++): 
                                            $val = str_pad($i, 2, '0', STR_PAD_LEFT);
                                        ?>
                                            <option value="<?= $val ?>" <?= $val == $m_now ? 'selected' : '' ?>>
                                                <?= date('F', mktime(0,0,0,$i, 1)) ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                    <input type="number" name="tahun" class="rsp-input" style="flex:1;" value="<?= date('Y') ?>" min="2024" max="2030">
                                </div>
                                <input type="hidden" name="periode" value="<?= date('Y-m') ?>">
                            </div>

                            <div class="form-group">
                                <label>Kategori Dokumen</label>
                                <div class="rsp-radio-group">
                                    <label class="rsp-radio-item">
                                        <input type="radio" name="jenis" value="REWARD" checked>
                                        <span class="radio-label">Apresiasi (Reward)</span>
                                    </label>
                                    <label class="rsp-radio-item">
                                        <input type="radio" name="jenis" value="SP">
                                        <span class="radio-label">Teguran (SP)</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Deskripsi Singkat</label>
                                <textarea class="rsp-input" name="deskripsi" rows="3" placeholder="Contoh: Reward kehadiran bulan April..."></textarea>
                            </div>

                            <button class="rsp-btn-primary" type="submit">Kirim Dokumen PDF</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="rsp-main">
                <div class="rsp-card">
                    <div class="rsp-card-head rsp-between">
                        <div>
                            <h3>Riwayat Terkirim</h3>
                            <p class="txt-muted">Menampilkan 50 distribusi dokumen terbaru</p>
                        </div>
                        
                        <form method="POST" action="<?= $base ?>/rewardsp/reset" onsubmit="return confirm('⚠️ HAPUS SEMUA RIWAYAT?\n\nFile PDF akan terhapus permanen dari server.');">
                            <button type="submit" class="rsp-btn-reset">
                                Reset Semua
                            </button>
                        </form>
                    </div>

                    <div class="rsp-card-body">
                        <?php if (empty($docs)): ?>
                            <div class="rsp-empty">
                                <p>Belum ada riwayat pengiriman dokumen.</p>
                            </div>
                        <?php else: ?>
                            <div class="rsp-list">
                                <?php foreach ($docs as $d): ?>
                                    <div class="rsp-item">
                                        <div class="rsp-item-info">
                                            <div class="teacher-name"><?= h($d['nama_guru']) ?> <span class="nip-tag"><?= h($d['nip']) ?></span></div>
                                            <div class="doc-meta">
                                                <span class="type-badge <?= strtolower($d['jenis']) ?>"><?= h($d['jenis']) ?></span>
                                                <span class="meta-dot"></span>
                                                <span class="meta-txt">Periode: <?= h($d['periode']) ?></span>
                                                <?php if (!empty($d['dibuat_pada'])): ?>
                                                    <span class="meta-dot"></span>
                                                    <span class="meta-txt"><?= h($d['dibuat_pada']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($d['deskripsi'])): ?>
                                                <div class="doc-desc"><?= h($d['deskripsi']) ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="rsp-item-action">
                                            <?php if (($d['status_unduh'] ?? '') === 'SUDAH_DIUNDUH'): ?>
                                                <span class="status-pill status-ok">Sudah Diunduh</span>
                                            <?php else: ?>
                                                <span class="status-pill status-wait">Belum Dilihat</span>
                                            <?php endif; ?>

                                            <a href="<?= $base ?>/rewardsp/download?id=<?= h($d['id_dokumen']) ?>" class="rsp-btn-dl">
                                                Unduh PDF
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Script penggabung periode otomatis
document.querySelector('form').onsubmit = function() {
    const b = this.querySelector('[name="bulan"]').value;
    const t = this.querySelector('[name="tahun"]').value;
    this.querySelector('[name="periode"]').value = t + '-' + b;
};
</script>

<style>
    /* Global Styling */
    :root {
        --primary-gradient: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
        --soft-bg: #f8fafc;
        --card-bg: rgba(255, 255, 255, 0.95);
        --text-main: #1e293b;
        --text-muted: #64748b;
    }

    .rsp-page { position: relative; padding: 40px 20px; background: var(--soft-bg); min-height: 100vh; overflow: hidden; font-family: 'Inter', sans-serif; }
    
    /* Background shapes */
    .bg-shape { position: absolute; border-radius: 50%; filter: blur(80px); z-index: 0; opacity: 0.6; }
    .shape-1 { width: 400px; height: 400px; background: rgba(59, 130, 246, 0.1); top: -100px; right: -100px; }
    .shape-2 { width: 300px; height: 300px; background: rgba(139, 92, 246, 0.1); bottom: -50px; left: -50px; }

    .rsp-container { position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; }

    /* Header */
    .rsp-header { margin-bottom: 30px; }
    .rsp-title-area h1 { margin: 0; font-size: 32px; font-weight: 800; color: var(--text-main); letter-spacing: -1px; }
    .rsp-subtitle { margin: 5px 0 0; color: var(--text-muted); font-size: 15px; }

    /* Layout Grid */
    .rsp-grid { display: grid; grid-template-columns: 380px 1fr; gap: 24px; align-items: start; }

    /* Card UI */
    .rsp-card { background: var(--card-bg); border-radius: 24px; border: 1px solid rgba(255,255,255,0.8); box-shadow: 0 10px 30px rgba(0,0,0,0.03); overflow: hidden; }
    .rsp-card-sticky { position: sticky; top: 24px; }
    .rsp-card-head { padding: 24px 30px; border-bottom: 1px solid #f1f5f9; background: rgba(248, 250, 252, 0.5); }
    .rsp-card-head h3 { margin: 0; font-size: 18px; font-weight: 700; color: var(--text-main); }
    .rsp-between { display: flex; justify-content: space-between; align-items: center; }

    .rsp-card-body { padding: 30px; }

    /* Form Styles */
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
    .rsp-input { width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; outline: none; transition: 0.2s; background: #fcfcfc; font-family: inherit; box-sizing: border-box; }
    .rsp-input:focus { border-color: #1d4ed8; background: #fff; box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.08); }
    .input-row { display: flex; gap: 10px; }

    /* Radio Group */
    .rsp-radio-group { display: flex; gap: 12px; }
    .rsp-radio-item { flex: 1; position: relative; display: flex; align-items: center; justify-content: center; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: 0.2s; }
    .rsp-radio-item input { position: absolute; opacity: 0; }
    .rsp-radio-item:has(input:checked) { border-color: #1d4ed8; background: #eff6ff; color: #1d4ed8; }
    .radio-label { font-size: 13px; font-weight: 700; }

    /* Buttons */
    .rsp-btn-primary { width: 100%; padding: 14px; background: var(--primary-gradient); color: #fff; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; }
    .rsp-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(29, 78, 216, 0.25); }
    .rsp-btn-reset { padding: 8px 16px; background: #fff; border: 1.5px solid #ffe4e6; color: #ef4444; border-radius: 10px; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; }
    .rsp-btn-reset:hover { background: #ef4444; color: #fff; }

    /* List Items */
    .rsp-list { display: flex; flex-direction: column; gap: 12px; }
    .rsp-item { display: flex; justify-content: space-between; align-items: center; padding: 20px; background: #fff; border: 1.5px solid #f1f5f9; border-radius: 18px; transition: 0.2s; }
    .rsp-item:hover { border-color: #dbeafe; background: #fbfcfe; }

    .teacher-name { font-weight: 700; font-size: 15px; color: var(--text-main); }
    .nip-tag { font-weight: 400; color: var(--text-muted); font-size: 13px; margin-left: 5px; }
    
    .doc-meta { display: flex; align-items: center; gap: 8px; margin-top: 6px; }
    .meta-txt { font-size: 12px; color: var(--text-muted); font-weight: 500; }
    .meta-dot { width: 4px; height: 4px; background: #cbd5e1; border-radius: 50%; }
    .doc-desc { margin-top: 8px; font-size: 13px; color: #1d4ed8; background: #eff6ff; padding: 6px 12px; border-radius: 8px; display: inline-block; }

    /* Badges */
    .type-badge { font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 6px; text-transform: uppercase; }
    .type-badge.reward { background: #dcfce7; color: #15803d; }
    .type-badge.sp { background: #fee2e2; color: #be123c; }

    .status-pill { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; margin-bottom: 8px; display: inline-block; }
    .status-ok { background: #e6f9f1; color: #10b981; }
    .status-wait { background: #fff7ed; color: #f59e0b; }

    .rsp-item-action { display: flex; flex-direction: column; align-items: flex-end; }
    .rsp-btn-dl { text-decoration: none; font-size: 13px; font-weight: 700; color: #1d4ed8; }
    .rsp-btn-dl:hover { text-decoration: underline; }

    .txt-muted { font-size: 13px; color: var(--text-muted); margin-top: 4px; }
    .rsp-empty { text-align: center; padding: 40px; color: var(--text-muted); font-style: italic; }

    @media (max-width: 1100px) {
        .rsp-grid { grid-template-columns: 1fr; }
        .rsp-card-sticky { position: static; }
    }
</style>