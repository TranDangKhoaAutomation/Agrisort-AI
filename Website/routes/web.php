<?php
declare(strict_types=1);

use App\Controllers\AccountController;
use App\Controllers\AdminController;
use App\Controllers\AppApiDocsController;
use App\Controllers\AuthController;
use App\Controllers\BlogController;
use App\Controllers\BillingController;
use App\Controllers\ContactController;
use App\Controllers\DashboardController;
use App\Controllers\DocsController;
use App\Controllers\HomeController;
use App\Controllers\PartnerController;
use App\Controllers\SupplyController;
use App\Controllers\TraceController;

$router->get('/', [HomeController::class, 'index'], ['csrf' => false]);
$router->get('/dashboard', [DashboardController::class, 'index'], ['auth' => true, 'csrf' => false, 'ensure_active' => true]);
$router->get('/vi', [HomeController::class, 'setVi'], ['csrf' => false]);
$router->get('/en', [HomeController::class, 'setEn'], ['csrf' => false]);

$router->get('/blog', [BlogController::class, 'index'], ['csrf' => false]);
$router->get('/blog/{slug}', [BlogController::class, 'show'], ['csrf' => false]);
$router->get('/billing', [BillingController::class, 'index'], ['csrf' => false]);
$router->post('/billing/request', [BillingController::class, 'requestVip']);
$router->get('/trace', [TraceController::class, 'index'], ['csrf' => false]);
$router->post('/trace/scan-upload', [TraceController::class, 'scanUpload']);
$router->get('/trace/lookup-token', [TraceController::class, 'lookupToken'], ['csrf' => false]);
$router->get('/trace/{qr_token}', [TraceController::class, 'show'], ['csrf' => false]);
$router->get('/docs', [DocsController::class, 'index'], ['csrf' => false]);
$router->get('/docs/openapi.json', [DocsController::class, 'openapi'], ['csrf' => false]);
$router->get('/docs/postman.json', [DocsController::class, 'postman'], ['csrf' => false]);
$router->get('/api-docs/app', [AppApiDocsController::class, 'index'], ['csrf' => false]);
$router->get('/api-docs/app/openapi.json', [AppApiDocsController::class, 'openapi'], ['csrf' => false]);
$router->get('/api-docs/app/postman.json', [AppApiDocsController::class, 'postman'], ['csrf' => false]);

$router->post('/contact', [ContactController::class, 'contact']);
$router->post('/demo-request', [ContactController::class, 'demoRequest']);

$router->get('/auth/login', [AuthController::class, 'showLogin'], ['csrf' => false]);
$router->post('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/register', [AuthController::class, 'register']);
$router->post('/partner/register', [AuthController::class, 'registerPartner']);
$router->post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
$router->get('/auth/reset-password', [AuthController::class, 'showResetPassword'], ['csrf' => false]);
$router->post('/auth/reset-password', [AuthController::class, 'resetPassword']);
$router->post('/auth/logout', [AuthController::class, 'logout']);

$router->get('/account', [AccountController::class, 'index'], ['auth' => true, 'csrf' => false]);
$router->post('/account/profile', [AccountController::class, 'updateProfile'], ['auth' => true]);
$router->post('/account/password', [AccountController::class, 'updatePassword'], ['auth' => true]);

