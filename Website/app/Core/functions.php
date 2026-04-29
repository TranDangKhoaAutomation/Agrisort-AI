<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Config;
use App\Services\TranslateService;

function env(string $key, ?string $default = null): ?string
{
    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function request_host(): string
{
    $forwardedHost = trim((string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''));
    if ($forwardedHost !== '') {
        $forwardedParts = explode(',', $forwardedHost);
        return trim((string) ($forwardedParts[0] ?? ''));
    }

    return trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
}

function is_localhost_host(?string $host): bool
{
    $value = trim((string) $host);
    if ($value === '') {
        return false;
    }

    return preg_match('/^(localhost|127\.0\.0\.1|\[::1\]|::1)(:\d+)?$/i', $value) === 1;
}

function is_https_request(): bool
{
    $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    $requestScheme = strtolower(trim((string) ($_SERVER['REQUEST_SCHEME'] ?? '')));
    $httpsFlag = strtolower(trim((string) ($_SERVER['HTTPS'] ?? '')));

    return $forwardedProto === 'https'
        || $requestScheme === 'https'
        || ($httpsFlag !== '' && $httpsFlag !== 'off' && $httpsFlag !== '0')
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

function script_base_path(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $scriptDir = str_replace('\\', '/', (string) dirname($scriptName));
    $basePath = trim($scriptDir, '/');

    return ($basePath === '' || $basePath === '.') ? '' : '/' . $basePath;
}

function localhost_host_name(string $host): string
{
    $value = trim($host);
    if ($value === '') {
        return 'localhost';
    }

    if (preg_match('/^(\[::1\]|::1)(:\d+)?$/i', $value) === 1) {
        return '[::1]';
    }

    return preg_replace('/:\d+$/', '', $value) ?? $value;
}

function detect_local_http_port(): ?int
{
    $ports = [];

    $configured = rtrim((string) config('app.url', ''), '/');
    if ($configured !== '') {
        $configuredHost = (string) (parse_url($configured, PHP_URL_HOST) ?? '');
        $configuredScheme = strtolower((string) (parse_url($configured, PHP_URL_SCHEME) ?? ''));
        $configuredPort = parse_url($configured, PHP_URL_PORT);
        if (is_localhost_host($configuredHost) && $configuredScheme === 'http') {
            $ports[] = is_int($configuredPort) ? $configuredPort : 80;
        }
    }

    $documentRoot = str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? BASE_PATH));
    $serverRoot = rtrim(str_replace('\\', '/', (string) dirname($documentRoot)), '/');
    $configPaths = [
        $serverRoot . '/apache/conf/httpd.conf',
        $serverRoot . '/apache/conf/extra/httpd-vhosts.conf',
    ];

    foreach ($configPaths as $configPath) {
        if (!is_file($configPath)) {
            continue;
        }

        $content = file_get_contents($configPath);
        if ($content === false || $content === '') {
            continue;
        }

        if (preg_match_all('/^\s*ServerName\s+[^\s:]+:(\d+)\s*$/mi', $content, $matches) === 1 || !empty($matches[1])) {
            foreach ((array) ($matches[1] ?? []) as $portValue) {
                $port = (int) $portValue;
                if ($port > 0 && $port !== 443) {
                    $ports[] = $port;
                }
            }
        }

        if (preg_match_all('/^\s*Listen\s+(\d+)\s*$/mi', $content, $matches) === 1 || !empty($matches[1])) {
            foreach ((array) ($matches[1] ?? []) as $portValue) {
                $port = (int) $portValue;
                if ($port > 0 && $port !== 443) {
                    $ports[] = $port;
                }
            }
        }
    }

    $ports = array_values(array_unique(array_filter($ports, static fn (int $port): bool => $port > 0 && $port !== 443)));
    if ($ports === []) {
        return null;
    }

    return (int) $ports[0];
}

function local_http_origin(?string $host = null): ?string
{
    $resolvedHost = trim((string) ($host ?? request_host()));
    if (!is_localhost_host($resolvedHost)) {
        return null;
    }

    $hostName = localhost_host_name($resolvedHost);
    $port = detect_local_http_port();
    if ($port === null || $port === 80) {
        return 'http://' . $hostName;
    }

    return 'http://' . $hostName . ':' . $port;
}

function app_base_url(): string
{
    $configured = rtrim((string) config('app.url', ''), '/');
    $configuredHost = parse_url($configured, PHP_URL_HOST);
    $configuredIsLocalhost = is_string($configuredHost) && in_array(strtolower($configuredHost), ['localhost', '127.0.0.1'], true);

    $requestHost = request_host();
    if ($configured !== '' && !$configuredIsLocalhost) {
        return $configured;
    }

    $host = $requestHost;

    if ($host === '') {
        $serverName = trim((string) ($_SERVER['SERVER_NAME'] ?? ''));
        $serverPort = trim((string) ($_SERVER['SERVER_PORT'] ?? ''));
        if ($serverName !== '') {
            if ($serverPort !== '' && !in_array($serverPort, ['80', '443'], true)) {
                $host = $serverName . ':' . $serverPort;
            } else {
                $host = $serverName;
            }
        }
    }

    if ($host === '') {
        return $configured !== '' ? $configured : 'http://localhost';
    }

    $basePath = script_base_path();

    if (is_localhost_host($host)) {
        $localOrigin = local_http_origin($host);
        if ($localOrigin !== null) {
            return rtrim($localOrigin, '/') . $basePath;
        }
    }

    $scheme = is_https_request() ? 'https' : 'http';

    return $scheme . '://' . $host . $basePath;
}

