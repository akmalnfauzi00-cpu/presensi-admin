<?php
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

$authUser = Auth::user();
$pageTitle = $pageTitle ?? 'Admin Presensi';

$path = (new Request())->path();

function active($current, $target) {
  if ($target === '/') return $current === '/' ? 'active' : '';
  return strpos($current, $target) === 0 ? 'active' : '';
}

$badgePending = 0;
try {
  $pdoBadge = Db::pdo();
  $stBadge = $pdoBadge->query("SELECT COUNT(*) AS jml FROM pengajuan_presensi WHERE status_verifikasi='MENUNGGU'");
  $badgeRow = $stBadge->fetch(PDO::FETCH_ASSOC);
  $badgePending = (int)($badgeRow['jml'] ?? 0);
} catch (Throwable $e) {
  $badgePending = 0;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body>
  <div class="layout">

    <aside class="sidebar">
      <div class="brand">
        <div class="logo">PA</div>
        <div class="meta">
          <div class="title">Presensi Admin</div>
          <div class="sub"><?= htmlspecialchars($authUser['nama'] ?? 'Admin') ?></div>
        </div>
      </div>

      <nav class="nav">
        <a class="<?= active($path, '/dashboard') ?>" href="<?= $base ?>/dashboard">
          <span>Dashboard</span>
        </a>

        <a class="<?= active($path, '/users') ?>" href="<?= $base ?>/users">
          <span>User Admin</span>
        </a>

        <a class="<?= active($path, '/guru') ?>" href="<?= $base ?>/guru">
          <span>Guru</span>
        </a>

        <a class="<?= active($path, '/setting') ?>" href="<?= $base ?>/setting">
          <span>Setting</span>
        </a>

        <a class="<?= active($path, '/rewardsp') ?>" href="<?= $base ?>/rewardsp">
          <span>Reward/SP</span>
        </a>

        <a class="<?= active($path, '/laporan') ?>" href="<?= $base ?>/laporan">
          <span>Laporan</span>
        </a>

        <a class="<?= active($path, '/pengumuman') ?>" href="<?= $base ?>/pengumuman">
  <span>Pengumuman</span>
</a>

        <a class="<?= active($path, '/pengajuan') ?>" href="<?= $base ?>/pengajuan" style="display:flex;align-items:center;gap:8px;">
          <span>Pengajuan</span>
          <?php if ($badgePending > 0): ?>
            <span style="margin-left:auto;background:#dc2626;color:#fff;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;">
              <?= $badgePending ?>
            </span>
          <?php endif; ?>
        </a>

        <form method="post" action="<?= $base ?>/logout" style="margin-top:12px;">
          <button class="btn danger" type="submit">Logout</button>
        </form>
      </nav>
    </aside>

    <main class="main">
      <header class="topbar">
        <div>
          <div class="h">Selamat Pagi, <?= htmlspecialchars($authUser['nama'] ?? 'Admin') ?></div>
          <div class="p">Ringkasan Hari Ini</div>
        </div>
        <div class="right" style="display:flex;align-items:center;gap:10px;">
          <span class="badge">Role: <?= htmlspecialchars(ucfirst($authUser['role'] ?? 'admin')) ?></span>

          <?php if ($badgePending > 0): ?>
            <a
              href="<?= $base ?>/pengajuan"
              style="text-decoration:none;background:#fef2f2;color:#b91c1c;padding:6px 12px;border-radius:999px;font-size:12px;font-weight:700;border:1px solid #fecaca;"
            >
              <?= $badgePending ?> pengajuan menunggu
            </a>
          <?php endif; ?>
        </div>
      </header>

      <div class="content">

        <?php if ($msg = Session::getFlash('success')): ?>
          <div class="flash success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if ($msg = Session::getFlash('error')): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php include $contentFile; ?>
      </div>
    </main>

  </div>
</body>
</html>