# Comments, labels and activity API

All routes require `Authorization: Bearer <access_token>`. The user must belong to the workspace addressed by `workspaceId`.

## Comments
- `GET /api/v1/workspaces/{workspaceId}/tasks/{taskId}/comments`
- `POST /api/v1/workspaces/{workspaceId}/tasks/{taskId}/comments` — JSON: `{"body":"Comment text"}`
- `DELETE /api/v1/workspaces/{workspaceId}/comments/{commentId}`

Workspace members can read and add comments. A comment author can delete their own comment; workspace owners/admins can delete any comment. Comment text is trimmed and limited to 5,000 characters.

## Labels
- `GET /api/v1/workspaces/{workspaceId}/labels`
- `POST /api/v1/workspaces/{workspaceId}/labels` — JSON: `{"name":"Backend","color":"#64748b"}`
- `PATCH /api/v1/workspaces/{workspaceId}/labels/{labelId}`
- `DELETE /api/v1/workspaces/{workspaceId}/labels/{labelId}`
- `GET /api/v1/workspaces/{workspaceId}/tasks/{taskId}/labels`
- `POST /api/v1/workspaces/{workspaceId}/tasks/{taskId}/labels` — JSON: `{"label_id":3}`
- `DELETE /api/v1/workspaces/{workspaceId}/tasks/{taskId}/labels/{labelId}`

All workspace members can list labels and assign/remove labels on tasks. Only owners/admins can create, edit or delete workspace labels. Names are unique within a workspace. Colors must be six-digit hexadecimal values. Deleting a label removes its task associations through the database foreign key cascade.

## Activity
- `GET /api/v1/workspaces/{workspaceId}/activity?limit=20&before_id=100`

The endpoint returns newest activity first. `limit` defaults to 20 and accepts 1–100. `before_id` is an optional cursor for older records. The response decodes JSON metadata into an object.

Activity is recorded for project creation/updates, task creation/updates/deletion, comment creation/deletion, label creation/edit/deletion, and label assignment/removal. Workspace creation and membership changes are not yet automatically logged.

## Database
The tables `comments`, `labels`, `task_labels` and `activity_logs` are already defined in `backend/database/migrations/001_create_core_tables.sql`. Apply migration 001 to a new database before using these endpoints. No additional migration is required for this module.

## Verification status
Code and routes are committed to the repository, but the endpoints have not yet been run against a live PHP/MySQL environment. Treat them as implemented but unverified until local/API tests pass.
