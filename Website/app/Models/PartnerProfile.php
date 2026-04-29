<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PartnerProfile
{
    public static function upsert(int $userId, array $data): void
    {
        $sql = 'INSERT INTO partner_profiles (user_id, organization_name, representative_name, phone, address, region, tax_code, created_at, updated_at)
                VALUES (:user_id, :organization_name, :representative_name, :phone, :address, :region, :tax_code, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                organization_name = VALUES(organization_name),
                representative_name = VALUES(representative_name),
                phone = VALUES(phone),
                address = VALUES(address),
                region = VALUES(region),
                tax_code = VALUES(tax_code),
                updated_at = NOW()';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'organization_name' => (string) ($data['organization_name'] ?? ''),
            'representative_name' => (string) ($data['representative_name'] ?? ''),
            'phone' => (string) ($data['phone'] ?? ''),
            'address' => (string) ($data['address'] ?? ''),
            'region' => (string) ($data['region'] ?? ''),
            'tax_code' => (string) ($data['tax_code'] ?? ''),
        ]);
    }

    public static function byUserId(int $userId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM partner_profiles WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
