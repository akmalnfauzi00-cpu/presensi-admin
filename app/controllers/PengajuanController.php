<?php

class PengajuanController
{
    private function base(): string {
        $b = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        return $b === '/' ? '' : $b;
    }

    private function uuid(): string {
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
    }

    public function index($req, $res): void {
        $pdo = Db::pdo();
        $stmt = $pdo->query("
            SELECT p.*, g.nama_guru, g.nip 
            FROM pengajuan_presensi p 
            JOIN guru g ON g.id_guru = p.id_guru 
            ORDER BY 
                CASE p.status_verifikasi 
                    WHEN 'MENUNGGU' THEN 1 
                    ELSE 2 
                END, 
            p.created_at DESC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $pageTitle = 'Daftar Pengajuan';
        $contentFile = __DIR__ . '/../views/pengajuan/index.php';
        require __DIR__ . '/../views/layouts/admin.php';
    }

    public function verifikasi($req, $res): void {
        $pdo = Db::pdo();
        $id = $_POST['id_pengajuan'] ?? '';
        $aksi = strtoupper($_POST['aksi'] ?? '');
        $catatan = $_POST['catatan_admin'] ?? '';

        $st = $pdo->prepare("SELECT * FROM pengajuan_presensi WHERE id_pengajuan = ?");
        $st->execute([$id]);
        $p = $st->fetch(PDO::FETCH_ASSOC);

        if (!$p) {
            $_SESSION['flash_error'] = 'Data tidak ditemukan';
            header('Location: ' . $this->base() . '/pengajuan'); exit;
        }

        try {
            $pdo->beginTransaction();
            $status = ($aksi === 'SETUJUI') ? 'DISETUJUI' : 'DITOLAK';
            
            $pdo->prepare("UPDATE pengajuan_presensi SET status_verifikasi=?, catatan_admin=?, updated_at=NOW() WHERE id_pengajuan=?")
                ->execute([$status, $catatan, $id]);

            if ($aksi === 'SETUJUI') {
                $start = new DateTime($p['tanggal_mulai']);
                $end = new DateTime($p['tanggal_selesai']);
                $end->modify('+1 day');
                
                $period = new DatePeriod($start, new DateInterval('P1D'), $end);

                foreach ($period as $dt) {
                    $tgl = $dt->format("Y-m-d");
                    $stH = $pdo->prepare("SELECT id_presensi FROM kehadiran WHERE tanggal = ?");
                    $stH->execute([$tgl]);
                    $h = $stH->fetch();
                    $idP = $h ? $h['id_presensi'] : $this->uuid();

                    if (!$h) {
                        $pdo->prepare("INSERT INTO kehadiran (id_presensi, tanggal, lokasi) VALUES (?,?,'Sekolah')")
                            ->execute([$idP, $tgl]);
                    }

                    $stD = $pdo->prepare("SELECT id_detail FROM presensi_detail WHERE id_presensi=? AND id_guru=?");
                    $stD->execute([$idP, $p['id_guru']]);
                    $d = $stD->fetch();

                    if ($d) {
                        $pdo->prepare("UPDATE presensi_detail SET status_kehadiran=?, jam_masuk=NULL WHERE id_detail=?")
                            ->execute([$p['jenis'], $d['id_detail']]);
                    } else {
                        $pdo->prepare("INSERT INTO presensi_detail (id_detail, id_presensi, id_guru, status_kehadiran) VALUES (?,?,?,?)")
                            ->execute([$this->uuid(), $idP, $p['id_guru'], $p['jenis']]);
                    }
                }
            }

            $pdo->commit();
            $_SESSION['flash_success'] = 'Berhasil memverifikasi pengajuan';
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_error'] = 'Gagal: ' . $e->getMessage();
        }
        header('Location: ' . $this->base() . '/pengajuan');
        exit;
    }

public function delete($req, $res): void {
    $pdo = Db::pdo();
    $id = $_POST['id_pengajuan'] ?? '';

    if (!$id) {
        $_SESSION['flash_error'] = 'ID tidak valid';
        header('Location: ' . $this->base() . '/pengajuan'); exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Ambil data pengajuan sebelum dihapus (untuk tahu siapa gurunya dan tanggal berapa saja)
        $st = $pdo->prepare("SELECT id_guru, tanggal_mulai, tanggal_selesai, lampiran_path FROM pengajuan_presensi WHERE id_pengajuan = ?");
        $st->execute([$id]);
        $p = $st->fetch(PDO::FETCH_ASSOC);

        if ($p) {
            // 2. LOGIKA RISET: Hapus data di tabel presensi_detail sesuai rentang tanggal pengajuan
            // Kita cari ID presensi di tabel kehadiran berdasarkan rentang tanggal
            $start = new DateTime($p['tanggal_mulai']);
            $end = new DateTime($p['tanggal_selesai']);
            $end->modify('+1 day');
            $period = new DatePeriod($start, new DateInterval('P1D'), $end);

            foreach ($period as $dt) {
                $tgl = $dt->format("Y-m-d");

                // Cari ID presensi (header) untuk tanggal tersebut
                $stH = $pdo->prepare("SELECT id_presensi FROM kehadiran WHERE tanggal = ?");
                $stH->execute([$tgl]);
                $h = $stH->fetch();

                if ($h) {
                    // Hapus baris absensi guru tersebut di tabel detail
                    $delDetail = $pdo->prepare("DELETE FROM presensi_detail WHERE id_presensi = ? AND id_guru = ?");
                    $delDetail->execute([$h['id_presensi'], $p['id_guru']]);
                }
            }

            // 3. Hapus data pengajuan izin/sakit itu sendiri
            $delPengajuan = $pdo->prepare("DELETE FROM pengajuan_presensi WHERE id_pengajuan = ?");
            $delPengajuan->execute([$id]);

            // 4. Hapus file lampiran fisik jika ada di server
            if (!empty($p['lampiran_path'])) {
                $file = dirname(__DIR__, 2) . '/public' . $p['lampiran_path'];
                if (is_file($file)) @unlink($file);
            }

            $pdo->commit();
            $_SESSION['flash_success'] = 'Pengajuan dan data presensi terkait berhasil diriset/dihapus.';
        } else {
            $pdo->rollBack();
            $_SESSION['flash_error'] = 'Data tidak ditemukan.';
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = 'Gagal riset: ' . $e->getMessage();
    }
    
    header('Location: ' . $this->base() . '/pengajuan');
    exit;
}
}