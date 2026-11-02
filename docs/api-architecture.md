# NEXORA API Architecture

## API prefix

Versioned endpoints use the prefix `/api/v1`.

The initial endpoint is:

- `GET /api/v1/health` — returns API status information.

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
- `App\\Middleware\\ErrorHandler`: logs exceptions and maps known API errors to safe JSON responses.
- `App\\Controllers\\HealthController`: handles the initial health endpoint.

## Validation

The validator supports:

- `required`
- `string`
- `email`
- `integer`
- `min:N` and `max:N` for string length or numeric value
- `in:value1,value2`

Example:

```php
$errors = Validator::validate($request->body(), [
    'name' => ['required', 'string', 'min:2', 'max:100'],
    'email' => ['required', 'email'],
]);

if ($errors !== []) {
    throw ApiException::validation($errors);
}
```

Validation is an application-level check; database constraints and authorization checks are still required.

## JSON response convention

Successful response:

```json
{
  "success": true,
  "message": "Operation completed.",
  "data": {}
}
```

Validation error (HTTP 422):

```json
{
  "success": false,
  "message": "The submitted data is invalid.",
  "errors": {
    "email": ["Enter a valid email address."]
  }
}
```

Other expected API errors use the status code supplied to `ApiException`. Unexpected exceptions return HTTP 500 with a generic message. Detailed exception information is only included when `APP_DEBUG=true`; keep debug mode disabled in production.

## Security notes

- Controllers must not assume that a submitted ID belongs to the authenticated user.
- Authorization must be checked for every workspace and project resource.
- Never return password hashes, token hashes, secrets, or raw exception traces to clients.
- Validate request data before calling services or repositories.
- Use parameterized SQL through PDO.
- Configure the production web root to `backend/public`, not the repository root.
