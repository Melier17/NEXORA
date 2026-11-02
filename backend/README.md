# NEXORA Backend

PHP REST API foundation for NEXORA.

## Requirements

- PHP 8.3+
- MySQL 8.0.16+
- Composer
- Apache with `mod_rewrite` enabled (for the included XAMPP setup)
- PHP extensions: PDO, PDO MySQL, OpenSSL, and mbstring

## Local setup

1. Create a MySQL database named `nexora_db` using `utf8mb4`.
2. Copy `.env.example` to `.env` and adjust local values.
3. From this directory, run `composer install`.
4. Apply the SQL migrations in `database/migrations/` in numerical order.
5. Configure Apache so the web document root points to `backend/public`.
6. Request `/api/v1/health` to check the API entry point.
7. Run `composer test` to execute the unit tests.

Never commit the real `.env` file.

## Authentication endpoints

All endpoints use the `/api/v1` prefix and JSON request bodies.

- `POST /api/v1/auth/register` — create an account using `name`, `email`, `password`, and `password_confirmation`.
- `POST /api/v1/auth/login` — authenticate with `email` and `password`.
- `GET /api/v1/auth/me` — return the current account.
- `POST /api/v1/auth/logout` — revoke the current access token.

Send the access token in the `Authorization: Bearer <token>` header for the `me` and `logout` endpoints. Access tokens are random opaque values; only their SHA-256 hashes are stored in the database. The default lifetime is 60 minutes and can be changed with `ACCESS_TOKEN_TTL_MINUTES` (minimum 5 minutes).

## Current implementation status

- Composer configuration and PSR-4 autoloading: scaffolded.
- Environment loading: scaffolded with phpdotenv.
- PDO connection factory: scaffolded; local connection not yet verified.
- HTTP request abstraction, JSON response class, router, and validation layer: scaffolded.
- Centralized exception handler with expected API error mapping: scaffolded.
- Authentication: registration, login, current-user lookup, and token revocation endpoints scaffolded.
- Passwords use PHP `password_hash()` and `password_verify()`.
- Access tokens are generated with `random_bytes()`; only SHA-256 hashes are stored.
- Authentication database migration: drafted; not yet executed against MySQL.
- Validator unit tests are written; tests have not yet been executed.
- Health and authentication endpoints: not yet verified locally.
- Email verification, password reset, refresh-token rotation, login rate limiting, and automated auth integration tests are not implemented yet.

The backend source code is kept outside the public document root. Only files inside `public/` should be served by Apache. Keep `APP_DEBUG=false` in production.

See [API architecture](../docs/api-architecture.md) for request flow, validation rules, and response conventions.
