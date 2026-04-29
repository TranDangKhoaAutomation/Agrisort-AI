<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function __construct(private readonly Request $request)
    {
    }

    public function get(string $pattern, ?array $handler = null, array $options = []): void
    {
        $this->add('GET', $pattern, $handler, $options);
    }

    public function post(string $pattern, ?array $handler = null, array $options = []): void
    {
        $this->add('POST', $pattern, $handler, $options);
    }

    public function put(string $pattern, ?array $handler = null, array $options = []): void
    {
        $this->add('PUT', $pattern, $handler, $options);
    }

    public function delete(string $pattern, ?array $handler = null, array $options = []): void
    {
        $this->add('DELETE', $pattern, $handler, $options);
    }

    private function add(string $method, string $pattern, ?array $handler, array $options): void
    {
        $normalized = '/' . trim($pattern, '/');
        if ($normalized === '//') {
            $normalized = '/';
        }

        $this->routes[$method][] = [
            'pattern' => $normalized,
            'handler' => $handler,
            'options' => $options,
        ];
    }

    public function dispatch(): void
    {
        $method = $this->request->method();
        $path = $this->request->path();

        $candidates = $this->routes[$method] ?? [];
        foreach ($candidates as $route) {
            $params = $this->match($route['pattern'], $path);
            if ($params === null) {
                continue;
            }

            if (isset($route['options']['redirect_to'])) {
                $redirectTo = trim((string) $route['options']['redirect_to']);
                if ($redirectTo === '') {
                    Response::abort(500, lang_text('Chuyển hướng tuyến đường không hợp lệ.', 'Invalid route redirect.'));
                }

                $target = $this->interpolateRedirectTarget($redirectTo, $params);
                $queryString = $this->request->queryString();
                if ($queryString !== '') {
                    $target .= str_contains($target, '?') ? '&' : '?';
                    $target .= $queryString;
                }

                $status = (int) ($route['options']['redirect_status'] ?? 302);
                Response::redirect($target, $status);
            }

            if ($method !== 'GET' && ($route['options']['csrf'] ?? true)) {
                $token = $this->request->input('_token');
                if (!CSRF::validate(is_string($token) ? $token : null)) {
                    Response::abort(419, lang_text('Mã thông báo CSRF không khớp.', 'CSRF token mismatch.'));
                }
            }

            if (($route['options']['auth'] ?? false) && !Auth::check()) {
                flash('error', lang_text('Bạn cần phải đăng nhập để tiếp tục.', 'You need to sign in to continue.'));
                Response::redirect('/auth/login');
            }

            if (($route['options']['ensure_active'] ?? false) === true) {
                $freshUser = Auth::refreshFromDatabase();
                if (!is_array($freshUser)) {
                    Auth::logout();
                    flash('error', lang_text('Phiên không hợp lệ. ', 'Invalid session. Please sign in again.'));
                    Response::redirect('/auth/login');
                }

                $freshRole = (string) ($freshUser['role'] ?? '');
                $freshStatus = (string) ($freshUser['status'] ?? '');
                if ($freshRole !== 'admin' && $freshStatus !== 'active') {
                    Auth::logout();
                    flash('error', lang_text('Tài khoản của bạn không còn hoạt động.', 'Your account is no longer active.'));
                    Response::redirect('/auth/login');
                }
            }

            $user = Auth::user();

            if (isset($route['options']['roles'])) {
                $allowedRoles = $route['options']['roles'];
                $normalizedRoles = [];
                if (is_array($allowedRoles)) {
                    foreach ($allowedRoles as $roleName) {
                        if (!is_string($roleName)) {
                            continue;
                        }
                        $roleName = trim($roleName);
                        if ($roleName !== '') {
                            $normalizedRoles[] = $roleName;
                        }
                    }
                }

                if (
                    $normalizedRoles !== []
                    && (!is_array($user) || !in_array((string) ($user['role'] ?? ''), $normalizedRoles, true))
                ) {
                    Response::abort(403, lang_text('Cấm', 'Forbidden'));
                }
            } elseif (isset($route['options']['role'])) {
                $role = (string) $route['options']['role'];
                if (!$user || $user['role'] !== $role) {
                    Response::abort(403, lang_text('Cấm', 'Forbidden'));
                }
            }

            $handler = $route['handler'];
            if (
                !is_array($handler)
                || count($handler) !== 2
                || !is_string($handler[0] ?? null)
                || !is_string($handler[1] ?? null)
            ) {
                Response::abort(500, lang_text('Trình xử lý tuyến đường không hợp lệ.', 'Invalid route handler.'));
            }

            [$controllerClass, $action] = $handler;
            $controller = new $controllerClass($this->request);
            $controller->{$action}($params);
            return;
        }

        Response::abort(404, lang_text('Không tìm thấy', 'Not Found'));
    }

    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z0-9_]+)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $k => $v) {
            if (!is_int($k)) {
                $params[$k] = $v;
            }
        }

        return $params;
    }

    /**
     * @param array<string,string> $params
     */
    private function interpolateRedirectTarget(string $target, array $params): string
    {
        return preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', static function (array $matches) use ($params): string {
            $name = (string) ($matches[1] ?? '');
            if ($name === '') {
                return '';
            }

            return rawurlencode((string) ($params[$name] ?? ''));
        }, $target) ?? $target;
    }
}
