<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Lot;
use App\Models\LotPackage;
use App\Models\TraceAssignment;
use App\Models\TraceEvent;

final class TraceBootstrapService
{
    public static function backfillCreationEvents(int $fallbackActorUserId = 1): int
    {
        $inserted = 0;
        $now = date('Y-m-d H:i:s');
        $fallbackActorUserId = $fallbackActorUserId > 0 ? $fallbackActorUserId : 1;

        $lots = Lot::allWithPartner();
        foreach ($lots as $lot) {
            $actorId = (int) ($lot['partner_id'] ?? 0);
            if ($actorId <= 0) {
                $actorId = $fallbackActorUserId;
            }

            TraceAssignment::upsert(
                'lot',
                (int) ($lot['id'] ?? 0),
                'lot-created',
                $actorId,
                $fallbackActorUserId
            );

            $eventTime = trim((string) ($lot['created_at'] ?? ''));
            if ($eventTime === '') {
                $eventTime = $now;
            }

            $location = trim((string) ($lot['origin_region'] ?? ''));
            if ($location === '') {
                $location = 'Unknown';
            }

            $lotId = (int) ($lot['id'] ?? 0);
            if (!self::eventExists('lot', $lotId, 'lot-created')) {
                TraceEvent::create([
                    'entity_type' => 'lot',
                    'entity_id' => $lotId,
                    'stage_code' => 'lot-created',
                    'event_time' => $eventTime,
                    'location_name' => $location,
                    'note' => 'Initial lot creation event (auto backfill).',
                    'actor_user_id' => $actorId,
                ]);
                $inserted++;
            }
        }

        $packages = LotPackage::allWithLot();
        foreach ($packages as $package) {
            $actorId = (int) ($package['partner_id'] ?? 0);
            if ($actorId <= 0) {
                $actorId = $fallbackActorUserId;
            }

            TraceAssignment::upsert(
                'package',
                (int) ($package['id'] ?? 0),
                'package-created',
                $actorId,
                $fallbackActorUserId
            );

            $eventTime = trim((string) ($package['created_at'] ?? ''));
            if ($eventTime === '') {
                $eventTime = $now;
            }

            $location = trim((string) ($package['origin_region'] ?? ''));
            if ($location === '') {
                $location = 'Packaging';
            }

            $packageId = (int) ($package['id'] ?? 0);
            if (!self::eventExists('package', $packageId, 'package-created')) {
                TraceEvent::create([
                    'entity_type' => 'package',
                    'entity_id' => $packageId,
                    'stage_code' => 'package-created',
                    'event_time' => $eventTime,
                    'location_name' => $location,
                    'note' => 'Initial package creation event (auto backfill).',
                    'actor_user_id' => $actorId,
                ]);
                $inserted++;
            }
        }

        return $inserted;
    }

    private static function eventExists(string $entityType, int $entityId, string $stageCode): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM trace_events WHERE entity_type = :entity_type AND entity_id = :entity_id AND stage_code = :stage_code'
        );
        $stmt->execute([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'stage_code' => $stageCode,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
