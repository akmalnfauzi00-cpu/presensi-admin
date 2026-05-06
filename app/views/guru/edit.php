<?php
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$error = Session::pullFlash('error');

$jk = $row['jenis_kelamin'] ?? '';
$st = $row['status_aktif'] ?? 'AKTIF';
?>
<div class="container">
  <div class="section-title">
    <h3>Edit Guru</h3>
    <a class="btn" href="<?= $base ?>/guru">Kembali</a>
  </div>

  <?php if ($error): ?><div class="flash error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <form method="post" action="<?= $base ?>/guru/edit" enctype="multipart/form-data">
      <input type="hidden" name="id_guru" value="<?= htmlspecialchars($row['id_guru']) ?>">

      <div class="grid-2">

        <div>
          <label class="muted">Nama Guru</label>
          <input class="input" name="nama" value="<?= htmlspecialchars($row['nama_guru']) ?>" required>
        </div>

        <div>
          <label class="muted">KTA (Login Aplikasi) *</label>
          <input class="input" name="nip" value="<?= htmlspecialchars($row['nip'] ?? '') ?>" required>
          <div class="muted" style="margin-top:6px;">NIP dipakai untuk login aplikasi presensi.</div>
        </div>

        <div>
          <label class="muted">Password Baru (Login Aplikasi)</label>
          <input class="input" type="text" name="password" id="passInputEdit" 
                 placeholder="Kosongkan jika tidak diubah..." 
                 pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}"
                 title="Gagal! Password minimal 8 karakter, wajib ada huruf BESAR, huruf kecil, angka, dan simbol khusus (contoh: P@ssw0rd!).">
          <div class="muted" style="margin-top:6px; font-size:12px; color:#EF4444;">
            *Isi hanya jika ingin mengganti sandi lama. Wajib kuat (Huruf Besar, Kecil, Angka, Simbol).
          </div>
        </div>

        <div>
          <label class="muted">Mata Pelajaran</label>
          <input class="input" name="mata_pelajaran" value="<?= htmlspecialchars($row['mata_pelajaran'] ?? '') ?>">
        </div>

        <div>
          <label class="muted">Jabatan</label>
          <input class="input" name="jabatan" value="<?= htmlspecialchars($row['jabatan'] ?? '') ?>">
        </div>

        <div>
          <label class="muted">Jenis Kelamin</label>
          <select class="input" name="jenis_kelamin" required>
            <option value="">-- pilih --</option>
            <option value="Laki-laki" <?= $jk==='Laki-laki'?'selected':'' ?>>Laki-laki</option>
            <option value="Perempuan" <?= $jk==='Perempuan'?'selected':'' ?>>Perempuan</option>
          </select>
        </div>

        <div>
          <label class="muted">Status</label>
          <select class="input" name="status_aktif" required>
            <option value="AKTIF" <?= $st==='AKTIF'?'selected':'' ?>>AKTIF</option>
            <option value="CUTI" <?= $st==='CUTI'?'selected':'' ?>>CUTI</option>
            <option value="NONAKTIF" <?= $st==='NONAKTIF'?'selected':'' ?>>NONAKTIF</option>
          </select>
          <div class="muted" style="margin-top:6px;">
            Jika status NONAKTIF/CUTI, sebaiknya login aplikasi dibatasi.
          </div>
        </div>

        <div style="grid-column:1/-1;">
          <label class="muted">Alamat</label>
          <textarea class="input" name="alamat" rows="3"><?= htmlspecialchars($row['alamat'] ?? '') ?></textarea>
        </div>

        <div>
          <label class="muted">No HP</label>
          <input class="input" name="no_hp" value="<?= htmlspecialchars($row['no_hp'] ?? '') ?>">
        </div>

        <div>
          <label class="muted">Email</label>
          <input class="input" name="email" value="<?= htmlspecialchars($row['email'] ?? '') ?>">
        </div>

        <div style="grid-column:1/-1;">
          <label class="muted">Ganti Foto (opsional)</label>
          <input class="input" type="file" name="foto" accept="image/*">

          <?php if (!empty($row['foto'])): ?>
            <div class="muted" style="margin-top:10px;">
              Foto saat ini:
              <img src="<?= $base ?>/<?= htmlspecialchars($row['foto']) ?>"
                   style="width:70px;height:70px;border-radius:14px;object-fit:cover;vertical-align:middle;margin-left:8px;">
            </div>
          <?php endif; ?>
        </div>

      </div>

      <div style="margin-top:12px; display:flex; gap:10px;">
        <button class="btn primary" type="submit">Update</button>
        <a class="btn" href="<?= $base ?>/guru">Batal</a>
      </div>
    </form>
  </div>
</div>