<?php
$pageTitle = lang_text('Quản lý blog (cho website công khai)', 'Blog management (for the public website)');
require BASE_PATH . '/resources/views/layouts/app_header.php';

$posts = is_array($posts ?? null) ? $posts : [];
$filters = is_array($filters ?? null) ? $filters : ['q' => '', 'status' => 'all', 'per_page' => 20];
$pagination = is_array($pagination ?? null) ? $pagination : ['page' => 1, 'per_page' => 20, 'total' => 0, 'total_pages' => 1];
$returnTo = (string) ($returnTo ?? '/dashboard/admin/blog');

$blogStatusLabel = static function (string $status): string {
    return match ($status) {
        'draft' => lang_text('Bản nháp', 'Draft'),
        'published' => lang_text('Đã xuất bản', 'Published'),
        default => $status,
    };
};

$seed = [];
foreach ($posts as $post) {
    $seed[] = [
        'id' => (int) ($post['id'] ?? 0),
        'slug' => (string) ($post['slug'] ?? ''),
        'title_vi' => (string) ($post['title_vi'] ?? ''),
        'content_vi' => (string) ($post['content_vi'] ?? ''),
        'status' => (string) ($post['status'] ?? 'draft'),
    ];
}
$seedJson = json_encode(
    $seed,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
);
if ($seedJson === false) {
    $seedJson = '[]';
}

