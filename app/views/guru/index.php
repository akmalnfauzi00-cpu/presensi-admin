<?php
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

$success = Session::pullFlash('success');
$error   = Session::pullFlash('error');

$q = $q ?? ($_GET['q'] ?? '');
?>

<div class="guru-page">

  <div class="guru-page-header">
    <div class="guru-title">
      <span class="guru-title-icon">👥</span>
      <h2>Kelola Guru</h2>
    </div>

    <a class="guru-btn-add" href="<?= $base ?>/guru/create">+ Tambah Guru</a>
  </div>

  <?php if ($success): ?>
    <div class="guru-flash guru-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="guru-flash guru-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="guru-card">
    <div class="guru-card-head">
      <div class="guru-card-title">
        <span class="guru-card-icon">🧾</span>
        <span>Tabel Data Guru</span>
      </div>

      <form class="guru-search" method="get" action="<?= $base ?>/guru">
        <input name="q" placeholder="Cari nama..." value="<?= htmlspecialchars($q) ?>">
        <button type="submit">Cari</button>
      </form>
    </div>

    <div class="guru-card-body">
      <div class="guru-table-wrap">
        <table class="guru-table">
          <thead>
            <tr>
              <th style="width:60px;">No</th>
              <th style="width:90px;">Foto</th>
              <th style="width:220px;">Nama Guru</th>
              <th style="width:170px;">NIP</th>
              <th style="width:220px;">Mata Pelajaran</th>
              <th style="width:160px;">Jenis Kelamin</th>
              <th style="width:140px;">Status</th>
              <th style="width:260px;">Alamat</th>
              <th style="width:160px;">No. HP</th>
              <th style="width:260px;">Email</th>
              <th style="width:210px; text-align:center;">Aksi</th>
            </tr>
          </thead>

          <tbody>
            <?php if (empty($rows)): ?>
              <tr>
                <td colspan="11" class="guru-empty">Belum ada data guru.</td>
              </tr>
            <?php endif; ?>

            <?php $no=1; foreach ($rows as $r): ?>
              <?php
                $jk = $r['jenis_kelamin'] ?? '';
                $jkClass = ($jk === 'Perempuan') ? 'guru-badge-green' : ($jk ? 'guru-badge-blue' : 'guru-badge-gray');

                $st = $r['status_aktif'] ?? 'AKTIF';
                $stClass = ($st === 'AKTIF') ? 'guru-badge-green' : (($st === 'CUTI') ? 'guru-badge-orange' : 'guru-badge-gray');

                $alamat = trim((string)($r['alamat'] ?? '-'));
                $alamatShort = (mb_strlen($alamat) > 30) ? mb_substr($alamat, 0, 30) . '...' : $alamat;

                $mapel = trim((string)($r['mata_pelajaran'] ?? '-'));
                $mapelShort = (mb_strlen($mapel) > 24) ? mb_substr($mapel, 0, 24) . '...' : $mapel;

                // indikator akun presensi: sudah punya password_hash atau belum
                $hasAccount = !empty($r['password_hash']);
              ?>
              <tr>
                <td><?= $no++ ?></td>

                <td>
                  <?php if (!empty($r['foto'])): ?>
                    <img class="guru-avatar" src="<?= $base ?>/<?= htmlspecialchars($r['foto']) ?>" alt="foto">
                  <?php else: ?>
                    <div class="guru-avatar guru-avatar-ph">
                      <?= strtoupper(substr($r['nama_guru'] ?? 'G', 0, 1)) ?>
                    </div>
                  <?php endif; ?>
                </td>

                <td class="guru-strong"><?= htmlspecialchars($r['nama_guru'] ?? '-') ?></td>
                <td title="<?= htmlspecialchars($r['nip'] ?? '') ?>"><?= htmlspecialchars($r['nip'] ?? '-') ?></td>

                <td title="<?= htmlspecialchars($mapel) ?>"><?= htmlspecialchars($mapelShort) ?></td>

                <td>
                  <?php if ($jk): ?>
                    <span class="guru-badge <?= $jkClass ?>"><?= htmlspecialchars($jk) ?></span>
                  <?php else: ?>
                    <span class="guru-badge guru-badge-gray">-</span>
                  <?php endif; ?>
                </td>

                <td>
                  <span class="guru-badge <?= $stClass ?>"><?= htmlspecialchars($st) ?></span>
                </td>

                <td title="<?= htmlspecialchars($alamat) ?>"><?= htmlspecialchars($alamatShort) ?></td>
                <td><?= htmlspecialchars($r['no_hp'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['email'] ?? '-') ?></td>

                <td class="guru-aksi">
                  <!-- 1) Edit data profil yang dipakai aplikasi -->
                  <a class="guru-btn-icon guru-warn"
                     href="<?= $base ?>/guru/edit?id=<?= urlencode($r['id_guru']) ?>"
                     title="Edit Profil Guru (untuk aplikasi)">✏️</a>
                  <!-- 3) Hapus -->
                  <form method="post"
                        action="<?= $base ?>/guru/delete"
                        class="guru-delete-form"
                        onsubmit="return confirm('Hapus guru ini?')">
                    <input type="hidden" name="id_guru" value="<?= htmlspecialchars($r['id_guru']) ?>">
                    <button type="submit" class="guru-btn-icon guru-danger" title="Hapus">🗑️</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="guru-foot">
        <a class="guru-back" href="<?= $base ?>/dashboard">← Kembali ke Dashboard</a>
      </div>
    </div>
  </div>

