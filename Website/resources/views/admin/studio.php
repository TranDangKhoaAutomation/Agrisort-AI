<?php
$pageTitle = lang_text('Website Studio (cho website công khai)', 'Website Studio (for the public website)');
require BASE_PATH . '/resources/views/layouts/app_header.php';

$sectionCatalog = is_array($sectionCatalog ?? null) ? $sectionCatalog : [];
$selectedSectionKey = (string) ($selectedSectionKey ?? 'hero');
$selectedSectionMeta = is_array($selectedSectionMeta ?? null) ? $selectedSectionMeta : ['label' => $selectedSectionKey, 'description' => ''];
$cmsSections = is_array($cmsSections ?? null) ? $cmsSections : [];
$selectedSection = is_array($cmsSections[$selectedSectionKey] ?? null) ? $cmsSections[$selectedSectionKey] : [];
$vi = is_array($selectedSection['vi'] ?? null) ? $selectedSection['vi'] : [];

$resolveMedia = static function (?string $path): string {
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    return app_url('/' . ltrim($path, '/'));
};

$currentImage = $resolveMedia($vi['image'] ?? ($en['image'] ?? null));
?>
<section class="container page-section admin-compact-page">
  <div class="section-head section-head-inline">
    <p class="section-kicker"><?= e(lang_text('Nội dung', 'Content')) ?></p>
    <h1><?= e($pageTitle) ?></h1>
  </div>

  <div class="admin-studio-shell">
    <aside class="card admin-studio-nav">
      <div class="section-head section-head-inline">
        <p class="section-kicker"><?= e(lang_text('Trang chính', 'Core pages')) ?></p>
        <h2><?= e(lang_text('Chọn phần cần sửa', 'Choose a section to edit')) ?></h2>
      </div>
      <p class="muted"><?= e(lang_text('Chỉ giữ những section thật sự cần chỉnh cho trang chính và dashboard.', 'Only keep the sections that matter for the main pages and dashboards.')) ?></p>
      <nav class="admin-studio-links">
        <?php foreach ($sectionCatalog as $sectionKey => $meta): ?>
          <a
            href="<?= e(app_url('/dashboard/admin/studio?section=' . rawurlencode((string) $sectionKey))) ?>"
            class="<?= $sectionKey === $selectedSectionKey ? 'is-active' : '' ?>"
          >
            <strong><?= e((string) ($meta['label'] ?? $sectionKey)) ?></strong>
            <small><?= e((string) ($meta['description'] ?? '')) ?></small>
          </a>
        <?php endforeach; ?>
      </nav>
    </aside>

    <article class="card studio-panel admin-single-panel">
      <div class="studio-editor-head">
        <div>
          <h2><?= e((string) ($selectedSectionMeta['label'] ?? $selectedSectionKey)) ?></h2>
          <p><?= e((string) ($selectedSectionMeta['description'] ?? '')) ?></p>
        </div>
        <span class="studio-editor-key"><?= e($selectedSectionKey) ?></span>
      </div>

      <?php if ($currentImage !== ''): ?>
        <div class="studio-image-preview studio-image-preview-compact">
          <img src="<?= e($currentImage) ?>" alt="<?= e((string) ($selectedSectionMeta['label'] ?? $selectedSectionKey)) ?>">
        </div>
      <?php endif; ?>

      <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/dashboard/admin/cms/' . $selectedSectionKey)) ?>" enctype="multipart/form-data" class="form-card compact admin-studio-form">
        <?= csrf_field() ?>
        <label><?= e(lang_text('Tiêu đề VI', 'Title VI')) ?> <input type="text" name="title_vi" value="<?= e((string) ($vi['title'] ?? '')) ?>"></label>
        <label><?= e(lang_text('Nội dung VI', 'Body VI')) ?> <textarea name="body_vi" rows="6"><?= e((string) ($vi['body'] ?? '')) ?></textarea></label>
        <p class="muted"><?= e(lang_text('Chỉ cần cập nhật nội dung tiếng Việt trong biểu mẫu này.', 'Only the Vietnamese content needs to be updated in this form.')) ?></p>
        <label><?= e(lang_text('Ảnh hiển thị', 'Display image')) ?> <input type="file" name="image" accept="image/*"></label>
        <button type="submit" class="btn primary"><?= e(lang_text('Lưu nội dung', 'Save content')) ?></button>
      </form>
    </article>
  </div>
</section>
<?php require BASE_PATH . '/resources/views/layouts/app_footer.php'; ?>