$blogQuery = static function (array $overrides = []) use ($filters, $pagination): string {
    $params = [
        'q' => (string) ($filters['q'] ?? ''),
        'status' => (string) ($filters['status'] ?? 'all'),
        'per_page' => (int) ($filters['per_page'] ?? 20),
        'page' => (int) ($pagination['page'] ?? 1),
    ];

    foreach ($overrides as $key => $value) {
        $params[$key] = $value;
    }

    return http_build_query($params);
};
?>
<section class="container page-section admin-blog-page" data-admin-blog-manager>
  <div class="section-head section-head-inline">
    <p class="section-kicker"><?= e(lang_text('Nội dung', 'Content')) ?></p>
    <h1><?= e($pageTitle) ?></h1>
  </div>

  <article class="card admin-single-panel">
    <div class="admin-blog-toolbar">
      <div>
        <p class="muted"><?= e(lang_text('Dữ liệu lớn thì chỉ tải đúng trang đang xem. Có tìm kiếm, lọc trạng thái và số dòng mỗi trang để không phải lướt mãi.', 'Large datasets load only the current page. Use search, status filters, and per-page controls instead of endless scrolling.')) ?></p>
        <p class="muted admin-table-meta">
          <?= e(lang_text('Tổng cộng', 'Total')) ?>: <?= e((string) ($pagination['total'] ?? 0)) ?>
          | <?= e(lang_text('Trang', 'Page')) ?> <?= e((string) ($pagination['page'] ?? 1)) ?>/<?= e((string) ($pagination['total_pages'] ?? 1)) ?>
        </p>
      </div>
      <button type="button" class="btn primary" data-admin-blog-add><?= e(lang_text('Thêm bài đăng', 'Add post')) ?></button>
    </div>

    <form method="get" accept-charset="UTF-8" action="<?= e(app_url('/dashboard/admin/blog')) ?>" class="admin-table-filter-bar">
      <label>
        <?= e(lang_text('Từ khóa', 'Keyword')) ?>
        <input type="text" name="q" value="<?= e((string) ($filters['q'] ?? '')) ?>" placeholder="<?= e(lang_text('slug, tiêu đề tiếng Việt', 'slug, Vietnamese title')) ?>">
      </label>
      <label>
        <?= e(lang_text('Trạng thái', 'Status')) ?>
        <select name="status">
          <option value="all" <?= (($filters['status'] ?? 'all') === 'all') ? 'selected' : '' ?>><?= e(lang_text('Tất cả', 'All')) ?></option>
          <option value="draft" <?= (($filters['status'] ?? 'all') === 'draft') ? 'selected' : '' ?>><?= e(lang_text('Bản nháp', 'Draft')) ?></option>
          <option value="published" <?= (($filters['status'] ?? 'all') === 'published') ? 'selected' : '' ?>><?= e(lang_text('Đã xuất bản', 'Published')) ?></option>
        </select>
      </label>
      <label>
        <?= e(lang_text('Số dòng mỗi trang', 'Rows per page')) ?>
        <select name="per_page">
          <?php foreach ([20, 50, 100] as $perPageOption): ?>
            <option value="<?= e((string) $perPageOption) ?>" <?= ((int) ($filters['per_page'] ?? 20) === $perPageOption) ? 'selected' : '' ?>><?= e((string) $perPageOption) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="admin-table-filter-actions">
        <button type="submit" class="btn primary"><?= e(lang_text('Áp dụng bộ lọc', 'Apply filters')) ?></button>
        <a href="<?= e(app_url('/dashboard/admin/blog')) ?>" class="btn secondary"><?= e(lang_text('Đặt lại', 'Reset')) ?></a>
      </div>
    </form>

    <?php if ($posts === []): ?>
      <p class="muted"><?= e(lang_text('Không có bài viết nào khớp bộ lọc hiện tại.', 'No posts match the current filters.')) ?></p>
    <?php else: ?>
      <div class="table-shell admin-blog-table-wrap">
        <table class="table admin-blog-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Slug</th>
              <th><?= e(lang_text('Tiêu đề', 'Title')) ?></th>
              <th><?= e(lang_text('Trạng thái', 'Status')) ?></th>
              <th><?= e(lang_text('Xuất bản lúc', 'Published at')) ?></th>
              <th><?= e(lang_text('Hành động', 'Actions')) ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($posts as $post): ?>
              <?php
              $postId = (int) ($post['id'] ?? 0);
              $postSlug = (string) ($post['slug'] ?? '');
              $postStatus = (string) ($post['status'] ?? 'draft');
              $publicUrl = app_url('/blog/' . $postSlug);
              ?>
              <tr>
                <td><?= e((string) $postId) ?></td>
                <td><span class="admin-blog-code"><?= e($postSlug) ?></span></td>
                <td><?= e((string) ($post['title_vi'] ?? '')) ?></td>
                <td>
                  <span class="admin-blog-status-badge <?= $postStatus === 'published' ? 'is-published' : 'is-draft' ?>">
                    <?= e($blogStatusLabel($postStatus)) ?>
                  </span>
                </td>
                <td><?= e((string) (($post['published_at'] ?? '') !== '' ? $post['published_at'] : '-')) ?></td>
                <td>
                  <div class="admin-blog-actions">
                    <button type="button" class="btn" data-admin-blog-edit="<?= e((string) $postId) ?>"><?= e(lang_text('Biên tập', 'Edit')) ?></button>
                    <?php if ($postStatus === 'published' && $postSlug !== ''): ?>
                      <a href="<?= e($publicUrl) ?>" class="btn secondary" target="_blank" rel="noopener"><?= e(lang_text('Xem công khai', 'View public')) ?></a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <?php if ((int) ($pagination['total_pages'] ?? 1) > 1): ?>
      <div class="admin-table-pagination">
        <?php $prevPage = max(1, (int) ($pagination['page'] ?? 1) - 1); ?>
        <?php $nextPage = min((int) ($pagination['total_pages'] ?? 1), (int) ($pagination['page'] ?? 1) + 1); ?>
        <a
          href="<?= e(app_url('/dashboard/admin/blog?' . $blogQuery(['page' => $prevPage]))) ?>"
          class="btn secondary<?= ((int) ($pagination['page'] ?? 1) <= 1) ? ' is-disabled' : '' ?>"
          <?= ((int) ($pagination['page'] ?? 1) <= 1) ? 'aria-disabled="true"' : '' ?>
        ><?= e(lang_text('Trang trước', 'Previous page')) ?></a>
        <span class="admin-table-page-indicator">
          <?= e(lang_text('Trang', 'Page')) ?> <?= e((string) ($pagination['page'] ?? 1)) ?>/<?= e((string) ($pagination['total_pages'] ?? 1)) ?>
        </span>
        <a
          href="<?= e(app_url('/dashboard/admin/blog?' . $blogQuery(['page' => $nextPage]))) ?>"
          class="btn secondary<?= ((int) ($pagination['page'] ?? 1) >= (int) ($pagination['total_pages'] ?? 1)) ? ' is-disabled' : '' ?>"
          <?= ((int) ($pagination['page'] ?? 1) >= (int) ($pagination['total_pages'] ?? 1)) ? 'aria-disabled="true"' : '' ?>
        ><?= e(lang_text('Trang sau', 'Next page')) ?></a>
      </div>
    <?php endif; ?>
  </article>

  <div class="admin-blog-modal" data-admin-blog-modal hidden>
    <div class="admin-blog-modal-backdrop" data-admin-blog-close></div>
    <div class="admin-blog-modal-panel" role="dialog" aria-modal="true" aria-labelledby="admin-blog-modal-title">
      <div class="admin-blog-modal-head">
        <h2 id="admin-blog-modal-title" data-admin-blog-title><?= e(lang_text('Thêm bài đăng', 'Add post')) ?></h2>
        <button type="button" class="admin-blog-modal-close" data-admin-blog-close-btn aria-label="<?= e(lang_text('Đóng', 'Close')) ?>">x</button>
      </div>

      <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/dashboard/admin/blog')) ?>" class="form-card admin-blog-form" data-admin-blog-form>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="" data-admin-blog-id>
        <input type="hidden" name="_redirect" value="<?= e($returnTo) ?>">

        <label>
          Slug
          <input type="text" name="slug" data-admin-blog-slug required>
        </label>

        <label>
          <?= e(lang_text('Tiêu đề VI', 'Title VI')) ?>
          <input type="text" name="title_vi" data-admin-blog-title-vi required>
        </label>

        <label>
          <?= e(lang_text('Nội dung VI', 'Content VI')) ?>
          <textarea name="content_vi" rows="7" data-admin-blog-content-vi required></textarea>
        </label>

        <p class="muted"><?= e(lang_text('Chỉ cần nhập nội dung tiếng Việt cho bài viết.', 'Only the Vietnamese content is required for this post.')) ?></p>

        <label>
          <?= e(lang_text('Trạng thái', 'Status')) ?>
          <select name="status" data-admin-blog-status>
            <option value="draft"><?= e(lang_text('Bản nháp', 'Draft')) ?></option>
            <option value="published"><?= e(lang_text('Đã xuất bản', 'Published')) ?></option>
          </select>
        </label>

        <div class="admin-blog-modal-actions">
          <button type="button" class="btn secondary" data-admin-blog-cancel><?= e(lang_text('Hủy bỏ', 'Cancel')) ?></button>
          <button type="submit" class="btn primary"><?= e(lang_text('Lưu bài đăng', 'Save post')) ?></button>
        </div>
      </form>
    </div>
  </div>

  <script type="application/json" id="admin-blog-seed"><?= $seedJson ?></script>
</section>
<?php require BASE_PATH . '/resources/views/layouts/app_footer.php'; ?>
