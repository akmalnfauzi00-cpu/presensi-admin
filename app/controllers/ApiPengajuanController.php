<?php

class ApiPengajuanController
{
  private function json($data, int $code = 200): void
  {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
  }

  private function optionsOk(): void
  {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
      $this->json(['ok' => true], 200);
    }
  }

  private function getBearerToken(): ?string
  {
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

  private function authGuru(): array
  {
    $token = $this->getBearerToken();
    if (!$token) $this->json(['message' => 'Unauthorized'], 401);

    $db = Db::pdo();
    $st = $db->prepare("
      SELECT id_guru, nip, nama_guru, status_aktif
      FROM guru
      WHERE api_token=?
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

  private function uuid(): string
  {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
  }

  public function index($req, $res): void
  {
    $this->optionsOk();
    $guru = $this->authGuru();

    $db = Db::pdo();

    $st = $db->prepare("
      SELECT
        id_pengajuan,
        jenis,
        tanggal,
        alasan,
        lampiran_path,
        status_verifikasi,
        catatan_admin,
        created_at
      FROM pengajuan_presensi
      WHERE id_guru=?
      ORDER BY tanggal DESC, created_at DESC
      LIMIT 100
    ");
    $st->execute([$guru['id_guru']]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $this->json(['items' => $rows]);
  }

  public function store($req, $res): void
  {
    $this->optionsOk();
    $guru = $this->authGuru();

    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $jenis = strtoupper(trim((string)($body['jenis'] ?? '')));
    $tanggal = trim((string)($body['tanggal'] ?? ''));
    $alasan = trim((string)($body['alasan'] ?? ''));
    $lampiranPath = trim((string)($body['lampiran_path'] ?? ''));

    if (!in_array($jenis, ['IZIN', 'SAKIT'], true)) {
      $this->json(['message' => 'Jenis pengajuan tidak valid'], 422);
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
      $this->json(['message' => 'Tanggal wajib format YYYY-MM-DD'], 422);
    }

    if ($alasan === '') {
      $this->json(['message' => 'Alasan wajib diisi'], 422);
    }

    $db = Db::pdo();

    $cek = $db->prepare("
      SELECT id_pengajuan
      FROM pengajuan_presensi
      WHERE id_guru=? AND tanggal=? AND jenis=?
      LIMIT 1
    ");
    $cek->execute([$guru['id_guru'], $tanggal, $jenis]);
    $exists = $cek->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
      $this->json(['message' => 'Pengajuan untuk tanggal dan jenis tersebut sudah ada'], 409);
    }

    $id = $this->uuid();

    $ins = $db->prepare("
      INSERT INTO pengajuan_presensi
      (id_pengajuan, id_guru, jenis, tanggal, alasan, lampiran_path, status_verifikasi, created_at)
      VALUES (?,?,?,?,?,?, 'MENUNGGU', NOW())
    ");
    $ins->execute([
      $id,
      $guru['id_guru'],
      $jenis,
      $tanggal,
      $alasan,
      $lampiranPath !== '' ? $lampiranPath : null
    ]);

    $this->json([
      'message' => 'Pengajuan berhasil dikirim',
      'item' => [
        'id_pengajuan' => $id,
        'jenis' => $jenis,
        'tanggal' => $tanggal,
        'alasan' => $alasan,
        'lampiran_path' => $lampiranPath !== '' ? $lampiranPath : null,
        'status_verifikasi' => 'MENUNGGU'
      ]
    ], 201);
  }
}