</div>

<style>
  .guru-page{ padding:18px; }
  .guru-page-header{ display:flex; align-items:center; justify-content:space-between; gap:14px; margin:4px 0 14px; }
  .guru-title{ display:flex; align-items:center; gap:12px; }
  .guru-title h2{ margin:0; font-size:32px; font-weight:900; }
  .guru-title-icon{ font-size:20px; }

  .guru-btn-add{ display:inline-flex; align-items:center; justify-content:center; padding:10px 14px; border-radius:12px; background:#1d4ed8; color:#fff; font-weight:900; text-decoration:none; }
  .guru-btn-add:hover{ filter:brightness(.95); }

  .guru-flash{ padding:12px 14px; border-radius:12px; margin-bottom:12px; border:1px solid transparent; }
  .guru-success{ background:#ecfdf5; border-color:#bbf7d0; color:#065f46; }
  .guru-error{ background:#fef2f2; border-color:#fecaca; color:#991b1b; }

  .guru-card{ border-radius:18px; overflow:hidden; background:#fff; border:1px solid #e5e7eb; }
  .guru-card-head{ background:#1d4ed8; color:#fff; padding:14px 16px; display:flex; align-items:center; justify-content:space-between; gap:12px; }
  .guru-card-title{ display:flex; align-items:center; gap:10px; font-weight:900; font-size:18px; }

  .guru-search{ display:flex; align-items:center; background:#fff; border-radius:999px; overflow:hidden; height:42px; min-width:320px; }
  .guru-search input{ border:0; outline:none; padding:0 14px; height:42px; width:100%; font-size:14px; }
  .guru-search button{ border:0; height:42px; padding:0 16px; font-weight:900; background:#0b5ed7; color:#fff; cursor:pointer; }
  .guru-search button:hover{ filter:brightness(.95); }

  .guru-card-body{ padding:14px; background:#f8fafc; }
  .guru-table-wrap{ background:#fff; border-radius:14px; overflow:auto; border:1px solid #e5e7eb; }

  .guru-table{ width:100%; border-collapse:collapse; table-layout:fixed; }
  .guru-table th{ background:#f3f4f6; text-align:left; padding:12px; font-weight:900; border-bottom:1px solid #e5e7eb; }
  .guru-table td{ padding:12px; border-bottom:1px solid #eef2f7; vertical-align:middle; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .guru-empty{ padding:18px !important; color:#6b7280; }

  .guru-strong{ font-weight:900; }

  .guru-avatar{ width:44px; height:44px; border-radius:12px; object-fit:cover; border:1px solid #e5e7eb; display:block; }
  .guru-avatar-ph{ display:flex; align-items:center; justify-content:center; background:#e8efff; color:#0b5ed7; font-weight:900; }

  .guru-badge{ display:inline-flex; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:900; }
  .guru-badge-blue{ background:#dbeafe; color:#1d4ed8; }
  .guru-badge-green{ background:#dcfce7; color:#166534; }
  .guru-badge-orange{ background:#ffedd5; color:#9a3412; }
  .guru-badge-gray{ background:#e5e7eb; color:#374151; }

  .guru-aksi{ text-align:center; white-space:nowrap; min-width:210px; }
  .guru-delete-form{ display:inline; }

  .guru-btn-icon{
    display:inline-flex; align-items:center; justify-content:center;
    width:40px; height:40px; border-radius:12px;
    border:0; cursor:pointer; text-decoration:none;
    margin:0 4px;
    font-size:16px;
  }
  .guru-warn{ background:#fbbf24; }
  .guru-primary{ background:#1d4ed8; color:#fff; }
  .guru-info{ background:#0ea5e9; color:#fff; }
  .guru-danger{ background:#ef4444; color:#fff; }
  .guru-btn-icon:hover{ filter:brightness(.95); }

  .guru-foot{ padding:10px 4px 0; display:flex; justify-content:flex-start; }
  .guru-back{ color:#111827; text-decoration:none; font-weight:700; }
  .guru-back:hover{ text-decoration:underline; }

  @media (max-width: 900px){
    .guru-card-head{ flex-direction:column; align-items:flex-start; }
    .guru-search{ width:100%; min-width:unset; }
  }
</style>