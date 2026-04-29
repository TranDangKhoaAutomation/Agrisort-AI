<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;

final class RateLimiter
{
    /**
     * @return array{attempts:int,window_start:int,remaining_seconds:int}
     */
    public static function inspect(string $key, int $windowSeconds): array
    {
        $now = time();

        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare('SELECT attempts, window_start FROM rate_limits WHERE key_name = :key LIMIT 1');
            $stmt->execute(['key' => $key]);
            $row = $stmt->fetch();

            if (!$row) {
                return [
                    'attempts' => 0,
                    'window_start' => $now,
                    'remaining_seconds' => $windowSeconds,
                ];
            }

            $windowStart = (int) $row['window_start'];
            $elapsed = $now - $windowStart;
            if ($elapsed > $windowSeconds) {
                return [
                    'attempts' => 0,
                    'window_start' => $now,
                    'remaining_seconds' => $windowSeconds,
                ];
            }

            return [
                'attempts' => (int) $row['attempts'],
                'window_start' => $windowStart,
                'remaining_seconds' => max(0, $windowSeconds - $elapsed),
            ];
        } catch (Throwable) {
            return [
                'attempts' => 0,
                'window_start' => $now,
                'remaining_seconds' => $windowSeconds,
            ];
        }
    }

    public static function allow(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare('SELECT id, attempts, window_start FROM rate_limits WHERE key_name = :key LIMIT 1');
            $stmt->execute(['key' => $key]);
            $row = $stmt->fetch();

            $now = time();

            if (!$row) {
                $insert = $pdo->prepare('INSERT INTO rate_limits (key_name, attempts, window_start) VALUES (:key, 1, :window_start)');
                $insert->execute(['key' => $key, 'window_start' => $now]);
                return true;
            }

            $elapsed = $now - (int) $row['window_start'];
            if ($elapsed > $windowSeconds) {
                $reset = $pdo->prepare('UPDATE rate_limits SET attempts = 1, window_start = :window_start WHERE id = :id');
                $reset->execute(['window_start' => $now, 'id' => (int) $row['id']]);
                return true;
            }

            if ((int) $row['attempts'] >= $maxAttempts) {
                return false;
            }

            $update = $pdo->prepare('UPDATE rate_limits SET attempts = attempts + 1 WHERE id = :id');
            $update->execute(['id' => (int) $row['id']]);
            return true;
        } catch (Throwable) {
            return true;
        }
    }
}
