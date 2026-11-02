<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Repositories\ProjectRepository;
use App\Repositories\WorkspaceRepository;
use App\Validators\Validator;
use DateTimeImmutable;
use PDO;

final class ProjectService
{
    private ProjectRepository $projects;
    private WorkspaceRepository $workspaces;

    public function __construct(private readonly PDO $database)
    {
        $this->projects = new ProjectRepository($database);
        $this->workspaces = new WorkspaceRepository($database);
    }

    public function list(int $userId, int $workspaceId): array
    {
        $this->requireMembership($userId, $workspaceId);
        return $this->projects->listForWorkspace($workspaceId);
    }

    public function show(int $userId, int $workspaceId, int $projectId): array
    {
        $this->requireMembership($userId, $workspaceId);
        return $this->requireProject($workspaceId, $projectId);
    }

    public function create(int $userId, int $workspaceId, array $input): array
    {
        $membership = $this->requireMembership($userId, $workspaceId);
        $this->requireManager($membership);

        $data = $this->validatedData($input, false);
        $projectId = $this->projects->create($workspaceId, $userId, $data);
        $this->logActivity($workspaceId, $userId, $projectId, 'project.created', ['name' => $data['name']]);

        return $this->requireProject($workspaceId, $projectId);
    }

    public function update(int $userId, int $workspaceId, int $projectId, array $input): array
    {
        $membership = $this->requireMembership($userId, $workspaceId);
        $this->requireManager($membership);
        $this->requireProject($workspaceId, $projectId);

        $data = $this->validatedData($input, true);
        if ($data === []) {
            throw new ApiException('Provide at least one project field to update.', 422);
        }

        $this->projects->update($projectId, $workspaceId, $data);
        $this->logActivity($workspaceId, $userId, $projectId, 'project.updated', ['fields' => array_keys($data)]);

        return $this->requireProject($workspaceId, $projectId);
    }

    private function logActivity(int $workspaceId, int $userId, int $projectId, string $action, array $metadata): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO activity_logs (workspace_id, actor_user_id, entity_type, entity_id, action, metadata)
             VALUES (:workspace_id, :actor_user_id, "project", :entity_id, :action, :metadata)'
        );
        $statement->execute([
            'workspace_id' => $workspaceId,
            'actor_user_id' => $userId,
            'entity_id' => $projectId,
            'action' => $action,
            'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
    }

    private function validatedData(array $input, bool $partial): array
    {
        $rules = [
            'name' => [$partial ? 'string' : 'required', 'string', 'min:3', 'max:160'],

            'description' => ['string', 'max:4000'],
            'status' => ['string', 'in:planning,active,on_hold,completed,archived'],
            'start_date' => ['string'],
            'due_date' => ['string'],
        ];
        $errors = Validator::validate($input, $rules);

        if (array_key_exists('name', $input) && (!is_string($input['name']) || trim($input['name']) === '')) {
            $errors['name'][] = 'Project name must be a non-empty string.';
        }

        foreach (['start_date', 'due_date'] as $field) {
            if (array_key_exists($field, $input) && $input[$field] !== null && $input[$field] !== '') {
                if (!is_string($input[$field]) || !$this->isDate($input[$field])) {
                    $errors[$field][] = 'Use a valid date in YYYY-MM-DD format.';
                }
            }
        }

        $start = $input['start_date'] ?? null;
        $due = $input['due_date'] ?? null;
        if (is_string($start) && is_string($due) && $start !== '' && $due !== '' && $start > $due) {
            $errors['due_date'][] = 'The due date cannot be earlier than the start date.';
        }

        if ($errors !== []) {
            throw ApiException::validation($errors);
        }

        $data = [];
        foreach (['name', 'description', 'status', 'start_date', 'due_date'] as $field) {
            if (array_key_exists($field, $input)) {
                $value = $input[$field];
                $data[$field] = $value === '' ? null : $value;
            }
        }

        if (!$partial) {
            $data['name'] = trim((string) $data['name']);
            $data += [
                'name' => trim((string) $input['name']),
                'description' => null,
                'status' => 'planning',
                'start_date' => null,
                'due_date' => null,
            ];
        } elseif (isset($data['name'])) {
            $data['name'] = trim((string) $data['name']);
        }

        return $data;
    }

    private function isDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function requireMembership(int $userId, int $workspaceId): array
    {
        $membership = $this->workspaces->findMembership($workspaceId, $userId);
        if ($membership === null) {
            throw new ApiException('Workspace not found.', 404);
        }
        return $membership;
    }

    private function requireManager(array $membership): void
    {
        if (!in_array($membership['role'], ['owner', 'admin'], true)) {
            throw new ApiException('Only workspace owners and admins can manage projects.', 403);
        }
    }

    private function requireProject(int $workspaceId, int $projectId): array
    {
        $project = $this->projects->findForWorkspace($projectId, $workspaceId);
        if ($project === null) {
            throw new ApiException('Project not found.', 404);
        }
        return $project;
    }
}
