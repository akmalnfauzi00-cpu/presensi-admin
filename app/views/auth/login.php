<?php
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base === '/') $base = '';

$pageTitle = $pageTitle ?? 'Login Admin';
$error = Session::getFlash('error');
$success = Session::getFlash('success');
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($pageTitle) ?></title>
  <style>
    body{background:#f3f6fb;margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif}
    .wrap{min-height:100vh;display:grid;place-items:center;padding:24px}
    .box{width:min(980px,100%);display:grid;grid-template-columns:1.15fr 1fr;gap:16px;align-items:stretch}
    .brand{border-radius:18px;padding:28px;color:#fff;background:linear-gradient(135deg,#1d4ed8,#0b2f8a);position:relative;overflow:hidden;box-shadow:0 18px 40px rgba(15,23,42,.12)}
    .brand:before{content:"";position:absolute;top:-90px;right:-120px;width:290px;height:290px;background:rgba(255,255,255,.10);border-radius:999px}
    .brand:after{content:"";position:absolute;bottom:-130px;left:-130px;width:340px;height:340px;background:rgba(255,255,255,.08);border-radius:999px}
    .school{position:relative;display:flex;gap:14px;align-items:center}
    .logo{width:56px;height:56px;border-radius:999px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.22);display:grid;place-items:center;font-size:20px;flex:0 0 auto}
    .school-name{margin:0;font-size:16px;line-height:1.2;letter-spacing:.4px;font-weight:700}
    .school-sub{margin:6px 0 0;font-size:12px;opacity:.9}
    .copy{position:relative;margin-top:18px;font-size:13px;line-height:1.7;opacity:.92}
    .form{border-radius:18px;background:#fff;box-shadow:0 18px 40px rgba(15,23,42,.08);padding:22px;display:flex;flex-direction:column;justify-content:center}
    .title{margin:0;font-size:18px;color:#0f172a;font-weight:700}
    .desc{margin:6px 0 16px;color:#64748b;font-size:13px}
    .err{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:10px 12px;border-radius:14px;font-size:13px;margin-bottom:12px}
    .ok{background:#dcfce7;border:1px solid #bbf7d0;color:#166534;padding:10px 12px;border-radius:14px;font-size:13px;margin-bottom:12px}
    .lbl{display:block;margin:0 0 6px;color:#0f172a;font-size:13px}
    .in{width:100%;padding:12px 12px;border:1px solid #e6edf7;border-radius:14px;outline:none;background:#fff;box-sizing:border-box}
    .btn-login{width:100%;margin-top:12px;padding:12px 14px;border-radius:14px;border:none;cursor:pointer;background:#1d4ed8;color:#fff;font-size:14px;font-weight:700}
    .setup-link{margin-top:10px;text-align:center;font-size:12px}
    .setup-link a{color:#1d4ed8;text-decoration:none;font-weight:700}
    .foot{margin-top:12px;text-align:center;color:#94a3b8;font-size:12px}
    @media (max-width: 860px){.box{grid-template-columns:1fr}.brand{min-height:170px}}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="box">
      <div class="brand">
        <div class="school">
          <div class="logo">SM</div>
          <div>
            <p class="school-name">SMP MUHAMMADIYAH 2 KARANGLEWAS</p>
            <p class="school-sub">Sistem Presensi Guru • Panel Admin</p>
          </div>
        </div>

        <div class="copy">
          Silakan masuk menggunakan akun admin untuk mengelola data guru,
          laporan presensi, serta reward/SP.
        </div>
      </div>

      <div class="form">
        <p class="title">Login Admin</p>
        <p class="desc">Masuk untuk mengelola presensi</p>

        <?php if ($error): ?><div class="err"><?= h($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="ok"><?= h($success) ?></div><?php endif; ?>

        <form method="post" action="<?= $base ?>/login">
          <div style="margin-bottom:12px;">
            <label class="lbl">Username</label>
            <input class="in" type="text" name="username" placeholder="Masukkan username" required>
          </div>

          <div style="margin-bottom:12px;">
            <label class="lbl">Password</label>
            <input class="in" type="password" name="password" placeholder="Masukkan password" required>
          </div>

          <button class="btn-login" type="submit">Masuk</button>
        </form>

        <div class="setup-link">
          Belum punya admin? <a href="<?= $base ?>/setup/create-admin">Buat admin pertama</a>
        </div>

        <div class="foot">
          © <?= date('Y') ?> SMP Muhammadiyah 2 Karanglewas
        </div>
      </div>
    </div>
  </div>
</body>
</html>