<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use PDO;

final class TokenService
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function issue(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $ttlMinutes = max(5, (int) ($_ENV['ACCESS_TOKEN_TTL_MINUTES'] ?? 60));
        $expiresAt = gmdate('Y-m-d H:i:s', time() + ($ttlMinutes * 60));

        $statement = $this->database->prepare(
            'INSERT INTO access_tokens (user_id, token_hash, expires_at)
             VALUES (:user_id, :token_hash, :expires_at)'
        );
        $statement->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        return $token;
    }

    public function authenticate(string $plainToken): array
    {
        $statement = $this->database->prepare(
            'SELECT u.id, u.name, u.email, u.status, u.created_at
             FROM access_tokens AS t
             INNER JOIN users AS u ON u.id = t.user_id
             WHERE t.token_hash = :token_hash
               AND t.revoked_at IS NULL
               AND t.expires_at > UTC_TIMESTAMP()
               AND u.status = :status
             LIMIT 1'
        );
        $statement->execute([
            'token_hash' => hash('sha256', $plainToken),
            'status' => 'active',
        ]);
        $user = $statement->fetch();

        if ($user === false) {
            throw new ApiException('Unauthenticated. Please sign in again.', 401);
        }

        return $user;
    }

    public function revoke(string $plainToken): void
    {
        $statement = $this->database->prepare(
            'UPDATE access_tokens
             SET revoked_at = UTC_TIMESTAMP()
             WHERE token_hash = :token_hash
               AND revoked_at IS NULL'
        );
        $statement->execute(['token_hash' => hash('sha256', $plainToken)]);
    }
}
