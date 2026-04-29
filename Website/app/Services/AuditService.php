<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

final class AuditService
{
    public static function log(string $action, string $entity, ?int $entityId = null, array $meta = []): void
    {
        $user = Auth::user();
        $stmt = Database::pdo()->prepare(
            'INSERT INTO audit_logs (actor_user_id, action, entity, entity_id, meta_json, created_at)
             VALUES (:actor_user_id, :action, :entity, :entity_id, :meta_json, NOW())'
        );
        $stmt->execute([
            'actor_user_id' => $user['id'] ?? null,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'meta_json' => json_encode($meta, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
