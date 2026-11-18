<?php
declare(strict_types=1);

namespace VMS\Api\Controllers;

use VMS\Core\Attributes\Route;
use VMS\Core\Exceptions\ValidationException;
use VMS\Core\Request;
use VMS\Core\Response;
use VMS\Core\Validator;
use VMS\Services\AuthService;
use VMS\Services\JwtService;

final class AuthApiController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly JwtService $jwt,
        private readonly Validator $validator,
        private readonly Response $response,
    ) {
    }

    #[Route('/api/v1/auth/login', methods: ['POST'], name: 'api.auth.login')]
    public function login(Request $request): Response
    {
        $data = $request->all();

        if (!$this->validator->validate($data, [
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ])) {
            return $this->response->json(['errors' => $this->validator->errors()], 422);
        }

        try {
            $user = $this->auth->authenticate((string) $data['email'], (string) $data['password']);
        } catch (ValidationException) {
            return $this->response->json(['errors' => ['credentials' => 'Invalid email or password.']], 401);
        }

        $token = $this->jwt->issue([
            'sub'  => (string) $user->id,
            'role' => $user->role,
            'name' => $user->name,
        ]);

        return $this->response->json([
            'data' => [
                'token'      => $token,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'user'       => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ],
            ],
        ], 200);
    }
}
