<?php
$mode = (string) ($_GET['mode'] ?? 'login');
if (!in_array($mode, ['login', 'register'], true)) {
    $mode = 'login';
}

$isLoginMode = $mode === 'login';
$pageTitle = $isLoginMode
    ? lang_text('Đăng nhập', 'Sign in')
    : lang_text('Đăng ký tài khoản', 'Create account');

$sections = is_array($sections ?? null) ? $sections : [];
$defaults = [
    'auth_login' => [
        'title' => lang_text(
            'Truy cập hệ thống AGRISORT-AI',
            'Access the AGRISORT-AI workspace'
        ),
        'body' => lang_text(
            "Quản lý lô hàng, mã QR và quy trình truy xuất trong một giao diện thống nhất.\n"
            . "Tài khoản sau khi được duyệt sẽ vào đúng khu vận hành theo vai trò của mình.\n"
            . "Mọi tác nhân trong chuỗi cung ứng đều đi qua một luồng kiểm soát dữ liệu tập trung.",
            "Manage lots, QR codes, and traceability workflows from one unified interface.\n"
            . "Approved accounts enter the right operational workspace for their role.\n"
            . "Every actor in the supply chain works through one controlled data flow."
        ),
    ],
    'auth_register' => [
        'title' => lang_text(
            'Tạo tài khoản cho đơn vị tham gia chuỗi cung ứng',
            'Create an account for your supply-chain role'
        ),
        'body' => lang_text(
            "Đăng ký để quản lý lô hàng, cập nhật sự kiện vận hành và truy xuất QR.\n"
            . "Tài khoản mới sẽ chờ quản trị viên phê duyệt trước khi sử dụng đầy đủ.\n"
            . "Khi cần triển khai thực tế, bạn có thể gửi nhu cầu hỗ trợ để đội ngũ AGRISORT-AI cùng phối hợp.",
            "Register to manage lots, update operational events, and work with QR traceability.\n"
            . "New accounts remain pending until an administrator approves them.\n"
            . "When real deployment support is needed, you can submit a request so the AGRISORT-AI team can coordinate with you."
        ),
    ],
];

$sec = [];
foreach ($defaults as $key => $value) {
    $sec[$key] = array_merge($value, $sections[$key] ?? []);
}

$activeSection = $isLoginMode ? $sec['auth_login'] : $sec['auth_register'];
$splitLines = static function (?string $text): array {
    $rows = preg_split('/\R+/u', trim((string) $text)) ?: [];

    return array_values(array_filter(array_map('trim', $rows), static fn (string $row): bool => $row !== ''));
};

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

$authIllustrations = [
    'login' => app_url('/public/assets/site/auth-login.svg'),
    'register' => app_url('/public/assets/site/auth-register.svg'),
];

$showcaseImage = $resolveMedia($activeSection['image'] ?? null);
if ($showcaseImage === '') {
    $showcaseImage = $isLoginMode
        ? $authIllustrations['login']
        : $authIllustrations['register'];
}

