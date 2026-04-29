<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\RateLimiter;
use App\Core\Validator;
use App\Models\PartnerProfile;
use App\Models\User;
use App\Services\AppApiAuthService;
use App\Services\MailService;
use App\Services\MembershipService;
use Throwable;

final class AppAuthApiController extends AppApiBaseController
{
    public function register(array $params = []): void
    {
        $payload = $this->payload();

        $fullName = trim((string) ($payload['full_name'] ?? ''));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');
        $role = User::normalizeRole((string) ($payload['role'] ?? 'farmer'));

        $allowedRoles = ['partner', 'farmer', 'transporter', 'warehouse', 'seller'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'farmer';
        }

        $profile = [
            'organization_name' => trim((string) ($payload['organization_name'] ?? '')),
            'representative_name' => trim((string) ($payload['representative_name'] ?? $fullName)),
            'phone' => trim((string) ($payload['phone'] ?? '')),
            'address' => trim((string) ($payload['address'] ?? '')),
            'region' => trim((string) ($payload['region'] ?? '')),
            'tax_code' => trim((string) ($payload['tax_code'] ?? '')),
        ];

        $errors = Validator::required(
            [
                'full_name' => $fullName,
                'email' => $email,
                'password' => $password,
            ],
            ['full_name', 'email', 'password']
        );

        if (!Validator::email($email)) {
            $errors['email'] = 'Email không đúng định dạng.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Mật khẩu phải có ít nhất 8 ký tự.';
        }
        if ($role === 'partner' && $profile['organization_name'] === '') {
            $errors['organization_name'] = 'Tên tổ chức là bắt buộc cho tài khoản đối tác.';
        }
        if (User::findByEmail($email)) {
            $errors['email'] = 'Email đã tồn tại trong hệ thống.';
        }

        if ($errors !== []) {
            $this->fail('Dữ liệu đăng ký chưa hợp lệ.', 422, $errors);
            return;
        }

        $userId = User::createAccount($fullName, $email, password_hash($password, PASSWORD_DEFAULT), $role);
        if (in_array($role, ['partner', 'farmer'], true)) {
            PartnerProfile::upsert($userId, $profile);
        }

        $this->ok('Đăng ký tài khoản thành công.', [
            'token_type' => null,
            'access_token' => null,
            'expires_at' => null,
            'pending_approval' => true,
            'user' => $this->buildUserPayload($userId),
        ], null, 201);
    }

    public function login(array $params = []): void
    {
        $payload = $this->payload();
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->fail('Vui lòng nhập đầy đủ email và mật khẩu.', 422, [
                'email' => $email === '' ? 'Email là bắt buộc.' : null,
                'password' => $password === '' ? 'Mật khẩu là bắt buộc.' : null,
            ]);
            return;
        }

        $rateKey = 'app_login:' . $this->request->ip() . ':' . $email;
        if (!RateLimiter::allow($rateKey, 12, 600)) {
            $this->fail('Bạn đã đăng nhập sai quá nhiều lần. Vui lòng thử lại sau 10 phút.', 429);
            return;
        }

