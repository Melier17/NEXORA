<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->database->prepare(
            'SELECT id, name, email, password_hash, status
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function create(string $name, string $email, string $passwordHash): int
    {
        $statement = $this->database->prepare(
            'INSERT INTO users (name, email, password_hash)
             VALUES (:name, :email, :password_hash)'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        return (int) $this->database->lastInsertId();
    }

    public function findPublicById(int $userId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT id, name, email, status, created_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }
}
