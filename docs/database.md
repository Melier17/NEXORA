# NEXORA Database Design

Target database: MySQL 8.0.16+ with InnoDB and utf8mb4.

The initial schema is defined in `backend/database/migrations/001_create_core_tables.sql`. It is a draft until executed and verified against a local MySQL instance.

## Tables

| Table | Purpose |
|---|---|
| users | User accounts and password hashes |
| workspaces | Team workspaces |
| workspace_members | Membership and workspace roles |
| projects | Projects inside a workspace |
| tasks | Work items belonging to projects |
| labels | Workspace-specific labels |
| task_labels | Many-to-many task/label relationship |
| comments | User comments on tasks |
| activity_logs | Workspace activity history |
| refresh_tokens | Hashed refresh-token records |

## Main relationships

- A user can create multiple workspaces.
- Users and workspaces have a many-to-many relationship through workspace_members.
- A workspace contains multiple projects.
- A project contains multiple tasks.
- A task can be assigned to a user.
- Tasks and labels have a many-to-many relationship through task_labels.
- A task can have multiple comments.
- A workspace can have multiple activity log entries.
- A user can have multiple refresh-token records.

## Important authorization rule

Foreign keys protect referential integrity, but they do not prove that a user belongs to the same workspace as a project, task, or assignee. The API must verify workspace membership and permissions on every relevant operation. This validation belongs in the application/service layer.

## Migration policy

- Migrations are versioned and applied in order.
- Do not run the initial migration repeatedly on an existing database.
- Test migrations against a disposable development database first.
- Add a migration-tracking table before introducing automated migration execution.
