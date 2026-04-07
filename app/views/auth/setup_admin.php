<?php
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$error = Session::pullFlash('error');

$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base === '/') $base = '';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Setup Admin</title>
  <style>
    body{margin:0;font-family:system-ui;background:#f3f6fb}
    .wrap{max-width:520px;margin:50px auto;background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px}
    .muted{color:#6b7280;font-size:12px}
    .input{width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:12px;box-sizing:border-box}
    .btn{width:100%;padding:10px 12px;border-radius:12px;border:0;background:#1d4ed8;color:#fff;font-weight:800;cursor:pointer}
    .flash{padding:10px 12px;border-radius:12px;margin-bottom:12px;background:#fef2f2;border:1px solid #fecaca}
    a{color:#1d4ed8;font-weight:800;text-decoration:none}
  </style>
</head>
<body>
  <div class="wrap">
    <h2 style="margin:0 0 6px;">Setup Admin Pertama</h2>
    <div class="muted" style="margin-bottom:14px;">
      Buat akun admin pertama. Setelah sukses, bisa login.
    </div>

    <?php if ($error): ?><div class="flash"><?= h($error) ?></div><?php endif; ?>

    <form method="post" action="<?= $base ?>/setup/create-admin">
      <div style="margin-bottom:10px">
        <label class="muted">Nama</label>
        <input class="input" name="nama" value="Admin Utama">
      </div>

      <div style="margin-bottom:10px">
        <label class="muted">Username</label>
        <input class="input" name="username" required>
      </div>

      <div style="margin-bottom:14px">
        <label class="muted">Password</label>
        <input class="input" type="password" name="password" required>
      </div>

      <button class="btn" type="submit">Buat Admin</button>
    </form>

    <div class="muted" style="margin-top:12px;">
      Sudah punya akun? <a href="<?= $base ?>/login">Login</a>
    </div>
  </div>
</body>
</html>