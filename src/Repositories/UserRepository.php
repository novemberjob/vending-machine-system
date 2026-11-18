<?php
declare(strict_types=1);

namespace VMS\Repositories;

use VMS\Models\User;

class UserRepository extends BaseRepository
{
    public function find(int $id): ?User
    {
        $row = $this->fetchOne('SELECT * FROM users WHERE id = :id', ['id' => $id]);
        return $row === null ? null : User::fromArray($row);
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->fetchOne('SELECT * FROM users WHERE email = :email', ['email' => $email]);
        return $row === null ? null : User::fromArray($row);
    }

    /** @param array{name:string,email:string,password_hash:string,role:string} $data */
    public function create(array $data): User
    {
        $this->execute(
            'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, :role)',
            [
                'name'  => $data['name'],
                'email' => $data['email'],
                'hash'  => $data['password_hash'],
                'role'  => $data['role'] ?? 'User',
            ],
        );

        return $this->find((int) $this->pdo->lastInsertId());
    }
}
