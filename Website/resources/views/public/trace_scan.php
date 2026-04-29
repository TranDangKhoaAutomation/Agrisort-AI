<?php
$pageTitle = t('trace_title');
$traceAccess = is_array($traceAccess ?? null) ? $traceAccess : [];
$traceBlocked = (bool) ($traceAccess['blocked'] ?? false);
$tracePlanLabel = (string) ($traceAccess['plan_label'] ?? lang_text('Truy xuất công khai', 'Public traceability'));
$traceSummary = (string) ($traceAccess['summary'] ?? '');
$traceHint = (string) ($traceAccess['hint'] ?? '');
$traceVipUntilLabel = (string) ($traceAccess['vip_until_label'] ?? '');
require BASE_PATH . '/resources/views/layouts/header.php';
?>
<section
  class="container page-section trace-scan-page"
  data-trace-scanner
  data-upload-endpoint="<?= e(app_url('/trace/scan-upload')) ?>"
  data-lookup-endpoint="<?= e(app_url('/trace/lookup-token')) ?>"
  data-trace-base="<?= e(app_url('/trace/')) ?>"
  data-msg-camera-ready="<?= e(lang_text('Máy ảnh đã sẵn sàng. Đặt mã QR vào trong khung.', 'Camera is ready. Place the QR code inside the frame.')) ?>"
  data-msg-camera-opening="<?= e(lang_text('Đang mở máy ảnh...', 'Opening camera...')) ?>"
  data-msg-camera-scanning="<?= e(lang_text('Đang quét từ máy ảnh...', 'Scanning from camera...')) ?>"
  data-msg-camera-stopped="<?= e(lang_text('Máy ảnh đã dừng lại.', 'Camera stopped.')) ?>"
  data-msg-camera-denied="<?= e(lang_text('Quyền dùng máy ảnh bị từ chối. Hãy chuyển sang tải ảnh lên hoặc nhập mã thủ công.', 'Camera permission denied. Use file upload or manual token input.')) ?>"
  data-msg-camera-insecure="<?= e(lang_text('Trang này chưa ở ngữ cảnh bảo mật hợp lệ để mở máy ảnh. Nếu đang chạy local, hãy dùng http://localhost hoặc http://127.0.0.1 thay vì https://localhost chưa được tin cậy.', 'This page is not in a valid secure context for camera access. If you are running locally, use http://localhost or http://127.0.0.1 instead of an untrusted https://localhost.')) ?>"
  data-msg-camera-not-found="<?= e(lang_text('Không tìm thấy máy ảnh trên thiết bị này.', 'No camera device was found on this device.')) ?>"
  data-msg-camera-busy="<?= e(lang_text('Máy ảnh đang được ứng dụng hoặc tab khác sử dụng. Hãy đóng ứng dụng đó rồi thử lại.', 'The camera is being used by another app or tab. Close it and try again.')) ?>"
  data-msg-camera-unsupported="<?= e(lang_text('Trình duyệt không hỗ trợ quét camera. Hãy chuyển sang tải ảnh lên.', 'Browser does not support camera scanning. Use file upload instead.')) ?>"
  data-msg-upload-processing="<?= e(lang_text('Đang xử lý tệp đã tải lên...', 'Processing uploaded file...')) ?>"
  data-msg-upload-client-failed="<?= e(lang_text('Không thể giải mã QR ngay trên trình duyệt, đang chuyển sang dự phòng máy chủ.', 'Cannot decode image QR on browser, switching to server fallback.')) ?>"
  data-msg-upload-server-failed="<?= e(lang_text('Không thể giải mã mã thông báo từ tệp đã tải lên.', 'Cannot decode token from uploaded file.')) ?>"
  data-msg-token-invalid="<?= e(lang_text('Mã thông báo không hợp lệ. Vui lòng kiểm tra lại.', 'Invalid token. Please check again.')) ?>"
  data-msg-result-camera="<?= e(lang_text('Mã thông báo được phát hiện từ máy ảnh.', 'Token detected from camera.')) ?>"
  data-msg-result-upload-client="<?= e(lang_text('Mã thông báo được phát hiện từ hình ảnh trên trình duyệt.', 'Token detected from image on browser.')) ?>"
  data-msg-result-upload-server="<?= e(lang_text('Mã thông báo được phát hiện từ dự phòng tải lên máy chủ.', 'Token detected from server upload fallback.')) ?>"
  data-msg-result-manual="<?= e(lang_text('Mã thông báo được chấp nhận từ đầu vào thủ công.', 'Token accepted from manual input.')) ?>"
  data-msg-toast-valid="<?= e(lang_text('Quét thành công.', 'Scan success.')) ?>"
  data-msg-toast-invalid="<?= e(lang_text('Đã phát hiện QR nhưng mã thông báo không hợp lệ.', 'QR detected but token is invalid.')) ?>"
  data-msg-toast-system="<?= e(lang_text('Không thể xử lý vào lúc này. Vui lòng thử lại.', 'Cannot process at this time. Please retry.')) ?>"
  data-msg-history-empty="<?= e(lang_text('Chưa có mục nào được quét.', 'No scanned item yet.')) ?>"
  data-label-status-valid="<?= e(lang_text('Có hiệu lực', 'Valid')) ?>"
  data-label-status-invalid="<?= e(lang_text('Không hợp lệ', 'Invalid')) ?>"
  data-label-entity-unknown="<?= e(lang_text('Không xác định', 'Unknown')) ?>"
  data-label-open-trace="<?= e(lang_text('Mở truy xuất', 'Open trace')) ?>"
