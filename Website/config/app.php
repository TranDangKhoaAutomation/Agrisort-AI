<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => env('APP_NAME', 'AGRISORT-AI'),
        'env' => env('APP_ENV', 'local'),
        'debug' => env('APP_DEBUG', 'false') === 'true',
        'url' => rtrim((string) env('APP_URL', ''), '/'),
        'timezone' => env('APP_TIMEZONE', 'Asia/Ho_Chi_Minh'),
    ],
    'db' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => (int) env('DB_PORT', '3306'),
        'name' => env('DB_DATABASE', env('DB_NAME', 'agrisort_ai')),
        'user' => env('DB_USERNAME', env('DB_USER', 'root')),
        'pass' => env('DB_PASSWORD', env('DB_PASS', '')),
    ],
    'mail' => [
        'host' => env('SMTP_HOST', 'smtp.gmail.com'),
        'port' => (int) env('SMTP_PORT', '587'),
        'user' => env('SMTP_USER', ''),
        'pass' => env('SMTP_PASS', ''),
        'from' => env('SMTP_FROM', 'admin@gmail.com'),
        'from_name' => env('SMTP_FROM_NAME', 'AGRISORT-AI'),
        'secure' => env('SMTP_SECURE', 'tls'),
    ],
    'services' => [
        'google_translate_api_key' => env('GOOGLE_TRANSLATE_API_KEY', ''),
    ],
    'membership' => [
        'trace_require_login' => env('TRACE_REQUIRE_LOGIN', 'false') === 'true',
        'trace_require_vip' => env('TRACE_REQUIRE_VIP', 'false') === 'true',
    ],
];
