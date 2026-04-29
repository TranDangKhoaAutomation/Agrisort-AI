<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class TraceAssignment
{
    public static function upsert(string $entityType, int $entityId, string $stageCode, int $actorUserId, int $assignedByUserId): void
    {
        $sql = 'INSERT INTO trace_assignments
                (entity_type, entity_id, stage_code, actor_user_id, assigned_by_user_id, created_at, updated_at)
                VALUES
                (:entity_type, :entity_id, :stage_code, :actor_user_id, :assigned_by_user_id, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    updated_at = NOW(),
                    assigned_by_user_id = VALUES(assigned_by_user_id)';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'stage_code' => $stageCode,
            'actor_user_id' => $actorUserId,
            'assigned_by_user_id' => $assignedByUserId,
        ]);
    }

    public static function deleteById(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM trace_assignments WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function actorHasStage(string $entityType, int $entityId, string $stageCode, int $actorUserId): bool
    {
        $sql = 'SELECT COUNT(*)
                FROM trace_assignments
                WHERE entity_type = :entity_type
                  AND entity_id = :entity_id
                  AND stage_code = :stage_code
                  AND actor_user_id = :actor_user_id';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'stage_code' => $stageCode,
            'actor_user_id' => $actorUserId,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function listByActor(int $actorUserId): array
    {
        $sql = 'SELECT ta.*,
                       u.full_name AS actor_name,
                       a.full_name AS assigned_by_name,
                       l.lot_code,
                       lp.package_code
                FROM trace_assignments ta
                LEFT JOIN users u ON u.id = ta.actor_user_id
                LEFT JOIN users a ON a.id = ta.assigned_by_user_id
                LEFT JOIN lots l ON ta.entity_type = \'lot\' AND l.id = ta.entity_id
                LEFT JOIN lot_packages lp ON ta.entity_type = \'package\' AND lp.id = ta.entity_id
                WHERE ta.actor_user_id = :actor_user_id
                ORDER BY ta.updated_at DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'actor_user_id' => $actorUserId,
        ]);

        return $stmt->fetchAll();
    }

    public static function allDetailed(): array
    {
        $sql = 'SELECT ta.*,
                       u.full_name AS actor_name,
                       u.email AS actor_email,
                       u.role AS actor_role,
                       a.full_name AS assigned_by_name,
                       l.lot_code,
                       lp.package_code
                FROM trace_assignments ta
                LEFT JOIN users u ON u.id = ta.actor_user_id
                LEFT JOIN users a ON a.id = ta.assigned_by_user_id
                LEFT JOIN lots l ON ta.entity_type = \'lot\' AND l.id = ta.entity_id
                LEFT JOIN lot_packages lp ON ta.entity_type = \'package\' AND lp.id = ta.entity_id
                ORDER BY ta.updated_at DESC';

        return Database::pdo()->query($sql)->fetchAll();
    }
}
