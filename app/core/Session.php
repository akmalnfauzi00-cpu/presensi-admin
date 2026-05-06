<?php

class Session
{
  public static function start(): void
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
  }

  /**
   * Jika $value diisi: Menyimpan pesan flash (Set)
   * Jika $value null: Mengambil pesan flash (Get)
   */
  public static function flash(string $key, ?string $value = null): ?string
  {
    self::start();
    if ($value !== null) {
      // Sedang menyimpan pesan
      $_SESSION['_flash'][$key] = $value;
      return null;
    }
    
    // Sedang mengambil pesan
    if (!isset($_SESSION['_flash'][$key])) {
      return null;
    }

    $msg = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $msg;
  }

  public static function getFlash(string $key): ?string
  {
    return self::flash($key);
  }

  public static function pullFlash(string $key): ?string
  {
    return self::flash($key);
  }
}