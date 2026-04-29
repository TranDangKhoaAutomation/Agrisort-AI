<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

/**
 * @return array<string,array{method:string,path:string}>
 */
function extract_routes_from_file(string $filePath): array
{
    $content = file_get_contents($filePath);
    if ($content === false) {
        return [];
    }

    $routes = [];
    if (preg_match_all('/\$router->(get|post|put|delete)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,/i', $content, $matches, PREG_SET_ORDER) <= 0) {
        return [];
    }

    foreach ($matches as $match) {
        $method = strtoupper((string) ($match[1] ?? 'GET'));
        $path = normalize_route_path((string) ($match[2] ?? '/'));
        $key = $method . ' ' . $path;
        $routes[$key] = ['method' => $method, 'path' => $path];
    }

    return $routes;
}

function normalize_route_path(string $path): string
{
    $normalized = '/' . trim($path, '/');
    if ($normalized === '//') {
        return '/';
    }
    return $normalized;
}

/**
 * @param array<string,array{method:string,path:string}> $routeSet
 * @return array<int,string>
 */
function sorted_route_keys(array $routeSet): array
{
    $keys = array_keys($routeSet);
    sort($keys);
    return $keys;
}

$webRoutes = extract_routes_from_file(BASE_PATH . '/routes/web.php');
$apiRoutes = extract_routes_from_file(BASE_PATH . '/routes/api.php');

$sourceRoutes = $webRoutes + $apiRoutes;

$catalogPayload = require BASE_PATH . '/resources/docs/route_catalog.php';
$catalogRows = is_array($catalogPayload['routes'] ?? null) ? $catalogPayload['routes'] : [];
$catalogRoutes = [];
foreach ($catalogRows as $row) {
    if (!is_array($row)) {
        continue;
    }
    $method = strtoupper((string) ($row['method'] ?? ''));
    $path = normalize_route_path((string) ($row['path'] ?? ''));
    if ($method === '' || $path === '') {
        continue;
    }
    $key = $method . ' ' . $path;
    $catalogRoutes[$key] = ['method' => $method, 'path' => $path];
}

$sourceKeys = sorted_route_keys($sourceRoutes);
$catalogKeys = sorted_route_keys($catalogRoutes);

$missingInCatalog = array_values(array_diff($sourceKeys, $catalogKeys));
$extraInCatalog = array_values(array_diff($catalogKeys, $sourceKeys));

if ($missingInCatalog === [] && $extraInCatalog === []) {
    echo '[PASS] docs_consistency: route catalog matches routes/web.php + routes/api.php' . PHP_EOL;
    exit(0);
}

echo '[FAIL] docs_consistency: route catalog mismatch detected' . PHP_EOL;

if ($missingInCatalog !== []) {
    echo '  Missing in catalog:' . PHP_EOL;
    foreach ($missingInCatalog as $key) {
        echo '  - ' . $key . PHP_EOL;
    }
}

if ($extraInCatalog !== []) {
    echo '  Extra in catalog:' . PHP_EOL;
    foreach ($extraInCatalog as $key) {
        echo '  - ' . $key . PHP_EOL;
    }
}

exit(1);
