<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require BASE_PATH . '/app/Core/functions.php';

use App\Core\Validator;
use App\Services\MembershipService;
use App\Services\QrService;
use App\Services\TraceTokenService;

$results = [];

$results[] = ['name' => 'normalize_slug', 'ok' => normalize_slug('AGRISORT AI Demo 2026!') === 'agrisort-ai-demo-2026'];
$results[] = ['name' => 'normalize_slug_vietnamese', 'ok' => normalize_slug('Đăng ký đối tác') === 'dang-ky-doi-tac'];
$results[] = ['name' => 'validator_email_true', 'ok' => Validator::email('demo@example.com') === true];
$results[] = ['name' => 'validator_email_false', 'ok' => Validator::email('bad-email') === false];
$results[] = ['name' => 'validator_required', 'ok' => isset(Validator::required(['a' => ''], ['a'])['a'])];
$results[] = ['name' => 'trace_token_extract_plain', 'ok' => TraceTokenService::extractToken('demo-token') === 'demo-token'];
$results[] = ['name' => 'membership_admin_internal_unlimited', 'ok' => MembershipService::isInternalRole(['role' => 'admin']) === true];
$results[] = ['name' => 'membership_farmer_not_internal_unlimited', 'ok' => MembershipService::isInternalRole(['role' => 'farmer']) === false];

$qrServiceReflection = new ReflectionClass(QrService::class);
$cachedBaseUrlProperty = $qrServiceReflection->getProperty('cachedBaseUrl');
$cachedBaseUrlProperty->setAccessible(true);
$cachedBaseUrlProperty->setValue(null, 'https://trace.example');
$payloadMethod = $qrServiceReflection->getMethod('payload');
$payloadMethod->setAccessible(true);

$results[] = ['name' => 'qr_payload_is_raw_token', 'ok' => $payloadMethod->invoke(null, 'demo-token') === 'demo-token'];
$results[] = ['name' => 'qr_url_uses_cached_base_url', 'ok' => QrService::url('demo-token') === 'https://trace.example/trace/demo-token'];
$results[] = ['name' => 'trace_token_extract_from_trace_url', 'ok' => TraceTokenService::extractToken(QrService::url('demo-token')) === 'demo-token'];

$cachedBaseUrlProperty->setValue(null, null);

$publicContentCmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(BASE_PATH . '/tests/public_content_sanity.php');
$publicContentOutput = [];
$publicContentCode = 0;
exec($publicContentCmd, $publicContentOutput, $publicContentCode);
$results[] = ['name' => 'public_content_sanity', 'ok' => $publicContentCode === 0];
if ($publicContentOutput !== []) {
    echo implode(PHP_EOL, $publicContentOutput) . PHP_EOL;
}

$autoTranslationCmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(BASE_PATH . '/tests/auto_translation_sanity.php');
$autoTranslationOutput = [];
$autoTranslationCode = 0;
exec($autoTranslationCmd, $autoTranslationOutput, $autoTranslationCode);
$results[] = ['name' => 'auto_translation_sanity', 'ok' => $autoTranslationCode === 0];
if ($autoTranslationOutput !== []) {
    echo implode(PHP_EOL, $autoTranslationOutput) . PHP_EOL;
}

$docsCmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(BASE_PATH . '/tests/docs_consistency.php');
$docsOutput = [];
$docsCode = 0;
exec($docsCmd, $docsOutput, $docsCode);
$results[] = ['name' => 'docs_consistency', 'ok' => $docsCode === 0];
if ($docsOutput !== []) {
    echo implode(PHP_EOL, $docsOutput) . PHP_EOL;
}

$encodingCmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(BASE_PATH . '/tests/encoding_consistency.php');
$encodingOutput = [];
$encodingCode = 0;
exec($encodingCmd, $encodingOutput, $encodingCode);
$results[] = ['name' => 'encoding_consistency', 'ok' => $encodingCode === 0];
if ($encodingOutput !== []) {
    echo implode(PHP_EOL, $encodingOutput) . PHP_EOL;
}

$proposalCmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(BASE_PATH . '/tests/proposal_alignment_sanity.php');
$proposalOutput = [];
$proposalCode = 0;
exec($proposalCmd, $proposalOutput, $proposalCode);
$results[] = ['name' => 'proposal_alignment_sanity', 'ok' => $proposalCode === 0];
if ($proposalOutput !== []) {
    echo implode(PHP_EOL, $proposalOutput) . PHP_EOL;
}

$routeCatalog = require BASE_PATH . '/resources/docs/route_catalog.php';
$routes = is_array($routeCatalog['routes'] ?? null) ? $routeCatalog['routes'] : [];
$findRoute = static function (string $method, string $path) use ($routes): ?array {
    foreach ($routes as $route) {
        if (!is_array($route)) {
            continue;
        }
        if ((string) ($route['method'] ?? '') === $method && (string) ($route['path'] ?? '') === $path) {
            return $route;
        }
    }

    return null;
};

$partnerWebRoute = $findRoute('GET', '/partner/lots');
$partnerApiRoute = $findRoute('GET', '/api/v1/app/partner/lots');
$results[] = ['name' => 'route_catalog_partner_web_partner_or_farmer', 'ok' => is_array($partnerWebRoute) && (($partnerWebRoute['role'] ?? null) === 'partner|farmer')];
$results[] = ['name' => 'route_catalog_partner_api_partner_or_farmer', 'ok' => is_array($partnerApiRoute) && (($partnerApiRoute['role'] ?? null) === 'partner|farmer')];

$failed = 0;
foreach ($results as $row) {
    echo ($row['ok'] ? '[PASS] ' : '[FAIL] ') . $row['name'] . PHP_EOL;
    if (!$row['ok']) {
        $failed++;
    }
}

exit($failed === 0 ? 0 : 1);
