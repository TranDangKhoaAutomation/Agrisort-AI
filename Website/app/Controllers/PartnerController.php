<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Models\Lot;
use App\Models\LotPackage;
use App\Models\PartnerProfile;
use App\Models\Setting;
use App\Models\TraceAssignment;
use App\Models\TraceEvent;
use App\Services\AuditService;
use App\Services\CsvService;
use App\Services\QrService;
use App\Services\UploadService;

final class PartnerController extends Controller
{
    private function partnerId(): int
    {
        return (int) (current_user()['id'] ?? 0);
    }

    private function generateLotCode(int $partnerId, string $harvestDate): string
    {
        $dateDigits = preg_replace('/\D+/', '', $harvestDate) ?? '';
        if (strlen($dateDigits) !== 8) {
            $dateDigits = date('Ymd');
        }

        for ($i = 0; $i < 20; $i++) {
            $suffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $candidate = 'LO-' . $dateDigits . '-' . $suffix;
            if (!Lot::existsByPartnerAndCode($partnerId, $candidate)) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Unable to auto-generate unique lot code.');
    }

    private function duplicateLotCodeMessage(): string
    {
        return lang_text('Mã lô này đã tồn tại trong tài khoản của bạn. Vui lòng dùng mã khác.', 'Lot code already exists for your account. Please use another code.');
    }

    private function recordLotCreatedTraceEvent(int $partnerId, int $lotId, array $data): void
    {
        TraceAssignment::upsert('lot', $lotId, 'lot-created', $partnerId, $partnerId);

        $location = trim((string) ($data['origin_region'] ?? ''));
        if ($location === '') {
            $location = 'Unknown';
        }

        TraceEvent::create([
            'entity_type' => 'lot',
            'entity_id' => $lotId,
            'stage_code' => 'lot-created',
            'event_time' => date('Y-m-d H:i:s'),
            'location_name' => $location,
            'note' => 'Lot created by partner.',
            'actor_user_id' => $partnerId,
        ]);
    }

    private function recordPackageCreatedTraceEvent(int $partnerId, int $packageId, int $lotId): void
    {
        TraceAssignment::upsert('package', $packageId, 'package-created', $partnerId, $partnerId);

        $location = 'Packaging';
        $lot = Lot::findByPartner($lotId, $partnerId);
        if ($lot) {
            $origin = trim((string) ($lot['origin_region'] ?? ''));
            if ($origin !== '') {
                $location = $origin;
            }
        }

        TraceEvent::create([
            'entity_type' => 'package',
            'entity_id' => $packageId,
            'stage_code' => 'package-created',
            'event_time' => date('Y-m-d H:i:s'),
            'location_name' => $location,
            'note' => 'Package created by partner.',
            'actor_user_id' => $partnerId,
        ]);
    }

    public function dashboard(array $params = []): void
    {
        $partnerId = $this->partnerId();
        $lots = Lot::listByPartner($partnerId);
        $profile = PartnerProfile::byUserId($partnerId);
        $locale = lang();

        $this->view('partner.dashboard', [
            'lots' => $lots,
            'profile' => $profile,
            'autoPublish' => Setting::get('auto_publish_lot', '1') === '1',
            'dashboardSection' => load_cms('partner_dashboard', $locale),
        ]);
    }

    public function lots(array $params = []): void
    {
        $this->dashboard($params);
    }

    public function qrPage(array $params = []): void
    {
        $partnerId = $this->partnerId();
        $lots = Lot::listByPartner($partnerId);
        $packages = LotPackage::listByPartner($partnerId);

        $this->view('partner.qr', [
            'lots' => $lots,
            'packages' => $packages,
            'autoPublish' => Setting::get('auto_publish_lot', '1') === '1',
        ]);
    }

    public function createLot(array $params = []): void
    {
        $partnerId = $this->partnerId();
        $data = [
            'lot_code' => '',
            'produce_type' => trim((string) $this->request->input('produce_type', '')),
            'origin_region' => trim((string) $this->request->input('origin_region', '')),
            'harvest_date' => trim((string) $this->request->input('harvest_date', '')),
            'grade1_count' => $this->request->input('grade1_count', 0),
            'grade2_count' => $this->request->input('grade2_count', 0),
            'defect_count' => $this->request->input('defect_count', 0),
            'notes' => trim((string) $this->request->input('notes', '')),
        ];

        $errors = Validator::required($data, ['produce_type', 'origin_region', 'harvest_date']);
        if (!Validator::intValue($data['grade1_count']) || !Validator::intValue($data['grade2_count']) || !Validator::intValue($data['defect_count'])) {
            $errors['grade'] = lang_text('Số điểm phải là số nguyên >= 0.', 'Grade counts must be integer >= 0.');
        }

        if ($errors) {
            flash('error', lang_text('Thông tin lô hàng không hợp lệ.', 'Invalid lot payload.'));
            $this->redirect('/partner/dashboard');
        }

        try {
            $data['lot_code'] = $this->generateLotCode($partnerId, (string) $data['harvest_date']);
        } catch (\RuntimeException $e) {
            flash('error', lang_text('Không thể tự động tạo mã lô. ', 'Unable to auto-generate lot code. Please try again.'));
            $this->redirect('/partner/dashboard');
        }

        try {
            $image = UploadService::image($this->request->file('image'));
            if ($image !== null) {
                $data['image_path'] = $image;
            }
        } catch (\RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->redirect('/partner/dashboard');
        }

        if (empty($data['image_path'])) {
            flash('error', lang_text('Vui lòng tải lên ảnh sản phẩm cho lô hàng này.', 'Please upload a product image for this lot.'));
            $this->redirect('/partner/dashboard');
        }

        $publishStatus = Setting::get('auto_publish_lot', '1') === '1' ? 'published' : 'draft';
        $lotToken = QrService::token();
        $lotId = 0;
        for ($attempt = 0; $attempt < 8; $attempt++) {
            try {
                $lotId = Lot::create($partnerId, $data, $lotToken, $publishStatus);
                break;
            } catch (\RuntimeException $e) {
                if ($e->getMessage() !== Lot::ERR_DUPLICATE_PARTNER_LOT_CODE) {
                    throw $e;
                }

                if ($attempt === 7) {
                    flash('error', lang_text('Không thể tạo mã lô duy nhất. ', 'Unable to create a unique lot code. Please try again.'));
                    $this->redirect('/partner/dashboard');
                }

                try {
                    $data['lot_code'] = $this->generateLotCode($partnerId, (string) $data['harvest_date']);
                } catch (\RuntimeException $inner) {
                    flash('error', lang_text('Không thể tự động tạo mã lô. ', 'Unable to auto-generate lot code. Please try again.'));
                    $this->redirect('/partner/dashboard');
                }
            }
        }

        if ($lotId <= 0) {
            flash('error', lang_text('Không thể tạo lô hàng. Vui lòng thử lại.', 'Unable to create lot. Please try again.'));
            $this->redirect('/partner/dashboard');
        }
        QrService::ensureStoredImage($lotToken, 'png', 8);
        QrService::ensureStoredImage($lotToken, 'svg', 8);

        try {
            $this->recordLotCreatedTraceEvent($partnerId, $lotId, $data);
        } catch (\Throwable) {
            // Keep lot creation successful even if trace event insert fails.
        }

        AuditService::log('create', 'lots', $lotId, ['partner_id' => $partnerId]);
        flash('success', lang_text('Tạo lô hàng thành công. Mã lô: ' . $data['lot_code'] . '.', 'Lot created successfully. Lot code: ' . $data['lot_code'] . '.'));
        $this->redirect('/partner/dashboard');
    }

    public function updateLot(array $params): void
    {
        $lotId = (int) ($params['id'] ?? 0);
        if ($lotId <= 0) {
            $this->redirect('/partner/dashboard');
        }
        $partnerId = $this->partnerId();
        $existing = Lot::findByPartner($lotId, $partnerId);
        if (!$existing) {
            flash('error', lang_text('Không tìm thấy lô hàng.', 'Lot not found.'));
            $this->redirect('/partner/dashboard');
        }

        $data = [
            'lot_code' => trim((string) $this->request->input('lot_code', '')),
            'produce_type' => trim((string) $this->request->input('produce_type', '')),
            'origin_region' => trim((string) $this->request->input('origin_region', '')),
            'harvest_date' => trim((string) $this->request->input('harvest_date', '')),
            'grade1_count' => $this->request->input('grade1_count', 0),
            'grade2_count' => $this->request->input('grade2_count', 0),
            'defect_count' => $this->request->input('defect_count', 0),
            'notes' => trim((string) $this->request->input('notes', '')),
        ];

        try {
            $image = UploadService::image($this->request->file('image'));
            if ($image !== null) {
                $data['image_path'] = $image;
            }
        } catch (\RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->redirect('/partner/dashboard');
        }

        if (empty($data['image_path']) && empty($existing['image_path'])) {
            flash('error', lang_text('Lô hàng này chưa có ảnh. Vui lòng tải lên ảnh sản phẩm trước khi lưu.', 'This lot has no image yet. Please upload a product image before saving.'));
            $this->redirect('/partner/dashboard');
        }

        if (Lot::existsByPartnerAndCode($partnerId, $data['lot_code'], $lotId)) {
            flash('error', $this->duplicateLotCodeMessage());
            $this->redirect('/partner/dashboard');
        }

        try {
            Lot::updateByPartner($lotId, $partnerId, $data);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === Lot::ERR_DUPLICATE_PARTNER_LOT_CODE) {
                flash('error', $this->duplicateLotCodeMessage());
                $this->redirect('/partner/dashboard');
            }

            throw $e;
        }

        AuditService::log('update', 'lots', $lotId, ['partner_id' => $partnerId]);

        flash('success', lang_text('Cập nhật lô hàng thành công.', 'Lot updated successfully.'));
        $this->redirect('/partner/dashboard');
    }

