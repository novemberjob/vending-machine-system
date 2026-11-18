<?php
declare(strict_types=1);

namespace VMS\Core;

final class Request
{
    /** @var array<string, mixed> */
    public array $attributes = [];

    public function __construct(
        private readonly string $uri,
        private readonly string $method,
        private readonly array $query = [],
        private readonly array $post = [],
        private readonly array $server = [],
        private readonly ?array $json = null,
    ) {
    }

    public static function fromGlobals(): self
    {
        $raw = file_get_contents('php://input');
        $json = null;
        if (str_contains(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') && $raw !== false && $raw !== '') {
            $decoded = json_decode($raw, true);
            $json = is_array($decoded) ? $decoded : [];
        }

        return new self(
            parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_GET,
            $_POST,
            $_SERVER,
            $json,
        );
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if ($this->json !== null && array_key_exists($key, $this->json)) {
            return $this->json[$key];
        }
        if (array_key_exists($key, $this->post)) {
            return $this->post[$key];
        }
        return $this->query[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->json ?? []);
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $this->server[$key] ?? $this->server[$name] ?? null;
        return $value === null ? null : (string) $value;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization');
        if ($header === null || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }
        return trim($matches[1]);
    }
}
