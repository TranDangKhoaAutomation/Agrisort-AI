<?php
$pageTitle = t('trace_title');
require BASE_PATH . '/resources/views/layouts/header.php';

$traceData = is_array($traceData ?? null) ? $traceData : [];
$entityType = (string) ($traceData['entity_type'] ?? 'lot');
$lot = is_array($traceData['lot'] ?? null) ? $traceData['lot'] : [];
$package = is_array($traceData['package'] ?? null) ? $traceData['package'] : null;
$events = is_array($traceData['events'] ?? null) ? $traceData['events'] : [];

$total = (int) ($lot['grade1_count'] ?? 0) + (int) ($lot['grade2_count'] ?? 0) + (int) ($lot['defect_count'] ?? 0);
$qrToken = (string) ($traceData['token'] ?? '');
$traceUrl = \App\Services\QrService::url($qrToken);
$qrAssets = \App\Services\QrService::storedImageUrls($qrToken, 8);
$qrPngUrl = (string) ($qrAssets['png'] ?? '');
$qrSvgUrl = (string) ($qrAssets['svg'] ?? '');
$qrImage = $qrPngUrl !== '' ? $qrPngUrl : \App\Services\QrService::imageDataUri($qrToken, 8);
$qrDownloadStem = 'trace-qr-' . preg_replace('/[^A-Za-z0-9._-]/', '-', $qrToken);
$productVisual = \App\Services\ProductVisualService::resolveLotImage($lot, lang());
$entityLabel = $entityType === 'package'
    ? lang_text('Gói hàng', 'Package')
    : lang_text('Lô hàng', 'Lot');
