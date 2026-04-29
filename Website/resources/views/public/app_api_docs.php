<?php
$pageTitle = lang_text('Tài liệu API ứng dụng', 'App API Documentation');
require BASE_PATH . '/resources/views/layouts/public_header.php';

$docsVersion = (string) ($docsVersion ?? 'v1');
$docsLocale = (string) ($docsLocale ?? lang());
$appApiHtml = (string) ($appApiHtml ?? '');
$appRouteMatrix = is_array($appRouteMatrix ?? null) ? $appRouteMatrix : [];
?>

<section class="container page-section">
  <div class="docs-hero card">
    <div>
      <p class="section-kicker"><?= e(lang_text('API chuyên dụng cho ứng dụng Android/iOS', 'Dedicated API for Android/iOS app')) ?></p>
      <h1>App API <span class="docs-version"><?= e(strtoupper($docsVersion)) ?></span></h1>
      <p><?= e(lang_text('Trang này tập trung vào các điểm cuối /api/v1/app/* với xác thực mã thông báo Bearer.', 'This page focuses on /api/v1/app/* endpoints with Bearer token authentication.')) ?></p>
      <p class="muted"><?= e(lang_text('Ngôn ngữ hiện tại:', 'Current language:')) ?> <strong><?= e(strtoupper($docsLocale)) ?></strong></p>
    </div>
    <div class="docs-hero-actions">
      <a href="<?= e(app_url('/api-docs/app/openapi.json')) ?>" class="btn secondary" target="_blank" rel="noopener">OpenAPI JSON</a>
      <a href="<?= e(app_url('/api-docs/app/postman.json')) ?>" class="btn secondary" target="_blank" rel="noopener">Postman JSON</a>
      <a href="<?= e(app_url('/docs?section=app-api')) ?>" class="btn primary"><?= e(lang_text('Mở trong cổng tài liệu', 'Open in docs portal')) ?></a>
    </div>
  </div>

  <article class="docs-article card">
    <div class="docs-article-head">
      <p class="section-kicker"><?= e(lang_text('Tổng quan về API', 'API Overview')) ?></p>
      <h2><?= e(lang_text('Hướng dẫn sử dụng', 'Usage Guide')) ?></h2>
    </div>
    <div class="docs-markdown">
      <?= $appApiHtml ?>
    </div>
  </article>

  <article class="docs-routes card">
    <div class="docs-article-head">
      <p class="section-kicker"><?= e(lang_text('Ma trận lộ trình không gian tên ứng dụng', 'App namespace route matrix')) ?></p>
      <h2>App Routes</h2>
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
          <?php foreach ($appRouteMatrix as $route): ?>
            <?php
              $method = strtoupper((string) ($route['method'] ?? 'GET'));
              $path = (string) ($route['path'] ?? '/');
              $scope = (string) ($route['scope'] ?? 'app-api');
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
</section>

<?php require BASE_PATH . '/resources/views/layouts/public_footer.php'; ?>

