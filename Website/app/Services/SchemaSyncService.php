<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use Throwable;

final class SchemaSyncService
{
    private static ?bool $hasUserVipColumn = null;
    private static ?bool $hasUserApiTokensTable = null;

    public static function ensureUserVipColumn(): bool
    {
        if (self::$hasUserVipColumn !== null) {
            return self::$hasUserVipColumn;
        }

        try {
            $pdo = Database::pdo();
            $database = $pdo->query('SELECT DATABASE()')->fetchColumn();
            if (!is_string($database) || trim($database) === '') {
                self::$hasUserVipColumn = false;
                return false;
            }

            $stmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = :schema
                   AND TABLE_NAME = :table_name
                   AND COLUMN_NAME = :column_name'
            );
            $stmt->execute([
                'schema' => $database,
                'table_name' => 'users',
                'column_name' => 'vip_until',
            ]);

            $exists = (int) $stmt->fetchColumn() > 0;
            if (!$exists) {
                $pdo->exec('ALTER TABLE users ADD COLUMN vip_until DATETIME NULL DEFAULT NULL AFTER status');
                $stmt->execute([
                    'schema' => $database,
                    'table_name' => 'users',
                    'column_name' => 'vip_until',
                ]);
                $exists = (int) $stmt->fetchColumn() > 0;
            }

            self::$hasUserVipColumn = $exists;
            return $exists;
        } catch (Throwable $e) {
            error_log('SchemaSyncService ensureUserVipColumn failed: ' . $e->getMessage());
            self::$hasUserVipColumn = false;
            return false;
        }
    }

    public static function ensureUserApiTokensTable(): bool
    {
        if (self::$hasUserApiTokensTable !== null) {
            return self::$hasUserApiTokensTable;
        }

        try {
            $pdo = Database::pdo();
            $database = $pdo->query('SELECT DATABASE()')->fetchColumn();
            if (!is_string($database) || trim($database) === '') {
                self::$hasUserApiTokensTable = false;
                return false;
            }

            $stmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = :schema
                   AND TABLE_NAME = :table_name'
            );
            $stmt->execute([
                'schema' => $database,
                'table_name' => 'user_api_tokens',
            ]);

            $exists = (int) $stmt->fetchColumn() > 0;
            if (!$exists) {
                $pdo->exec(
                    'CREATE TABLE user_api_tokens (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        token_hash CHAR(64) NOT NULL,
                        token_name VARCHAR(120) NOT NULL DEFAULT "android-app",
                        last_used_at DATETIME NULL DEFAULT NULL,
                        expires_at DATETIME NOT NULL,
                        revoked_at DATETIME NULL DEFAULT NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY uniq_user_api_tokens_hash (token_hash),
                        KEY idx_user_api_tokens_user (user_id),
                        KEY idx_user_api_tokens_expiry (expires_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
                );

                $stmt->execute([
                    'schema' => $database,
                    'table_name' => 'user_api_tokens',
                ]);
                $exists = (int) $stmt->fetchColumn() > 0;
            }

            self::$hasUserApiTokensTable = $exists;
            return $exists;
        } catch (Throwable $e) {
            error_log('SchemaSyncService ensureUserApiTokensTable failed: ' . $e->getMessage());
            self::$hasUserApiTokensTable = false;
            return false;
        }
    }
}
