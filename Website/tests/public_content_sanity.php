<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

use App\Support\PublicContent;

$errors = [];

$cmsSections = PublicContent::cmsSections();
$viText = '';
foreach ($cmsSections as $payload) {
    $vi = is_array($payload['vi'] ?? null) ? $payload['vi'] : [];
    $viText .= "\n" . (string) ($vi['title'] ?? '');
    $viText .= "\n" . (string) ($vi['body'] ?? '');
}

$requiredPhrases = [
    'AGRISORT-AI: Hệ thống phân loại nông sản thông minh tích hợp truy xuất nguồn gốc QR',
    '93-96%',
    '1.000-2.000 quả/giờ',
    '50-70%',
    'Nguyễn Khắc Tùng Lâm - Team Lead / System Integration',
    'Nguyễn Đăng Quang - AI & Data Pipeline',
    'Võ Thị Mỹ - Business & Market Analysis',
    'Trần Đăng Khoa - Embedded & Control',
    'Lê Hữu Trăng - Mechanical & Automation',
    'KS. Vũ Văn Dũng - Advisor',
    'ThS. Nguyễn Thị Thành - Advisor',
];

foreach ($requiredPhrases as $phrase) {
    if (!str_contains($viText, $phrase)) {
        $errors[] = '[MISSING-PHRASE] public content missing: ' . $phrase;
    }
}

$applicationsPath = BASE_PATH . '/applications.html';
$applicationsHtml = is_file($applicationsPath) ? (string) file_get_contents($applicationsPath) : '';
if ($applicationsHtml === '') {
    $errors[] = '[MISSING-FILE] applications.html is missing or unreadable.';
}

$bannedApplicationTerms = [
    'Apache Friends',
    'Bitnami',
    'phpMyAdmin',
    'apachefriends.org',
    'bitnami.com/xampp',
    '/dashboard/phpinfo.php',
    '/dashboard/faq.html',
];

foreach ($bannedApplicationTerms as $term) {
    if (stripos($applicationsHtml, $term) !== false) {
        $errors[] = '[BANNED-APPLICATIONS-CONTENT] applications.html still contains: ' . $term;
    }
}

if (!preg_match('#href="/"#', $applicationsHtml)) {
    $errors[] = '[MISSING-HOME-LINK] applications.html must contain a direct link to /.';
}

$databaseSqlPath = BASE_PATH . '/database.sql';
$databaseSql = is_file($databaseSqlPath) ? (string) file_get_contents($databaseSqlPath) : '';
if ($databaseSql === '') {
    $errors[] = '[MISSING-FILE] database.sql is missing or unreadable.';
}

$requiredSqlSnippets = [
    "('feasibility', JSON_OBJECT(",
    'Hệ sinh thái và chuỗi liên kết giá trị',
    'Tính khả thi kỹ thuật',
    'Liên hệ dự án',
];

foreach ($requiredSqlSnippets as $snippet) {
    if (!str_contains($databaseSql, $snippet)) {
        $errors[] = '[MISSING-SQL-SNIPPET] database.sql missing: ' . $snippet;
    }
}

$syncScriptPath = BASE_PATH . '/scripts/sync_public_content.php';
if (!is_file($syncScriptPath)) {
    $errors[] = '[MISSING-FILE] scripts/sync_public_content.php is missing.';
}

$sensitiveValues = [
    '0388588812',
    '0973517387',
    '0325270213',
    '0915773938',
    '0971264113',
    '0936763612',
    '0974702006',
    'vvdung@uneti.edu.vn',
    'ntthanh@uneti.edu.vn',
    'nktlam.dhtd16a2cl@sv.uneti.edu.vn',
    'ndquang.dhtd17a2hn@sv.uneti.edu.vn',
    'vtmy.dhkt17a6hn@sv.uneti.edu.vn',
    'tdkhoa.24104300082@sv.uneti.edu.vn',
    'llhtrang24104300107@sv.uneti.edu.vn',
];

$sourcesToCheck = [
    'public_content_source' => $viText,
    'applications.html' => $applicationsHtml,
    'database.sql' => $databaseSql,
];

