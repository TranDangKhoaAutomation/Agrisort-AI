<?php
$pageTitle = lang_text('Không tìm thấy', 'Not Found');
require BASE_PATH . '/resources/views/layouts/header.php';
?>
<section class="container page-section">
  <article class="card empty-state-card">
    <span class="empty-state-code">404</span>
    <p class="section-kicker"><?= e(lang_text('Trang lỗi', 'Error page')) ?></p>
    <h1><?= e(lang_text('Không tìm thấy nội dung', 'Content not found')) ?></h1>
    <p><?= e(lang_text('Không tìm thấy nội dung được yêu cầu.', 'The requested content was not found.')) ?></p>
    <div class="inline-actions">
      <a href="<?= e(app_url('/')) ?>" class="btn primary"><?= e(lang_text('Về trang chủ', 'Back home')) ?></a>
      <a href="<?= e(app_url('/blog')) ?>" class="btn secondary"><?= e(lang_text('Xem blog', 'View blog')) ?></a>
    </div>
  </article>
</section>
<?php require BASE_PATH . '/resources/views/layouts/footer.php'; ?>
