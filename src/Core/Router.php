<?php
declare(strict_types=1);

namespace VMS\Core;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use VMS\Core\Attributes\RequiresAuth;
use VMS\Core\Attributes\RequiresRole;
use VMS\Core\Attributes\Route;
use VMS\Core\Middleware\AuthMiddleware;
use VMS\Core\Middleware\JwtMiddleware;

/**
 * Small router supporting manual registration and PHP 8 attribute discovery.
 */
final class Router
{
    /** @var list<array<string, mixed>> */
    private array $routes = [];

    public function __construct(
        private readonly Container $container,
        private readonly Request $request,
        private readonly Response $response,
    ) {
    }

    public function add(string|array $methods, string $path, string $controller, string $action, array $options = []): void
    {
        foreach ((array) $methods as $method) {
            $this->routes[] = [
                'method'     => strtoupper($method),
                'regex'      => $this->compile($path),
                'controller' => $controller,
                'action'     => $action,
                'auth'       => (bool) ($options['auth'] ?? false),
                'roles'      => (array) ($options['roles'] ?? []),
                'api'        => (bool) ($options['api'] ?? false),
                'name'       => $options['name'] ?? null,
            ];
        }
    }

    /** Register every #[Route] method on a controller class. */
    public function discover(string $controllerClass): void
    {
        $reflection = new ReflectionClass($controllerClass);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $routeAttributes = $method->getAttributes(Route::class);
            if ($routeAttributes === []) {
                continue;
            }

            $requiresAuth = $method->getAttributes(RequiresAuth::class) !== [];
            $roles = [];
            foreach ($method->getAttributes(RequiresRole::class) as $attribute) {
                $roles[] = $attribute->newInstance()->role;
            }

            foreach ($routeAttributes as $attribute) {
                $route = $attribute->newInstance();
                $this->add($route->httpMethods(), $route->path, $controllerClass, $method->getName(), [
                    'auth'  => $requiresAuth || $roles !== [] || $route->api,
                    'roles' => $roles,
                    'api'   => $route->api,
                    'name'  => $route->name,
                ]);
            }
        }
    }

    public function dispatch(): Response
    {
        $path = $this->request->uri();
        $method = $this->request->method();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method || !preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }

            $invoke = function () use ($route, $params): Response {
                $controller = $this->container->get($route['controller']);
                return $this->call($controller, $route['action'], $params);
            };

            if ($route['api']) {
                return $this->container->get(JwtMiddleware::class)
                    ->handle($this->request, $this->response, $invoke, $route['roles'][0] ?? null);
            }

            if ($route['auth'] || $route['roles'] !== []) {
                return $this->container->get(AuthMiddleware::class)
                    ->handle($this->request, $this->response, $invoke, $route['roles'][0] ?? null);
            }

            return $invoke();
        }

        if (str_starts_with($path, '/api/')) {
            return $this->response->json(['errors' => ['route' => 'Not found.']], 404);
        }

        return $this->response->html($this->container->get(View::class)->render('errors/404'), 404);
    }

    /** @param array<string, string> $params */
    private function call(object $controller, string $action, array $params): Response
    {
        $reflection = new ReflectionMethod($controller, $action);
        $args = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();

            if ($name === 'request') {
                $args[] = $this->request;
                continue;
            }

            if (array_key_exists($name, $params)) {
                $value = $params[$name];
                $type = $parameter->getType();
                if ($type instanceof ReflectionNamedType && $type->getName() === 'int') {
                    $value = (int) $value;
                }
                $args[] = $value;
                continue;
            }

            $args[] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
        }

        $result = $reflection->invokeArgs($controller, $args);
        return $result instanceof Response ? $result : $this->response;
    }

    private function compile(string $path): string
    {
        $pattern = preg_replace_callback('/\{(\w+)\}/', static function (array $match): string {
            return $match[1] === 'id'
                ? '(?P<id>\d+)'
                : '(?P<' . $match[1] . '>[A-Za-z0-9_-]+)';
        }, $path) ?? $path;

        return '#^' . $pattern . '$#';
    }
}
