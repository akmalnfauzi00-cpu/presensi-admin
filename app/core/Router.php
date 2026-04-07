<?php

class Router {
  private array $routes = [];

  public function add(string $method, string $path, callable $handler): void {
    $this->routes[] = [strtoupper($method), $path, $handler];
  }

  public function dispatch(Request $req): void {
    $method = $req->method();
    $path   = $req->path();

    foreach ($this->routes as [$m, $p, $h]) {
      if ($m === $method && $p === $path) {
        $h($req, new Response());
        return;
      }
    }

    http_response_code(404);
    echo "404 Not Found";
  }
}