$partnerRoles = ['partner', 'farmer'];
$router->get('/partner/dashboard', null, ['redirect_to' => '/dashboard', 'redirect_status' => 301, 'csrf' => false]);
$router->get('/partner', null, ['redirect_to' => '/dashboard', 'redirect_status' => 301, 'csrf' => false]);
$router->get('/partner/lots', [PartnerController::class, 'lots'], ['auth' => true, 'roles' => $partnerRoles, 'csrf' => false, 'ensure_active' => true]);
$router->post('/partner/lots', [PartnerController::class, 'createLot'], ['auth' => true, 'roles' => $partnerRoles, 'ensure_active' => true]);
$router->put('/partner/lots/{id}', [PartnerController::class, 'updateLot'], ['auth' => true, 'roles' => $partnerRoles, 'ensure_active' => true]);
$router->delete('/partner/lots/{id}', [PartnerController::class, 'deleteLot'], ['auth' => true, 'roles' => $partnerRoles, 'ensure_active' => true]);
$router->post('/partner/lots/import-csv', [PartnerController::class, 'importCsv'], ['auth' => true, 'roles' => $partnerRoles, 'ensure_active' => true]);
$router->post('/partner/lots/{id}/regenerate-qr', [PartnerController::class, 'regenerateQr'], ['auth' => true, 'roles' => $partnerRoles, 'ensure_active' => true]);
$router->get('/partner/qr', [PartnerController::class, 'qrPage'], ['auth' => true, 'roles' => $partnerRoles, 'csrf' => false, 'ensure_active' => true]);
$router->post('/partner/packages', [PartnerController::class, 'createPackage'], ['auth' => true, 'roles' => $partnerRoles, 'ensure_active' => true]);
$router->put('/partner/packages/{id}', [PartnerController::class, 'updatePackage'], ['auth' => true, 'roles' => $partnerRoles, 'ensure_active' => true]);
$router->delete('/partner/packages/{id}', [PartnerController::class, 'deletePackage'], ['auth' => true, 'roles' => $partnerRoles, 'ensure_active' => true]);
$router->post('/partner/packages/{id}/regenerate-qr', [PartnerController::class, 'regeneratePackageQr'], ['auth' => true, 'roles' => $partnerRoles, 'ensure_active' => true]);

$supplyRoles = ['transporter', 'warehouse', 'seller'];
$router->get('/supply/dashboard', null, ['redirect_to' => '/dashboard', 'redirect_status' => 301, 'csrf' => false]);
$router->post('/supply/events', [SupplyController::class, 'createEvent'], ['auth' => true, 'roles' => $supplyRoles, 'ensure_active' => true]);
$router->put('/supply/events/{id}', [SupplyController::class, 'updateEvent'], ['auth' => true, 'roles' => $supplyRoles, 'ensure_active' => true]);

$router->get('/dashboard/admin', [AdminController::class, 'dashboard'], ['auth' => true, 'role' => 'admin', 'csrf' => false, 'ensure_active' => true]);
$router->get('/dashboard/admin/trace', [AdminController::class, 'trace'], ['auth' => true, 'role' => 'admin', 'csrf' => false, 'ensure_active' => true]);
$router->get('/dashboard/admin/users', [AdminController::class, 'users'], ['auth' => true, 'role' => 'admin', 'csrf' => false, 'ensure_active' => true]);
$router->get('/dashboard/admin/blog', [AdminController::class, 'blog'], ['auth' => true, 'role' => 'admin', 'csrf' => false, 'ensure_active' => true]);
$router->get('/dashboard/admin/studio', [AdminController::class, 'studioPage'], ['auth' => true, 'role' => 'admin', 'csrf' => false, 'ensure_active' => true]);
$router->get('/dashboard/admin/settings', [AdminController::class, 'settingsPage'], ['auth' => true, 'role' => 'admin', 'csrf' => false, 'ensure_active' => true]);
$router->get('/dashboard/admin/api-keys', [AdminController::class, 'apiKeysPage'], ['auth' => true, 'role' => 'admin', 'csrf' => false, 'ensure_active' => true]);
$router->post('/dashboard/admin/users/{id}/status', [AdminController::class, 'updateUserStatus'], ['auth' => true, 'role' => 'admin', 'ensure_active' => true]);
$router->post('/dashboard/admin/users/{id}/vip', [AdminController::class, 'updateUserVip'], ['auth' => true, 'role' => 'admin', 'ensure_active' => true]);
$router->post('/dashboard/admin/trace/assignments', [AdminController::class, 'saveTraceAssignment'], ['auth' => true, 'role' => 'admin', 'ensure_active' => true]);
$router->delete('/dashboard/admin/trace/assignments/{id}', [AdminController::class, 'deleteTraceAssignment'], ['auth' => true, 'role' => 'admin', 'ensure_active' => true]);
$router->post('/dashboard/admin/partners/{id}/approve', [AdminController::class, 'approvePartner'], ['auth' => true, 'role' => 'admin', 'ensure_active' => true]);
$router->post('/dashboard/admin/settings', [AdminController::class, 'updateSettings'], ['auth' => true, 'role' => 'admin', 'ensure_active' => true]);
$router->post('/dashboard/admin/cms/{section_key}', [AdminController::class, 'updateCms'], ['auth' => true, 'role' => 'admin', 'ensure_active' => true]);
$router->post('/dashboard/admin/blog', [AdminController::class, 'saveBlog'], ['auth' => true, 'role' => 'admin', 'ensure_active' => true]);
$router->post('/dashboard/admin/api-keys', [AdminController::class, 'createApiKey'], ['auth' => true, 'role' => 'admin', 'ensure_active' => true]);
$router->post('/dashboard/admin/lots/{id}/publish', [AdminController::class, 'setLotPublish'], ['auth' => true, 'role' => 'admin', 'ensure_active' => true]);

