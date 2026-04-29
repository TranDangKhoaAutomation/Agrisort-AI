<?php
$pageTitle = lang_text('Studio quản trị website', 'Website admin studio');
require BASE_PATH . '/resources/views/layouts/app_header.php';

$counts = is_array($counts ?? null) ? $counts : [];
$partners = is_array($partners ?? null) ? $partners : [];
$lots = is_array($lots ?? null) ? $lots : [];
$apiKeys = is_array($apiKeys ?? null) ? $apiKeys : [];
$blogPosts = is_array($blogPosts ?? null) ? $blogPosts : [];

$partnerStatusLabel = static function (string $status): string {
    return match ($status) {
        'active' => lang_text('Đang hoạt động', 'Active'),
        'pending' => lang_text('Chờ duyệt', 'Pending'),
        'suspended' => lang_text('Tạm khóa', 'Suspended'),
        default => $status,
    };
};

$lotStatusLabel = static function (string $status): string {
    return match ($status) {
        'draft' => lang_text('Bản nháp', 'Draft'),
        'published' => lang_text('Đã công khai', 'Published'),
        default => $status,
    };
};

$audienceLabel = static function (string $title, string $audienceVi, string $audienceEn): string {
    return $title . ' ' . lang_text('(' . $audienceVi . ')', '(' . $audienceEn . ')');
};

