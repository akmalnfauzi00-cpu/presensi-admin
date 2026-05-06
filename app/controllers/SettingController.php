<?php

class SettingController
{
    private function base(): string {
        $b = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        return $b === '/' ? '' : $b;
    }

    private function uuid(): string {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function getConfig(PDO $db): array {
        $st = $db->query("SELECT * FROM presensi_master ORDER BY created_at DESC LIMIT 1");
        $cfg = $st->fetch(PDO::FETCH_ASSOC);
        if ($cfg) return $cfg;

        return [
            'id_master' => null,
            'jam_masuk' => '07:00:00',
            'jam_pulang' => '15:30:00',
            'batas_terlambat' => '08:15:00',
            'toleransi_terlambat' => 0,
            'minimal_hadir_reward' => 22,
            'minimal_tidak_hadir_sp' => 3,
            'maksimal_terlambat_sp' => 3,
            'maks_izin' => 3,
            'block_pending' => 1,
            'latitude' => null,
            'longitude' => null,
            'radius_meter' => 150,
        ];
    }

    private function upsert(PDO $db, array $partial): void {
        $cfg = $this->getConfig($db);
        $new = array_merge($cfg, $partial);

        if (empty($cfg['id_master'])) {
            $id = $this->uuid();
            $sql = "INSERT INTO presensi_master
                (id_master, jam_masuk, jam_pulang, batas_terlambat, toleransi_terlambat,
                 minimal_hadir_reward, minimal_tidak_hadir_sp, maksimal_terlambat_sp, 
                 maks_izin, block_pending,
                 latitude, longitude, radius_meter)
                VALUES
                (:id_master, :jam_masuk, :jam_pulang, :batas_terlambat, :toleransi_terlambat,
                 :minimal_hadir_reward, :minimal_tidak_hadir_sp, :maksimal_terlambat_sp, 
                 :maks_izin, :block_pending,
                 :latitude, :longitude, :radius_meter)";

            $st = $db->prepare($sql);
            $st->execute([
                ':id_master' => $id,
                ':jam_masuk' => $new['jam_masuk'],
                ':jam_pulang' => $new['jam_pulang'],
                ':batas_terlambat' => $new['batas_terlambat'],
                ':toleransi_terlambat' => (int)$new['toleransi_terlambat'],
                ':minimal_hadir_reward' => (int)$new['minimal_hadir_reward'],
                ':minimal_tidak_hadir_sp' => (int)$new['minimal_tidak_hadir_sp'],
                ':maksimal_terlambat_sp' => (int)($new['maksimal_terlambat_sp'] ?? 3),
                ':maks_izin' => (int)($new['maks_izin'] ?? 3),
                ':block_pending' => (int)($new['block_pending'] ?? 1),
                ':latitude' => $new['latitude'],
                ':longitude' => $new['longitude'],
                ':radius_meter' => (int)$new['radius_meter'],
            ]);
            return;
        }

        $sql = "UPDATE presensi_master SET
            jam_masuk=:jam_masuk, jam_pulang=:jam_pulang, batas_terlambat=:batas_terlambat,
            toleransi_terlambat=:toleransi_terlambat, minimal_hadir_reward=:minimal_hadir_reward,
            minimal_tidak_hadir_sp=:minimal_tidak_hadir_sp, maksimal_terlambat_sp=:maksimal_terlambat_sp,
            maks_izin=:maks_izin, block_pending=:block_pending,
            latitude=:latitude, longitude=:longitude, radius_meter=:radius_meter, updated_at=NOW()
            WHERE id_master=:id_master";

        $st = $db->prepare($sql);
        $st->execute([
            ':id_master' => $cfg['id_master'],
            ':jam_masuk' => $new['jam_masuk'],
            ':jam_pulang' => $new['jam_pulang'],
            ':batas_terlambat' => $new['batas_terlambat'],
            ':toleransi_terlambat' => (int)$new['toleransi_terlambat'],
            ':minimal_hadir_reward' => (int)$new['minimal_hadir_reward'],
            ':minimal_tidak_hadir_sp' => (int)$new['minimal_tidak_hadir_sp'],
            ':maksimal_terlambat_sp' => (int)($new['maksimal_terlambat_sp'] ?? 3),
            ':maks_izin' => (int)($new['maks_izin'] ?? 3),
            ':block_pending' => (int)($new['block_pending'] ?? 1),
            ':latitude' => $new['latitude'],
            ':longitude' => $new['longitude'],
            ':radius_meter' => (int)$new['radius_meter'],
        ]);
    }

    public function index($req, $res) {
        $pdo = Db::pdo();
        $data = $this->getConfig($pdo); 
        $tab = $_GET['tab'] ?? 'jam';
        $base = $this->base();

        $libur = [];
        if ($tab === 'libur') {
            $st = $pdo->query("SELECT * FROM hari_libur ORDER BY tanggal DESC");
            $libur = $st->fetchAll(PDO::FETCH_ASSOC);
        }

        $pageTitle = 'Pengaturan Sistem';
        $contentFile = __DIR__ . '/../views/setting/index.php';
        $layoutFile  = __DIR__ . '/../views/layouts/admin.php';

        require $layoutFile;
    }

    public function saveJam($req, $res) {
        $pdo = Db::pdo();
        $base = $this->base();
        $jam_masuk = trim($_POST['jam_masuk'] ?? '07:00');
        $jam_pulang = trim($_POST['jam_pulang'] ?? '15:30');
        $toleransi = (int)($_POST['toleransi_terlambat'] ?? 0);

        $dt = DateTime::createFromFormat('H:i', $jam_masuk);
        if ($dt) {
            $dt->modify("+{$toleransi} minutes");
            $batas = $dt->format('H:i:s');
        } else {
            $batas = $jam_masuk . ':00';
        }

        $this->upsert($pdo, [
            'jam_masuk' => $jam_masuk . ':00',
            'jam_pulang' => $jam_pulang . ':00',
            'toleransi_terlambat' => $toleransi,
            'batas_terlambat' => $batas,
        ]);

        Session::flash('success', 'Konfigurasi jam kerja berhasil diperbarui.');
        header("Location: {$base}/setting?tab=jam");
        exit;
    }

    public function saveReward($req, $res) {
        $pdo = Db::pdo();
        $base = $this->base();
        $this->upsert($pdo, [
            'minimal_hadir_reward' => max(0, (int)$_POST['minimal_hadir_reward']),
            'minimal_tidak_hadir_sp' => max(0, (int)$_POST['minimal_tidak_hadir_sp']),
            'maksimal_terlambat_sp' => max(0, (int)$_POST['maksimal_terlambat_sp']),
        ]);
        
        Session::flash('success', 'Aturan Reward & SP berhasil diperbarui.');
        header("Location: {$base}/setting?tab=reward");
        exit;
    }

    public function saveLokasi($req, $res) {
        $pdo = Db::pdo();
        $base = $this->base();
        $this->upsert($pdo, [
            'latitude' => $_POST['latitude'] ? (float)$_POST['latitude'] : null,
            'longitude' => $_POST['longitude'] ? (float)$_POST['longitude'] : null,
            'radius_meter' => max(10, (int)$_POST['radius_meter']),
        ]);

        Session::flash('success', 'Lokasi presensi sekolah berhasil diperbarui.');
        header("Location: {$base}/setting?tab=lokasi");
        exit;
    }

    public function saveBatas($req, $res) {
        $pdo = Db::pdo();
        $base = $this->base();
        $this->upsert($pdo, [
            'maks_izin' => max(1, (int)$_POST['maks_izin']),
            'block_pending' => (int)$_POST['block_pending'],
        ]);

        Session::flash('success', 'Batasan pengajuan izin berhasil diperbarui.');
        header("Location: {$base}/setting?tab=batas");
        exit;
    }

    public function saveLibur($req, $res) {
        $pdo = Db::pdo();
        $base = $this->base();
        if ($_POST['tanggal'] && $_POST['keterangan']) {
            $st = $pdo->prepare("INSERT INTO hari_libur (tanggal, keterangan) VALUES (?, ?)");
            $st->execute([$_POST['tanggal'], $_POST['keterangan']]);
            Session::flash('success', 'Hari libur baru berhasil ditambahkan.');
        }
        header("Location: {$base}/setting?tab=libur");
        exit;
    }

    public function deleteLibur($req, $res) {
        $pdo = Db::pdo();
        $base = $this->base();
        if ((int)$_POST['id'] > 0) {
            $st = $pdo->prepare("DELETE FROM hari_libur WHERE id = ?");
            $st->execute([(int)$_POST['id']]);
            Session::flash('success', 'Hari libur berhasil dihapus.');
        }
        header("Location: {$base}/setting?tab=libur");
        exit;
    }
}