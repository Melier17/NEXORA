<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ApiException;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use PDO;

final class AuthController
{
    private AuthService $auth;

    public function __construct(PDO $database)
    {
        $this->auth = new AuthService($database);
    }

    public function register(Request $request): Response
    {
        return Response::json([
            'success' => true,
            'message' => 'Account created successfully.',
            'data' => $this->auth->register($request->body()),
        ], 201);
    }

    public function login(Request $request): Response
    {
        return Response::json([
            'success' => true,
            'message' => 'Signed in successfully.',
            'data' => $this->auth->login($request->body()),
        ]);
    }

    public function me(Request $request): Response
    {
        $user = $this->auth->currentUser($this->bearerToken($request));

        return Response::json([
            'success' => true,
            'data' => ['user' => $user],
        ]);
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout($this->bearerToken($request));

        return Response::json([
            'success' => true,
            'message' => 'Signed out successfully.',
        ]);
    }

    private function bearerToken(Request $request): string
    {
        $authorization = $request->header('Authorization') ?? '';

        if (preg_match('/^Bearer\s+([a-f0-9]{64})$/i', trim($authorization), $matches) !== 1) {
            throw new ApiException('A valid Bearer token is required.', 401);
        }

        return $matches[1];
    }
}
