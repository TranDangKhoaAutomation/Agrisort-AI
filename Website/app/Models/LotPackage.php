<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class LotPackage
{
    public static function create(int $partnerId, array $data, string $qrToken, string $publishStatus): int
    {
        $sql = 'INSERT INTO lot_packages
                (lot_id, package_code, package_label, quantity, net_weight_kg, qr_token, publish_status, created_by_user_id, created_at, updated_at)
                VALUES
                (:lot_id, :package_code, :package_label, :quantity, :net_weight_kg, :qr_token, :publish_status, :created_by_user_id, NOW(), NOW())';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'lot_id' => (int) $data['lot_id'],
            'package_code' => (string) $data['package_code'],
            'package_label' => $data['package_label'] !== '' ? (string) $data['package_label'] : null,
            'quantity' => (int) $data['quantity'],
            'net_weight_kg' => $data['net_weight_kg'] !== '' ? (float) $data['net_weight_kg'] : null,
            'qr_token' => $qrToken,
            'publish_status' => $publishStatus,
            'created_by_user_id' => $partnerId,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function listByPartner(int $partnerId): array
    {
        $sql = 'SELECT lp.*, l.lot_code, l.produce_type, l.partner_id
                FROM lot_packages lp
                INNER JOIN lots l ON l.id = lp.lot_id
                WHERE l.partner_id = :partner_id
                ORDER BY lp.created_at DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['partner_id' => $partnerId]);

        return $stmt->fetchAll();
    }

    public static function listByLotForPartner(int $partnerId, int $lotId): array
    {
        $sql = 'SELECT lp.*, l.lot_code, l.produce_type, l.partner_id
                FROM lot_packages lp
                INNER JOIN lots l ON l.id = lp.lot_id
                WHERE l.partner_id = :partner_id
                  AND lp.lot_id = :lot_id
                ORDER BY lp.created_at DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'partner_id' => $partnerId,
            'lot_id' => $lotId,
        ]);

        return $stmt->fetchAll();
    }

    public static function findByPartner(int $id, int $partnerId): ?array
    {
        $sql = 'SELECT lp.*, l.lot_code, l.produce_type, l.partner_id
                FROM lot_packages lp
                INNER JOIN lots l ON l.id = lp.lot_id
                WHERE lp.id = :id AND l.partner_id = :partner_id
                LIMIT 1';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'partner_id' => $partnerId,
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function updateByPartner(int $id, int $partnerId, array $data): void
    {
        $sql = 'UPDATE lot_packages lp
                INNER JOIN lots l ON l.id = lp.lot_id
                SET lp.package_code = :package_code,
                    lp.package_label = :package_label,
                    lp.quantity = :quantity,
                    lp.net_weight_kg = :net_weight_kg,
                    lp.publish_status = :publish_status,
                    lp.updated_at = NOW()
                WHERE lp.id = :id
                  AND l.partner_id = :partner_id';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'package_code' => (string) $data['package_code'],
            'package_label' => $data['package_label'] !== '' ? (string) $data['package_label'] : null,
            'quantity' => (int) $data['quantity'],
            'net_weight_kg' => $data['net_weight_kg'] !== '' ? (float) $data['net_weight_kg'] : null,
            'publish_status' => (string) $data['publish_status'],
            'id' => $id,
            'partner_id' => $partnerId,
        ]);
    }

    public static function deleteByPartner(int $id, int $partnerId): void
    {
        $sql = 'DELETE lp
                FROM lot_packages lp
                INNER JOIN lots l ON l.id = lp.lot_id
                WHERE lp.id = :id
                  AND l.partner_id = :partner_id';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'partner_id' => $partnerId,
        ]);
    }

    public static function regenerateQrByPartner(int $id, int $partnerId, string $token): void
    {
        $sql = 'UPDATE lot_packages lp
                INNER JOIN lots l ON l.id = lp.lot_id
                SET lp.qr_token = :token,
                    lp.updated_at = NOW()
                WHERE lp.id = :id
                  AND l.partner_id = :partner_id';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'token' => $token,
            'id' => $id,
            'partner_id' => $partnerId,
        ]);
    }

    public static function findPublicByToken(string $token): ?array
    {
        $sql = 'SELECT lp.*, l.id AS lot_id, l.lot_code, l.produce_type, l.origin_region, l.harvest_date,
                       l.grade1_count, l.grade2_count, l.defect_count, l.notes, l.image_path,
                       l.publish_status AS lot_publish_status, l.partner_id, p.organization_name, u.full_name AS partner_name
                FROM lot_packages lp
                INNER JOIN lots l ON l.id = lp.lot_id
                LEFT JOIN partner_profiles p ON p.user_id = l.partner_id
                LEFT JOIN users u ON u.id = l.partner_id
                WHERE lp.qr_token = :qr_token
                  AND lp.publish_status = :package_publish_status
                  AND l.publish_status = :lot_publish_status
                LIMIT 1';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'qr_token' => $token,
            'package_publish_status' => 'published',
            'lot_publish_status' => 'published',
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function existsByToken(string $token): bool
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM lot_packages WHERE qr_token = :qr_token');
        $stmt->execute(['qr_token' => $token]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function allWithLot(): array
    {
        $sql = 'SELECT lp.*, l.lot_code, l.produce_type, l.partner_id, u.full_name AS partner_name, p.organization_name
                FROM lot_packages lp
                INNER JOIN lots l ON l.id = lp.lot_id
                LEFT JOIN users u ON u.id = l.partner_id
                LEFT JOIN partner_profiles p ON p.user_id = l.partner_id
                ORDER BY lp.created_at DESC';

        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $sql = 'SELECT lp.*, l.lot_code, l.produce_type, l.partner_id, u.full_name AS partner_name, p.organization_name
                FROM lot_packages lp
                INNER JOIN lots l ON l.id = lp.lot_id
                LEFT JOIN users u ON u.id = l.partner_id
                LEFT JOIN partner_profiles p ON p.user_id = l.partner_id
                WHERE lp.id = :id
                LIMIT 1';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function counts(): array
    {
        $pdo = Database::pdo();
        $total = (int) $pdo->query('SELECT COUNT(*) FROM lot_packages')->fetchColumn();
        $published = (int) $pdo->query("SELECT COUNT(*) FROM lot_packages WHERE publish_status='published'")->fetchColumn();

        return [
            'total_packages' => $total,
            'published_packages' => $published,
        ];
    }
}