$router->get('/admin', null, ['redirect_to' => '/dashboard', 'redirect_status' => 301, 'csrf' => false]);
$router->get('/admin/dashboard', null, ['redirect_to' => '/dashboard', 'redirect_status' => 301, 'csrf' => false]);
$router->get('/admin/trace', null, ['redirect_to' => '/dashboard/admin/trace', 'redirect_status' => 301, 'csrf' => false]);
$router->get('/admin/users', null, ['redirect_to' => '/dashboard/admin/users', 'redirect_status' => 301, 'csrf' => false]);
$router->get('/admin/blog', null, ['redirect_to' => '/dashboard/admin/blog', 'redirect_status' => 301, 'csrf' => false]);
$router->get('/admin/studio', null, ['redirect_to' => '/dashboard/admin/studio', 'redirect_status' => 301, 'csrf' => false]);
$router->get('/admin/settings', null, ['redirect_to' => '/dashboard/admin/settings', 'redirect_status' => 301, 'csrf' => false]);
$router->get('/admin/api-keys', null, ['redirect_to' => '/dashboard/admin/api-keys', 'redirect_status' => 301, 'csrf' => false]);
$router->post('/admin/users/{id}/status', null, ['redirect_to' => '/dashboard/admin/users/{id}/status', 'redirect_status' => 308, 'csrf' => false]);
$router->post('/admin/users/{id}/vip', null, ['redirect_to' => '/dashboard/admin/users/{id}/vip', 'redirect_status' => 308, 'csrf' => false]);
$router->post('/admin/trace/assignments', null, ['redirect_to' => '/dashboard/admin/trace/assignments', 'redirect_status' => 308, 'csrf' => false]);
$router->delete('/admin/trace/assignments/{id}', null, ['redirect_to' => '/dashboard/admin/trace/assignments/{id}', 'redirect_status' => 308, 'csrf' => false]);
$router->post('/admin/partners/{id}/approve', null, ['redirect_to' => '/dashboard/admin/partners/{id}/approve', 'redirect_status' => 308, 'csrf' => false]);
$router->post('/admin/settings', null, ['redirect_to' => '/dashboard/admin/settings', 'redirect_status' => 308, 'csrf' => false]);
$router->post('/admin/cms/{section_key}', null, ['redirect_to' => '/dashboard/admin/cms/{section_key}', 'redirect_status' => 308, 'csrf' => false]);
$router->post('/admin/blog', null, ['redirect_to' => '/dashboard/admin/blog', 'redirect_status' => 308, 'csrf' => false]);
$router->post('/admin/api-keys', null, ['redirect_to' => '/dashboard/admin/api-keys', 'redirect_status' => 308, 'csrf' => false]);
$router->post('/admin/lots/{id}/publish', null, ['redirect_to' => '/dashboard/admin/lots/{id}/publish', 'redirect_status' => 308, 'csrf' => false]);
