<?php

declare(strict_types=1);

use App\Config\Database;
use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Controllers\ProjectController;
use App\Controllers\TaskController;
use App\Controllers\WorkspaceController;
use App\Core\Request;
use App\Core\Router;

$router = new Router();

$healthController = new HealthController();
$router->get('/api/v1/health', [$healthController, 'show']);

// Database connections are created only when an endpoint is requested.
$authController = static fn (): AuthController => new AuthController(Database::connect());
$workspaceController = static fn (): WorkspaceController => new WorkspaceController(Database::connect());
$projectController = static fn (): ProjectController => new ProjectController(Database::connect());
$taskController = static fn (): TaskController => new TaskController(Database::connect());

$router->post('/api/v1/auth/register', static fn (Request $request) => $authController()->register($request));
$router->post('/api/v1/auth/login', static fn (Request $request) => $authController()->login($request));
$router->get('/api/v1/auth/me', static fn (Request $request) => $authController()->me($request));
$router->post('/api/v1/auth/logout', static fn (Request $request) => $authController()->logout($request));

$router->get('/api/v1/workspaces', static fn (Request $request) => $workspaceController()->index($request));
$router->post('/api/v1/workspaces', static fn (Request $request) => $workspaceController()->store($request));
$router->get('/api/v1/workspaces/{workspaceId}', static fn (Request $request, array $parameters) => $workspaceController()->show($request, $parameters));
$router->get('/api/v1/workspaces/{workspaceId}/members', static fn (Request $request, array $parameters) => $workspaceController()->members($request, $parameters));
$router->post('/api/v1/workspaces/{workspaceId}/members', static fn (Request $request, array $parameters) => $workspaceController()->addMember($request, $parameters));
$router->patch('/api/v1/workspaces/{workspaceId}/members/{userId}', static fn (Request $request, array $parameters) => $workspaceController()->updateMemberRole($request, $parameters));
$router->delete('/api/v1/workspaces/{workspaceId}/members/{userId}', static fn (Request $request, array $parameters) => $workspaceController()->removeMember($request, $parameters));

$router->get('/api/v1/workspaces/{workspaceId}/projects', static fn (Request $request, array $parameters) => $projectController()->index($request, $parameters));
$router->post('/api/v1/workspaces/{workspaceId}/projects', static fn (Request $request, array $parameters) => $projectController()->store($request, $parameters));
$router->get('/api/v1/workspaces/{workspaceId}/projects/{projectId}', static fn (Request $request, array $parameters) => $projectController()->show($request, $parameters));
$router->patch('/api/v1/workspaces/{workspaceId}/projects/{projectId}', static fn (Request $request, array $parameters) => $projectController()->update($request, $parameters));

$router->get('/api/v1/workspaces/{workspaceId}/projects/{projectId}/tasks', static fn (Request $request, array $parameters) => $taskController()->index($request, $parameters));
$router->post('/api/v1/workspaces/{workspaceId}/projects/{projectId}/tasks', static fn (Request $request, array $parameters) => $taskController()->store($request, $parameters));
$router->patch('/api/v1/workspaces/{workspaceId}/tasks/{taskId}', static fn (Request $request, array $parameters) => $taskController()->update($request, $parameters));
$router->delete('/api/v1/workspaces/{workspaceId}/tasks/{taskId}', static fn (Request $request, array $parameters) => $taskController()->destroy($request, $parameters));

return $router;
