<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Validator;
use App\Models\PartnerProfile;
use App\Models\User;
use App\Services\AuditService;
use App\Services\MailService;

final class AuthController extends Controller
{
    public function showLogin(array $params = []): void
    {
        $locale = lang();

        $this->view('auth.login', [
            'sections' => [
                'auth_login' => load_cms('auth_login', $locale),
                'auth_register' => load_cms('auth_register', $locale),
            ],
        ]);
    }

    public function login(array $params = []): void
    {
        $email = trim((string) $this->request->input('email', ''));
        $password = (string) $this->request->input('password', '');

        $rateKey = 'login:' . $this->request->ip() . ':' . strtolower($email);
        if (!RateLimiter::allow($rateKey, 10, 600)) {
            flash('error', lang_text('Quá nhiều lần thử đăng nhập. ', 'Too many login attempts. Try again in 10 minutes.'));
            $this->redirect('/auth/login');
        }

        $user = User::findByEmail($email);
        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            flash('error', lang_text('Thông tin xác thực không hợp lệ.', 'Invalid credentials.'));
            $this->redirect('/auth/login');
        }

        if ($user['role'] !== 'admin' && $user['status'] !== 'active') {
            flash('error', lang_text('Tài khoản đang chờ quản trị viên phê duyệt.', 'Account is pending admin approval.'));
            $this->redirect('/auth/login');
        }

