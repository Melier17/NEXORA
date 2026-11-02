<?php

declare(strict_types=1);

use App\Config\Database;
use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Core\Request;
use App\Core\Router;

$router = new Router();

$healthController = new HealthController();
$router->get('/api/v1/health', [$healthController, 'show']);

// The database connection is created only when an authentication endpoint is called.
$authController = static fn (): AuthController => new AuthController(Database::connect());

$router->post('/api/v1/auth/register', static fn (Request $request) => $authController()->register($request));
$router->post('/api/v1/auth/login', static fn (Request $request) => $authController()->login($request));
$router->get('/api/v1/auth/me', static fn (Request $request) => $authController()->me($request));
$router->post('/api/v1/auth/logout', static fn (Request $request) => $authController()->logout($request));

return $router;
