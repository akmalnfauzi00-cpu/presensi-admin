<?php
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<div class="guru-form-page">
  <div class="guru-form-head">
    <h2>Edit User Admin</h2>
    <p>Perbarui data akun admin dashboard.</p>
  </div>

  <div class="guru-form-card">
    <form method="post" action="<?= $base ?>/users/edit" class="guru-form-grid">
      <input type="hidden" name="id_user" value="<?= htmlspecialchars($row['id_user']) ?>">

      <div class="guru-form-group">
        <label>Nama</label>
        <input type="text" name="nama" value="<?= htmlspecialchars($row['nama'] ?? '') ?>" required>
      </div>

      <div class="guru-form-group">
        <label>Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($row['username'] ?? '') ?>" required>
      </div>

      <div class="guru-form-group">
        <label>Password Baru</label>
        <input type="password" name="password" placeholder="Kosongkan jika tidak ingin diubah">
      </div>

      <div class="guru-form-group">
        <label>Role</label>
        <select name="role" required>
          <option value="admin" <?= (($row['role'] ?? 'admin') === 'admin') ? 'selected' : '' ?>>Admin</option>
          <option value="superadmin" <?= (($row['role'] ?? '') === 'superadmin') ? 'selected' : '' ?>>Superadmin</option>
        </select>
      </div>

      <div class="guru-form-group">
        <label>Status Aktif</label>
        <select name="status_aktif" required>
          <option value="1" <?= ((int)($row['status_aktif'] ?? 1) === 1) ? 'selected' : '' ?>>Aktif</option>
          <option value="0" <?= ((int)($row['status_aktif'] ?? 1) === 0) ? 'selected' : '' ?>>Nonaktif</option>
        </select>
      </div>

      <div class="guru-form-actions">
        <a href="<?= $base ?>/users" class="btn-back">← Kembali</a>
        <button type="submit" class="btn-save">Update</button>
      </div>
    </form>
  </div>
</div>

<style>
  .guru-form-page{
    padding:18px;
    width:100%;
    max-width:none;
    box-sizing:border-box;
  }

  .guru-form-head h2{
    margin:0 0 6px;
    font-size:30px;
    font-weight:900;
  }

  .guru-form-head p{
    margin:0 0 18px;
    color:#6b7280;
  }

  .guru-form-card{
    width:100%;
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:18px;
    padding:20px;
    box-sizing:border-box;
  }

  .guru-form-grid{
    display:grid;
    grid-template-columns:repeat(2, minmax(320px, 1fr));
    gap:16px;
    width:100%;
  }

  .guru-form-group{
    display:flex;
    flex-direction:column;
    gap:8px;
  }

  .guru-form-group label{
    font-weight:800;
    color:#111827;
  }

  .guru-form-group input,
  .guru-form-group select{
    width:100%;
    height:46px;
    border:1px solid #d1d5db;
    border-radius:12px;
    padding:0 14px;
    font-size:14px;
    outline:none;
    background:#fff;
    box-sizing:border-box;
  }

  .guru-form-group input:focus,
  .guru-form-group select:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 3px rgba(37,99,235,.12);
  }

  .guru-form-actions{
    grid-column:1 / -1;
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:8px;
  }

  .btn-back{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:10px 14px;
    border-radius:12px;
    text-decoration:none;
    background:#e5e7eb;
    color:#111827;
    font-weight:800;
  }

  .btn-save{
    border:0;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:10px 18px;
    border-radius:12px;
    background:#1d4ed8;
    color:#fff;
    font-weight:900;
  }

  .btn-save:hover,
  .btn-back:hover{
    filter:brightness(.97);
  }

  @media (max-width: 720px){
    .guru-form-grid{
      grid-template-columns:1fr;
    }
  }
</style>