<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

$errors = [];

$publicFiles = [
    BASE_PATH . '/resources/views/public/billing.php',
    BASE_PATH . '/resources/views/public/trace_scan.php',
    BASE_PATH . '/resources/views/auth/login.php',
    BASE_PATH . '/resources/views/account/index.php',
    BASE_PATH . '/resources/views/layouts/public_footer.php',
];

$bannedPublicPhrases = [
    'Gói VIP',
    'VIP plans',
    'Submit VIP upgrade request',
    'Choose VIP plan',
    'Upgrade to VIP',
    'View VIP plans',
];

foreach ($publicFiles as $path) {
    $content = is_file($path) ? (string) file_get_contents($path) : '';
    if ($content === '') {
        $errors[] = '[MISSING-FILE] ' . str_replace(BASE_PATH . '/', '', $path);
        continue;
    }

    foreach ($bannedPublicPhrases as $phrase) {
        if (str_contains($content, $phrase)) {
            $errors[] = '[BANNED-PHRASE] ' . str_replace(BASE_PATH . '/', '', $path) . ' contains: ' . $phrase;
        }
    }
}

$viRbac = is_file(BASE_PATH . '/resources/docs/vi/rbac-auth.md')
    ? (string) file_get_contents(BASE_PATH . '/resources/docs/vi/rbac-auth.md')
    : '';
$enRbac = is_file(BASE_PATH . '/resources/docs/en/rbac-auth.md')
    ? (string) file_get_contents(BASE_PATH . '/resources/docs/en/rbac-auth.md')
    : '';

if (!str_contains($viRbac, '`partner`: dùng `/partner/*`')) {
    $errors[] = '[RBAC-MISMATCH] vi/rbac-auth.md must state that partner uses /partner/*.';
}
if (str_contains($viRbac, 'khong vao khu tao lo') || str_contains($viRbac, 'billing, docs')) {
    $errors[] = '[RBAC-STALE] vi/rbac-auth.md still contains old partner restrictions.';
}
if (!str_contains($enRbac, '`partner`: uses `/partner/*`')) {
    $errors[] = '[RBAC-MISMATCH] en/rbac-auth.md must state that partner uses /partner/*.';
}
if (str_contains($enRbac, 'no lot workspace access')) {
    $errors[] = '[RBAC-STALE] en/rbac-auth.md still contains old partner restrictions.';
}

$envFiles = [
    BASE_PATH . '/.env',
    BASE_PATH . '/.env.example',
];

foreach ($envFiles as $path) {
    $content = is_file($path) ? (string) file_get_contents($path) : '';
    if ($content === '') {
        $errors[] = '[MISSING-FILE] ' . str_replace(BASE_PATH . '/', '', $path);
        continue;
    }

    if (!str_contains($content, 'TRACE_REQUIRE_LOGIN=false')) {
        $errors[] = '[TRACE-DEFAULT] ' . str_replace(BASE_PATH . '/', '', $path) . ' must set TRACE_REQUIRE_LOGIN=false';
    }
    if (!str_contains($content, 'TRACE_REQUIRE_VIP=false')) {
        $errors[] = '[TRACE-DEFAULT] ' . str_replace(BASE_PATH . '/', '', $path) . ' must set TRACE_REQUIRE_VIP=false';
    }
}

$authController = is_file(BASE_PATH . '/app/Controllers/AuthController.php')
    ? (string) file_get_contents(BASE_PATH . '/app/Controllers/AuthController.php')
    : '';
$appAuthController = is_file(BASE_PATH . '/app/Controllers/AppAuthApiController.php')
    ? (string) file_get_contents(BASE_PATH . '/app/Controllers/AppAuthApiController.php')
    : '';

if (str_contains($authController, "in_array(\$role, ['partner', 'farmer'], true)) {\n            \$required[] = 'organization_name';")) {
    $errors[] = '[AUTH-MISMATCH] AuthController still requires organization_name for farmer registration.';
}
if (str_contains($appAuthController, "in_array(\$role, ['partner', 'farmer'], true) && \$profile['organization_name'] === ''")) {
    $errors[] = '[AUTH-MISMATCH] AppAuthApiController still requires organization_name for farmer registration.';
}

if ($errors === []) {
    echo '[PASS] proposal_alignment_sanity: public surface, RBAC docs, and trace defaults align with AGRISORT-AI scope.' . PHP_EOL;
    exit(0);
}

echo '[FAIL] proposal_alignment_sanity: issues detected' . PHP_EOL;
foreach ($errors as $error) {
    echo '  - ' . $error . PHP_EOL;
}
exit(1);
