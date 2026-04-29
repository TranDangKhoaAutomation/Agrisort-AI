<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function view(string $view, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        View::render($view, $data);
    }

    public static function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function redirect(string $path, int $status = 302): never
    {
        if ($status < 300 || $status > 399) {
            $status = 302;
        }

        header('Location: ' . app_url($path), true, $status);
        exit;
    }

    public static function abort(int $status, string $message = 'Error'): void
    {
        http_response_code($status);
        echo $message;
        exit;
    }
}
