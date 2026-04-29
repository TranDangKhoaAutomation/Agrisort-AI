<?php
if (function_exists('local_http_origin') && function_exists('is_https_request')) {
    $host = (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
    $localOrigin = local_http_origin($host);
    if ($localOrigin !== null && is_https_request()) {
        header('Location: ' . $localOrigin . (string) ($_SERVER['REQUEST_URI'] ?? '/'), true, 302);
        exit;
    }
}

$pageTitle = $pageTitle ?? 'AGRISORT-AI';
$user = current_user();
$assetVersion = (string) (@filemtime(BASE_PATH . '/public/css/app.css') ?: '20260310');
$currentLang = lang();
$currentPathWithQuery = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$currentPath = (string) (parse_url($currentPathWithQuery, PHP_URL_PATH) ?? '/');
$toggleRoute = $currentLang === 'vi' ? '/en' : '/vi';
$langToggleHref = app_url($toggleRoute . '?redirect=' . urlencode($currentPathWithQuery));
$langToggleLabel = $currentLang === 'vi' ? 'EN' : 'VI';

$isActive = static function (array $prefixes) use ($currentPath): bool {
    foreach ($prefixes as $prefix) {
        if ($prefix === $currentPath || str_starts_with($currentPath, $prefix . '/')) {
            return true;
        }
    }

    return false;
};

$accountName = '';
$accountLabel = t('nav_account');
if ($user) {
    $accountName = trim((string) ($user['full_name'] ?? $user['name'] ?? ''));
    if ($accountName === '') {
        $accountName = t('nav_account');
    }

    if (function_exists('mb_strimwidth')) {
        $accountLabel = mb_strimwidth($accountName, 0, 20, '...', 'UTF-8');
    } else {
        $accountLabel = strlen($accountName) > 20
            ? substr($accountName, 0, 17) . '...'
            : $accountName;
    }
}

$primaryLinks = [
    [
        'href' => app_url('/'),
        'label' => t('nav_home'),
        'active' => $isActive(['/']),
        'class' => '',
    ],
    [
        'href' => app_url('/trace'),
        'label' => t('nav_trace_qr_scan'),
        'active' => $isActive(['/trace']),
        'class' => '',
    ],
];

if ($user) {
    $primaryLinks[] = [
        'href' => app_url('/dashboard'),
        'label' => t('nav_dashboard'),
        'active' => $isActive(['/dashboard', '/admin', '/partner', '/supply']),
        'class' => '',
    ];
    $primaryLinks[] = [
        'href' => app_url('/account'),
        'label' => t('nav_account'),
        'active' => $isActive(['/account']),
        'class' => '',
    ];
}

$secondaryLinks = [
    [
        'href' => app_url('/blog'),
        'label' => t('nav_blog'),
        'active' => $isActive(['/blog']),
    ],
    [
        'href' => app_url('/docs'),
        'label' => t('nav_docs'),
        'active' => $isActive(['/docs']),
    ],
];
?>
<!doctype html>
<html lang="<?= e(lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" type="image/png" href="<?= e(app_url('/public/assets/favicon-agrisort.png?v=20260228')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(app_url('/public/css/app.css?v=' . rawurlencode($assetVersion))) ?>">
  <style>
    .public-layout .site-header {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 110;
      pointer-events: none;
    }

    .public-layout .nav-wrap {
      pointer-events: auto;
    }

    .public-layout .site-main {
      padding-top: var(--public-header-offset);
    }

    @media (max-width: 760px) {
      .public-layout .site-main {
        padding-top: var(--public-header-offset-mobile);
      }
    }
  </style>
</head>
<body class="public-layout">
<div class="site-backdrop" aria-hidden="true">
  <span class="site-backdrop-orb orb-a"></span>
  <span class="site-backdrop-orb orb-b"></span>
  <span class="site-backdrop-orb orb-c"></span>
  <span class="site-backdrop-grid"></span>
</div>
<header class="site-header">
  <div class="container nav-wrap">
    <div class="nav-top-row">
      <a class="brand" href="<?= e(app_url('/')) ?>" aria-label="AGRISORT-AI">
        <img
          src="<?= e(app_url('/public/assets/agrisort-logo-full.png')) ?>"
          alt="AGRISORT-AI logo"
          class="brand-logo-full"
          width="56"
          height="42"
        >
      </a>
      <button
        type="button"
        class="public-nav-toggle"
        data-public-nav-toggle
        aria-controls="public-main-nav"
        aria-expanded="false"
        aria-label="<?= e(lang_text('Mở menu điều hướng', 'Open navigation menu')) ?>"
      >
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
    <nav class="main-nav" id="public-main-nav" data-public-nav>
      <div class="main-nav-group main-nav-group-primary">
        <?php foreach ($primaryLinks as $item): ?>
          <a href="<?= e((string) $item['href']) ?>" class="<?= trim((!empty($item['active']) ? 'is-active ' : '') . (string) ($item['class'] ?? '')) ?>">
            <?= e((string) $item['label']) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="main-nav-group main-nav-group-secondary">
        <?php foreach ($secondaryLinks as $item): ?>
          <a href="<?= e((string) $item['href']) ?>" class="<?= !empty($item['active']) ? 'is-active' : '' ?>">
            <?= e((string) $item['label']) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="main-nav-utility">
        <?php if ($user): ?>
          <span class="nav-user-chip" title="<?= e($accountName) ?>"><?= e($accountLabel) ?></span>
          <a href="<?= e($langToggleHref) ?>" class="lang-toggle"><?= e($langToggleLabel) ?></a>
          <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/auth/logout')) ?>" class="nav-inline-form">
            <?= csrf_field() ?>
            <button type="submit" class="link-btn nav-logout-btn"><?= e(t('logout')) ?></button>
          </form>
        <?php else: ?>
          <a href="<?= e($langToggleHref) ?>" class="lang-toggle"><?= e($langToggleLabel) ?></a>
          <a href="<?= e(app_url('/auth/login')) ?>" class="<?= $isActive(['/auth/login']) ? 'is-active ' : '' ?>nav-cta"><?= e(t('nav_login')) ?></a>
        <?php endif; ?>
      </div>
    </nav>
  </div>
</header>
<main class="site-main">
  <div class="container flash-wrap">
    <?php if ($msg = get_flash('success')): ?>
      <div class="flash success"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = get_flash('error')): ?>
      <div class="flash error"><?= e($msg) ?></div>
    <?php endif; ?>
  </div>
