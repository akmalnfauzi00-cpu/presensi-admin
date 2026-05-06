<?php

class ApiUploadController
{
    private function json($data, int $code = 200): void
    {
        if (ob_get_level()) ob_end_clean();
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
        $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
        if (!$hdr && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $hdr = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        if (!$hdr) return null;
        if (preg_match('/Bearer\s+(\S+)/i', $hdr, $m)) return $m[1];
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
        if (($guru['status_aktif'] ?? 'AKTIF') !== 'AKTIF') $this->json(['message' => 'Akun tidak aktif'], 403);
        return $guru;
    }

    public function presensi($req, $res): void
    {
        $this->optionsOk();
        $guru = $this->authGuru();

        // SINKRONISASI: Mobile mengirim 'image', bukan 'file'
        $file = $_FILES['image'] ?? $_FILES['file'] ?? null;

        if (!$file) {
            $this->json(['message' => 'File selfie tidak ditemukan'], 422);
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->json(['message' => 'Upload selfie gagal di sistem server'], 422);
        }

        $tmpPath = $file['tmp_name'];
        $size = (int)$file['size'];

        if ($size > 10 * 1024 * 1024) { // Tingkatkan ke 10MB karena foto kamera biasanya besar
            $this->json(['message' => 'Ukuran selfie maksimal 10MB'], 422);
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
            $this->json(['message' => 'Format file ditolak (Gunakan JPG/PNG)'], 422);
        }

        $ext = $allowed[$mime];
        $fileName = 'presensi_' . date('Ymd_His') . '_' . $guru['id_guru'] . '.' . $ext;
        $projectRoot = dirname(__DIR__, 2);
        $uploadDir = $projectRoot . '/public/uploads/presensi';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $targetPath = $uploadDir . '/' . $fileName;

        // PROSES WATERMARK
        try {
            $imageString = file_get_contents($tmpPath);
            $image = imagecreatefromstring($imageString);
            
            if ($image !== false) {
                $latInput = $_POST['lat'] ?? '-';
                $lngInput = $_POST['lng'] ?? '-';
                $waktu = date('Y-m-d H:i:s');
                
                $text1 = "GURU: {$guru['nama_guru']} | {$waktu}";
                $text2 = "LOC: {$latInput}, {$lngInput}";
                
                $textColor = imagecolorallocate($image, 255, 255, 255);
                $bgBox = imagecolorallocatealpha($image, 0, 0, 0, 80);
                $imgHeight = imagesy($image);
                $imgWidth = imagesx($image);

                imagefilledrectangle($image, 0, $imgHeight - 80, $imgWidth, $imgHeight, $bgBox);
                
                // Menggunakan imagestring agar tidak bergantung file .ttf jika tidak ada
                imagestring($image, 5, 20, $imgHeight - 60, $text1, $textColor);
                imagestring($image, 5, 20, $imgHeight - 35, $text2, $textColor);

                if ($ext === 'png') imagepng($image, $targetPath);
                else if ($ext === 'webp') imagewebp($image, $targetPath);
                else imagejpeg($image, $targetPath, 80);
                
                imagedestroy($image);
            } else {
                move_uploaded_file($tmpPath, $targetPath);
            }
        } catch (Exception $e) {
            move_uploaded_file($tmpPath, $targetPath);
        }

        $relativePath = 'uploads/presensi/' . $fileName;
        
        $this->json([
            'status' => 'success',
            'message' => 'Foto berhasil diupload',
            'path' => $relativePath, // Sesuai dengan yang diminta ApiPresensiController
            'url' => $relativePath
        ], 201);
    }
}