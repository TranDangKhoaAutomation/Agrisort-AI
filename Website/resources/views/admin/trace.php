<?php
$pageTitle = lang_text('Điều phối truy xuất nguồn gốc (cho vận chuyển, kho, người bán)', 'Trace coordination (for transport, warehouse, sellers)');
require BASE_PATH . '/resources/views/layouts/app_header.php';

$pendingActors = is_array($pendingActors ?? null) ? $pendingActors : [];
$actors = is_array($actors ?? null) ? $actors : [];
$assignments = is_array($assignments ?? null) ? $assignments : [];
$events = is_array($events ?? null) ? $events : [];
$lots = is_array($lots ?? null) ? $lots : [];
$packages = is_array($packages ?? null) ? $packages : [];
$traceInfo = trim((string) ($traceInfo ?? ''));

$pendingFilters = array_merge([
    'q' => '',
    'role' => 'all',
    'per_page' => 10,
], is_array($pendingFilters ?? null) ? $pendingFilters : []);
$pendingPagination = array_merge([
    'page' => 1,
    'per_page' => 10,
    'total' => 0,
    'total_pages' => 1,
], is_array($pendingPagination ?? null) ? $pendingPagination : []);

$assignmentFilters = array_merge([
    'q' => '',
    'entity_type' => 'all',
    'actor_role' => 'all',
    'per_page' => 10,
], is_array($assignmentFilters ?? null) ? $assignmentFilters : []);
$assignmentPagination = array_merge([
    'page' => 1,
    'per_page' => 10,
    'total' => 0,
    'total_pages' => 1,
], is_array($assignmentPagination ?? null) ? $assignmentPagination : []);

$eventFilters = array_merge([
    'q' => '',
    'entity_type' => 'all',
    'actor_role' => 'all',
    'per_page' => 10,
], is_array($eventFilters ?? null) ? $eventFilters : []);
$eventPagination = array_merge([
    'page' => 1,
    'per_page' => 10,
    'total' => 0,
    'total_pages' => 1,
], is_array($eventPagination ?? null) ? $eventPagination : []);

$entityLabel = static function (string $entityType): string {
    return $entityType === 'package'
        ? lang_text('Gói hàng', 'Package')
        : lang_text('Lô hàng', 'Lot');
};

$roleOptions = ['partner', 'farmer', 'transporter', 'warehouse', 'seller'];
$eventRoleOptions = ['admin', 'partner', 'farmer', 'transporter', 'warehouse', 'seller'];
$currentQuery = [];
foreach ($_GET as $key => $value) {
    if (is_array($value)) {
        continue;
    }
    $currentQuery[(string) $key] = (string) $value;
}

$buildTraceUrl = static function (array $updates = [], array $remove = []) use ($currentQuery): string {
    $query = $currentQuery;
    foreach ($remove as $key) {
        unset($query[$key]);
    }

    foreach ($updates as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
            continue;
        }
        $query[$key] = (string) $value;
    }

    $queryString = http_build_query($query);
    return app_url('/dashboard/admin/trace' . ($queryString !== '' ? '?' . $queryString : ''));
};

$renderPreserveFields = static function (array $excludeKeys) use ($currentQuery): string {
    $html = '';
    foreach ($currentQuery as $key => $value) {
        if (in_array($key, $excludeKeys, true)) {
            continue;
        }
        $html .= '<input type="hidden" name="' . e($key) . '" value="' . e($value) . '">' . PHP_EOL;
    }

    return $html;
};

