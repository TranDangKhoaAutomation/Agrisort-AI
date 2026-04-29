<?php
$pageTitle = lang_text('Đặt lại mật khẩu', 'Reset Password');
require BASE_PATH . '/resources/views/layouts/header.php';
?>
<section class="container page-section">
  <article class="card narrow auth-reset-card auth-reset-card-vivid">
    <p class="section-kicker"><?= e(lang_text('Bảo mật tài khoản', 'Account security')) ?></p>
    <h1><?= e($pageTitle) ?></h1>
    <p class="page-lead"><?= e(lang_text('Cập nhật mật khẩu với giao diện mới rõ ràng, nổi bật và dễ thao tác hơn.', 'Update your password in a clearer, more vibrant, and easier-to-use interface.')) ?></p>
    <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/auth/reset-password')) ?>" class="form-card">
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e((string) $token) ?>">
      <label><?= e(lang_text('Mật khẩu mới', 'New password')) ?> <input type="password" name="password" required></label>
      <label><?= e(lang_text('Xác nhận mật khẩu', 'Confirm password')) ?> <input type="password" name="password_confirmation" required></label>
      <button type="submit" class="btn primary"><?= e(lang_text('Cập nhật mật khẩu', 'Update password')) ?></button>
    </form>
  </article>
</section>
<?php require BASE_PATH . '/resources/views/layouts/footer.php'; ?>
