<?php
$pageTitle = lang_text('Trung tâm QR truy xuất', 'Trace QR center');
require BASE_PATH . '/resources/views/layouts/app_header.php';

$lots = is_array($lots ?? null) ? $lots : [];
$packages = is_array($packages ?? null) ? $packages : [];
$autoPublish = !empty($autoPublish);

$statusLabel = static function (string $status): string {
    return match ($status) {
        'published' => lang_text('Đã xuất bản', 'Published'),
        default => lang_text('Bản nháp', 'Draft'),
    };
};

$statusTone = static function (string $status): string {
    return $status === 'published' ? 'is-published' : 'is-draft';
};

$formatWeight = static function ($weight): string {
    if ($weight === null || $weight === '') {
        return lang_text('Chưa khai báo', 'Not set');
    }

    return rtrim(rtrim(number_format((float) $weight, 3, '.', ''), '0'), '.') . ' kg';
};

$totalLots = count($lots);
$totalPackages = count($packages);
$publishedLots = 0;
$publishedPackages = 0;

foreach ($lots as $lot) {
    if ((string) ($lot['publish_status'] ?? 'draft') === 'published') {
        $publishedLots++;
    }
}

foreach ($packages as $package) {
    if ((string) ($package['publish_status'] ?? 'draft') === 'published') {
        $publishedPackages++;
    }
}