$renderPager = static function (array $pagination, array $updates, string $pageKey) use ($buildTraceUrl): string {
    $page = max(1, (int) ($pagination['page'] ?? 1));
    $totalPages = max(1, (int) ($pagination['total_pages'] ?? 1));

    ob_start();
    ?>
    <div class="inline-actions trace-table-pager">
      <?php if ($page > 1): ?>
        <a class="btn secondary" href="<?= e($buildTraceUrl($updates + [$pageKey => $page - 1])) ?>"><?= e(lang_text('Trang trước', 'Previous page')) ?></a>
      <?php else: ?>
        <span class="btn secondary is-disabled"><?= e(lang_text('Trang trước', 'Previous page')) ?></span>
      <?php endif; ?>

      <span class="status-pill trace-table-status">
        <?= e(lang_text('Trang', 'Page')) ?> <?= e((string) $page) ?>/<?= e((string) $totalPages) ?>
      </span>

      <?php if ($page < $totalPages): ?>
        <a class="btn secondary" href="<?= e($buildTraceUrl($updates + [$pageKey => $page + 1])) ?>"><?= e(lang_text('Trang sau', 'Next page')) ?></a>
      <?php else: ?>
        <span class="btn secondary is-disabled"><?= e(lang_text('Trang sau', 'Next page')) ?></span>
      <?php endif; ?>
    </div>
    <?php
    return (string) ob_get_clean();
};
?>
<section class="container page-section trace-admin-page">
  <div class="section-head section-head-inline">
    <p class="section-kicker"><?= e(lang_text('Trung tâm điều phối truy xuất', 'Trace coordination center')) ?></p>
    <h1><?= e($pageTitle) ?></h1>
  </div>
  <div class="inline-actions trace-admin-quick-actions">
    <a href="<?= e(app_url('/dashboard/admin/users')) ?>" class="btn secondary"><?= e(lang_text('Quản lý người dùng', 'User management')) ?></a>
    <a href="<?= e(app_url('/dashboard/admin')) ?>" class="btn secondary"><?= e(lang_text('Quay lại trang tổng quan', 'Back to dashboard')) ?></a>
  </div>
  <?php if ($traceInfo !== ''): ?>
    <p class="status-pill"><strong><?= e($traceInfo) ?></strong></p>
  <?php endif; ?>

  <article class="card">
    <div class="section-head section-head-inline">
      <h2><?= e(lang_text('Tài khoản chuỗi cung ứng chờ duyệt', 'Pending supply-chain accounts')) ?></h2>
      <p class="muted">
        <?= e(lang_text('Tổng cộng', 'Total')) ?>: <strong><?= e((string) $pendingPagination['total']) ?></strong>
        | <?= e(lang_text('Trang', 'Page')) ?> <?= e((string) $pendingPagination['page']) ?>/<?= e((string) $pendingPagination['total_pages']) ?>
      </p>
    </div>

    <form method="get" accept-charset="UTF-8" action="<?= e(app_url('/dashboard/admin/trace')) ?>" class="form-card compact trace-table-filters">
      <?= $renderPreserveFields(['pending_q', 'pending_role', 'pending_per_page', 'pending_page']) ?>
      <input type="hidden" name="pending_page" value="1">
      <div class="grid-2">
        <label>
          <?= e(lang_text('Từ khóa', 'Keyword')) ?>
          <input type="text" name="pending_q" value="<?= e((string) $pendingFilters['q']) ?>" maxlength="120" placeholder="<?= e(lang_text('Tên, email, tổ chức', 'Name, email, organization')) ?>">
        </label>
        <label>
          <?= e(lang_text('Vai trò', 'Role')) ?>
          <select name="pending_role">
            <option value="all" <?= (string) $pendingFilters['role'] === 'all' ? 'selected' : '' ?>><?= e(lang_text('Tất cả vai trò', 'All roles')) ?></option>
            <?php foreach ($roleOptions as $role): ?>
              <option value="<?= e($role) ?>" <?= (string) $pendingFilters['role'] === $role ? 'selected' : '' ?>><?= e(\App\Models\User::roleLabel($role, lang())) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <div class="grid-2">
        <label>
          <?= e(lang_text('Số dòng mỗi trang', 'Rows per page')) ?>
          <select name="pending_per_page">
            <?php foreach ([10, 25, 50, 100] as $size): ?>
              <option value="<?= e((string) $size) ?>" <?= (int) $pendingFilters['per_page'] === $size ? 'selected' : '' ?>><?= e((string) $size) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <div class="inline-actions">
        <button type="submit" class="btn primary"><?= e(lang_text('Áp dụng bộ lọc', 'Apply filters')) ?></button>
        <a href="<?= e($buildTraceUrl([], ['pending_q', 'pending_role', 'pending_per_page', 'pending_page'])) ?>" class="btn secondary"><?= e(lang_text('Đặt lại', 'Reset')) ?></a>
      </div>
    </form>

    <?php if ($pendingActors === []): ?>
      <p class="muted"><?= e(lang_text('Hiện không có tài khoản nào khớp bộ lọc hiện tại.', 'No pending accounts match the current filters.')) ?></p>
    <?php else: ?>
      <div class="table-shell trace-table-shell">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th><?= e(lang_text('Tên', 'Name')) ?></th>
              <th>Email</th>
              <th><?= e(lang_text('Vai trò', 'Role')) ?></th>
              <th><?= e(lang_text('Trạng thái', 'Status')) ?></th>
              <th><?= e(lang_text('Thao tác', 'Action')) ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pendingActors as $actor): ?>
              <tr>
                <td><?= e((string) $actor['id']) ?></td>
                <td><?= e((string) $actor['full_name']) ?></td>
                <td><?= e((string) $actor['email']) ?></td>
                <td><?= e(\App\Models\User::roleLabel((string) ($actor['role'] ?? ''), lang())) ?></td>
                <td><?= e((string) $actor['status']) ?></td>
                <td>
                  <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/dashboard/admin/users/' . $actor['id'] . '/status')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_redirect" value="/dashboard/admin/trace">
                    <select name="status">
                      <option value="active"><?= e(lang_text('Kích hoạt', 'Active')) ?></option>
                      <option value="pending" <?= (string) $actor['status'] === 'pending' ? 'selected' : '' ?>><?= e(lang_text('Chờ duyệt', 'Pending')) ?></option>
                      <option value="suspended"><?= e(lang_text('Tạm khóa', 'Suspended')) ?></option>
                    </select>
                    <button type="submit" class="btn"><?= e(lang_text('Lưu trạng thái', 'Save status')) ?></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <?= $renderPager($pendingPagination, [], 'pending_page') ?>
  </article>

  <div class="trace-admin-stack">
    <article class="card">
      <h2><?= e(lang_text('Phân công giai đoạn truy xuất', 'Trace stage assignments')) ?></h2>
      <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/dashboard/admin/trace/assignments')) ?>" class="form-card">
        <?= csrf_field() ?>
        <label>
          <?= e(lang_text('Loại đối tượng', 'Entity type')) ?>
          <select name="entity_type" required>
            <option value="lot"><?= e(lang_text('Lô hàng', 'Lot')) ?></option>
            <option value="package"><?= e(lang_text('Gói hàng', 'Package')) ?></option>
          </select>
        </label>
        <label>
          <?= e(lang_text('ID đối tượng', 'Entity ID')) ?>
          <input type="number" name="entity_id" min="1" required>
        </label>
        <label>
          <?= e(lang_text('Mã giai đoạn', 'Stage code')) ?>
          <input type="text" name="stage_code" placeholder="warehouse-received" required>
        </label>
        <label>
          <?= e(lang_text('Tác nhân phụ trách', 'Assigned actor')) ?>
          <select name="actor_user_id" required>
            <?php foreach ($actors as $actor): ?>
              <option value="<?= e((string) $actor['id']) ?>">
                #<?= e((string) $actor['id']) ?> - <?= e((string) $actor['full_name']) ?> (<?= e(\App\Models\User::roleLabel((string) ($actor['role'] ?? ''), lang())) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <button type="submit" class="btn primary"><?= e(lang_text('Lưu phân công', 'Save assignment')) ?></button>
      </form>

      <div class="trace-id-list">
        <p class="trace-id-label"><?= e(lang_text('ID lô hàng sẵn có', 'Available lot IDs')) ?></p>
        <div class="trace-id-row">
          <?php if ($lots === []): ?>
            <span class="trace-id-empty"><?= e(lang_text('Chưa có lô hàng nào khả dụng.', 'No lots available.')) ?></span>
          <?php else: ?>
            <?php foreach ($lots as $lot): ?>
              <span class="trace-id-chip">#<?= e((string) $lot['id']) ?> (<?= e((string) $lot['lot_code']) ?>)</span>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="trace-id-list">
        <p class="trace-id-label"><?= e(lang_text('ID gói hàng sẵn có', 'Available package IDs')) ?></p>
        <div class="trace-id-row">
          <?php if ($packages === []): ?>
            <span class="trace-id-empty"><?= e(lang_text('Chưa có gói hàng nào khả dụng.', 'No packages available.')) ?></span>
          <?php else: ?>
            <?php foreach ($packages as $package): ?>
              <span class="trace-id-chip">#<?= e((string) $package['id']) ?> (<?= e((string) $package['package_code']) ?>)</span>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </article>

    <article class="card">
      <div class="section-head section-head-inline">
        <h2><?= e(lang_text('Danh sách phân công', 'Assignment list')) ?></h2>
        <p class="muted">
          <?= e(lang_text('Tổng cộng', 'Total')) ?>: <strong><?= e((string) $assignmentPagination['total']) ?></strong>
          | <?= e(lang_text('Trang', 'Page')) ?> <?= e((string) $assignmentPagination['page']) ?>/<?= e((string) $assignmentPagination['total_pages']) ?>
        </p>
      </div>

      <form method="get" accept-charset="UTF-8" action="<?= e(app_url('/dashboard/admin/trace')) ?>" class="form-card compact trace-table-filters">
        <?= $renderPreserveFields(['assignment_q', 'assignment_entity_type', 'assignment_actor_role', 'assignment_per_page', 'assignment_page']) ?>
        <input type="hidden" name="assignment_page" value="1">
        <div class="grid-2">
          <label>
            <?= e(lang_text('Từ khóa', 'Keyword')) ?>
            <input type="text" name="assignment_q" value="<?= e((string) $assignmentFilters['q']) ?>" maxlength="120" placeholder="<?= e(lang_text('Mã chặng, actor, mã lô/gói', 'Stage, actor, lot/package code')) ?>">
          </label>
          <label>
            <?= e(lang_text('Loại đối tượng', 'Entity type')) ?>
            <select name="assignment_entity_type">
              <option value="all" <?= (string) $assignmentFilters['entity_type'] === 'all' ? 'selected' : '' ?>><?= e(lang_text('Tất cả', 'All')) ?></option>
              <option value="lot" <?= (string) $assignmentFilters['entity_type'] === 'lot' ? 'selected' : '' ?>><?= e(lang_text('Lô hàng', 'Lot')) ?></option>
              <option value="package" <?= (string) $assignmentFilters['entity_type'] === 'package' ? 'selected' : '' ?>><?= e(lang_text('Gói hàng', 'Package')) ?></option>
            </select>
          </label>
        </div>
        <div class="grid-2">
          <label>
            <?= e(lang_text('Vai trò tác nhân', 'Actor role')) ?>
            <select name="assignment_actor_role">
              <option value="all" <?= (string) $assignmentFilters['actor_role'] === 'all' ? 'selected' : '' ?>><?= e(lang_text('Tất cả vai trò', 'All roles')) ?></option>
              <?php foreach ($roleOptions as $role): ?>
                <option value="<?= e($role) ?>" <?= (string) $assignmentFilters['actor_role'] === $role ? 'selected' : '' ?>><?= e(\App\Models\User::roleLabel($role, lang())) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>
            <?= e(lang_text('Số dòng mỗi trang', 'Rows per page')) ?>
            <select name="assignment_per_page">
              <?php foreach ([10, 25, 50, 100] as $size): ?>
                <option value="<?= e((string) $size) ?>" <?= (int) $assignmentFilters['per_page'] === $size ? 'selected' : '' ?>><?= e((string) $size) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>
        <div class="inline-actions">
          <button type="submit" class="btn primary"><?= e(lang_text('Áp dụng bộ lọc', 'Apply filters')) ?></button>
          <a href="<?= e($buildTraceUrl([], ['assignment_q', 'assignment_entity_type', 'assignment_actor_role', 'assignment_per_page', 'assignment_page'])) ?>" class="btn secondary"><?= e(lang_text('Đặt lại', 'Reset')) ?></a>
        </div>
      </form>

      <?php if ($assignments === []): ?>
        <p class="muted"><?= e(lang_text('Chưa có phân công nào khớp bộ lọc hiện tại.', 'No assignments match the current filters.')) ?></p>
      <?php else: ?>
        <div class="table-shell trace-table-shell trace-assignment-table-shell">
          <table class="table trace-assignment-table">
            <thead>
              <tr>
                <th>ID</th>
                <th><?= e(lang_text('Giai đoạn', 'Stage')) ?></th>
                <th><?= e(lang_text('Đối tượng', 'Entity')) ?></th>
                <th><?= e(lang_text('Tác nhân', 'Actor')) ?></th>
                <th><?= e(lang_text('Cập nhật lúc', 'Updated at')) ?></th>
                <th><?= e(lang_text('Thao tác', 'Action')) ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($assignments as $assignment): ?>
                <?php $entityCode = (string) ($assignment['entity_type'] === 'lot' ? ($assignment['lot_code'] ?? '') : ($assignment['package_code'] ?? '')); ?>
                <tr>
                  <td>#<?= e((string) $assignment['id']) ?></td>
                  <td>
                    <strong><?= e($entityLabel((string) $assignment['entity_type'])) ?></strong><br>
                    <span class="muted"><?= e((string) $assignment['stage_code']) ?></span>
                  </td>
                  <td>
                    #<?= e((string) $assignment['entity_id']) ?>
                    <?php if ($entityCode !== ''): ?>
                      <br><span class="muted"><?= e($entityCode) ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?= e((string) ($assignment['actor_name'] ?? '-')) ?><br>
                    <span class="muted"><?= e(\App\Models\User::roleLabel((string) ($assignment['actor_role'] ?? ''), lang())) ?></span>
                  </td>
                  <td><?= e((string) ($assignment['updated_at'] ?? '')) ?></td>
                  <td>
                    <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/dashboard/admin/trace/assignments/' . $assignment['id'])) ?>">
                      <?= csrf_field() ?>
                      <?= method_field('DELETE') ?>
                      <button type="submit" class="btn danger"><?= e(lang_text('Xóa', 'Delete')) ?></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <?= $renderPager($assignmentPagination, [], 'assignment_page') ?>
    </article>
  </div>

  <article class="card">
    <div class="section-head section-head-inline">
      <h2><?= e(lang_text('Dòng thời gian hệ thống', 'System timeline')) ?></h2>
      <p class="muted">
        <?= e(lang_text('Tổng cộng', 'Total')) ?>: <strong><?= e((string) $eventPagination['total']) ?></strong>
        | <?= e(lang_text('Trang', 'Page')) ?> <?= e((string) $eventPagination['page']) ?>/<?= e((string) $eventPagination['total_pages']) ?>
      </p>
    </div>

    <form method="get" accept-charset="UTF-8" action="<?= e(app_url('/dashboard/admin/trace')) ?>" class="form-card compact trace-table-filters">
      <?= $renderPreserveFields(['event_q', 'event_entity_type', 'event_actor_role', 'event_per_page', 'event_page']) ?>
      <input type="hidden" name="event_page" value="1">
      <div class="grid-2">
        <label>
          <?= e(lang_text('Từ khóa', 'Keyword')) ?>
          <input type="text" name="event_q" value="<?= e((string) $eventFilters['q']) ?>" maxlength="120" placeholder="<?= e(lang_text('Mã chặng, vị trí, actor, mã lô/gói', 'Stage, location, actor, lot/package code')) ?>">
        </label>
        <label>
          <?= e(lang_text('Loại đối tượng', 'Entity type')) ?>
          <select name="event_entity_type">
            <option value="all" <?= (string) $eventFilters['entity_type'] === 'all' ? 'selected' : '' ?>><?= e(lang_text('Tất cả', 'All')) ?></option>
            <option value="lot" <?= (string) $eventFilters['entity_type'] === 'lot' ? 'selected' : '' ?>><?= e(lang_text('Lô hàng', 'Lot')) ?></option>
            <option value="package" <?= (string) $eventFilters['entity_type'] === 'package' ? 'selected' : '' ?>><?= e(lang_text('Gói hàng', 'Package')) ?></option>
          </select>
        </label>
      </div>
      <div class="grid-2">
        <label>
          <?= e(lang_text('Vai trò cập nhật', 'Actor role')) ?>
          <select name="event_actor_role">
            <option value="all" <?= (string) $eventFilters['actor_role'] === 'all' ? 'selected' : '' ?>><?= e(lang_text('Tất cả vai trò', 'All roles')) ?></option>
            <?php foreach ($eventRoleOptions as $role): ?>
              <option value="<?= e($role) ?>" <?= (string) $eventFilters['actor_role'] === $role ? 'selected' : '' ?>><?= e(\App\Models\User::roleLabel($role, lang())) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          <?= e(lang_text('Số dòng mỗi trang', 'Rows per page')) ?>
          <select name="event_per_page">
            <?php foreach ([10, 25, 50, 100] as $size): ?>
              <option value="<?= e((string) $size) ?>" <?= (int) $eventFilters['per_page'] === $size ? 'selected' : '' ?>><?= e((string) $size) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <div class="inline-actions">
        <button type="submit" class="btn primary"><?= e(lang_text('Áp dụng bộ lọc', 'Apply filters')) ?></button>
        <a href="<?= e($buildTraceUrl([], ['event_q', 'event_entity_type', 'event_actor_role', 'event_per_page', 'event_page'])) ?>" class="btn secondary"><?= e(lang_text('Đặt lại', 'Reset')) ?></a>
      </div>
    </form>

    <?php if ($events === []): ?>
      <p class="muted"><?= e(lang_text('Chưa có sự kiện nào khớp bộ lọc hiện tại.', 'No events match the current filters.')) ?></p>
    <?php else: ?>
      <div class="table-shell trace-table-shell">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th><?= e(lang_text('Đối tượng', 'Entity')) ?></th>
              <th><?= e(lang_text('Mã giai đoạn', 'Stage code')) ?></th>
              <th><?= e(lang_text('Thời gian sự kiện', 'Event time')) ?></th>
              <th><?= e(lang_text('Vị trí', 'Location')) ?></th>
              <th><?= e(lang_text('Người cập nhật', 'Updated by')) ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($events as $event): ?>
              <tr>
                <td><?= e((string) $event['id']) ?></td>
                <td><?= e($entityLabel((string) $event['entity_type'])) ?> #<?= e((string) $event['entity_id']) ?></td>
                <td><?= e((string) $event['stage_code']) ?></td>
                <td><?= e((string) $event['event_time']) ?></td>
                <td><?= e((string) $event['location_name']) ?></td>
                <td><?= e((string) ($event['actor_name'] ?? '-')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <?= $renderPager($eventPagination, [], 'event_page') ?>
  </article>
</section>
<?php require BASE_PATH . '/resources/views/layouts/app_footer.php'; ?>
