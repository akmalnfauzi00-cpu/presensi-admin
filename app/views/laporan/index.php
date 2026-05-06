<?php function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } ?>
<?php
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base === '/') $base = '';
?>

<div class="report-page">
    <div class="bg-glow"></div>

    <div class="report-container">
        <header class="report-header">
            <div class="header-content">
                <nav class="breadcrumb">Laporan / Kehadiran / <span>Rekapitulasi</span></nav>
                <h1>Rekap Kehadiran Guru</h1>
                <p class="description">Pantau performa kehadiran guru secara kolektif dalam satu periode.</p>
            </div>
            <div class="period-status">
                <div class="status-indicator"></div>
                <span>Periode Aktif: <strong><?= (int)$jumlahHari ?> Hari</strong></span>
            </div>
        </header>

        <div class="report-grid">
            <aside class="report-sidebar">
                <div class="glass-card sticky-filter">
                    <div class="card-title-group">
                        <h3>Filter Laporan</h3>
                    </div>
                    
                    <form method="GET" action="<?= $base ?>/laporan" class="modern-form">
                        <div class="date-row">
                            <div class="form-group">
                                <label>Mulai</label>
                                <input type="date" name="mulai" value="<?= h($mulai) ?>" class="in-modern">
                            </div>
                            <div class="form-group">
                                <label>Selesai</label>
                                <input type="date" name="selesai" value="<?= h($selesai) ?>" class="in-modern">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Nama Guru</label>
                            <select name="id_guru" class="in-modern">
                                <option value="">Semua Guru</option>
                                <?php foreach ($guru as $g): ?>
                                    <option value="<?= h($g['id_guru']) ?>" <?= ($idGuru === (string)$g['id_guru']) ? 'selected' : '' ?>>
                                        <?= h($g['nama_guru']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn-primary">
                            <span>Terapkan Filter</span>
                        </button>
                    </form>

                    <div class="divider"></div>

                    <a class="btn-export" 
                       href="<?= $base ?>/laporan/export-pdf?mulai=<?= h($mulai) ?>&selesai=<?= h($selesai) ?>&id_guru=<?= h($idGuru) ?>">
                        <div class="export-text">
                            <strong>Export Laporan PDF</strong>
                            <span>Unduh ringkasan periode ini</span>
                        </div>
                    </a>
                </div>
            </aside>

            <main class="report-main">
                <div class="glass-card">
                    <div class="table-header">
                        <div class="table-info">
                            <h3>Daftar Kehadiran</h3>
                            <p><?= h(date('d M Y', strtotime($mulai))) ?> — <?= h(date('d M Y', strtotime($selesai))) ?></p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Nama Guru</th>
                                    <th class="txt-center">Hadir</th>
                                    <th class="txt-center">Lambat</th>
                                    <th class="txt-center">Izin</th>
                                    <th class="txt-center">Sakit</th>
                                    <th class="txt-center highlight-head">Alpha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr>
                                        <td colspan="6" class="table-empty">Belum ada data pada periode ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $r): ?>
                                        <tr>
                                            <td>
                                                <div class="teacher-info">
                                                    <span class="name"><?= h($r['nama_guru']) ?></span>
                                                    <span class="nip"><?= h($r['nip']) ?></span>
                                                </div>
                                            </td>
                                            <td class="txt-center stat-val"><?= (int)($r['hadir'] ?? 0) ?></td>
                                            <td class="txt-center stat-val warning"><?= (int)($r['lambat'] ?? 0) ?></td>
                                            <td class="txt-center stat-val info"><?= (int)($r['izin'] ?? 0) ?></td>
                                            <td class="txt-center stat-val danger"><?= (int)($r['sakit'] ?? 0) ?></td>
                                            <td class="txt-center stat-val alpha-bold"><?= (int)($r['tidak_hadir'] ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="table-footer">
                        Menampilkan <strong><?= count($rows) ?></strong> Tenaga Pendidik
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<style>
    :root {
        --primary: #2563eb;
        --text-dark: #0f172a;
        --text-muted: #64748b;
        --bg-body: #f8fafc;
    }

    .report-page { position: relative; padding: 40px 20px; background: var(--bg-body); min-height: 100vh; font-family: 'Plus Jakarta Sans', sans-serif; }
    .report-container { position: relative; z-index: 1; max-width: 1280px; margin: 0 auto; }

    /* Header */
    .report-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; }
    .breadcrumb { font-size: 12px; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
    .breadcrumb span { color: var(--primary); font-weight: 700; }
    .report-header h1 { font-size: 32px; font-weight: 800; color: var(--text-dark); margin: 0; }
    .period-status { background: #fff; padding: 12px 20px; border-radius: 100px; display: flex; align-items: center; gap: 12px; border: 1px solid #f1f5f9; font-size: 14px; }
    .status-indicator { width: 10px; height: 10px; background: #22c55e; border-radius: 50%; }

    /* Grid */
    .report-grid { display: grid; grid-template-columns: 340px 1fr; gap: 30px; align-items: start; }

    /* Glass Card */
    .glass-card { background: #fff; border-radius: 24px; border: 1px solid #f1f5f9; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); }
    .card-title-group { padding: 25px 30px; border-bottom: 1px solid #f1f5f9; }
    .card-title-group h3 { margin: 0; font-size: 18px; font-weight: 700; color: var(--text-dark); }

    /* Perbaikan Input Tanggal */
    .modern-form { padding: 25px 30px; }
    .date-row { display: flex; gap: 10px; margin-bottom: 15px; } /* Menggunakan flex agar rapi */
    .date-row .form-group { flex: 1; }
    
    .form-group label { display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; }
    .in-modern { 
        width: 100%; 
        padding: 10px 12px; 
        border: 1.5px solid #e2e8f0; 
        border-radius: 12px; 
        font-family: inherit; 
        font-size: 13px; 
        color: var(--text-dark); 
        box-sizing: border-box; 
    }

    .btn-primary { width: 100%; padding: 14px; background: var(--primary); color: #fff; border: none; border-radius: 14px; font-weight: 700; cursor: pointer; margin-top: 10px; }
    .divider { height: 1px; background: #f1f5f9; margin: 0 30px; }
    .btn-export { display: block; padding: 25px 30px; text-decoration: none; }
    .export-text strong { display: block; color: var(--text-dark); font-size: 14px; }
    .export-text span { font-size: 12px; color: var(--text-muted); }

    /* Table */
    .table-header { padding: 30px; border-bottom: 1px solid #f1f5f9; }
    .table-header h3 { margin: 0; font-size: 20px; font-weight: 700; color: var(--text-dark); }
    .modern-table { width: 100%; border-collapse: collapse; }
    .modern-table th { text-align: left; padding: 16px 25px; font-size: 12px; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid #f1f5f9; background: #fafafa; }
    .modern-table td { padding: 18px 25px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    
    .teacher-info .name { display: block; font-weight: 700; color: var(--text-dark); font-size: 15px; }
    .teacher-info .nip { font-size: 12px; color: var(--text-muted); }

    .txt-center { text-align: center !important; }
    .stat-val { font-weight: 600; color: var(--text-dark); }
    .stat-val.warning { color: #f59e0b; }
    .stat-val.info { color: #3b82f6; }
    .stat-val.danger { color: #ef4444; }
    .alpha-bold { font-weight: 800; color: #7c3aed; }
    .highlight-head { background: #f5f3ff !important; color: #7c3aed !important; }
    .table-footer { padding: 20px 30px; font-size: 13px; color: var(--text-muted); }

    @media (max-width: 1024px) {
        .report-grid { grid-template-columns: 1fr; }
        .date-row { flex-direction: column; }
    }
</style>