foreach ($sourcesToCheck as $label => $source) {
    foreach ($sensitiveValues as $value) {
        if ($source !== '' && stripos($source, $value) !== false) {
            $errors[] = '[SENSITIVE-DATA] ' . $label . ' exposes: ' . $value;
        }
    }
}

$homeViewPath = BASE_PATH . '/resources/views/public/home.php';
$homeView = is_file($homeViewPath) ? (string) file_get_contents($homeViewPath) : '';
$expectedHomeImages = [
    BASE_PATH . '/public/uploads/demo/mango-demo.jpg',
    BASE_PATH . '/public/uploads/demo/mango-harvest-demo.jpg',
    BASE_PATH . '/public/uploads/demo/dragonfruit-demo.jpg',
    BASE_PATH . '/public/uploads/demo/red-dragonfruit-market-demo.jpg',
    BASE_PATH . '/public/uploads/demo/orange-demo.jpg',
    BASE_PATH . '/public/uploads/demo/avocado-demo.jpg',
    BASE_PATH . '/public/uploads/demo/avocado-harvest-demo.jpg',
    BASE_PATH . '/public/uploads/demo/avocado-tree-multi-demo.jpg',
    BASE_PATH . '/public/uploads/demo/banana-demo.jpg',
    BASE_PATH . '/public/uploads/demo/banana-tree-demo.jpg',
];

foreach ($expectedHomeImages as $expectedHomeImage) {
    if (!is_file($expectedHomeImage)) {
        $errors[] = '[MISSING-HOME-IMAGE] missing file: ' . str_replace(BASE_PATH . '/', '', $expectedHomeImage);
    }
}

if ($homeView === '') {
    $errors[] = '[MISSING-FILE] resources/views/public/home.php is missing or unreadable.';
} else {
    $requiredHomeSnippets = [
        '/public/uploads/demo/mango-demo.jpg',
        '/public/uploads/demo/mango-harvest-demo.jpg',
        '/public/uploads/demo/dragonfruit-demo.jpg',
        '/public/uploads/demo/red-dragonfruit-market-demo.jpg',
        '/public/uploads/demo/orange-demo.jpg',
        '/public/uploads/demo/avocado-demo.jpg',
        '/public/uploads/demo/avocado-harvest-demo.jpg',
        '/public/uploads/demo/avocado-tree-multi-demo.jpg',
        '/public/uploads/demo/banana-demo.jpg',
        '/public/uploads/demo/banana-tree-demo.jpg',
        'Ảnh chụp thực tế',
    ];

    foreach ($requiredHomeSnippets as $snippet) {
        if (!str_contains($homeView, $snippet)) {
            $errors[] = '[MISSING-HOME-SNIPPET] home.php missing: ' . $snippet;
        }
    }

    $bannedHomeSnippets = [
        'ProductVisualService::illustration',
        '/public/assets/site/hero-platform.svg',
        '/public/assets/site/capability-',
        'capabilityPhotoMap',
        'A fruit packing house in post-harvest operations',
        '/public/uploads/demo/market-cart-demo.jpg',
        '/public/uploads/demo/packinghouse-demo.jpg',
        'Wikimedia Commons',
        'Nguồn ảnh:',
        'real-photo-credit',
    ];

    foreach ($bannedHomeSnippets as $snippet) {
        if (str_contains($homeView, $snippet)) {
            $errors[] = '[BANNED-HOME-SNIPPET] home.php still contains: ' . $snippet;
        }
    }

    preg_match_all('#/public/uploads/demo/[a-z0-9-]+\.jpg#', $homeView, $homeImageMatches);
    $uniqueHomeImages = array_values(array_unique($homeImageMatches[0] ?? []));
    if (count($uniqueHomeImages) < 10) {
        $errors[] = '[INSUFFICIENT-HOME-IMAGES] home.php should reference at least 10 unique real images; found ' . count($uniqueHomeImages);
    }
}

if ($errors === []) {
    echo '[PASS] public_content_sanity: public VI content, applications bridge, and SQL seed are aligned.' . PHP_EOL;
    exit(0);
}

echo '[FAIL] public_content_sanity: issues detected' . PHP_EOL;
foreach ($errors as $error) {
    echo '  - ' . $error . PHP_EOL;
}
exit(1);