    public function deleteLot(array $params): void
    {
        $lotId = (int) ($params['id'] ?? 0);
        Lot::deleteByPartner($lotId, $this->partnerId());
        AuditService::log('delete', 'lots', $lotId, ['partner_id' => $this->partnerId()]);

        flash('success', lang_text('Đã xóa rất nhiều.', 'Lot deleted.'));
        $this->redirect('/partner/dashboard');
    }

    public function regenerateQr(array $params): void
    {
        $lotId = (int) ($params['id'] ?? 0);
        $token = QrService::token();
        Lot::regenerateQr($lotId, $this->partnerId(), $token);
        QrService::ensureStoredImage($token, 'png', 8);
        QrService::ensureStoredImage($token, 'svg', 8);

        AuditService::log('regenerate_qr', 'lots', $lotId, ['partner_id' => $this->partnerId()]);
        flash('success', lang_text('Mã thông báo QR đã được tạo lại.', 'QR token regenerated.'));
        $this->redirect('/partner/dashboard');
    }

    public function createPackage(array $params = []): void
    {
        $partnerId = $this->partnerId();
        $lotId = (int) $this->request->input('lot_id', 0);
        $data = [
            'lot_id' => $lotId,
            'package_code' => trim((string) $this->request->input('package_code', '')),
            'package_label' => trim((string) $this->request->input('package_label', '')),
            'quantity' => (int) $this->request->input('quantity', 1),
            'net_weight_kg' => trim((string) $this->request->input('net_weight_kg', '')),
        ];

        if ($lotId <= 0 || !Lot::belongsToPartner($lotId, $partnerId)) {
            flash('error', lang_text('Lô đã chọn không hợp lệ.', 'Selected lot is invalid.'));
            $this->redirect('/partner/qr');
        }

        $errors = Validator::required($data, ['package_code']);
        if ($data['quantity'] < 1) {
            $errors['quantity'] = lang_text('Số lượng gói hàng phải >= 1.', 'Package quantity must be >= 1.');
        }

        if ($errors) {
            flash('error', lang_text('Tải trọng gói không hợp lệ.', 'Invalid package payload.'));
            $this->redirect('/partner/qr');
        }

        $publishStatus = Setting::get('auto_publish_lot', '1') === '1' ? 'published' : 'draft';
        $token = QrService::token();
        $packageId = LotPackage::create($partnerId, $data, $token, $publishStatus);
        QrService::ensureStoredImage($token, 'png', 8);
        QrService::ensureStoredImage($token, 'svg', 8);

        try {
            $this->recordPackageCreatedTraceEvent($partnerId, $packageId, $lotId);
        } catch (\Throwable) {
            // Keep package creation successful even if trace event insert fails.
        }

        AuditService::log('create', 'lot_packages', $packageId, ['partner_id' => $partnerId, 'lot_id' => $lotId]);

        flash('success', lang_text('Đã tạo gói và mã thông báo QR.', 'Package and QR token created.'));
        $this->redirect('/partner/qr');
    }

