<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Services\AppApiAuthService;
use App\Services\QrService;

abstract class AppApiBaseController extends Controller
{
    /**
     * @param mixed $data
     * @param array<string,mixed>|null $meta
     */
    protected function ok(string $message, mixed $data = null, ?array $meta = null, int $status = 200): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
            'meta' => $meta,
        ], $status);
    }

    /**
     * @param array<string,mixed>|null $errors
     * @param mixed $data
     */
    protected function fail(string $message, int $status = 400, ?array $errors = null, mixed $data = null): void
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
            'meta' => null,
        ], $status);
    }

    /**
     * @return array<string,mixed>
     */
    protected function payload(): array
    {
        $json = $this->request->json();
        if ($json !== []) {
            return $json;
        }

        return $this->request->all();
    }

    /**
     * @return array<string,mixed>|null
     */
    protected function requireToken(): ?array
    {
        $resolved = AppApiAuthService::resolveUserFromRequest($this->request);
        if ($resolved === null) {
            $this->fail('Token truy cập không hợp lệ hoặc đã hết hạn.', 401);
            return null;
        }

        $user = is_array($resolved['user'] ?? null) ? $resolved['user'] : null;
        if ($user === null || (string) ($user['status'] ?? '') !== 'active') {
            $this->fail('Tài khoản đã bị khóa hoặc chưa được kích hoạt.', 403);
            return null;
        }

        Auth::login($user);
        return $resolved;
    }

    /**
     * @param array<int,string>|string $roles
     * @return array<string,mixed>|null
     */
    protected function requireRole(array|string $roles): ?array
    {
        $resolved = $this->requireToken();
        if ($resolved === null) {
            return null;
        }

        $roleList = is_array($roles) ? $roles : [$roles];
        $roleList = array_values(array_filter($roleList, static fn (mixed $value): bool => is_string($value) && trim($value) !== ''));

        $user = is_array($resolved['user'] ?? null) ? $resolved['user'] : [];
        $userRole = (string) ($user['role'] ?? '');
        if ($roleList !== [] && !in_array($userRole, $roleList, true)) {
            $this->fail('Bạn không có quyền truy cập chức năng này.', 403);
            return null;
        }

        return $resolved;
    }

    protected function fileUrl(?string $path): ?string
    {
        $value = trim((string) $path);
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        return app_url('/' . ltrim($value, '/'));
    }

    protected function traceUrlFromToken(?string $token): ?string
    {
        $value = trim((string) $token);
        if ($value === '') {
            return null;
        }

        return app_url('/trace/' . rawurlencode($value));
    }

    /**
     * @return array{png:?string,svg:?string}|null
     */
    protected function qrExportPayload(?string $token): ?array
    {
        $value = trim((string) $token);
        if ($value === '') {
            return null;
        }

        $urls = QrService::storedImageUrls($value, 8);
        return [
            'png' => (string) ($urls['png'] ?? '') !== '' ? (string) $urls['png'] : null,
            'svg' => (string) ($urls['svg'] ?? '') !== '' ? (string) $urls['svg'] : null,
        ];
    }

    protected function qrPreferredImageUrl(?string $token): ?string
    {
        $export = $this->qrExportPayload($token);
        if (!is_array($export)) {
            return null;
        }

        $png = trim((string) ($export['png'] ?? ''));
        if ($png !== '') {
            return $png;
        }

        $svg = trim((string) ($export['svg'] ?? ''));
        if ($svg !== '') {
            return $svg;
        }

        return null;
    }

    protected function qrPreferredImageFormat(?string $token): ?string
    {
        $export = $this->qrExportPayload($token);
        if (!is_array($export)) {
            return null;
        }

        if (trim((string) ($export['png'] ?? '')) !== '') {
            return 'png';
        }

        if (trim((string) ($export['svg'] ?? '')) !== '') {
            return 'svg';
        }

        return null;
    }
}
