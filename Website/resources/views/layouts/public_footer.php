<?php
$footerUser = current_user();
$footerYear = (string) date('Y');
$footerSecondaryHref = $footerUser ? app_url('/dashboard') : app_url('/docs');
$footerSecondaryLabel = $footerUser ? t('nav_dashboard') : t('nav_docs');
$footerSecondaryCopy = $footerUser
    ? lang_text('Đi thẳng vào khu làm việc theo vai trò của bạn để quản lý lô, QR và truy xuất.', 'Jump back into your role-based workspace to manage lots, QR assets, and traceability.')
    : lang_text('Xem tài liệu trước khi triển khai hoặc gửi yêu cầu hỗ trợ vận hành.', 'Review the docs before deployment or before submitting a deployment-support request.');

$footerExploreLinks = [
    [
        'href' => app_url('/'),
        'label' => t('nav_home'),
        'copy' => lang_text('Trang giới thiệu công khai của hệ thống AGRISORT-AI.', 'Public landing page for the AGRISORT-AI system.'),
    ],
    [
        'href' => app_url('/trace'),
        'label' => t('nav_trace_qr_scan'),
        'copy' => lang_text('Tra cứu lô hàng bằng QR hoặc mã truy xuất.', 'Look up lots by QR or trace token.'),
    ],
    [
        'href' => app_url('/billing'),
        'label' => t('nav_billing'),
        'copy' => lang_text('Gửi nhu cầu pilot, mở rộng hoặc hỗ trợ vận hành.', 'Submit pilot, rollout, or operational-support needs.'),
    ],
    [
        'href' => app_url('/blog'),
        'label' => t('nav_blog'),
        'copy' => lang_text('Tin tức, bài viết và tình huống triển khai.', 'News, articles, and deployment stories.'),
    ],
];

$footerAccountLinks = [
    [
        'href' => $footerUser ? app_url('/account') : app_url('/auth/login'),
        'label' => $footerUser ? t('nav_account') : t('nav_login'),
        'copy' => $footerUser
            ? lang_text('Xem trạng thái tài khoản và hồ sơ vận hành hiện tại.', 'Check your account status and current operational profile.')
            : lang_text('Đăng nhập để vào khu vận hành hoặc hoàn thiện thông tin đơn vị.', 'Sign in to enter the operations workspace or complete your organization profile.'),
    ],
    [
        'href' => $footerSecondaryHref,
        'label' => $footerSecondaryLabel,
        'copy' => $footerSecondaryCopy,
    ],
    [
        'href' => app_url('/docs'),
        'label' => t('nav_docs'),
        'copy' => lang_text('Tài liệu API, tích hợp và hướng dẫn triển khai.', 'API, integration, and deployment documentation.'),
    ],
];
?>
</main>
<footer class="site-footer">
  <div class="container">
    <div class="site-footer-shell">
      <div class="site-footer-top">
        <section class="site-footer-brand">
          <span class="footer-kicker">AGRISORT-AI</span>
          <h2><?= e(lang_text('Truy xuất nông sản rõ ràng, dữ liệu theo mã lô và vận hành sát thực tế sau thu hoạch.', 'Clear agricultural traceability, lot-level data, and operations grounded in real post-harvest workflows.')) ?></h2>
          <p><?= e(t('footer_text')) ?></p>
          <small class="footer-note"><?= e(lang_text('Cuối trang ưu tiên ba việc chính: quay lại khu truy xuất, xem tài liệu kỹ thuật và gửi nhu cầu triển khai nếu cần.', 'The footer prioritizes three key actions: return to traceability, open the technical docs, and submit deployment needs when required.')) ?></small>
          <div class="site-footer-signals" aria-label="<?= e(lang_text('Điểm nổi bật', 'Highlights')) ?>">
            <span><?= e(lang_text('QR truy xuất', 'QR trace')) ?></span>
            <span><?= e(lang_text('Dữ liệu theo mã lô', 'Lot-level data')) ?></span>
            <span><?= e(lang_text('Hỗ trợ triển khai', 'Deployment support')) ?></span>
          </div>
        </section>

        <div class="site-footer-columns">
          <nav class="site-footer-column" aria-label="<?= e(lang_text('Khám phá hệ thống', 'Explore the system')) ?>">
            <span class="site-footer-section-label"><?= e(lang_text('Khám phá', 'Explore')) ?></span>
            <div class="site-footer-link-list">
              <?php foreach ($footerExploreLinks as $link): ?>
                <a href="<?= e((string) $link['href']) ?>">
                  <strong><?= e((string) $link['label']) ?></strong>
                  <span><?= e((string) $link['copy']) ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </nav>

          <nav class="site-footer-column" aria-label="<?= e(lang_text('Tài khoản và tài liệu', 'Account and docs')) ?>">
            <span class="site-footer-section-label"><?= e(lang_text('Tài khoản & tài liệu', 'Account & docs')) ?></span>
            <div class="site-footer-link-list">
              <?php foreach ($footerAccountLinks as $link): ?>
                <a href="<?= e((string) $link['href']) ?>">
                  <strong><?= e((string) $link['label']) ?></strong>
                  <span><?= e((string) $link['copy']) ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </nav>

          <section class="site-footer-cta">
            <span class="site-footer-cta-label"><?= e(lang_text('Triển khai thực tế', 'Real deployment')) ?></span>
            <strong><?= e(lang_text('Gửi nhu cầu pilot hoặc mở rộng vận hành để đội ngũ AGRISORT-AI cùng rà bối cảnh dữ liệu, QR và quy trình sau thu hoạch.', 'Submit pilot or rollout needs so the AGRISORT-AI team can review your data, QR flow, and post-harvest process context.')) ?></strong>
            <p class="site-footer-cta-copy"><?= e(lang_text('Trang hỗ trợ triển khai dùng cho tiếp nhận nhu cầu và trao đổi kỹ thuật, không phải cổng thanh toán dịch vụ.', 'The deployment-support page is used for intake and technical coordination, not as a service checkout page.')) ?></p>
            <div class="site-footer-actions">
              <a href="<?= e(app_url('/billing')) ?>" class="btn primary"><?= e(lang_text('Gửi nhu cầu triển khai', 'Submit deployment request')) ?></a>
              <a href="<?= e($footerSecondaryHref) ?>" class="btn secondary"><?= e($footerSecondaryLabel) ?></a>
            </div>
          </section>
        </div>
      </div>

      <div class="site-footer-bottom">
        <p class="site-footer-legal">&copy; <?= e($footerYear) ?> AGRISORT-AI. <?= e(lang_text('Thiết kế cho truy xuất nông nghiệp thực dụng, dữ liệu minh bạch và triển khai sát nhu cầu vận hành.', 'Built for practical agricultural traceability, transparent data, and deployment aligned with real operations.')) ?></p>
        <div class="site-footer-mini-links" aria-label="<?= e(lang_text('Điều hướng cuối trang', 'Footer navigation')) ?>">
          <a href="<?= e(app_url('/')) ?>"><?= e(t('nav_home')) ?></a>
          <a href="<?= e(app_url('/trace')) ?>"><?= e(t('nav_trace_qr_scan')) ?></a>
          <a href="<?= e(app_url('/billing')) ?>"><?= e(t('nav_billing')) ?></a>
          <a href="<?= e(app_url('/docs')) ?>"><?= e(t('nav_docs')) ?></a>
        </div>
      </div>
    </div>
  </div>
</footer>
<script src="<?= e(app_url('/public/vendor/jsQR.js')) ?>"></script>
<script src="<?= e(app_url('/public/js/app.js')) ?>"></script>
</body>
</html>
