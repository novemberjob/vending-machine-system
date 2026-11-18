<?php
declare(strict_types=1);

namespace VMS\Core;

use Dotenv\Dotenv;
use PDO;
use VMS\Api\Controllers\AuthApiController;
use VMS\Api\Controllers\ProductApiController;
use VMS\Controllers\ProductsController;
use VMS\Services\JwtService;

final class Application
{
    /** @return array{0: Container, 1: Router} */
    public static function boot(?Request $request = null): array
    {
        $root = dirname(__DIR__, 2);
        require_once $root . '/vendor/autoload.php';

        if (file_exists($root . '/.env')) {
            Dotenv::createImmutable($root)->safeLoad();
        }

        $request ??= Request::fromGlobals();
        $response = new Response();

        $container = new Container();
        $container->instance(Request::class, $request);
        $container->instance(Response::class, $response);
        $container->set(PDO::class, static fn (): PDO => Database::connection());
        $container->set(View::class, static fn (): View => new View($root . '/src/Views'));
        $container->set('$secret', static fn (): string => (string) env('JWT_SECRET', 'dev-only-secret'));

        $router = new Router($container, $request, $response);

        foreach (require $root . '/config/routes.php' as $route) {
            $router->add($route[0], $route[1], $route[2], $route[3], $route[4] ?? []);
        }

        $router->discover(ProductsController::class);
        $router->discover(AuthApiController::class);
        $router->discover(ProductApiController::class);

        // Fail fast on a misconfigured JWT secret.
        $container->get(JwtService::class);

        return [$container, $router];
    }
}
