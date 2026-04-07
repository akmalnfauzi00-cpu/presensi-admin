<?php

class ApiRewardSpController
{
  private function json($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
  }

  private function optionsOk(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
      $this->json(['ok' => true], 200);
    }
  }

  private function getBearerToken(): ?string {
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (!$hdr && function_exists('apache_request_headers')) {
      $headers = apache_request_headers();
      $hdr = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (!$hdr) return null;

    if (preg_match('/Bearer\s+(\S+)/i', $hdr, $m)) {
      return $m[1];
    }

    return null;
  }

  private function getTokenFlexible(): ?string {
    $bearer = $this->getBearerToken();
    if ($bearer) return $bearer;

    $q = trim((string)($_GET['token_preview'] ?? ''));
    if ($q !== '') return $q;

    return null;
  }

  private function authGuru(): array {
    $token = $this->getTokenFlexible();
    if (!$token) $this->json(['message' => 'Unauthorized'], 401);

    $db = Db::pdo();
    $st = $db->prepare("
      SELECT id_guru, nip, nama_guru, status_aktif
      FROM guru
      WHERE api_token = ?
      LIMIT 1
    ");
    $st->execute([$token]);
    $guru = $st->fetch(PDO::FETCH_ASSOC);

    if (!$guru) $this->json(['message' => 'Unauthorized'], 401);

    if (($guru['status_aktif'] ?? 'AKTIF') !== 'AKTIF') {
      $this->json(['message' => 'Akun tidak aktif'], 403);
    }

    return $guru;
  }

  private function getConfig(PDO $db): array {
    $st = $db->query("SELECT * FROM presensi_master ORDER BY created_at DESC LIMIT 1");
    $cfg = $st->fetch(PDO::FETCH_ASSOC);

    if (!$cfg) {
      return [
        'minimal_hadir_reward' => 0,
        'minimal_tidak_hadir_sp' => 3,
      ];
    }

    return $cfg;
  }

  private function getDateRange(string $periode): array {
    $periode = trim($periode);

    if (preg_match('/^\d{4}-\d{2}$/', $periode)) {
      $mulai = $periode . '-01';
      $selesai = date('Y-m-t', strtotime($mulai));
      return [$mulai, $selesai];
    }

    $mulai = date('Y-m-01');
    $selesai = date('Y-m-t');
    return [$mulai, $selesai];
  }

  private function countWeekdays(string $mulai, string $selesai): int {
    if ($mulai > $selesai) return 0;

    $count = 0;
    $cursor = strtotime($mulai);
    $end = strtotime($selesai);

    while ($cursor <= $end) {
      $dayOfWeek = (int)date('N', $cursor);
      if ($dayOfWeek <= 5) {
        $count++;
      }
      $cursor = strtotime('+1 day', $cursor);
    }

    return $count;
  }

  private function hitungBulanan(PDO $db, string $idGuru, string $periode): array {
    [$mulai, $selesai] = $this->getDateRange($periode);

    $totalHari = (int)((strtotime($selesai) - strtotime($mulai)) / 86400) + 1;
    if ($totalHari < 1) $totalHari = 1;

    $today = date('Y-m-d');
    $hariSelesaiTerhitung = $selesai;

    if ($mulai > $today) {
      $hariSelesaiTerhitung = date('Y-m-d', strtotime($mulai . ' -1 day'));
    } elseif ($selesai > $today) {
      $hariSelesaiTerhitung = $today;
    }

    $totalHariKerja = $this->countWeekdays($mulai, $selesai);
    $hariKerjaTerhitung = ($hariSelesaiTerhitung >= $mulai)
      ? $this->countWeekdays($mulai, $hariSelesaiTerhitung)
      : 0;

    $st = $db->prepare("
      SELECT
        SUM(CASE WHEN pd.status_kehadiran = 'HADIR' THEN 1 ELSE 0 END) AS hadir,
        SUM(CASE WHEN pd.status_kehadiran = 'IZIN' THEN 1 ELSE 0 END) AS izin,
        SUM(CASE WHEN pd.status_kehadiran = 'SAKIT' THEN 1 ELSE 0 END) AS sakit
      FROM presensi_detail pd
      JOIN kehadiran k ON k.id_presensi = pd.id_presensi
      WHERE pd.id_guru = :id_guru
        AND k.tanggal BETWEEN :mulai AND :selesai
        AND DAYOFWEEK(k.tanggal) BETWEEN 2 AND 6
    ");
    $st->execute([
      ':id_guru' => $idGuru,
      ':mulai' => $mulai,
      ':selesai' => $selesai,
    ]);

    $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];

    $hadir = (int)($row['hadir'] ?? 0);
    $izin = (int)($row['izin'] ?? 0);
    $sakit = (int)($row['sakit'] ?? 0);

    $tidakHadir = max(0, $hariKerjaTerhitung - ($hadir + $izin + $sakit));

    return [
      'periode' => $periode,
      'mulai' => $mulai,
      'selesai' => $selesai,
      'today' => $today,
      'total_hari' => $totalHari,
      'total_hari_kerja' => $totalHariKerja,
      'hari_terhitung' => $hariKerjaTerhitung,
      'hari_kerja_terhitung' => $hariKerjaTerhitung,
      'hadir' => $hadir,
      'izin' => $izin,
      'sakit' => $sakit,
      'tidak_hadir' => $tidakHadir,
    ];
  }

  public function me($req, $res): void {
    $this->optionsOk();
    $guru = $this->authGuru();
    $db = Db::pdo();

    $periode = trim((string)($_GET['periode'] ?? date('Y-m')));
    if (!preg_match('/^\d{4}-\d{2}$/', $periode)) {
      $periode = date('Y-m');
    }

    $cfg = $this->getConfig($db);
    $stat = $this->hitungBulanan($db, $guru['id_guru'], $periode);

    $minimalHadirReward = (int)($cfg['minimal_hadir_reward'] ?? 0);
    $minimalTidakHadirSp = (int)($cfg['minimal_tidak_hadir_sp'] ?? 3);

    if ($minimalHadirReward <= 0) {
      $minimalHadirReward = $stat['total_hari_kerja'];
    }

    $eligibleReward = $stat['hadir'] >= $minimalHadirReward;
    $eligibleSp = $stat['tidak_hadir'] >= $minimalTidakHadirSp;

    $st = $db->prepare("
      SELECT id_dokumen, periode, jenis, deskripsi, file_pdf_path, status_unduh, dibuat_pada
      FROM reward_sp_dokumen
      WHERE id_guru = :id_guru
        AND periode = :periode
      ORDER BY dibuat_pada DESC
    ");
    $st->execute([
      ':id_guru' => $guru['id_guru'],
      ':periode' => $periode,
    ]);
    $docs = $st->fetchAll(PDO::FETCH_ASSOC);

    $rewardDocs = [];
    $spDocs = [];

    foreach ($docs as $d) {
      $item = [
        'id_dokumen' => $d['id_dokumen'],
        'periode' => $d['periode'],
        'jenis' => $d['jenis'],
        'deskripsi' => $d['deskripsi'],
        'status_unduh' => $d['status_unduh'],
        'dibuat_pada' => $d['dibuat_pada'],
        'download_url' => '/rewardsp/download?id=' . urlencode($d['id_dokumen']),
        'api_download_url' => '/api/rewardsp/download?id=' . urlencode($d['id_dokumen']),
      ];

      if (($d['jenis'] ?? '') === 'REWARD') $rewardDocs[] = $item;
      if (($d['jenis'] ?? '') === 'SP') $spDocs[] = $item;
    }

    $this->json([
      'periode' => $periode,
      'setting' => [
        'minimal_hadir_reward' => $minimalHadirReward,
        'minimal_tidak_hadir_sp' => $minimalTidakHadirSp,
      ],
      'statistik' => $stat,
      'reward' => [
        'eligible' => $eligibleReward,
        'dokumen' => $rewardDocs,
      ],
      'sp' => [
        'eligible' => $eligibleSp,
        'dokumen' => $spDocs,
      ],
    ]);
  }

  public function download($req, $res): void {
    $this->optionsOk();
    $guru = $this->authGuru();

    $db = Db::pdo();
    $id = trim((string)($_GET['id'] ?? ''));
    if ($id === '') $this->json(['message' => 'ID dokumen tidak valid'], 422);

    $st = $db->prepare("
      SELECT *
      FROM reward_sp_dokumen
      WHERE id_dokumen = ?
        AND id_guru = ?
      LIMIT 1
    ");
    $st->execute([$id, $guru['id_guru']]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
      http_response_code(404);
      die('Dokumen tidak ditemukan');
    }

    $cfg = $this->getConfig($db);
    $stat = $this->hitungBulanan($db, $guru['id_guru'], $row['periode']);

    $minimalHadirReward = (int)($cfg['minimal_hadir_reward'] ?? 0);
    $minimalTidakHadirSp = (int)($cfg['minimal_tidak_hadir_sp'] ?? 3);

    if ($minimalHadirReward <= 0) {
      $minimalHadirReward = $stat['total_hari_kerja'];
    }

    $allowed = false;

    if (($row['jenis'] ?? '') === 'REWARD' && $stat['hadir'] >= $minimalHadirReward) {
      $allowed = true;
    }

    if (($row['jenis'] ?? '') === 'SP' && $stat['tidak_hadir'] >= $minimalTidakHadirSp) {
      $allowed = true;
    }

    if (!$allowed) {
      http_response_code(403);
      die('Dokumen belum bisa diunduh karena syarat belum terpenuhi');
    }

    $abs = __DIR__ . '/../../public' . $row['file_pdf_path'];
    if (!is_file($abs)) {
      http_response_code(404);
      die('File tidak ditemukan');
    }

    $upd = $db->prepare("UPDATE reward_sp_dokumen SET status_unduh='SUDAH_DIUNDUH' WHERE id_dokumen=?");
    $upd->execute([$id]);

    header("Content-Type: application/pdf");
    header('Content-Disposition: attachment; filename="' . basename($row['file_pdf_path']) . '"');
    header("Content-Length: " . filesize($abs));
    readfile($abs);
    exit;
  }
}