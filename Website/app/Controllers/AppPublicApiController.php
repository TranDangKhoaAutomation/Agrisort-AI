<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\BlogPost;
use App\Services\AppApiAuthService;
use App\Services\MembershipService;
use App\Services\TraceLookupService;
use App\Services\TraceTokenService;
use App\Services\TranslateService;

final class AppPublicApiController extends AppApiBaseController
{
    public function blogIndex(array $params = []): void
    {
        $limit = (int) $this->request->input('limit', 20);
        if ($limit <= 0) {
            $limit = 20;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        $rows = BlogPost::published($limit);
        $items = array_map(fn (array $row): array => $this->blogPayload($row), $rows);

        $this->ok('Lay danh sach bai viet thanh cong.', $items, [
            'total' => count($items),
            'limit' => $limit,
        ]);
    }

    public function blogShow(array $params): void
    {
        $slug = trim((string) ($params['slug'] ?? ''));
        if ($slug === '') {
            $this->fail('Slug bai viet khong hop le.', 422);
            return;
        }

        $row = BlogPost::bySlug($slug);
        if (!$row || (string) ($row['status'] ?? '') !== 'published') {
            $this->fail('Khong tim thay bai viet.', 404);
            return;
        }

        $this->ok('Lay chi tiet bai viet thanh cong.', $this->blogPayload($row));
    }

    public function traceLookup(array $params = []): void
    {
        $traceUser = $this->traceApiUser();
        $access = MembershipService::ensureTracePreviewAllowed($traceUser, $this->request->ip());
        if (!$access['allowed']) {
            $this->fail($access['message'], 403);
            return;
        }

        $raw = trim((string) $this->request->input('token', ''));
        $token = TraceTokenService::extractToken($raw);
        if ($token === null) {
            $this->fail('Token truy xuat khong hop le.', 422);
            return;
        }

        $trace = TraceLookupService::findPublicByToken($token);
        if ($trace === null) {
            $this->fail('Khong tim thay du lieu truy xuat.', 404);
            return;
        }

        $access = MembershipService::consumeTraceView($traceUser, $this->request->ip());
        if (!$access['allowed']) {
            $this->fail($access['message'], 403);
            return;
        }

        $this->ok('Tra cuu token thanh cong.', $this->tracePayload($trace));
    }

    public function traceShow(array $params): void
    {
        $traceUser = $this->traceApiUser();
        $access = MembershipService::ensureTracePreviewAllowed($traceUser, $this->request->ip());
        if (!$access['allowed']) {
            $this->fail($access['message'], 403);
            return;
        }

        $token = trim((string) ($params['qr_token'] ?? ''));
        if ($token === '') {
            $this->fail('Ma truy xuat khong hop le.', 422);
            return;
        }

        $trace = TraceLookupService::findPublicByToken($token);
        if ($trace === null) {
            $this->fail('Khong tim thay thong tin truy xuat.', 404);
            return;
        }

        $access = MembershipService::consumeTraceView($traceUser, $this->request->ip());
        if (!$access['allowed']) {
            $this->fail($access['message'], 403);
            return;
        }

        $this->ok('Lay du lieu truy xuat thanh cong.', $this->tracePayload($trace));
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function blogPayload(array $row): array
    {
        $titleVi = (string) ($row['title_vi'] ?? '');
        $excerptVi = (string) ($row['excerpt_vi'] ?? '');
        $contentVi = (string) ($row['content_vi'] ?? '');

        return [
            'id' => (int) ($row['id'] ?? 0),
            'slug' => (string) ($row['slug'] ?? ''),
            'title_vi' => $titleVi,
            'title_en' => $this->englishText($titleVi, (string) ($row['title_en'] ?? '')),
            'excerpt_vi' => $excerptVi,
            'excerpt_en' => $this->englishText($excerptVi, (string) ($row['excerpt_en'] ?? '')),
            'content_vi' => $contentVi,
            'content_en' => $this->englishText($contentVi, (string) ($row['content_en'] ?? '')),
            'thumbnail' => (string) ($row['thumbnail'] ?? ''),
            'thumbnail_url' => $this->fileUrl((string) ($row['thumbnail'] ?? '')),
            'status' => (string) ($row['status'] ?? ''),
            'published_at' => (string) ($row['published_at'] ?? ''),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }

    /**
     * @param array<string,mixed> $trace
     * @return array<string,mixed>
     */
    private function tracePayload(array $trace): array
    {
        $lot = is_array($trace['lot'] ?? null) ? $trace['lot'] : null;
        $package = is_array($trace['package'] ?? null) ? $trace['package'] : null;
        $events = is_array($trace['events'] ?? null) ? $trace['events'] : [];

        if ($lot !== null) {
            $lotToken = (string) ($lot['qr_token'] ?? '');
            $lot['image_url'] = $this->fileUrl((string) ($lot['image_path'] ?? ''));
            $lot['trace_url'] = $this->traceUrlFromToken($lotToken);
            $lotQrExport = $this->qrExportPayload($lotToken);
            $lot['qr_png_url'] = $lotQrExport['png'] ?? null;
            $lot['qr_svg_url'] = $lotQrExport['svg'] ?? null;
            $lot['qr_image_url'] = $this->qrPreferredImageUrl($lotToken);
            $lot['qr_image_format'] = $this->qrPreferredImageFormat($lotToken);
        }

        if ($package !== null) {
            $packageToken = (string) ($package['qr_token'] ?? '');
            $package['trace_url'] = $this->traceUrlFromToken($packageToken);
            $packageQrExport = $this->qrExportPayload($packageToken);
            $package['qr_png_url'] = $packageQrExport['png'] ?? null;
            $package['qr_svg_url'] = $packageQrExport['svg'] ?? null;
            $package['qr_image_url'] = $this->qrPreferredImageUrl($packageToken);
            $package['qr_image_format'] = $this->qrPreferredImageFormat($packageToken);
        }

        $eventPayload = [];
        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }
            $attachments = is_array($event['attachments'] ?? null) ? $event['attachments'] : [];
            foreach ($attachments as &$attachment) {
                if (!is_array($attachment)) {
                    continue;
                }
                $attachment['file_url'] = $this->fileUrl((string) ($attachment['file_path'] ?? ''));
            }
            unset($attachment);

            $event['attachments'] = $attachments;
            $eventPayload[] = $event;
        }

        $entityToken = (string) ($trace['token'] ?? '');
        $entityQrExport = $this->qrExportPayload($entityToken);

        return [
            'entity_type' => (string) ($trace['entity_type'] ?? ''),
            'entity_label' => (string) ($trace['entity_label'] ?? ''),
            'token' => $entityToken,
            'trace_url' => $this->traceUrlFromToken($entityToken),
            'qr_png_url' => $entityQrExport['png'] ?? null,
            'qr_svg_url' => $entityQrExport['svg'] ?? null,
            'qr_image_url' => $this->qrPreferredImageUrl($entityToken),
            'qr_image_format' => $this->qrPreferredImageFormat($entityToken),
            'lot' => $lot,
            'package' => $package,
            'events' => $eventPayload,
        ];
    }

    private function englishText(string $vi, string $fallback = ''): string
    {
        $vi = trim($vi);
        if ($vi === '') {
            return trim($fallback);
        }

        $translated = TranslateService::toEnglish($vi);
        if (
            trim($fallback) !== ''
            && $translated === $vi
            && preg_match('/[^\x00-\x7F]/u', $vi) === 1
        ) {
            return trim($fallback);
        }

        return $translated !== '' ? $translated : (trim($fallback) !== '' ? trim($fallback) : $vi);
    }

    private function traceApiUser(): ?array
    {
        $resolved = AppApiAuthService::resolveUserFromRequest($this->request);
        $user = is_array($resolved['user'] ?? null) ? $resolved['user'] : null;
        if ($user === null) {
            return null;
        }

        return (string) ($user['status'] ?? '') === 'active' ? $user : null;
    }
}
