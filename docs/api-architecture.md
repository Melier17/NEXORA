# NEXORA API Architecture

## API prefix

Versioned endpoints use the prefix `/api/v1`.

## Request flow

1. Apache forwards non-file requests to `backend/public/index.php`.
2. Composer loads the PSR-4 classes.
3. Dotenv loads local environment variables.
4. The request abstraction normalizes the HTTP method, path, headers, query, and JSON body.
5. The router matches the method and route.
6. A controller validates input and delegates business work to services.
7. A response object sends JSON with an HTTP status code.

## Core components

- `App\\Core\\Request`: captures and normalizes an HTTP request.
- `App\\Core\\Router`: registers routes and dispatches matching requests.
- `App\\Core\\Response`: sends JSON responses with status and headers.
- `App\\Core\\ApiException`: represents expected API errors with an HTTP status and optional field errors.
- `App\\Validators\\Validator`: checks common input rules and returns field-level errors.
- `App\\Repositories\\UserRepository`: reads and creates user records with prepared statements.
- `App\\Services\\AuthService`: coordinates registration, login, and account lookup.
- `App\\Services\\TokenService`: issues, validates, and revokes opaque bearer tokens.
- `App\\Repositories\\WorkspaceRepository`: performs workspace and membership database operations.
- `App\\Services\\WorkspaceService`: enforces workspace membership and role permissions.
- `App\\Controllers\\WorkspaceController`: exposes workspace endpoints.
- `App\\Middleware\\ErrorHandler`: maps known API errors to safe JSON responses and logs unexpected exceptions.

## Authentication endpoints

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/v1/auth/register` | Create an account |
| POST | `/api/v1/auth/login` | Sign in and issue an access token |
| GET | `/api/v1/auth/me` | Return the authenticated user |
| POST | `/api/v1/auth/logout` | Revoke the presented access token |

Registration expects a JSON object with `name`, `email`, `password`, and `password_confirmation`. Passwords must contain at least 12 characters and are hashed with PHP's `password_hash()`. Login uses `password_verify()`.

Protected requests send `Authorization: Bearer <token>`. Tokens are cryptographically random opaque values. The database stores only the SHA-256 hash, along with the user, expiry, and revocation timestamp. The default lifetime is 60 minutes, configurable through `ACCESS_TOKEN_TTL_MINUTES`.

The authentication migration `002_create_access_tokens.sql` must be applied after the initial schema migration. The legacy `refresh_tokens` table is reserved for a future refresh-token flow and is not used by this initial access-token implementation.

## Workspace and role endpoints

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/v1/workspaces` | List the caller's workspaces |
| POST | `/api/v1/workspaces` | Create a workspace; creator becomes owner |
| GET | `/api/v1/workspaces/{workspaceId}` | View a workspace |
| GET | `/api/v1/workspaces/{workspaceId}/members` | List workspace members |
| POST | `/api/v1/workspaces/{workspaceId}/members` | Add an existing user by email |
| PATCH | `/api/v1/workspaces/{workspaceId}/members/{userId}` | Change a member's role |
| DELETE | `/api/v1/workspaces/{workspaceId}/members/{userId}` | Remove a member |

All workspace routes require a valid Bearer token and membership in the requested workspace. A workspace creator receives the `owner` role in the same database transaction that creates the workspace.

Role rules:

- **Owner:** can add admins or members, change non-owner roles, and remove admins or members.
- **Admin:** can add and remove regular members, but cannot grant or remove administrator privileges.
- **Member:** can view their workspace and its member list.

The owner cannot be demoted or removed through these endpoints. Only existing active user accounts can be added. Email invitations, ownership transfer, and pending invitation acceptance are future features.

## Validation and errors

The validator supports `required`, `string`, `email`, `integer`, `min:N`, `max:N`, and `in:value1,value2`.

Validation failures return HTTP 422 with field-level messages. Invalid credentials return HTTP 401. Duplicate registration email or duplicate membership returns HTTP 409. A user without workspace membership receives HTTP 404 for that workspace, avoiding disclosure of inaccessible workspace records. Permission failures return HTTP 403. Unexpected exceptions return HTTP 500 with a generic message. Detailed exception information is only included when `APP_DEBUG=true`; keep debug mode disabled in production.

## Security notes

- Never return password hashes, token hashes, secrets, or raw exception traces to clients.
- Use parameterized SQL through PDO.
- Access tokens are stored as hashes and can be revoked on logout.
- Check workspace membership and role on every workspace operation.
- Authentication and workspace endpoints still need production rate limiting and security testing.
- Configure the production web root to `backend/public`, not the repository root.
