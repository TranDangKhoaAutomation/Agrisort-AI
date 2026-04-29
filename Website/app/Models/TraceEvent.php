<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class TraceEvent
{
    public static function create(array $data): int
    {
        $sql = 'INSERT INTO trace_events
                (entity_type, entity_id, stage_code, event_time, location_name, note, actor_user_id, created_at, updated_at)
                VALUES
                (:entity_type, :entity_id, :stage_code, :event_time, :location_name, :note, :actor_user_id, NOW(), NOW())';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'entity_type' => (string) $data['entity_type'],
            'entity_id' => (int) $data['entity_id'],
            'stage_code' => (string) $data['stage_code'],
            'event_time' => (string) $data['event_time'],
            'location_name' => (string) $data['location_name'],
            'note' => (string) ($data['note'] ?? ''),
            'actor_user_id' => (int) $data['actor_user_id'],
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function updateById(int $id, array $data): void
    {
        $sql = 'UPDATE trace_events
                SET event_time = :event_time,
                    location_name = :location_name,
                    note = :note,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'event_time' => (string) $data['event_time'],
            'location_name' => (string) $data['location_name'],
            'note' => (string) ($data['note'] ?? ''),
            'id' => $id,
        ]);
    }

    public static function findById(int $id): ?array
    {
        $sql = 'SELECT te.*, u.full_name AS actor_name, u.role AS actor_role
                FROM trace_events te
                LEFT JOIN users u ON u.id = te.actor_user_id
                WHERE te.id = :id
                LIMIT 1';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $row['attachments'] = self::attachmentsByEventId((int) $row['id']);
        return $row;
    }

    public static function listByEntity(string $entityType, int $entityId): array
    {
        $sql = 'SELECT te.*, u.full_name AS actor_name, u.role AS actor_role
                FROM trace_events te
                LEFT JOIN users u ON u.id = te.actor_user_id
                WHERE te.entity_type = :entity_type
                  AND te.entity_id = :entity_id
                ORDER BY te.event_time DESC, te.id DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);
        $rows = $stmt->fetchAll();
        if ($rows === []) {
            return [];
        }

        $ids = array_map(static fn (array $r): int => (int) $r['id'], $rows);
        $attachments = self::attachmentsByEventIds($ids);
        foreach ($rows as &$row) {
            $id = (int) $row['id'];
            $row['attachments'] = $attachments[$id] ?? [];
        }
        unset($row);

        return $rows;
    }

    public static function listByActor(int $actorUserId): array
    {
        $sql = 'SELECT te.*, u.full_name AS actor_name, l.lot_code, lp.package_code
                FROM trace_events te
                LEFT JOIN users u ON u.id = te.actor_user_id
                LEFT JOIN lots l ON te.entity_type = \'lot\' AND l.id = te.entity_id
                LEFT JOIN lot_packages lp ON te.entity_type = \'package\' AND lp.id = te.entity_id
                WHERE te.actor_user_id = :actor_user_id
                ORDER BY te.event_time DESC, te.id DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['actor_user_id' => $actorUserId]);
        $rows = $stmt->fetchAll();
        if ($rows === []) {
            return [];
        }

        $ids = array_map(static fn (array $r): int => (int) $r['id'], $rows);
        $attachments = self::attachmentsByEventIds($ids);
        foreach ($rows as &$row) {
            $id = (int) $row['id'];
            $row['attachments'] = $attachments[$id] ?? [];
        }
        unset($row);

        return $rows;
    }

    public static function listAllDetailed(int $limit = 200): array
    {
        $sql = 'SELECT te.*, u.full_name AS actor_name, u.role AS actor_role,
                       l.lot_code, lp.package_code
                FROM trace_events te
                LEFT JOIN users u ON u.id = te.actor_user_id
                LEFT JOIN lots l ON te.entity_type = \'lot\' AND l.id = te.entity_id
                LEFT JOIN lot_packages lp ON te.entity_type = \'package\' AND lp.id = te.entity_id
                ORDER BY te.event_time DESC, te.id DESC
                LIMIT :limit';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        if ($rows === []) {
            return [];
        }

        $ids = array_map(static fn (array $r): int => (int) $r['id'], $rows);
        $attachments = self::attachmentsByEventIds($ids);
        foreach ($rows as &$row) {
            $id = (int) $row['id'];
            $row['attachments'] = $attachments[$id] ?? [];
        }
        unset($row);

        return $rows;
    }

    public static function addAttachment(int $eventId, array $file): void
    {
        $sql = 'INSERT INTO trace_event_attachments
                (event_id, file_path, original_name, mime_type, file_size, created_at)
                VALUES
                (:event_id, :file_path, :original_name, :mime_type, :file_size, NOW())';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'event_id' => $eventId,
            'file_path' => (string) $file['file_path'],
            'original_name' => (string) $file['original_name'],
            'mime_type' => (string) $file['mime_type'],
            'file_size' => (int) $file['file_size'],
        ]);
    }

    public static function attachmentsByEventId(int $eventId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM trace_event_attachments WHERE event_id = :event_id ORDER BY id DESC'
        );
        $stmt->execute(['event_id' => $eventId]);

        return $stmt->fetchAll();
    }

    /**
     * @param array<int,int> $eventIds
     * @return array<int,array<int,array<string,mixed>>>
     */
    public static function attachmentsByEventIds(array $eventIds): array
    {
        if ($eventIds === []) {
            return [];
        }

        $eventIds = array_values(array_unique(array_map('intval', $eventIds)));
        $placeholders = [];
        $params = [];
        foreach ($eventIds as $index => $eventId) {
            $key = ':id' . $index;
            $placeholders[] = $key;
            $params[$key] = $eventId;
        }

        $sql = 'SELECT * FROM trace_event_attachments
                WHERE event_id IN (' . implode(',', $placeholders) . ')
                ORDER BY id DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $eventId = (int) $row['event_id'];
            if (!isset($grouped[$eventId])) {
                $grouped[$eventId] = [];
            }
            $grouped[$eventId][] = $row;
        }

        return $grouped;
    }
}
