<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $post,
        private readonly array $files,
        private readonly array $server,
        private readonly string $rawBody
    ) {
    }

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path = '/' . trim((string) $uriPath, '/');
        if ($path === '//') {
            $path = '/';
        }

        return new self(
            $method,
            $path,
            $_GET,
            $_POST,
            $_FILES,
            $_SERVER,
            file_get_contents('php://input') ?: ''
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $key, string $default = ''): string
    {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return (string) ($this->server[$serverKey] ?? $default);
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function json(): array
    {
        $decoded = json_decode($this->rawBody, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function queryString(): string
    {
        $queryString = (string) ($this->server['QUERY_STRING'] ?? '');
        return trim($queryString);
    }
}
