<?php

class Pengumuman
{
  public static function all(): array
  {
    $pdo = Db::pdo();
    $st = $pdo->query("SELECT * FROM pengumuman ORDER BY created_at DESC");
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function create(array $data): bool
  {
    $pdo = Db::pdo();
    $st = $pdo->prepare("INSERT INTO pengumuman (title, content, created_at) VALUES (?, ?, NOW())");
    return $st->execute([$data['title'], $data['content']]);
  }

  public static function delete(int $id): bool
  {
    $pdo = Db::pdo();
    $st = $pdo->prepare("DELETE FROM pengumuman WHERE id = ?");
    return $st->execute([$id]);
  }
}