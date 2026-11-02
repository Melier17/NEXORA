<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:callable}> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, callable $handler): void
    {
        $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $normalizedPath = '/' . trim($path, '/');
        if ($normalizedPath === '//') {
            $normalizedPath = '/';
        }

        $segments = trim($normalizedPath, '/') === ''
            ? []
            : explode('/', trim($normalizedPath, '/'));

        $patternSegments = [];

        foreach ($segments as $segment) {
            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $segment, $matches) === 1) {
                $patternSegments[] = '(?P<' . $matches[1] . '>[^/]+)';
            } else {
                $patternSegments[] = preg_quote($segment, '~');
            }
        }

        $pattern = '~^/' . implode('/', $patternSegments) . '/?$~';

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }

            if (preg_match($route['pattern'], $request->path(), $matches) !== 1) {
                continue;
            }

            $parameters = array_filter(
                $matches,
                static fn (string|int $key): bool => is_string($key),
                ARRAY_FILTER_USE_KEY
            );

            $result = ($route['handler'])($request, $parameters);

            return $result instanceof Response
                ? $result
                : Response::json(['success' => true, 'data' => $result]);
        }

        return Response::json([
            'success' => false,
            'message' => 'Endpoint not found.',
        ], 404);
    }
}
