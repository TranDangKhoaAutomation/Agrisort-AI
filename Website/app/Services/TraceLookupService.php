<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Lot;
use App\Models\LotPackage;
use App\Models\TraceEvent;

final class TraceLookupService
{
    public static function detectEntityByToken(string $token): array
    {
        try {
            if (LotPackage::findPublicByToken($token)) {
                return [
                    'entity_type' => 'package',
                    'entity_label' => 'Kiện',
                ];
            }
        } catch (\Throwable) {
            // Table may not exist before migration; fallback to lot-only lookup.
        }

        try {
            if (Lot::findPublicByToken($token)) {
                return [
                    'entity_type' => 'lot',
                    'entity_label' => 'Lô',
                ];
            }
        } catch (\Throwable) {
            // Keep unknown if lot lookup fails.
        }

        try {
            if (LotPackage::existsByToken($token)) {
                return [
                    'entity_type' => 'package',
                    'entity_label' => 'Kiện',
                ];
            }
        } catch (\Throwable) {
            // Keep unknown if table lookup fails.
        }

        try {
            if (Lot::existsByToken($token)) {
                return [
                    'entity_type' => 'lot',
                    'entity_label' => 'Lô',
                ];
            }
        } catch (\Throwable) {
            // Keep unknown if lot lookup fails.
        }

        return [
            'entity_type' => 'unknown',
            'entity_label' => 'Unknown',
        ];
    }

    public static function findPublicByToken(string $token): ?array
    {
        try {
            $package = LotPackage::findPublicByToken($token);
        } catch (\Throwable) {
            $package = null;
        }

        if ($package) {
            $lot = Lot::findPublicById((int) $package['lot_id']);
            try {
                $events = TraceEvent::listByEntity('package', (int) $package['id']);
            } catch (\Throwable) {
                $events = [];
            }

            return [
                'entity_type' => 'package',
                'token' => $token,
                'lot' => $lot,
                'package' => $package,
                'events' => $events,
                'entity_label' => 'Kiện ' . (string) ($package['package_code'] ?? ''),
            ];
        }

        try {
            $lot = Lot::findPublicByToken($token);
        } catch (\Throwable) {
            $lot = null;
        }

        if ($lot) {
            try {
                $events = TraceEvent::listByEntity('lot', (int) $lot['id']);
            } catch (\Throwable) {
                $events = [];
            }

            return [
                'entity_type' => 'lot',
                'token' => $token,
                'lot' => $lot,
                'package' => null,
                'events' => $events,
                'entity_label' => 'Lô ' . (string) ($lot['lot_code'] ?? ''),
            ];
        }

        return null;
    }
}
