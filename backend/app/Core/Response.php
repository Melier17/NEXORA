<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public function __construct(
        private readonly array $payload,
        private readonly int $statusCode = 200,
        private readonly array $headers = []
    ) {
    }

    public static function json(
        array $payload,
        int $statusCode = 200,
        array $headers = []
    ): self {
        return new self($payload, $statusCode, $headers);
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo json_encode(
            $this->payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
