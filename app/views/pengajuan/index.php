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
      <div style="font-weight:700;">Pengajuan Izin / Sakit</div>
      <div class="p">Home / Pengajuan</div>
    </div>

    <div style="display:flex;align-items:center;gap:10px;">
      <?php if (!empty($jumlahMenunggu)): ?>
        <span style="background:#fef2f2;color:#b91c1c;padding:6px 12px;border-radius:999px;font-size:12px;font-weight:700;border:1px solid #fecaca;">
          <?= (int)$jumlahMenunggu ?> menunggu
        </span>
      <?php endif; ?>

      <a href="<?= $base ?>/laporan" class="btn primary" style="text-decoration:none;">
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
  <div class="card-body">
    <form method="GET" action="<?= $base ?>/pengajuan" style="display:grid;grid-template-columns:1fr 1fr 1.2fr auto;gap:10px;align-items:end;">
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

      <button class="btn primary" type="submit">Terapkan</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-head">Daftar Pengajuan</div>
  <div class="card-body" style="padding:0;">
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr style="background:#f6f8fc;">
          <th class="th" style="text-align:left;">Guru</th>
          <th class="th">Jenis</th>
          <th class="th">Tanggal</th>
          <th class="th" style="text-align:left;">Alasan</th>
          <th class="th">Lampiran</th>
          <th class="th">Status</th>
          <th class="th">Dikirim</th>
          <th class="th" style="text-align:left;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="8" style="padding:16px;color:#6b7280;">Belum ada pengajuan.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <?php
              $status = strtoupper((string)($r['status_verifikasi'] ?? 'MENUNGGU'));
              $lampiranUrl = '';
              if (!empty($r['lampiran_path'])) {
                $lampiranUrl = $base . $r['lampiran_path'];
              }
            ?>
            <tr>
              <td class="td" style="text-align:left;">
                <div style="font-weight:700;"><?= h($r['nama_guru']) ?></div>
                <div class="p"><?= h($r['nip']) ?></div>
              </td>

              <td class="td">
                <?php if (($r['jenis'] ?? '') === 'SAKIT'): ?>
                  <span class="badge danger">SAKIT</span>
                <?php else: ?>
                  <span class="badge warn">IZIN</span>
                <?php endif; ?>
              </td>

              <td class="td"><?= h($r['tanggal']) ?></td>

              <td class="td" style="text-align:left;max-width:260px;">
                <div><?= nl2br(h($r['alasan'] ?? '')) ?></div>

                <?php if (!empty($r['catatan_admin'])): ?>
                  <div style="margin-top:8px;padding:8px 10px;background:#f8fafc;border-radius:10px;color:#475569;">
                    <b>Catatan admin:</b><br>
                    <?= nl2br(h($r['catatan_admin'])) ?>
                  </div>
                <?php endif; ?>
              </td>

              <td class="td">
                <?php if ($lampiranUrl !== ''): ?>
                  <a href="<?= h($lampiranUrl) ?>" target="_blank" style="text-decoration:none;">
                    <img
                      src="<?= h($lampiranUrl) ?>"
                      alt="Lampiran"
                      style="width:74px;height:74px;object-fit:cover;border-radius:12px;border:1px solid #e5e7eb;"
                    >
                  </a>
                <?php else: ?>
                  <span style="color:#94a3b8;">Tidak ada</span>
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

              <td class="td">
                <?= h(date('d-m-Y H:i', strtotime($r['created_at'] ?? 'now'))) ?>
              </td>

              <td class="td" style="text-align:left;min-width:260px;">
                <?php if ($status === 'MENUNGGU'): ?>
                  <form method="POST" action="<?= $base ?>/pengajuan/verifikasi" style="display:grid;gap:8px;margin-bottom:8px;">
                    <input type="hidden" name="id_pengajuan" value="<?= h($r['id_pengajuan']) ?>">

                    <textarea
                      name="catatan_admin"
                      class="in"
                      placeholder="Catatan admin (opsional)"
                      style="min-height:74px;resize:vertical;"
                    ></textarea>

                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                      <button class="btn success" type="submit" name="aksi" value="SETUJUI">
                        Setujui
                      </button>
                      <button
                        class="btn danger"
                        type="submit"
                        name="aksi"
                        value="TOLAK"
                        onclick="return confirm('Yakin ingin menolak pengajuan ini?')"
                      >
                        Tolak
                      </button>
                    </div>
                  </form>

                  <form method="POST" action="<?= $base ?>/pengajuan/delete" onsubmit="return confirm('Yakin ingin menghapus pengajuan ini?');" style="margin:0;">
                    <input type="hidden" name="id_pengajuan" value="<?= h($r['id_pengajuan']) ?>">
                    <button class="btn danger-soft-btn" type="submit">Hapus</button>
                  </form>
                <?php else: ?>
                  <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-start;">
                    <div style="color:#64748b;font-weight:600;">Sudah diverifikasi</div>

                    <form method="POST" action="<?= $base ?>/pengajuan/delete" onsubmit="return confirm('Yakin ingin menghapus pengajuan ini?');" style="margin:0;">
                      <input type="hidden" name="id_pengajuan" value="<?= h($r['id_pengajuan']) ?>">
                      <button class="btn danger-soft-btn" type="submit">Hapus</button>
                    </form>
                  </div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
  .lbl{
    display:block;
    margin:0 0 6px;
    font-weight:600;
    font-size:13px;
    color:#111827;
  }
  .in{
    width:100%;
    padding:10px 12px;
    border:1px solid #dbe6f5;
    border-radius:12px;
    outline:none;
    background:#fff;
  }
  .flash{
    padding:12px 14px;
    border-radius:12px;
    margin-bottom:12px;
    font-weight:600;
  }
  .flash.ok{
    background:#ecfdf5;
    color:#166534;
    border:1px solid #bbf7d0;
  }
  .flash.err{
    background:#fef2f2;
    color:#991b1b;
    border:1px solid #fecaca;
  }
  .sum-card{
    background:#fff;
    border-radius:16px;
    padding:16px;
    box-shadow:0 6px 18px rgba(15,23,42,.05);
  }
  .sum-label{
    color:#64748b;
    font-size:13px;
    font-weight:600;
  }
  .sum-value{
    margin-top:8px;
    font-size:28px;
    font-weight:800;
    color:#0f172a;
  }
  .th{
    padding:12px;
    border-bottom:1px solid #e8eef6;
    text-align:center;
    font-size:13px;
    font-weight:700;
    color:#334155;
  }
  .td{
    padding:12px;
    border-bottom:1px solid #e8eef6;
    text-align:center;
    vertical-align:top;
    font-size:14px;
  }
  .badge{
    display:inline-block;
    padding:6px 10px;
    border-radius:999px;
    font-weight:700;
    font-size:12px;
  }
  .badge.ok{
    background:#dcfce7;
    color:#166534;
  }
  .badge.warn{
    background:#fef3c7;
    color:#92400e;
  }
  .badge.pending{
    background:#e0f2fe;
    color:#075985;
  }
  .badge.danger{
    background:#fee2e2;
    color:#b91c1c;
  }
  .badge.danger-soft{
    background:#f1f5f9;
    color:#475569;
  }
  .btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:10px 14px;
    border:none;
    border-radius:12px;
    font-weight:700;
    cursor:pointer;
    text-decoration:none;
  }
  .btn.primary{
    background:#2563eb;
    color:#fff;
  }
  .btn.success{
    background:#16a34a;
    color:#fff;
  }
  .btn.danger{
    background:#dc2626;
    color:#fff;
  }
  .btn.danger-soft-btn{
    background:#fee2e2;
    color:#b91c1c;
    border:1px solid #fecaca;
  }
</style>