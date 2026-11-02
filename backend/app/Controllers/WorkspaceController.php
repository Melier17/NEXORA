<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ApiException;
use App\Core\Request;
use App\Core\Response;
use App\Services\TokenService;
use App\Services\WorkspaceService;
use PDO;

final class WorkspaceController
{
    private TokenService $tokens;
    private WorkspaceService $workspaces;

    public function __construct(PDO $database)
    {
        $this->tokens = new TokenService($database);
        $this->workspaces = new WorkspaceService($database);
    }

    public function index(Request $request): Response
    {
        $user = $this->authenticatedUser($request);

        return Response::json([
            'success' => true,
            'data' => ['workspaces' => $this->workspaces->listForUser((int) $user['id'])],
        ]);
    }

    public function store(Request $request): Response
    {
        $user = $this->authenticatedUser($request);

        return Response::json([
            'success' => true,
            'message' => 'Workspace created successfully.',
            'data' => ['workspace' => $this->workspaces->create((int) $user['id'], $request->body())],
        ], 201);
    }

    public function show(Request $request, array $parameters): Response
    {
        $user = $this->authenticatedUser($request);
        $workspaceId = $this->positiveId($parameters['workspaceId'] ?? null);

        return Response::json([
            'success' => true,
            'data' => ['workspace' => $this->workspaces->show((int) $user['id'], $workspaceId)],
        ]);
    }

    public function members(Request $request, array $parameters): Response
    {
        $user = $this->authenticatedUser($request);
        $workspaceId = $this->positiveId($parameters['workspaceId'] ?? null);

        return Response::json([
            'success' => true,
            'data' => ['members' => $this->workspaces->members((int) $user['id'], $workspaceId)],
        ]);
    }

    public function addMember(Request $request, array $parameters): Response
    {
        $user = $this->authenticatedUser($request);
        $workspaceId = $this->positiveId($parameters['workspaceId'] ?? null);

        return Response::json([
            'success' => true,
            'message' => 'Member added successfully.',
            'data' => $this->workspaces->addMember((int) $user['id'], $workspaceId, $request->body()),
        ], 201);
    }

    public function updateMemberRole(Request $request, array $parameters): Response
    {
        $user = $this->authenticatedUser($request);
        $workspaceId = $this->positiveId($parameters['workspaceId'] ?? null);
        $targetUserId = $this->positiveId($parameters['userId'] ?? null);

        return Response::json([
            'success' => true,
            'message' => 'Member role updated successfully.',
            'data' => $this->workspaces->updateMemberRole(
                (int) $user['id'],
                $workspaceId,
                $targetUserId,
                $request->body()
            ),
        ]);
    }

    public function removeMember(Request $request, array $parameters): Response
    {
        $user = $this->authenticatedUser($request);
        $workspaceId = $this->positiveId($parameters['workspaceId'] ?? null);
        $targetUserId = $this->positiveId($parameters['userId'] ?? null);

        $this->workspaces->removeMember((int) $user['id'], $workspaceId, $targetUserId);

        return Response::json([
            'success' => true,
            'message' => 'Member removed successfully.',
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
