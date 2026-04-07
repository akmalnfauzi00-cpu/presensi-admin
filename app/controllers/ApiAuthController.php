<?php

class ApiAuthController
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

    public function login($req, $res): void
    {
        $this->optionsOk();

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $nip  = trim((string)($body['nip'] ?? ''));
        $pass = (string)($body['password'] ?? '');

        if ($nip === '' || $pass === '') {
            $this->json([
                'message' => 'NIP dan password wajib diisi',
                'debug' => [
                    'nip' => $nip,
                    'password_length' => strlen($pass),
                    'raw_body' => $body
                ]
            ], 422);
        }

        $db = Db::pdo();

        $st = $db->prepare("
            SELECT id_guru, nip, nama_guru, password_hash, status_aktif
            FROM guru
            WHERE nip=?
            LIMIT 1
        ");
        $st->execute([$nip]);
        $guru = $st->fetch(PDO::FETCH_ASSOC);

        if (!$guru) $this->json(['message' => 'NIP atau password salah'], 401);

        if (($guru['status_aktif'] ?? 'AKTIF') !== 'AKTIF') {
            $this->json(['message' => 'Akun tidak aktif'], 403);
        }

        $hash = (string)($guru['password_hash'] ?? '');
        if ($hash === '' || !password_verify($pass, $hash)) {
            $this->json(['message' => 'NIP atau password salah'], 401);
        }

        $token = bin2hex(random_bytes(32));

        $up = $db->prepare("UPDATE guru SET api_token=?, updated_at=NOW() WHERE id_guru=?");
        $up->execute([$token, $guru['id_guru']]);

        $this->json([
            'token' => $token,
            'guru' => [
                'id_guru' => $guru['id_guru'],
                'nip' => $guru['nip'],
                'nama_guru' => $guru['nama_guru'],
            ]
        ]);
    }

    public function me($req, $res): void
    {
        $this->optionsOk();

        $token = $this->getBearerToken();
        if (!$token) $this->json(['message' => 'Unauthorized'], 401);

        $db = Db::pdo();

        $st = $db->prepare("
            SELECT id_guru, nip, nama_guru, jabatan, mata_pelajaran,
                   jenis_kelamin, alamat, no_hp, email, foto,
                   status_aktif, updated_at
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

        $this->json(['guru' => $guru]);
    }

    public function logout($req, $res): void
    {
        $this->optionsOk();

        $token = $this->getBearerToken();
        if (!$token) $this->json(['ok' => true], 200);

        $db = Db::pdo();
        $st = $db->prepare("UPDATE guru SET api_token=NULL, updated_at=NOW() WHERE api_token=?");
        $st->execute([$token]);

        $this->json(['ok' => true], 200);
    }
}