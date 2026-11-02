<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ProjectRepository
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function create(int $workspaceId, int $userId, array $data): int
    {
        $statement = $this->database->prepare(
            'INSERT INTO projects (workspace_id, created_by, name, description, status, start_date, due_date)
             VALUES (:workspace_id, :created_by, :name, :description, :status, :start_date, :due_date)'
        );
        $statement->execute([
            'workspace_id' => $workspaceId,
            'created_by' => $userId,
            'name' => $data['name'],
            'description' => $data['description'],
            'status' => $data['status'],
            'start_date' => $data['start_date'],
            'due_date' => $data['due_date'],
        ]);

        return (int) $this->database->lastInsertId();
    }

    public function listForWorkspace(int $workspaceId): array
    {
        $statement = $this->database->prepare(
            'SELECT p.id, p.workspace_id, p.created_by, p.name, p.description, p.status,
                    p.start_date, p.due_date, p.created_at, p.updated_at,
                    u.name AS creator_name,
                    (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) AS task_count
             FROM projects p
             INNER JOIN users u ON u.id = p.created_by
             WHERE p.workspace_id = :workspace_id
             ORDER BY p.updated_at DESC, p.id DESC'
        );
        $statement->execute(['workspace_id' => $workspaceId]);

        return $statement->fetchAll();
    }

    public function findForWorkspace(int $projectId, int $workspaceId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT p.id, p.workspace_id, p.created_by, p.name, p.description, p.status,
                    p.start_date, p.due_date, p.created_at, p.updated_at,
                    u.name AS creator_name,
                    (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) AS task_count
             FROM projects p
             INNER JOIN users u ON u.id = p.created_by
             WHERE p.id = :project_id AND p.workspace_id = :workspace_id
             LIMIT 1'
        );
        $statement->execute([
            'project_id' => $projectId,
            'workspace_id' => $workspaceId,
        ]);
        $project = $statement->fetch();

        return $project === false ? null : $project;
    }

    public function update(int $projectId, int $workspaceId, array $data): void
    {
        $sets = [];
        $parameters = [
            'project_id' => $projectId,
            'workspace_id' => $workspaceId,
        ];

        foreach (['name', 'description', 'status', 'start_date', 'due_date'] as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = $field . ' = :' . $field;
                $parameters[$field] = $data[$field];
            }
        }

        if ($sets === []) {
            return;
        }

        $statement = $this->database->prepare(
            'UPDATE projects SET ' . implode(', ', $sets) .
            ' WHERE id = :project_id AND workspace_id = :workspace_id'
        );
        $statement->execute($parameters);
    }
}
