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
4. Apply `database/migrations/001_create_core_tables.sql` to the development database.
5. Configure Apache so the web document root points to `backend/public`.
6. Request `/api/v1/health` to check the API entry point.
7. Run `composer test` to execute the unit tests.

Never commit the real `.env` file.

## Current implementation status

- Composer configuration and PSR-4 autoloading: scaffolded.
- Environment loading: scaffolded with phpdotenv.
- PDO connection factory: scaffolded; local connection not yet verified.
- HTTP request abstraction, JSON response class, and router: scaffolded.
- Centralized exception handler with expected API error mapping: scaffolded.
- Reusable input validator: scaffolded with unit tests written; tests not yet executed.
- PHPUnit configuration: added; dependencies not yet installed locally.
- Health endpoint: scaffolded; local HTTP response not yet verified.
- Initial schema migration: drafted; not yet executed against MySQL.
- Authentication and business endpoints: not implemented yet.

The backend source code is kept outside the public document root. Only files inside `public/` should be served by Apache.

## API endpoint

- `GET /api/v1/health` — basic API status response.

See [API architecture](../docs/api-architecture.md) for request flow, validation rules, and response conventions.
