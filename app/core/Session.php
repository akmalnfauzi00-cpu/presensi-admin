<?php

class Session
{
  public static function start(): void
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
  }

  public static function flash(string $key, string $value): void
  {
    self::start();
    $_SESSION['_flash'][$key] = $value;
  }

  public static function getFlash(string $key): ?string
  {
    self::start();

    if (!isset($_SESSION['_flash'][$key])) {
      return null;
    }

    $value = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    return $value;
  }

  public static function pullFlash(string $key): ?string
  {
    return self::getFlash($key);
  }
}