function app_url(string $path = ''): string
{
    $base = rtrim(app_base_url(), '/');
    $path = ltrim($path, '/');
    return $path === '' ? $base : $base . '/' . $path;
}

function redirect_to(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}

function csrf_token(): string
{
    return CSRF::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function method_field(string $method): string
{
    return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
}

function old(string $key, string $default = ''): string
{
    return $_SESSION['_old'][$key] ?? $default;
}

function with_old(array $input): void
{
    $_SESSION['_old'] = $input;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function flash(string $key, string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }

    $message = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $message;
}

function current_user(): ?array
{
    return Auth::user();
}

function is_admin(): bool
{
    return Auth::check() && Auth::user()['role'] === 'admin';
}

function is_partner(): bool
{
    if (!Auth::check()) {
        return false;
    }

    $role = (string) (Auth::user()['role'] ??'');
    return in_array($role, ['partner', 'farmer'], true);
}

function is_supply_actor(): bool
{
    if (!Auth::check()) {
        return false;
    }

    $role = (string) (Auth::user()['role'] ??'');
    return in_array($role, ['transporter', 'warehouse', 'seller'], true);
}

function lang(): string
{
    $locale = strtolower(trim((string) ($_SESSION['lang'] ?? 'vi')));

    return in_array($locale, ['vi', 'en'], true) ? $locale : 'vi';
}

function lang_text(string $vi, string $en = ''): string
{
    if (lang() === 'vi') {
        return $vi;
    }

    static $cache = [];

    $cacheKey = sha1($vi . '|' . $en);
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $translated = TranslateService::toEnglish($vi);
    $fallback = trim($en);
    if (
        $fallback !== ''
        && $translated === $vi
        && preg_match('/[^\x00-\x7F]/u', $vi) === 1
    ) {
        $translated = $fallback;
    }

    $cache[$cacheKey] = $translated !== '' ? $translated : ($fallback !== '' ? $fallback : $vi);
    return $cache[$cacheKey];
}

function t(string $key, ?string $locale = null): string
{
    static $cache = [];

    $locale = $locale ?? lang();
    if (!isset($cache[$locale])) {
        $file = BASE_PATH . '/resources/lang/' . $locale . '.php';
        $cache[$locale] = is_file($file) ? require $file : [];
    }

    return $cache[$locale][$key] ?? $key;
}

function load_cms(string $sectionKey, string $locale): array
{
    try {
        if ($locale !== 'en') {
            return \App\Models\CmsSection::getContent($sectionKey, 'vi');
        }

        $vi = \App\Models\CmsSection::getContent($sectionKey, 'vi');
        if ($vi === []) {
            return \App\Models\CmsSection::getContent($sectionKey, 'en');
        }

        return TranslateService::structuredToEnglish($vi);
    } catch (\Throwable $e) {
        error_log('load_cms failed for [' . $sectionKey . '/' . $locale . ']: ' . $e->getMessage());
        return [];
    }
}

function normalize_slug(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }

    if (function_exists('mb_strtolower')) {
        $text = mb_strtolower($text, 'UTF-8');
    } else {
        $text = strtolower($text);
    }

    $text = strtr($text, [
        'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
        'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
        'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
        'À' => 'a', 'Á' => 'a', 'Ạ' => 'a', 'Ả' => 'a', 'Ã' => 'a',
        'Â' => 'a', 'Ầ' => 'a', 'Ấ' => 'a', 'Ậ' => 'a', 'Ẩ' => 'a', 'Ẫ' => 'a',
        'Ă' => 'a', 'Ằ' => 'a', 'Ắ' => 'a', 'Ặ' => 'a', 'Ẳ' => 'a', 'Ẵ' => 'a',
        'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
        'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
        'È' => 'e', 'É' => 'e', 'Ẹ' => 'e', 'Ẻ' => 'e', 'Ẽ' => 'e',
        'Ê' => 'e', 'Ề' => 'e', 'Ế' => 'e', 'Ệ' => 'e', 'Ể' => 'e', 'Ễ' => 'e',
        'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
        'Ì' => 'i', 'Í' => 'i', 'Ị' => 'i', 'Ỉ' => 'i', 'Ĩ' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
        'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
        'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
        'Ò' => 'o', 'Ó' => 'o', 'Ọ' => 'o', 'Ỏ' => 'o', 'Õ' => 'o',
        'Ô' => 'o', 'Ồ' => 'o', 'Ố' => 'o', 'Ộ' => 'o', 'Ổ' => 'o', 'Ỗ' => 'o',
        'Ơ' => 'o', 'Ờ' => 'o', 'Ớ' => 'o', 'Ợ' => 'o', 'Ở' => 'o', 'Ỡ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
        'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
        'Ù' => 'u', 'Ú' => 'u', 'Ụ' => 'u', 'Ủ' => 'u', 'Ũ' => 'u',
        'Ư' => 'u', 'Ừ' => 'u', 'Ứ' => 'u', 'Ự' => 'u', 'Ử' => 'u', 'Ữ' => 'u',
        'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
        'Ỳ' => 'y', 'Ý' => 'y', 'Ỵ' => 'y', 'Ỷ' => 'y', 'Ỹ' => 'y',
        'đ' => 'd', 'Đ' => 'd',
    ]);

    $text = preg_replace('/[^a-z0-9\s-]/u', ' ', $text) ?? '';
    $text = preg_replace('/[-\s]+/u', '-', $text) ?? '';
    return trim($text, '-');
}
