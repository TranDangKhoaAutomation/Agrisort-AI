<?php
$pageTitle = lang_text('Cài đặt hệ thống (cho toàn hệ thống)', 'System settings (for the full system)');
require BASE_PATH . '/resources/views/layouts/app_header.php';

$settings = is_array($settings ?? null) ? $settings : [];
?>
<section class="container page-section admin-compact-page">
  <div class="section-head section-head-inline">
    <p class="section-kicker"><?= e(lang_text('Vận hành', 'Operations')) ?></p>
    <h1><?= e($pageTitle) ?></h1>
  </div>

  <article class="card studio-panel admin-single-panel">
    <div class="section-head section-head-inline">
      <p class="section-kicker"><?= e(lang_text('Cấu hình cốt lõi', 'Core configuration')) ?></p>
      <h2><?= e(lang_text('Thông số vận hành chính', 'Core operating settings')) ?></h2>
    </div>
    <p class="muted"><?= e(lang_text('Giữ ở mức cần thiết: QR, SMTP và cấu hình dịch tự động.', 'Keep only the essentials here: QR, SMTP, and automatic translation settings.')) ?></p>

    <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/dashboard/admin/settings')) ?>" class="form-card compact admin-settings-form">
      <?= csrf_field() ?>
      <label>
        <?= e(lang_text('Tự động công khai lô mới', 'Auto-publish new lots')) ?>
        <select name="auto_publish_lot">
          <option value="1" <?= (($settings['auto_publish_lot'] ?? '1') === '1') ? 'selected' : '' ?>><?= e(lang_text('Bật', 'On')) ?></option>
          <option value="0" <?= (($settings['auto_publish_lot'] ?? '1') === '0') ? 'selected' : '' ?>><?= e(lang_text('Tắt', 'Off')) ?></option>
        </select>
      </label>
      <label><?= e(lang_text('QR base URL', 'QR base URL')) ?> <input type="text" name="qr_base_url" value="<?= e((string) ($settings['qr_base_url'] ?? '')) ?>"></label>
      <label><?= e(lang_text('SMTP host', 'SMTP host')) ?> <input type="text" name="smtp_host" value="<?= e((string) ($settings['smtp_host'] ?? '')) ?>"></label>
      <label><?= e(lang_text('SMTP port', 'SMTP port')) ?> <input type="text" name="smtp_port" value="<?= e((string) ($settings['smtp_port'] ?? '')) ?>"></label>
      <label><?= e(lang_text('SMTP user', 'SMTP user')) ?> <input type="text" name="smtp_user" value="<?= e((string) ($settings['smtp_user'] ?? '')) ?>"></label>
      <label><?= e(lang_text('SMTP password', 'SMTP password')) ?> <input type="text" name="smtp_pass" value="<?= e((string) ($settings['smtp_pass'] ?? '')) ?>"></label>
      <label><?= e(lang_text('Email gửi mặc định', 'Default sender email')) ?> <input type="text" name="smtp_from" value="<?= e((string) ($settings['smtp_from'] ?? '')) ?>"></label>
      <label><?= e(lang_text('Google Translate API key', 'Google Translate API key')) ?> <input type="text" name="google_translate_api_key" value="<?= e((string) ($settings['google_translate_api_key'] ?? '')) ?>"></label>
      <button type="submit" class="btn primary"><?= e(lang_text('Lưu cài đặt', 'Save settings')) ?></button>
    </form>
  </article>
</section>
<?php require BASE_PATH . '/resources/views/layouts/app_footer.php'; ?>
