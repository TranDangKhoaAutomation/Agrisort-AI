<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRCodeOutputException;

final class QrService
{
    private const DEFAULT_SCALE = 8;
    private const MIN_SCALE = 4;
    private const MAX_SCALE = 14;
    private const STORAGE_DIR = 'public/qr';
    private const PAYLOAD_VERSION = 5;

    private static ?string $cachedBaseUrl = null;

    public static function token(): string
    {
        return bin2hex(random_bytes(16));
    }

    public static function url(string $token): string
    {
        $base = self::resolveBaseUrl();
        return rtrim($base, '/') . '/trace/' . rawurlencode($token);
    }

    public static function imageDataUri(string $token, int $scale = 5): string
    {
        $scale = self::normalizeScale($scale);
        $data = self::payload($token);
        $options = self::baseOptions($scale) + ['outputBase64' => true];

        if (extension_loaded('gd')) {
            try {
                return self::renderQr($data, $options + ['outputType' => QRCode::OUTPUT_IMAGE_PNG]);
            } catch (QRCodeOutputException) {
                // fallback to SVG below
            }
        }

        try {
            return self::renderQr($data, $options + [
                'outputType' => QRCode::OUTPUT_MARKUP_SVG,
                'svgAddXmlHeader' => false,
            ]);
        } catch (QRCodeOutputException) {
            return self::placeholderDataUri();
        }
    }

    public static function storedImageUrls(string $token, int $scale = self::DEFAULT_SCALE): array
    {
        $pngPath = self::ensureStoredImage($token, 'png', $scale);
        $svgPath = self::ensureStoredImage($token, 'svg', $scale);

        return [
            'png' => $pngPath !== null ? self::toAppUrl($pngPath) : null,
            'svg' => $svgPath !== null ? self::toAppUrl($svgPath) : null,
        ];
    }

    public static function ensureStoredImage(string $token, string $format = 'png', int $scale = self::DEFAULT_SCALE): ?string
    {
        $format = strtolower(trim($format));
        if (!in_array($format, ['png', 'svg'], true)) {
            return null;
        }

        $scale = self::normalizeScale($scale);
        $targetPath = self::buildStoredImagePath($token, $format);
        if (is_file($targetPath) && (int) @filesize($targetPath) > 0) {
            return $targetPath;
        }

        $directory = dirname($targetPath);
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            return null;
        }

        $payload = $format === 'png'
            ? self::renderPngBinary($token, $scale)
            : self::renderSvgMarkup($token, $scale);

        if ($payload === null || $payload === '') {
            return null;
        }

        $written = @file_put_contents($targetPath, $payload, LOCK_EX);
        if ($written === false || $written <= 0) {
            return null;
        }

        return $targetPath;
    }

    private static function renderQr(string $data, array $options): string
    {
        return (new QRCode(new QROptions($options)))->render($data);
    }

    private static function renderPngBinary(string $token, int $scale): ?string
    {
        $data = self::payload($token);
        $options = self::baseOptions($scale) + ['outputBase64' => false];

        if (extension_loaded('gd')) {
            try {
                return self::renderQr($data, $options + ['outputType' => QRCode::OUTPUT_IMAGE_PNG]);
            } catch (QRCodeOutputException) {
                // fallback to imagick below
            }
        }

        if (extension_loaded('imagick')) {
            try {
                return self::renderQr($data, $options + [
                    'outputType' => QRCode::OUTPUT_IMAGICK,
                    'imagickFormat' => 'png32',
                ]);
            } catch (QRCodeOutputException) {
                return null;
            }
        }

        return null;
    }

    private static function renderSvgMarkup(string $token, int $scale): ?string
    {
        $data = self::payload($token);
        try {
            return self::renderQr($data, self::baseOptions($scale) + [
                'outputType' => QRCode::OUTPUT_MARKUP_SVG,
                'svgAddXmlHeader' => true,
                'outputBase64' => false,
            ]);
        } catch (QRCodeOutputException) {
            return null;
        }
    }

    private static function payload(string $token): string
    {
        $normalized = trim($token);

        if ($normalized === '') {
            return '';
        }

        return $normalized;
    }

    private static function baseOptions(int $scale): array
    {
        return [
            'version' => QRCode::VERSION_AUTO,
            'eccLevel' => QRCode::ECC_H,
            'scale' => $scale,
            'addQuietzone' => true,
            'quietzoneSize' => 4,
        ];
    }

    private static function normalizeScale(int $scale): int
    {
        return max(self::MIN_SCALE, min($scale, self::MAX_SCALE));
    }

    private static function buildStoredImagePath(string $token, string $format): string
    {
        return BASE_PATH . '/' . self::STORAGE_DIR . '/' . self::tokenFileStem($token) . '.' . $format;
    }

    private static function tokenFileStem(string $token): string
    {
        $normalized = strtolower(trim($token));
        $slug = preg_replace('/[^a-z0-9._-]+/', '-', $normalized) ?? 'qr-token';
        $slug = trim($slug, '-._');
        if ($slug === '') {
            $slug = 'qr-token';
        }

        return substr($slug, 0, 42) . '-v' . self::PAYLOAD_VERSION . '-' . substr(sha1($token), 0, 12);
    }

    private static function toAppUrl(string $absolutePath): string
    {
        $base = str_replace('\\', '/', BASE_PATH);
        $normalized = str_replace('\\', '/', $absolutePath);
        if (str_starts_with($normalized, $base)) {
            $relative = '/' . ltrim(substr($normalized, strlen($base)), '/');
            return app_url($relative);
        }

        return app_url('/public/qr');
    }

    private static function placeholderDataUri(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240">'
            . '<rect width="240" height="240" fill="#ffffff"/>'
            . '<rect x="16" y="16" width="208" height="208" rx="20" fill="#f3f8f4" stroke="#1c7a4f" stroke-width="8"/>'
            . '<circle cx="120" cy="120" r="54" fill="#1c7a4f" opacity="0.14"/>'
            . '<path d="M92 120h56M120 92v56" stroke="#1c7a4f" stroke-width="12" stroke-linecap="round"/>'
            . '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private static function resolveBaseUrl(): string
    {
        if (self::$cachedBaseUrl !== null) {
            return self::$cachedBaseUrl;
        }

        $configured = '';
        try {
            $configured = trim(Setting::get('qr_base_url', ''));
        } catch (\Throwable) {
            $configured = '';
        }

        if ($configured !== '') {
            self::$cachedBaseUrl = rtrim($configured, '/');
            return self::$cachedBaseUrl;
        }

        $appUrl = rtrim((string) app_url(''), '/');
        if ($appUrl === '') {
            $appUrl = 'http://localhost';
        }

        $host = (string) ($_SERVER['HTTP_HOST'] ??'');
        if ($host !== '' && preg_match('/localhost|127\.0\.0\.1/i', $appUrl) === 1) {
            self::$cachedBaseUrl = $appUrl;
            return self::$cachedBaseUrl;
        }

        self::$cachedBaseUrl = $appUrl;
        return self::$cachedBaseUrl;
    }
}
