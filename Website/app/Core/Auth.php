<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    public static function login(array $user): void
    {
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
            'status' => (string) $user['status'],
            'name' => (string) ($user['full_name'] ?? $user['email']),
            'vip_until' => isset($user['vip_until']) && $user['vip_until'] !== null ? (string) $user['vip_until'] : null,
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function refreshFromDatabase(): ?array
    {
        $sessionUser = self::user();
        if (!is_array($sessionUser)) {
            return null;
        }

        $userId = (int) ($sessionUser['id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        $freshUser = User::findById($userId);
        if ($freshUser === null) {
            return null;
        }

        self::login($freshUser);
        return $freshUser;
    }
}
