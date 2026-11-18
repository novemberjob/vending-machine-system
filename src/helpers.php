<?php
declare(strict_types=1);

if (!function_exists('env')) {
    /** Read configuration from $_ENV, $_SERVER or the process environment. */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return $value === false || $value === null ? $default : $value;
    }
}

if (!function_exists('e')) {
    /** HTML-escape a value for safe output in views. */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('old')) {
    /** @param array<string, mixed> $old */
    function old(array $old, string $key, mixed $default = ''): mixed
    {
        return $old[$key] ?? $default;
    }
}

if (!function_exists('field_error')) {
    /** @param array<string, string> $errors */
    function field_error(array $errors, string $key): string
    {
        return isset($errors[$key])
            ? '<span class="field-error" role="alert">' . e($errors[$key]) . '</span>'
            : '';
    }
}

if (!function_exists('can_purchase')) {
    /** @param array<string, mixed>|null $user */
    function can_purchase(?array $user): bool
    {
        return $user !== null && ($user['role'] ?? null) === 'User';
    }
}
