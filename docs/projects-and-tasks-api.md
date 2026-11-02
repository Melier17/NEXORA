# NEXORA — Projects and tasks API

All endpoints use the /api/v1 prefix and require an active Bearer token.

## Projects

| Method | Endpoint | Permission |
|---|---|---|
| GET | /workspaces/{workspaceId}/projects | Workspace member |
| POST | /workspaces/{workspaceId}/projects | Owner or admin |
| GET | /workspaces/{workspaceId}/projects/{projectId} | Workspace member |
| PATCH | /workspaces/{workspaceId}/projects/{projectId} | Owner or admin |

Project fields: name (3–160 characters), description, status (planning, active, on_hold, completed, archived), start_date and due_date. Dates use YYYY-MM-DD.

## Tasks

| Method | Endpoint | Permission |
|---|---|---|
| GET | /workspaces/{workspaceId}/projects/{projectId}/tasks | Workspace member |
| POST | /workspaces/{workspaceId}/projects/{projectId}/tasks | Workspace member |
| PATCH | /workspaces/{workspaceId}/tasks/{taskId} | Owner/admin, creator or assignee |
| DELETE | /workspaces/{workspaceId}/tasks/{taskId} | Owner/admin or creator |

Task fields: title (3–180 characters), description, status (todo, in_progress, review, done), priority (low, medium, high, urgent), assigned_to and due_date. Task due_date uses YYYY-MM-DD HH:MM:SS. An assignee must be a member of the same workspace.

## Authorization and validation

Every project and task lookup is scoped to the requested workspace. A user who is not a member receives a 404 response for that workspace. Project creation and updates are restricted to owners and admins. Task assignment checks workspace membership.

These endpoints are committed to the repository but have not yet been executed against a local PHP/MySQL environment. Apply migrations in numerical order and run the test suite before treating the API as verified.
