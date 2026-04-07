<?php
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

$success = Session::pullFlash('success');
$error   = Session::pullFlash('error');

$q = $q ?? ($_GET['q'] ?? '');
?>

<div class="guru-page">

  <div class="guru-page-header">
    <div class="guru-title">
      <span class="guru-title-icon">🛡️</span>
      <h2>Kelola User Admin</h2>
    </div>

    <a class="guru-btn-add" href="<?= $base ?>/users/create">+ Tambah Admin</a>
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
        <span class="guru-card-icon">👤</span>
        <span>Tabel User Admin</span>
      </div>

      <form class="guru-search" method="get" action="<?= $base ?>/users">
        <input name="q" placeholder="Cari username / nama / role..." value="<?= htmlspecialchars($q) ?>">
        <button type="submit">Cari</button>
      </form>
    </div>

    <div class="guru-card-body">
      <div class="guru-table-wrap">
        <table class="guru-table">
          <thead>
            <tr>
              <th style="width:60px;">No</th>
              <th style="width:220px;">Nama</th>
              <th style="width:180px;">Username</th>
              <th style="width:140px;">Role</th>
              <th style="width:140px;">Status</th>
              <th style="width:180px;">Dibuat</th>
              <th style="width:180px;">Diubah</th>
              <th style="width:170px; text-align:center;">Aksi</th>
            </tr>
          </thead>

          <tbody>
            <?php if (empty($rows)): ?>
              <tr>
                <td colspan="8" class="guru-empty">Belum ada data user admin.</td>
              </tr>
            <?php endif; ?>

            <?php $no = 1; foreach ($rows as $r): ?>
              <?php
                $isActive = (int)($r['status_aktif'] ?? 1) === 1;
                $statusLabel = $isActive ? 'Aktif' : 'Nonaktif';
                $statusClass = $isActive ? 'guru-badge-green' : 'guru-badge-gray';

                $role = strtolower((string)($r['role'] ?? 'admin'));
                $roleClass = $role === 'superadmin' ? 'guru-badge-blue' : 'guru-badge-orange';
              ?>
              <tr>
                <td><?= $no++ ?></td>
                <td class="guru-strong"><?= htmlspecialchars($r['nama'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['username'] ?? '-') ?></td>
                <td>
                  <span class="guru-badge <?= $roleClass ?>">
                    <?= htmlspecialchars(ucfirst($role)) ?>
                  </span>
                </td>
                <td>
                  <span class="guru-badge <?= $statusClass ?>">
                    <?= htmlspecialchars($statusLabel) ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($r['created_at'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['updated_at'] ?? '-') ?></td>
                <td class="guru-aksi">
                  <a class="guru-btn-icon guru-warn"
                     href="<?= $base ?>/users/edit?id=<?= urlencode($r['id_user']) ?>"
                     title="Edit User">✏️</a>

                  <form method="post"
                        action="<?= $base ?>/users/delete"
                        class="guru-delete-form"
                        onsubmit="return confirm('Hapus user admin ini?')">
                    <input type="hidden" name="id_user" value="<?= htmlspecialchars($r['id_user']) ?>">
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

  .guru-badge{ display:inline-flex; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:900; }
  .guru-badge-blue{ background:#dbeafe; color:#1d4ed8; }
  .guru-badge-green{ background:#dcfce7; color:#166534; }
  .guru-badge-orange{ background:#ffedd5; color:#9a3412; }
  .guru-badge-gray{ background:#e5e7eb; color:#374151; }

  .guru-aksi{ text-align:center; white-space:nowrap; min-width:170px; }
  .guru-delete-form{ display:inline; }

  .guru-btn-icon{
    display:inline-flex; align-items:center; justify-content:center;
    width:40px; height:40px; border-radius:12px;
    border:0; cursor:pointer; text-decoration:none;
    margin:0 4px;
    font-size:16px;
  }
  .guru-warn{ background:#fbbf24; }
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