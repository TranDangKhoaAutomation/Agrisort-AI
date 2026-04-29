<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class MembershipService
{
    /**
     * @var list<string>
     */
    private const INTERNAL_UNLIMITED_ROLES = ['admin'];

    public static function currentUserRecord(): ?array
    {
        SchemaSyncService::ensureUserVipColumn();

        $userId = (int) (current_user()['id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        return User::findById($userId);
    }

    public static function hasVip(?array $user): bool
    {
        if (!is_array($user)) {
            return false;
        }

        $vipUntil = trim((string) ($user['vip_until'] ?? ''));
        if ($vipUntil === '') {
            return false;
        }

        $timestamp = strtotime($vipUntil);
        return $timestamp !== false && $timestamp >= time();
    }

    public static function isInternalRole(?array $user): bool
    {
        if (!is_array($user)) {
            return false;
        }

        $role = strtolower(trim((string) ($user['role'] ?? '')));
        return in_array($role, self::INTERNAL_UNLIMITED_ROLES, true);
    }

    public static function traceRequiresLogin(): bool
    {
        return self::traceRequiresVip() || self::configFlag('membership.trace_require_login');
    }

    public static function traceRequiresVip(): bool
    {
        return self::configFlag('membership.trace_require_vip');
    }

    /**
     * @return array{
     *   plan_code:string,
     *   plan_label:string,
     *   summary:string,
     *   hint:string,
     *   is_logged_in:bool,
     *   is_vip:bool,
     *   is_unlimited:bool,
     *   daily_limit:int|null,
     *   used:int,
     *   remaining:int|null,
     *   blocked:bool,
     *   blocked_message:string,
     *   requires_login:bool,
     *   requires_vip:bool,
     *   reset_in_seconds:int,
     *   vip_until_label:string|null
     * }
     */
    public static function traceAccessSnapshot(?array $user = null, ?string $ip = null): array
    {
        SchemaSyncService::ensureUserVipColumn();

        $user = $user ?? self::currentUserRecord();
        $isLoggedIn = is_array($user);
        $isVip = self::hasVip($user);
        $isInternal = self::isInternalRole($user);
        $requiresVip = self::traceRequiresVip();
        $requiresLogin = self::traceRequiresLogin();
        $isOpenByDefault = !$requiresLogin && !$requiresVip;

        $planCode = 'public';
        $planLabel = lang_text('Truy xuất công khai', 'Public traceability');
        $summary = lang_text('Quét QR hoặc nhập mã lô', 'Scan a QR code or enter a lot token');
        $hint = lang_text(
            'Người dùng cuối có thể quét QR để xem thông tin lô, chất lượng và hành trình đã được công khai.',
            'End users can scan the QR code to view the published lot profile, quality data, and trace timeline.'
        );

        if ($isLoggedIn) {
            $planCode = 'operator';
            $planLabel = lang_text('Tài khoản vận hành', 'Operational account');
            $summary = lang_text('Đã đăng nhập khu vận hành', 'Signed in to the operations workspace');
            $hint = lang_text(
                'Tài khoản này có thể dùng web dashboard và App API theo đúng vai trò đã được phê duyệt.',
                'This account can use the web dashboard and the App API according to its approved role.'
            );
        }

        if ($isInternal) {
            $planCode = 'internal';
            $planLabel = lang_text('Tài khoản quản trị', 'Administrative account');
            $summary = lang_text('Toàn quyền vận hành nội bộ', 'Internal administrative access');
            $hint = lang_text(
                'Tài khoản quản trị dùng cho điều phối dữ liệu, người dùng, truy xuất và cấu hình hệ thống.',
                'Administrative accounts are used to manage data, users, traceability, and system settings.'
            );
        } elseif ($isVip) {
            $planCode = 'extended';
            $planLabel = lang_text('Quyền truy cập mở rộng', 'Extended access');
            $summary = lang_text('Đã kích hoạt hỗ trợ vận hành', 'Deployment support access enabled');
            $hint = lang_text(
                'Tài khoản này đang có mốc kích hoạt hỗ trợ vận hành mở rộng trong hệ thống.',
                'This account currently has an extended deployment-support activation window.'
            );
        }

        $blockedMessage = self::tracePolicyBlockedMessage($isLoggedIn, $isVip, $isInternal, $requiresLogin, $requiresVip);
        $blocked = $blockedMessage !== '';

        if (!$blocked && $isOpenByDefault) {
            $summary = $isLoggedIn
                ? lang_text('Truy xuất theo QR và mã lô', 'QR and lot-token traceability')
                : lang_text('Mở công khai theo QR', 'Open public QR traceability');
        }

        return [
            'plan_code' => $planCode,
            'plan_label' => $planLabel,
            'summary' => $summary,
            'hint' => $hint,
            'is_logged_in' => $isLoggedIn,
            'is_vip' => $isVip,
            'is_unlimited' => !$blocked,
            'daily_limit' => null,
            'used' => 0,
            'remaining' => null,
            'blocked' => $blocked,
            'blocked_message' => $blockedMessage,
            'requires_login' => $requiresLogin,
            'requires_vip' => $requiresVip,
            'reset_in_seconds' => 0,
            'vip_until_label' => self::formatVipUntil((string) ($user['vip_until'] ?? '')),
        ];
    }

    /**
     * @return array{
     *   allowed:bool,
     *   plan_code:string,
     *   plan_label:string,
     *   summary:string,
     *   hint:string,
     *   is_logged_in:bool,
     *   is_vip:bool,
     *   is_unlimited:bool,
     *   daily_limit:int|null,
     *   used:int,
     *   remaining:int|null,
     *   blocked:bool,
     *   blocked_message:string,
     *   requires_login:bool,
     *   requires_vip:bool,
     *   reset_in_seconds:int,
     *   vip_until_label:string|null,
     *   message:string
     * }
     */
    public static function ensureTracePreviewAllowed(?array $user = null, ?string $ip = null): array
    {
        $snapshot = self::traceAccessSnapshot($user, $ip);

        if ((string) ($snapshot['blocked_message'] ?? '') !== '') {
            return $snapshot + [
                'allowed' => false,
                'message' => (string) $snapshot['blocked_message'],
            ];
        }

        return $snapshot + [
            'allowed' => true,
            'message' => '',
        ];
    }

    /**
     * @return array{
     *   allowed:bool,
     *   plan_code:string,
     *   plan_label:string,
     *   summary:string,
     *   hint:string,
     *   is_logged_in:bool,
     *   is_vip:bool,
     *   is_unlimited:bool,
     *   daily_limit:int|null,
     *   used:int,
     *   remaining:int|null,
     *   blocked:bool,
     *   blocked_message:string,
     *   requires_login:bool,
     *   requires_vip:bool,
     *   reset_in_seconds:int,
     *   vip_until_label:string|null,
     *   message:string
     * }
     */
    public static function consumeTraceView(?array $user = null, ?string $ip = null): array
    {
        $snapshot = self::traceAccessSnapshot($user, $ip);
        if ((string) ($snapshot['blocked_message'] ?? '') !== '') {
            return $snapshot + [
                'allowed' => false,
                'message' => (string) $snapshot['blocked_message'],
            ];
        }

        return $snapshot + [
            'allowed' => true,
            'message' => '',
        ];
    }

    public static function formatVipUntil(?string $vipUntil, ?string $locale = null): ?string
    {
        $vipUntil = trim((string) $vipUntil);
        if ($vipUntil === '') {
            return null;
        }

        $timestamp = strtotime($vipUntil);
        if ($timestamp === false) {
            return null;
        }

        $locale = $locale ?? lang();
        return $locale === 'en'
            ? date('M j, Y H:i', $timestamp)
            : date('d/m/Y H:i', $timestamp);
    }

    private static function configFlag(string $key, bool $default = false): bool
    {
        $value = config($key, $default);
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    private static function tracePolicyBlockedMessage(
        bool $isLoggedIn,
        bool $isVip,
        bool $isInternal,
        bool $requiresLogin,
        bool $requiresVip
    ): string {
        if ($isInternal || $isVip) {
            return '';
        }

        if ($requiresVip) {
            if (!$isLoggedIn) {
                return lang_text(
                    'Bạn cần đăng nhập bằng tài khoản đã được kích hoạt hỗ trợ vận hành để mở dữ liệu truy xuất.',
                    'You need to sign in with an activation-enabled account to open trace data.'
                );
            }

            return lang_text(
                'Tài khoản hiện tại chưa được kích hoạt quyền truy cập mở rộng cho dữ liệu truy xuất.',
                'This account does not have extended trace-data access enabled yet.'
            );
        }

        if ($requiresLogin && !$isLoggedIn) {
            return lang_text(
                'Bạn cần đăng nhập để tiếp tục truy xuất dữ liệu trong hệ thống.',
                'You need to sign in before continuing to trace data.'
            );
        }

        return '';
    }
}
