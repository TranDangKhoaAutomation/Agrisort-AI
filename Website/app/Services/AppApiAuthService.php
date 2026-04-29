<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Models\UserApiToken;

final class AppApiAuthService
{
    /**
     * @return array{plain_token:string,expires_at:string,token_id:int}
     */
    public static function issueToken(int $userId, string $tokenName = 'android-app', int $ttlDays = 30): array
    {
        return UserApiToken::issue($userId, $tokenName, $ttlDays);
    }

    public static function resolveUserFromRequest(Request $request): ?array
    {
        $token = self::extractBearerToken($request);
        if ($token === null) {
            return null;
        }

        $row = UserApiToken::findActiveByPlainToken($token);
        if ($row === null) {
            return null;
        }

        return [
            'token' => $token,
            'token_id' => (int) $row['token_id'],
            'user' => [
                'id' => (int) $row['user_id'],
                'full_name' => (string) $row['full_name'],
                'email' => (string) $row['email'],
                'password_hash' => (string) $row['password_hash'],
                'role' => (string) $row['role'],
                'status' => (string) $row['status'],
            ],
            'expires_at' => (string) $row['expires_at'],
        ];
    }

    public static function revokeTokenFromRequest(Request $request): void
    {
        $token = self::extractBearerToken($request);
        if ($token === null) {
            return;
        }

        UserApiToken::revokeByPlainToken($token);
    }

    public static function extractBearerToken(Request $request): ?string
    {
        $header = trim((string) $request->header('Authorization', ''));
        if ($header === '' && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $header = trim((string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        }

        if ($header === '') {
            return null;
        }

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches) !== 1) {
            return null;
        }

        $token = trim((string) ($matches[1] ?? ''));
        return $token !== '' ? $token : null;
    }
}

