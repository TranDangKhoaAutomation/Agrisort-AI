<?php
$pageTitle = lang_text('Bảng điều khiển chuỗi cung ứng', 'Supply-chain dashboard');
require BASE_PATH . '/resources/views/layouts/app_header.php';

$assignments = is_array($assignments ?? null) ? $assignments : [];
$events = is_array($events ?? null) ? $events : [];
$dashboardSection = array_merge([
    'title' => lang_text('Theo dõi các chặng vận hành và cập nhật sự kiện truy xuất', 'Track operational stages and update traceability events'),
    'body' => lang_text(
        'Tại đây các vai trò vận chuyển, kho và bán hàng có thể ghi nhận thời gian, vị trí và tệp đính kèm cho từng chặng được phân công.',
        'Transport, warehouse, and seller roles can record time, location, and attachments for every assigned stage from here.'
    ),
], is_array($dashboardSection ?? null) ? $dashboardSection : []);

$entityLabel = static function (string $entityType): string {
    return $entityType === 'package'
        ? lang_text('Gói hàng', 'Package')
        : lang_text('Lô hàng', 'Lot');
};

$splitLines = static function (?string $text): array {
    $rows = preg_split('/\R+/u', trim((string) $text)) ?: [];

    return array_values(array_filter(array_map('trim', $rows), static fn (string $row): bool => $row !== ''));
};

