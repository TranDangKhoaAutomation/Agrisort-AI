<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CmsSection
{
    public static function getContent(string $sectionKey, string $locale): array
    {
        $stmt = Database::pdo()->prepare('SELECT content_json_vi, content_json_en FROM cms_sections WHERE section_key = :section_key LIMIT 1');
        $stmt->execute(['section_key' => $sectionKey]);
        $row = $stmt->fetch();

        if (!$row) {
            return [];
        }

        $field = $locale === 'en' ? 'content_json_en' : 'content_json_vi';
        $decoded = json_decode((string) $row[$field], true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function save(string $sectionKey, array $vi, array $en): void
    {
        $sql = 'INSERT INTO cms_sections (section_key, content_json_vi, content_json_en, updated_at)
                VALUES (:section_key, :content_vi, :content_en, NOW())
                ON DUPLICATE KEY UPDATE
                content_json_vi = VALUES(content_json_vi),
                content_json_en = VALUES(content_json_en),
                updated_at = NOW()';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'section_key' => $sectionKey,
            'content_vi' => json_encode($vi, JSON_UNESCAPED_UNICODE),
            'content_en' => json_encode($en, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public static function all(): array
    {
        return Database::pdo()->query('SELECT * FROM cms_sections ORDER BY section_key')->fetchAll();
    }
}