?>
<section class="container page-section">
  <div class="section-head section-head-inline">
    <p class="section-kicker"><?= e(lang_text('Truy xuất QR', 'QR trace')) ?></p>
    <h1><?= e(t('trace_title')) ?></h1>
  </div>

  <div class="trace-grid">
    <article class="card">
      <p><strong><?= e(lang_text('Loại đối tượng:', 'Entity type:')) ?></strong> <?= e($entityLabel) ?></p>
      <?php if ($package): ?>
        <p><strong><?= e(lang_text('Mã gói:', 'Package code:')) ?></strong> <?= e((string) ($package['package_code'] ?? '')) ?></p>
        <p><strong><?= e(lang_text('Nhãn gói:', 'Package label:')) ?></strong> <?= e((string) ($package['package_label'] ?? '-')) ?></p>
        <p><strong><?= e(lang_text('Số lượng gói:', 'Package quantity:')) ?></strong> <?= e((string) ($package['quantity'] ?? 0)) ?></p>
        <p><strong><?= e(lang_text('Khối lượng tịnh (kg):', 'Net weight (kg):')) ?></strong> <?= e((string) ($package['net_weight_kg'] ?? '-')) ?></p>
      <?php endif; ?>
      <p><strong><?= e(lang_text('Mã lô:', 'Lot code:')) ?></strong> <?= e((string) ($lot['lot_code'] ?? '')) ?></p>
      <p><strong><?= e(lang_text('Sản phẩm:', 'Produce:')) ?></strong> <?= e((string) ($lot['produce_type'] ?? '')) ?></p>
      <p><strong><?= e(lang_text('Xuất xứ:', 'Origin region:')) ?></strong> <?= e((string) ($lot['origin_region'] ?? '')) ?></p>
      <p><strong><?= e(lang_text('Ngày thu hoạch:', 'Harvest date:')) ?></strong> <?= e((string) ($lot['harvest_date'] ?? '')) ?></p>
      <p><strong><?= e(lang_text('Đơn vị:', 'Organization:')) ?></strong> <?= e((string) (($lot['organization_name'] ?? '') ?: ($lot['partner_name'] ?? ''))) ?></p>
      <p><strong><?= e(lang_text('Loại 1:', 'Grade 1:')) ?></strong> <?= e((string) ($lot['grade1_count'] ?? 0)) ?></p>
      <p><strong><?= e(lang_text('Loại 2:', 'Grade 2:')) ?></strong> <?= e((string) ($lot['grade2_count'] ?? 0)) ?></p>
      <p><strong><?= e(lang_text('Lỗi:', 'Defect:')) ?></strong> <?= e((string) ($lot['defect_count'] ?? 0)) ?></p>
      <p><strong><?= e(lang_text('Tổng số:', 'Total:')) ?></strong> <?= e((string) $total) ?></p>
      <?php if (!empty($lot['notes'])): ?>
        <p><strong><?= e(lang_text('Ghi chú:', 'Notes:')) ?></strong> <?= e((string) $lot['notes']) ?></p>
      <?php endif; ?>

      <figure class="lot-figure">
        <img src="<?= e((string) $productVisual['src']) ?>" alt="<?= e((string) $productVisual['alt']) ?>" class="lot-image">
        <?php if (!empty($productVisual['is_placeholder'])): ?>
          <figcaption class="lot-image-note"><?= e((string) $productVisual['note']) ?></figcaption>
        <?php endif; ?>
      </figure>
    </article>

    <article class="card qr-card">
      <h2><?= e(lang_text('QR da quet', 'Scanned QR')) ?></h2>
      <img src="<?= e($qrImage) ?>" alt="<?= e(lang_text('Mã QR', 'QR code')) ?> <?= e($qrToken) ?>" class="qr-image qr-image-lg">
      <p class="muted"><?= e(lang_text('Mã truy xuất:', 'Trace token:')) ?> <?= e($qrToken) ?></p>
      <p class="muted"><?= e(lang_text('QR này chỉ nhúng token truy xuất, không nhúng URL.', 'This QR embeds only the trace token, not a URL.')) ?></p>
      <button type="button" class="btn copy-url-btn" data-url="<?= e($qrToken) ?>" data-copied-label="<?= e(lang_text('Đã sao chép', 'Copied')) ?>"><?= e(lang_text('Sao chép token', 'Copy token')) ?></button>
      <a class="btn secondary" href="<?= e($traceUrl) ?>" target="_blank"><?= e(lang_text('Mở trang truy xuất', 'Open trace page')) ?></a>
      <p class="muted"><?= e(lang_text('QR nay duoc dung lai tu token da quet de doi chieu tren dien thoai va may tinh.', 'This QR is rebuilt from the scanned token so it can be checked on both phone and desktop.')) ?></p>
      <?php if ($qrPngUrl !== ''): ?>
        <a class="btn" href="<?= e($qrPngUrl) ?>" download="<?= e($qrDownloadStem . '.png') ?>"><?= e(lang_text('Tải PNG', 'Download PNG')) ?></a>
      <?php else: ?>
        <button type="button" class="btn download-png-btn" data-img="<?= e($qrImage) ?>" data-filename="<?= e($qrDownloadStem . '.png') ?>"><?= e(lang_text('Tải PNG', 'Download PNG')) ?></button>
      <?php endif; ?>
      <?php if ($qrSvgUrl !== ''): ?>
        <a class="btn" href="<?= e($qrSvgUrl) ?>" download="<?= e($qrDownloadStem . '.svg') ?>"><?= e(lang_text('Tải SVG', 'Download SVG')) ?></a>
      <?php endif; ?>
    </article>
  </div>

  <article class="card trace-timeline-card">
    <h2><?= e(lang_text('Hành trình chuỗi cung ứng', 'Supply-chain timeline')) ?></h2>
    <?php if ($events === []): ?>
      <p class="muted"><?= e(lang_text('Chưa có sự kiện chuỗi cung ứng nào được công bố.', 'No supply-chain events have been published yet.')) ?></p>
    <?php else: ?>
      <div class="trace-timeline">
        <?php foreach ($events as $event): ?>
          <div class="trace-event-item">
            <p><strong><?= e((string) ($event['event_time'] ?? '')) ?></strong> - <?= e((string) ($event['stage_code'] ?? '')) ?></p>
            <p><?= e((string) ($event['location_name'] ?? '')) ?></p>
            <?php if (!empty($event['note'])): ?>
              <p class="muted"><?= e((string) $event['note']) ?></p>
            <?php endif; ?>
            <p class="muted"><?= e(lang_text('Cập nhật bởi', 'Updated by')) ?>: <?= e((string) ($event['actor_name'] ?? '-')) ?></p>
            <?php $attachments = is_array($event['attachments'] ?? null) ? $event['attachments'] : []; ?>
            <?php if ($attachments !== []): ?>
              <div class="trace-attachments">
                <?php foreach ($attachments as $attachment): ?>
                  <a
                    href="<?= e(app_url('/' . ltrim((string) ($attachment['file_path'] ?? ''), '/'))) ?>"
                    target="_blank"
                    rel="noopener"
                  >
                    <?= e((string) ($attachment['original_name'] ?? 'Attachment')) ?>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </article>
</section>
<?php require BASE_PATH . '/resources/views/layouts/footer.php'; ?>
