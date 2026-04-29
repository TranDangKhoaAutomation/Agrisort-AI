<?php
declare(strict_types=1);

namespace App\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

final class DocsService
{
    /** @var array<string,mixed>|null */
    private ?array $manifestCache = null;
    /** @var array<string,mixed>|null */
    private ?array $catalogCache = null;
    private ?MarkdownConverter $markdownConverter = null;
    private bool $markdownInitialized = false;

    /**
     * @return array<string,mixed>
     */
    public function portalData(string $locale, string $requestedSection = ''): array
    {
        $locale = $this->resolveLocale($locale);
        $manifest = $this->manifest();
        $sections = $this->sectionsForLocale($locale);

        $fallbackSlug = (string) ($manifest['sections'][0]['slug'] ?? 'overview');
        $activeSlug = $requestedSection !== '' ? $requestedSection : $fallbackSlug;
        if (!isset($sections[$activeSlug])) {
            $activeSlug = $fallbackSlug;
        }

        $activeSection = $sections[$activeSlug] ?? [
            'slug' => $fallbackSlug,
            'title' => $fallbackSlug,
            'html' => '',
            'markdown' => '',
        ];

        $nav = [];
        foreach ($manifest['sections'] as $row) {
            $slug = (string) ($row['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $titleMap = is_array($row['title'] ?? null) ? $row['title'] : [];
            $nav[] = [
                'slug' => $slug,
                'title' => (string) ($titleMap[$locale] ?? $titleMap['en'] ?? $slug),
                'active' => $slug === $activeSlug,
            ];
        }

        return [
            'docsVersion' => (string) ($manifest['version'] ?? 'v1'),
            'docsLocale' => $locale,
            'sectionsNav' => $nav,
            'activeSection' => $activeSection,
            'routeMatrix' => $this->routeMatrix($locale),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function buildOpenApi(string $locale = 'en', string $pathPrefix = ''): array
    {
        $locale = $this->resolveLocale($locale);
        $catalog = $this->routeCatalog();
        $routes = $this->filteredRoutes($pathPrefix);

        $paths = [];
        foreach ($routes as $route) {
            if (!is_array($route)) {
                continue;
            }

            $method = strtoupper((string) ($route['method'] ?? 'GET'));
            $path = (string) ($route['path'] ?? '/');
            if ($path === '') {
                continue;
            }

            $methodKey = strtolower($method);
            $operation = [
                'tags' => [(string) ($route['scope'] ?? 'public')],
                'operationId' => $this->operationId($method, $path),
                'summary' => $this->localized($route['summary'] ?? [], $locale),
                'description' => $this->localized($route['description'] ?? [], $locale),
                'responses' => $this->openApiResponses($route, $locale),
            ];

            $parameters = $this->openApiParameters($route, $locale);
            if ($parameters !== []) {
                $operation['parameters'] = $parameters;
            }

            $requestBody = $this->openApiRequestBody($route, $locale);
            if ($requestBody !== null) {
                $operation['requestBody'] = $requestBody;
            }

            if (!empty($route['auth'])) {
                if (str_starts_with($path, '/api/v1/machine/')) {
                    $operation['security'] = [['ApiKeyAuth' => []]];
                } elseif (str_starts_with($path, '/api/v1/app/')) {
                    $operation['security'] = [['BearerAuth' => []]];
                } else {
                    $operation['security'] = [['SessionCookieAuth' => []]];
                }
            }

            $paths[$path][$methodKey] = $operation;
        }

        $title = $pathPrefix !== '' ? 'AGRISORT-AI App API Reference' : 'AGRISORT-AI Web/API Reference';
        $description = $pathPrefix !== ''
            ? 'Mobile app API reference for Android/iOS clients (Bearer token).'
            : 'Route catalog for public web, partner/admin dashboard, and machine integration API.';

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => $title,
                'version' => (string) ($catalog['version'] ?? 'v1'),
                'description' => $description,
            ],
            'servers' => [
                ['url' => rtrim((string) app_url(''), '/') ?: 'http://localhost'],
            ],
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'SessionCookieAuth' => [
                        'type' => 'apiKey',
                        'in' => 'cookie',
                        'name' => 'PHPSESSID',
                    ],
                    'ApiKeyAuth' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-Key',
                    ],
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'Token',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function buildPostmanCollection(string $locale = 'en', string $pathPrefix = ''): array
    {
        $locale = $this->resolveLocale($locale);
        $catalog = $this->routeCatalog();
        $routes = $this->filteredRoutes($pathPrefix);

        $groups = [];
        foreach ($routes as $route) {
            if (!is_array($route)) {
                continue;
            }

            $scope = (string) ($route['scope'] ?? 'public');
            $groupName = ucfirst($scope);
            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'name' => $groupName,
                    'item' => [],
                ];
            }

            $groups[$groupName]['item'][] = $this->postmanItem($route, $locale);
        }

        $collectionName = $pathPrefix !== '' ? 'AGRISORT-AI App API Collection' : 'AGRISORT-AI Route Collection';
        $collectionDescription = $pathPrefix !== ''
            ? 'Generated app API collection from resources/docs/route_catalog.php'
            : 'Generated from resources/docs/route_catalog.php';

        return [
            'info' => [
                '_postman_id' => bin2hex(random_bytes(8)),
                'name' => $collectionName,
                'description' => $collectionDescription,
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => array_values($groups),
            'variable' => [
                [
                    'key' => 'base_url',
                    'value' => rtrim((string) app_url(''), '/') ?: 'http://localhost',
                ],
            ],
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function routeMatrix(string $locale, string $pathPrefix = ''): array
    {
        $routes = $this->filteredRoutes($pathPrefix);

        $out = [];
        foreach ($routes as $row) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'method' => strtoupper((string) ($row['method'] ?? 'GET')),
                'path' => (string) ($row['path'] ?? '/'),
                'scope' => (string) ($row['scope'] ?? 'public'),
                'auth' => !empty($row['auth']),
                'role' => (string) ($row['role'] ?? '-'),
                'csrf' => array_key_exists('csrf', $row) ? (bool) $row['csrf'] : true,
                'summary' => $this->localized($row['summary'] ?? [], $locale),
            ];
        }

        return $out;
    }

    /**
     * @return array<string,mixed>
     */
    public function manifest(): array
    {
        if ($this->manifestCache !== null) {
            return $this->manifestCache;
        }

        $path = BASE_PATH . '/resources/docs/manifest.php';
        $manifest = is_file($path) ? require $path : [];
        if (!is_array($manifest)) {
            $manifest = [];
        }

        $manifest['version'] = (string) ($manifest['version'] ?? 'v1');
        if (!is_array($manifest['sections'] ?? null)) {
            $manifest['sections'] = [];
        }

        $this->manifestCache = $manifest;
        return $manifest;
    }

    /**
     * @return array<string,mixed>
     */
    public function routeCatalog(): array
    {
        if ($this->catalogCache !== null) {
            return $this->catalogCache;
        }

        $path = BASE_PATH . '/resources/docs/route_catalog.php';
        $catalog = is_file($path) ? require $path : [];
        if (!is_array($catalog)) {
            $catalog = [];
        }
        if (!is_array($catalog['routes'] ?? null)) {
            $catalog['routes'] = [];
        }
        $catalog['version'] = (string) ($catalog['version'] ?? 'v1');

        $this->catalogCache = $catalog;
        return $catalog;
    }

    private function resolveLocale(string $locale): string
    {
        return $locale === 'en' ? 'en' : 'vi';
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function filteredRoutes(string $pathPrefix = ''): array
    {
        $routes = $this->routeCatalog()['routes'] ?? [];
        if (!is_array($routes)) {
            return [];
        }

        $pathPrefix = trim($pathPrefix);
        if ($pathPrefix === '') {
            return $routes;
        }

        $filtered = [];
        foreach ($routes as $row) {
            if (!is_array($row)) {
                continue;
            }

            $path = (string) ($row['path'] ?? '');
            if ($path !== '' && str_starts_with($path, $pathPrefix)) {
                $filtered[] = $row;
            }
        }

        return $filtered;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function sectionsForLocale(string $locale): array
    {
        $sections = [];
        foreach ($this->manifest()['sections'] as $row) {
            if (!is_array($row)) {
                continue;
            }

            $slug = (string) ($row['slug'] ?? '');
            if ($slug === '') {
                continue;
            }

            $titleMap = is_array($row['title'] ?? null) ? $row['title'] : [];
            $title = (string) ($titleMap[$locale] ?? $titleMap['en'] ?? $slug);
            $markdown = $this->loadMarkdown($locale, $slug);
            $sections[$slug] = [
                'slug' => $slug,
                'title' => $title,
                'markdown' => $markdown,
                'html' => $this->renderMarkdown($markdown),
            ];
        }

        return $sections;
    }

    private function loadMarkdown(string $locale, string $slug): string
    {
        $path = BASE_PATH . '/resources/docs/' . $locale . '/' . $slug . '.md';
        if (!is_file($path)) {
            $fallback = BASE_PATH . '/resources/docs/en/' . $slug . '.md';
            if (is_file($fallback)) {
                $path = $fallback;
            }
        }

        if (!is_file($path)) {
            return '# Missing section' . PHP_EOL . PHP_EOL . 'Section file not found: ' . $slug;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return '# Read error' . PHP_EOL . PHP_EOL . 'Cannot read section: ' . $slug;
        }

        return str_replace("\r\n", "\n", $raw);
    }

    private function renderMarkdown(string $markdown): string
    {
        $converter = $this->markdown();
        if ($converter === null) {
            return '<pre>' . e($markdown) . '</pre>';
        }

        return (string) $converter->convert($markdown);
    }

    private function markdown(): ?MarkdownConverter
    {
        if ($this->markdownInitialized) {
            return $this->markdownConverter;
        }

        $this->markdownInitialized = true;
        if (!class_exists(MarkdownConverter::class)) {
            $this->markdownConverter = null;
            return null;
        }

        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());

        $this->markdownConverter = new MarkdownConverter($environment);
        return $this->markdownConverter;
    }

    private function localized(mixed $value, string $locale): string
    {
        if (is_array($value)) {
            return (string) ($value[$locale] ?? $value['en'] ?? $value['vi'] ?? '');
        }
        return is_string($value) ? $value : '';
    }

    private function operationId(string $method, string $path): string
    {
        $clean = preg_replace('/[^a-z0-9]+/i', '_', trim($path, '/')) ?? 'root';
        $clean = trim($clean, '_');
        if ($clean === '') {
            $clean = 'root';
        }

        return strtolower($method) . '_' . strtolower($clean);
    }

    /**
     * @param array<string,mixed> $route
     * @return array<int,array<string,mixed>>
     */
    private function openApiParameters(array $route, string $locale): array
    {
        $params = [];
        $path = (string) ($route['path'] ?? '/');
        if (preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $path, $matches) > 0) {
            foreach ($matches[1] as $paramName) {
                $params[] = [
                    'name' => $paramName,
                    'in' => 'path',
                    'required' => true,
                    'schema' => ['type' => 'string'],
                    'description' => 'Path parameter: ' . $paramName,
                ];
            }
        }

        $request = is_array($route['request'] ?? null) ? $route['request'] : [];
        $query = is_array($request['query'] ?? null) ? $request['query'] : [];
        foreach ($query as $field) {
            if (!is_array($field)) {
                continue;
            }

            $name = (string) ($field['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $params[] = [
                'name' => $name,
                'in' => 'query',
                'required' => !empty($field['required']),
                'schema' => ['type' => (string) ($field['type'] ?? 'string')],
                'description' => $this->localized($field['description'] ?? [], $locale),
            ];
        }

        $headers = is_array($request['headers'] ?? null) ? $request['headers'] : [];
        foreach ($headers as $field) {
            if (!is_array($field)) {
                continue;
            }

            $name = (string) ($field['name'] ?? '');
            if ($name === '') {
                continue;
            }
            if (strcasecmp($name, 'Content-Type') === 0) {
                continue;
            }

            $params[] = [
                'name' => $name,
                'in' => 'header',
                'required' => !empty($field['required']),
                'schema' => ['type' => (string) ($field['type'] ?? 'string')],
                'description' => $this->localized($field['description'] ?? [], $locale),
            ];
        }

        return $params;
    }

    /**
     * @param array<string,mixed> $route
     * @return array<string,mixed>|null
     */
    private function openApiRequestBody(array $route, string $locale): ?array
    {
        $request = is_array($route['request'] ?? null) ? $route['request'] : [];
        $type = (string) ($request['type'] ?? 'none');
        $fields = is_array($request['fields'] ?? null) ? $request['fields'] : [];

        if ($type === 'none' || $fields === []) {
            return null;
        }

        $properties = [];
        $required = [];
        $example = [];
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            $name = (string) ($field['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $properties[$name] = [
                'type' => (string) ($field['type'] ?? 'string'),
                'description' => $this->localized($field['description'] ?? [], $locale),
            ];

            if (!empty($field['required'])) {
                $required[] = $name;
            }

            if (array_key_exists('example', $field)) {
                $example[$name] = $field['example'];
            }
        }

        $schema = ['type' => 'object', 'properties' => $properties];
        if ($required !== []) {
            $schema['required'] = $required;
        }

        $contentType = $type === 'json' ? 'application/json' : 'application/x-www-form-urlencoded';
        $content = [$contentType => ['schema' => $schema]];
        if ($example !== []) {
            $content[$contentType]['example'] = $example;
        }

        return [
            'required' => $required !== [],
            'content' => $content,
        ];
    }

    /**
     * @param array<string,mixed> $route
     * @return array<string,mixed>
     */
    private function openApiResponses(array $route, string $locale): array
    {
        $responses = [];
        $rows = is_array($route['responses'] ?? null) ? $route['responses'] : [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $status = (string) ($row['status'] ?? '200');
            $description = $this->localized($row['description'] ?? [], $locale);
            if ($description === '') {
                $description = 'Response ' . $status;
            }

            $contentType = (string) ($row['content_type'] ?? '');
            $example = $row['example'] ?? null;
            $response = ['description' => $description];

            if ($contentType !== '') {
                $media = [];
                if (is_array($example)) {
                    $media['example'] = $example;
                } elseif (is_string($example) && $example !== '') {
                    $media['example'] = $example;
                }

                if ($media !== []) {
                    $response['content'] = [$contentType => $media];
                } else {
                    $response['content'] = [$contentType => new \stdClass()];
                }
            }

            $responses[$status] = $response;
        }

        $errors = is_array($route['errors'] ?? null) ? $route['errors'] : [];
        foreach ($errors as $code) {
            $status = (string) $code;
            if (isset($responses[$status])) {
                continue;
            }
            $responses[$status] = [
                'description' => 'Error response ' . $status,
                'content' => [
                    'application/json' => [
                        'example' => [
                            'success' => false,
                            'message' => 'Error ' . $status,
                        ],
                    ],
                ],
            ];
        }

        if ($responses === []) {
            $responses['200'] = ['description' => 'OK'];
        }

        return $responses;
    }

    /**
     * @param array<string,mixed> $route
     * @return array<string,mixed>
     */
    private function postmanItem(array $route, string $locale): array
    {
        $method = strtoupper((string) ($route['method'] ?? 'GET'));
        $path = (string) ($route['path'] ?? '/');
        $summary = $this->localized($route['summary'] ?? [], $locale);

        $headers = [];
        $request = is_array($route['request'] ?? null) ? $route['request'] : [];
        $headerRows = is_array($request['headers'] ?? null) ? $request['headers'] : [];
        foreach ($headerRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = (string) ($row['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $headers[] = [
                'key' => $name,
                'value' => (string) ($row['example'] ?? ''),
                'type' => 'text',
            ];
        }

        $body = null;
        $type = (string) ($request['type'] ?? 'none');
        $fields = is_array($request['fields'] ?? null) ? $request['fields'] : [];
        if ($type === 'json' && $fields !== []) {
            $payload = [];
            foreach ($fields as $field) {
                if (!is_array($field)) {
                    continue;
                }
                $name = (string) ($field['name'] ?? '');
                if ($name === '') {
                    continue;
                }
                $payload[$name] = $field['example'] ?? '';
            }

            $body = [
                'mode' => 'raw',
                'raw' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'options' => [
                    'raw' => ['language' => 'json'],
                ],
            ];
            $headers[] = ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'];
        } elseif ($type === 'form' && $fields !== []) {
            $encoded = [];
            foreach ($fields as $field) {
                if (!is_array($field)) {
                    continue;
                }
                $name = (string) ($field['name'] ?? '');
                if ($name === '') {
                    continue;
                }
                $encoded[] = [
                    'key' => $name,
                    'value' => (string) ($field['example'] ?? ''),
                    'type' => 'text',
                ];
            }

            $body = [
                'mode' => 'urlencoded',
                'urlencoded' => $encoded,
            ];
            $headers[] = ['key' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded', 'type' => 'text'];
        }

        $queryRows = is_array($request['query'] ?? null) ? $request['query'] : [];
        $query = [];
        foreach ($queryRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = (string) ($row['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $query[] = [
                'key' => $name,
                'value' => (string) ($row['example'] ?? ''),
            ];
        }

        $item = [
            'name' => $method . ' ' . $path,
            'request' => [
                'method' => $method,
                'header' => $headers,
                'url' => [
                    'raw' => '{{base_url}}' . $path,
                    'host' => ['{{base_url}}'],
                    'path' => $this->postmanPathSegments($path),
                    'query' => $query,
                ],
                'description' => $summary,
            ],
            'response' => [],
        ];

        if ($body !== null) {
            $item['request']['body'] = $body;
        }

        return $item;
    }

    /**
     * @return array<int,string>
     */
    private function postmanPathSegments(string $path): array
    {
        $trimmed = trim($path, '/');
        if ($trimmed === '') {
            return [];
        }
        return explode('/', $trimmed);
    }
}
