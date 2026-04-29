<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\TraceAssignment;
use App\Models\TraceEvent;
use App\Services\TraceAssignmentService;
use App\Services\TraceAttachmentService;

final class AppSupplyApiController extends AppApiBaseController
{
    public function listAssignments(array $params = []): void
    {
        $resolved = $this->requireRole(['transporter', 'warehouse', 'seller']);
        if ($resolved === null) {
            return;
        }

        $actorId = (int) (($resolved['user']['id'] ?? 0));
        $rows = TraceAssignment::listByActor($actorId);
        $this->ok('Lấy danh sách phân công thành công.', $rows, [
            'total' => count($rows),
        ]);
    }

    public function listEvents(array $params = []): void
    {
        $resolved = $this->requireRole(['transporter', 'warehouse', 'seller']);
        if ($resolved === null) {
            return;
        }

        $actorId = (int) (($resolved['user']['id'] ?? 0));
        $rows = TraceEvent::listByActor($actorId);
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->eventPayload($row);
        }

        $this->ok('Lấy danh sách sự kiện hành trình thành công.', $items, [
            'total' => count($items),
        ]);
    }

    public function createEvent(array $params = []): void
    {
        $resolved = $this->requireRole(['transporter', 'warehouse', 'seller']);
        if ($resolved === null) {
            return;
        }

        $actorId = (int) (($resolved['user']['id'] ?? 0));
        $payload = $this->payload();

        $entityType = TraceAssignmentService::normalizeEntityType((string) ($payload['entity_type'] ?? ''));
        $entityId = (int) ($payload['entity_id'] ?? 0);
        $stageCode = TraceAssignmentService::normalizeStageCode((string) ($payload['stage_code'] ?? ''));
        $eventTime = trim((string) ($payload['event_time'] ?? date('Y-m-d H:i:s')));
        $locationName = trim((string) ($payload['location_name'] ?? ''));
        $note = trim((string) ($payload['note'] ?? ''));

        $errors = [];
        if ($entityType === null) {
            $errors['entity_type'] = 'Loại đối tượng chỉ hỗ trợ lot hoặc package.';
        }
        if ($entityId <= 0) {
            $errors['entity_id'] = 'ID đối tượng phải lớn hơn 0.';
        }
        if ($locationName === '') {
            $errors['location_name'] = 'Vị trí cập nhật không được để trống.';
        }
        if (!$this->isDateTime($eventTime)) {
            $errors['event_time'] = 'Thời gian sự kiện phải theo định dạng YYYY-MM-DD HH:MM:SS.';
        }

        if ($errors !== []) {
            $this->fail('Dữ liệu sự kiện hành trình chưa hợp lệ.', 422, $errors);
            return;
        }

        if (!TraceAssignmentService::canActorWrite((string) $entityType, $entityId, $stageCode, $actorId)) {
            $this->fail('Bạn không được phân công cập nhật chặng này.', 403);
            return;
        }

        $eventId = TraceEvent::create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'stage_code' => $stageCode,
            'event_time' => $eventTime,
            'location_name' => $locationName,
            'note' => $note,
            'actor_user_id' => $actorId,
        ]);

        try {
            $files = TraceAttachmentService::storeUploadedFiles($this->request->file('attachments'));
            foreach ($files as $file) {
                TraceEvent::addAttachment($eventId, $file);
            }
        } catch (\RuntimeException $e) {
            $this->fail($e->getMessage(), 422);
            return;
        }

        $event = TraceEvent::findById($eventId);
        $this->ok('Tạo sự kiện hành trình thành công.', [
            'event' => $event ? $this->eventPayload($event) : ['id' => $eventId],
        ], null, 201);
    }

    public function updateEvent(array $params): void
    {
        $resolved = $this->requireRole(['transporter', 'warehouse', 'seller']);
        if ($resolved === null) {
            return;
        }

        $actorId = (int) (($resolved['user']['id'] ?? 0));
        $eventId = (int) ($params['id'] ?? 0);
        if ($eventId <= 0) {
            $this->fail('ID sự kiện không hợp lệ.', 422);
            return;
        }

        $event = TraceEvent::findById($eventId);
        if (!$event) {
            $this->fail('Không tìm thấy sự kiện hành trình.', 404);
            return;
        }

        if ((int) ($event['actor_user_id'] ?? 0) !== $actorId) {
            $this->fail('Bạn không có quyền sửa sự kiện này.', 403);
            return;
        }

        if (!TraceAssignmentService::canActorWrite((string) $event['entity_type'], (int) $event['entity_id'], (string) $event['stage_code'], $actorId)) {
            $this->fail('Bạn không còn quyền cập nhật sự kiện này.', 403);
            return;
        }

        $payload = $this->payload();
        $eventTime = trim((string) ($payload['event_time'] ?? (string) $event['event_time']));
        $locationName = trim((string) ($payload['location_name'] ?? (string) $event['location_name']));
        $note = trim((string) ($payload['note'] ?? (string) ($event['note'] ?? '')));

        $errors = [];
        if ($locationName === '') {
            $errors['location_name'] = 'Vị trí cập nhật không được để trống.';
        }
        if (!$this->isDateTime($eventTime)) {
            $errors['event_time'] = 'Thời gian sự kiện phải theo định dạng YYYY-MM-DD HH:MM:SS.';
        }
        if ($errors !== []) {
            $this->fail('Dữ liệu cập nhật sự kiện chưa hợp lệ.', 422, $errors);
            return;
        }

        TraceEvent::updateById($eventId, [
            'event_time' => $eventTime,
            'location_name' => $locationName,
            'note' => $note,
        ]);

        try {
            $files = TraceAttachmentService::storeUploadedFiles($this->request->file('attachments'));
            foreach ($files as $file) {
                TraceEvent::addAttachment($eventId, $file);
            }
        } catch (\RuntimeException $e) {
            $this->fail($e->getMessage(), 422);
            return;
        }

        $updated = TraceEvent::findById($eventId);
        $this->ok('Cập nhật sự kiện hành trình thành công.', [
            'event' => $updated ? $this->eventPayload($updated) : ['id' => $eventId],
        ]);
    }

    private function isDateTime(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
        return $dt instanceof \DateTime && $dt->format('Y-m-d H:i:s') === $value;
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
}
