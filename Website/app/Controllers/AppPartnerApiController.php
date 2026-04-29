<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Validator;
use App\Models\Lot;
use App\Models\LotPackage;
use App\Models\Setting;
use App\Models\TraceAssignment;
use App\Models\TraceEvent;
use App\Services\QrService;
use App\Services\UploadService;

final class AppPartnerApiController extends AppApiBaseController
{
    /**
     * @return array<string,mixed>|null
     */
    private function requirePartnerWorkspaceUser(): ?array
    {
        return $this->requireRole(['partner', 'farmer']);
    }

    public function listLots(array $params = []): void
    {
        $resolved = $this->requirePartnerWorkspaceUser();
        if ($resolved === null) {
            return;
        }

        $partnerId = (int) (($resolved['user']['id'] ?? 0));
        $rows = Lot::listByPartner($partnerId);
        $items = array_map(fn (array $row): array => $this->lotPayload($row), $rows);

        $this->ok('Lấy danh sách lô hàng thành công.', $items, [
            'total' => count($items),
        ]);
    }

    public function createLot(array $params = []): void
    {
        $resolved = $this->requirePartnerWorkspaceUser();
        if ($resolved === null) {
            return;
        }

        $partnerId = (int) (($resolved['user']['id'] ?? 0));
        $payload = $this->payload();

        $data = [
            'lot_code' => trim((string) ($payload['lot_code'] ?? '')),
            'produce_type' => trim((string) ($payload['produce_type'] ?? '')),
            'origin_region' => trim((string) ($payload['origin_region'] ?? '')),
            'harvest_date' => trim((string) ($payload['harvest_date'] ?? '')),
            'grade1_count' => (int) ($payload['grade1_count'] ?? 0),
            'grade2_count' => (int) ($payload['grade2_count'] ?? 0),
            'defect_count' => (int) ($payload['defect_count'] ?? 0),
            'notes' => trim((string) ($payload['notes'] ?? '')),
        ];

        if ($data['lot_code'] === '') {
            try {
                $data['lot_code'] = $this->generateLotCode($partnerId, $data['harvest_date']);
            } catch (\RuntimeException $e) {
                $this->fail('Không thể tự động tạo mã lô. Vui lòng thử lại.', 500);
                return;
            }
        }

        $errors = $this->validateLotPayload($data);
        if ($errors !== []) {
            $this->fail('Dữ liệu lô hàng chưa hợp lệ.', 422, $errors);
            return;
        }

        if (Lot::existsByPartnerAndCode($partnerId, $data['lot_code'])) {
            $this->fail('Mã lô đã tồn tại trong tài khoản của bạn.', 409);
            return;
        }

        try {
            $image = UploadService::image($this->request->file('image'));
            if ($image !== null) {
                $data['image_path'] = $image;
            }
        } catch (\RuntimeException $e) {
            $this->fail($e->getMessage(), 422);
            return;
        }

        if (empty($data['image_path'])) {
            $this->fail('Lô hàng bắt buộc phải có ảnh sản phẩm.', 422, [
                'image' => 'Vui lòng tải lên ảnh sản phẩm cho lô hàng.',
            ]);
            return;
        }

        $publishStatus = Setting::get('auto_publish_lot', '1') === '1' ? 'published' : 'draft';
        $token = QrService::token();

        try {
            $lotId = Lot::create($partnerId, $data, $token, $publishStatus);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === Lot::ERR_DUPLICATE_PARTNER_LOT_CODE) {
                $this->fail('Mã lô đã tồn tại trong tài khoản của bạn.', 409);
                return;
            }
            $this->fail('Có lỗi không xác định.', 500);
            return;
        }

        QrService::ensureStoredImage($token, 'png', 8);
        QrService::ensureStoredImage($token, 'svg', 8);

        try {
            $this->recordLotCreatedTraceEvent($partnerId, $lotId, $data);
        } catch (\Throwable) {
            // Keep API response successful even when trace insert fails.
        }

