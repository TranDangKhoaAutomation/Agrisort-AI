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
$role = (string) ($user['role'] ?? '');
$assetVersion = (string) (@filemtime(BASE_PATH . '/public/css/app.css') ?: '20260310');
$roleLabel = match ($role) {
    'admin' => lang_text('Quản trị viên', 'Admin'),
    'partner' => lang_text('Đối tác', 'Partner'),
    'farmer' => lang_text('Nông dân', 'Farmer'),
    'transporter' => lang_text('Vận chuyển', 'Transporter'),
    'warehouse' => lang_text('Kho', 'Warehouse'),
    'seller' => lang_text('Người bán', 'Seller'),
    default => lang_text('Tài khoản', 'Account'),
};
$currentPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');

$isActive = static function (array $prefixes) use ($currentPath): bool {
    foreach ($prefixes as $prefix) {
        if ($prefix === $currentPath || str_starts_with($currentPath, $prefix . '/')) {
            return true;
        }
    }

    return false;
};

$isExactPath = static function (array $paths) use ($currentPath): bool {
    return in_array($currentPath, $paths, true);
};

$adminAudienceSuffix = static function (?string $audience) use ($role): string {
    if ($role !== 'admin') {
        return '';
    }

    $audience = trim((string) $audience);
    return $audience !== '' ? ' (' . $audience . ')' : '';
};

$accountName = trim((string) ($user['full_name'] ?? $user['name'] ?? ''));
if ($accountName === '') {
    $accountName = t('nav_account');
}

if (function_exists('mb_strimwidth')) {
    $accountLabel = mb_strimwidth($accountName, 0, 22, '...', 'UTF-8');
} else {
    $accountLabel = strlen($accountName) > 22
        ? substr($accountName, 0, 19) . '...'
        : $accountName;
}

$accountInitials = '';
$accountInitialsCount = 0;
$accountParts = preg_split('/\s+/u', $accountName) ?: [];
foreach ($accountParts as $part) {
    if ($part === '') {
        continue;
    }

    if (function_exists('mb_substr')) {
        $accountInitials .= mb_substr($part, 0, 1, 'UTF-8');
    } else {
        $accountInitials .= substr($part, 0, 1);
    }

    $accountInitialsCount++;
    if ($accountInitialsCount >= 2) {
        break;
    }
}

if ($accountInitials === '') {
    $accountInitials = 'A';
}

if (function_exists('mb_strtoupper')) {
    $accountInitials = mb_strtoupper($accountInitials, 'UTF-8');
} else {
    $accountInitials = strtoupper($accountInitials);
}

$primaryItems = [
    [
        'href' => app_url('/dashboard'),
        'label' => t('nav_dashboard'),
        'active' => $isExactPath(['/dashboard', '/dashboard/admin']),
    ],
];

if ($role === 'admin') {
    $primaryItems[] = [
        'href' => app_url('/dashboard/admin/trace'),
        'label' => lang_text('Điều phối truy xuất', 'Trace coordination') . $adminAudienceSuffix(lang_text('cho vận chuyển, kho, người bán', 'for transport, warehouse, sellers')),
        'active' => $isActive(['/dashboard/admin/trace']),
    ];

    $primaryItems[] = [
        'href' => app_url('/dashboard/admin/users'),
        'label' => lang_text('Quản lý người dùng', 'User management') . $adminAudienceSuffix(lang_text('cho mọi role', 'for all roles')),
        'active' => $isActive(['/dashboard/admin/users']),
    ];
}

if (in_array($role, ['partner', 'farmer'], true)) {
    $primaryItems[] = [
        'href' => app_url('/partner/lots'),
        'label' => lang_text('Lo da tao', 'Created lots'),
        'active' => $isActive(['/partner/lots']),
    ];

    $primaryItems[] = [
        'href' => app_url('/partner/qr'),
        'label' => lang_text('QR nông dân', 'Farmer QR'),
        'active' => $isActive(['/partner/qr']),
    ];
    $primaryItems[count($primaryItems) - 1]['label'] = lang_text('QR va goi hang', 'QR & packages');
}

$contentItems = [
    [
        'href' => app_url($role === 'admin' ? '/dashboard/admin/blog' : '/blog'),
        'label' => $role === 'admin'
            ? lang_text('Quản lý blog', 'Blog management') . $adminAudienceSuffix(lang_text('cho website công khai', 'for the public website'))
            : t('nav_blog'),
        'active' => $role === 'admin'
            ? $isActive(['/dashboard/admin/blog'])
            : $isActive(['/blog']),
    ],
];

$operationsItems = [];

