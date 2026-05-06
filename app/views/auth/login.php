<?php
// Helper untuk keamanan output agar terhindar dari XSS
if (!function_exists('h')) {
    function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

// Penentuan base URL agar link internal (login, setup, assets) tidak rusak
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base === '/') $base = '';

$pageTitle = $pageTitle ?? 'Login Admin';

// Mengambil pesan notifikasi dari session
$error = Session::pullFlash('error');
$success = Session::pullFlash('success');
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #1d4ed8;
      --primary-dark: #1e3a8a;
      --text-main: #0f172a;
      --text-sub: #64748b;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: #fff;
      overflow-x: hidden;
    }

    .login-wrapper {
      display: flex;
      min-height: 100vh;
      width: 100%;
    }

    /* SISI KIRI - VISUAL MENDOMINASI */
    .side-branding {
      flex: 3.5; 
      background: #1d4ed8;
      background-image: 
        radial-gradient(at 0% 0%, hsla(224,96%,41%,1) 0px, transparent 50%),
        radial-gradient(at 100% 0%, hsla(217,91%,60%,1) 0px, transparent 50%),
        radial-gradient(at 100% 100%, hsla(224,84%,25%,1) 0px, transparent 50%),
        radial-gradient(at 0% 100%, hsla(201,84%,52%,1) 0px, transparent 50%);
      color: #fff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 0 10%;
      position: relative;
      overflow: hidden;
    }

    .side-branding::before {
      content: "";
      position: absolute;
      inset: 0;
      opacity: 0.1;
      background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
      z-index: 1;
    }

    .brand-content { position: relative; z-index: 5; }

    .logo-badge {
      width: 64px; height: 64px;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.25);
      border-radius: 20px;
      display: flex; align-items: center; justify-content: center;
      font-size: 24px; font-weight: 800; margin-bottom: 40px;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .side-branding h1 {
      font-size: 56px; font-weight: 800; margin: 0;
      line-height: 1; letter-spacing: -3px; text-transform: uppercase;
    }

    .side-branding h1 span {
      display: block;
      background: linear-gradient(to bottom, #ffffff, #cbd5e1);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }

    .side-branding p.subtitle {
      font-size: 16px; margin-top: 24px; color: rgba(255, 255, 255, 0.7);
      font-weight: 500; letter-spacing: 4px; text-transform: uppercase;
      display: flex; align-items: center; gap: 15px;
    }

    .side-branding p.subtitle::after {
      content: ""; height: 1px; width: 50px; background: rgba(255, 255, 255, 0.3);
    }

    /* SISI KANAN - FORM */
    .side-form {
      flex: 1; background: #ffffff;
      display: flex; flex-direction: column; justify-content: center;
      padding: 40px; border-left: 1px solid #f1f5f9; min-width: 340px;
    }

    .form-container { width: 100%; max-width: 280px; margin: 0 auto; }

    .side-form h2 { font-size: 24px; font-weight: 800; color: var(--text-main); margin: 0 0 8px; }

    .side-form p.form-desc { color: var(--text-sub); font-size: 13px; margin-bottom: 32px; }

    .alert { padding: 10px 14px; border-radius: 10px; font-size: 12px; font-weight: 600; margin-bottom: 20px; }
    .alert-err { background: #fff1f2; color: #be123c; border: 1px solid #ffe4e6; }
    .alert-ok { background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }

    .field-box { margin-bottom: 20px; }
    .field-box label { display: block; font-size: 12px; font-weight: 700; color: var(--text-main); margin-bottom: 6px; }
    .field-box input {
      width: 100%; padding: 12px 14px; border: 1.5px solid #f1f5f9;
      border-radius: 12px; font-size: 14px; box-sizing: border-box;
      transition: all 0.2s ease; background: #f8fafc;
    }

    .field-box input:focus { outline: none; border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.08); }

    .forgot-link { display: inline-block; margin-top: 8px; font-size: 11px; color: var(--primary); text-decoration: none; font-weight: 600; }

    .btn-submit {
      width: 100%; margin-top: 10px; padding: 14px; background: var(--primary);
      color: #fff; border: none; border-radius: 12px; font-size: 14px; font-weight: 700;
      cursor: pointer; transition: all 0.3s ease;
    }

    .btn-submit:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(29, 78, 216, 0.2); }

    .setup-info { margin-top: 24px; text-align: center; font-size: 12px; color: var(--text-sub); }
    .setup-info a { color: var(--primary); text-decoration: none; font-weight: 700; }

    .copyright { margin-top: auto; padding-top: 30px; font-size: 10px; color: #cbd5e1; text-align: center; }

    @media (max-width: 768px) {
      .login-wrapper { flex-direction: column; }
      .side-branding { flex: none; padding: 60px 24px; height: 35vh; }
      .side-form { border-left: none; padding: 40px 24px; }
      .form-container { max-width: 100%; }
    }
  </style>
</head>
<body>

  <div class="login-wrapper">
    <div class="side-branding">
      <div class="brand-content">
        <div class="logo-badge">SM</div>
        <h1>
          <span>SMP</span>
          <span>MUHAMMADIYAH 2</span>
          <span>KARANGLEWAS</span>
        </h1>
        <p class="subtitle">Administrator System</p>
      </div>
    </div>

    <div class="side-form">
      <div class="form-container">
        <h2>Sign In</h2>
        <p class="form-desc">Selamat datang kembali di panel kendali.</p>

        <!-- Flash Message Notification[cite: 1] -->
        <?php if ($error): ?>
          <div class="alert alert-err"><?= h($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
          <div class="alert alert-ok"><?= h($success) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= $base ?>/login">
          <div class="field-box">
            <label>KTA / Username</label>
            <input type="text" name="username" placeholder="Masukkan akun" required autofocus>
          </div>

          <div class="field-box">
            <label>Password</label>
            <input type="password" name="password" placeholder="••••••••" required>
            <a href="<?= $base ?>/forgot-password" class="forgot-link">Lupa kata sandi?</a>
          </div>

          <button type="submit" class="btn-submit">Masuk Sekarang</button>
        </form>

        <div class="setup-info">
          <!-- Link ke Halaman Daftar Admin[cite: 1] -->
          Baru di sini? <a href="<?= $base ?>/setup/create-admin">Daftar Akun</a>
        </div>
      </div>

      <div class="copyright">
        &copy; <?= date('Y') ?> SMP MUHAMMADIYAH 2 KARANGLEWAS
      </div>
    </div>
  </div>

</body>
</html>