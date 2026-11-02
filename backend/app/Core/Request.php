<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;

final class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query = [],
        private readonly array $body = [],
        private readonly array $headers = []
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path = is_string($uriPath) ? $uriPath : '/';

        // Remove the public directory prefix when the app runs in a subdirectory.
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = rtrim(str_replace('\\\\', '/', dirname($scriptName)), '/');

        if ($basePath !== '' && $basePath !== '/' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        $path = '/' . trim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $contentType = strtolower((string) ($headers['Content-Type'] ?? $headers['content-type'] ?? ''));

        $body = $_POST;
        if (str_contains($contentType, 'application/json')) {
            $rawBody = file_get_contents('php://input') ?: '';
            if ($rawBody !== '') {
                try {
                    $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
                } catch (\\JsonException) {
                    throw new InvalidArgumentException('The request body contains invalid JSON.');
                }

                if (!is_array($decoded)) {
                    throw new InvalidArgumentException('The JSON request body must be an object.');
                }

                $body = $decoded;
            }
        }

        return new self($method, $path, $_GET, $body, $headers);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(): array
    {
        return $this->query;
    }

    public function body(): array
    {
        return $this->body;
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp((string) $key, $name) === 0) {
                return is_string($value) ? $value : null;
            }
        }

        return null;
    }
}
