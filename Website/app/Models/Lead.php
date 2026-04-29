<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Lead
{
    public static function createContact(array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO contact_messages (name, email, phone, subject, message, status, created_at)
             VALUES (:name, :email, :phone, :subject, :message, :status, NOW())'
        );
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
            'status' => 'new',
        ]);
    }

    public static function createDemo(array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO demo_requests (name, organization, phone, email, province, message, status, created_at)
             VALUES (:name, :organization, :phone, :email, :province, :message, :status, NOW())'
        );
        $stmt->execute([
            'name' => $data['name'],
            'organization' => $data['organization'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
            'province' => $data['province'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => 'new',
        ]);
    }

    public static function latestContacts(): array
    {
        return Database::pdo()->query('SELECT * FROM contact_messages ORDER BY id DESC LIMIT 50')->fetchAll();
    }

    public static function latestDemos(): array
    {
        return Database::pdo()->query('SELECT * FROM demo_requests ORDER BY id DESC LIMIT 50')->fetchAll();
    }

    public static function countAll(): int
    {
        $pdo = Database::pdo();
        $a = (int) $pdo->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn();
        $b = (int) $pdo->query('SELECT COUNT(*) FROM demo_requests')->fetchColumn();
        return $a + $b;
    }
}
