<?php
declare(strict_types=1);

namespace App\Core;

final class CSRF
{
    public static function token(): string
    {
        if (!isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    public static function validate(?string $token): bool
    {
        if (!isset($_SESSION['_csrf']) || $token === null) {
            return false;
        }

        return hash_equals((string) $_SESSION['_csrf'], $token);
    }
}
