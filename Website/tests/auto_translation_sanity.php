<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

$errors = [];

$fileChecks = [
    BASE_PATH . '/resources/views/admin/studio.php' => [
        'banned' => [
            'name="title_en"',
            'name="body_en"',
            'name="auto_translate_en"',
        ],
        'required' => [],
    ],
    BASE_PATH . '/resources/views/admin/blog_manage.php' => [
        'banned' => [
            'name="title_en"',
            'name="content_en"',
            'data-admin-blog-title-en',
            'data-admin-blog-content-en',
        ],
        'required' => [],
    ],
    BASE_PATH . '/public/js/app.js' => [
        'banned' => [
            '[data-admin-blog-title-en]',
            '[data-admin-blog-content-en]',
        ],
        'required' => [],
    ],
    BASE_PATH . '/app/Controllers/AdminController.php' => [
        'banned' => [
            "input('title_en'",
            "input('content_en'",
            "input('body_en'",
            "input('excerpt_en'",
            "input('auto_translate_en'",
        ],
        'required' => [
            'TranslateService::structuredToEnglish($vi)',
            'TranslateService::toEnglish($titleVi)',
            'TranslateService::toEnglish($contentVi)',
            'TranslateService::toEnglish($excerptVi)',
        ],
    ],
    BASE_PATH . '/app/Controllers/AppAdminApiController.php' => [
        'banned' => [
            "['title_en'] ??",
            "['content_en'] ??",
            "['body_en'] ??",
            "['excerpt_en'] ??",
            "['auto_translate_en'] ??",
        ],
        'required' => [
            'TranslateService::structuredToEnglish($vi)',
            'TranslateService::toEnglish($titleVi)',
            'TranslateService::toEnglish($contentVi)',
            'TranslateService::toEnglish($excerptVi)',
        ],
    ],
    BASE_PATH . '/app/Core/functions.php' => [
        'banned' => [],
        'required' => [
            'TranslateService::toEnglish($vi)',
            'return TranslateService::structuredToEnglish($vi);',
        ],
    ],
    BASE_PATH . '/app/Support/PublicContent.php' => [
        'banned' => [],
        'required' => [
            'TranslateService::toEnglish($vi)',
            'return TranslateService::structuredToEnglish($vi);',
        ],
    ],
];

foreach ($fileChecks as $path => $checks) {
    $contents = is_file($path) ? (string) file_get_contents($path) : '';
    if ($contents === '') {
        $errors[] = '[MISSING-FILE] ' . basename($path) . ' is missing or unreadable.';
        continue;
    }

    foreach ($checks['banned'] as $snippet) {
        if (str_contains($contents, $snippet)) {
            $errors[] = '[BANNED-SNIPPET] ' . basename($path) . ' still contains: ' . $snippet;
        }
    }

    foreach ($checks['required'] as $snippet) {
        if (!str_contains($contents, $snippet)) {
            $errors[] = '[MISSING-SNIPPET] ' . basename($path) . ' is missing: ' . $snippet;
        }
    }
}

if ($errors === []) {
    echo '[PASS] auto_translation_sanity: Vietnamese-first authoring and automatic English generation are enforced.' . PHP_EOL;
    exit(0);
}

echo '[FAIL] auto_translation_sanity: issues detected' . PHP_EOL;
foreach ($errors as $error) {
    echo '  - ' . $error . PHP_EOL;
}
exit(1);