$showcaseLines = $splitLines((string) ($activeSection['body'] ?? ''));
require BASE_PATH . '/resources/views/layouts/header.php';
?>
<section class="container page-section auth-shell auth-shell-rich">
  <div class="auth-showcase auth-showcase-rich card">
    <div class="auth-showcase-copy">
      <p class="section-kicker"><?= e($isLoginMode ? lang_text('Không gian truy cập', 'Workspace access') : lang_text('Khởi tạo tài khoản', 'Account onboarding')) ?></p>
      <h1><?= e((string) ($activeSection['title'] ?? 'AGRISORT-AI')) ?></h1>
      <div class="auth-copy-list">
        <?php foreach ($showcaseLines as $line): ?>
          <p><?= e($line) ?></p>
        <?php endforeach; ?>
      </div>

      <div class="auth-benefits auth-benefits-rich">
        <span><?= e(lang_text('Phân quyền theo vai trò', 'Role-based access')) ?></span>
        <span><?= e(lang_text('Theo dõi QR và lô hàng', 'QR and lot tracking')) ?></span>
        <span><?= e(lang_text('Hỗ trợ triển khai khi cần', 'Deployment support when needed')) ?></span>
      </div>

      <div class="auth-showcase-actions">
        <a class="btn primary" href="<?= e($isLoginMode ? app_url('/auth/login?mode=register') : app_url('/auth/login')) ?>">
          <?= e($isLoginMode ? lang_text('Tạo tài khoản', 'Create account') : lang_text('Đã có tài khoản', 'I already have an account')) ?>
        </a>
        <a class="btn secondary" href="<?= e(app_url('/trace')) ?>"><?= e(t('nav_trace_qr_scan')) ?></a>
      </div>
    </div>

    <div class="auth-visual-stage">
      <img src="<?= e($showcaseImage) ?>" alt="<?= e((string) ($activeSection['title'] ?? 'AGRISORT-AI')) ?>" class="auth-showcase-image">
      <div class="auth-spotlight-card auth-spotlight-top">
        <strong><?= e(lang_text('Tài khoản vận hành', 'Operational access')) ?></strong>
        <p><?= e(lang_text('Được phân quyền theo vai trò sau khi tài khoản được phê duyệt.', 'Role-based access is enabled after the account is approved.')) ?></p>
      </div>
      <div class="auth-spotlight-card auth-spotlight-bottom">
        <strong><?= e(lang_text('Hỗ trợ triển khai', 'Deployment support')) ?></strong>
        <p><?= e(lang_text('Dùng khi cần trao đổi pilot, mở rộng vận hành hoặc tích hợp thực tế.', 'Used when pilot, rollout, or real deployment coordination is needed.')) ?></p>
      </div>
    </div>
  </div>

  <article class="card auth-card auth-card-rich">
    <div class="auth-card-head">
      <div>
        <p class="section-kicker"><?= e($isLoginMode ? lang_text('Đăng nhập hệ thống', 'Sign in') : lang_text('Đăng ký tham gia', 'Create account')) ?></p>
        <h2><?= e($pageTitle) ?></h2>
      </div>
      <div class="auth-mode-switch">
        <a href="<?= e(app_url('/auth/login')) ?>" class="<?= $isLoginMode ? 'is-active' : '' ?>"><?= e(lang_text('Đăng nhập', 'Sign in')) ?></a>
        <a href="<?= e(app_url('/auth/login?mode=register')) ?>" class="<?= !$isLoginMode ? 'is-active' : '' ?>"><?= e(lang_text('Đăng ký', 'Register')) ?></a>
      </div>
    </div>

    <?php if ($isLoginMode): ?>
      <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/auth/login')) ?>" class="form-card auth-form-main">
        <?= csrf_field() ?>
        <label>Email <input type="email" name="email" required value="<?= e(old('email')) ?>"></label>
        <label><?= e(lang_text('Mật khẩu', 'Password')) ?> <input type="password" name="password" required></label>
        <button type="submit" class="btn primary"><?= e(lang_text('Đăng nhập', 'Sign in')) ?></button>
      </form>

      <div class="auth-support-card">
        <h3><?= e(lang_text('Quên mật khẩu?', 'Forgot your password?')) ?></h3>
        <p><?= e(lang_text(
            'Nhập email để nhận liên kết đặt lại. Liên kết có hiệu lực trong thời gian ngắn.',
            'Enter your email to receive a reset link. The link stays valid for a limited time.'
        )) ?></p>
        <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/auth/forgot-password')) ?>" class="form-card compact">
          <?= csrf_field() ?>
          <label>Email <input type="email" name="email" required></label>
          <button type="submit" class="btn secondary"><?= e(lang_text('Gửi liên kết đặt lại', 'Send reset link')) ?></button>
        </form>
      </div>
    <?php else: ?>
      <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/auth/register')) ?>" class="form-card auth-form-main auth-form-grid">
        <?= csrf_field() ?>
        <label>
          <?= e(lang_text('Vai trò', 'Role')) ?>
          <select name="role" required>
            <?php $selectedRole = old('role', 'partner'); ?>
            <option value="partner" <?= $selectedRole === 'partner' ? 'selected' : '' ?>><?= e(lang_text('Đối tác / Hợp tác xã', 'Partner / Cooperative')) ?></option>
            <option value="farmer" <?= $selectedRole === 'farmer' ? 'selected' : '' ?>><?= e(lang_text('Nông dân', 'Farmer')) ?></option>
            <option value="transporter" <?= $selectedRole === 'transporter' ? 'selected' : '' ?>><?= e(lang_text('Vận chuyển', 'Transporter')) ?></option>
            <option value="warehouse" <?= $selectedRole === 'warehouse' ? 'selected' : '' ?>><?= e(lang_text('Kho', 'Warehouse')) ?></option>
            <option value="seller" <?= $selectedRole === 'seller' ? 'selected' : '' ?>><?= e(lang_text('Người bán', 'Seller')) ?></option>
          </select>
        </label>
        <label><?= e(lang_text('Họ và tên', 'Full name')) ?> <input type="text" name="full_name" required value="<?= e(old('full_name')) ?>"></label>
        <label>Email <input type="email" name="email" required value="<?= e(old('email')) ?>"></label>
        <label><?= e(lang_text('Mật khẩu (ít nhất 8 ký tự)', 'Password (at least 8 characters)')) ?> <input type="password" name="password" required></label>
        <label><?= e(lang_text('Tên tổ chức / đơn vị', 'Organization / operating unit')) ?> <input type="text" name="organization_name" value="<?= e(old('organization_name')) ?>"></label>
        <label><?= e(lang_text('Người đại diện', 'Representative')) ?> <input type="text" name="representative_name" value="<?= e(old('representative_name')) ?>"></label>
        <label><?= e(lang_text('Điện thoại', 'Phone')) ?> <input type="text" name="phone" value="<?= e(old('phone')) ?>"></label>
        <label><?= e(lang_text('Địa chỉ', 'Address')) ?> <input type="text" name="address" value="<?= e(old('address')) ?>"></label>
        <label><?= e(lang_text('Khu vực', 'Region')) ?> <input type="text" name="region" value="<?= e(old('region')) ?>"></label>
        <label><?= e(lang_text('Mã số thuế', 'Tax code')) ?> <input type="text" name="tax_code" value="<?= e(old('tax_code')) ?>"></label>
        <div class="auth-form-wide">
          <button type="submit" class="btn primary"><?= e(lang_text('Đăng ký tài khoản', 'Create account')) ?></button>
        </div>
      </form>

      <div class="auth-support-card">
        <h3><?= e(lang_text('Lưu ý phê duyệt', 'Approval note')) ?></h3>
        <p><?= e(lang_text(
            'Tài khoản mới cần được quản trị viên phê duyệt trước khi dùng đầy đủ các chức năng vận hành.',
            'New accounts must be approved by an administrator before full operational access is enabled.'
        )) ?></p>
      </div>
    <?php endif; ?>
  </article>
</section>
<?php require BASE_PATH . '/resources/views/layouts/footer.php'; ?>
