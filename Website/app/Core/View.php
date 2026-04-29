<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }

        $viewPath = BASE_PATH . '/resources/views/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($viewPath)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
            return;
        }

        extract($data, EXTR_SKIP);
        require $viewPath;
        clear_old();
    }
}
