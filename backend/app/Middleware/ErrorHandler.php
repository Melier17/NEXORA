<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Response;
use Throwable;

final class ErrorHandler
{
    public static function handle(Throwable $exception): void
    {
        error_log((string) $exception);

        $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL);

        $payload = [
            'success' => false,
            'message' => 'An unexpected server error occurred.',
        ];

        if ($debug) {
            $payload['debug'] = [
                'type' => $exception::class,
                'message' => $exception->getMessage(),
            ];
        }

        Response::json($payload, 500)->send();
    }
}