$sampleToken = '';
if ($packages !== []) {
    $sampleToken = (string) ($packages[0]['qr_token'] ?? '');
} elseif ($lots !== []) {
    $sampleToken = (string) ($lots[0]['qr_token'] ?? '');
}
if ($sampleToken === '') {
    $sampleToken = 'sample-trace-token';
}
$sampleTraceUrl = \App\Services\QrService::url($sampleToken);
?>
<section class="container page-section partner-qr-page">
  <div class="card qr-hero">
    <div class="qr-hero-main">
      <p class="section-kicker"><?= e(lang_text('Không gian vận hành QR', 'QR operations workspace')) ?></p>
      <h1><?= e($pageTitle) ?></h1>
      <p class="page-lead">
        <?= e(lang_text(
            'Quản lý toàn bộ QR theo lô và gói trong một màn hình: xem trước mã, tải xuống PNG/SVG, in nhãn và mở nhanh trang truy xuất.',
            'Manage all lot and package QR assets from one screen: preview the code, download PNG/SVG, print labels, and jump straight to the trace page.'
        )) ?>
      </p>

      <div class="dashboard-pill-list qr-hero-pills">
        <span><?= e(lang_text('QR giờ chỉ nhúng token truy xuất', 'QR now embeds only the trace token')) ?></span>
        <span><?= e($autoPublish ? lang_text('Tự động xuất bản đang bật', 'Auto publish is on') : lang_text('Tự động xuất bản đang tắt', 'Auto publish is off')) ?></span>
        <span><?= e(lang_text('Sẵn sàng tải và in nhãn trực tiếp', 'Ready for direct download and label printing')) ?></span>
      </div>
    </div>

    <div class="qr-hero-side">
      <div class="qr-metric-grid">
        <div class="qr-metric-card">
          <strong><?= e((string) $totalLots) ?></strong>
          <span><?= e(lang_text('lô có QR', 'lots with QR')) ?></span>
        </div>
        <div class="qr-metric-card">
          <strong><?= e((string) $totalPackages) ?></strong>
          <span><?= e(lang_text('gói có QR', 'packages with QR')) ?></span>
        </div>
        <div class="qr-metric-card">
          <strong><?= e((string) $publishedLots) ?></strong>
          <span><?= e(lang_text('lô đang công khai', 'published lots')) ?></span>
        </div>
        <div class="qr-metric-card">
          <strong><?= e((string) $publishedPackages) ?></strong>
          <span><?= e(lang_text('gói đang công khai', 'published packages')) ?></span>
        </div>
      </div>

      <div class="qr-hero-note">
        <span class="qr-note-label"><?= e(lang_text('Payload trong QR', 'QR payload')) ?></span>
        <strong><?= e(lang_text('Payload trong QR chỉ còn token, phù hợp cho máy quét hoặc app truy xuất đọc trực tiếp.', 'The QR payload now stores only the token, which works better for scanners or trace apps that read the raw token directly.')) ?></strong>
        <code class="qr-hero-link"><?= e($sampleToken) ?></code>
      </div>
    </div>
  </div>

  <div class="qr-workspace-grid">
    <article class="card qr-studio-card">
      <div class="section-head section-head-inline">
        <p class="section-kicker"><?= e(lang_text('Thiết lập nhanh', 'Quick setup')) ?></p>
        <h2><?= e(lang_text('Nguyên tắc để QR hoạt động tốt trên điện thoại', 'Rules for QR that work well on phones')) ?></h2>
      </div>

      <div class="qr-highlight-list">
        <div class="qr-highlight-item">
          <strong><?= e(lang_text('QR hiện chỉ chứa token', 'QR now stores token only')) ?></strong>
          <p><?= e(lang_text('Dùng trang quét QR hoặc ứng dụng tích hợp để đọc token và mở đúng hồ sơ truy xuất. Mã không còn phụ thuộc trực tiếp vào domain công khai.', 'Use the QR scan page or an integrated app to read the token and open the correct trace record. The code no longer depends directly on a public domain.')) ?></p>
        </div>
        <div class="qr-highlight-item">
          <strong><?= e(lang_text('Phát hành theo trạng thái phù hợp', 'Publish with the right status')) ?></strong>
          <p><?= e($autoPublish
              ? lang_text('Hệ thống hiện đang tự động xuất bản bản ghi mới ngay khi tạo.', 'The system is currently auto-publishing new records as soon as they are created.')
              : lang_text('Hệ thống hiện giữ bản ghi ở trạng thái nháp cho đến khi bạn công khai.', 'The system currently keeps new records in draft until you publish them.')) ?></p>
        </div>
        <div class="qr-highlight-item">
          <strong><?= e(lang_text('Ưu tiên SVG khi in số lượng lớn', 'Prefer SVG for bulk printing')) ?></strong>
          <p><?= e(lang_text(
              'SVG giữ nét tốt hơn cho in tem số lượng lớn, còn PNG phù hợp khi gửi nhanh qua chat hoặc tài liệu.',
              'SVG keeps edges sharper for bulk label printing, while PNG is convenient for quick sharing in chat or documents.'
          )) ?></p>
        </div>
      </div>
    </article>

    <article class="card qr-studio-card">
      <div class="section-head section-head-inline">
        <p class="section-kicker"><?= e(lang_text('Tạo mới', 'Create new')) ?></p>
        <h2><?= e(lang_text('Tạo QR cho gói hàng', 'Create QR for a package')) ?></h2>
      </div>

      <?php if ($lots === []): ?>
        <div class="qr-empty-state">
          <p><?= e(lang_text('Bạn cần tạo ít nhất một lô trước khi sinh QR cho gói hàng.', 'You need at least one lot before creating package QR codes.')) ?></p>
        </div>
      <?php else: ?>
        <p class="qr-form-note muted"><?= e(lang_text(
            'Mỗi gói sẽ được gắn với một lô nguồn để trang truy xuất hiển thị đúng loại nông sản, số lượng và trọng lượng.',
            'Each package is linked to a source lot so the trace page can show the correct produce type, quantity, and weight.'
        )) ?></p>

        <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/partner/packages')) ?>" class="form-card qr-package-create-form">
          <?= csrf_field() ?>
          <label>
            <?= e(lang_text('Chọn lô nguồn', 'Select source lot')) ?>
            <select name="lot_id" required>
              <?php foreach ($lots as $lot): ?>
                <option value="<?= e((string) $lot['id']) ?>"><?= e((string) $lot['lot_code']) ?> - <?= e((string) $lot['produce_type']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label><?= e(lang_text('Mã gói', 'Package code')) ?> <input type="text" name="package_code" required></label>
          <label><?= e(lang_text('Nhãn gói hàng', 'Package label')) ?> <input type="text" name="package_label"></label>
          <label><?= e(lang_text('Số lượng', 'Quantity')) ?> <input type="number" name="quantity" min="1" value="1" required></label>
          <label><?= e(lang_text('Trọng lượng tịnh (kg)', 'Net weight (kg)')) ?> <input type="number" step="0.001" min="0" name="net_weight_kg"></label>
          <button type="submit" class="btn primary"><?= e(lang_text('Tạo gói và sinh QR', 'Create package and generate QR')) ?></button>
        </form>
      <?php endif; ?>
    </article>
  </div>

  <div class="section-head section-head-inline">
    <p class="section-kicker"><?= e(lang_text('QR theo lô', 'Lot QR library')) ?></p>
    <h2><?= e(lang_text('Mã QR của từng lô hàng', 'QR assets for each lot')) ?></h2>
  </div>

  <?php if ($lots === []): ?>
    <article class="card qr-empty-state">
      <p><?= e(lang_text('Chưa có lô hàng nào để hiển thị QR.', 'There are no lots to display QR assets for yet.')) ?></p>
    </article>
  <?php else: ?>
    <div class="card-list qr-collection">
      <?php foreach ($lots as $lot): ?>
        <?php
          $lotToken = (string) ($lot['qr_token'] ?? '');
          $lotTrace = \App\Services\QrService::url($lotToken);
          $lotAssets = \App\Services\QrService::storedImageUrls($lotToken, 8);
          $lotPngUrl = (string) ($lotAssets['png'] ?? '');
          $lotSvgUrl = (string) ($lotAssets['svg'] ?? '');
          $lotQrImage = $lotPngUrl !== '' ? $lotPngUrl : \App\Services\QrService::imageDataUri($lotToken, 8);
          $lotDownloadName = 'lot-qr-' . preg_replace('/[^A-Za-z0-9._-]/', '-', (string) ($lot['lot_code'] ?? $lotToken)) . '.svg';
          $lotDownloadPng = str_replace('.svg', '.png', $lotDownloadName);
          $lotStatus = (string) ($lot['publish_status'] ?? 'draft');
          $productVisual = \App\Services\ProductVisualService::resolveLotImage($lot, lang());
          $lotNotes = trim((string) ($lot['notes'] ?? ''));
        ?>
        <article class="card qr-entity-card">
          <div class="qr-entity-head">
            <div>
              <p class="qr-entity-eyebrow"><?= e(lang_text('QR lô hàng', 'Lot QR')) ?></p>
              <h3><?= e((string) ($lot['lot_code'] ?? '')) ?> - <?= e((string) ($lot['produce_type'] ?? '')) ?></h3>
              <div class="qr-entity-summary">
                <span class="qr-summary-chip <?= e($statusTone($lotStatus)) ?>"><?= e($statusLabel($lotStatus)) ?></span>
                <span class="qr-summary-chip"><?= e((string) ($lot['origin_region'] ?? lang_text('Chưa có vùng', 'No region'))) ?></span>
                <span class="qr-summary-chip"><?= e((string) ($lot['harvest_date'] ?? lang_text('Chưa có ngày', 'No harvest date'))) ?></span>
              </div>
            </div>

            <a class="btn secondary" href="<?= e($lotTrace) ?>" target="_blank" rel="noopener">
              <?= e(lang_text('Mở trang truy xuất', 'Open trace page')) ?>
            </a>
          </div>

          <div class="qr-entity-grid">
            <div class="qr-side-stack">
              <div class="product-preview-card qr-product-preview">
                <img src="<?= e((string) $productVisual['src']) ?>" alt="<?= e((string) $productVisual['alt']) ?>" class="product-preview-image">
                <div class="product-preview-copy">
                  <span class="product-badge <?= !empty($productVisual['is_placeholder']) ? 'is-placeholder' : 'is-photo' ?>"><?= e((string) $productVisual['badge']) ?></span>
                  <p class="product-preview-note"><?= e((string) $productVisual['note']) ?></p>
                </div>
              </div>

              <div class="qr-fact-grid">
                <div class="qr-fact">
                  <span><?= e(lang_text('Token', 'Token')) ?></span>
                  <strong class="qr-fact-mono"><?= e($lotToken) ?></strong>
                </div>
                <div class="qr-fact">
                  <span><?= e(lang_text('Mã lô', 'Lot code')) ?></span>
                  <strong><?= e((string) ($lot['lot_code'] ?? '')) ?></strong>
                </div>
                <div class="qr-fact">
                  <span><?= e(lang_text('Nông sản', 'Produce')) ?></span>
                  <strong><?= e((string) ($lot['produce_type'] ?? '')) ?></strong>
                </div>
                <div class="qr-fact">
                  <span><?= e(lang_text('Ghi chú', 'Notes')) ?></span>
                  <strong><?= e($lotNotes !== '' ? $lotNotes : lang_text('Chưa có ghi chú', 'No notes yet')) ?></strong>
                </div>
              </div>
            </div>

            <div class="qr-preview qr-preview-rich">
              <img src="<?= e($lotQrImage) ?>" alt="QR <?= e($lotToken) ?>" class="qr-image">
              <div class="qr-meta">
                <div class="qr-link-field">
                  <span><?= e(lang_text('Payload đang được nhúng trong QR', 'Payload embedded inside QR')) ?></span>
                  <code title="<?= e($lotToken) ?>"><?= e($lotToken) ?></code>
                </div>
                <div class="qr-link-field">
                  <span><?= e(lang_text('Trang truy xuất mở từ token', 'Trace page opened from token')) ?></span>
                  <code title="<?= e($lotTrace) ?>"><?= e($lotTrace) ?></code>
                </div>

                <div class="qr-meta-actions">
                  <button type="button" class="btn copy-url-btn" data-url="<?= e($lotToken) ?>" data-copied-label="<?= e(lang_text('Đã sao chép', 'Copied')) ?>">
                    <?= e(lang_text('Sao chép token', 'Copy token')) ?>
                  </button>
                  <button type="button" class="btn secondary copy-url-btn" data-url="<?= e($lotTrace) ?>" data-copied-label="<?= e(lang_text('Đã sao chép', 'Copied')) ?>">
                    <?= e(lang_text('Sao chép trang truy xuất', 'Copy trace page')) ?>
                  </button>
                  <button type="button" class="btn print-qr-btn" data-url="<?= e($lotToken) ?>" data-img="<?= e($lotQrImage) ?>">
                    <?= e(lang_text('In nhãn QR', 'Print QR label')) ?>
                  </button>
                  <?php if ($lotPngUrl !== ''): ?>
                    <a class="btn" href="<?= e($lotPngUrl) ?>" download="<?= e($lotDownloadPng) ?>"><?= e(lang_text('Tải PNG', 'Download PNG')) ?></a>
                  <?php else: ?>
                    <button type="button" class="btn download-png-btn" data-img="<?= e($lotQrImage) ?>" data-filename="<?= e($lotDownloadPng) ?>"><?= e(lang_text('Tải PNG', 'Download PNG')) ?></button>
                  <?php endif; ?>
                  <?php if ($lotSvgUrl !== ''): ?>
                    <a class="btn" href="<?= e($lotSvgUrl) ?>" download="<?= e($lotDownloadName) ?>"><?= e(lang_text('Tải SVG', 'Download SVG')) ?></a>
                  <?php endif; ?>
                </div>

                <p class="qr-note"><?= e(lang_text('QR n?y ch? ch?a token truy xu?t. D?ng trang qu?t QR ho?c n?t m? nhanh ?? v?o ??ng h? s? c?ng khai.', 'This QR stores only the trace token. Use the scan page or the quick-open button to reach the public record.')) ?></p>
              </div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="section-head section-head-inline">
    <p class="section-kicker"><?= e(lang_text('QR theo gói', 'Package QR library')) ?></p>
    <h2><?= e(lang_text('Mã QR của từng gói hàng', 'QR assets for each package')) ?></h2>
  </div>

  <?php if ($packages === []): ?>
    <article class="card qr-empty-state">
      <p><?= e(lang_text('Chưa có gói hàng nào để hiển thị QR.', 'There are no packages to display QR assets for yet.')) ?></p>
    </article>
  <?php else: ?>
    <div class="card-list qr-collection qr-collection-packages">
      <?php foreach ($packages as $package): ?>
        <?php
          $token = (string) ($package['qr_token'] ?? '');
          $traceUrl = \App\Services\QrService::url($token);
          $packageAssets = \App\Services\QrService::storedImageUrls($token, 8);
          $packagePngUrl = (string) ($packageAssets['png'] ?? '');
          $packageSvgUrl = (string) ($packageAssets['svg'] ?? '');
          $qrImage = $packagePngUrl !== '' ? $packagePngUrl : \App\Services\QrService::imageDataUri($token, 8);
          $packageDownloadName = 'package-qr-' . preg_replace('/[^A-Za-z0-9._-]/', '-', (string) ($package['package_code'] ?? $token)) . '.svg';
          $packageDownloadPng = str_replace('.svg', '.png', $packageDownloadName);
          $packageStatus = (string) ($package['publish_status'] ?? 'draft');
          $packageLabel = trim((string) ($package['package_label'] ?? ''));
        ?>
        <article class="card qr-entity-card qr-package-card">
          <div class="qr-entity-head">
            <div>
              <p class="qr-entity-eyebrow"><?= e(lang_text('QR gói hàng', 'Package QR')) ?></p>
              <h3><?= e((string) ($package['package_code'] ?? '')) ?> - <?= e((string) ($package['lot_code'] ?? '')) ?></h3>
              <div class="qr-entity-summary">
                <span class="qr-summary-chip <?= e($statusTone($packageStatus)) ?>"><?= e($statusLabel($packageStatus)) ?></span>
                <span class="qr-summary-chip"><?= e((string) ($package['produce_type'] ?? lang_text('Chưa rõ loại', 'Unknown produce'))) ?></span>
                <span class="qr-summary-chip"><?= e(lang_text('SL', 'Qty') . ': ' . (string) ($package['quantity'] ?? 0)) ?></span>
              </div>
            </div>

            <a class="btn secondary" href="<?= e($traceUrl) ?>" target="_blank" rel="noopener">
              <?= e(lang_text('Mở trang truy xuất', 'Open trace page')) ?>
            </a>
          </div>

          <div class="qr-entity-grid qr-entity-grid-package">
            <div class="qr-side-stack">
              <div class="qr-fact-grid">
                <div class="qr-fact">
                  <span><?= e(lang_text('Mã gói', 'Package code')) ?></span>
                  <strong><?= e((string) ($package['package_code'] ?? '')) ?></strong>
                </div>
                <div class="qr-fact">
                  <span><?= e(lang_text('Thuộc lô', 'Source lot')) ?></span>
                  <strong><?= e((string) ($package['lot_code'] ?? '')) ?></strong>
                </div>
                <div class="qr-fact">
                  <span><?= e(lang_text('Loại nông sản', 'Produce type')) ?></span>
                  <strong><?= e((string) ($package['produce_type'] ?? '')) ?></strong>
                </div>
                <div class="qr-fact">
                  <span><?= e(lang_text('Nhãn gói', 'Package label')) ?></span>
                  <strong><?= e($packageLabel !== '' ? $packageLabel : lang_text('Chưa đặt nhãn', 'No label yet')) ?></strong>
                </div>
                <div class="qr-fact">
                  <span><?= e(lang_text('Số lượng', 'Quantity')) ?></span>
                  <strong><?= e((string) ($package['quantity'] ?? 0)) ?></strong>
                </div>
                <div class="qr-fact">
                  <span><?= e(lang_text('Trọng lượng', 'Net weight')) ?></span>
                  <strong><?= e($formatWeight($package['net_weight_kg'] ?? null)) ?></strong>
                </div>
                <div class="qr-fact qr-fact-span-2">
                  <span><?= e(lang_text('Token', 'Token')) ?></span>
                  <strong class="qr-fact-mono"><?= e($token) ?></strong>
                </div>
              </div>

              <div class="qr-preview qr-preview-rich">
                <img src="<?= e($qrImage) ?>" alt="QR <?= e($token) ?>" class="qr-image">
                <div class="qr-meta">
                  <div class="qr-link-field">
                    <span><?= e(lang_text('Payload ?ang ???c nh?ng trong QR', 'Payload embedded inside QR')) ?></span>
                    <code title="<?= e($token) ?>"><?= e($token) ?></code>
                  </div>
                  <div class="qr-link-field">
                    <span><?= e(lang_text('Trang truy xuất mở từ token', 'Trace page opened from token')) ?></span>
                    <code title="<?= e($traceUrl) ?>"><?= e($traceUrl) ?></code>
                  </div>

                  <div class="qr-meta-actions">
                    <button type="button" class="btn copy-url-btn" data-url="<?= e($token) ?>" data-copied-label="<?= e(lang_text('Đã sao chép', 'Copied')) ?>">
                      <?= e(lang_text('Sao chép token', 'Copy token')) ?>
                    </button>
                    <button type="button" class="btn secondary copy-url-btn" data-url="<?= e($traceUrl) ?>" data-copied-label="<?= e(lang_text('Đã sao chép', 'Copied')) ?>">
                      <?= e(lang_text('Sao chép token', 'Copy token')) ?>
                    </button>
                    <button type="button" class="btn print-qr-btn" data-url="<?= e($token) ?>" data-img="<?= e($qrImage) ?>">
                      <?= e(lang_text('In nhãn QR', 'Print QR label')) ?>
                    </button>
                    <?php if ($packagePngUrl !== ''): ?>
                      <a class="btn" href="<?= e($packagePngUrl) ?>" download="<?= e($packageDownloadPng) ?>"><?= e(lang_text('Tải PNG', 'Download PNG')) ?></a>
                    <?php else: ?>
                      <button type="button" class="btn download-png-btn" data-img="<?= e($qrImage) ?>" data-filename="<?= e($packageDownloadPng) ?>"><?= e(lang_text('Tải PNG', 'Download PNG')) ?></button>
                    <?php endif; ?>
                    <?php if ($packageSvgUrl !== ''): ?>
                      <a class="btn" href="<?= e($packageSvgUrl) ?>" download="<?= e($packageDownloadName) ?>"><?= e(lang_text('Tải SVG', 'Download SVG')) ?></a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>

            <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/partner/packages/' . $package['id'])) ?>" class="form-card compact qr-package-form">
              <?= csrf_field() ?>
              <?= method_field('PUT') ?>
              <label><?= e(lang_text('Mã gói', 'Package code')) ?> <input type="text" name="package_code" value="<?= e((string) ($package['package_code'] ?? '')) ?>" required></label>
              <label><?= e(lang_text('Nhãn gói hàng', 'Package label')) ?> <input type="text" name="package_label" value="<?= e((string) ($package['package_label'] ?? '')) ?>"></label>
              <label><?= e(lang_text('Số lượng', 'Quantity')) ?> <input type="number" min="1" name="quantity" value="<?= e((string) ($package['quantity'] ?? 1)) ?>" required></label>
              <label><?= e(lang_text('Trọng lượng tịnh (kg)', 'Net weight (kg)')) ?> <input type="number" step="0.001" min="0" name="net_weight_kg" value="<?= e((string) ($package['net_weight_kg'] ?? '')) ?>"></label>
              <label>
                <?= e(lang_text('Trạng thái xuất bản', 'Publish status')) ?>
                <select name="publish_status">
                  <option value="draft" <?= $packageStatus === 'draft' ? 'selected' : '' ?>><?= e(lang_text('Bản nháp', 'Draft')) ?></option>
                  <option value="published" <?= $packageStatus === 'published' ? 'selected' : '' ?>><?= e(lang_text('Đã xuất bản', 'Published')) ?></option>
                </select>
              </label>
              <button type="submit" class="btn secondary"><?= e(lang_text('Cập nhật gói', 'Update package')) ?></button>
            </form>
          </div>

          <div class="inline-actions">
            <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/partner/packages/' . $package['id'] . '/regenerate-qr')) ?>">
              <?= csrf_field() ?>
              <button type="submit" class="btn"><?= e(lang_text('Tạo lại QR', 'Regenerate QR')) ?></button>
            </form>
            <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/partner/packages/' . $package['id'])) ?>" onsubmit="return confirm('<?= e(lang_text('Xóa gói này?', 'Delete this package?')) ?>');">
              <?= csrf_field() ?>
              <?= method_field('DELETE') ?>
              <button type="submit" class="btn danger"><?= e(lang_text('Xóa gói', 'Delete package')) ?></button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php require BASE_PATH . '/resources/views/layouts/app_footer.php'; ?>
