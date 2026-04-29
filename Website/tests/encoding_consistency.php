<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

$extensions = [
    'php',
    'md',
    'json',
    'sql',
    'txt',
    'css',
    'js',
    'xml',
    'yml',
    'yaml',
    'gradle',
    'kt',
    'java',
];
$extLookup = array_fill_keys($extensions, true);
$bomStrictLookup = array_fill_keys([
    'php',
    'css',
    'js',
    'json',
    'xml',
    'yml',
    'yaml',
    'gradle',
    'kt',
    'java',
], true);

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(BASE_PATH, FilesystemIterator::SKIP_DOTS)
);

$checked = 0;
$errors = [];
$maxErrors = 200;

foreach ($iterator as $fileInfo) {
    if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
        continue;
    }

    $ext = strtolower((string) $fileInfo->getExtension());
    if (!isset($extLookup[$ext])) {
        continue;
    }

    $path = $fileInfo->getPathname();
    $normalizedPath = str_replace('\\', '/', $path);
    if (str_contains($normalizedPath, '/storage/backups/')) {
        continue;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $errors[] = '[READ-FAIL] ' . $path;
        if (count($errors) >= $maxErrors) {
            break;
        }
        continue;
    }

    $checked++;

    if (isset($bomStrictLookup[$ext]) && str_starts_with($content, "\xEF\xBB\xBF")) {
        $errors[] = '[BOM] ' . $path;
    }
    if (!mb_check_encoding($content, 'UTF-8')) {
        $errors[] = '[NOT-UTF8] ' . $path;
    }
    if (str_contains($content, "\xEF\xBF\xBD")) {
        $errors[] = '[REPLACEMENT-CHAR] ' . $path;
    }

    if (count($errors) >= $maxErrors) {
        break;
    }
}

if ($errors === []) {
    echo '[PASS] encoding_consistency: UTF-8/BOM/replacement-char checks passed. Files checked: ' . $checked . PHP_EOL;
    exit(0);
}

echo '[FAIL] encoding_consistency: encoding issues detected' . PHP_EOL;
foreach ($errors as $line) {
    echo '  - ' . $line . PHP_EOL;
}
echo '  Files checked: ' . $checked . PHP_EOL;
exit(1);
