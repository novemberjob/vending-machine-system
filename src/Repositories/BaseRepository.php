<?php
declare(strict_types=1);

namespace VMS\Repositories;

use PDO;

abstract class BaseRepository
{
    public function __construct(protected readonly PDO $pdo)
    {
    }

    /** @param array<string, mixed> $params */
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<string, mixed> $params */
    protected function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    /** @param array<string, mixed> $params */
    protected function execute(string $sql, array $params = []): int
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->rowCount();
    }
}
