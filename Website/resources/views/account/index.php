<?php
$pageTitle = t('account_title');
require BASE_PATH . '/resources/views/layouts/app_header.php';

$userData = is_array($userData ?? null) ? $userData : [];
$partnerProfile = is_array($partnerProfile ?? null) ? $partnerProfile : [];
$membershipStatus = is_array($membershipStatus ?? null) ? $membershipStatus : [];
$role = (string) ($userData['role'] ?? '');
$isPartner = in_array($role, ['partner', 'farmer'], true);
$isPartnerOrgRequired = $role === 'partner';
$roleLabel = \App\Models\User::roleLabel($role, lang());
$initials = (string) ($avatarInitials ?? 'U');
$membershipPlanLabel = (string) ($membershipStatus['plan_label'] ?? lang_text('Tài khoản vận hành', 'Operational account'));
$membershipSummary = (string) ($membershipStatus['summary'] ?? '');
$membershipHint = (string) ($membershipStatus['hint'] ?? '');
$membershipVipUntil = (string) ($membershipStatus['vip_until_label'] ?? '');
?>

<section class="container page-section account-shell">
  <article class="card account-hero">
    <div class="avatar-initials"><?= e($initials) ?></div>
    <div class="account-hero-content">
      <p class="section-kicker"><?= e(t('account_manage')) ?></p>
      <h1><?= e((string) ($userData['full_name'] ?? '')) ?></h1>
      <p><?= e((string) ($userData['email'] ?? '')) ?></p>
      <span class="status-pill"><?= e(t('account_role')) ?>: <strong><?= e($roleLabel) ?></strong></span>
    </div>
  </article>

  <div class="account-grid">
    <article class="card account-card">
      <h2><?= e(t('account_profile_title')) ?></h2>
      <p class="muted"><?= e(t('account_profile_hint')) ?></p>

      <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/account/profile')) ?>" class="form-card">
        <?= csrf_field() ?>
        <label>
          <?= e(t('account_full_name')) ?>
          <input type="text" name="full_name" required value="<?= e((string) ($userData['full_name'] ?? '')) ?>">
        </label>
        <label>
          <?= e(t('account_email')) ?>
          <input type="email" name="email" required value="<?= e((string) ($userData['email'] ?? '')) ?>">
        </label>

        <?php if ($isPartner): ?>
          <h3><?= e(t('account_partner_title')) ?></h3>
          <label>
            <?= e(lang_text('Tổ chức / đơn vị phụ trách', 'Organization / operating unit')) ?>
            <input type="text" name="organization_name" <?= $isPartnerOrgRequired ? 'required' : '' ?> value="<?= e((string) ($partnerProfile['organization_name'] ?? '')) ?>">
          </label>
          <label>
            <?= e(t('account_representative')) ?>
            <input type="text" name="representative_name" value="<?= e((string) ($partnerProfile['representative_name'] ?? '')) ?>">
          </label>
          <label>
            <?= e(t('account_phone')) ?>
            <input type="text" name="phone" value="<?= e((string) ($partnerProfile['phone'] ?? '')) ?>">
          </label>
          <label>
            <?= e(t('account_address')) ?>
            <input type="text" name="address" value="<?= e((string) ($partnerProfile['address'] ?? '')) ?>">
          </label>
          <label>
            <?= e(t('account_region')) ?>
            <input type="text" name="region" value="<?= e((string) ($partnerProfile['region'] ?? '')) ?>">
          </label>
          <label>
            <?= e(t('account_tax_code')) ?>
            <input type="text" name="tax_code" value="<?= e((string) ($partnerProfile['tax_code'] ?? '')) ?>">
          </label>
        <?php endif; ?>

        <button type="submit" class="btn primary"><?= e(t('account_save_profile')) ?></button>
      </form>
    </article>

    <article class="card account-card membership-card">
      <h2><?= e(lang_text('Trạng thái truy cập', 'Access status')) ?></h2>
      <p class="muted"><?= e($membershipHint) ?></p>

      <div class="membership-meta">
        <span class="status-pill"><?= e($membershipPlanLabel) ?></span>
        <strong><?= e($membershipSummary) ?></strong>
      </div>

      <?php if (($membershipStatus['daily_limit'] ?? null) !== null): ?>
        <div class="trace-access-metrics">
          <div class="trace-access-metric">
            <strong><?= e((string) ($membershipStatus['daily_limit'] ?? 0)) ?></strong>
            <span><?= e(lang_text('lượt mỗi ngày', 'views per day')) ?></span>
          </div>
          <div class="trace-access-metric">
            <strong><?= e((string) ($membershipStatus['remaining'] ?? 0)) ?></strong>
            <span><?= e(lang_text('còn lại hôm nay', 'remaining today')) ?></span>
          </div>
          <div class="trace-access-metric">
            <strong><?= e((string) ($membershipStatus['used'] ?? 0)) ?></strong>
            <span><?= e(lang_text('đã dùng', 'used')) ?></span>
          </div>
        </div>
      <?php elseif ($membershipVipUntil !== ''): ?>
        <p><strong><?= e(lang_text('Mốc kích hoạt mở rộng đến:', 'Extended-access activation until:')) ?></strong> <?= e($membershipVipUntil) ?></p>
      <?php endif; ?>

      <div class="inline-actions">
        <a class="btn secondary" href="<?= e(app_url('/trace')) ?>"><?= e(lang_text('Mở trung tâm truy xuất', 'Open trace center')) ?></a>
        <a class="btn secondary" href="<?= e(app_url('/billing')) ?>"><?= e(lang_text('Gửi nhu cầu triển khai', 'Submit deployment request')) ?></a>
      </div>
    </article>

    <article class="card account-card">
      <h2><?= e(t('account_password_title')) ?></h2>
      <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/account/password')) ?>" class="form-card">
        <?= csrf_field() ?>
        <label>
          <?= e(t('account_current_password')) ?>
          <input type="password" name="current_password" required>
        </label>
        <label>
          <?= e(t('account_new_password')) ?>
          <input type="password" name="password" required>
        </label>
        <label>
          <?= e(t('account_confirm_password')) ?>
          <input type="password" name="password_confirmation" required>
        </label>
        <button type="submit" class="btn secondary"><?= e(t('account_save_password')) ?></button>
      </form>
    </article>
  </div>
</section>

<?php require BASE_PATH . '/resources/views/layouts/app_footer.php'; ?>