    public function updatePackage(array $params): void
    {
        $partnerId = $this->partnerId();
        $id = (int) ($params['id'] ?? 0);

        $payload = [
            'package_code' => trim((string) $this->request->input('package_code', '')),
            'package_label' => trim((string) $this->request->input('package_label', '')),
            'quantity' => (int) $this->request->input('quantity', 1),
            'net_weight_kg' => trim((string) $this->request->input('net_weight_kg', '')),
            'publish_status' => (string) $this->request->input('publish_status', 'draft'),
        ];
        if (!in_array($payload['publish_status'], ['draft', 'published'], true)) {
            $payload['publish_status'] = 'draft';
        }

        $row = LotPackage::findByPartner($id, $partnerId);
        if (!$row) {
            flash('error', lang_text('Không tìm thấy gói.', 'Package not found.'));
            $this->redirect('/partner/qr');
        }

        LotPackage::updateByPartner($id, $partnerId, $payload);
        AuditService::log('update', 'lot_packages', $id, ['partner_id' => $partnerId]);
        flash('success', lang_text('Đã cập nhật gói.', 'Package updated.'));
        $this->redirect('/partner/qr');
    }

    public function deletePackage(array $params): void
    {
        $partnerId = $this->partnerId();
        $id = (int) ($params['id'] ?? 0);
        LotPackage::deleteByPartner($id, $partnerId);
        AuditService::log('delete', 'lot_packages', $id, ['partner_id' => $partnerId]);

        flash('success', lang_text('Đã xóa gói.', 'Package deleted.'));
        $this->redirect('/partner/qr');
    }

