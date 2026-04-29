<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Validator;
use App\Models\PartnerProfile;
use App\Models\User;
use App\Services\AuditService;
use App\Services\MembershipService;

final class AccountController extends Controller
{
    public function index(array $params = []): void
    {
        $user = $this->currentUserRecord();
        if ($user === null) {
            return;
        }

        $profile = null;
        if (in_array((string) ($user['role'] ?? ''), ['partner', 'farmer'], true)) {
            $profile = PartnerProfile::byUserId((int) $user['id']);
        }

        $this->view('account.index', [
            'userData' => $user,
            'partnerProfile' => $profile,
            'avatarInitials' => $this->initials((string) ($user['full_name'] ?? '')),
            'membershipStatus' => MembershipService::traceAccessSnapshot($user, $this->request->ip()),
        ]);
    }

    public function updateProfile(array $params = []): void
    {
        $user = $this->currentUserRecord();
        if ($user === null) {
            return;
        }

        $userId = (int) $user['id'];
        $fullName = trim((string) $this->request->input('full_name', ''));
        $email = strtolower(trim((string) $this->request->input('email', '')));
        $role = (string) ($user['role'] ?? '');

        $errors = Validator::required(
            ['full_name' => $fullName, 'email' => $email],
            ['full_name', 'email']
        );

        if (!Validator::email($email)) {
            $errors['email'] = t('account_error_email_format');
        }

        if (User::existsByEmailExceptId($email, $userId)) {
            $errors['email'] = t('account_error_email_exists');
        }

        $profile = null;
        if (in_array($role, ['partner', 'farmer'], true)) {
            $profile = [
                'organization_name' => trim((string) $this->request->input('organization_name', '')),
                'representative_name' => trim((string) $this->request->input('representative_name', '')),
                'phone' => trim((string) $this->request->input('phone', '')),
                'address' => trim((string) $this->request->input('address', '')),
                'region' => trim((string) $this->request->input('region', '')),
                'tax_code' => trim((string) $this->request->input('tax_code', '')),
            ];

            if ($role === 'partner' && $profile['organization_name'] === '') {
                $errors['organization_name'] = t('account_error_org_required');
            }

            if ($profile['representative_name'] === '') {
                $profile['representative_name'] = $fullName;
            }
        }

        if ($errors) {
            flash('error', t('account_error_update'));
            $this->redirect('/account');
        }

        $oldEmail = (string) $user['email'];
        User::updateAccount($userId, $fullName, $email);
        if ($profile !== null) {
            PartnerProfile::upsert($userId, $profile);
        }

        AuditService::log('update_account_profile', 'users', $userId, [
            'role' => $role,
            'email_changed' => strcasecmp($oldEmail, $email) !== 0,
        ]);

        if (strcasecmp($oldEmail, $email) !== 0) {
            AuditService::log('change_account_email', 'users', $userId, [
                'old_email' => $oldEmail,
                'new_email' => $email,
            ]);

            Auth::logout();
            flash('success', t('account_success_email_relogin'));
            $this->redirect('/auth/login');
        }

        $fresh = User::findById($userId);
        if ($fresh !== null) {
            Auth::login($fresh);
        }

        flash('success', t('account_success_profile'));
        $this->redirect('/account');
    }

    public function updatePassword(array $params = []): void
    {
        $user = $this->currentUserRecord();
        if ($user === null) {
            return;
        }

        $currentPassword = (string) $this->request->input('current_password', '');
        $newPassword = (string) $this->request->input('password', '');
        $confirmPassword = (string) $this->request->input('password_confirmation', '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            flash('error', t('account_error_password_fields'));
            $this->redirect('/account');
        }

        if (!password_verify($currentPassword, (string) $user['password_hash'])) {
            flash('error', t('account_error_current_password'));
            $this->redirect('/account');
        }

        if (strlen($newPassword) < 8) {
            flash('error', t('account_error_new_password_length'));
            $this->redirect('/account');
        }

        if ($newPassword !== $confirmPassword) {
            flash('error', t('account_error_password_confirm'));
            $this->redirect('/account');
        }

        $userId = (int) $user['id'];
        User::updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));
        AuditService::log('change_account_password', 'users', $userId, []);

        flash('success', t('account_success_password'));
        $this->redirect('/account');
    }

    private function currentUserRecord(): ?array
    {
        $userId = (int) (current_user()['id'] ?? 0);
        if ($userId <= 0) {
            Auth::logout();
            flash('error', t('account_error_session'));
            $this->redirect('/auth/login');
        }

        $user = User::findById($userId);
        if ($user === null) {
            Auth::logout();
            flash('error', t('account_error_not_found'));
            $this->redirect('/auth/login');
        }

        return $user;
    }

    private function initials(string $fullName): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', $fullName) ?? '');
        if ($clean === '') {
            return 'U';
        }

        $parts = explode(' ', $clean);
        $first = $this->firstChar($parts[0]);
        $last = count($parts) > 1 ? $this->firstChar($parts[count($parts) - 1]) : '';

        $value = $this->toUpper($first . $last);
        return $value !== '' ? $value : 'U';
    }

    private function firstChar(string $text): string
    {
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_substr')) {
            return (string) mb_substr($text, 0, 1, 'UTF-8');
        }

        return substr($text, 0, 1);
    }

    private function toUpper(string $text): string
    {
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strtoupper')) {
            return (string) mb_strtoupper($text, 'UTF-8');
        }

        return strtoupper($text);
    }
}
