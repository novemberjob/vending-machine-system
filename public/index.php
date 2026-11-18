<?php
declare(strict_types=1);

use VMS\Core\Application;
use VMS\Core\View;

$root = dirname(__DIR__);
require_once $root . '/vendor/autoload.php';

try {
    [$container, $router] = Application::boot();
    $router->dispatch()->send();
} catch (Throwable $exception) {
    error_log('VMS error: ' . $exception->getMessage());

    http_response_code(500);
    if (str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/api/')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['errors' => ['server' => 'Internal server error.']]);
        return;
    }

    header('Content-Type: text/html; charset=utf-8');
    echo (new View($root . '/src/Views'))->render('errors/500');
}
