<?php
declare(strict_types=1);

namespace VMS\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use VMS\Api\Controllers\ProductApiController;
use VMS\Core\Attributes\RequiresAuth;
use VMS\Core\Attributes\RequiresRole;
use VMS\Core\Attributes\Route;
use VMS\Controllers\ProductsController;

final class RoutingAttributesTest extends TestCase
{
    public function testPurchaseRouteUsesSeoFriendlyPath(): void
    {
        $route = $this->routeOf(ProductsController::class, 'purchase');

        $this->assertSame('/products/{id}-{slug}/buy', $route->path);
        $this->assertSame(['GET', 'POST'], $route->httpMethods());
        $this->assertSame('products.purchase', $route->name);

        $method = new ReflectionMethod(ProductsController::class, 'purchase');
        $this->assertNotSame([], $method->getAttributes(RequiresAuth::class));
    }

    public function testManagementActionsRequireAdminRole(): void
    {
        foreach (['create', 'store', 'edit', 'update', 'destroy'] as $action) {
            $method = new ReflectionMethod(ProductsController::class, $action);
            $this->assertNotSame([], $method->getAttributes(RequiresRole::class), "{$action} is not protected");
        }
    }

    public function testPurchaseRequiresUserRole(): void
    {
        foreach ([ProductsController::class, ProductApiController::class] as $controller) {
            $method = new ReflectionMethod($controller, 'purchase');
            $roles = array_map(
                static fn (\ReflectionAttribute $attribute): string => $attribute->newInstance()->role,
                $method->getAttributes(RequiresRole::class),
            );

            $this->assertContains('User', $roles, "{$controller}::purchase does not require the User role");
        }
    }

    public function testApiRoutesAreMarkedAsApi(): void
    {
        foreach (['index', 'show', 'store', 'update', 'destroy', 'purchase'] as $action) {
            $this->assertTrue($this->routeOf(ProductApiController::class, $action)->api, "{$action} is not an API route");
        }
    }

    private function routeOf(string $class, string $method): Route
    {
        $reflection = new ReflectionMethod($class, $method);
        $attributes = $reflection->getAttributes(Route::class);

        $this->assertNotSame([], $attributes, "No route on {$class}::{$method}");

        return $attributes[0]->newInstance();
    }
}
