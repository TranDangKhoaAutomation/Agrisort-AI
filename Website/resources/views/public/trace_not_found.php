<?php
$pageTitle = lang_text('Không tìm thấy mã QR', 'QR Not Found');
require BASE_PATH . '/resources/views/layouts/header.php';
?>
<section class="container page-section">
  <article class="card empty-state-card">
    <span class="empty-state-code">QR</span>
    <p class="section-kicker"><?= e(lang_text('Kết quả theo dõi', 'Trace result')) ?></p>
    <h1><?= e(lang_text('Không tìm thấy mã thông báo QR', 'QR token not found')) ?></h1>
    <p><?= e(lang_text('Mã thông báo:', 'Token:')) ?> <?= e((string) $token) ?></p>
    <p><?= e(lang_text('Lô không được công bố hoặc không tồn tại.', 'Lot is not published or does not exist.')) ?></p>
    <div class="inline-actions">
      <a href="<?= e(app_url('/trace')) ?>" class="btn primary"><?= e(lang_text('Quét lại QR', 'Scan again')) ?></a>
      <a href="<?= e(app_url('/')) ?>" class="btn secondary"><?= e(lang_text('Về trang chủ', 'Back home')) ?></a>
    </div>
  </article>
</section>
<?php require BASE_PATH . '/resources/views/layouts/footer.php'; ?>
