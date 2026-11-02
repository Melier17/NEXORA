<?php

declare(strict_types=1);

use App\Controllers\HealthController;
use App\Core\Router;

$router = new Router();

$healthController = new HealthController();
$router->get('/api/v1/health', [$healthController, 'show']);

return $router;
