<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

final class HealthController
{
    public function show(Request $request): Response
    {
        return Response::json([
            'success' => true,
            'message' => 'NEXORA API is running.',
            'data' => [
                'status' => 'ok',
                'version' => '0.1.0',
            ],
        ]);
    }
}
