# NEXORA Backend

PHP REST API foundation for NEXORA.

## Requirements

- PHP 8.3+
- MySQL 8.0.16+
- Composer
- Apache with `mod_rewrite` enabled (for the included XAMPP setup)

Required PHP extensions include PDO and PDO MySQL.

## Local setup

1. Create a MySQL database named `nexora_db` using `utf8mb4`.
2. Copy `.env.example` to `.env` and adjust local values.
3. Install PHP dependencies from this directory with `composer install`.
4. Apply the SQL migration in `database/migrations/001_create_core_tables.sql`.
5. Configure Apache so the web document root points to `backend/public`.
6. Request the `/health` endpoint to check the API entry point.

Never commit the real `.env` file.

## Current implementation status

- Composer configuration and PSR-4 autoloading: scaffolded.
- Environment loading: scaffolded with phpdotenv.
- PDO connection factory: scaffolded; local connection not yet verified.
- Health endpoint: scaffolded; local HTTP response not yet verified.
- Initial schema migration: drafted; not yet executed against MySQL.
- Authentication and business endpoints: not implemented yet.

The backend source code is kept outside the public document root. Only files inside `public/` should be served by Apache.