        $row = Lot::findByPartner($lotId, $partnerId);
        $this->ok('Tạo lô hàng thành công.', [
            'lot' => $row ? $this->lotPayload($row) : ['id' => $lotId],
        ], null, 201);
    }

    public function showLot(array $params): void
    {
        $resolved = $this->requirePartnerWorkspaceUser();
        if ($resolved === null) {
            return;
        }

        $partnerId = (int) (($resolved['user']['id'] ?? 0));
        $lotId = (int) ($params['id'] ?? 0);
        if ($lotId <= 0) {
            $this->fail('ID lô hàng không hợp lệ.', 422);
            return;
        }

        $row = Lot::findByPartner($lotId, $partnerId);
        if (!$row) {
            $this->fail('Không tìm thấy lô hàng.', 404);
            return;
        }

        $this->ok('Lấy chi tiết lô hàng thành công.', [
            'lot' => $this->lotPayload($row),
            'packages' => array_map(
                fn (array $package): array => $this->packagePayload($package),
                LotPackage::listByLotForPartner($partnerId, $lotId)
            ),
            'events' => $this->traceEventPayloads('lot', $lotId),
            'trace_preview' => $this->tracePreviewPayload('lot', (string) ($row['lot_code'] ?? ''), (string) ($row['qr_token'] ?? '')),
        ]);
    }

    public function updateLot(array $params): void
    {
        $resolved = $this->requirePartnerWorkspaceUser();
        if ($resolved === null) {
            return;
        }

        $partnerId = (int) (($resolved['user']['id'] ?? 0));
        $lotId = (int) ($params['id'] ?? 0);
        if ($lotId <= 0) {
            $this->fail('ID lô hàng không hợp lệ.', 422);
            return;
        }

        $existing = Lot::findByPartner($lotId, $partnerId);
        if (!$existing) {
            $this->fail('Không tìm thấy lô hàng.', 404);
            return;
        }

        $payload = $this->payload();
        $data = [
            'lot_code' => trim((string) ($payload['lot_code'] ?? (string) $existing['lot_code'])),
            'produce_type' => trim((string) ($payload['produce_type'] ?? (string) $existing['produce_type'])),
            'origin_region' => trim((string) ($payload['origin_region'] ?? (string) $existing['origin_region'])),
            'harvest_date' => trim((string) ($payload['harvest_date'] ?? (string) $existing['harvest_date'])),
            'grade1_count' => (int) ($payload['grade1_count'] ?? (int) $existing['grade1_count']),
            'grade2_count' => (int) ($payload['grade2_count'] ?? (int) $existing['grade2_count']),
            'defect_count' => (int) ($payload['defect_count'] ?? (int) $existing['defect_count']),
            'notes' => trim((string) ($payload['notes'] ?? (string) ($existing['notes'] ?? ''))),
        ];

        $errors = $this->validateLotPayload($data);
        if ($errors !== []) {
            $this->fail('Dữ liệu cập nhật lô hàng chưa hợp lệ.', 422, $errors);
            return;
        }

        try {
            $image = UploadService::image($this->request->file('image'));
            if ($image !== null) {
                $data['image_path'] = $image;
            }
        } catch (\RuntimeException $e) {
            $this->fail($e->getMessage(), 422);
            return;
        }

        if (empty($data['image_path']) && empty($existing['image_path'])) {
            $this->fail('Lô hàng bắt buộc phải có ảnh sản phẩm.', 422, [
                'image' => 'Lô hàng này chưa có ảnh. Hãy tải lên ảnh trước khi lưu.',
            ]);
            return;
        }

        if (Lot::existsByPartnerAndCode($partnerId, $data['lot_code'], $lotId)) {
            $this->fail('Mã lô đã tồn tại trong tài khoản của bạn.', 409);
            return;
        }

        try {
            Lot::updateByPartner($lotId, $partnerId, $data);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === Lot::ERR_DUPLICATE_PARTNER_LOT_CODE) {
                $this->fail('Mã lô đã tồn tại trong tài khoản của bạn.', 409);
                return;
            }
            $this->fail('Có lỗi không xác định.', 500);
            return;
        }

        $row = Lot::findByPartner($lotId, $partnerId);
        $this->ok('Cập nhật lô hàng thành công.', [
            'lot' => $row ? $this->lotPayload($row) : ['id' => $lotId],
        ]);
    }

    public function deleteLot(array $params): void
    {
        $resolved = $this->requirePartnerWorkspaceUser();
        if ($resolved === null) {
            return;
        }

        $partnerId = (int) (($resolved['user']['id'] ?? 0));
        $lotId = (int) ($params['id'] ?? 0);
        if ($lotId <= 0) {
            $this->fail('ID lô hàng không hợp lệ.', 422);
            return;
        }

        $existing = Lot::findByPartner($lotId, $partnerId);
        if (!$existing) {
            $this->fail('Không tìm thấy lô hàng.', 404);
            return;
        }

        Lot::deleteByPartner($lotId, $partnerId);
        $this->ok('Xóa lô hàng thành công.');
    }

    public function regenerateLotQr(array $params): void
    {
        $resolved = $this->requirePartnerWorkspaceUser();
        if ($resolved === null) {
            return;
        }

        $partnerId = (int) (($resolved['user']['id'] ?? 0));
        $lotId = (int) ($params['id'] ?? 0);
        if ($lotId <= 0) {
            $this->fail('ID lô hàng không hợp lệ.', 422);
            return;
        }

        $row = Lot::findByPartner($lotId, $partnerId);
        if (!$row) {
            $this->fail('Không tìm thấy lô hàng.', 404);
            return;
        }

        $token = QrService::token();
        Lot::regenerateQr($lotId, $partnerId, $token);
        QrService::ensureStoredImage($token, 'png', 8);
        QrService::ensureStoredImage($token, 'svg', 8);

        $updated = Lot::findByPartner($lotId, $partnerId);
        $qrExport = $this->qrExportPayload($token);
        $this->ok('Tạo lại QR lô hàng thành công.', [
            'lot' => $updated ? $this->lotPayload($updated) : [
                'id' => $lotId,
                'qr_token' => $token,
                'trace_url' => $this->traceUrlFromToken($token),
                'qr_png_url' => $qrExport['png'] ?? null,
                'qr_svg_url' => $qrExport['svg'] ?? null,
            ],
        ]);
    }

    public function listPackages(array $params = []): void
    {
        $resolved = $this->requirePartnerWorkspaceUser();
        if ($resolved === null) {
            return;
        }

        $partnerId = (int) (($resolved['user']['id'] ?? 0));
        $rows = LotPackage::listByPartner($partnerId);
        $items = array_map(fn (array $row): array => $this->packagePayload($row), $rows);

        $this->ok('Lấy danh sách kiện hàng thành công.', $items, [
            'total' => count($items),
        ]);
    }

    public function showPackage(array $params): void
    {
        $resolved = $this->requirePartnerWorkspaceUser();
        if ($resolved === null) {
            return;
        }

        $partnerId = (int) (($resolved['user']['id'] ?? 0));
        $packageId = (int) ($params['id'] ?? 0);
        if ($packageId <= 0) {
            $this->fail('ID kien hang khong hop le.', 422);
            return;
        }

        $row = LotPackage::findByPartner($packageId, $partnerId);
        if (!$row) {
            $this->fail('Khong tim thay kien hang.', 404);
            return;
        }

        $lot = Lot::findByPartner((int) ($row['lot_id'] ?? 0), $partnerId);
        $this->ok('Lay chi tiet kien hang thanh cong.', [
            'package' => $this->packagePayload($row),
            'lot' => $lot ? $this->lotPayload($lot) : null,
            'events' => $this->traceEventPayloads('package', $packageId),
            'trace_preview' => $this->tracePreviewPayload('package', (string) ($row['package_code'] ?? ''), (string) ($row['qr_token'] ?? '')),
        ]);
    }

    public function createPackage(array $params = []): void
    {
        $resolved = $this->requirePartnerWorkspaceUser();
        if ($resolved === null) {
            return;
        }

        $partnerId = (int) (($resolved['user']['id'] ?? 0));
        $payload = $this->payload();

        $data = [
            'lot_id' => (int) ($payload['lot_id'] ?? 0),
            'package_code' => trim((string) ($payload['package_code'] ?? '')),
            'package_label' => trim((string) ($payload['package_label'] ?? '')),
            'quantity' => (int) ($payload['quantity'] ?? 1),
            'net_weight_kg' => trim((string) ($payload['net_weight_kg'] ?? '')),
        ];

        $errors = [];
        if ($data['lot_id'] <= 0 || !Lot::belongsToPartner($data['lot_id'], $partnerId)) {
            $errors['lot_id'] = 'Lô hàng được chọn không hợp lệ.';
        }
        if ($data['package_code'] === '') {
            $errors['package_code'] = 'Mã kiện không được để trống.';
        }
        if ($data['quantity'] < 1) {
            $errors['quantity'] = 'Số lượng kiện phải lớn hơn hoặc bằng 1.';
        }

        if ($errors !== []) {
            $this->fail('Dữ liệu kiện hàng chưa hợp lệ.', 422, $errors);
            return;
        }

        $publishStatus = Setting::get('auto_publish_lot', '1') === '1' ? 'published' : 'draft';
        $token = QrService::token();
        try {
            $packageId = LotPackage::create($partnerId, $data, $token, $publishStatus);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->fail('Mã kiện đã tồn tại trong lô hàng này.', 409);
                return;
            }

            $this->fail('Không thể tạo kiện hàng lúc này.', 500);
            return;
        }
        QrService::ensureStoredImage($token, 'png', 8);
        QrService::ensureStoredImage($token, 'svg', 8);

        try {
            $this->recordPackageCreatedTraceEvent($partnerId, $packageId, $data['lot_id']);
        } catch (\Throwable) {
            // Keep API response successful even when trace insert fails.
        }

        $row = LotPackage::findByPartner($packageId, $partnerId);
        $this->ok('Tạo kiện hàng thành công.', [
            'package' => $row ? $this->packagePayload($row) : ['id' => $packageId],
        ], null, 201);
    }

    public function updatePackage(array $params): void
    {
        $resolved = $this->requirePartnerWorkspaceUser();
        if ($resolved === null) {
            return;
        }

        $partnerId = (int) (($resolved['user']['id'] ?? 0));
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            $this->fail('ID kiện hàng không hợp lệ.', 422);
            return;
        }

        $row = LotPackage::findByPartner($id, $partnerId);
        if (!$row) {
            $this->fail('Không tìm thấy kiện hàng.', 404);
            return;
        }

        $payload = $this->payload();
        $update = [
            'package_code' => trim((string) ($payload['package_code'] ?? (string) $row['package_code'])),
            'package_label' => trim((string) ($payload['package_label'] ?? (string) ($row['package_label'] ?? ''))),
            'quantity' => (int) ($payload['quantity'] ?? (int) $row['quantity']),
            'net_weight_kg' => trim((string) ($payload['net_weight_kg'] ?? (string) ($row['net_weight_kg'] ?? ''))),
            'publish_status' => (string) ($payload['publish_status'] ?? (string) $row['publish_status']),
        ];

        if (!in_array($update['publish_status'], ['draft', 'published'], true)) {
            $update['publish_status'] = (string) $row['publish_status'];
        }

        $errors = Validator::required($update, ['package_code']);
        if ($update['quantity'] < 1) {
            $errors['quantity'] = 'Số lượng kiện phải lớn hơn hoặc bằng 1.';
        }

        if ($errors !== []) {
            $this->fail('Dữ liệu cập nhật kiện hàng chưa hợp lệ.', 422, $errors);
            return;
        }

        try {
            LotPackage::updateByPartner($id, $partnerId, $update);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->fail('Mã kiện đã tồn tại trong lô hàng này.', 409);
                return;
            }

            $this->fail('Không thể cập nhật kiện hàng lúc này.', 500);
            return;
        }
        $updated = LotPackage::findByPartner($id, $partnerId);

        $this->ok('Cập nhật kiện hàng thành công.', [
            'package' => $updated ? $this->packagePayload($updated) : ['id' => $id],
        ]);
    }

    public function deletePackage(array $params): void
    {
        $resolved = $this->requirePartnerWorkspaceUser();
        if ($resolved === null) {
            return;
        }

        $partnerId = (int) (($resolved['user']['id'] ?? 0));
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            $this->fail('ID kiện hàng không hợp lệ.', 422);
            return;
        }

        $row = LotPackage::findByPartner($id, $partnerId);
        if (!$row) {
            $this->fail('Không tìm thấy kiện hàng.', 404);
            return;
        }

        LotPackage::deleteByPartner($id, $partnerId);
        $this->ok('Xóa kiện hàng thành công.');
    }

    public function regeneratePackageQr(array $params): void
    {
        $resolved = $this->requirePartnerWorkspaceUser();
        if ($resolved === null) {
            return;
        }

        $partnerId = (int) (($resolved['user']['id'] ?? 0));
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            $this->fail('ID kiện hàng không hợp lệ.', 422);
            return;
        }

        $row = LotPackage::findByPartner($id, $partnerId);
        if (!$row) {
            $this->fail('Không tìm thấy kiện hàng.', 404);
            return;
        }

        $token = QrService::token();
        LotPackage::regenerateQrByPartner($id, $partnerId, $token);
        QrService::ensureStoredImage($token, 'png', 8);
        QrService::ensureStoredImage($token, 'svg', 8);

        $updated = LotPackage::findByPartner($id, $partnerId);
        $qrExport = $this->qrExportPayload($token);
        $this->ok('Tạo lại QR kiện hàng thành công.', [
            'package' => $updated ? $this->packagePayload($updated) : [
                'id' => $id,
                'qr_token' => $token,
                'trace_url' => $this->traceUrlFromToken($token),
                'qr_png_url' => $qrExport['png'] ?? null,
                'qr_svg_url' => $qrExport['svg'] ?? null,
            ],
        ]);
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

        throw new \RuntimeException('Cannot generate lot code.');
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,string>
     */
    private function validateLotPayload(array $data): array
    {
        $errors = Validator::required($data, ['lot_code', 'produce_type', 'origin_region', 'harvest_date']);
        if (!Validator::intValue($data['grade1_count'] ?? 0) || !Validator::intValue($data['grade2_count'] ?? 0) || !Validator::intValue($data['defect_count'] ?? 0)) {
            $errors['grade_count'] = 'Số lượng phân loại phải là số nguyên lớn hơn hoặc bằng 0.';
        }
        if (!$this->isDateYmd((string) ($data['harvest_date'] ?? ''))) {
            $errors['harvest_date'] = 'Ngày thu hoạch phải theo định dạng YYYY-MM-DD.';
        }

        return $errors;
    }

    private function isDateYmd(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }
        $dt = \DateTime::createFromFormat('Y-m-d', $value);
        return $dt instanceof \DateTime && $dt->format('Y-m-d') === $value;
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function lotPayload(array $row): array
    {
        $token = (string) ($row['qr_token'] ?? '');
        $traceUrl = $this->traceUrlFromToken($token);
        $qrExport = $this->qrExportPayload($token);
        return [
            'id' => (int) ($row['id'] ?? 0),
            'partner_id' => (int) ($row['partner_id'] ?? 0),
            'lot_code' => (string) ($row['lot_code'] ?? ''),
            'produce_type' => (string) ($row['produce_type'] ?? ''),
            'origin_region' => (string) ($row['origin_region'] ?? ''),
            'harvest_date' => (string) ($row['harvest_date'] ?? ''),
            'grade1_count' => (int) ($row['grade1_count'] ?? 0),
            'grade2_count' => (int) ($row['grade2_count'] ?? 0),
            'defect_count' => (int) ($row['defect_count'] ?? 0),
            'notes' => (string) ($row['notes'] ?? ''),
            'image_path' => (string) ($row['image_path'] ?? ''),
            'image_url' => $this->fileUrl((string) ($row['image_path'] ?? '')),
            'publish_status' => (string) ($row['publish_status'] ?? ''),
            'qr_token' => $token,
            'trace_url' => $traceUrl,
            'qr_png_url' => $qrExport['png'] ?? null,
            'qr_svg_url' => $qrExport['svg'] ?? null,
            'qr_image_url' => $this->qrPreferredImageUrl($token),
            'qr_image_format' => $this->qrPreferredImageFormat($token),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function packagePayload(array $row): array
    {
        $token = (string) ($row['qr_token'] ?? '');
        $traceUrl = $this->traceUrlFromToken($token);
        $qrExport = $this->qrExportPayload($token);
        return [
            'id' => (int) ($row['id'] ?? 0),
            'lot_id' => (int) ($row['lot_id'] ?? 0),
            'lot_code' => (string) ($row['lot_code'] ?? ''),
            'produce_type' => (string) ($row['produce_type'] ?? ''),
            'package_code' => (string) ($row['package_code'] ?? ''),
            'package_label' => (string) ($row['package_label'] ?? ''),
            'quantity' => (int) ($row['quantity'] ?? 0),
            'net_weight_kg' => (string) ($row['net_weight_kg'] ?? ''),
            'publish_status' => (string) ($row['publish_status'] ?? ''),
            'qr_token' => $token,
            'trace_url' => $traceUrl,
            'qr_png_url' => $qrExport['png'] ?? null,
            'qr_svg_url' => $qrExport['svg'] ?? null,
            'qr_image_url' => $this->qrPreferredImageUrl($token),
            'qr_image_format' => $this->qrPreferredImageFormat($token),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function tracePreviewPayload(string $entityType, string $entityCode, string $token): array
    {
        $labelPrefix = $entityType === 'package' ? 'Kien ' : 'Lo ';
        $qrExport = $this->qrExportPayload($token);

        return [
            'entity_type' => $entityType,
            'entity_label' => trim($labelPrefix . $entityCode),
            'token' => $token,
            'trace_url' => $this->traceUrlFromToken($token),
            'qr_png_url' => $qrExport['png'] ?? null,
            'qr_svg_url' => $qrExport['svg'] ?? null,
            'qr_image_url' => $this->qrPreferredImageUrl($token),
            'qr_image_format' => $this->qrPreferredImageFormat($token),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function traceEventPayloads(string $entityType, int $entityId): array
    {
        $rows = TraceEvent::listByEntity($entityType, $entityId);
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->eventPayload($row);
        }

        return $items;
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function eventPayload(array $row): array
    {
        $attachments = is_array($row['attachments'] ?? null) ? $row['attachments'] : [];
        foreach ($attachments as &$attachment) {
            if (!is_array($attachment)) {
                continue;
            }
            $attachment['file_url'] = $this->fileUrl((string) ($attachment['file_path'] ?? ''));
        }
        unset($attachment);

        $row['attachments'] = $attachments;
        return $row;
    }

    /**
     * @param array<string,mixed> $data
     */
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
            'note' => 'Lot created by partner via app API.',
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
            'note' => 'Package created by partner via app API.',
            'actor_user_id' => $partnerId,
        ]);
    }
}
