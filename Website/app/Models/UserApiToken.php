<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use RuntimeException;
use App\Services\SchemaSyncService;

final class UserApiToken
{
    /**
     * @return array{plain_token:string,expires_at:string,token_id:int}
     */
    public static function issue(int $userId, string $tokenName = 'android-app', int $ttlDays = 30): array
    {
        if (!SchemaSyncService::ensureUserApiTokensTable()) {
            throw new RuntimeException('User API token storage is unavailable.');
        }

        $plainToken = 'agt_' . bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = date('Y-m-d H:i:s', time() + ($ttlDays * 86400));

        $stmt = Database::pdo()->prepare(
            'INSERT INTO user_api_tokens
            (user_id, token_hash, token_name, last_used_at, expires_at, revoked_at, created_at, updated_at)
            VALUES
            (:user_id, :token_hash, :token_name, NOW(), :expires_at, NULL, NOW(), NOW())'
        );
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'token_name' => $tokenName,
            'expires_at' => $expiresAt,
        ]);

        return [
            'plain_token' => $plainToken,
            'expires_at' => $expiresAt,
            'token_id' => (int) Database::pdo()->lastInsertId(),
        ];
    }

    public static function findActiveByPlainToken(string $plainToken): ?array
    {
        if (!SchemaSyncService::ensureUserApiTokensTable()) {
            return null;
        }
        if (!SchemaSyncService::ensureUserVipColumn()) {
            return null;
        }

        $tokenHash = hash('sha256', $plainToken);
        $stmt = Database::pdo()->prepare(
            'SELECT
                t.id AS token_id,
                t.user_id AS token_user_id,
                t.token_name,
                t.last_used_at,
                t.expires_at,
                t.revoked_at,
                u.id AS user_id,
                u.full_name,
                u.email,
                u.password_hash,
                u.role,
                u.status,
                u.vip_until
            FROM user_api_tokens t
            INNER JOIN users u ON u.id = t.user_id
            WHERE t.token_hash = :token_hash
              AND t.revoked_at IS NULL
              AND t.expires_at > NOW()
            LIMIT 1'
        );
        $stmt->execute([
            'token_hash' => $tokenHash,
        ]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $touch = Database::pdo()->prepare('UPDATE user_api_tokens SET last_used_at = NOW(), updated_at = NOW() WHERE id = :id');
        $touch->execute(['id' => (int) $row['token_id']]);

        return $row;
    }

    public static function revokeByPlainToken(string $plainToken): void
    {
        if (!SchemaSyncService::ensureUserApiTokensTable()) {
            return;
        }

        $tokenHash = hash('sha256', $plainToken);
        $stmt = Database::pdo()->prepare(
            'UPDATE user_api_tokens
             SET revoked_at = NOW(), updated_at = NOW()
             WHERE token_hash = :token_hash
               AND revoked_at IS NULL'
        );
        $stmt->execute([
            'token_hash' => $tokenHash,
        ]);
    }

    public static function revokeByUserId(int $userId): void
    {
        if (!SchemaSyncService::ensureUserApiTokensTable()) {
            return;
        }

        $stmt = Database::pdo()->prepare(
            'UPDATE user_api_tokens
             SET revoked_at = NOW(), updated_at = NOW()
             WHERE user_id = :user_id
               AND revoked_at IS NULL'
        );
        $stmt->execute([
            'user_id' => $userId,
        ]);
    }
}
