<?php
$pageTitle = lang_text('API key (cho app và máy phân loại)', 'API keys (for app and sorting machines)');
require BASE_PATH . '/resources/views/layouts/app_header.php';

$apiKeys = is_array($apiKeys ?? null) ? $apiKeys : [];
?>
<section class="container page-section admin-compact-page">
  <div class="section-head section-head-inline">
    <p class="section-kicker"><?= e(lang_text('Vận hành', 'Operations')) ?></p>
    <h1><?= e($pageTitle) ?></h1>
  </div>

  <div class="admin-page-split">
    <article class="card studio-panel admin-single-panel">
      <div class="section-head section-head-inline">
        <p class="section-kicker"><?= e(lang_text('Tích hợp máy móc', 'Machine integration')) ?></p>
        <h2><?= e(lang_text('Tạo API key mới', 'Create a new API key')) ?></h2>
      </div>
      <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/dashboard/admin/api-keys')) ?>" class="form-card compact">
        <?= csrf_field() ?>
        <label><?= e(lang_text('Tên API key', 'API key name')) ?> <input type="text" name="name" value="<?= e(lang_text('Máy phân loại', 'Sorting machine')) ?>"></label>
        <button type="submit" class="btn primary"><?= e(lang_text('Tạo API key mới', 'Create new API key')) ?></button>
      </form>
    </article>

    <article class="card studio-panel admin-single-panel">
      <div class="section-head section-head-inline">
        <p class="section-kicker"><?= e(lang_text('Danh sách gọn', 'Compact list')) ?></p>
        <h2><?= e(lang_text('API key đang quản lý', 'Managed API keys')) ?></h2>
      </div>
      <?php if ($apiKeys === []): ?>
        <p class="muted"><?= e(lang_text('Chưa có API key nào.', 'No API keys yet.')) ?></p>
      <?php else: ?>
        <div class="admin-hub-list admin-api-key-list">
          <?php foreach ($apiKeys as $apiKey): ?>
            <article class="admin-hub-list-item">
              <strong><?= e((string) ($apiKey['name'] ?? 'API key')) ?></strong>
              <p><?= e(lang_text('Trạng thái', 'Status')) ?>: <?= e((string) ($apiKey['status'] ?? 'active')) ?></p>
              <small><?= e(lang_text('Lần dùng gần nhất', 'Last used')) ?>: <?= e((string) (($apiKey['last_used_at'] ?? '') !== '' ? $apiKey['last_used_at'] : '-')) ?></small>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </article>
  </div>
</section>
<?php require BASE_PATH . '/resources/views/layouts/app_footer.php'; ?>
