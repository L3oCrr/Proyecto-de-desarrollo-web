<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Http\JsonResponder;

/**
 * Enrutador HTTP mínimo basado en expresiones regulares estrictas.
 */
final class Router
{
    /** @var array<int, array{method: string, pattern: string, handler: callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): self
    {
        return $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): self
    {
        return $this->addRoute('POST', $pattern, $handler);
    }

    public function addRoute(string $method, string $pattern, callable $handler): self
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $this->normalizePattern($pattern),
            'handler' => $handler,
        ];

        return $this;
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($uri);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches) !== 1) {
                continue;
            }

            $params = array_filter(
                $matches,
                static fn ($key) => is_string($key),
                ARRAY_FILTER_USE_KEY
            );

            ($route['handler'])(...array_values($params));

            return;
        }

        JsonResponder::send(404, [
            'error' => true,
            'message' => 'La ruta solicitada no existe.',
            'path' => $path,
        ]);
    }

    private function normalizePattern(string $pattern): string
    {
        $pattern = $this->normalizePath($pattern);

        return '#^' . $pattern . '$#';
    }

    private function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
