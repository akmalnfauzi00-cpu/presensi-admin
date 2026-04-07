<?php function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } ?>

<?php
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base === '/') $base = '';
?>

<div class="card">
  <div class="card-head">
    <div class="p" style="margin:0;">Reward &amp; SP Guru — Kirim dokumen PDF ke guru dan cek status unduh.</div>
  </div>
</div>

<div style="height:12px;"></div>

<div class="grid" style="display:grid;grid-template-columns:420px 1fr;gap:14px;align-items:start;">

  <div class="card">
    <div class="card-head">Kirim Dokumen Baru</div>
    <div class="card-body">
        <form method="POST" action="<?= $base ?>/rewardsp/create" enctype="multipart/form-data">
            <div style="margin-bottom:12px;">
                <label style="display:block;margin-bottom:6px;">Pilih Nama Guru</label>
                <select name="guru_pick" required class="input">
                    <option value="" disabled selected>— pilih guru —</option>
                    <?php foreach ($guru as $g): ?>
                        <option value="<?= h($g['id_guru']) ?>">
                            <?= h($g['nama_guru']) ?> (<?= h($g['nip']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block;margin-bottom:6px;">Periode (wajib)</label>
                <input class="input" name="periode" placeholder="contoh: 2026-02 / 2026-Semester1" value="<?= h(date('Y-m')) ?>">
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block;margin-bottom:6px;">Jenis</label>
                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                    <label style="display:flex;gap:8px;align-items:center;">
                        <input type="radio" name="jenis" value="REWARD" checked> Reward
                    </label>
                    <label style="display:flex;gap:8px;align-items:center;">
                        <input type="radio" name="jenis" value="SP"> SP
                    </label>
                </div>
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block;margin-bottom:6px;">Deskripsi</label>
                <input class="input" name="deskripsi" placeholder="Tulis keterangan singkat (opsional)">
            </div>

            <button class="btn primary" type="submit" style="width:100%;">Kirim Dokumen</button>
        </form>
    </div>
</div>
  <div class="card">
    <div class="card-head" style="display:flex;justify-content:space-between;align-items:center;">
      <div>Riwayat Terkirim</div>
      <div class="p">Terbaru (maks 50)</div>
    </div>

    <div class="card-body">
      <?php if (empty($docs)): ?>
        <div class="p">Belum ada dokumen.</div>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <?php foreach ($docs as $d): ?>
            <div style="border:1px solid #e8eef6;border-radius:12px;padding:12px;display:flex;justify-content:space-between;gap:12px;background:#fff;">
              <div>
                <div><?= h($d['nama_guru']) ?> <span style="color:#6b7280;">(<?= h($d['nip']) ?>)</span></div>
                <div class="p" style="margin-top:4px;">
                  <?= h($d['jenis']) ?> • Periode: <?= h($d['periode']) ?>
                  <?php if (!empty($d['dibuat_pada'])): ?> • <?= h($d['dibuat_pada']) ?><?php endif; ?>
                  <?php if (!empty($d['deskripsi'])): ?> • <?= h($d['deskripsi']) ?><?php endif; ?>
                </div>
              </div>

              <div style="text-align:right;display:flex;flex-direction:column;gap:8px;align-items:flex-end;">
                <?php if (($d['status_unduh'] ?? '') === 'SUDAH_DIUNDUH'): ?>
                  <span class="badge ok">SUDAH DIUNDUH</span>
                <?php else: ?>
                  <span class="badge wait">BELUM DIUNDUH</span>
                <?php endif; ?>

                <a href="<?= $base ?>/rewardsp/download?id=<?= h($d['id_dokumen']) ?>"
                   style="color:#2563eb;text-decoration:none;font-size:12px;">Unduh PDF</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<style>
  .input{ width:100%; padding:10px 12px; border:1px solid #dbe6f5; border-radius:12px; outline:none; }
  .badge{ padding:6px 10px; border-radius:999px; font-weight:700; font-size:11px; }
  .badge.ok{ background:#eafff2; border:1px solid #bff0cf; color:#0f5132; }
  .badge.wait{ background:#fff7e6; border:1px solid #ffd699; color:#7a4b00; }
  @media (max-width:1100px){ .grid{ grid-template-columns:1fr !important; } }
</style>