        Auth::login($user);
        AuditService::log('login', 'users', (int) $user['id'], ['role' => $user['role']]);
        $this->redirect('/dashboard');
    }

    public function logout(array $params = []): void
    {
        AuditService::log('logout', 'users', current_user()['id'] ?? null, []);
        Auth::logout();
        flash('success', lang_text('Đăng xuất thành công.', 'Logged out successfully.'));
        $this->redirect('/');
    }

    public function register(array $params = []): void
    {
        $this->registerAccount(false);
    }

    public function registerPartner(array $params = []): void
    {
        $this->registerAccount(false);
    }

    private function registerAccount(bool $forceLegacyPartnerRole): void
    {
        $fullName = trim((string) $this->request->input('full_name', ''));
        $email = trim((string) $this->request->input('email', ''));
        $password = (string) $this->request->input('password', '');
        $requestedRole = (string) $this->request->input('role', 'farmer');

        $role = $forceLegacyPartnerRole
            ? 'partner'
            : User::normalizeRole($requestedRole);

        $allowedSelfRoles = ['partner', 'farmer', 'transporter', 'warehouse', 'seller'];
        if (!in_array($role, $allowedSelfRoles, true)) {
            $role = 'farmer';
        }

        $profile = [
            'organization_name' => trim((string) $this->request->input('organization_name', '')),
            'representative_name' => trim((string) $this->request->input('representative_name', $fullName)),
            'phone' => trim((string) $this->request->input('phone', '')),
            'address' => trim((string) $this->request->input('address', '')),
            'region' => trim((string) $this->request->input('region', '')),
            'tax_code' => trim((string) $this->request->input('tax_code', '')),
        ];

        $required = ['full_name', 'email', 'password'];
        $requiredPayload = [
            'full_name' => $fullName,
            'email' => $email,
            'password' => $password,
        ];
        if ($role === 'partner') {
            $required[] = 'organization_name';
            $requiredPayload['organization_name'] = $profile['organization_name'];
        }

        $errors = Validator::required($requiredPayload, $required);

        if (!Validator::email($email)) {
            $errors['email'] = lang_text('Định dạng email không hợp lệ.', 'Invalid email format.');
        }

        if (strlen($password) < 8) {
            $errors['password'] = lang_text('Mật khẩu phải có ít nhất 8 ký tự.', 'Password must be at least 8 characters.');
        }

        if (User::findByEmail($email)) {
            $errors['email'] = lang_text('Email đã tồn tại.', 'Email already exists.');
        }

        if ($errors) {
            with_old(array_merge(['full_name' => $fullName, 'email' => $email, 'role' => $role], $profile));
            flash('error', lang_text('Đăng ký không thành công. ', 'Registration failed. Please check your inputs.'));
            $this->redirect('/auth/login?mode=register');
        }

        $userId = User::createAccount($fullName, $email, password_hash($password, PASSWORD_DEFAULT), $role);

        if (in_array($role, ['partner', 'farmer'], true)) {
            PartnerProfile::upsert($userId, $profile);
        }

        AuditService::log('register_actor', 'users', $userId, ['email' => $email, 'role' => $role]);

        flash('success', lang_text('Đăng ký thành công. ', 'Registration succeeded. Your account is pending approval.'));
        $this->redirect('/auth/login');
    }

    public function forgotPassword(array $params = []): void
    {
        $email = trim((string) $this->request->input('email', ''));
        $rateKey = 'forgot:' . $this->request->ip() . ':' . strtolower($email);
        if (!RateLimiter::allow($rateKey, 6, 600)) {
            flash('error', lang_text('Quá nhiều lần thử thiết lập lại. ', 'Too many reset attempts. Try again later.'));
            $this->redirect('/auth/login');
        }

        $user = User::findByEmail($email);
        if (!$user) {
            flash('success', lang_text('Nếu email này tồn tại thì liên kết đặt lại đã được gửi.', 'If this email exists, a reset link has been sent.'));
            $this->redirect('/auth/login');
        }

        $plainToken = bin2hex(random_bytes(24));
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        User::savePasswordReset((int) $user['id'], $tokenHash, $expiresAt);

        $link = app_url('/auth/reset-password?token=' . urlencode($plainToken));
        $subject = lang_text('AGRISORT-AI - Đặt lại mật khẩu', 'AGRISORT-AI - Password reset');
        $body = '<p>' . e(lang_text('Xin chào', 'Hello')) . ' ' . e((string) $user['full_name']) . ',</p>'
            . '<p>' . e(lang_text('Bạn đã yêu cầu đặt lại mật khẩu. ', 'You requested a password reset. This link is valid for 60 minutes:')) . '</p>'
            . '<p><a href="' . e($link) . '">' . e($link) . '</a></p>';

        MailService::send((string) $user['email'], $subject, $body);
        AuditService::log('forgot_password', 'users', (int) $user['id'], []);

        flash('success', lang_text('Nếu email này tồn tại thì liên kết đặt lại đã được gửi.', 'If this email exists, a reset link has been sent.'));
        $this->redirect('/auth/login');
    }

    public function showResetPassword(array $params = []): void
    {
        $token = trim((string) $this->request->input('token', ''));
        $this->view('auth.reset_password', ['token' => $token]);
    }

    public function resetPassword(array $params = []): void
    {
        $token = trim((string) $this->request->input('token', ''));
        $password = (string) $this->request->input('password', '');
        $confirm = (string) $this->request->input('password_confirmation', '');

        if ($token === '' || strlen($password) < 8 || $password !== $confirm) {
            flash('error', lang_text('Tải trọng đặt lại mật khẩu không hợp lệ.', 'Invalid password reset payload.'));
            $this->redirect('/auth/reset-password?token=' . urlencode($token));
        }

        $record = User::verifyPasswordReset(hash('sha256', $token));
        if (!$record) {
            flash('error', lang_text('Mã thông báo không hợp lệ hoặc đã hết hạn.', 'Token is invalid or expired.'));
            $this->redirect('/auth/login');
        }

        User::updatePassword((int) $record['user_id'], password_hash($password, PASSWORD_DEFAULT));
        User::markPasswordResetUsed((int) $record['id']);
        AuditService::log('reset_password', 'users', (int) $record['user_id'], []);

        flash('success', lang_text('Đã cập nhật mật khẩu thành công.', 'Password updated successfully.'));
        $this->redirect('/auth/login');
    }
}




