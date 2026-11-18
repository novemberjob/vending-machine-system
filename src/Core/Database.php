<?php
declare(strict_types=1);

namespace VMS\Core;

use PDO;

/**
 * Manages the single PDO connection and provides a transaction helper.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $dsn  = env('DB_DSN', 'mysql:host=127.0.0.1;dbname=vms;charset=utf8mb4');
            $user = env('DB_USER', 'root');
            $pass = env('DB_PASS', '');

            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return self::$pdo;
    }

    /**
     * Execute $fn inside a transaction, rolling back on any exception.
     */
    public static function transaction(callable $fn): mixed
    {
        $pdo = self::connection();
        $pdo->beginTransaction();

        try {
            $result = $fn($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Primarily useful for tests. */
    public static function reset(): void
    {
        self::$pdo = null;
    }
}
