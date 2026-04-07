<?php

class Response {
  private function base(): string {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    return rtrim(dirname($scriptName), '/');
  }

  public function redirect(string $path): void {
    if ($path !== '' && $path[0] === '/') {
      $path = $this->base() . $path;
    }

    header('Location: ' . $path);
    exit;
  }
}