if ($role === 'admin') {
    $contentItems[] = [
        'href' => app_url('/dashboard/admin/studio'),
        'label' => lang_text('Website Studio', 'Website Studio') . $adminAudienceSuffix(lang_text('cho website công khai', 'for the public website')),
        'active' => $isActive(['/dashboard/admin/studio']),
    ];

    $operationsItems[] = [
        'href' => app_url('/dashboard/admin/settings'),
        'label' => lang_text('Cài đặt hệ thống', 'System settings') . $adminAudienceSuffix(lang_text('cho toàn hệ thống', 'for the full system')),
        'active' => $isActive(['/dashboard/admin/settings']),
    ];

    $operationsItems[] = [
        'href' => app_url('/dashboard/admin/api-keys'),
        'label' => lang_text('API key', 'API keys') . $adminAudienceSuffix(lang_text('cho app và máy phân loại', 'for app and sorting machines')),
        'active' => $isActive(['/dashboard/admin/api-keys']),
    ];
}

$supportItems = [
    [
        'href' => app_url('/account'),
        'label' => t('nav_account'),
        'active' => $isActive(['/account']),
    ],
    [
        'href' => app_url('/docs'),
        'label' => t('nav_docs'),
        'active' => $isActive(['/docs']),
    ],
];

$accountMenuItems = [
    [
        'href' => app_url('/account'),
        'label' => t('nav_account'),
        'active' => $isActive(['/account']),
    ],
    [
        'href' => app_url('/docs'),
        'label' => t('nav_docs'),
        'active' => $isActive(['/docs']),
    ],
    [
        'href' => app_url('/'),
        'label' => t('nav_home'),
        'active' => $isExactPath(['/']),
    ],
];

$navSections = array_values(array_filter([
    [
        'label' => lang_text('Tác vụ chính', 'Main tasks'),
        'items' => $primaryItems,
    ],
    [
        'label' => lang_text('Nội dung', 'Content'),
        'items' => $contentItems,
    ],
    [
        'label' => lang_text('Vận hành', 'Operations'),
        'items' => $operationsItems,
    ],
    [
        'label' => lang_text('Tài khoản và hỗ trợ', 'Account and support'),
        'items' => $supportItems,
    ],
], static fn (array $section): bool => $section['items'] !== []));

$activeNavLabel = $pageTitle;
foreach ($navSections as $section) {
    foreach ((array) ($section['items'] ?? []) as $item) {
        if (!empty($item['active'])) {
            $activeNavLabel = (string) ($item['label'] ?? $pageTitle);
            break 2;
        }
    }
}

$workspaceTitle = lang_text('Bảng điều hành AGRISORT', 'AGRISORT console');
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
    .app-layout .app-shell {
      display: grid;
      grid-template-columns: 248px minmax(0, 1fr);
      align-items: start;
    }

    .app-layout .app-sidebar {
      position: sticky;
      top: 10px;
      left: auto;
      bottom: auto;
      width: 248px;
      height: calc(100vh - 20px);
      max-height: calc(100vh - 20px);
      overflow: hidden;
    }

    .app-layout .app-side-nav {
      min-height: 0;
      max-height: 100%;
      overflow-y: auto;
    }

    .app-layout .app-topbar {
      position: sticky;
      top: 10px;
      left: auto;
      right: auto;
      margin: 10px 12px 16px;
    }

    .app-layout .app-content {
      margin-left: 0;
      min-width: 0;
    }

    .app-layout .app-main {
      padding-top: 0;
    }

    .app-layout .app-topbar-right {
      margin-left: auto;
      justify-content: flex-end;
      flex-wrap: wrap;
    }

    @media (max-width: 1040px) {
      .app-layout .app-shell {
        grid-template-columns: 216px minmax(0, 1fr);
      }

      .app-layout .app-sidebar {
        width: 216px;
      }

      .app-layout .app-content {
        margin-left: 0;
      }

      .app-layout .app-topbar {
        left: auto;
      }
    }

    @media (max-width: 760px) {
      .app-layout .app-shell {
        display: block;
      }

      .app-layout .app-sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        display: grid;
        grid-template-rows: auto auto minmax(0, 1fr) auto auto;
        width: min(86vw, 300px);
        max-width: 300px;
        min-height: 100dvh;
        height: 100dvh;
        max-height: 100dvh;
        overflow: hidden;
      }

      .app-layout .app-side-nav {
        min-height: 0;
        max-height: 100%;
        overflow-y: auto;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        padding-right: 4px;
        touch-action: pan-y;
      }

      .app-layout .app-topbar {
        position: sticky;
        left: auto;
        right: auto;
        margin: 8px 10px 12px;
        padding: 8px 10px;
        border-radius: 12px;
        gap: 8px;
      }

      .app-layout .app-main {
        padding-top: 0;
      }

      .app-layout .app-topbar-left {
        flex: 1 1 auto;
        flex-basis: auto;
        align-items: center;
      }

      .app-layout .app-topbar-heading-row {
        display: block;
      }

      .app-layout .app-topbar-right {
        width: auto;
        flex: 0 0 auto;
        margin-left: auto;
        justify-content: flex-end;
        gap: 6px;
      }

      .app-layout .app-content {
        margin-left: 0;
      }
    }
  </style>
