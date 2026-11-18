<?php
declare(strict_types=1);

namespace VMS\Core;

/**
 * Session-backed authentication state, flash messages and CSRF tokens.
 */
class Session
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** @param array{id:int,name:string,role:string} $user */
    public function login(array $user): void
    {
        $this->regenerate();
        $this->set('user', $user);
    }

    public function logout(): void
    {
        $this->forget('user');
        $this->regenerate();
    }

    /** @return array{id:int,name:string,role:string}|null */
    public function user(): ?array
    {
        $user = $this->get('user');
        return is_array($user) ? $user : null;
    }

    public function isLoggedIn(): bool
    {
        return $this->user() !== null;
    }

    public function hasRole(string $role): bool
    {
        return $this->isLoggedIn() && ($this->user()['role'] ?? null) === $role;
    }

    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public function pullFlash(string $key): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public function csrfToken(): string
    {
        $token = $this->get('_csrf');
        if (!is_string($token)) {
            $token = bin2hex(random_bytes(32));
            $this->set('_csrf', $token);
        }
        return $token;
    }

    public function validateCsrf(?string $token): bool
    {
        return is_string($token) && hash_equals($this->csrfToken(), $token);
    }

    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
