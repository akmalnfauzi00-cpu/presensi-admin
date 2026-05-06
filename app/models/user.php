<?php

class User
{
  public static function all(string $q = ''): array
  {
    $pdo = Db::pdo();

    if ($q !== '') {
      $st = $pdo->prepare("
        SELECT *
        FROM users
        WHERE username LIKE ?
           OR nama LIKE ?
           OR role LIKE ?
           OR nip LIKE ?
           OR email LIKE ?
        ORDER BY created_at DESC, username ASC
      ");
      $like = '%' . $q . '%';
      $st->execute([$like, $like, $like, $like, $like]);
      return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    $st = $pdo->query("
      SELECT *
      FROM users
      ORDER BY created_at DESC, username ASC
    ");
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function findById(string $id): ?array
  {
    $pdo = Db::pdo();
    $st = $pdo->prepare("SELECT * FROM users WHERE id_user = ? LIMIT 1");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public static function findByUsername(string $username): ?array
  {
    $pdo = Db::pdo();
    $st = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $st->execute([$username]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public static function existsUsername(string $username, ?string $excludeId = null): bool
  {
    $pdo = Db::pdo();

    if ($excludeId) {
      $st = $pdo->prepare("
        SELECT COUNT(*) AS jml
        FROM users
        WHERE username = ?
          AND id_user <> ?
      ");
      $st->execute([$username, $excludeId]);
    } else {
      $st = $pdo->prepare("
        SELECT COUNT(*) AS jml
        FROM users
        WHERE username = ?
      ");
      $st->execute([$username]);
    }

    $row = $st->fetch(PDO::FETCH_ASSOC);
    return (int)($row['jml'] ?? 0) > 0;
  }

  /**
   * PERBAIKAN: Menambahkan nip dan email ke query INSERT
   */
  public static function create(array $data): bool
  {
    $pdo = Db::pdo();

    $st = $pdo->prepare("
      INSERT INTO users
      (id_user, nip, username, password_hash, nama, email, role, status_aktif, created_at, updated_at)
      VALUES
      (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    return $st->execute([
      $data['id_user'],
      $data['nip'],          // Tambahan
      $data['username'],
      $data['password_hash'],
      $data['nama'],
      $data['email'],        // Tambahan
      $data['role'],
      $data['status_aktif'],
    ]);
  }

  /**
   * PERBAIKAN: Menambahkan nip dan email ke query UPDATE
   */
  public static function update(string $id, array $data): bool
  {
    $pdo = Db::pdo();

    $fields = [
      'nip = ?',          // Tambahan
      'username = ?',
      'nama = ?',
      'email = ?',        // Tambahan
      'role = ?',
      'status_aktif = ?',
      'updated_at = NOW()',
    ];

    $params = [
      $data['nip'],       // Tambahan
      $data['username'],
      $data['nama'],
      $data['email'],     // Tambahan
      $data['role'],
      $data['status_aktif'],
    ];

    if (!empty($data['password_hash'])) {
      $fields[] = 'password_hash = ?';
      $params[] = $data['password_hash'];
    }

    $params[] = $id;

    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id_user = ?";
    $st = $pdo->prepare($sql);

    return $st->execute($params);
  }

  public static function delete(string $id): bool
  {
    $pdo = Db::pdo();
    $st = $pdo->prepare("DELETE FROM users WHERE id_user = ?");
    return $st->execute([$id]);
  }
}