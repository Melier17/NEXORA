<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Repositories\WorkspaceRepository;
use App\Validators\Validator;
use PDO;
use PDOException;

final class WorkspaceService
{
    private WorkspaceRepository $workspaces;

    public function __construct(private readonly PDO $database)
    {
        $this->workspaces = new WorkspaceRepository($database);
    }

    public function create(int $userId, array $input): array
    {
        $name = is_string($input['name'] ?? null) ? trim($input['name']) : $input['name'] ?? null;
        $description = is_string($input['description'] ?? null)
            ? trim($input['description'])
            : $input['description'] ?? null;

        $errors = Validator::validate(
            ['name' => $name, 'description' => $description],
            [
                'name' => ['required', 'string', 'min:3', 'max:120'],
                'description' => ['string', 'max:2000'],
            ]
        );

        if ($errors !== []) {
            throw ApiException::validation($errors);
        }

        $baseSlug = strtolower((string) preg_replace('/[^a-z0-9]+/', '-', (string) $name));
        $baseSlug = trim($baseSlug, '-');
        if ($baseSlug === '') {
            $baseSlug = 'workspace';
        }
        $slug = substr($baseSlug, 0, 110) . '-' . bin2hex(random_bytes(5));

        try {
            $this->database->beginTransaction();
            $workspaceId = $this->workspaces->create(
                $name,
                $slug,
                $description === '' ? null : $description,
                $userId
            );
            $this->workspaces->addMember($workspaceId, $userId, 'owner');
            $this->database->commit();
        } catch (\Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }

        return $this->workspaces->findForUser($workspaceId, $userId) ?? [];
    }

    public function listForUser(int $userId): array
    {
        return $this->workspaces->listForUser($userId);
    }

    public function show(int $userId, int $workspaceId): array
    {
        $workspace = $this->workspaces->findForUser($workspaceId, $userId);

        if ($workspace === null) {
            throw new ApiException('Workspace not found.', 404);
        }

        return $workspace;
    }

    public function members(int $userId, int $workspaceId): array
    {
        $this->show($userId, $workspaceId);

        return $this->workspaces->listMembers($workspaceId);
    }

    public function addMember(int $actorId, int $workspaceId, array $input): array
    {
        $email = is_string($input['email'] ?? null)
            ? strtolower(trim($input['email']))
            : $input['email'] ?? null;
        $role = $input['role'] ?? 'member';

        $errors = Validator::validate(
            ['email' => $email, 'role' => $role],
            [
                'email' => ['required', 'string', 'email', 'max:180'],
                'role' => ['required', 'string', 'in:admin,member'],
            ]
        );

        if ($errors !== []) {
            throw ApiException::validation($errors);
        }

        $actor = $this->requireMembership($actorId, $workspaceId);
        if ($actor['role'] !== 'owner' && ($actor['role'] !== 'admin' || $role !== 'member')) {
            throw new ApiException('You do not have permission to add a member with this role.', 403);
        }

        $user = $this->workspaces->findActiveUserByEmail($email);
        if ($user === null) {
            throw new ApiException('No active account was found for this email.', 404);
        }

        if ($this->workspaces->findMembership($workspaceId, (int) $user['id']) !== null) {
            throw new ApiException('This user is already a workspace member.', 409);
        }

        try {
            $this->workspaces->addMember($workspaceId, (int) $user['id'], $role);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new ApiException('This user is already a workspace member.', 409);
            }
            throw $exception;
        }

        return [
            'user' => $user,
            'role' => $role,
        ];
    }

    public function updateMemberRole(int $actorId, int $workspaceId, int $targetUserId, array $input): array
    {
        $role = $input['role'] ?? null;
        $errors = Validator::validate(
            ['role' => $role],
            ['role' => ['required', 'string', 'in:admin,member']]
        );

        if ($errors !== []) {
            throw ApiException::validation($errors);
        }

        $actor = $this->requireMembership($actorId, $workspaceId);
        $target = $this->requireMembership($targetUserId, $workspaceId);

        if ($actor['role'] !== 'owner') {
            if ($actor['role'] !== 'admin' || $target['role'] !== 'member' || $role !== 'member') {
                throw new ApiException('Only the workspace owner can assign administrator roles.', 403);
            }
        }

        if ($target['role'] === 'owner') {
            throw new ApiException('The workspace owner role cannot be changed here.', 403);
        }

        $this->workspaces->updateMemberRole($workspaceId, $targetUserId, $role);

        return [
            'user_id' => $targetUserId,
            'role' => $role,
        ];
    }

    public function removeMember(int $actorId, int $workspaceId, int $targetUserId): void
    {
        $actor = $this->requireMembership($actorId, $workspaceId);
        $target = $this->requireMembership($targetUserId, $workspaceId);

        if ($target['role'] === 'owner') {
            throw new ApiException('The workspace owner cannot be removed.', 403);
        }

        if ($actor['role'] !== 'owner') {
            if ($actor['role'] !== 'admin' || $target['role'] !== 'member') {
                throw new ApiException('You do not have permission to remove this member.', 403);
            }
        }

        $this->workspaces->removeMember($workspaceId, $targetUserId);
    }

    private function requireMembership(int $userId, int $workspaceId): array
    {
        $membership = $this->workspaces->findMembership($workspaceId, $userId);

        if ($membership === null) {
            throw new ApiException('Workspace not found.', 404);
        }

        return $membership;
    }
}