>
  <div class="section-head section-head-inline">
    <p class="section-kicker"><?= e(lang_text('Trung tâm truy xuất', 'Trace center')) ?></p>
    <h1><?= e(t('trace_title')) ?></h1>
  </div>

  <article class="card trace-access-card<?= $traceBlocked ? ' is-blocked' : '' ?>">
    <div class="trace-access-head">
      <div>
        <p class="section-kicker"><?= e(lang_text('Trạng thái truy cập', 'Access status')) ?></p>
        <h2><?= e($tracePlanLabel) ?></h2>
      </div>
      <span class="status-pill"><?= e($traceSummary) ?></span>
    </div>

    <p class="muted"><?= e($traceHint) ?></p>

    <?php if (($traceAccess['daily_limit'] ?? null) !== null): ?>
      <div class="trace-access-metrics">
        <div class="trace-access-metric">
          <strong><?= e((string) ($traceAccess['daily_limit'] ?? 0)) ?></strong>
          <span><?= e(lang_text('lượt mỗi ngày', 'views per day')) ?></span>
        </div>
        <div class="trace-access-metric">
          <strong><?= e((string) ($traceAccess['remaining'] ?? 0)) ?></strong>
          <span><?= e(lang_text('còn lại hôm nay', 'remaining today')) ?></span>
        </div>
        <div class="trace-access-metric">
          <strong><?= e((string) ($traceAccess['used'] ?? 0)) ?></strong>
          <span><?= e(lang_text('đã dùng', 'used')) ?></span>
        </div>
      </div>
    <?php elseif ($traceVipUntilLabel !== ''): ?>
      <p class="muted"><?= e(lang_text('Mốc kích hoạt mở rộng đến: ', 'Extended-access activation until: ')) . e($traceVipUntilLabel) ?></p>
    <?php endif; ?>

    <div class="inline-actions">
      <?php if (!(bool) ($traceAccess['is_logged_in'] ?? false)): ?>
        <a class="btn primary" href="<?= e(app_url('/auth/login')) ?>">
          <?= e(lang_text('Đăng nhập khu vận hành', 'Sign in to the operations workspace')) ?>
        </a>
      <?php else: ?>
        <a class="btn secondary" href="<?= e(app_url('/account')) ?>">
          <?= e(lang_text('Xem trạng thái tài khoản', 'View account status')) ?>
        </a>
      <?php endif; ?>
      <a class="btn secondary" href="<?= e(app_url('/billing')) ?>">
        <?= e(lang_text('Gửi nhu cầu triển khai', 'Submit deployment request')) ?>
      </a>
    </div>
  </article>

  <div class="trace-scan-grid">
    <article class="card trace-scan-card">
      <h2><?= e(lang_text('Quét bằng máy ảnh', 'Scan with camera')) ?></h2>
      <p class="muted"><?= e(lang_text('Ưu tiên camera sau trên điện thoại để nhận diện mã QR ổn định hơn.', 'Use the rear camera on mobile for better QR detection.')) ?></p>

      <div class="trace-camera-shell">
        <video class="trace-camera-preview" data-trace-video playsinline muted autoplay></video>
        <canvas class="trace-overlay-canvas" data-trace-overlay></canvas>
      </div>

      <div class="inline-actions">
        <button type="button" class="btn primary" data-trace-camera-start<?= $traceBlocked ? ' disabled' : '' ?>>
          <?= e(lang_text('Khởi động máy ảnh', 'Start camera')) ?>
        </button>
        <button type="button" class="btn secondary" data-trace-camera-stop disabled>
          <?= e(lang_text('Dừng máy ảnh', 'Stop camera')) ?>
        </button>
      </div>

      <p class="muted trace-status" data-trace-camera-status>
        <?= e(lang_text('Sẵn sàng để quét. Nhấn "Khởi động máy ảnh" để bắt đầu.', 'Ready to scan. Click "Start camera" to begin.')) ?>
      </p>
    </article>

    <article class="card trace-upload-card">
      <h2><?= e(lang_text('Tải lên tệp QR', 'Upload QR file')) ?></h2>
      <p class="muted"><?= e(lang_text('Hỗ trợ ảnh JPG/PNG/WEBP và tệp PDF có chứa mã QR.', 'Supports JPG/PNG/WEBP images and PDF files containing QR code.')) ?></p>

      <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/trace/scan-upload')) ?>" enctype="multipart/form-data" class="form-card" data-trace-upload-form>
        <?= csrf_field() ?>
        <label>
          <?= e(lang_text('Chọn tệp', 'Choose file')) ?>
          <input type="file" name="trace_file" accept="image/*,.pdf" required data-trace-upload-input<?= $traceBlocked ? ' disabled' : '' ?>>
        </label>
        <button type="submit" class="btn secondary"<?= $traceBlocked ? ' disabled' : '' ?>>
          <?= e(lang_text('Phân tích tệp', 'Analyze file')) ?>
        </button>
      </form>

      <p class="muted trace-status" data-trace-upload-status></p>

      <hr class="trace-divider">

      <h3><?= e(lang_text('Nhập mã thủ công', 'Manual token input')) ?></h3>
      <form class="form-card compact" accept-charset="UTF-8" data-trace-manual-form>
        <label>
          <?= e(lang_text('Mã thông báo hoặc URL', 'Token or URL')) ?>
          <input
            type="text"
            placeholder="<?= e(lang_text('Ví dụ: agrisort-lot-001', 'Example: agrisort-lot-001')) ?>"
            data-trace-token-input
            required
            <?= $traceBlocked ? 'disabled' : '' ?>
          >
        </label>
        <button type="submit" class="btn"<?= $traceBlocked ? ' disabled' : '' ?>>
          <?= e(lang_text('Tra cứu mã', 'Lookup token')) ?>
        </button>
      </form>
      <p class="muted trace-status" data-trace-manual-status></p>

      <div class="inline-actions">
        <a class="btn secondary" href="<?= e(app_url('/trace/agrisort-lot-001')) ?>">
          <?= e(lang_text('Xem lô mẫu', 'View sample lot')) ?>
        </a>
      </div>
    </article>
  </div>

  <article class="card trace-result-card is-hidden" data-trace-result-card>
    <p class="section-kicker"><?= e(lang_text('Kết quả quét', 'Scan result')) ?></p>
    <h2><?= e(lang_text('Đã phát hiện mã QR hợp lệ', 'Valid QR token detected')) ?></h2>
    <p><strong><?= e(lang_text('Mã thông báo:', 'Token:')) ?></strong> <span data-trace-result-token>-</span></p>
    <p><strong><?= e(lang_text('Loại thực thể:', 'Entity type:')) ?></strong> <span data-trace-result-entity><?= e(lang_text('Không xác định', 'Unknown')) ?></span></p>
    <p class="muted" data-trace-result-source></p>
    <div class="inline-actions">
      <a class="btn primary" href="#" data-trace-open-link>
        <?= e(lang_text('Mở trang truy xuất', 'Open trace page')) ?>
      </a>
      <button type="button" class="btn secondary" data-trace-reset>
        <?= e(lang_text('Xóa kết quả hiện tại', 'Clear current result')) ?>
      </button>
    </div>
  </article>

  <article class="card trace-history-card">
    <div class="trace-history-head">
      <h2><?= e(lang_text('Lịch sử quét', 'Scan history')) ?></h2>
      <button type="button" class="btn secondary" data-trace-history-clear>
        <?= e(lang_text('Xóa lịch sử', 'Clear history')) ?>
      </button>
    </div>
    <div class="trace-history-table-wrap">
      <table class="table trace-history-table">
        <thead>
          <tr>
            <th><?= e(lang_text('Thực thể', 'Entity')) ?></th>
            <th><?= e(lang_text('Mã thông báo', 'Token')) ?></th>
            <th><?= e(lang_text('Trạng thái', 'Status')) ?></th>
            <th><?= e(lang_text('Lần cuối thấy', 'Last seen')) ?></th>
            <th><?= e(lang_text('Số lần', 'Count')) ?></th>
            <th><?= e(lang_text('Hành động', 'Action')) ?></th>
          </tr>
        </thead>
        <tbody data-trace-history-body>
          <tr data-trace-history-empty>
            <td colspan="6"><?= e(lang_text('Chưa có mục nào được quét.', 'No scanned item yet.')) ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </article>

  <div class="trace-toast-wrap" data-trace-toast-wrap aria-live="polite" aria-atomic="true"></div>
</section>
<?php require BASE_PATH . '/resources/views/layouts/footer.php'; ?>
