<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Setting
{
    public static function get(string $key, string $default = ''): string
    {
        $stmt = Database::pdo()->prepare('SELECT key_value FROM admin_settings WHERE key_name = :key_name LIMIT 1');
        $stmt->execute(['key_name' => $key]);
        $value = $stmt->fetchColumn();
        return $value === false ? $default : (string) $value;
    }

    public static function set(string $key, string $value): void
    {
        $sql = 'INSERT INTO admin_settings (key_name, key_value, updated_at)
                VALUES (:key_name, :key_value, NOW())
                ON DUPLICATE KEY UPDATE key_value = VALUES(key_value), updated_at = NOW()';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'key_name' => $key,
            'key_value' => $value,
        ]);
    }

    public static function all(): array
    {
        $rows = Database::pdo()->query('SELECT key_name, key_value FROM admin_settings')->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $out[$row['key_name']] = $row['key_value'];
        }
        return $out;
    }
}
