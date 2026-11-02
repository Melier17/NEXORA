<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($method === 'GET' && is_string($path) && str_ends_with($path, '/health')) {
    http_response_code(200);

    echo json_encode([
        'success' => true,
        'message' => 'NEXORA API is running.',
        'data' => [
            'status' => 'ok',
            'version' => '0.1.0',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}

http_response_code(404);

echo json_encode([
    'success' => false,
    'message' => 'Endpoint not found.',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