        $user = User::findByEmail($email);
        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            $this->fail('Email hoặc mật khẩu không chính xác.', 401);
            return;
        }

        if ((string) $user['status'] !== 'active') {
            $this->fail('Tài khoản chưa được kích hoạt hoặc đã bị khóa.', 403);
            return;
        }

        try {
            $token = AppApiAuthService::issueToken((int) $user['id'], 'android-login', 30);
        } catch (Throwable $e) {
            error_log('AppAuthApiController login token issue failed: ' . $e->getMessage());
            $this->fail('Khong the tao phien dang nhap luc nay. Vui long thu lai sau.', 503);
            return;
        }
        $this->ok('Đăng nhập thành công.', [
            'token_type' => 'Bearer',
            'access_token' => $token['plain_token'],
            'expires_at' => $token['expires_at'],
            'user' => $this->buildUserPayload((int) $user['id']),
        ]);
    }

    public function logout(array $params = []): void
    {
        $resolved = $this->requireToken();
        if ($resolved === null) {
            return;
        }

        AppApiAuthService::revokeTokenFromRequest($this->request);
        $this->ok('Đăng xuất thành công.', null);
    }

    public function me(array $params = []): void
    {
        $resolved = $this->requireToken();
        if ($resolved === null) {
            return;
        }

        $user = is_array($resolved['user'] ?? null) ? $resolved['user'] : [];
        $this->ok('Lấy thông tin tài khoản thành công.', [
            'user' => $this->buildUserPayload((int) ($user['id'] ?? 0)),
            'expires_at' => (string) ($resolved['expires_at'] ?? ''),
        ]);
    }

    public function updateProfile(array $params = []): void
    {
        $resolved = $this->requireToken();
        if ($resolved === null) {
            return;
        }

        $currentUser = is_array($resolved['user'] ?? null) ? $resolved['user'] : null;
        if ($currentUser === null) {
            $this->fail('Phiên đăng nhập không hợp lệ.', 401);
            return;
        }

        $userId = (int) $currentUser['id'];
        $payload = $this->payload();

        $fullName = trim((string) ($payload['full_name'] ?? $currentUser['full_name'] ?? ''));
        $email = strtolower(trim((string) ($payload['email'] ?? $currentUser['email'] ?? '')));
        $role = (string) ($currentUser['role'] ?? '');

        $errors = Validator::required(
            ['full_name' => $fullName, 'email' => $email],
            ['full_name', 'email']
        );
        if (!Validator::email($email)) {
            $errors['email'] = 'Email không đúng định dạng.';
        }
        if (User::existsByEmailExceptId($email, $userId)) {
            $errors['email'] = 'Email đã được sử dụng bởi tài khoản khác.';
        }

        $profilePayload = null;
        if (in_array($role, ['partner', 'farmer'], true)) {
            $profilePayload = [
                'organization_name' => trim((string) ($payload['organization_name'] ?? '')),
                'representative_name' => trim((string) ($payload['representative_name'] ?? $fullName)),
                'phone' => trim((string) ($payload['phone'] ?? '')),
                'address' => trim((string) ($payload['address'] ?? '')),
                'region' => trim((string) ($payload['region'] ?? '')),
                'tax_code' => trim((string) ($payload['tax_code'] ?? '')),
            ];
            if ($role === 'partner' && $profilePayload['organization_name'] === '') {
                $errors['organization_name'] = 'Tên tổ chức không được để trống.';
            }
        }

        if ($errors !== []) {
            $this->fail('Dữ liệu cập nhật hồ sơ chưa hợp lệ.', 422, $errors);
            return;
        }

        User::updateAccount($userId, $fullName, $email);
        if ($profilePayload !== null) {
            PartnerProfile::upsert($userId, $profilePayload);
        }

        $this->ok('Cập nhật hồ sơ thành công.', [
            'user' => $this->buildUserPayload($userId),
        ]);
    }

    public function updatePassword(array $params = []): void
    {
        $resolved = $this->requireToken();
        if ($resolved === null) {
            return;
        }

        $currentUser = is_array($resolved['user'] ?? null) ? $resolved['user'] : null;
        if ($currentUser === null) {
            $this->fail('Phiên đăng nhập không hợp lệ.', 401);
            return;
        }

        $payload = $this->payload();
        $currentPassword = (string) ($payload['current_password'] ?? '');
        $newPassword = (string) ($payload['password'] ?? '');
        $confirmPassword = (string) ($payload['password_confirmation'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $this->fail('Vui lòng nhập đầy đủ thông tin đổi mật khẩu.', 422);
            return;
        }

        if (!password_verify($currentPassword, (string) ($currentUser['password_hash'] ?? ''))) {
            $this->fail('Mật khẩu hiện tại không chính xác.', 422);
            return;
        }

        if (strlen($newPassword) < 8) {
            $this->fail('Mật khẩu mới phải có ít nhất 8 ký tự.', 422);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            $this->fail('Xác nhận mật khẩu mới không khớp.', 422);
            return;
        }

        $userId = (int) $currentUser['id'];
        User::updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));

        $this->ok('Đổi mật khẩu thành công.', [
            'user' => $this->buildUserPayload($userId),
        ]);
    }

    public function forgotPassword(array $params = []): void
    {
        $payload = $this->payload();
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        if ($email === '') {
            $this->fail('Vui lòng nhập email để nhận liên kết đặt lại mật khẩu.', 422);
            return;
        }

        $rateKey = 'app_forgot:' . $this->request->ip() . ':' . $email;
        if (!RateLimiter::allow($rateKey, 6, 600)) {
            $this->fail('Bạn đã gửi yêu cầu quá nhiều lần. Vui lòng thử lại sau.', 429);
            return;
        }

        $user = User::findByEmail($email);
        if ($user) {
            $plainToken = bin2hex(random_bytes(24));
            $tokenHash = hash('sha256', $plainToken);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);
            User::savePasswordReset((int) $user['id'], $tokenHash, $expiresAt);

            $link = app_url('/auth/reset-password?token=' . urlencode($plainToken));
            $subject = 'AGRISORT-AI - Đặt lại mật khẩu';
            $body = '<p>Xin cho ' . e((string) ($user['full_name'] ?? '')) . ',</p>'
                . '<p>Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản AGRISORT-AI. Liên kết có hiệu lực trong 60 phút:</p>'
                . '<p><a href="' . e($link) . '">' . e($link) . '</a></p>';
            MailService::send((string) $user['email'], $subject, $body);
        }

        $this->ok('Nếu email tồn tại trong hệ thống, liên kết đặt lại mật khẩu đã được gửi.');
    }

    public function resetPassword(array $params = []): void
    {
        $payload = $this->payload();
        $token = trim((string) ($payload['token'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $confirmPassword = (string) ($payload['password_confirmation'] ?? '');

        if ($token === '' || $password === '' || $confirmPassword === '') {
            $this->fail('Thiếu dữ liệu đặt lại mật khẩu.', 422);
            return;
        }

        if (strlen($password) < 8) {
            $this->fail('Mật khẩu mới phải có ít nhất 8 ký tự.', 422);
            return;
        }
        if ($password !== $confirmPassword) {
            $this->fail('Xác nhận mật khẩu không khớp.', 422);
            return;
        }

        $record = User::verifyPasswordReset(hash('sha256', $token));
        if (!$record) {
            $this->fail('Token đặt lại mật khẩu không hợp lệ.', 422);
            return;
        }

        User::updatePassword((int) $record['user_id'], password_hash($password, PASSWORD_DEFAULT));
        User::markPasswordResetUsed((int) $record['id']);

        $this->ok('Đặt lại mật khẩu thành công.');
    }

    /**
     * @return array<string,mixed>
     */
    private function buildUserPayload(int $userId): array
    {
        $user = User::findById($userId);
        if (!$user) {
            return [
                'id' => $userId,
            ];
        }

        $role = (string) ($user['role'] ?? '');
        $out = [
            'id' => (int) $user['id'],
            'full_name' => (string) $user['full_name'],
            'email' => (string) $user['email'],
            'role' => $role,
            'status' => (string) $user['status'],
            'role_label' => User::roleLabel($role, 'vi'),
            'vip_until' => trim((string) ($user['vip_until'] ?? '')) !== '' ? (string) $user['vip_until'] : null,
            'vip_until_label' => MembershipService::formatVipUntil((string) ($user['vip_until'] ?? '')),
            'is_vip' => MembershipService::hasVip($user),
        ];

        if (in_array($role, ['partner', 'farmer'], true)) {
            $out['partner_profile'] = PartnerProfile::byUserId((int) $user['id']);
        }

        return $out;
    }
}
