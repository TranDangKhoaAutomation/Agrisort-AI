<?php
$pageTitle = lang_text('Quản lý người dùng (cho mọi role)', 'User management (for all roles)');
require BASE_PATH . '/resources/views/layouts/app_header.php';

$users = is_array($users ?? null) ? $users : [];
$filters = is_array($filters ?? null) ? $filters : [];
$pagination = is_array($pagination ?? null) ? $pagination : [];
$allowedRoles = is_array($allowedRoles ?? null) ? $allowedRoles : ['admin', 'partner', 'farmer', 'transporter', 'warehouse', 'seller', 'visitor'];
$allowedStatuses = is_array($allowedStatuses ?? null) ? $allowedStatuses : ['active', 'pending', 'suspended'];
$hasVipColumn = (bool) ($hasVipColumn ?? false);

$currentPage = max(1, (int) ($pagination['page'] ?? 1));
$totalPages = max(1, (int) ($pagination['total_pages'] ?? 1));
$totalUsers = max(0, (int) ($pagination['total'] ?? 0));

$currentFilters = [
    'q' => (string) ($filters['q'] ?? ''),
    'role' => (string) ($filters['role'] ?? 'all'),
    'status' => (string) ($filters['status'] ?? 'all'),
    'per_page' => (string) ($filters['per_page'] ?? '25'),
];

$statusLabel = static function (string $status): string {
    return match ($status) {
        'active' => lang_text('Đang hoạt động', 'Active'),
        'pending' => lang_text('Chờ duyệt', 'Pending'),
        'suspended' => lang_text('Tạm khóa', 'Suspended'),
        default => $status,
    };
};

$roleFilterLabel = static function (string $role): string {
    if ($role === 'all') {
        return lang_text('Tất cả vai trò', 'All roles');
    }

    return \App\Models\User::roleLabel($role, lang());
};

$statusFilterLabel = static function (string $status) use ($statusLabel): string {
    if ($status === 'all') {
        return lang_text('Tất cả trạng thái', 'All statuses');
    }

    return $statusLabel($status);
};

$buildUsersUrl = static function (array $query): string {
    $queryString = http_build_query($query);
    if ($queryString === '') {
        return app_url('/dashboard/admin/users');
    }

    return app_url('/dashboard/admin/users?' . $queryString);
};

$membershipLabel = static function (array $row): string {
    if (\App\Services\MembershipService::hasVip($row)) {
        $vipUntil = \App\Services\MembershipService::formatVipUntil((string) ($row['vip_until'] ?? ''));
        if ($vipUntil !== null) {
            return lang_text('Kích hoạt mở rộng đến ', 'Extended access until ') . $vipUntil;
        }

        return lang_text('Quyền truy cập mở rộng', 'Extended access');
    }

    if (\App\Services\MembershipService::isInternalRole($row)) {
        return lang_text('Tài khoản nội bộ không giới hạn', 'Internal unlimited account');
    }

    return lang_text('Tài khoản vận hành chuẩn', 'Standard operational account');
};

$vipDateInputValue = static function (array $row): string {
    $vipUntil = trim((string) ($row['vip_until'] ?? ''));
    return $vipUntil !== '' ? substr($vipUntil, 0, 10) : '';
};

