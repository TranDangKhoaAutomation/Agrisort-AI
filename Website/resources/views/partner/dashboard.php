<?php
$pageTitle = lang_text('Bảng điều khiển đối tác', 'Partner dashboard');
require BASE_PATH . '/resources/views/layouts/app_header.php';

$lots = is_array($lots ?? null) ? $lots : [];
$profile = is_array($profile ?? null) ? $profile : [];
$dashboardSection = array_merge([
    'title' => lang_text('Tạo lô, quản lý ảnh sản phẩm và xuất QR từ một nơi', 'Create lots, manage product photos, and publish QR from one place'),
    'body' => lang_text(
        'Mỗi lô hàng phải có ảnh sản phẩm thật trước khi lưu. Từ đây bạn có thể tạo lô, nhập CSV, cập nhật QR và kiểm soát thông tin hiển thị.',
        'Every lot must have a real product image before saving. From here you can create lots, import CSV data, update QR codes, and control visible information.'
    ),
], is_array($dashboardSection ?? null) ? $dashboardSection : []);

$statusLabel = static function (string $status): string {
    return match ($status) {
        'draft' => lang_text('Bản nháp', 'Draft'),
        'published' => lang_text('Đã công khai', 'Published'),
        default => $status,
    };
};

$splitLines = static function (?string $text): array {
    $rows = preg_split('/\R+/u', trim((string) $text)) ?: [];

    return array_values(array_filter(array_map('trim', $rows), static fn (string $row): bool => $row !== ''));
};

$totalLots = count($lots);
$publishedLots = 0;
$draftLots = 0;
$totalUnits = 0;

