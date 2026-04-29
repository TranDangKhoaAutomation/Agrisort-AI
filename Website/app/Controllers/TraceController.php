<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\MembershipService;
use App\Services\TraceDecodeService;
use App\Services\TraceLookupService;
use App\Services\TraceTokenService;

final class TraceController extends Controller
{
    public function index(array $params = []): void
    {
        $this->view('public.trace_scan', [
            'traceAccess' => MembershipService::traceAccessSnapshot(null, $this->request->ip()),
        ]);
    }

    public function scanUpload(array $params = []): void
    {
        $access = MembershipService::ensureTracePreviewAllowed(null, $this->request->ip());
        if (!$access['allowed']) {
            $this->json([
                'success' => false,
                'qr_detected' => false,
                'token_valid' => false,
                'token' => null,
                'message' => $access['message'],
            ], 403);
            return;
        }

        try {
            $decodedText = TraceDecodeService::decodeUploadedFile($this->request->file('trace_file'));
        } catch (\RuntimeException $e) {
            $this->json([
                'success' => false,
                'qr_detected' => false,
                'token_valid' => false,
                'token' => null,
                'message' => $e->getMessage(),
            ], 422);
            return;
        } catch (\Throwable $e) {
            error_log('Trace scan-upload failed: ' . $e::class . ' - ' . $e->getMessage());
            $this->json([
                'success' => false,
                'qr_detected' => false,
                'token_valid' => false,
                'token' => null,
                'message' => lang_text('Không thể xử lý tệp truy xuất vào lúc này.', 'Cannot process trace file at this time.'),
            ], 500);
            return;
        }

        $token = TraceTokenService::extractToken($decodedText);
        if ($token === null) {
            $this->json([
                'success' => false,
                'qr_detected' => true,
                'token_valid' => false,
                'token' => null,
                'message' => lang_text('Đã phát hiện thấy QR nhưng mã thông báo không hợp lệ.', 'QR was detected but token is invalid.'),
            ], 422);
            return;
        }

        $entity = TraceLookupService::detectEntityByToken($token);
        $this->json([
            'success' => true,
            'qr_detected' => true,
            'token_valid' => true,
            'message' => lang_text('Đã phát hiện mã thông báo QR hợp lệ.', 'Valid QR token detected.'),
            'token' => $token,
            'trace_url' => app_url('/trace/' . rawurlencode($token)),
            'entity_type' => (string) $entity['entity_type'],
            'entity_label' => (string) $entity['entity_label'],
        ]);
    }

    public function lookupToken(array $params = []): void
    {
        $access = MembershipService::ensureTracePreviewAllowed(null, $this->request->ip());
        if (!$access['allowed']) {
            $this->json([
                'success' => false,
                'token_valid' => false,
                'token' => null,
                'message' => $access['message'],
            ], 403);
            return;
        }

        $raw = trim((string) $this->request->input('token', ''));
        $token = TraceTokenService::extractToken($raw);

        if ($token === null) {
            $this->json([
                'success' => false,
                'token_valid' => false,
                'token' => null,
                'message' => lang_text('Mã thông báo không hợp lệ.', 'Invalid token.'),
            ], 422);
            return;
        }

        $entity = TraceLookupService::detectEntityByToken($token);
        $this->json([
            'success' => true,
            'token_valid' => true,
            'token' => $token,
            'trace_url' => app_url('/trace/' . rawurlencode($token)),
            'entity_type' => (string) $entity['entity_type'],
            'entity_label' => (string) $entity['entity_label'],
        ]);
    }

    public function show(array $params): void
    {
        $access = MembershipService::ensureTracePreviewAllowed(null, $this->request->ip());
        if (!$access['allowed']) {
            flash('error', $access['message']);
            $this->redirect('/trace');
        }

        $token = (string) ($params['qr_token'] ?? '');
        $trace = TraceLookupService::findPublicByToken($token);

        if (!$trace && $token === 'agrisort-lot-001') {
            $trace = TraceLookupService::findPublicByToken('demo-token');
        }

        if (!$trace) {
            $this->view('public.trace_not_found', ['token' => $token], 404);
            return;
        }

        $access = MembershipService::consumeTraceView(null, $this->request->ip());
        if (!$access['allowed']) {
            flash('error', $access['message']);
            $this->redirect('/trace');
        }

        $this->view('public.trace_show', [
            'traceData' => $trace,
        ]);
    }
}