    public function regeneratePackageQr(array $params): void
    {
        $partnerId = $this->partnerId();
        $id = (int) ($params['id'] ?? 0);
        $token = QrService::token();
        LotPackage::regenerateQrByPartner($id, $partnerId, $token);
        QrService::ensureStoredImage($token, 'png', 8);
        QrService::ensureStoredImage($token, 'svg', 8);
        AuditService::log('regenerate_qr', 'lot_packages', $id, ['partner_id' => $partnerId]);

        flash('success', lang_text('Đã tạo lại mã thông báo QR gói.', 'Package QR token regenerated.'));
        $this->redirect('/partner/qr');
    }

    public function importCsv(array $params = []): void
    {
        $partnerId = $this->partnerId();
        $file = $this->request->file('csv_file');
        if (!$file || (int) $file['error'] !== UPLOAD_ERR_OK) {
            flash('error', lang_text('Tệp CSV bị thiếu hoặc không hợp lệ.', 'CSV file missing or invalid.'));
            $this->redirect('/partner/dashboard');
        }

        try {
            $defaultImage = UploadService::image($this->request->file('default_image'));
        } catch (\RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->redirect('/partner/dashboard');
        }

        if ($defaultImage === null) {
            flash('error', lang_text('Nhập CSV bắt buộc phải có một ảnh sản phẩm mặc định.', 'CSV import requires a default product image.'));
            $this->redirect('/partner/dashboard');
        }

        $tmp = BASE_PATH . '/storage/uploads/csv_' . bin2hex(random_bytes(8)) . '.csv';
        if (!move_uploaded_file((string) $file['tmp_name'], $tmp)) {
            flash('error', lang_text('Không thể tải lên tệp CSV.', 'Cannot upload CSV file.'));
            $this->redirect('/partner/dashboard');
        }

        $result = CsvService::parse($tmp);
        $errors = $result['errors'];
        $rows = $result['rows'];

        $errorLines = [];
        foreach ($errors as $err) {
            if (isset($err['line'])) {
                $errorLines[(int) $err['line']] = true;
            }
        }

        $successRows = 0;
        $duplicateRows = 0;
        $publishStatus = Setting::get('auto_publish_lot', '1') === '1' ? 'published' : 'draft';

        foreach ($rows as $row) {
            $lineNo = (int) ($row['__line'] ?? 0);
            if ($lineNo > 0 && isset($errorLines[$lineNo])) {
                continue;
            }

            $lotPayload = [
                'lot_code' => trim((string) $row['lot_code']),
                'produce_type' => trim((string) $row['produce_type']),
                'origin_region' => trim((string) $row['origin_region']),
                'harvest_date' => trim((string) $row['harvest_date']),
                'grade1_count' => (int) $row['grade1_count'],
                'grade2_count' => (int) $row['grade2_count'],
                'defect_count' => (int) $row['defect_count'],
                'notes' => trim((string) ($row['notes'] ?? '')),
                'image_path' => $defaultImage,
            ];

            if (Lot::existsByPartnerAndCode($partnerId, $lotPayload['lot_code'])) {
                $duplicateRows++;
                continue;
            }

            try {
                $lotId = Lot::create(
                    $partnerId,
                    $lotPayload,
                    QrService::token(),
                    $publishStatus
                );
            } catch (\RuntimeException $e) {
                if ($e->getMessage() === Lot::ERR_DUPLICATE_PARTNER_LOT_CODE) {
                    $duplicateRows++;
                    continue;
                }

                throw $e;
            }

            try {
                $this->recordLotCreatedTraceEvent($partnerId, $lotId, $lotPayload);
            } catch (\Throwable) {
                // Keep CSV import successful even if trace event insert fails.
            }

            $successRows++;
        }
        $failRows = count($errors) + $duplicateRows;

        $jobStmt = Database::pdo()->prepare(
            'INSERT INTO lot_import_jobs (partner_id, file_path, total_rows, success_rows, fail_rows, created_at)
             VALUES (:partner_id, :file_path, :total_rows, :success_rows, :fail_rows, NOW())'
        );
        $jobStmt->execute([
            'partner_id' => $partnerId,
            'file_path' => str_replace(BASE_PATH . '/', '', $tmp),
            'total_rows' => count($rows),
            'success_rows' => $successRows,
            'fail_rows' => $failRows,
        ]);

        AuditService::log('import_csv', 'lot_import_jobs', null, ['success_rows' => $successRows, 'fail_rows' => $failRows]);

        if ($failRows > 0) {
            flash('error', lang_text(
                'Nhập CSV. Thành công:' . $successRows . ', lỗi:' . $failRows . '.',
                'CSV import done. Success: ' . $successRows . ', errors: ' . $failRows . '.'
            ));
        } else {
            flash('success', lang_text(
                'Nhập CSV thành công. Sử dụng:' . $successRows . '.',
                'CSV import success. Rows: ' . $successRows . '.'
            ));
        }

        $this->redirect('/partner/dashboard');
    }
}
