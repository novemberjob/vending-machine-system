<?php
declare(strict_types=1);

namespace VMS\Core;

final class Response
{
    private int $status = 200;

    /** @var array<string, string> */
    private array $headers = [];

    private string $body = '';

    public function setStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function html(string $body, int $status = 200): self
    {
        $this->status = $status;
        $this->body = $body;
        $this->header('Content-Type', 'text/html; charset=utf-8');
        return $this;
    }

    public function json(array $payload, int $status = 200): self
    {
        $this->status = $status;
        $this->body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
        $this->header('Content-Type', 'application/json; charset=utf-8');
        return $this;
    }

    public function redirect(string $url): self
    {
        $this->status = 302;
        $this->body = '';
        $this->header('Location', $url);
        return $this;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo $this->body;
    }
}
