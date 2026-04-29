<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Models\Setting;

final class TranslateService
{
    public static function toEnglish(string $text): string
    {
        static $memoryCache = [];

        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (array_key_exists($text, $memoryCache)) {
            return $memoryCache[$text];
        }

        $memoryCache[$text] = self::translate($text, 'vi', 'en');
        return $memoryCache[$text];
    }

    public static function translate(string $text, string $source, string $target): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $hash = sha1($source . '|' . $target . '|' . $text);
        $stmt = Database::pdo()->prepare('SELECT translated_text FROM translation_cache WHERE source_text_hash = :hash LIMIT 1');
        $stmt->execute(['hash' => $hash]);
        $cached = $stmt->fetchColumn();
        if ($cached !== false) {
            return (string) $cached;
        }

        $apiKey = Setting::get('google_translate_api_key', (string) Config::get('services.google_translate_api_key', ''));
        if ($apiKey === '') {
            return $text;
        }

        $url = 'https://translation.googleapis.com/language/translate/v2?key=' . urlencode($apiKey);
        $payload = json_encode([
            'q' => $text,
            'source' => $source,
            'target' => $target,
            'format' => 'text',
        ], JSON_UNESCAPED_UNICODE);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload ?: '{}',
                'timeout' => 10,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return $text;
        }

        $decoded = json_decode($response, true);
        $translated = html_entity_decode(
            (string) ($decoded['data']['translations'][0]['translatedText'] ?? $text),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $insert = Database::pdo()->prepare(
            'INSERT INTO translation_cache (source_text_hash, source_lang, target_lang, translated_text, provider, created_at)
             VALUES (:hash, :source, :target, :translated, :provider, NOW())'
        );
        $insert->execute([
            'hash' => $hash,
            'source' => $source,
            'target' => $target,
            'translated' => $translated,
            'provider' => 'google',
        ]);

        return (string) $translated;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function structuredToEnglish(array $payload): array
    {
        $translated = [];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $translated[$key] = self::structuredToEnglish($value);
                continue;
            }

            if (!is_string($value)) {
                $translated[$key] = $value;
                continue;
            }

            $translated[$key] = self::shouldKeepOriginal((string) $key, $value)
                ? $value
                : self::toEnglish($value);
        }

        return $translated;
    }

    private static function shouldKeepOriginal(string $key, string $value): bool
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return true;
        }

        $normalizedKey = strtolower($key);
        if (in_array($normalizedKey, ['image', 'image_path', 'image_url', 'thumbnail', 'thumbnail_path', 'thumbnail_url', 'icon', 'slug', 'url', 'href', 'src'], true)) {
            return true;
        }

        if (preg_match('#^(?:https?://|/|\.{1,2}/)#i', $trimmed) === 1) {
            return true;
        }

        return preg_match('#\.(?:png|jpe?g|webp|gif|svg)(?:\?.*)?$#i', $trimmed) === 1;
    }
}