$adminActions = [
    [
        'href' => app_url('/dashboard/admin/users'),
        'title' => $audienceLabel(lang_text('Quản lý người dùng', 'Manage users'), 'cho mọi role', 'for all roles'),
        'body' => lang_text('Duyệt đối tác, khóa tài khoản và kiểm soát vai trò.', 'Approve partners, suspend accounts, and control roles.'),
    ],
    [
        'href' => app_url('/dashboard/admin/trace'),
        'title' => $audienceLabel(lang_text('Điều phối truy xuất', 'Trace coordination'), 'cho vận chuyển, kho, người bán', 'for transport, warehouse, sellers'),
        'body' => lang_text('Phân công actor và theo dõi timeline truy xuất.', 'Assign actors and monitor the trace timeline.'),
    ],
    [
        'href' => app_url('/dashboard/admin/blog'),
        'title' => $audienceLabel(lang_text('Quản lý blog', 'Blog management'), 'cho website công khai', 'for the public website'),
        'body' => lang_text('Tìm, lọc và chỉnh bài viết theo từng trang.', 'Search, filter, and edit posts page by page.'),
    ],
    [
        'href' => app_url('/dashboard/admin/studio'),
        'title' => $audienceLabel(lang_text('Website Studio', 'Website Studio'), 'cho website công khai', 'for the public website'),
        'body' => lang_text('Chỉnh đúng section cần thiết của các trang chính.', 'Edit only the necessary sections of key pages.'),
    ],
    [
        'href' => app_url('/dashboard/admin/settings'),
        'title' => $audienceLabel(lang_text('Cài đặt hệ thống', 'System settings'), 'cho toàn hệ thống', 'for the full system'),
        'body' => lang_text('SMTP, QR và các cấu hình vận hành cốt lõi.', 'SMTP, QR, and core operating settings.'),
    ],
    [
        'href' => app_url('/dashboard/admin/api-keys'),
        'title' => $audienceLabel(lang_text('API key', 'API keys'), 'cho app và máy phân loại', 'for app and sorting machines'),
        'body' => lang_text('Tạo và xem trạng thái các key tích hợp máy móc.', 'Create and review integration keys for machines.'),
    ],
];
?>
<section class="container page-section admin-dashboard-page admin-dashboard-hub">
  <div class="dashboard-hero dashboard-hero-admin card admin-hub-hero">
    <div class="dashboard-hero-copy">
      <p class="section-kicker"><?= e(lang_text('Trung tâm điều phối', 'Control center')) ?></p>
      <h1><?= e(lang_text('Hub quản trị gọn cho các tác vụ chính', 'Compact admin hub for core tasks')) ?></h1>
      <p><?= e(lang_text(
          'Trang này chỉ giữ overview, KPI và đường tắt. Các form dài đã được tách sang Website Studio, Cài đặt hệ thống, API key và Blog để tránh cuộn quá nhiều.',
          'This page keeps only overview, KPIs, and shortcuts. Long forms have been moved to Website Studio, System Settings, API keys, and Blog to avoid excessive scrolling.'
      )) ?></p>
    </div>
    <div class="dashboard-hero-side">
      <div class="dashboard-pill-list">
        <span><?= e(lang_text('Dashboard gọn', 'Compact dashboard')) ?></span>
        <span><?= e(lang_text('Tác vụ chia nhóm', 'Task grouping')) ?></span>
        <span><?= e(lang_text('Preview tối đa 5 dòng', '5-row previews')) ?></span>
      </div>
      <div class="dashboard-hero-panel">
        <strong><?= e(lang_text('Nguyên tắc hiện tại', 'Current rule')) ?></strong>
        <p><?= e(lang_text(
            'Những gì dùng thường xuyên được đưa lên nav và shortcut. Phần nặng chỉ mở khi cần, không còn nhồi hết vào dashboard.',
            'Frequently used areas stay in nav and shortcuts. Heavy editing screens open only when needed instead of being packed into the dashboard.'
        )) ?></p>
      </div>
    </div>
  </div>

  <div class="admin-hub-action-grid">
    <?php foreach ($adminActions as $index => $action): ?>
      <a href="<?= e((string) $action['href']) ?>" class="card admin-hub-action <?= $index === 0 ? 'is-primary' : '' ?>">
        <strong><?= e((string) $action['title']) ?></strong>
        <p><?= e((string) $action['body']) ?></p>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="kpi-grid kpi-grid-rich kpi-grid-compact">
    <div class="kpi">
      <strong><?= e((string) ($counts['pending_partners'] ?? 0)) ?></strong>
      <span><?= e(lang_text('Đối tác chờ duyệt', 'Partners pending approval')) ?></span>
    </div>
    <div class="kpi">
      <strong><?= e((string) ($counts['active_partners'] ?? 0)) ?></strong>
      <span><?= e(lang_text('Đối tác hoạt động', 'Active partners')) ?></span>
    </div>
    <div class="kpi">
      <strong><?= e((string) ($counts['total_lots'] ?? 0)) ?></strong>
      <span><?= e(lang_text('Tổng số lô', 'Total lots')) ?></span>
    </div>
    <div class="kpi">
      <strong><?= e((string) ($counts['published_lots'] ?? 0)) ?></strong>
      <span><?= e(lang_text('Lô đã công khai', 'Published lots')) ?></span>
    </div>
    <div class="kpi">
      <strong><?= e((string) ($counts['published_posts'] ?? 0)) ?></strong>
      <span><?= e(lang_text('Bài blog đã xuất bản', 'Published blog posts')) ?></span>
    </div>
    <div class="kpi">
      <strong><?= e((string) ($counts['api_keys'] ?? 0)) ?></strong>
      <span><?= e(lang_text('API key đang quản lý', 'Managed API keys')) ?></span>
    </div>
  </div>

  <div class="admin-hub-preview-grid">
    <article class="card admin-hub-preview">
      <div class="section-head section-head-inline">
        <p class="section-kicker"><?= e(lang_text('Kiểm duyệt nhanh', 'Quick approvals')) ?></p>
        <h2><?= e(lang_text('Đối tác cần xử lý', 'Partners needing action')) ?></h2>
      </div>
      <?php if ($partners === []): ?>
        <p class="muted"><?= e(lang_text('Hiện không có tài khoản nào cần xử lý nhanh.', 'There are no accounts needing quick action right now.')) ?></p>
      <?php else: ?>
        <div class="admin-hub-list">
          <?php foreach ($partners as $partner): ?>
            <article class="admin-hub-list-item">
              <strong><?= e((string) ($partner['full_name'] ?? '')) ?></strong>
              <p><?= e((string) ($partner['email'] ?? '')) ?></p>
              <small><?= e($partnerStatusLabel((string) ($partner['status'] ?? 'pending'))) ?></small>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <a href="<?= e(app_url('/dashboard/admin/users')) ?>" class="btn secondary"><?= e($audienceLabel(lang_text('Mở quản lý người dùng', 'Open user management'), 'cho mọi role', 'for all roles')) ?></a>
    </article>

    <article class="card admin-hub-preview">
      <div class="section-head section-head-inline">
        <p class="section-kicker"><?= e(lang_text('Nội dung', 'Content')) ?></p>
        <h2><?= e(lang_text('Bài viết gần đây', 'Recent posts')) ?></h2>
      </div>
      <?php if ($blogPosts === []): ?>
        <p class="muted"><?= e(lang_text('Chưa có bài viết nào.', 'No posts yet.')) ?></p>
      <?php else: ?>
        <div class="admin-hub-list">
          <?php foreach ($blogPosts as $post): ?>
            <article class="admin-hub-list-item">
              <strong><?= e((string) ($post['title_vi'] ?? '')) ?></strong>
              <p><?= e((string) ($post['slug'] ?? '')) ?></p>
              <small><?= e((string) ($post['status'] ?? 'draft')) ?></small>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <a href="<?= e(app_url('/dashboard/admin/blog')) ?>" class="btn secondary"><?= e($audienceLabel(lang_text('Mở trang blog', 'Open blog page'), 'cho website công khai', 'for the public website')) ?></a>
    </article>

    <article class="card admin-hub-preview">
      <div class="section-head section-head-inline">
        <p class="section-kicker"><?= e(lang_text('Lô hàng', 'Lots')) ?></p>
        <h2><?= e(lang_text('Lô gần đây', 'Recent lots')) ?></h2>
      </div>
      <?php if ($lots === []): ?>
        <p class="muted"><?= e(lang_text('Chưa có lô hàng nào.', 'No lots yet.')) ?></p>
      <?php else: ?>
        <div class="admin-hub-list">
          <?php foreach ($lots as $lot): ?>
            <?php $partnerName = (string) (($lot['organization_name'] ?? '') !== '' ? $lot['organization_name'] : ($lot['partner_name'] ?? '')); ?>
            <article class="admin-hub-list-item">
              <strong><?= e((string) ($lot['lot_code'] ?? '')) ?></strong>
              <p><?= e($partnerName) ?></p>
              <small><?= e($lotStatusLabel((string) ($lot['publish_status'] ?? 'draft'))) ?></small>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <a href="<?= e(app_url('/dashboard/admin/trace')) ?>" class="btn secondary"><?= e($audienceLabel(lang_text('Mở điều phối truy xuất', 'Open trace coordination'), 'cho vận chuyển, kho, người bán', 'for transport, warehouse, sellers')) ?></a>
    </article>

    <article class="card admin-hub-preview">
      <div class="section-head section-head-inline">
        <p class="section-kicker"><?= e(lang_text('Tích hợp', 'Integrations')) ?></p>
        <h2><?= e(lang_text('API key gần đây', 'Recent API keys')) ?></h2>
      </div>
      <?php if ($apiKeys === []): ?>
        <p class="muted"><?= e(lang_text('Chưa có API key nào.', 'No API keys yet.')) ?></p>
      <?php else: ?>
        <div class="admin-hub-list">
          <?php foreach ($apiKeys as $apiKey): ?>
            <article class="admin-hub-list-item">
              <strong><?= e((string) ($apiKey['name'] ?? 'API key')) ?></strong>
              <p><?= e(lang_text('Trạng thái', 'Status')) ?>: <?= e((string) ($apiKey['status'] ?? 'active')) ?></p>
              <small><?= e(lang_text('Lần dùng gần nhất', 'Last used')) ?>: <?= e((string) (($apiKey['last_used_at'] ?? '') !== '' ? $apiKey['last_used_at'] : '-')) ?></small>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <a href="<?= e(app_url('/dashboard/admin/api-keys')) ?>" class="btn secondary"><?= e($audienceLabel(lang_text('Mở trang API key', 'Open API key page'), 'cho app và máy phân loại', 'for app and sorting machines')) ?></a>
    </article>
  </div>
</section>
<?php require BASE_PATH . '/resources/views/layouts/app_footer.php'; ?>
