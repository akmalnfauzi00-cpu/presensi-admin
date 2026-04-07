<?php
// htdocs/presensi-admin/public/api/index.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function jsonOut($data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function readJson(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '[]', true);
  return is_array($data) ? $data : [];
}

function bearerToken(): ?string {
  $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  if (!$h && function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $h = $headers['Authorization'] ?? $headers['authorization'] ?? '';
  }
  if (!$h) return null;
  $t = preg_replace('/^Bearer\s+/i', '', trim($h));
  return $t !== '' ? $t : null;
}

function pdo(): PDO {
  // SESUAIKAN kalau credential kamu beda
  $host = "127.0.0.1";
  $db   = "presensi_admin";
  $user = "root";
  $pass = "";

  return new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Helpers: cocokkan route walau base path beda
function endsWith(string $haystack, string $needle): bool {
  return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
}

/**
 * POST .../public/api/login
 * body: {nip, password}
 */
if ($method === 'POST' && endsWith($path, '/public/api/login')) {
  $in = readJson();
  $nip = trim((string)($in['nip'] ?? ''));
  $password = (string)($in['password'] ?? '');

  if ($nip === '' || $password === '') {
    jsonOut(['message' => 'NIP dan password wajib diisi'], 422);
  }

  $pdo = pdo();
  $st = $pdo->prepare("SELECT id_guru, nip, nama_guru, password_hash, status_aktif
                       FROM guru WHERE nip=? LIMIT 1");
  $st->execute([$nip]);
  $guru = $st->fetch();

  if (!$guru) jsonOut(['message' => 'NIP atau password salah'], 401);
  if (($guru['status_aktif'] ?? 'AKTIF') !== 'AKTIF') {
    jsonOut(['message' => 'Akun tidak aktif'], 403);
  }
  if (empty($guru['password_hash']) || !password_verify($password, $guru['password_hash'])) {
    jsonOut(['message' => 'NIP atau password salah'], 401);
  }

  $token = bin2hex(random_bytes(32)); // 64 char
  $up = $pdo->prepare("UPDATE guru SET api_token=? WHERE id_guru=?");
  $up->execute([$token, $guru['id_guru']]);

  jsonOut([
    'token' => $token,
    'user' => [
      'id_guru' => $guru['id_guru'],
      'nip' => $guru['nip'],
      'nama_guru' => $guru['nama_guru'],
    ]
  ]);
}

/**
 * GET .../public/api/me
 * header: Authorization: Bearer <token>
 */
if ($method === 'GET' && endsWith($path, '/public/api/me')) {
  $token = bearerToken();
  if (!$token) jsonOut(['message' => 'Unauthorized'], 401);

  $pdo = pdo();
  $st = $pdo->prepare("SELECT id_guru, nip, nama_guru, jabatan, mata_pelajaran, jenis_kelamin, alamat, no_hp, email, foto, status_aktif, updated_at
                       FROM guru WHERE api_token=? LIMIT 1");
  $st->execute([$token]);
  $guru = $st->fetch();

  if (!$guru) jsonOut(['message' => 'Unauthorized'], 401);
  if (($guru['status_aktif'] ?? 'AKTIF') !== 'AKTIF') {
    jsonOut(['message' => 'Akun tidak aktif'], 403);
  }

  jsonOut(['user' => $guru]);
}

/**
 * POST .../public/api/logout
 */
if ($method === 'POST' && endsWith($path, '/public/api/logout')) {
  $token = bearerToken();
  if ($token) {
    $pdo = pdo();
    $st = $pdo->prepare("UPDATE guru SET api_token=NULL WHERE api_token=?");
    $st->execute([$token]);
  }
  jsonOut(['message' => 'OK']);
}

jsonOut(['message' => 'Not Found'], 404);