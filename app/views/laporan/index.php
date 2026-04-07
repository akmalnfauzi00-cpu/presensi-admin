<?php function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } ?>
<?php
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base === '/') $base = '';
?>

<div class="card">
  <div class="card-head" style="display:flex;justify-content:space-between;align-items:center;">
    <div>
      <div style="font-weight:700;">Rekapitulasi Guru</div>
      <div class="p">Home / Laporan / Kehadiran</div>
    </div>
    <div class="p">Total hari periode: <strong><?= (int)$jumlahHari ?></strong></div>
  </div>
</div>

<div style="height:12px;"></div>

<div style="display:grid;grid-template-columns:380px 1fr;gap:14px;align-items:start;">
  <div class="card">
    <div class="card-head">Filter Laporan</div>
    <div class="card-body">
      <form method="GET" action="<?= $base ?>/laporan">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
          <div>
            <label class="lbl">Mulai</label>
            <input class="in" type="date" name="mulai" value="<?= h($mulai) ?>">
          </div>
          <div>
            <label class="lbl">Selesai</label>
            <input class="in" type="date" name="selesai" value="<?= h($selesai) ?>">
          </div>
        </div>

        <div style="margin-top:10px;">
          <label class="lbl">Nama Guru</label>
          <select class="in" name="id_guru">
            <option value="">Semua Guru</option>
            <?php foreach ($guru as $g): ?>
              <option value="<?= h($g['id_guru']) ?>" <?= ($idGuru === (string)$g['id_guru']) ? 'selected' : '' ?>>
                <?= h($g['nama_guru']) ?> (<?= h($g['nip']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <button class="btn primary" type="submit" style="width:100%;margin-top:12px;">
          Terapkan Filter
        </button>
      </form>

      <hr style="margin:16px 0;">

      <div style="display:grid;grid-template-columns:1fr;gap:12px;margin-top:12px;">
        <a class="btn"
           style="text-align:center;text-decoration:none;background:#334155;color:#fff;padding:12px;border-radius:12px;font-weight:600;"
           href="<?= $base ?>/laporan/print?mulai=<?= h($mulai) ?>&selesai=<?= h($selesai) ?>&id_guru=<?= h($idGuru) ?>"
           target="_blank">
           🖨️ Print Preview
        </a>
      </div>

      <div class="p" style="margin-top:10px;color:#6b7280;font-size:12px;">
        PDF dan print akan menampilkan kolom Tidak Hadir.
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-head" style="display:flex;justify-content:space-between;align-items:center;">
      <div>Data Kehadiran</div>
      <div class="p"><?= h(date('d M Y', strtotime($mulai))) ?> - <?= h(date('d M Y', strtotime($selesai))) ?></div>
    </div>

    <div class="card-body" style="padding:0;overflow:auto;">
      <table style="width:100%;border-collapse:collapse;min-width:760px;">
        <thead>
          <tr style="background:#f6f8fc;">
            <th style="text-align:left;padding:12px;border-bottom:1px solid #e8eef6;">Nama Guru</th>
            <th style="text-align:center;padding:12px;border-bottom:1px solid #e8eef6;width:90px;">Hadir</th>
            <th style="text-align:center;padding:12px;border-bottom:1px solid #e8eef6;width:90px;">Lambat</th>
            <th style="text-align:center;padding:12px;border-bottom:1px solid #e8eef6;width:90px;">Izin</th>
            <th style="text-align:center;padding:12px;border-bottom:1px solid #e8eef6;width:90px;">Sakit</th>
            <th style="text-align:center;padding:12px;border-bottom:1px solid #e8eef6;width:120px;">Tidak Hadir</th>
          </tr>
        </thead>

        <tbody>
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="6" style="padding:14px;color:#6b7280;">Tidak ada data.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td style="padding:12px;border-bottom:1px solid #e8eef6;">
                  <div style="font-weight:600;"><?= h($r['nama_guru']) ?></div>
                  <div class="p"><?= h($r['nip']) ?></div>
                </td>

                <td style="text-align:center;padding:12px;border-bottom:1px solid #e8eef6;">
                  <?= (int)($r['hadir'] ?? 0) ?>
                </td>

                <td style="text-align:center;padding:12px;border-bottom:1px solid #e8eef6;color:#b45309;">
                  <?= (int)($r['lambat'] ?? 0) ?>
                </td>

                <td style="text-align:center;padding:12px;border-bottom:1px solid #e8eef6;color:#0369a1;">
                  <?= (int)($r['izin'] ?? 0) ?>
                </td>

                <td style="text-align:center;padding:12px;border-bottom:1px solid #e8eef6;color:#dc2626;">
                  <?= (int)($r['sakit'] ?? 0) ?>
                </td>

                <td style="text-align:center;padding:12px;border-bottom:1px solid #e8eef6;color:#7c3aed;font-weight:700;">
                  <?= (int)($r['tidak_hadir'] ?? 0) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

      <div class="p" style="padding:12px;">
        Menampilkan <?= count($rows) ?> dari <?= count($rows) ?> guru
      </div>
    </div>
  </div>
</div>

<style>
  .lbl{display:block;margin:0 0 6px;font-weight:600;font-size:13px;color:#111827}
  .in{width:100%;padding:10px 12px;border:1px solid #dbe6f5;border-radius:12px;outline:none}
</style>