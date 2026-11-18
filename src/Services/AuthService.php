<?php
declare(strict_types=1);

namespace VMS\Services;

use VMS\Core\Exceptions\ValidationException;
use VMS\Core\PasswordHasher;
use VMS\Models\User;
use VMS\Repositories\UserRepository;

final class AuthService
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    /** @param array<string, mixed> $data */
    public function register(array $data): User
    {
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        if ($this->users->findByEmail($email) !== null) {
            throw new ValidationException(['email' => 'That email is already registered.']);
        }

        return $this->users->create([
            'name'          => trim((string) ($data['name'] ?? '')),
            'email'         => $email,
            'password_hash' => PasswordHasher::hash((string) ($data['password'] ?? '')),
            'role'          => 'User',
        ]);
    }

    /** @throws ValidationException */
    public function authenticate(string $email, string $password): User
    {
        $user = $this->users->findByEmail(strtolower(trim($email)));

        if ($user === null || !PasswordHasher::verify($password, (string) $user->passwordHash)) {
            throw new ValidationException(['credentials' => 'Invalid email or password.']);
        }

        return $user;
    }
}
