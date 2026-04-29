<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Lot;
use App\Models\LotPackage;
use App\Models\TraceAssignment;
use App\Models\User;

final class TraceAssignmentService
{
    public static function normalizeEntityType(string $entityType): ?string
    {
        $entityType = strtolower(trim($entityType));
        if (!in_array($entityType, ['lot', 'package'], true)) {
            return null;
        }

        return $entityType;
    }

    public static function normalizeStageCode(string $value): string
    {
        $value = trim(strtolower($value));
        $value = preg_replace('/[^a-z0-9._-]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        if ($value === '') {
            return 'unassigned-stage';
        }

        return substr($value, 0, 60);
    }

    public static function entityExists(string $entityType, int $entityId): bool
    {
        if ($entityType === 'lot') {
            return Lot::findById($entityId) !== null;
        }

        if ($entityType === 'package') {
            return LotPackage::findById($entityId) !== null;
        }

        return false;
    }

    public static function canActorWrite(string $entityType, int $entityId, string $stageCode, int $actorUserId): bool
    {
        if ($actorUserId <= 0 || $entityId <= 0) {
            return false;
        }

        $actor = User::findById($actorUserId);
        if (!$actor || (string) $actor['status'] !== 'active') {
            return false;
        }

        $role = (string) ($actor['role'] ?? '');
        if ($role === 'admin') {
            return true;
        }

        if (!in_array($role, ['transporter', 'warehouse', 'seller'], true)) {
            return false;
        }

        return TraceAssignment::actorHasStage($entityType, $entityId, $stageCode, $actorUserId);
    }
}
