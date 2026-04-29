<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class BlogPost
{
    /**
     * @param array{q?: string, status?: string, page?: int, per_page?: int} $filters
     * @return array{rows: array<int,array<string,mixed>>, total: int, page: int, per_page: int, total_pages: int}
     */
    public static function adminList(array $filters = []): array
    {
        $keyword = trim((string) ($filters['q'] ?? ''));
        if ($keyword !== '') {
            $keyword = function_exists('mb_substr') ? mb_substr($keyword, 0, 120) : substr($keyword, 0, 120);
        }

        $status = strtolower(trim((string) ($filters['status'] ?? 'all')));
        if (!in_array($status, ['all', 'draft', 'published'], true)) {
            $status = 'all';
        }

        $page = (int) ($filters['page'] ?? 1);
        if ($page <= 0) {
            $page = 1;
        }

        $perPage = (int) ($filters['per_page'] ?? 20);
        if (!in_array($perPage, [20, 50, 100], true)) {
            $perPage = 20;
        }

        $where = [];
        $params = [];

        if ($status !== 'all') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        if ($keyword !== '') {
            $where[] = '(slug LIKE :keyword OR title_vi LIKE :keyword OR title_en LIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }

        $whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

        $countStmt = Database::pdo()->prepare('SELECT COUNT(*) FROM blog_posts' . $whereSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;

        $stmt = Database::pdo()->prepare(
            'SELECT * FROM blog_posts'
            . $whereSql
            . ' ORDER BY COALESCE(published_at, created_at) DESC, id DESC
                LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return [
            'rows' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    public static function published(int $limit = 50): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM blog_posts WHERE status = :status ORDER BY published_at DESC, id DESC LIMIT :limit');
        $stmt->bindValue('status', 'published');
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function bySlug(string $slug): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM blog_posts WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function all(): array
    {
        return Database::pdo()->query('SELECT * FROM blog_posts ORDER BY id DESC')->fetchAll();
    }

    public static function create(array $data): int
    {
        $sql = 'INSERT INTO blog_posts
                (slug, title_vi, title_en, content_vi, content_en, excerpt_vi, excerpt_en, thumbnail, status, seo_title, seo_description, published_at, created_at, updated_at)
                VALUES
                (:slug, :title_vi, :title_en, :content_vi, :content_en, :excerpt_vi, :excerpt_en, :thumbnail, :status, :seo_title, :seo_description, :published_at, NOW(), NOW())';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'slug' => $data['slug'],
            'title_vi' => $data['title_vi'],
            'title_en' => $data['title_en'],
            'content_vi' => $data['content_vi'],
            'content_en' => $data['content_en'],
            'excerpt_vi' => $data['excerpt_vi'],
            'excerpt_en' => $data['excerpt_en'],
            'thumbnail' => $data['thumbnail'] ?? null,
            'status' => $data['status'],
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'published_at' => $data['status'] === 'published' ? date('Y-m-d H:i:s') : null,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $sql = 'UPDATE blog_posts SET
                    slug = :slug,
                    title_vi = :title_vi,
                    title_en = :title_en,
                    content_vi = :content_vi,
                    content_en = :content_en,
                    excerpt_vi = :excerpt_vi,
                    excerpt_en = :excerpt_en,
                    thumbnail = COALESCE(:thumbnail, thumbnail),
                    status = :status,
                    seo_title = :seo_title,
                    seo_description = :seo_description,
                    published_at = CASE WHEN :status = "published" AND published_at IS NULL THEN NOW() ELSE published_at END,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'slug' => $data['slug'],
            'title_vi' => $data['title_vi'],
            'title_en' => $data['title_en'],
            'content_vi' => $data['content_vi'],
            'content_en' => $data['content_en'],
            'excerpt_vi' => $data['excerpt_vi'],
            'excerpt_en' => $data['excerpt_en'],
            'thumbnail' => $data['thumbnail'] ?? null,
            'status' => $data['status'],
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
        ]);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM blog_posts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function countPublished(): int
    {
        return (int) Database::pdo()->query("SELECT COUNT(*) FROM blog_posts WHERE status='published'")->fetchColumn();
    }
}
