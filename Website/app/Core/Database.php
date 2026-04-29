<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use Throwable;

final class Database
{
    private static ?PDO $pdo = null;
    private static int $lastHealthCheckAt = 0;
    private const HEALTH_CHECK_INTERVAL = 5;

    public static function init(bool $forceReconnect = false): bool
    {
        if (!$forceReconnect && self::$pdo instanceof PDO) {
            return true;
        }

        self::$pdo = null;
        self::$lastHealthCheckAt = 0;

        try {
            self::$pdo = self::connect();
            self::$lastHealthCheckAt = 0;
            return true;
        } catch (PDOException $e) {
            error_log('Database init failed: ' . $e->getMessage());
            return false;
        }
    }

    public static function pdo(): PDO
    {
        if (!self::init() || !self::$pdo instanceof PDO) {
            throw new PDOException('Database connection unavailable.');
        }

        self::ensureAlive();

        if (!self::$pdo instanceof PDO) {
            throw new PDOException('Database connection unavailable.');
        }

        return self::$pdo;
    }

    private static function connect(): PDO
    {
        $host = (string) Config::get('db.host');
        $port = (int) Config::get('db.port');
        $name = (string) Config::get('db.name');
        $user = (string) Config::get('db.user');
        $pass = (string) Config::get('db.pass');

        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("SET SESSION collation_connection = 'utf8mb4_unicode_ci'");

        return $pdo;
    }

    private static function ensureAlive(): void
    {
        if (!self::$pdo instanceof PDO) {
            return;
        }

        $now = time();
        if (
            self::$lastHealthCheckAt > 0
            && ($now - self::$lastHealthCheckAt) < self::HEALTH_CHECK_INTERVAL
        ) {
            return;
        }

        try {
            self::$pdo->query('SELECT 1');
            self::$lastHealthCheckAt = $now;
        } catch (PDOException $e) {
            if (!self::shouldReconnect($e)) {
                throw $e;
            }

            self::$pdo = null;
            self::$lastHealthCheckAt = 0;

            if (!self::init(true) || !self::$pdo instanceof PDO) {
                throw $e;
            }
        }
    }

    private static function shouldReconnect(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'server has gone away')
            || str_contains($message, 'lost connection')
            || str_contains($message, 'connection was killed')
            || str_contains($message, 'error: 2006')
            || str_contains($message, 'error: 2013');
    }
}
