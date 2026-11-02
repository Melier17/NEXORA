<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ApiException;
use App\Core\Request;
use App\Core\Response;
use App\Services\ProjectService;
use App\Services\TokenService;
use PDO;

final class ProjectController
{
    private TokenService $tokens;
    private ProjectService $projects;

    public function __construct(PDO $database)
    {
        $this->tokens = new TokenService($database);
        $this->projects = new ProjectService($database);
    }

    public function index(Request $request, array $parameters): Response
    {
        $user = $this->authenticatedUser($request);
        $workspaceId = $this->positiveId($parameters['workspaceId'] ?? null);

        return Response::json([
            'success' => true,
            'data' => ['projects' => $this->projects->list((int) $user['id'], $workspaceId)],
        ]);
    }

    public function store(Request $request, array $parameters): Response
    {
        $user = $this->authenticatedUser($request);
        $workspaceId = $this->positiveId($parameters['workspaceId'] ?? null);

        return Response::json([
            'success' => true,
            'message' => 'Project created successfully.',
            'data' => ['project' => $this->projects->create((int) $user['id'], $workspaceId, $request->body())],
        ], 201);
    }

    public function show(Request $request, array $parameters): Response
    {
        $user = $this->authenticatedUser($request);
        $workspaceId = $this->positiveId($parameters['workspaceId'] ?? null);
        $projectId = $this->positiveId($parameters['projectId'] ?? null);

        return Response::json([
            'success' => true,
            'data' => ['project' => $this->projects->show((int) $user['id'], $workspaceId, $projectId)],
        ]);
    }

    public function update(Request $request, array $parameters): Response
    {
        $user = $this->authenticatedUser($request);
        $workspaceId = $this->positiveId($parameters['workspaceId'] ?? null);
        $projectId = $this->positiveId($parameters['projectId'] ?? null);

        return Response::json([
            'success' => true,
            'message' => 'Project updated successfully.',
            'data' => ['project' => $this->projects->update((int) $user['id'], $workspaceId, $projectId, $request->body())],
        ]);
    }

    private function authenticatedUser(Request $request): array
    {
        $authorization = $request->header('Authorization') ?? '';
        if (preg_match('/^Bearer\s+([a-f0-9]{64})$/i', trim($authorization), $matches) !== 1) {
            throw new ApiException('A valid Bearer token is required.', 401);
        }
        return $this->tokens->authenticate($matches[1]);
    }

    private function positiveId(mixed $value): int
    {
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
            throw new ApiException('A valid resource identifier is required.', 400);
        }
        return (int) $value;
    }
}
