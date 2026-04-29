<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;

final class DashboardController extends Controller
{
    public function index(array $params = []): void
    {
        if (!Auth::check()) {
            $this->redirect('/auth/login');
        }

        $role = (string) (Auth::user()['role'] ?? '');

        if ($role === 'admin') {
            (new AdminController($this->request))->dashboard($params);
            return;
        }

        if (in_array($role, ['partner', 'farmer'], true)) {
            (new PartnerController($this->request))->dashboard($params);
            return;
        }

        if (in_array($role, ['transporter', 'warehouse', 'seller'], true)) {
            (new SupplyController($this->request))->dashboard($params);
            return;
        }

        flash('error', lang_text('Khong co trang tong quan nao cho vai tro cua ban.', 'No dashboard is available for your role.'));
        $this->redirect('/');
    }
}
