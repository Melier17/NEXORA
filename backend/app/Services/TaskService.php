<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Repositories\ProjectRepository;
use App\Repositories\TaskRepository;
use App\Repositories\WorkspaceRepository;
use App\Validators\Validator;
use DateTimeImmutable;
use PDO;

final class TaskService
{
    private TaskRepository $tasks;
    private ProjectRepository $projects;
    private WorkspaceRepository $workspaces;

    public function __construct(private readonly PDO $database)
    {
        $this->tasks = new TaskRepository($database);
        $this->projects = new ProjectRepository($database);
        $this->workspaces = new WorkspaceRepository($database);
    }

    public function list(int $userId, int $workspaceId, int $projectId): array
    {
        $this->requireMembership($userId, $workspaceId);
        $this->requireProject($workspaceId, $projectId);
        return $this->tasks->listForProject($projectId);
    }

    public function create(int $userId, int $workspaceId, int $projectId, array $input): array
    {
        $this->requireMembership($userId, $workspaceId);
        $this->requireProject($workspaceId, $projectId);
        $data = $this->validatedData($input, false);
        $this->validateAssignee($workspaceId, $data['assigned_to']);

        $taskId = $this->tasks->create($projectId, $userId, $data);
        return $this->requireTask($workspaceId, $taskId);
    }

    public function update(int $userId, int $workspaceId, int $taskId, array $input): array
    {
        $membership = $this->requireMembership($userId, $workspaceId);
        $task = $this->requireTask($workspaceId, $taskId);

        $canManage = in_array($membership['role'], ['owner', 'admin'], true)
            || (int) $task['created_by'] === $userId
            || (int) ($task['assigned_to'] ?? 0) === $userId;

        if (!$canManage) {
            throw new ApiException('You do not have permission to update this task.', 403);
        }

        $data = $this->validatedData($input, true);
        if ($data === []) {
            throw new ApiException('Provide at least one task field to update.', 422);
        }

        if (array_key_exists('assigned_to', $data)) {
            $this->validateAssignee($workspaceId, $data['assigned_to']);
        }

        $this->tasks->update($taskId, $data);
        return $this->requireTask($workspaceId, $taskId);
    }

    public function delete(int $userId, int $workspaceId, int $taskId): void
    {
        $membership = $this->requireMembership($userId, $workspaceId);
        $task = $this->requireTask($workspaceId, $taskId);

        $canManage = in_array($membership['role'], ['owner', 'admin'], true)
            || (int) $task['created_by'] === $userId;

        if (!$canManage) {
            throw new ApiException('Only workspace managers or the task creator can delete this task.', 403);
        }

        $this->tasks->delete($taskId);
    }

    private function validatedData(array $input, bool $partial): array
    {
        $rules = [
            'title' => [$partial ? 'string' : 'required', 'min:3', 'max:180'],
            'description' => ['string', 'max:5000'],
            'status' => ['string', 'in:todo,in_progress,review,done'],
            'priority' => ['string', 'in:low,medium,high,urgent'],
            'assigned_to' => ['integer'],
            'due_date' => ['string'],
        ];
        $errors = Validator::validate($input, $rules);

        if (array_key_exists('assigned_to', $input) && $input['assigned_to'] !== null && $input['assigned_to'] !== '') {
            if (filter_var($input['assigned_to'], FILTER_VALIDATE_INT) === false || (int) $input['assigned_to'] < 1) {
                $errors['assigned_to'][] = 'Select a valid workspace member.';
            }
        }

        if (array_key_exists('due_date', $input) && $input['due_date'] !== null && $input['due_date'] !== '') {
            if (!is_string($input['due_date']) || !$this->isDateTime($input['due_date'])) {
                $errors['due_date'][] = 'Use a valid date and time in YYYY-MM-DD HH:MM:SS format.';
            }
        }

        if ($errors !== []) {
            throw ApiException::validation($errors);
        }

        $data = [];
        foreach (['title', 'description', 'status', 'priority', 'assigned_to', 'due_date'] as $field) {
            if (array_key_exists($field, $input)) {
                $value = $input[$field];
                if ($field === 'title' && is_string($value)) {
                    $value = trim($value);
                }
                $data[$field] = $value === '' ? null : $value;
            }
        }

        if (!$partial) {
            $data += [
                'title' => trim((string) $input['title']),
                'description' => null,
                'status' => 'todo',
                'priority' => 'medium',
                'assigned_to' => null,
                'due_date' => null,
            ];
        }

        return $data;
    }

    private function isDateTime(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
        return $date !== false && $date->format('Y-m-d H:i:s') === $value;
    }

    private function validateAssignee(int $workspaceId, mixed $assigneeId): void
    {
        if ($assigneeId === null) {
            return;
        }

        $membership = $this->workspaces->findMembership($workspaceId, (int) $assigneeId);
        if ($membership === null) {
            throw ApiException::validation([
                'assigned_to' => ['The assignee must be a member of this workspace.'],
            ]);
        }
    }

    private function requireMembership(int $userId, int $workspaceId): array
    {
        $membership = $this->workspaces->findMembership($workspaceId, $userId);
        if ($membership === null) {
            throw new ApiException('Workspace not found.', 404);
        }
        return $membership;
    }

    private function requireProject(int $workspaceId, int $projectId): array
    {
        $project = $this->projects->findForWorkspace($projectId, $workspaceId);
        if ($project === null) {
            throw new ApiException('Project not found.', 404);
        }
        return $project;
    }

    private function requireTask(int $workspaceId, int $taskId): array
    {
        $task = $this->tasks->findForWorkspace($taskId, $workspaceId);
        if ($task === null) {
            throw new ApiException('Task not found.', 404);
        }
        return $task;
    }
}
