<?php
declare(strict_types=1);

namespace VMS\Models;

final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $role,
        public readonly ?string $passwordHash = null,
        public readonly ?string $createdAt = null,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            (int) ($row['id'] ?? 0),
            (string) ($row['name'] ?? ''),
            (string) ($row['email'] ?? ''),
            (string) ($row['role'] ?? 'User'),
            $row['password_hash'] ?? null,
            $row['created_at'] ?? null,
        );
    }

    public function isAdmin(): bool
    {
        return $this->role === 'Admin';
    }
}
