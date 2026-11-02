# NEXORA API Architecture

## API prefix

Versioned endpoints use the prefix `/api/v1`.

The first registered endpoint is:

- `GET /api/v1/health` — returns API status information.

## Request flow

1. Apache forwards non-file requests to `backend/public/index.php`.
2. Composer loads the PSR-4 classes.
3. Dotenv loads local environment variables.
4. The request abstraction normalizes the HTTP method, path, headers, query, and JSON body.
5. The router matches the method and route.
6. A controller handles the request and returns a response.
7. The response object sends JSON with an HTTP status code.

## JSON response convention

Successful response:

```json
{
  "success": true,
  "message": "Operation completed.",
  "data": {}
}
```

Error response:

```json
{
  "success": false,
  "message": "A safe explanation of the error."
}
```

The exact fields may be extended when pagination and validation errors are implemented.

## Error handling

Unexpected exceptions are logged on the server. Detailed exception information is only included when `APP_DEBUG=true`; keep debug mode disabled in production.

## Security notes

- Controllers must not assume that a submitted ID belongs to the authenticated user.
- Authorization must be checked for every workspace and project resource.
- Never return password hashes, token hashes, secrets, or raw exception traces to clients.
- Validate request data before calling services or repositories.
- Use parameterized SQL through PDO.