</head>
<body class="app-layout app-layout-vivid">
<div class="site-backdrop site-backdrop-app" aria-hidden="true">
  <span class="site-backdrop-orb orb-a"></span>
  <span class="site-backdrop-orb orb-b"></span>
  <span class="site-backdrop-orb orb-c"></span>
  <span class="site-backdrop-grid"></span>
</div>
<div class="app-shell">
  <aside class="app-sidebar" data-app-sidebar>
    <div class="app-sidebar-head">
      <a class="app-brand" href="<?= e(app_url('/dashboard')) ?>" aria-label="AGRISORT-AI">
        <img
          src="<?= e(app_url('/public/assets/agrisort-logo-full.png')) ?>"
          alt="AGRISORT-AI logo"
          class="app-brand-logo-full"
          width="61"
          height="46"
        >
      </a>
      <button type="button" class="app-sidebar-close" data-app-sidebar-close aria-label="<?= e(lang_text('Đóng menu', 'Close menu')) ?>">x</button>
    </div>

    <p class="app-sidebar-kicker"><?= e(lang_text('Không gian làm việc', 'Workspace')) ?></p>
    <nav class="app-side-nav">
      <?php foreach ($navSections as $section): ?>
        <div class="app-side-nav-group">
          <p class="app-side-nav-label"><?= e((string) $section['label']) ?></p>
          <?php foreach ((array) $section['items'] as $item): ?>
            <a href="<?= e((string) $item['href']) ?>" class="<?= !empty($item['active']) ? 'is-active' : '' ?>">
              <?= e((string) $item['label']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </nav>

    <div class="app-sidebar-summary">
      <span class="app-sidebar-summary-label"><?= e(lang_text('Đang mở', 'Open now')) ?></span>
      <strong><?= e($pageTitle) ?></strong>
      <p><?= e(lang_text('Vai trò hiện tại: ', 'Current role: ') . $roleLabel) ?></p>
    </div>

    <div class="app-sidebar-footer">
      <a href="<?= e(app_url('/')) ?>" class="btn secondary"><?= e(lang_text('Xem website công khai', 'View public site')) ?></a>
      <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/auth/logout')) ?>" class="app-logout-form">
        <?= csrf_field() ?>
        <button type="submit" class="btn danger"><?= e(t('logout')) ?></button>
      </form>
    </div>
  </aside>

  <div class="app-content">
    <header class="app-topbar">
      <div class="app-topbar-left">
        <button type="button" class="app-sidebar-toggle" data-app-sidebar-toggle aria-label="<?= e(lang_text('Mở menu', 'Open menu')) ?>">
          <span class="app-sidebar-toggle-icon" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
          </span>
          <span><?= e(lang_text('Menu', 'Menu')) ?></span>
        </button>
        <div class="app-topbar-title-wrap">
          <p class="app-topbar-kicker"><?= e(lang_text('Không gian nội bộ', 'Internal workspace')) ?></p>
          <div class="app-topbar-heading-row">
            <p class="app-topbar-title"><?= e($workspaceTitle) ?></p>
            <span class="app-topbar-page-pill"><?= e($activeNavLabel) ?></span>
          </div>
        </div>
      </div>
      <div class="app-topbar-right">
        <span class="app-topbar-role-pill"><?= e($roleLabel) ?></span>
        <details class="nav-account-menu app-account-menu">
          <summary class="app-user-chip" aria-label="<?= e(lang_text('Mở menu tài khoản', 'Open account menu')) ?>">
            <span class="app-user-chip-avatar" aria-hidden="true"><?= e($accountInitials) ?></span>
            <span class="nav-account-label" title="<?= e($accountName) ?>"><?= e($accountLabel) ?></span>
          </summary>
          <div class="nav-account-menu-list">
            <?php foreach ($accountMenuItems as $item): ?>
              <a href="<?= e((string) $item['href']) ?>" class="<?= !empty($item['active']) ? 'is-active' : '' ?>">
                <?= e((string) $item['label']) ?>
              </a>
            <?php endforeach; ?>
            <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/auth/logout')) ?>" class="nav-account-menu-form">
              <?= csrf_field() ?>
              <button type="submit" class="link-btn"><?= e(t('logout')) ?></button>
            </form>
          </div>
        </details>
        <a href="<?= e(app_url('/')) ?>" class="btn secondary app-public-link"><?= e(t('nav_home')) ?></a>
        <span class="lang-toggle app-lang-toggle is-disabled"><?= e(strtoupper(lang())) ?></span>
      </div>
    </header>

    <main class="site-main app-main">
      <div class="container flash-wrap">
        <?php if ($msg = get_flash('success')): ?>
          <div class="flash success"><?= e($msg) ?></div>
        <?php endif; ?>
        <?php if ($msg = get_flash('error')): ?>
          <div class="flash error"><?= e($msg) ?></div>
        <?php endif; ?>
      </div>
