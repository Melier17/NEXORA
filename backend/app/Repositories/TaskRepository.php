<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TaskRepository
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function create(int $projectId, int $userId, array $data): int
    {
        $statement = $this->database->prepare(
            'INSERT INTO tasks (project_id, created_by, assigned_to, title, description, status, priority, due_date)
             VALUES (:project_id, :created_by, :assigned_to, :title, :description, :status, :priority, :due_date)'
        );
        $statement->execute([
            'project_id' => $projectId,
            'created_by' => $userId,
            'assigned_to' => $data['assigned_to'],
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => $data['status'],
            'priority' => $data['priority'],
            'due_date' => $data['due_date'],
        ]);

        return (int) $this->database->lastInsertId();
    }

    public function listForProject(int $projectId): array
    {
        $statement = $this->database->prepare(
            'SELECT t.id, t.project_id, t.created_by, t.assigned_to, t.title, t.description,
                    t.status, t.priority, t.due_date, t.position, t.created_at, t.updated_at,
                    creator.name AS creator_name, assignee.name AS assignee_name
             FROM tasks t
             INNER JOIN users creator ON creator.id = t.created_by
             LEFT JOIN users assignee ON assignee.id = t.assigned_to
             WHERE t.project_id = :project_id
             ORDER BY t.position ASC, t.created_at DESC, t.id DESC'
        );
        $statement->execute(['project_id' => $projectId]);

        return $statement->fetchAll();
    }

    public function findForWorkspace(int $taskId, int $workspaceId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT t.id, t.project_id, t.created_by, t.assigned_to, t.title, t.description,
                    t.status, t.priority, t.due_date, t.position, t.created_at, t.updated_at,
                    p.workspace_id, creator.name AS creator_name, assignee.name AS assignee_name
             FROM tasks t
             INNER JOIN projects p ON p.id = t.project_id
             INNER JOIN users creator ON creator.id = t.created_by
             LEFT JOIN users assignee ON assignee.id = t.assigned_to
             WHERE t.id = :task_id AND p.workspace_id = :workspace_id
             LIMIT 1'
        );
        $statement->execute([
            'task_id' => $taskId,
            'workspace_id' => $workspaceId,
        ]);
        $task = $statement->fetch();

        return $task === false ? null : $task;
    }

    public function update(int $taskId, array $data): void
    {
        $sets = [];
        $parameters = ['task_id' => $taskId];

        foreach (['assigned_to', 'title', 'description', 'status', 'priority', 'due_date'] as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = $field . ' = :' . $field;
                $parameters[$field] = $data[$field];
            }
        }

        if ($sets === []) {
            return;
        }

        $statement = $this->database->prepare(
            'UPDATE tasks SET ' . implode(', ', $sets) . ' WHERE id = :task_id'
        );
        $statement->execute($parameters);
    }

    public function delete(int $taskId): void
    {
        $statement = $this->database->prepare('DELETE FROM tasks WHERE id = :task_id');
        $statement->execute(['task_id' => $taskId]);
    }
}
