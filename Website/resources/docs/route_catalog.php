<?php
declare(strict_types=1);

return (static function (): array {
    $field = static fn (
        string $name,
        string $type,
        bool $required,
        mixed $example = '',
        string $viDescription = '',
        string $enDescription = ''
    ): array => [
        'name' => $name,
        'type' => $type,
        'required' => $required,
        'example' => $example,
        'description' => [
            'vi' => $viDescription !== '' ? $viDescription : $name,
            'en' => $enDescription !== '' ? $enDescription : $name,
        ],
    ];

    $response = static fn (
        int $status,
        string $contentType,
        string $viDescription,
        string $enDescription,
        mixed $example = null
    ): array => array_filter([
        'status' => $status,
        'content_type' => $contentType,
        'description' => ['vi' => $viDescription, 'en' => $enDescription],
        'example' => $example,
    ], static fn (mixed $value): bool => $value !== null);

    $requestNone = ['type' => 'none', 'headers' => [], 'query' => [], 'fields' => []];
    $requestForm = static fn (bool $withCsrf = true): array => [
        'type' => 'form',
        'headers' => [],
        'query' => [],
        'fields' => $withCsrf ? [
            $field('_token', 'string', true, 'csrf-token', 'CSRF token', 'CSRF token'),
        ] : [],
    ];
    $requestJson = static fn (array $headers = []): array => [
        'type' => 'json',
        'headers' => $headers,
        'query' => [],
        'fields' => [],
    ];

    $extractRoutes = static function (string $filePath): array {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        $rows = [];
        if (preg_match_all('/\$router->(get|post|put|delete)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,/i', $content, $matches, PREG_SET_ORDER) <= 0) {
            return [];
        }

        foreach ($matches as $match) {
            $method = strtoupper((string) ($match[1] ?? 'GET'));
            $path = '/' . trim((string) ($match[2] ?? '/'), '/');
            if ($path === '//') {
                $path = '/';
            }
            $key = $method . ' ' . $path;
            $rows[$key] = [
                'method' => $method,
                'path' => $path,
            ];
        }

        return array_values($rows);
    };

    $allRoutes = array_merge(
        $extractRoutes(BASE_PATH . '/routes/web.php'),
        $extractRoutes(BASE_PATH . '/routes/api.php')
    );

    $dedup = [];
    foreach ($allRoutes as $row) {
        if (!is_array($row)) {
            continue;
        }
        $method = strtoupper((string) ($row['method'] ?? 'GET'));
        $path = (string) ($row['path'] ?? '/');
        $key = $method . ' ' . $path;
        $dedup[$key] = ['method' => $method, 'path' => $path];
    }
    $allRoutes = array_values($dedup);

    usort($allRoutes, static function (array $a, array $b): int {
        return strcmp($a['method'] . ' ' . $a['path'], $b['method'] . ' ' . $b['path']);
    });

    $publicAppRoutes = [
        'POST /api/v1/app/auth/register',
        'POST /api/v1/app/auth/login',
        'POST /api/v1/app/auth/forgot-password',
        'POST /api/v1/app/auth/reset-password',
        'GET /api/v1/app/blog',
        'GET /api/v1/app/blog/{slug}',
        'GET /api/v1/app/trace/lookup',
        'GET /api/v1/app/trace/{qr_token}',
    ];

    $toRoute = static function (array $row) use ($publicAppRoutes, $requestNone, $requestForm, $requestJson, $response, $field): array {
        $method = (string) ($row['method'] ?? 'GET');
        $path = (string) ($row['path'] ?? '/');
        $key = $method . ' ' . $path;

        $scope = 'public';
        $auth = false;
        $role = null;
        $csrf = $method !== 'GET';

        if (str_starts_with($path, '/api/v1/machine/')) {
            $scope = 'machine-api';
            $auth = true;
            $role = 'api-key';
            $csrf = false;
        } elseif (str_starts_with($path, '/api/v1/app/')) {
            $scope = 'app-api';
            $auth = !in_array($key, $publicAppRoutes, true);
            $csrf = false;

            if (str_starts_with($path, '/api/v1/app/admin/')) {
                $role = 'admin';
            } elseif (str_starts_with($path, '/api/v1/app/partner/')) {
                $role = 'partner|farmer';
            } elseif (str_starts_with($path, '/api/v1/app/supply/')) {
                $role = 'transporter|warehouse|seller';
            }
        } elseif (str_starts_with($path, '/admin')) {
            $scope = 'admin';
            $auth = true;
            $role = 'admin';
        } elseif (str_starts_with($path, '/partner')) {
            $scope = 'partner';
            $auth = true;
            $role = 'partner|farmer';
        } elseif (str_starts_with($path, '/supply')) {
            $scope = 'supply';
            $auth = true;
            $role = 'transporter|warehouse|seller';
        } elseif (str_starts_with($path, '/auth') || str_starts_with($path, '/account')) {
            $scope = 'auth';
            if (str_starts_with($path, '/account') || $path === '/auth/logout') {
                $auth = true;
            }
        }

        if ($path === '/dashboard') {
            $scope = 'dashboard';
            $auth = true;
            $role = 'role-aware';
            $csrf = false;
        } elseif (str_starts_with($path, '/dashboard/admin')) {
            $scope = 'admin';
            $auth = true;
            $role = 'admin';
        }

        $authHeaders = [];
        if (str_starts_with($path, '/api/') && $auth) {
            if ($scope === 'machine-api') {
                $authHeaders[] = $field('X-API-Key', 'string', true, 'agri_xxxxx', 'API key', 'API key');
            } elseif ($scope === 'app-api') {
                $authHeaders[] = $field('Authorization', 'string', true, 'Bearer <token>', 'Bearer token', 'Bearer token');
            }
        }

        $request = $requestNone;
        if ($authHeaders !== []) {
            $request['headers'] = $authHeaders;
        }
        if (in_array($method, ['POST', 'PUT'], true)) {
            if (str_starts_with($path, '/api/')) {
                $request = $requestJson($authHeaders);
            } else {
                $request = $requestForm($csrf);
            }
        }

        if ($method === 'GET' && str_starts_with($path, '/api/v1/app/trace/lookup')) {
            $request = [
                'type' => 'none',
                'headers' => [],
                'query' => [
                    $field('token', 'string', true, 'agrisort-lot-001', 'Token truy xuất', 'Trace token'),
                ],
                'fields' => [],
            ];
        }

        $responses = [];
        $errors = [];
        if (str_starts_with($path, '/api/')) {
            $successStatus = $method === 'POST' ? 201 : 200;
            if ($path === '/api/v1/app/auth/login' || $path === '/api/v1/app/auth/register' || $path === '/api/v1/app/auth/logout') {
                $successStatus = 200;
            }
            $responses[] = $response($successStatus, 'application/json', 'Phản hồi JSON', 'JSON response');
            $errors = [400, 401, 403, 404, 409, 422, 429, 500];
        } elseif ($method === 'GET') {
            $responses[] = $response(200, 'text/html', 'Trang HTML', 'HTML page');
        } else {
            $responses[] = $response(302, 'text/plain', 'Điều hướng', 'Redirect');
            if ($csrf) {
                $errors[] = 419;
            }
        }

        return [
            'method' => $method,
            'path' => $path,
            'scope' => $scope,
            'auth' => $auth,
            'role' => $role,
            'csrf' => $csrf,
            'summary' => [
                'vi' => $method . ' ' . $path,
                'en' => $method . ' ' . $path,
            ],
            'request' => $request,
            'responses' => $responses,
            'errors' => $errors,
        ];
    };

    $routes = array_map($toRoute, $allRoutes);

    return [
        'version' => 'v2',
        'routes' => $routes,
    ];
})();
