<?php
declare(strict_types=1);

namespace VMS\Controllers;

use VMS\Core\Exceptions\ValidationException;
use VMS\Core\Request;
use VMS\Core\Response;
use VMS\Core\Session;
use VMS\Core\Validator;
use VMS\Core\View;
use VMS\Services\AuthService;

final class AuthController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly Validator $validator,
        private readonly Session $session,
        private readonly Response $response,
        private readonly View $view,
    ) {
    }

    public function login(Request $request): Response
    {
        return $this->render('auth/login', ['errors' => [], 'old' => []]);
    }

    public function postLogin(Request $request): Response
    {
        if (!$this->session->validateCsrf($request->input('_csrf'))) {
            return $this->response->html('Invalid CSRF token.', 419);
        }

        $data = $request->all();
        $rules = ['email' => ['required', 'email'], 'password' => ['required']];

        if (!$this->validator->validate($data, $rules)) {
            return $this->render('auth/login', ['errors' => $this->validator->errors(), 'old' => $data], 422);
        }

        try {
            $user = $this->auth->authenticate((string) $data['email'], (string) $data['password']);
        } catch (ValidationException $exception) {
            return $this->render('auth/login', [
                'errors' => $exception->errors,
                'old'    => ['email' => $data['email']],
            ], 422);
        }

        $this->session->login([
            'id'   => $user->id,
            'name' => $user->name,
            'role' => $user->role,
        ]);
        $this->session->flash('success', "Welcome back, {$user->name}!");

        return $this->response->redirect('/products');
    }

    public function register(Request $request): Response
    {
        return $this->render('auth/register', ['errors' => [], 'old' => []]);
    }

    public function postRegister(Request $request): Response
    {
        if (!$this->session->validateCsrf($request->input('_csrf'))) {
            return $this->response->html('Invalid CSRF token.', 419);
        }

        $data = $request->all();

        if (!$this->validator->validate($data, Validator::registerRules())) {
            return $this->render('auth/register', ['errors' => $this->validator->errors(), 'old' => $data], 422);
        }

        try {
            $user = $this->auth->register($data);
        } catch (ValidationException $exception) {
            return $this->render('auth/register', [
                'errors' => $exception->errors,
                'old'    => $data,
            ], 422);
        }

        $this->session->login([
            'id'   => $user->id,
            'name' => $user->name,
            'role' => $user->role,
        ]);
        $this->session->flash('success', 'Account created. Happy shopping!');

        return $this->response->redirect('/products');
    }

    public function logout(Request $request): Response
    {
        if (!$this->session->validateCsrf($request->input('_csrf'))) {
            return $this->response->html('Invalid CSRF token.', 419);
        }

        $this->session->logout();
        return $this->response->redirect('/login');
    }

    /** @param array<string, mixed> $data */
    private function render(string $template, array $data = [], int $status = 200): Response
    {
        $data['user'] = $this->session->user();
        $data['csrf'] = $this->session->csrfToken();
        $data['flashSuccess'] = $this->session->pullFlash('success');
        $data['flashError'] = $this->session->pullFlash('error');

        return $this->response->html($this->view->render($template, $data), $status);
    }
}