$attachmentCount = 0;
foreach ($events as $event) {
    $attachmentCount += count(is_array($event['attachments'] ?? null) ? $event['attachments'] : []);
}
?>
<section class="container page-section dashboard-page">
  <div class="dashboard-hero card">
    <div class="dashboard-hero-copy">
      <p class="section-kicker"><?= e(lang_text('Không gian tác nghiệp', 'Operations workspace')) ?></p>
      <h1><?= e((string) ($dashboardSection['title'] ?? $pageTitle)) ?></h1>
      <?php foreach ($splitLines((string) ($dashboardSection['body'] ?? '')) as $line): ?>
        <p><?= e($line) ?></p>
      <?php endforeach; ?>
      <div class="dashboard-pill-list">
        <span><?= e(lang_text('Cập nhật thời gian và vị trí', 'Update time and location')) ?></span>
        <span><?= e(lang_text('Đính kèm ảnh hoặc PDF', 'Attach images or PDFs')) ?></span>
        <span><?= e(lang_text('Làm việc theo phân công', 'Work by assignment')) ?></span>
      </div>
    </div>

    <div class="dashboard-hero-side">
      <div class="dashboard-mini-stats">
        <div>
          <strong><?= e((string) count($assignments)) ?></strong>
          <span><?= e(lang_text('Phân công', 'Assignments')) ?></span>
        </div>
        <div>
          <strong><?= e((string) count($events)) ?></strong>
          <span><?= e(lang_text('Sự kiện', 'Events')) ?></span>
        </div>
        <div>
          <strong><?= e((string) $attachmentCount) ?></strong>
          <span><?= e(lang_text('Tệp đính kèm', 'Attachments')) ?></span>
        </div>
      </div>
      <div class="dashboard-hero-panel">
        <strong><?= e(lang_text('Nguyên tắc nhập liệu', 'Data-entry rule')) ?></strong>
        <p><?= e(lang_text(
            'Chỉ cập nhật những chặng bạn được gán. Thông tin ghi nhận sẽ xuất hiện trên luồng truy xuất của lô hoặc gói hàng tương ứng.',
            'Only update stages assigned to you. Recorded information will appear in the traceability flow of the related lot or package.'
        )) ?></p>
      </div>
    </div>
  </div>

  <div class="grid-2 dashboard-form-grid">
    <article class="card studio-panel">
      <div class="section-head section-head-inline">
        <p class="section-kicker"><?= e(lang_text('Theo dõi phân công', 'Assignments')) ?></p>
        <h2><?= e(lang_text('Các giai đoạn được giao', 'Assigned stages')) ?></h2>
      </div>
      <?php if ($assignments === []): ?>
        <p class="muted"><?= e(lang_text('Chưa có phân công nào cho tài khoản này.', 'No stage assignments for this account yet.')) ?></p>
      <?php else: ?>
        <div class="table-shell">
          <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th><?= e(lang_text('Đối tượng', 'Entity')) ?></th>
                <th><?= e(lang_text('Mã lô / gói', 'Lot / package code')) ?></th>
                <th><?= e(lang_text('Mã chặng', 'Stage code')) ?></th>
                <th><?= e(lang_text('Cập nhật lúc', 'Updated at')) ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($assignments as $assignment): ?>
                <tr>
                  <td><?= e((string) $assignment['id']) ?></td>
                  <td><?= e($entityLabel((string) $assignment['entity_type'])) ?></td>
                  <td><?= e((string) ($assignment['entity_type'] === 'lot' ? ($assignment['lot_code'] ?? '') : ($assignment['package_code'] ?? ''))) ?></td>
                  <td><?= e((string) $assignment['stage_code']) ?></td>
                  <td><?= e((string) $assignment['updated_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </article>

    <article class="card studio-panel">
      <div class="section-head section-head-inline">
        <p class="section-kicker"><?= e(lang_text('Ghi nhận sự kiện', 'Event logging')) ?></p>
        <h2><?= e(lang_text('Tạo sự kiện vận hành mới', 'Create a new operational event')) ?></h2>
      </div>
      <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/supply/events')) ?>" enctype="multipart/form-data" class="form-card">
        <?= csrf_field() ?>
        <label>
          <?= e(lang_text('Loại đối tượng', 'Entity type')) ?>
          <select name="entity_type" required>
            <option value="lot"><?= e(lang_text('Lô hàng', 'Lot')) ?></option>
            <option value="package"><?= e(lang_text('Gói hàng', 'Package')) ?></option>
          </select>
        </label>
        <label><?= e(lang_text('ID đối tượng', 'Entity ID')) ?> <input type="number" name="entity_id" min="1" required></label>
        <label><?= e(lang_text('Mã giai đoạn', 'Stage code')) ?> <input type="text" name="stage_code" required></label>
        <label><?= e(lang_text('Thời gian sự kiện', 'Event time')) ?> <input type="datetime-local" name="event_time" required></label>
        <label><?= e(lang_text('Vị trí', 'Location')) ?> <input type="text" name="location_name" required></label>
        <label><?= e(lang_text('Ghi chú', 'Note')) ?> <textarea name="note" rows="3"></textarea></label>
        <label><?= e(lang_text('Tệp đính kèm (ảnh / PDF)', 'Attachment (image / PDF)')) ?> <input type="file" name="attachments[]" accept="image/*,.pdf" multiple></label>
        <button type="submit" class="btn primary"><?= e(lang_text('Lưu sự kiện', 'Save event')) ?></button>
      </form>
    </article>
  </div>

  <article class="card studio-panel">
    <div class="section-head section-head-inline">
      <p class="section-kicker"><?= e(lang_text('Lịch sử công việc', 'Work history')) ?></p>
      <h2><?= e(lang_text('Các sự kiện bạn đã ghi nhận', 'Events you have recorded')) ?></h2>
    </div>

    <?php if ($events === []): ?>
      <p class="muted"><?= e(lang_text('Chưa có sự kiện nào.', 'No events yet.')) ?></p>
    <?php else: ?>
      <div class="card-list dashboard-card-list">
        <?php foreach ($events as $event): ?>
          <?php $attachments = is_array($event['attachments'] ?? null) ? $event['attachments'] : []; ?>
          <article class="card dashboard-event-card">
            <div class="dashboard-event-head">
              <div>
                <h3><?= e((string) $event['stage_code']) ?> - <?= e($entityLabel((string) $event['entity_type'])) ?> #<?= e((string) $event['entity_id']) ?></h3>
                <p><?= e((string) $event['event_time']) ?> - <?= e((string) $event['location_name']) ?></p>
              </div>
              <span class="status-pill"><?= e(count($attachments) . ' ' . lang_text('tệp đính kèm', 'attachments')) ?></span>
            </div>

            <?php if (!empty($event['note'])): ?>
              <p class="muted"><?= e((string) $event['note']) ?></p>
            <?php endif; ?>

            <?php if ($attachments !== []): ?>
              <div class="trace-attachments">
                <?php foreach ($attachments as $attachment): ?>
                  <a href="<?= e(app_url('/' . ltrim((string) $attachment['file_path'], '/'))) ?>" target="_blank" rel="noopener">
                    <?= e((string) $attachment['original_name']) ?>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/supply/events/' . $event['id'])) ?>" enctype="multipart/form-data" class="form-card compact">
              <?= csrf_field() ?>
              <?= method_field('PUT') ?>
              <label><?= e(lang_text('Thời gian sự kiện', 'Event time')) ?> <input type="datetime-local" name="event_time" value="<?= e((string) date('Y-m-d\\TH:i', strtotime((string) $event['event_time']))) ?>" required></label>
              <label><?= e(lang_text('Vị trí', 'Location')) ?> <input type="text" name="location_name" value="<?= e((string) $event['location_name']) ?>" required></label>
              <label><?= e(lang_text('Ghi chú', 'Note')) ?> <textarea name="note" rows="2"><?= e((string) ($event['note'] ?? '')) ?></textarea></label>
              <label><?= e(lang_text('Thêm tệp đính kèm', 'Add attachment')) ?> <input type="file" name="attachments[]" accept="image/*,.pdf" multiple></label>
              <button type="submit" class="btn secondary"><?= e(lang_text('Cập nhật sự kiện', 'Update event')) ?></button>
            </form>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </article>
</section>
<?php require BASE_PATH . '/resources/views/layouts/app_footer.php'; ?>