foreach ($lots as $lot) {
    if ((string) ($lot['publish_status'] ?? 'draft') === 'published') {
        $publishedLots++;
    } else {
        $draftLots++;
    }

    $totalUnits += (int) ($lot['grade1_count'] ?? 0);
    $totalUnits += (int) ($lot['grade2_count'] ?? 0);
    $totalUnits += (int) ($lot['defect_count'] ?? 0);
}
?>
<section class="container page-section dashboard-page">
  <div class="dashboard-hero card">
    <div class="dashboard-hero-copy">
      <p class="section-kicker"><?= e(lang_text('Không gian vận hành đối tác', 'Partner workspace')) ?></p>
      <h1><?= e((string) ($dashboardSection['title'] ?? $pageTitle)) ?></h1>
      <?php foreach ($splitLines((string) ($dashboardSection['body'] ?? '')) as $line): ?>
        <p><?= e($line) ?></p>
      <?php endforeach; ?>

      <div class="dashboard-pill-list">
        <span><?= e(lang_text('Ảnh sản phẩm là bắt buộc', 'Product images are required')) ?></span>
        <span><?= e(lang_text('QR truy xuất sẵn sàng tải xuống', 'QR assets are ready to download')) ?></span>
        <span><?= e($autoPublish ? lang_text('Tự động công khai: Bật', 'Auto publish: On') : lang_text('Tự động công khai: Tắt', 'Auto publish: Off')) ?></span>
      </div>
    </div>

    <div class="dashboard-hero-side">
      <div class="dashboard-hero-panel">
        <strong><?= e((string) ($profile['organization_name'] ?? lang_text('Chưa khai báo tổ chức', 'Organization not set'))) ?></strong>
        <p><?= e((string) ($profile['representative_name'] ?? lang_text('Chưa có người đại diện', 'No representative yet'))) ?></p>
        <small><?= e((string) ($profile['phone'] ?? lang_text('Chưa có số điện thoại', 'No phone number'))) ?></small>
      </div>
      <div class="dashboard-mini-stats">
        <div>
          <strong><?= e((string) $totalLots) ?></strong>
          <span><?= e(lang_text('Lô hàng', 'Lots')) ?></span>
        </div>
        <div>
          <strong><?= e((string) $publishedLots) ?></strong>
          <span><?= e(lang_text('Lô công khai', 'Published')) ?></span>
        </div>
        <div>
          <strong><?= e((string) $totalUnits) ?></strong>
          <span><?= e(lang_text('Tổng sản lượng', 'Total units')) ?></span>
        </div>
      </div>
    </div>
  </div>

  <div class="grid-2 dashboard-form-grid">
    <article class="card studio-panel">
      <div class="section-head section-head-inline">
        <p class="section-kicker"><?= e(lang_text('Tạo mới', 'Create')) ?></p>
        <h2><?= e(lang_text('Tạo lô hàng mới', 'Create a new lot')) ?></h2>
      </div>
      <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/partner/lots')) ?>" enctype="multipart/form-data" class="form-card">
        <?= csrf_field() ?>
        <p class="muted"><?= e(lang_text(
            'Mã lô được tạo tự động. Ảnh sản phẩm là bắt buộc để lô hàng hiển thị đúng trên website và trang truy xuất.',
            'Lot codes are generated automatically. A product image is required so the lot displays correctly on the website and trace page.'
        )) ?></p>
        <label><?= e(lang_text('Tên sản phẩm', 'Produce name')) ?> <input type="text" name="produce_type" required></label>
        <label><?= e(lang_text('Vùng xuất xứ', 'Origin region')) ?> <input type="text" name="origin_region" required></label>
        <label><?= e(lang_text('Ngày thu hoạch', 'Harvest date')) ?> <input type="date" name="harvest_date" required></label>
        <label><?= e(lang_text('Số lượng loại 1', 'Grade 1 count')) ?> <input type="number" name="grade1_count" min="0" value="0" required></label>
        <label><?= e(lang_text('Số lượng loại 2', 'Grade 2 count')) ?> <input type="number" name="grade2_count" min="0" value="0" required></label>
        <label><?= e(lang_text('Số lượng lỗi', 'Defect count')) ?> <input type="number" name="defect_count" min="0" value="0" required></label>
        <label><?= e(lang_text('Ghi chú', 'Notes')) ?> <textarea name="notes" rows="3"></textarea></label>
        <label><?= e(lang_text('Ảnh sản phẩm', 'Product image')) ?> <input type="file" name="image" accept="image/*" required></label>
        <button type="submit" class="btn primary"><?= e(lang_text('Tạo lô hàng', 'Create lot')) ?></button>
      </form>
    </article>

    <article class="card studio-panel">
      <div class="section-head section-head-inline">
        <p class="section-kicker"><?= e(lang_text('Nhập dữ liệu', 'Import data')) ?></p>
        <h2><?= e(lang_text('Nhập danh sách từ CSV', 'Import from CSV')) ?></h2>
      </div>
      <p><?= e(lang_text(
          'Các cột bắt buộc: lot_code, produce_type, origin_region, harvest_date, grade1_count, grade2_count, defect_count, notes.',
          'Required columns: lot_code, produce_type, origin_region, harvest_date, grade1_count, grade2_count, defect_count, notes.'
      )) ?></p>
      <p class="muted"><?= e(lang_text(
          'Mỗi lần nhập CSV phải kèm một ảnh mặc định. Ảnh này sẽ gán cho toàn bộ lô trong đợt nhập.',
          'Each CSV import must include one default image. That image is assigned to every lot in the batch.'
      )) ?></p>
      <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/partner/lots/import-csv')) ?>" enctype="multipart/form-data" class="form-card compact">
        <?= csrf_field() ?>
        <label><?= e(lang_text('Tệp CSV', 'CSV file')) ?> <input type="file" name="csv_file" accept=".csv" required></label>
        <label><?= e(lang_text('Ảnh mặc định', 'Default image')) ?> <input type="file" name="default_image" accept="image/*" required></label>
        <button type="submit" class="btn secondary"><?= e(lang_text('Nhập dữ liệu', 'Import data')) ?></button>
      </form>
    </article>
  </div>

  <div class="section-head section-head-inline">
    <p class="section-kicker"><?= e(lang_text('Danh sách lô', 'Lot inventory')) ?></p>
    <h2><?= e(lang_text('Lô hàng đang quản lý', 'Managed lots')) ?></h2>
  </div>

  <?php if ($lots === []): ?>
    <article class="card studio-panel">
      <p><?= e(lang_text('Chưa có lô hàng nào. Hãy tạo lô đầu tiên với ảnh sản phẩm thật để website hiển thị đẹp và đúng.', 'There are no lots yet. Create your first lot with a real product photo so the website looks correct.')) ?></p>
    </article>
  <?php else: ?>
    <div class="card-list dashboard-card-list">
      <?php foreach ($lots as $lot): ?>
        <?php
        $qrToken = (string) ($lot['qr_token'] ?? '');
        $traceUrl = \App\Services\QrService::url($qrToken);
        $qrAssets = \App\Services\QrService::storedImageUrls($qrToken, 7);
        $qrPngUrl = (string) ($qrAssets['png'] ?? '');
        $qrSvgUrl = (string) ($qrAssets['svg'] ?? '');
        $qrImage = $qrPngUrl !== '' ? $qrPngUrl : \App\Services\QrService::imageDataUri($qrToken, 7);
        $lotQrName = 'lot-qr-' . preg_replace('/[^A-Za-z0-9._-]/', '-', (string) ($lot['lot_code'] ?? $qrToken));
        $productVisual = \App\Services\ProductVisualService::resolveLotImage($lot, lang());
        $needsRealImage = empty($lot['image_path']);
        ?>
        <article class="card dashboard-lot-card">
          <div class="dashboard-lot-head">
            <div>
              <h3><?= e((string) $lot['lot_code']) ?> - <?= e((string) $lot['produce_type']) ?></h3>
              <p><?= e(lang_text('Trạng thái', 'Status')) ?>: <strong><?= e($statusLabel((string) $lot['publish_status'])) ?></strong></p>
            </div>
            <a href="<?= e(app_url('/trace/' . rawurlencode((string) $lot['qr_token']))) ?>" target="_blank" class="btn secondary"><?= e(lang_text('Mở trang truy xuất', 'Open trace page')) ?></a>
          </div>

          <div class="dashboard-lot-layout">
            <div class="product-preview-card">
              <img src="<?= e((string) $productVisual['src']) ?>" alt="<?= e((string) $productVisual['alt']) ?>" class="product-preview-image">
              <div class="product-preview-copy">
                <span class="product-badge <?= !empty($productVisual['is_placeholder']) ? 'is-placeholder' : 'is-photo' ?>"><?= e((string) $productVisual['badge']) ?></span>
                <p class="product-preview-note"><?= e((string) $productVisual['note']) ?></p>
              </div>
            </div>

            <div class="qr-preview">
              <img src="<?= e($qrImage) ?>" alt="<?= e(lang_text('Mã QR', 'QR code')) ?> <?= e($qrToken) ?>" class="qr-image">
              <div class="qr-meta">
                <button type="button" class="btn copy-url-btn" data-url="<?= e($traceUrl) ?>" data-copied-label="<?= e(lang_text('Đã sao chép', 'Copied')) ?>"><?= e(lang_text('Sao chép liên kết QR', 'Copy QR link')) ?></button>
                <?php if ($qrPngUrl !== ''): ?>
                  <a class="btn" href="<?= e($qrPngUrl) ?>" download="<?= e($lotQrName . '.png') ?>"><?= e(lang_text('Tải PNG', 'Download PNG')) ?></a>
                <?php else: ?>
                  <button type="button" class="btn download-png-btn" data-img="<?= e($qrImage) ?>" data-filename="<?= e($lotQrName . '.png') ?>"><?= e(lang_text('Tải PNG', 'Download PNG')) ?></button>
                <?php endif; ?>
                <?php if ($qrSvgUrl !== ''): ?>
                  <a class="btn" href="<?= e($qrSvgUrl) ?>" download="<?= e($lotQrName . '.svg') ?>"><?= e(lang_text('Tải SVG', 'Download SVG')) ?></a>
                <?php endif; ?>
                <small><?= e(lang_text(
                    'Nếu điện thoại không mở được localhost, hãy cấu hình QR base URL ở phần admin.',
                    'If a phone cannot open localhost, configure the QR base URL in admin settings.'
                )) ?></small>
              </div>
            </div>
          </div>

          <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/partner/lots/' . $lot['id'])) ?>" enctype="multipart/form-data" class="form-card">
            <?= csrf_field() ?>
            <?= method_field('PUT') ?>
            <label><?= e(lang_text('Mã lô', 'Lot code')) ?> <input type="text" name="lot_code" value="<?= e((string) $lot['lot_code']) ?>" required></label>
            <label><?= e(lang_text('Tên sản phẩm', 'Produce name')) ?> <input type="text" name="produce_type" value="<?= e((string) $lot['produce_type']) ?>" required></label>
            <label><?= e(lang_text('Vùng xuất xứ', 'Origin region')) ?> <input type="text" name="origin_region" value="<?= e((string) $lot['origin_region']) ?>" required></label>
            <label><?= e(lang_text('Ngày thu hoạch', 'Harvest date')) ?> <input type="date" name="harvest_date" value="<?= e((string) $lot['harvest_date']) ?>" required></label>
            <label><?= e(lang_text('Số lượng loại 1', 'Grade 1 count')) ?> <input type="number" min="0" name="grade1_count" value="<?= e((string) $lot['grade1_count']) ?>" required></label>
            <label><?= e(lang_text('Số lượng loại 2', 'Grade 2 count')) ?> <input type="number" min="0" name="grade2_count" value="<?= e((string) $lot['grade2_count']) ?>" required></label>
            <label><?= e(lang_text('Số lượng lỗi', 'Defect count')) ?> <input type="number" min="0" name="defect_count" value="<?= e((string) $lot['defect_count']) ?>" required></label>
            <label><?= e(lang_text('Ghi chú', 'Notes')) ?> <textarea name="notes" rows="2"><?= e((string) ($lot['notes'] ?? '')) ?></textarea></label>
            <label><?= e(lang_text('Cập nhật ảnh sản phẩm', 'Update product image')) ?> <input type="file" name="image" accept="image/*" <?= $needsRealImage ? 'required' : '' ?>></label>
            <small class="muted"><?= e($needsRealImage
                ? lang_text('Lô này chưa có ảnh thật. Bạn phải tải ảnh sản phẩm trước khi lưu cập nhật.', 'This lot has no real image yet. You must upload a real product image before saving.')
                : lang_text('Chỉ tải ảnh mới nếu bạn muốn thay ảnh hiện tại.', 'Upload a new image only if you want to replace the current one.')) ?></small>
            <button type="submit" class="btn secondary"><?= e(lang_text('Cập nhật lô hàng', 'Update lot')) ?></button>
          </form>

          <div class="inline-actions">
            <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/partner/lots/' . $lot['id'] . '/regenerate-qr')) ?>">
              <?= csrf_field() ?>
              <button type="submit" class="btn"><?= e(lang_text('Tạo lại QR', 'Regenerate QR')) ?></button>
            </form>
            <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/partner/lots/' . $lot['id'])) ?>" onsubmit="return confirm('<?= e(lang_text('Bạn có chắc muốn xóa lô hàng này?', 'Delete this lot?')) ?>');">
              <?= csrf_field() ?>
              <?= method_field('DELETE') ?>
              <button type="submit" class="btn danger"><?= e(lang_text('Xóa lô hàng', 'Delete lot')) ?></button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php require BASE_PATH . '/resources/views/layouts/app_footer.php'; ?>
