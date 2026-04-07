<?php
require_once __DIR__ . '/ApiBaseController.php';

class ApiGuruController extends ApiBaseController
{
  public function login($req, $res)
  {
    $pdo = Db::pdo();
    $d = $this->input();

    $nip = trim((string)($d['nip'] ?? $d['username'] ?? ''));
    if ($nip === '') $this->json(['ok'=>false,'message'=>'NIP wajib'], 422);

    // cari guru berdasar NIP
    $stmt = $pdo->prepare("SELECT id_guru, nama_guru, nip FROM guru WHERE nip = :nip LIMIT 1");
    $stmt->execute([':nip'=>$nip]);
    $g = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$g) $this->json(['ok'=>false,'message'=>'NIP tidak ditemukan'], 404);

    // buat token 30 hari
    $token = bin2hex(random_bytes(32));
    $exp = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));

    // hapus token lama guru ini (biar 1 device = 1 token, simple)
    $pdo->prepare("DELETE FROM guru_tokens WHERE id_guru = :id")->execute([':id'=>$g['id_guru']]);

    $ins = $pdo->prepare("INSERT INTO guru_tokens (id_guru, token, expired_at) VALUES (:id, :t, :exp)");
    $ins->execute([':id'=>$g['id_guru'], ':t'=>$token, ':exp'=>$exp]);

    $this->json([
      'ok'=>true,
      'token'=>$token,
      'guru'=>[
        'id_guru'=>$g['id_guru'],
        'nama_guru'=>$g['nama_guru'],
        'nip'=>$g['nip'],
      ]
    ]);
  }

  public function me($req, $res)
  {
    $u = $this->authGuru();
    $this->json(['ok'=>true,'guru'=>$u]);
  }

  public function logout($req, $res)
  {
    $pdo = Db::pdo();
    $token = $this->bearerToken();
    if ($token) {
      $pdo->prepare("DELETE FROM guru_tokens WHERE token = :t")->execute([':t'=>$token]);
    }
    $this->json(['ok'=>true,'message'=>'logout']);
  }
}