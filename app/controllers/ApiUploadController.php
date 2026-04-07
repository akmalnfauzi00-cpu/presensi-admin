<?php

class ApiUploadController
{
  private function json($data, int $code = 200): void
  {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
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
    $st = $db->prepare("SELECT id_guru, nama_guru, status_aktif FROM guru WHERE api_token=? LIMIT 1");
    $st->execute([$token]);
    $guru = $st->fetch(PDO::FETCH_ASSOC);

    if (!$guru) $this->json(['message' => 'Unauthorized'], 401);
    if (($guru['status_aktif'] ?? 'AKTIF') !== 'AKTIF') {
      $this->json(['message' => 'Akun tidak aktif'], 403);
    }

    return $guru;
  }

  private function randomName(string $ext = 'jpg'): string
  {
    return date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
  }

  public function presensi($req, $res): void
  {
    $this->optionsOk();
    $guru = $this->authGuru();

    if (!isset($_FILES['file'])) {
      $this->json(['message' => 'File selfie tidak ditemukan'], 422);
    }

    $file = $_FILES['file'];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      $this->json(['message' => 'Upload selfie gagal'], 422);
    }

    $tmpPath = $file['tmp_name'] ?? '';
    $originalName = $file['name'] ?? 'selfie';
    $size = (int)($file['size'] ?? 0);

    if ($size <= 0) {
      $this->json(['message' => 'Ukuran file tidak valid'], 422);
    }

    if ($size > 3 * 1024 * 1024) {
      $this->json(['message' => 'Ukuran selfie maksimal 3MB'], 422);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpPath);
    finfo_close($finfo);

    $allowed = [
      'image/jpeg' => 'jpg',
      'image/png'  => 'png',
      'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
      $this->json(['message' => 'Format selfie harus JPG, PNG, atau WEBP'], 422);
    }

    $ext = $allowed[$mime];
    $fileName = 'presensi_' . date('Ymd_His') . '_' . $guru['id_guru'] . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

    // Lokasi penyimpanan file
    $projectRoot = dirname(__DIR__, 2);
    $uploadDir = $projectRoot . '/public/uploads/presensi';

    if (!is_dir($uploadDir)) {
      if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        $this->json(['message' => 'Folder upload selfie gagal dibuat'], 500);
      }
    }

    $targetPath = $uploadDir . '/' . $fileName;

    // Manipulasi gambar dengan watermark
    $image = imagecreatefromstring(file_get_contents($tmpPath));
    $text = "Nama: {$guru['nama_guru']}\n" . date('Y-m-d H:i:s') . "\nLat: {$req->lat}, Lng: {$req->lng}";
    $font = './arial.ttf'; // Path font TrueType
    $fontSize = 20;
    $textColor = imagecolorallocate($image, 255, 255, 255); // Warna putih
    $x = 20;
    $y = imagesy($image) - 40;

    // Menambahkan watermark
    imagettftext($image, $fontSize, 0, $x, $y, $textColor, $font, $text);

    // Simpan gambar dengan watermark
    imagejpeg($image, $targetPath);
    imagedestroy($image);

    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if ($base === '/') $base = '';

    $relativePath = '/uploads/presensi/' . $fileName;
    $publicUrl = $base . $relativePath;

    $this->json([
      'message' => 'Upload selfie berhasil',
      'file' => [
        'original_name' => $originalName,
        'mime' => $mime,
        'size' => $size,
        'path' => $relativePath,
        'url' => $publicUrl,
      ],
    ], 201);
  }
}