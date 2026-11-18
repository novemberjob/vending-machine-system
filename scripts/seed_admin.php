<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use VMS\Core\Database;
use VMS\Core\PasswordHasher;

if (file_exists(__DIR__ . '/../.env')) {
    Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
}

$name = $argv[1] ?? 'Admin';
$email = $argv[2] ?? 'admin@example.com';
$password = $argv[3] ?? 'admin12345';

$pdo = Database::connection();
$hash = PasswordHasher::hash($password);

$stmt = $pdo->prepare(
    'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, "Admin")
     ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), role = "Admin"'
);
$stmt->execute(['name' => $name, 'email' => $email, 'hash' => $hash]);

echo "Admin seeded: {$email} / {$password}\n";
