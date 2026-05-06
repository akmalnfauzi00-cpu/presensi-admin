<?php
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$error = Session::pullFlash('error');
?>
<div class="container">
  <div class="section-title">
    <h3>Tambah Guru</h3>
    <a class="btn" href="<?= $base ?>/guru">Kembali</a>
  </div>

  <?php if ($error): ?><div class="flash error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <form method="post" action="<?= $base ?>/guru/create" enctype="multipart/form-data">
      <div class="grid-2">

        <div>
          <label class="muted">Nama Guru</label>
          <input class="input" name="nama" required>
        </div>

        <div>
          <label class="muted">KTA (Login Aplikasi) *</label>
          <input class="input" name="nip" required placeholder="Wajib untuk login aplikasi">
        </div>

        <div>
          <label class="muted">Password (Login Aplikasi)</label>
          <input class="input" type="text" name="password" id="passInputTambah" 
                 placeholder="Wajib unik & kuat..." 
                 pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}"
                 title="Gagal! Password minimal 8 karakter, wajib ada huruf BESAR, huruf kecil, angka, dan simbol khusus (contoh: P@ssw0rd!)."
                 required>
          <div class="muted" style="margin-top:6px; font-size:12px; color:#EF4444;">
            *Min. 8 karakter. Wajib kombinasi: Huruf Besar, Kecil, Angka & Simbol.
          </div>
        </div>

        <div>
          <label class="muted">Mata Pelajaran</label>
          <input class="input" name="mata_pelajaran" placeholder="Opsional">
        </div>

        <div>
          <label class="muted">Jabatan</label>
          <input class="input" name="jabatan" placeholder="Opsional">
        </div>

        <div>
          <label class="muted">Jenis Kelamin</label>
          <select class="input" name="jenis_kelamin" required>
            <option value="">-- pilih --</option>
            <option value="Laki-laki">Laki-laki</option>
            <option value="Perempuan">Perempuan</option>
          </select>
        </div>

        <div>
          <label class="muted">Status</label>
          <select class="input" name="status_aktif" required>
            <option value="AKTIF">AKTIF</option>
            <option value="CUTI">CUTI</option>
            <option value="NONAKTIF">NONAKTIF</option>
          </select>
        </div>

        <div style="grid-column:1/-1;">
          <label class="muted">Alamat</label>
          <textarea class="input" name="alamat" rows="3" placeholder="Opsional"></textarea>
        </div>

        <div>
          <label class="muted">No HP</label>
          <input class="input" name="no_hp" placeholder="Opsional">
        </div>

        <div>
          <label class="muted">Email</label>
          <input class="input" name="email" placeholder="Opsional">
        </div>

        <div style="grid-column:1/-1;">
          <label class="muted">Foto (JPG/PNG/WebP)</label>
          <input class="input" type="file" name="foto" accept="image/*">
        </div>

      </div>

      <div style="margin-top:12px;">
        <button class="btn primary" type="submit">Simpan</button>
      </div>
    </form>
  </div>
</div>