<?php
declare(strict_types=1);

namespace App\Core;

class BaseController
{
    protected function view(string $view, array $data = [], int $status = 200): void
    {
        Response::view($view, $data, $status);
    }

    protected function json(array $payload, int $status = 200): void
    {
        Response::json($payload, $status);
    }

    protected function redirect(string $path, int $status = 302): never
    {
        Response::redirect($path, $status);
    }
}
