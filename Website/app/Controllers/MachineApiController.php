<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\RateLimiter;
use App\Models\ApiKey;
use App\Models\Lot;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditService;
use App\Services\QrService;

final class MachineApiController extends Controller
{
    public function createLot(array $params = []): void
    {
        $apiKey = trim($this->request->header('X-API-Key', ''));
        if ($apiKey === '') {
            $this->json(['success' => false, 'message' => 'Missing API key.'], 401);
            return;
        }

        if (!RateLimiter::allow('machine_api:' . $this->request->ip(), 60, 60)) {
            $this->json(['success' => false, 'message' => 'Rate limit exceeded.'], 429);
            return;
        }

        $keyRecord = ApiKey::validate($apiKey);
        if (!$keyRecord) {
            $this->json(['success' => false, 'message' => 'Invalid API key.'], 403);
            return;
        }

        $payload = $this->request->json();
        $required = ['partner_ref', 'lot_code', 'produce_type', 'origin_region', 'harvest_date', 'grade1_count', 'grade2_count', 'defect_count', 'image_url'];
        foreach ($required as $field) {
            if (!isset($payload[$field]) || trim((string) $payload[$field]) === '') {
                $this->json(['success' => false, 'message' => 'Missing field: ' . $field], 422);
                return;
            }
        }

        $partnerRef = trim((string) $payload['partner_ref']);
        $partner = User::findByEmail($partnerRef);
        if (!$partner || (string) $partner['role'] !== 'farmer') {
            $this->json(['success' => false, 'message' => 'Invalid partner_ref. Use farmer email.'], 422);
            return;
        }

        $publishStatus = Setting::get('auto_publish_lot', '1') === '1' ? 'published' : 'draft';
        $data = [
            'lot_code' => trim((string) $payload['lot_code']),
            'produce_type' => trim((string) $payload['produce_type']),
            'origin_region' => trim((string) $payload['origin_region']),
            'harvest_date' => trim((string) $payload['harvest_date']),
            'grade1_count' => (int) $payload['grade1_count'],
            'grade2_count' => (int) $payload['grade2_count'],
            'defect_count' => (int) $payload['defect_count'],
            'notes' => trim((string) ($payload['notes'] ?? '')),
            'image_path' => trim((string) ($payload['image_url'] ?? '')),
        ];

        $partnerId = (int) $partner['id'];
        if (Lot::existsByPartnerAndCode($partnerId, $data['lot_code'])) {
            $this->json([
                'success' => false,
                'message' => 'Duplicate lot_code for this partner.',
            ], 409);
            return;
        }

        $token = QrService::token();
        try {
            $lotId = Lot::create($partnerId, $data, $token, $publishStatus);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === Lot::ERR_DUPLICATE_PARTNER_LOT_CODE) {
                $this->json([
                    'success' => false,
                    'message' => 'Duplicate lot_code for this partner.',
                ], 409);
                return;
            }

            throw $e;
        }
        QrService::ensureStoredImage($token, 'png', 8);
        QrService::ensureStoredImage($token, 'svg', 8);
        AuditService::log('machine_create', 'lots', $lotId, ['api_key_id' => $keyRecord['id']]);

        $this->json([
            'success' => true,
            'lot_id' => $lotId,
            'qr_token' => $token,
            'publish_status' => $publishStatus,
            'message' => 'Lot created successfully.',
        ], 201);
    }
}


