<?php

class Db {
  private static ?PDO $pdo = null;

  public static function pdo(): PDO {
    if (self::$pdo === null) {
      self::$pdo = new PDO(
        'mysql:host=127.0.0.1;dbname=presensi_admin;charset=utf8mb4',
        'root',
        '',
        [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
      );
    }

    return self::$pdo;
  }
}