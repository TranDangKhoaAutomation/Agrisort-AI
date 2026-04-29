<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\TraceAssignment;
use App\Models\TraceEvent;
use App\Services\AuditService;
use App\Services\TraceAssignmentService;
use App\Services\TraceAttachmentService;

final class SupplyController extends Controller
{
    private function actorId(): int
    {
        return (int) (current_user()['id'] ?? 0);
    }

    public function dashboard(array $params = []): void
    {
        $actorId = $this->actorId();
        $assignments = TraceAssignment::listByActor($actorId);
        $events = TraceEvent::listByActor($actorId);
        $locale = lang();

        $this->view('supply.dashboard', [
            'assignments' => $assignments,
            'events' => $events,
            'dashboardSection' => load_cms('supply_dashboard', $locale),
        ]);
    }

    public function createEvent(array $params = []): void
    {
        $actorId = $this->actorId();
        $entityType = TraceAssignmentService::normalizeEntityType((string) $this->request->input('entity_type', ''));
        $entityId = (int) $this->request->input('entity_id', 0);
        $stageCode = TraceAssignmentService::normalizeStageCode((string) $this->request->input('stage_code', ''));
        $eventTime = trim((string) $this->request->input('event_time', ''));
        $locationName = trim((string) $this->request->input('location_name', ''));
        $note = trim((string) $this->request->input('note', ''));

        if ($entityType === null || $entityId <= 0 || $eventTime === '' || $locationName === '') {
            flash('error', lang_text('Thiếu trường sự kiện theo dõi.', 'Missing trace event fields.'));
            $this->redirect('/supply/dashboard');
        }

        if (!TraceAssignmentService::canActorWrite($entityType, $entityId, $stageCode, $actorId)) {
            flash('error', lang_text('Bạn không được chỉ định vào giai đoạn này.', 'You are not assigned to this stage.'));
            $this->redirect('/supply/dashboard');
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
            flash('error', $e->getMessage());
            $this->redirect('/supply/dashboard');
        }

        AuditService::log('create', 'trace_events', $eventId, [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'stage_code' => $stageCode,
        ]);
        flash('success', lang_text('Đã thêm sự kiện theo dõi.', 'Trace event added.'));
        $this->redirect('/supply/dashboard');
    }

    public function updateEvent(array $params): void
    {
        $actorId = $this->actorId();
        $eventId = (int) ($params['id'] ?? 0);
        $event = TraceEvent::findById($eventId);
        if (!$event) {
            flash('error', lang_text('Không tìm thấy sự kiện.', 'Event not found.'));
            $this->redirect('/supply/dashboard');
        }

        if ((int) $event['actor_user_id'] !== $actorId) {
            flash('error', lang_text('Bạn không thể chỉnh sửa sự kiện này.', 'You cannot edit this event.'));
            $this->redirect('/supply/dashboard');
        }

        if (!TraceAssignmentService::canActorWrite((string) $event['entity_type'], (int) $event['entity_id'], (string) $event['stage_code'], $actorId)) {
            flash('error', lang_text('Bạn không còn được chỉ định vào giai đoạn này nữa.', 'You are no longer assigned to this stage.'));
            $this->redirect('/supply/dashboard');
        }

        $eventTime = trim((string) $this->request->input('event_time', ''));
        $locationName = trim((string) $this->request->input('location_name', ''));
        $note = trim((string) $this->request->input('note', ''));
        if ($eventTime === '' || $locationName === '') {
            flash('error', lang_text('Thiếu trường cập nhật sự kiện.', 'Missing event update fields.'));
            $this->redirect('/supply/dashboard');
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
            flash('error', $e->getMessage());
            $this->redirect('/supply/dashboard');
        }

        AuditService::log('update', 'trace_events', $eventId, []);
        flash('success', lang_text('Đã cập nhật sự kiện theo dõi.', 'Trace event updated.'));
        $this->redirect('/supply/dashboard');
    }
}
