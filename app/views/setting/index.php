<?php
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$tab = $tab ?? ($_GET['tab'] ?? 'jam');

// PERBAIKAN: Gunakan Null Coalescing agar tidak Undefined
$lat = ($data['latitude'] ?? null) === null ? '' : $data['latitude'];
$lng = ($data['longitude'] ?? null) === null ? '' : $data['longitude'];
$radius = (int)($data['radius_meter'] ?? 150);

$defaultLat = ($lat === '' ? -6.2088 : (float)$lat);
$defaultLng = ($lng === '' ? 106.8456 : (float)$lng);
$base = $base ?? '';

// Flash message untuk notifikasi pembaruan
$success = Session::pullFlash('success');
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
    /* Global & Estetika */
    .setting-wrap { position: relative; padding: 30px; min-height: 100vh; background: #f8fafc; font-family: 'Inter', sans-serif; overflow: hidden; }
    
    /* Background shapes agar estetik */
    .bg-shape { position: absolute; border-radius: 50%; filter: blur(80px); z-index: 0; opacity: 0.5; }
    .shape-1 { width: 300px; height: 300px; background: rgba(37, 99, 235, 0.1); top: -50px; right: -50px; }
    .shape-2 { width: 250px; height: 250px; background: rgba(139, 92, 246, 0.1); bottom: -20px; left: -20px; }

    .setting-container { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; }

    .header-area { margin-bottom: 30px; }
    .header-area h1 { font-size: 28px; font-weight: 800; color: #1e293b; margin: 0; letter-spacing: -0.5px; }
    .muted { color: #64748b; font-size: 14px; margin-top: 6px; }

    /* Flash Message Modern */
    .alert-success { 
        background: #ecfdf5; color: #065f46; padding: 16px 20px; border-radius: 14px; 
        margin-bottom: 25px; border: 1px solid #bbf7d0; font-weight: 600; 
        display: flex; align-items: center; gap: 10px; animation: slideDown 0.3s ease;
    }
    @keyframes slideDown { from { transform: translateY(-10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    /* Navigasi Tab Modern */
    .tabs { display: flex; gap: 8px; margin-bottom: 25px; flex-wrap: wrap; }
    .tab { 
        padding: 12px 20px; border-radius: 12px; text-decoration: none; color: #64748b; 
        background: #fff; border: 1px solid #e2e8f0; font-weight: 600; font-size: 14px; 
        transition: 0.3s;
    }
    .tab.active { background: #1d4ed8; color: #fff; border-color: #1d4ed8; box-shadow: 0 4px 12px rgba(29, 78, 216, 0.2); }
    .tab:hover:not(.active) { background: #f1f5f9; }

    /* Card UI */
    .card { background: rgba(255, 255, 255, 0.95); border: 1px solid #f1f5f9; border-radius: 24px; padding: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); margin-bottom: 20px; }
    .card h2 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0 0 20px; }

    /* Form & Input */
    .row { display: flex; gap: 20px; flex-wrap: wrap; }
    .col { flex: 1; min-width: 250px; }
    label { display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
    input, select { 
        width: 100%; padding: 13px 16px; border: 1.5px solid #e2e8f0; border-radius: 14px; 
        font-size: 14px; outline: none; transition: 0.2s; background: #fcfcfc; font-family: inherit;
    }
    input:focus, select:focus { border-color: #1d4ed8; background: #fff; box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.08); }
    
    /* Buttons */
    .actions { display: flex; justify-content: flex-end; margin-top: 25px; }
    button { 
        padding: 14px 24px; border: 0; border-radius: 12px; background: #1d4ed8; color: #fff; 
        cursor: pointer; font-weight: 700; font-size: 14px; transition: 0.3s;
    }
    button:hover { background: #1e3a8a; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(29, 78, 216, 0.2); }
    .btn-danger { background: #fee2e2; color: #ef4444; border: 1px solid #fecaca; font-size: 12px; padding: 8px 12px; }
    .btn-danger:hover { background: #ef4444; color: #fff; }

    /* Map & Radius */
    #map { height: 400px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); }
    .radiusBox { margin-top: 20px; padding: 24px; border-radius: 20px; background: #f8fafc; border: 1px solid #e2e8f0; }
    
    /* Input Group */
    .input-group { display: flex; align-items: center; }
    .input-group input { border-top-right-radius: 0; border-bottom-right-radius: 0; }
    .input-group-text { padding: 13px 16px; background: #f1f5f9; border: 1.5px solid #e2e8f0; border-left: 0; border-top-right-radius: 14px; border-bottom-right-radius: 14px; color: #64748b; font-size: 14px; font-weight: 700; }

    /* Table */
    .modern-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .modern-table th { text-align: left; padding: 15px; font-size: 12px; color: #94a3b8; text-transform: uppercase; border-bottom: 1px solid #f1f5f9; }
    .modern-table td { padding: 18px 15px; border-bottom: 1px solid #f9f9f9; font-size: 14px; }
</style>

<div class="setting-wrap">
    <div class="bg-shape shape-1"></div>
    <div class="bg-shape shape-2"></div>

    <div class="setting-container">
        <div class="header-area">
            <h1>Pengaturan Sistem</h1>
            <div class="muted">Konfigurasi operasional, kebijakan reward, dan parameter lokasi sekolah.</div>
        </div>

        <?php if ($success): ?>
            <div class="alert-success">
                <span>✨</span> <?= htmlspecialchars($success) ?> (Data sudah diperbarui)
            </div>
        <?php endif; ?>

        <div class="tabs">
            <a class="tab <?= $tab==='jam'?'active':'' ?>" href="<?= $base ?>/setting?tab=jam">Jam Kerja</a>
            <a class="tab <?= $tab==='reward'?'active':'' ?>" href="<?= $base ?>/setting?tab=reward">Reward &amp; SP</a>
            <a class="tab <?= $tab==='libur'?'active':'' ?>" href="<?= $base ?>/setting?tab=libur">Hari Libur</a>
            <a class="tab <?= $tab==='lokasi'?'active':'' ?>" href="<?= $base ?>/setting?tab=lokasi">Lokasi Sekolah</a>
            <a class="tab <?= $tab==='batas'?'active':'' ?>" href="<?= $base ?>/setting?tab=batas">Batas Pengajuan</a>
        </div>

        <?php if ($tab === 'jam'): ?>
            <div class="card">
                <h2>Konfigurasi Waktu Kerja</h2>
                <form method="POST" action="<?= $base ?>/setting/save-jam">
                    <div class="row">
                        <div class="col">
                            <label>Jam Masuk</label>
                            <input type="time" name="jam_masuk" value="<?= h(substr((string)($data['jam_masuk']??'07:00'), 0, 5)) ?>" required>
                        </div>
                        <div class="col">
                            <label>Jam Pulang</label>
                            <input type="time" name="jam_pulang" value="<?= h(substr((string)($data['jam_pulang']??'15:30'), 0, 5)) ?>" required>
                        </div>
                        <div class="col">
                            <label>Toleransi (Menit)</label>
                            <input type="number" name="toleransi_terlambat" value="<?= h($data['toleransi_terlambat']??0) ?>" required>
                        </div>
                    </div>
                    <div class="actions"><button type="submit">Update Jam Kerja</button></div>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'reward'): ?>
            <div class="card">
                <h2>Ambang Batas Reward & SP</h2>
                <form method="POST" action="<?= $base ?>/setting/save-reward">
                    <div class="row">
                        <div class="col">
                            <label>Min. Hadir (Reward)</label>
                            <input type="number" name="minimal_hadir_reward" value="<?= h($data['minimal_hadir_reward'] ?? 0) ?>">
                            <div class="muted">Syarat kehadiran guru untuk klaim reward bulanan.</div>
                        </div>
                        <div class="col">
                            <label>Maks. Alpha (SP)</label>
                            <input type="number" name="minimal_tidak_hadir_sp" value="<?= h($data['minimal_tidak_hadir_sp'] ?? 3) ?>">
                            <div class="muted">Otomatisasi SP jika jumlah Alpha tercapai.</div>
                        </div>
                        <div class="col">
                            <label>Maks. Telat (SP)</label>
                            <input type="number" name="maksimal_terlambat_sp" value="<?= h($data['maksimal_terlambat_sp'] ?? 3) ?>">
                            <div class="muted">Batas keterlambatan sebelum tindakan disiplin.</div>
                        </div>
                    </div>
                    <div class="actions"><button type="submit">Update Kebijakan</button></div>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'libur'): ?>
            <div class="card">
                <h2>Kalender Hari Libur</h2>
                <form method="POST" action="<?= $base ?>/setting/save-libur" style="background:#f1f5f9; padding:25px; border-radius:20px; margin-bottom:30px;">
                    <div class="row" style="align-items: flex-end;">
                        <div class="col" style="flex: 1;"><label>Tanggal</label><input type="date" name="tanggal" required></div>
                        <div class="col" style="flex: 2;"><label>Keterangan Libur</label><input type="text" name="keterangan" placeholder="Contoh: Idul Fitri" required></div>
                        <button type="submit" style="height: 48px;">+ Tambah</button>
                    </div>
                </form>
                <table class="modern-table">
                    <thead><tr><th>Tanggal</th><th>Keterangan</th><th style="text-align:right;">Opsi</th></tr></thead>
                    <tbody>
                        <?php if(empty($libur)): ?><tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:40px;">Tidak ada hari libur terjadwal.</td></tr>
                        <?php else: foreach($libur as $l): ?>
                            <tr>
                                <td style="font-weight:700; color:#1e293b;"><?= date('d M Y', strtotime($l['tanggal'])) ?></td>
                                <td><?= h($l['keterangan']) ?></td>
                                <td style="text-align:right;">
                                    <form method="POST" action="<?= $base ?>/setting/delete-libur">
                                        <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                        <button type="submit" class="btn-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'lokasi'): ?>
            <div class="card">
                <h2>Titik Presensi & Radius</h2>
                <div id="map"></div>
                <div class="radiusBox">
                    <form method="POST" action="<?= $base ?>/setting/save-lokasi">
                        <div class="row">
                            <div class="col"><label>Latitude</label><input type="text" name="latitude" id="latitude" value="<?= h($lat) ?>" readonly style="background:#f8fafc; color:#64748b;"></div>
                            <div class="col"><label>Longitude</label><input type="text" name="longitude" id="longitude" value="<?= h($lng) ?>" readonly style="background:#f8fafc; color:#64748b;"></div>
                            <div class="col">
                                <label>Jangkauan (Meter)</label>
                                <input type="range" id="radiusRange" min="10" max="1000" step="10" value="<?= h($radius) ?>">
                                <div style="margin-top:12px; font-weight:800; color:#1d4ed8; font-size:18px;"><span id="radiusLabel"><?= h($radius) ?></span> m</div>
                                <input type="hidden" name="radius_meter" id="radius_meter" value="<?= h($radius) ?>">
                            </div>
                        </div>
                        <div class="actions"><button type="submit">Simpan Lokasi</button></div>
                    </form>
                </div>
            </div>
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <script>
                (function(){
                    const initLat = <?= json_encode($defaultLat) ?>;
                    const initLng = <?= json_encode($defaultLng) ?>;
                    const latInput = document.getElementById('latitude'), lngInput = document.getElementById('longitude'), 
                          radiusRange = document.getElementById('radiusRange'), radiusHidden = document.getElementById('radius_meter'), radiusLabel = document.getElementById('radiusLabel');
                    let radius = parseInt(radiusRange.value || "150", 10);
                    const map = L.map('map').setView([initLat, initLng], 16);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                    let marker = null, circle = null;
                    function setRadius(r){ radius = r; radiusHidden.value = String(r); radiusLabel.textContent = String(r); if (circle) circle.setRadius(r); }
                    function setPoint(lat, lng){
                        latInput.value = lat.toFixed(7); lngInput.value = lng.toFixed(7);
                        if (!marker) marker = L.marker([lat, lng]).addTo(map); else marker.setLatLng([lat, lng]);
                        if (!circle) circle = L.circle([lat, lng], { radius, color: '#1d4ed8', fillOpacity: 0.15 }).addTo(map); else { circle.setLatLng([lat, lng]); circle.setRadius(radius); }
                    }
                    if (latInput.value && lngInput.value) { setPoint(parseFloat(latInput.value), parseFloat(lngInput.value)); map.setView([parseFloat(latInput.value), parseFloat(lngInput.value)], 17); }
                    map.on('click', (e) => setPoint(e.latlng.lat, e.latlng.lng));
                    radiusRange.addEventListener('input', function(){ setRadius(parseInt(this.value, 10)); });
                    setRadius(radius);
                })();
            </script>
        <?php endif; ?>

        <?php if ($tab === 'batas'): ?>
            <div class="card">
                <h2>Limitasi Pengajuan Guru</h2>
                <form method="POST" action="<?= $base ?>/setting/save-batas">
                    <div class="row">
                        <div class="col">
                            <label>Kuota Izin Per Bulan</label>
                            <div class="input-group">
                                <input type="number" name="maks_izin" value="<?= h($data['maks_izin'] ?? 3) ?>" required>
                                <span class="input-group-text">Kali</span>
                            </div>
                            <div class="muted">Limit maksimal pengajuan izin/sakit dalam satu periode bulan.</div>
                        </div>
                        <div class="col">
                            <label>Validasi Pengajuan Pending</label>
                            <select name="block_pending">
                                <option value="1" <?= ($data['block_pending'] ?? 1) == 1 ? 'selected' : '' ?>>Ya, Blokir Jika Ada Pending</option>
                                <option value="0" <?= ($data['block_pending'] ?? 1) == 0 ? 'selected' : '' ?>>Bolehkan Pengajuan Ganda</option>
                            </select>
                            <div class="muted">Mencegah guru mengirim izin baru sebelum admin memproses pengajuan sebelumnya.</div>
                        </div>
                    </div>
                    <div class="actions"><button type="submit">Update Batasan</button></div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>