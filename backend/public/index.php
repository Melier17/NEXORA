<?php

declare(strict_types=1);

use App\Core\Request;
use App\Middleware\ErrorHandler;
use Dotenv\Dotenv;
use InvalidArgumentException;
use Throwable;

require dirname(__DIR__) . '/vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

try {
    $request = Request::fromGlobals();

    /** @var \App\Core\Router $router */
    $router = require dirname(__DIR__) . '/routes/api.php';

    $response = $router->dispatch($request);
    $response->send();
} catch (InvalidArgumentException $exception) {
    \App\Core\Response::json([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 400)->send();
} catch (Throwable $exception) {
    ErrorHandler::handle($exception);
}
