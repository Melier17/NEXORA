<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Repositories\UserRepository;
use App\Validators\Validator;
use PDO;
use PDOException;

final class AuthService
{
    private UserRepository $users;
    private TokenService $tokens;

    public function __construct(private readonly PDO $database)
    {
        $this->users = new UserRepository($database);
        $this->tokens = new TokenService($database);
    }

    public function register(array $input): array
    {
        $input['name'] = is_string($input['name'] ?? null) ? trim($input['name']) : $input['name'] ?? null;
        $input['email'] = is_string($input['email'] ?? null)
            ? strtolower(trim($input['email']))
            : $input['email'] ?? null;

        $errors = Validator::validate($input, [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:180'],
            'password' => ['required', 'string', 'min:12', 'max:72'],
            'password_confirmation' => ['required', 'string'],
        ]);

        if (is_string($input['password'] ?? null) && strlen($input['password']) > 72) {
            $errors['password'][] = 'Password must not exceed 72 bytes.';
        }

        if (
            is_string($input['password'] ?? null)
            && is_string($input['password_confirmation'] ?? null)
            && !hash_equals($input['password'], $input['password_confirmation'])
        ) {
            $errors['password_confirmation'][] = 'Password confirmation does not match.';
        }

        if ($errors !== []) {
            throw ApiException::validation($errors);
        }

        if ($this->users->findByEmail($input['email']) !== null) {
            throw new ApiException('An account with this email already exists.', 409);
        }

        try {
            $this->database->beginTransaction();

            $passwordHash = password_hash($input['password'], PASSWORD_DEFAULT);
            $userId = $this->users->create($input['name'], $input['email'], $passwordHash);
            $token = $this->tokens->issue($userId);
            $user = $this->users->findPublicById($userId);

            $this->database->commit();
        } catch (PDOException $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            if ($exception->getCode() === '23000') {
                throw new ApiException('An account with this email already exists.', 409);
            }

            throw $exception;
        } catch (\Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $exception;
        }

        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $this->tokenLifetimeSeconds(),
        ];
    }

    public function login(array $input): array
    {
        $input['email'] = is_string($input['email'] ?? null)
            ? strtolower(trim($input['email']))
            : $input['email'] ?? null;

        $errors = Validator::validate($input, [
            'email' => ['required', 'string', 'email', 'max:180'],
            'password' => ['required', 'string', 'max:72'],
        ]);

        if (is_string($input['password'] ?? null) && strlen($input['password']) > 72) {
            $errors['password'][] = 'Password must not exceed 72 bytes.';
        }

        if ($errors !== []) {
            throw ApiException::validation($errors);
        }

        $user = $this->users->findByEmail($input['email']);

        if (
            $user === null
            || !password_verify($input['password'], $user['password_hash'])
            || $user['status'] !== 'active'
        ) {
            throw new ApiException('Invalid email or password.', 401);
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($input['password'], PASSWORD_DEFAULT);
            $statement = $this->database->prepare(
                'UPDATE users SET password_hash = :password_hash WHERE id = :id'
            );
            $statement->execute([
                'password_hash' => $newHash,
                'id' => $user['id'],
            ]);
        }

        $token = $this->tokens->issue((int) $user['id']);
        $publicUser = $this->users->findPublicById((int) $user['id']);

        return [
            'user' => $publicUser,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $this->tokenLifetimeSeconds(),
        ];
    }

    public function currentUser(string $token): array
    {
        return $this->tokens->authenticate($token);
    }

    public function logout(string $token): void
    {
        $this->tokens->revoke($token);
    }

    private function tokenLifetimeSeconds(): int
    {
        return max(5, (int) ($_ENV['ACCESS_TOKEN_TTL_MINUTES'] ?? 60)) * 60;
    }
}
