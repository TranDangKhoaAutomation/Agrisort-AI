<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\MembershipService;
use App\Services\SchemaSyncService;

final class User
{
    public static function findByEmail(string $email): ?array
    {
        SchemaSyncService::ensureUserVipColumn();

        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function findById(int $id): ?array
    {
        SchemaSyncService::ensureUserVipColumn();

        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function createPartner(string $fullName, string $email, string $passwordHash): int
    {
        return self::createAccount($fullName, $email, $passwordHash, 'partner');
    }

    public static function createAccount(string $fullName, string $email, string $passwordHash, string $role): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO users (full_name, email, password_hash, role, status, created_at, updated_at)
             VALUES (:full_name, :email, :password_hash, :role, :status, NOW(), NOW())'
        );

        $stmt->execute([
            'full_name' => $fullName,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => self::normalizeRole($role),
            'status' => 'pending',
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function normalizeRole(string $role): string
    {
        $role = strtolower(trim($role));

        return match ($role) {
            'admin',
            'partner',
            'farmer',
            'transporter',
            'warehouse',
            'seller',
            'visitor' => $role,
            default => 'farmer',
        };
    }

    public static function roleLabel(string $role, string $locale = 'vi'): string
    {
        $role = self::normalizeRole($role);
        $vi = [
            'admin' => 'Quản trị viên',
            'partner' => 'Đối tác',
            'farmer' => 'Nông dân',
            'transporter' => 'Vận chuyển',
            'warehouse' => 'Kho',
            'seller' => 'Người bán',
            'visitor' => 'Khách',
        ];
        $en = [
            'admin' => 'Admin',
            'partner' => 'Partner',
            'farmer' => 'Farmer',
            'transporter' => 'Transporter',
            'warehouse' => 'Warehouse',
            'seller' => 'Seller',
            'visitor' => 'Visitor',
        ];

        $labels = $locale === 'en' ? $en : $vi;

        return $labels[$role] ?? $role;
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public static function setVipUntil(int $id, ?string $vipUntil): void
    {
        if (!SchemaSyncService::ensureUserVipColumn()) {
            return;
        }

        $stmt = Database::pdo()->prepare(
            'UPDATE users
             SET vip_until = :vip_until,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        if ($vipUntil === null) {
            $stmt->bindValue(':vip_until', null, \PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':vip_until', $vipUntil);
        }
        $stmt->execute();
    }

    public static function hasVip(array $user): bool
    {
        return MembershipService::hasVip($user);
    }

    public static function existsByEmailExceptId(string $email, int $id): bool
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND id <> :id');
        $stmt->execute([
            'email' => $email,
            'id' => $id,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function updateAccount(int $id, string $fullName, string $email): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE users
             SET full_name = :full_name,
                 email = :email,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'full_name' => $fullName,
            'email' => $email,
        ]);
    }

    public static function updatePassword(int $id, string $passwordHash): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'password_hash' => $passwordHash,
            'id' => $id,
        ]);
    }

    public static function pendingPartners(): array
    {
        $sql = 'SELECT u.id, u.full_name, u.email, u.role, u.status, p.organization_name, p.phone
                FROM users u
                LEFT JOIN partner_profiles p ON p.user_id = u.id
                WHERE u.role IN (\'partner\',\'farmer\') AND u.status = :status
                ORDER BY u.created_at DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'status' => 'pending',
        ]);

        return $stmt->fetchAll();
    }

    public static function partnerList(): array
    {
        $sql = 'SELECT u.id, u.full_name, u.email, u.role, u.status, p.organization_name, p.phone
                FROM users u
                LEFT JOIN partner_profiles p ON p.user_id = u.id
                WHERE u.role IN (\'partner\',\'farmer\')
                ORDER BY u.created_at DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function pendingActors(): array
    {
        $sql = 'SELECT u.id, u.full_name, u.email, u.role, u.status, p.organization_name, p.phone
                FROM users u
                LEFT JOIN partner_profiles p ON p.user_id = u.id
                WHERE u.role IN (\'partner\',\'farmer\',\'transporter\',\'warehouse\',\'seller\')
                  AND u.status = :status
                ORDER BY u.created_at DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'status' => 'pending',
        ]);

        return $stmt->fetchAll();
    }

    public static function listByRoles(array $roles): array
    {
        $cleanRoles = [];
        foreach ($roles as $value) {
            if (!is_string($value)) {
                continue;
            }
            $cleanRoles[] = self::normalizeRole($value);
        }

        if ($cleanRoles === []) {
            return [];
        }

        $cleanRoles = array_values(array_unique($cleanRoles));
        $params = [];
        $placeholders = [];
        foreach ($cleanRoles as $index => $role) {
            $key = ':role' . $index;
            $placeholders[] = $key;
            $params[$key] = $role;
        }

        $sql = 'SELECT u.id, u.full_name, u.email, u.role, u.status, p.organization_name, p.phone
                FROM users u
                LEFT JOIN partner_profiles p ON p.user_id = u.id
                WHERE u.role IN (' . implode(',', $placeholders) . ')
                ORDER BY u.created_at DESC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function dashboardCounts(): array
    {
        $pdo = Database::pdo();
        $pending = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('partner','farmer') AND status='pending'")->fetchColumn();
        $active = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('partner','farmer') AND status='active'")->fetchColumn();

        return [
            'pending_partners' => $pending,
            'active_partners' => $active,
        ];
    }

    public static function supplyCounts(): array
    {
        $pdo = Database::pdo();
        $pending = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('transporter','warehouse','seller') AND status='pending'")->fetchColumn();
        $active = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('transporter','warehouse','seller') AND status='active'")->fetchColumn();

        return [
            'pending_supply_users' => $pending,
            'active_supply_users' => $active,
        ];
    }

    public static function savePasswordReset(int $userId, string $tokenHash, string $expiresAt): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at)
             VALUES (:user_id, :token_hash, :expires_at, NOW())'
        );
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);
    }

    public static function verifyPasswordReset(string $tokenHash): ?array
    {
        $sql = 'SELECT pr.*, u.email, u.full_name
                FROM password_resets pr
                INNER JOIN users u ON u.id = pr.user_id
                WHERE pr.token_hash = :token_hash
                  AND pr.used_at IS NULL
                  AND pr.expires_at > NOW()
                ORDER BY pr.id DESC
                LIMIT 1';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['token_hash' => $tokenHash]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function markPasswordResetUsed(int $id): void
    {
        $stmt = Database::pdo()->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
