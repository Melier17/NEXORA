<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class ApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 400,
        private readonly array $errors = []
    ) {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public static function validation(array $errors): self
    {
        return new self('The submitted data is invalid.', 422, $errors);
    }
}
