<?php
declare(strict_types=1);

namespace VMS\Core;

class View
{
    public function __construct(private readonly string $basePath)
    {
    }

    /** Render a template wrapped in the shared layout. */
    public function render(string $template, array $data = []): string
    {
        $content = $this->partial($template, $data);
        return $this->partial('layout', array_merge($data, ['content' => $content]));
    }

    /** Render a template without the layout. */
    public function partial(string $template, array $data = []): string
    {
        $path = rtrim($this->basePath, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $template) . '.php';

        if (!is_file($path)) {
            throw new \RuntimeException("View not found: {$template}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $path;
            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }
}