$queryBase = $currentFilters;
$queryBase['page'] = $currentPage;
$currentRedirect = '/dashboard/admin/users?' . http_build_query($queryBase);
?>
<section class="container page-section admin-users-page">
  <div class="section-head section-head-inline">
    <p class="section-kicker"><?= e(lang_text('Quản trị hệ thống', 'System administration')) ?></p>
    <h1><?= e($pageTitle) ?></h1>
  </div>

  <article class="card">
    <form method="get" accept-charset="UTF-8" action="<?= e(app_url('/dashboard/admin/users')) ?>" class="form-card compact">
      <div class="grid-2">
        <label>
          <?= e(lang_text('Từ khóa', 'Keyword')) ?>
          <input type="text" name="q" value="<?= e($currentFilters['q']) ?>" maxlength="120" placeholder="<?= e(lang_text('Tên, email, tổ chức', 'Name, email, organization')) ?>">
        </label>
        <label>
          <?= e(lang_text('Vai trò', 'Role')) ?>
          <select name="role">
            <option value="all" <?= $currentFilters['role'] === 'all' ? 'selected' : '' ?>><?= e($roleFilterLabel('all')) ?></option>
            <?php foreach ($allowedRoles as $role): ?>
              <option value="<?= e($role) ?>" <?= $currentFilters['role'] === $role ? 'selected' : '' ?>><?= e($roleFilterLabel($role)) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <div class="grid-2">
        <label>
          <?= e(lang_text('Trạng thái', 'Status')) ?>
          <select name="status">
            <option value="all" <?= $currentFilters['status'] === 'all' ? 'selected' : '' ?>><?= e($statusFilterLabel('all')) ?></option>
            <?php foreach ($allowedStatuses as $status): ?>
              <option value="<?= e($status) ?>" <?= $currentFilters['status'] === $status ? 'selected' : '' ?>><?= e($statusFilterLabel($status)) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          <?= e(lang_text('Số dòng mỗi trang', 'Rows per page')) ?>
          <select name="per_page">
            <?php foreach ([10, 25, 50, 100] as $size): ?>
              <option value="<?= e((string) $size) ?>" <?= (int) $currentFilters['per_page'] === $size ? 'selected' : '' ?>><?= e((string) $size) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <div class="inline-actions">
        <button type="submit" class="btn primary"><?= e(lang_text('Áp dụng bộ lọc', 'Apply filters')) ?></button>
        <a href="<?= e(app_url('/dashboard/admin/users')) ?>" class="btn secondary"><?= e(lang_text('Đặt lại', 'Reset')) ?></a>
      </div>
    </form>
  </article>

  <article class="card">
    <div class="section-head section-head-inline">
      <h2><?= e(lang_text('Danh sách người dùng', 'User list')) ?></h2>
      <p class="muted">
        <?= e(lang_text('Tổng cộng', 'Total')) ?>: <strong><?= e((string) $totalUsers) ?></strong>
        | <?= e(lang_text('Trang', 'Page')) ?> <?= e((string) $currentPage) ?>/<?= e((string) $totalPages) ?>
      </p>
    </div>

    <?php if (!$hasVipColumn): ?>
      <p class="muted"><?= e(lang_text('Cột mốc truy cập mở rộng chưa sẵn sàng trong cơ sở dữ liệu, phần gán mốc này có thể chưa hoạt động.', 'The extended-access marker column is not ready in the database yet, so this assignment may not work yet.')) ?></p>
    <?php endif; ?>

    <?php if ($users === []): ?>
      <p><?= e(lang_text('Không có người dùng nào phù hợp với bộ lọc hiện tại.', 'No users match current filters.')) ?></p>
    <?php else: ?>
      <div class="table-shell">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th><?= e(lang_text('Họ tên', 'Full name')) ?></th>
              <th>Email</th>
              <th><?= e(lang_text('Vai trò', 'Role')) ?></th>
              <th><?= e(lang_text('Trạng thái', 'Status')) ?></th>
              <th><?= e(lang_text('Trạng thái truy cập', 'Access status')) ?></th>
              <th><?= e(lang_text('Tổ chức', 'Organization')) ?></th>
              <th><?= e(lang_text('Ngày tạo', 'Created at')) ?></th>
              <th><?= e(lang_text('Thao tác', 'Actions')) ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $row): ?>
              <?php
              $roleValue = (string) ($row['role'] ?? '');
              $statusValue = (string) ($row['status'] ?? '');
              $isVip = \App\Services\MembershipService::hasVip($row);
              $isInternal = \App\Services\MembershipService::isInternalRole($row);
              ?>
              <tr>
                <td><?= e((string) ($row['id'] ?? '')) ?></td>
                <td><?= e((string) ($row['full_name'] ?? '')) ?></td>
                <td><?= e((string) ($row['email'] ?? '')) ?></td>
                <td><?= e(\App\Models\User::roleLabel($roleValue, lang())) ?></td>
                <td><?= e($statusLabel($statusValue)) ?></td>
                <td>
                  <div class="table-cell-stack">
                    <strong><?= e($membershipLabel($row)) ?></strong>
                    <?php if (!$isVip && $isInternal): ?>
                      <span class="muted"><?= e(lang_text('Tài khoản vận hành nội bộ không cần gán mốc mở rộng riêng.', 'Internal operation accounts do not need a separate extended-access marker.')) ?></span>
                    <?php endif; ?>
                  </div>
                </td>
                <td><?= e((string) ($row['organization_name'] ?? '')) ?></td>
                <td><?= e((string) ($row['created_at'] ?? '')) ?></td>
                <td>
                  <div class="table-cell-stack">
                    <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/dashboard/admin/users/' . (int) ($row['id'] ?? 0) . '/status')) ?>" class="inline-form">
                      <?= csrf_field() ?>
                      <input type="hidden" name="_redirect" value="<?= e($currentRedirect) ?>">
                      <select name="status">
                        <option value="active" <?= $statusValue === 'active' ? 'selected' : '' ?>><?= e($statusLabel('active')) ?></option>
                        <option value="pending" <?= $statusValue === 'pending' ? 'selected' : '' ?>><?= e($statusLabel('pending')) ?></option>
                        <option value="suspended" <?= $statusValue === 'suspended' ? 'selected' : '' ?>><?= e($statusLabel('suspended')) ?></option>
                      </select>
                      <button type="submit" class="btn"><?= e(lang_text('Lưu trạng thái', 'Save status')) ?></button>
                    </form>

                    <?php if ($hasVipColumn): ?>
                      <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/dashboard/admin/users/' . (int) ($row['id'] ?? 0) . '/vip')) ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_redirect" value="<?= e($currentRedirect) ?>">
                        <input type="date" name="vip_until" value="<?= e($vipDateInputValue($row)) ?>">
                        <button type="submit" class="btn secondary"><?= e(lang_text('Lưu mốc mở rộng', 'Save extended access')) ?></button>
                      </form>

                      <?php if ($vipDateInputValue($row) !== ''): ?>
                        <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/dashboard/admin/users/' . (int) ($row['id'] ?? 0) . '/vip')) ?>" class="inline-form">
                          <?= csrf_field() ?>
                          <input type="hidden" name="_redirect" value="<?= e($currentRedirect) ?>">
                          <input type="hidden" name="vip_until" value="">
                          <button type="submit" class="btn secondary"><?= e(lang_text('Gỡ mốc mở rộng', 'Remove extended access')) ?></button>
                        </form>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <div class="inline-actions">
      <?php if ($currentPage > 1): ?>
        <?php $prevQuery = $currentFilters; $prevQuery['page'] = $currentPage - 1; ?>
        <a class="btn secondary" href="<?= e($buildUsersUrl($prevQuery)) ?>"><?= e(lang_text('Trang trước', 'Previous page')) ?></a>
      <?php else: ?>
        <span class="btn secondary is-disabled"><?= e(lang_text('Trang trước', 'Previous page')) ?></span>
      <?php endif; ?>

      <?php if ($currentPage < $totalPages): ?>
        <?php $nextQuery = $currentFilters; $nextQuery['page'] = $currentPage + 1; ?>
        <a class="btn secondary" href="<?= e($buildUsersUrl($nextQuery)) ?>"><?= e(lang_text('Trang sau', 'Next page')) ?></a>
      <?php else: ?>
        <span class="btn secondary is-disabled"><?= e(lang_text('Trang sau', 'Next page')) ?></span>
      <?php endif; ?>
    </div>
  </article>
</section>
<?php require BASE_PATH . '/resources/views/layouts/app_footer.php'; ?>
