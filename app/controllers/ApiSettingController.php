<?php

class ApiSettingController
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

  public function index($req, $res): void {
    $this->optionsOk();

    $db = Db::pdo();
    $st = $db->query("SELECT * FROM presensi_master ORDER BY created_at DESC LIMIT 1");
    $cfg = $st->fetch(PDO::FETCH_ASSOC);

    if (!$cfg) {
      $cfg = [
        'jam_masuk' => '07:00:00',
        'jam_pulang' => '15:30:00',
        'batas_terlambat' => '08:15:00',
        'toleransi_terlambat' => 0,
        'minimal_hadir_reward' => 0,
        'minimal_tidak_hadir_sp' => 3,
        'latitude' => null,
        'longitude' => null,
        'radius_meter' => 150,
      ];
    }

    $this->json([
      'jam' => [
        'jam_masuk' => (string)($cfg['jam_masuk'] ?? '07:00:00'),
        'jam_pulang' => (string)($cfg['jam_pulang'] ?? '15:30:00'),
        'batas_terlambat' => (string)($cfg['batas_terlambat'] ?? '08:15:00'),
        'toleransi_terlambat' => (int)($cfg['toleransi_terlambat'] ?? 0),
      ],
      'reward_sp' => [
        'minimal_hadir_reward' => (int)($cfg['minimal_hadir_reward'] ?? 0),
        'minimal_tidak_hadir_sp' => (int)($cfg['minimal_tidak_hadir_sp'] ?? 3),
      ],
      'sekolah' => [
        'nama' => 'SMP Muhammadiyah 2 Karanglewas',
        'alamat' => '',
        'lat' => isset($cfg['latitude']) && $cfg['latitude'] !== null ? (float)$cfg['latitude'] : null,
        'lng' => isset($cfg['longitude']) && $cfg['longitude'] !== null ? (float)$cfg['longitude'] : null,
        'radius_m' => (int)($cfg['radius_meter'] ?? 150),
      ],
    ]);
  }
}