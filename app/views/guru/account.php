<?php
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

$success = Session::pullFlash('success');
$error   = Session::pullFlash('error');

$g = $guru ?? []; // dari controller
?>

<div style="padding:18px; max-width:900px;">
  <h2 style="margin:0 0 6px; font-size:28px; font-weight:900;">🔐 Akun Presensi Guru</h2>
  <div style="color:#6b7280; margin-bottom:14px;">
    Buat / reset akun untuk login aplikasi presensi (NIP + Password).
  </div>

  <?php if ($success): ?>
    <div style="padding:12px 14px;border-radius:12px;border:1px solid #bbf7d0;background:#ecfdf5;color:#065f46;margin-bottom:12px;">
      <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div style="padding:12px 14px;border-radius:12px;border:1px solid #fecaca;background:#fef2f2;color:#991b1b;margin-bottom:12px;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:14px;">
    <div style="font-weight:900; margin-bottom:10px;">
      Guru: <?= htmlspecialchars($g['nama_guru'] ?? '-') ?>
    </div>

    <form method="post" action="<?= $base ?>/guru/account">
      <input type="hidden" name="id_guru" value="<?= htmlspecialchars($g['id_guru'] ?? '') ?>">

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div>
          <label style="font-weight:800;">NIP (untuk login)</label>
          <input
            name="nip"
            value="<?= htmlspecialchars($g['nip'] ?? '') ?>"
            placeholder="Masukkan NIP"
            style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid #e5e7eb;outline:none;"
          >
          <div style="font-size:12px;color:#6b7280;margin-top:6px;">
            Login aplikasi hanya pakai NIP (sesuai request kamu).
          </div>
        </div>

        <div>
          <label style="font-weight:800;">Password (bebas)</label>
          <input
            name="password"
            type="text"
            placeholder="Contoh: 123456"
            style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid #e5e7eb;outline:none;"
          >
          <div style="font-size:12px;color:#6b7280;margin-top:6px;">
            Kosongkan jika tidak ingin mengubah password.
          </div>
        </div>
      </div>

      <div style="display:flex; gap:10px; margin-top:14px;">
        <button type="submit"
          style="padding:10px 14px;border-radius:12px;border:0;background:#1d4ed8;color:#fff;font-weight:900;cursor:pointer;">
          Simpan Akun
        </button>

        <a href="<?= $base ?>/guru"
          style="padding:10px 14px;border-radius:12px;border:1px solid #e5e7eb;background:#fff;font-weight:900;text-decoration:none;color:#111827;">
          Kembali
        </a>
      </div>
    </form>
  </div>
</div>