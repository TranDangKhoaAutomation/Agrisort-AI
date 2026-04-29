<?php
declare(strict_types=1);

namespace App\Services;

final class TraceTokenService
{
    public static function extractToken(string $decodedText): ?string
    {
        $text = trim($decodedText);
        if ($text === '') {
            return null;
        }

        $fromPath = self::extractFromPath($text);
        if ($fromPath !== null) {
            return $fromPath;
        }

        $fromQuery = self::extractFromQuery($text);
        if ($fromQuery !== null) {
            return $fromQuery;
        }

        return self::isValidToken($text) ? $text : null;
    }

    public static function isValidToken(string $token): bool
    {
        return preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{2,120}$/', $token) === 1;
    }

    private static function extractFromPath(string $text): ?string
    {
        if (preg_match('#/trace/([^/?\#\s]+)#i', $text, $matches) !== 1) {
            return null;
        }

        return self::normalizeCandidate((string) ($matches[1] ?? ''));
    }

    private static function extractFromQuery(string $text): ?string
    {
        if (preg_match('/(?:\?|&)(?:qr_token|token)=([^&#\s]+)/i', $text, $matches) !== 1) {
            return null;
        }

        return self::normalizeCandidate((string) ($matches[1] ?? ''));
    }

    private static function normalizeCandidate(string $candidate): ?string
    {
        $candidate = trim($candidate);
        if ($candidate === '') {
            return null;
        }

        $decoded = rawurldecode($candidate);
        return self::isValidToken($decoded) ? $decoded : null;
    }
}
