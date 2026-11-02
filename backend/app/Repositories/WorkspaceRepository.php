<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class WorkspaceRepository
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function create(string $name, string $slug, ?string $description, int $ownerId): int
    {
        $statement = $this->database->prepare(
            'INSERT INTO workspaces (name, slug, description, created_by)
             VALUES (:name, :slug, :description, :created_by)'
        );
        $statement->execute([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'created_by' => $ownerId,
        ]);

        return (int) $this->database->lastInsertId();
    }

    public function addMember(int $workspaceId, int $userId, string $role): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO workspace_members (workspace_id, user_id, role)
             VALUES (:workspace_id, :user_id, :role)'
        );
        $statement->execute([
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'role' => $role,
        ]);
    }

    public function findMembership(int $workspaceId, int $userId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT workspace_id, user_id, role, joined_at
             FROM workspace_members
             WHERE workspace_id = :workspace_id AND user_id = :user_id
             LIMIT 1'
        );
        $statement->execute([
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
        ]);
        $membership = $statement->fetch();

        return $membership === false ? null : $membership;
    }

    public function listForUser(int $userId): array
    {
        $statement = $this->database->prepare(
            'SELECT w.id, w.name, w.slug, w.description, w.created_at,
                    wm.role, wm.joined_at
             FROM workspaces AS w
             INNER JOIN workspace_members AS wm ON wm.workspace_id = w.id
             WHERE wm.user_id = :user_id
             ORDER BY w.created_at DESC'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public function findForUser(int $workspaceId, int $userId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT w.id, w.name, w.slug, w.description, w.created_by, w.created_at,
                    wm.role, wm.joined_at
             FROM workspaces AS w
             INNER JOIN workspace_members AS wm ON wm.workspace_id = w.id
             WHERE w.id = :workspace_id AND wm.user_id = :user_id
             LIMIT 1'
        );
        $statement->execute([
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
        ]);
        $workspace = $statement->fetch();

        return $workspace === false ? null : $workspace;
    }

    public function listMembers(int $workspaceId): array
    {
        $statement = $this->database->prepare(
            'SELECT u.id, u.name, u.email, wm.role, wm.joined_at
             FROM workspace_members AS wm
             INNER JOIN users AS u ON u.id = wm.user_id
             WHERE wm.workspace_id = :workspace_id
             ORDER BY FIELD(wm.role, "owner", "admin", "member"), u.name'
        );
        $statement->execute(['workspace_id' => $workspaceId]);

        return $statement->fetchAll();
    }

    public function findActiveUserByEmail(string $email): ?array
    {
        $statement = $this->database->prepare(
            'SELECT id, name, email
             FROM users
             WHERE email = :email AND status = "active"
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function updateMemberRole(int $workspaceId, int $userId, string $role): void
    {
        $statement = $this->database->prepare(
            'UPDATE workspace_members
             SET role = :role
             WHERE workspace_id = :workspace_id AND user_id = :user_id'
        );
        $statement->execute([
            'role' => $role,
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
        ]);
    }

    public function removeMember(int $workspaceId, int $userId): void
    {
        $statement = $this->database->prepare(
            'DELETE FROM workspace_members
             WHERE workspace_id = :workspace_id AND user_id = :user_id'
        );
        $statement->execute([
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
        ]);
    }
}
