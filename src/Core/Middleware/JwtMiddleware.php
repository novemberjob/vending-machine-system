<?php
declare(strict_types=1);

namespace VMS\Core\Middleware;

use Throwable;
use VMS\Core\Request;
use VMS\Core\Response;
use VMS\Services\JwtService;

final class JwtMiddleware
{
    public function __construct(private readonly JwtService $jwt)
    {
    }

    public function handle(Request $request, Response $response, callable $next, ?string $role = null): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            return $response->json(['errors' => ['auth' => 'Bearer token required.']], 401);
        }

        try {
            $claims = $this->jwt->verify($token);
        } catch (Throwable) {
            return $response->json(['errors' => ['auth' => 'Invalid or expired token.']], 401);
        }

        if ($role !== null && ($claims['role'] ?? null) !== $role) {
            return $response->json(['errors' => ['auth' => 'Insufficient permissions.']], 403);
        }

        $request->attributes['auth'] = $claims;
        return $next($request, $response);
    }
}
