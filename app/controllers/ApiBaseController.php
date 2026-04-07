<?php

class ApiBaseController
{
  protected function cors(): void
  {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
      http_response_code(200);
      exit;
    }
  }

  protected function json($data, int $code = 200): void
  {
    $this->cors();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
  }

  protected function input(): array
  {
    $raw = file_get_contents('php://input');
    $j = json_decode($raw, true);
    if (is_array($j)) return $j;
    return $_POST ?? [];
  }

  protected function bearerToken(): ?string
  {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $h, $m)) return trim($m[1]);
    return null;
  }

  protected function authGuru(): array
  {
    $pdo = Db::pdo();
    $token = $this->bearerToken();
    if (!$token) $this->json(['ok'=>false,'message'=>'Unauthorized'], 401);

    $stmt = $pdo->prepare("
      SELECT gt.id_guru, g.nama_guru, g.nip
      FROM guru_tokens gt
      JOIN guru g ON g.id_guru = gt.id_guru
      WHERE gt.token = :t AND gt.expired_at > NOW()
      LIMIT 1
    ");
    $stmt->execute([':t'=>$token]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$u) $this->json(['ok'=>false,'message'=>'Token invalid/expired'], 401);
    return $u;
  }

  protected function uuid(): string
  {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
  }

  protected function saveUpload(string $field, string $folder): ?string
  {
    if (!isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      return null;
    }

    $f = $_FILES[$field];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png'], true)) {
      $this->json(['ok'=>false,'message'=>'File harus jpg/png'], 422);
    }

    $baseDir = __DIR__ . '/../../public/uploads/' . $folder;
    if (!is_dir($baseDir)) mkdir($baseDir, 0777, true);

    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $baseDir . '/' . $name;

    if (!move_uploaded_file($f['tmp_name'], $dest)) {
      $this->json(['ok'=>false,'message'=>'Upload gagal'], 500);
    }

    // path yang disimpan ke DB (relative dari public)
    return 'uploads/' . $folder . '/' . $name;
  }
}