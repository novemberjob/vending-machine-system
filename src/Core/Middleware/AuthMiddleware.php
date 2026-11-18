<?php
declare(strict_types=1);

namespace VMS\Core\Middleware;

use VMS\Core\Request;
use VMS\Core\Response;
use VMS\Core\Session;
use VMS\Core\View;

final class AuthMiddleware
{
    public function __construct(private readonly Session $session, private readonly View $view)
    {
    }

    public function handle(Request $request, Response $response, callable $next, ?string $role = null): Response
    {
        if (!$this->session->isLoggedIn()) {
            if (str_starts_with($request->uri(), '/api/')) {
                return $response->json(['errors' => ['auth' => 'Authentication required.']], 401);
            }
            $this->session->flash('error', 'Please log in to continue.');
            return $response->redirect('/login');
        }

        if ($role !== null && !$this->session->hasRole($role)) {
            if (str_starts_with($request->uri(), '/api/')) {
                return $response->json(['errors' => ['auth' => 'You do not have permission to do that.']], 403);
            }
            return $response->html($this->view->render('errors/403'), 403);
        }

        return $next($request, $response);
    }
}
