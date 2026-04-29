<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ApiKey
{
    public static function create(string $name, string $plainKey): int
    {
        $hash = hash('sha256', $plainKey);
        $stmt = Database::pdo()->prepare(
            'INSERT INTO api_keys (name, api_key_hash, status, created_at)
             VALUES (:name, :hash, :status, NOW())'
        );
        $stmt->execute([
            'name' => $name,
            'hash' => $hash,
            'status' => 'active',
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function listAll(): array
    {
        return Database::pdo()->query('SELECT id, name, status, last_used_at, created_at FROM api_keys ORDER BY id DESC')->fetchAll();
    }

    public static function validate(string $plainKey): ?array
    {
        $hash = hash('sha256', $plainKey);
        $stmt = Database::pdo()->prepare('SELECT * FROM api_keys WHERE api_key_hash = :hash AND status = :status LIMIT 1');
        $stmt->execute([
            'hash' => $hash,
            'status' => 'active',
        ]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $update = Database::pdo()->prepare('UPDATE api_keys SET last_used_at = NOW() WHERE id = :id');
        $update->execute(['id' => (int) $row['id']]);

        return $row;
    }
}
