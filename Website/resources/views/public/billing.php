<?php
$pageTitle = lang_text('Đăng ký triển khai AGRISORT-AI', 'AGRISORT-AI deployment support');
$plans = is_array($plans ?? null) ? $plans : [];
$selectedPlanCode = (string) ($selectedPlanCode ?? '');
$selectedPlan = is_array($plans[$selectedPlanCode] ?? null) ? $plans[$selectedPlanCode] : (count($plans) > 0 ? reset($plans) : null);
$billingPrefill = is_array($billingPrefill ?? null) ? $billingPrefill : [];
$membershipStatus = is_array($membershipStatus ?? null) ? $membershipStatus : [];
$userData = is_array($userData ?? null) ? $userData : [];
$currentPlanLabel = (string) ($membershipStatus['plan_label'] ?? lang_text('Truy xuất công khai', 'Public traceability'));
$currentPlanSummary = (string) ($membershipStatus['summary'] ?? '');
$currentPlanHint = (string) ($membershipStatus['hint'] ?? '');
$currentVipUntil = (string) ($membershipStatus['vip_until_label'] ?? '');
require BASE_PATH . '/resources/views/layouts/header.php';
?>
<section class="container page-section billing-page">
  <div class="section-head section-head-inline">
    <p class="section-kicker"><?= e(lang_text('Hỗ trợ triển khai', 'Deployment support')) ?></p>
    <h1><?= e(lang_text('Đăng ký nhu cầu triển khai và phối hợp vận hành AGRISORT-AI', 'Register AGRISORT-AI deployment and operational support needs')) ?></h1>
  </div>

  <article class="card billing-hero">
    <div class="billing-hero-copy">
      <span class="billing-hero-badge"><?= e(lang_text('Bám theo thuyết minh', 'Proposal-aligned')) ?></span>
      <h2><?= e(lang_text('Trang này dùng để tiếp nhận nhu cầu pilot, mở rộng vận hành và phối hợp dữ liệu theo mã lô, QR truy xuất và quy trình sau thu hoạch.', 'This page captures pilot, rollout, and coordination needs around lot-level data, QR traceability, and post-harvest operations.')) ?></h2>
      <p class="muted"><?= e(lang_text('Không phải trang bán gói dịch vụ. Sau khi gửi biểu mẫu, đội ngũ AGRISORT-AI sẽ liên hệ để làm rõ bối cảnh triển khai, loại nông sản, khối lượng vận hành và mức độ tích hợp mong muốn.', 'This is not a pricing page. After you submit the form, the AGRISORT-AI team will follow up to clarify deployment context, produce category, operating volume, and the desired level of integration.')) ?></p>

      <div class="billing-hero-points">
        <span><?= e(lang_text('Phù hợp cho HTX, trạm thu mua, cơ sở sơ chế', 'Fits cooperatives, buying stations, and post-harvest facilities')) ?></span>
        <span><?= e(lang_text('Gắn với luồng Camera -> AI -> Phân loại -> QR', 'Built around Camera -> AI -> Sorting -> QR workflow')) ?></span>
        <span><?= e(lang_text('Ưu tiên dữ liệu theo mã lô và truy xuất công khai', 'Prioritizes lot-level data and public traceability')) ?></span>
      </div>
    </div>

    <aside class="billing-current-card">
      <p class="section-kicker"><?= e(lang_text('Trạng thái hiện tại', 'Current status')) ?></p>
      <h3><?= e($currentPlanLabel) ?></h3>
      <?php if ($currentPlanSummary !== ''): ?>
        <p><?= e($currentPlanSummary) ?></p>
      <?php endif; ?>
      <?php if ($currentVipUntil !== ''): ?>
        <p><strong><?= e(lang_text('Mốc kích hoạt mở rộng đến:', 'Extended-access activation until:')) ?></strong> <?= e($currentVipUntil) ?></p>
      <?php elseif ($currentPlanHint !== ''): ?>
        <p><?= e($currentPlanHint) ?></p>
      <?php endif; ?>
      <?php if (!empty($userData)): ?>
        <div class="billing-current-meta">
          <span><?= e((string) ($userData['email'] ?? '')) ?></span>
          <span><?= e(\App\Models\User::roleLabel((string) ($userData['role'] ?? ''), lang())) ?></span>
        </div>
      <?php endif; ?>
    </aside>
  </article>

  <div class="billing-plan-grid">
    <?php foreach ($plans as $plan): ?>
      <article class="card billing-plan-card<?= !empty($plan['recommended']) ? ' is-featured' : '' ?>">
        <div class="billing-plan-head">
          <span class="billing-plan-badge"><?= e((string) ($plan['badge'] ?? '')) ?></span>
          <?php if (!empty($plan['recommended'])): ?>
            <span class="billing-plan-recommend"><?= e(lang_text('Ưu tiên', 'Recommended')) ?></span>
          <?php endif; ?>
        </div>
        <h3><?= e((string) ($plan['title'] ?? lang_text('Nhu cầu triển khai', 'Deployment request'))) ?></h3>
        <div class="billing-plan-price">
          <strong><?= e((string) ($plan['duration_label'] ?? '')) ?></strong>
          <span><?= e((string) ($plan['focus_label'] ?? '')) ?></span>
        </div>
        <p class="muted"><?= e((string) ($plan['summary'] ?? '')) ?></p>
        <ul class="billing-feature-list">
          <?php foreach ((array) ($plan['features'] ?? []) as $feature): ?>
            <li><?= e((string) $feature) ?></li>
          <?php endforeach; ?>
        </ul>
        <a class="btn <?= $selectedPlanCode === (string) ($plan['code'] ?? '') ? 'primary' : 'secondary' ?>" href="<?= e(app_url('/billing?plan=' . rawurlencode((string) ($plan['code'] ?? '')) . '#request')) ?>">
          <?= e(lang_text('Chọn nhu cầu này', 'Choose this option')) ?>
        </a>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="billing-request-grid">
    <article id="request" class="card billing-request-card">
      <div class="billing-request-head">
        <div>
          <p class="section-kicker"><?= e(lang_text('Biểu mẫu phối hợp', 'Coordination form')) ?></p>
          <h2><?= e(lang_text('Gửi yêu cầu triển khai', 'Submit deployment request')) ?></h2>
        </div>
        <?php if (is_array($selectedPlan)): ?>
          <span class="status-pill"><?= e((string) ($selectedPlan['title'] ?? lang_text('Triển khai', 'Deployment'))) ?></span>
        <?php endif; ?>
      </div>

      <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/billing/request')) ?>" class="form-card billing-request-form">
        <?= csrf_field() ?>
        <div class="billing-form-grid">
          <label>
            <?= e(lang_text('Nhu cầu ưu tiên', 'Preferred support track')) ?>
            <select name="plan_code" required>
              <?php foreach ($plans as $plan): ?>
                <?php $planCode = (string) ($plan['code'] ?? ''); ?>
                <option value="<?= e($planCode) ?>" <?= ($billingPrefill['plan_code'] ?? '') === $planCode ? 'selected' : '' ?>>
                  <?= e((string) ($plan['title'] ?? '')) ?> - <?= e((string) ($plan['duration_label'] ?? '')) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label>
            <?= e(lang_text('Họ và tên', 'Full name')) ?>
            <input type="text" name="name" required value="<?= e((string) ($billingPrefill['name'] ?? '')) ?>">
          </label>

          <label>
            <?= e(lang_text('Đơn vị / tổ chức', 'Organization / operating unit')) ?>
            <input type="text" name="organization" required value="<?= e((string) ($billingPrefill['organization'] ?? '')) ?>">
          </label>

          <label>
            <?= e(lang_text('Email', 'Email')) ?>
            <input type="email" name="email" required value="<?= e((string) ($billingPrefill['email'] ?? '')) ?>">
          </label>

          <label>
            <?= e(lang_text('Điện thoại', 'Phone')) ?>
            <input type="text" name="phone" value="<?= e((string) ($billingPrefill['phone'] ?? '')) ?>">
          </label>

          <label>
            <?= e(lang_text('Tỉnh / vùng triển khai', 'Province / deployment region')) ?>
            <input type="text" name="province" value="<?= e((string) ($billingPrefill['province'] ?? '')) ?>">
          </label>
        </div>

        <label>
          <?= e(lang_text('Mô tả nhu cầu', 'Describe your needs')) ?>
          <textarea name="message" rows="5" placeholder="<?= e(lang_text('Ví dụ: cần pilot cho xoài/thanh long, cần đồng bộ dữ liệu theo mã lô, muốn dùng QR truy xuất cho khách hàng sau sơ chế...', 'Example: pilot for mango or dragon fruit, lot-level synchronization, QR traceability for customers after post-harvest processing...')) ?>"><?= e((string) ($billingPrefill['message'] ?? '')) ?></textarea>
        </label>

        <div class="billing-request-actions">
          <button type="submit" class="btn primary"><?= e(lang_text('Gửi yêu cầu triển khai', 'Submit deployment request')) ?></button>
          <a href="<?= e(app_url('/trace')) ?>" class="btn secondary"><?= e(lang_text('Mở trung tâm truy xuất', 'Open trace center')) ?></a>
        </div>
      </form>
    </article>

    <article class="card billing-note-card">
      <p class="section-kicker"><?= e(lang_text('Quy trình phối hợp', 'Coordination flow')) ?></p>
      <h2><?= e(lang_text('3 bước làm rõ nhu cầu triển khai', '3 steps to clarify deployment needs')) ?></h2>
      <ol class="billing-step-list">
        <li>
          <strong><?= e(lang_text('Chọn giai đoạn phù hợp', 'Choose the relevant phase')) ?></strong>
          <span><?= e(lang_text('Xác định bạn đang ở giai đoạn pilot, mở rộng vận hành hay chuẩn hóa dài hạn.', 'Identify whether you are in pilot, operational rollout, or longer-term standardization.')) ?></span>
        </li>
        <li>
          <strong><?= e(lang_text('Gửi thông tin đơn vị', 'Submit organizational details')) ?></strong>
          <span><?= e(lang_text('Cung cấp đơn vị, vùng triển khai, loại nông sản và nhu cầu phối hợp dữ liệu.', 'Provide the organization, deployment region, produce type, and data-coordination needs.')) ?></span>
        </li>
        <li>
          <strong><?= e(lang_text('Đội ngũ AGRISORT-AI phản hồi', 'AGRISORT-AI follows up')) ?></strong>
          <span><?= e(lang_text('Chúng tôi liên hệ để thống nhất hướng triển khai, bối cảnh QR truy xuất và các đầu việc vận hành liên quan.', 'We follow up to align on deployment approach, QR traceability context, and the related operational tasks.')) ?></span>
        </li>
      </ol>

      <div class="billing-note-box">
        <strong><?= e(lang_text('Lưu ý', 'Note')) ?></strong>
        <p><?= e(lang_text('Route `/billing` được giữ lại để tương thích kỹ thuật, nhưng nội dung hiện dùng cho hỗ trợ triển khai và phối hợp vận hành, không phải trang bán gói trả phí.', 'The `/billing` route is kept for technical compatibility, but its content now serves deployment support and operational coordination rather than paid-plan sales.')) ?></p>
      </div>
    </article>
  </div>
</section>
<?php require BASE_PATH . '/resources/views/layouts/footer.php'; ?>
