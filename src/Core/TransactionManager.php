<?php
declare(strict_types=1);

namespace VMS\Core;

/**
 * Thin wrapper around Database::transaction() so services can be mocked in tests.
 */
class TransactionManager
{
    public function run(callable $fn): mixed
    {
        return Database::transaction($fn);
    }
}
