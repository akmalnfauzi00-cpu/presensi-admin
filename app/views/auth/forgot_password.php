<?php
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
// Mendeteksi base path aplikasi
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base === '/') $base = '';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lupa Password - Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #2563eb;
      --primary-hover: #1d4ed8;
      --bg-canvas: #f8fafc;
      --text-main: #0f172a;
      --text-muted: #64748b;
      --glass-bg: rgba(255, 255, 255, 0.9);
      --glass-border: rgba(255, 255, 255, 0.4);
    }

    body {
      background: var(--bg-canvas);
      background-image: 
        radial-gradient(at 0% 0%, rgba(37, 99, 235, 0.1) 0px, transparent 50%),
        radial-gradient(at 100% 100%, rgba(37, 99, 235, 0.05) 0px, transparent 50%);
      margin: 0;
      font-family: 'Inter', sans-serif;
      color: var(--text-main);
    }

    .wrap {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .card {
      width: 100%;
      max-width: 420px;
      background: var(--glass-bg);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid var(--glass-border);
      border-radius: 24px;
      padding: 40px;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
      transition: transform 0.3s ease;
    }

    .title {
      margin: 0 0 10px 0;
      font-size: 26px;
      font-weight: 700;
      color: var(--text-main);
      letter-spacing: -0.025em;
    }

    .desc {
      margin: 0 0 32px 0;
      color: var(--text-muted);
      font-size: 15px;
      line-height: 1.6;
    }

    .lbl {
      display: block;
      margin-bottom: 8px;
      color: var(--text-main);
      font-size: 14px;
      font-weight: 600;
    }

    .in {
      width: 100%;
      padding: 14px 16px;
      background: #ffffff;
      border: 1.5px solid #e2e8f0;
      border-radius: 14px;
      outline: none;
      box-sizing: border-box;
      margin-bottom: 20px;
      font-size: 15px;
      font-family: inherit;
      transition: all 0.2s ease;
    }

    .in:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
    }

    .btn {
      width: 100%;
      padding: 14px;
      border-radius: 14px;
      border: none;
      background: var(--primary);
      color: #ffffff;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
    }

    .btn:hover {
      background: var(--primary-hover);
      transform: translateY(-1px);
      box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
    }

    .btn:active {
      transform: translateY(0);
    }

    .btn:disabled {
      background: #94a3b8;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    .back {
      display: block;
      margin-top: 24px;
      text-align: center;
      font-size: 14px;
      color: var(--primary);
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s ease;
    }

    .back:hover {
      color: var(--primary-hover);
      text-decoration: underline;
    }

    /* Animasi sederhana untuk transisi step */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .step-content {
      animation: fadeIn 0.4s ease-out;
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div id="step1" class="step-content">
        <h1 class="title">Lupa Password</h1>
        <p class="desc">Masukkan NIP Anda. Kami akan mengirimkan kode verifikasi OTP ke email yang terdaftar.</p>
        
        <label class="lbl">KTA Pegawai</label>
        <input type="text" id="nip" class="in" placeholder="Contoh: 2203040001" spellcheck="false">
        
        <button class="btn" onclick="requestOTP()" id="btn1">Kirim Kode OTP</button>
      </div>

      <div id="step2" class="step-content" style="display:none">
        <h1 class="title">Verifikasi OTP</h1>
        <p class="desc">Kode OTP telah dikirim. Silakan masukkan kode tersebut beserta password baru Anda.</p>
        
        <label class="lbl">Kode OTP</label>
        <input type="text" id="otp" class="in" placeholder="6-digit kode" maxlength="6" style="text-align:center; letter-spacing: 0.5em; font-weight: 700;">
        
        <label class="lbl">Password Baru</label>
        <input type="password" id="new_pass" class="in" placeholder="Minimal 8 karakter">
        
        <button class="btn" onclick="resetPassword()" id="btn2">Perbarui Password</button>
      </div>

      <a href="<?= $base ?>/login" class="back">Kembali ke halaman Login</a>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    const BASE_URL = window.location.origin + "<?= $base ?>";
    const API = BASE_URL + "/api";

    // Toast configuration
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    });

    async function requestOTP() {
      const nip = document.getElementById('nip').value;
      const btn = document.getElementById('btn1');
      if(!nip) return Swal.fire({ icon: 'warning', title: 'Opps!', text: 'NIP wajib diisi', borderRadius: '15px' });

      btn.disabled = true;
      btn.innerText = 'Memproses...';

      try {
        const res = await fetch(`${API}/forgot-password`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({nip: nip})
        });
        
        const data = await res.json();
        
        if(res.ok) {
          Toast.fire({ icon: 'success', title: 'Kode OTP berhasil dikirim' });
          document.getElementById('step1').style.display = 'none';
          document.getElementById('step2').style.display = 'block';
        } else {
          Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'NIP tidak ditemukan' });
        }
      } catch(e) { 
        Swal.fire({ icon: 'error', title: 'Koneksi Gagal', text: 'Gagal menghubungi server.' }); 
      } finally {
        btn.disabled = false;
        btn.innerText = 'Kirim Kode OTP';
      }
    }

    async function resetPassword() {
      const nip = document.getElementById('nip').value;
      const otp = document.getElementById('otp').value;
      const password = document.getElementById('new_pass').value;
      const btn = document.getElementById('btn2');

      if(!otp || !password) return Swal.fire({ icon: 'warning', text: 'Semua kolom wajib diisi' });
      if(password.length < 8) return Swal.fire({ icon: 'info', text: 'Password minimal 8 karakter' });

      btn.disabled = true;
      btn.innerText = 'Memperbarui...';

      try {
        const res = await fetch(`${API}/reset-password`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ nip, otp, password })
        });
        
        const data = await res.json();
        
        if(res.ok) {
          Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Password Anda telah diperbarui.',
            confirmButtonColor: '#2563eb'
          }).then(() => {
            window.location.href = BASE_URL + "/login";
          });
        } else {
          Swal.fire({ icon: 'error', text: data.message || 'Kode OTP salah atau kadaluarsa' });
        }
      } catch(e) { 
        Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal memproses data.' }); 
      } finally {
        btn.disabled = false;
        btn.innerText = 'Perbarui Password';
      }
    }
  </script>
</body>
</html>