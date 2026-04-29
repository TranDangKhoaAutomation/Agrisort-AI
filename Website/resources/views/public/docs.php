<?php
$pageTitle = lang_text('Tài liệu dành cho nhà phát triển', 'Developer Documentation');
require BASE_PATH . '/resources/views/layouts/public_header.php';

$docsVersion = (string) ($docsVersion ?? 'v1');
$docsLocale = (string) ($docsLocale ?? lang());
$sectionsNav = is_array($sectionsNav ?? null) ? $sectionsNav : [];
$activeSection = is_array($activeSection ?? null) ? $activeSection : ['slug' => 'overview', 'title' => 'Overview', 'html' => ''];
$routeMatrix = is_array($routeMatrix ?? null) ? $routeMatrix : [];
?>

<section class="container page-section">
  <div class="docs-hero card">
    <div>
      <p class="section-kicker"><?= e(lang_text('Cổng tài liệu dành cho nhà phát triển', 'Documentation portal for developers')) ?></p>
      <h1><?= e(lang_text('Tài liệu dành cho nhà phát triển', 'Developer Documentation')) ?> <span class="docs-version"><?= e(strtoupper($docsVersion)) ?></span></h1>
      <p><?= e(lang_text('Bao gồm trang web công cộng, xác thực, đối tác, quản trị viên và API máy. ', 'Covers public site, auth, partner, admin, and machine API. Content is generated from a route catalog to avoid drift.')) ?></p>
    </div>
    <div class="docs-hero-actions">
      <a href="<?= e(app_url('/docs/openapi.json')) ?>" class="btn secondary" target="_blank" rel="noopener">OpenAPI JSON</a>
      <a href="<?= e(app_url('/docs/postman.json')) ?>" class="btn secondary" target="_blank" rel="noopener">Postman JSON</a>
      <a href="<?= e(app_url('/api-docs/app')) ?>" class="btn secondary"><?= e(lang_text('Tài liệu API ứng dụng', 'App API Docs')) ?></a>
      <a href="<?= e(app_url('/docs?section=machine-api')) ?>" class="btn primary"><?= e(lang_text('Chuyển tới API máy', 'Jump to Machine API')) ?></a>
    </div>
  </div>

  <div class="docs-shell">
    <aside class="docs-aside card">
      <h2><?= e(lang_text('Phần', 'Sections')) ?></h2>
      <nav class="docs-nav">
        <?php foreach ($sectionsNav as $row): ?>
          <?php
            $slug = (string) ($row['slug'] ?? '');
            $title = (string) ($row['title'] ?? $slug);
            $active = !empty($row['active']);
          ?>
          <a
            href="<?= e(app_url('/docs?section=' . urlencode($slug))) ?>"
            class="<?= $active ? 'is-active' : '' ?>"
            aria-current="<?= $active ? 'page' : 'false' ?>"
          >
            <?= e($title) ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <p class="muted"><?= e(lang_text('Ngôn ngữ hiện tại:', 'Current language:')) ?> <strong><?= e(strtoupper($docsLocale)) ?></strong></p>
    </aside>

    <div class="docs-content">
      <article class="docs-article card" id="docs-active-section">
        <div class="docs-article-head">
          <p class="section-kicker"><?= e(lang_text('Nội dung tài liệu', 'Documentation content')) ?></p>
          <h2><?= e((string) $activeSection['title']) ?></h2>
        </div>
        <div class="docs-markdown">
          <?= (string) ($activeSection['html'] ?? '') ?>
        </div>
      </article>

      <article class="docs-routes card">
        <div class="docs-article-head">
          <p class="section-kicker"><?= e(lang_text('Nguồn: tài nguyên/docs/route_catalog.php', 'Source: resources/docs/route_catalog.php')) ?></p>
          <h2><?= e(lang_text('Ma trận tuyến đường', 'Route Matrix')) ?></h2>
        </div>
        <div class="docs-table-wrap">
          <table class="table docs-table">
            <thead>
              <tr>
                <th>Method</th>
                <th>Path</th>
                <th><?= e(lang_text('Phạm vi', 'Scope')) ?></th>
                <th><?= e(lang_text('Xác thực', 'Auth')) ?></th>
                <th><?= e(lang_text('Vai trò', 'Role')) ?></th>
                <th>CSRF</th>
                <th><?= e(lang_text('Bản tóm tắt', 'Summary')) ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($routeMatrix as $route): ?>
                <?php
                  $method = strtoupper((string) ($route['method'] ?? 'GET'));
                  $path = (string) ($route['path'] ?? '/');
                  $scope = (string) ($route['scope'] ?? 'public');
                  $auth = !empty($route['auth']);
                  $role = (string) ($route['role'] ?? '-');
                  $csrf = !empty($route['csrf']);
                  $summary = (string) ($route['summary'] ?? '');
                ?>
                <tr>
                  <td><span class="method-badge method-<?= e(strtolower($method)) ?>"><?= e($method) ?></span></td>
                  <td><code><?= e($path) ?></code></td>
                  <td><span class="scope-badge scope-<?= e(strtolower($scope)) ?>"><?= e($scope) ?></span></td>
                  <td><?= e($auth ? 'yes' : 'no') ?></td>
                  <td><?= e($role !== '' ? $role : '-') ?></td>
                  <td><?= e($csrf ? 'yes' : 'no') ?></td>
                  <td><?= e($summary) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </div>
</section>

<?php require BASE_PATH . '/resources/views/layouts/public_footer.php'; ?>


