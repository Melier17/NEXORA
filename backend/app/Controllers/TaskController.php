<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ApiException;
use App\Core\Request;
use App\Core\Response;
use App\Services\TaskService;
use App\Services\TokenService;
use PDO;

final class TaskController
{
    private TokenService $tokens;
    private TaskService $tasks;

    public function __construct(PDO $database)
    {
        $this->tokens = new TokenService($database);
        $this->tasks = new TaskService($database);
    }

    public function index(Request $request, array $parameters): Response
    {
        $user = $this->authenticatedUser($request);
        $workspaceId = $this->positiveId($parameters['workspaceId'] ?? null);
        $projectId = $this->positiveId($parameters['projectId'] ?? null);

        return Response::json([
            'success' => true,
            'data' => ['tasks' => $this->tasks->list((int) $user['id'], $workspaceId, $projectId)],
        ]);
    }

    public function store(Request $request, array $parameters): Response
    {
        $user = $this->authenticatedUser($request);
        $workspaceId = $this->positiveId($parameters['workspaceId'] ?? null);
        $projectId = $this->positiveId($parameters['projectId'] ?? null);

        return Response::json([
            'success' => true,
            'message' => 'Task created successfully.',
            'data' => ['task' => $this->tasks->create((int) $user['id'], $workspaceId, $projectId, $request->body())],
        ], 201);
    }

    public function update(Request $request, array $parameters): Response
    {
        $user = $this->authenticatedUser($request);
        $workspaceId = $this->positiveId($parameters['workspaceId'] ?? null);
        $taskId = $this->positiveId($parameters['taskId'] ?? null);

        return Response::json([
            'success' => true,
            'message' => 'Task updated successfully.',
            'data' => ['task' => $this->tasks->update((int) $user['id'], $workspaceId, $taskId, $request->body())],
        ]);
    }

    public function destroy(Request $request, array $parameters): Response
    {
        $user = $this->authenticatedUser($request);
        $workspaceId = $this->positiveId($parameters['workspaceId'] ?? null);
        $taskId = $this->positiveId($parameters['taskId'] ?? null);
        $this->tasks->delete((int) $user['id'], $workspaceId, $taskId);

        return Response::json([
            'success' => true,
            'message' => 'Task deleted successfully.',
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
