<?php

class Request
{
  public function method(): string
  {
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
  }

  public function path(): string
  {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = rtrim(dirname($scriptName), '/');

    if ($base && $base !== '/' && strpos($path, $base) === 0) {
      $path = substr($path, strlen($base));
    }

    if ($path === '' || $path === false) {
      $path = '/';
    }

    return $path;
  }

  public function input(string $key, $default = null)
  {
    if ($this->method() === 'POST') {
      return $_POST[$key] ?? $default;
    }

    return $_GET[$key] ?? $default;
  }

  public function all(): array
  {
    return $this->method() === 'POST' ? $_POST : $_GET;
  }

  public function file(string $key): ?array
  {
    return $_FILES[$key] ?? null;
  }

  public function files(?string $key = null)
  {
    if ($key === null) {
      return $_FILES;
    }

    return $_FILES[$key] ?? null;
  }

  public function hasFile(string $key): bool
  {
    return isset($_FILES[$key]) &&
           is_array($_FILES[$key]) &&
           ($_FILES[$key]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
  }

  public function isPost(): bool
  {
    return $this->method() === 'POST';
  }

  public function isGet(): bool
  {
    return $this->method() === 'GET';
  }
}