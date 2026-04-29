<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDOException;

final class Lot
{
    public const ERR_DUPLICATE_PARTNER_LOT_CODE = 'duplicate_partner_lot_code';

    public static function create(int $partnerId, array $data, string $qrToken, string $publishStatus): int
    {
        $sql = 'INSERT INTO lots
                (partner_id, lot_code, produce_type, origin_region, harvest_date, grade1_count, grade2_count, defect_count, notes, image_path, publish_status, qr_token, created_at, updated_at)
                VALUES
                (:partner_id, :lot_code, :produce_type, :origin_region, :harvest_date, :grade1_count, :grade2_count, :defect_count, :notes, :image_path, :publish_status, :qr_token, NOW(), NOW())';
        $stmt = Database::pdo()->prepare($sql);
        try {
            $stmt->execute([
                'partner_id' => $partnerId,
                'lot_code' => $data['lot_code'],
                'produce_type' => $data['produce_type'],
                'origin_region' => $data['origin_region'],
                'harvest_date' => $data['harvest_date'],
                'grade1_count' => (int) $data['grade1_count'],
                'grade2_count' => (int) $data['grade2_count'],
                'defect_count' => (int) $data['defect_count'],
                'notes' => $data['notes'] ?? null,
                'image_path' => $data['image_path'] ?? null,
                'publish_status' => $publishStatus,
                'qr_token' => $qrToken,
            ]);
        } catch (PDOException $e) {
            if (self::isDuplicatePartnerLotCodeError($e)) {
                throw new \RuntimeException(self::ERR_DUPLICATE_PARTNER_LOT_CODE, 0, $e);
            }

            throw $e;
        }

        return (int) Database::pdo()->lastInsertId();
    }

    public static function updateByPartner(int $id, int $partnerId, array $data): void
    {
        $sql = 'UPDATE lots SET
                    lot_code = :lot_code,
                    produce_type = :produce_type,
                    origin_region = :origin_region,
                    harvest_date = :harvest_date,
                    grade1_count = :grade1_count,
                    grade2_count = :grade2_count,
                    defect_count = :defect_count,
                    notes = :notes,
                    image_path = COALESCE(:image_path, image_path),
                    updated_at = NOW()
                WHERE id = :id AND partner_id = :partner_id';
        $stmt = Database::pdo()->prepare($sql);
        try {
            $stmt->execute([
                'lot_code' => $data['lot_code'],
                'produce_type' => $data['produce_type'],
                'origin_region' => $data['origin_region'],
                'harvest_date' => $data['harvest_date'],
                'grade1_count' => (int) $data['grade1_count'],
                'grade2_count' => (int) $data['grade2_count'],
                'defect_count' => (int) $data['defect_count'],
                'notes' => $data['notes'] ?? null,
                'image_path' => $data['image_path'] ?? null,
                'id' => $id,
                'partner_id' => $partnerId,
            ]);
        } catch (PDOException $e) {
            if (self::isDuplicatePartnerLotCodeError($e)) {
                throw new \RuntimeException(self::ERR_DUPLICATE_PARTNER_LOT_CODE, 0, $e);
            }

            throw $e;
        }
    }

    public static function existsByPartnerAndCode(int $partnerId, string $lotCode, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM lots WHERE partner_id = :partner_id AND lot_code = :lot_code';
        $params = [
            'partner_id' => $partnerId,
            'lot_code' => $lotCode,
        ];

        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function deleteByPartner(int $id, int $partnerId): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM lots WHERE id = :id AND partner_id = :partner_id');
        $stmt->execute(['id' => $id, 'partner_id' => $partnerId]);
    }

    public static function listByPartner(int $partnerId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM lots WHERE partner_id = :partner_id ORDER BY created_at DESC');
        $stmt->execute(['partner_id' => $partnerId]);
        return $stmt->fetchAll();
    }

    public static function findByPartner(int $id, int $partnerId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM lots WHERE id = :id AND partner_id = :partner_id LIMIT 1');
        $stmt->execute(['id' => $id, 'partner_id' => $partnerId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findPublicByToken(string $token): ?array
    {
        $sql = 'SELECT l.*, p.organization_name, u.full_name AS partner_name
                FROM lots l
                LEFT JOIN partner_profiles p ON p.user_id = l.partner_id
                LEFT JOIN users u ON u.id = l.partner_id
                WHERE l.qr_token = :qr_token
                  AND l.publish_status = :publish_status
                LIMIT 1';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'qr_token' => $token,
            'publish_status' => 'published',
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function existsByToken(string $token): bool
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM lots WHERE qr_token = :qr_token');
        $stmt->execute(['qr_token' => $token]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function findById(int $id): ?array
    {
        $sql = 'SELECT l.*, p.organization_name, u.full_name AS partner_name
                FROM lots l
                LEFT JOIN partner_profiles p ON p.user_id = l.partner_id
                LEFT JOIN users u ON u.id = l.partner_id
                WHERE l.id = :id
                LIMIT 1';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findPublicById(int $id): ?array
    {
        $sql = 'SELECT l.*, p.organization_name, u.full_name AS partner_name
                FROM lots l
                LEFT JOIN partner_profiles p ON p.user_id = l.partner_id
                LEFT JOIN users u ON u.id = l.partner_id
                WHERE l.id = :id
                  AND l.publish_status = :publish_status
                LIMIT 1';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'publish_status' => 'published',
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function featuredPublic(int $limit = 6): array
    {
        $limit = max(1, min($limit, 12));

        $sql = 'SELECT l.*, p.organization_name, u.full_name AS partner_name
                FROM lots l
                LEFT JOIN partner_profiles p ON p.user_id = l.partner_id
                LEFT JOIN users u ON u.id = l.partner_id
                WHERE l.publish_status = :publish_status
                ORDER BY (l.image_path IS NULL), l.created_at DESC
                LIMIT ' . $limit;

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'publish_status' => 'published',
        ]);

        return $stmt->fetchAll();
    }

    public static function belongsToPartner(int $lotId, int $partnerId): bool
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM lots WHERE id = :id AND partner_id = :partner_id');
        $stmt->execute([
            'id' => $lotId,
            'partner_id' => $partnerId,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function regenerateQr(int $id, int $partnerId, string $token): void
    {
        $stmt = Database::pdo()->prepare('UPDATE lots SET qr_token = :token, updated_at = NOW() WHERE id = :id AND partner_id = :partner_id');
        $stmt->execute([
            'token' => $token,
            'id' => $id,
            'partner_id' => $partnerId,
        ]);
    }

    public static function setPublishStatus(int $id, string $status): void
    {
        $stmt = Database::pdo()->prepare('UPDATE lots SET publish_status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public static function allWithPartner(): array
    {
        $sql = 'SELECT l.*, u.full_name AS partner_name, p.organization_name
                FROM lots l
                LEFT JOIN users u ON u.id = l.partner_id
                LEFT JOIN partner_profiles p ON p.user_id = l.partner_id
                ORDER BY l.created_at DESC';
        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function counts(): array
    {
        $pdo = Database::pdo();
        $total = (int) $pdo->query('SELECT COUNT(*) FROM lots')->fetchColumn();
        $published = (int) $pdo->query("SELECT COUNT(*) FROM lots WHERE publish_status='published'")->fetchColumn();
        return [
            'total_lots' => $total,
            'published_lots' => $published,
        ];
    }

    private static function isDuplicatePartnerLotCodeError(PDOException $e): bool
    {
        return $e->getCode() === '23000'
            && str_contains(strtolower($e->getMessage()), 'uq_lots_partner_lot_code');
    }
}
