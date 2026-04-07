<?php

class Auth
{
  public static function check(): bool
  {
    Session::start();
    return !empty($_SESSION['auth_user']);
  }

  public static function user(): ?array
  {
    Session::start();
    return $_SESSION['auth_user'] ?? null;
  }

  public static function login(array $user): void
  {
    Session::start();
    $_SESSION['auth_user'] = $user;
  }

  public static function logout(): void
  {
    Session::start();
    unset($_SESSION['auth_user']);
  }
}