<?php
declare(strict_types=1);

use App\Controllers\AppAdminApiController;
use App\Controllers\AppAuthApiController;
use App\Controllers\AppPartnerApiController;
use App\Controllers\AppPublicApiController;
use App\Controllers\AppSupplyApiController;
use App\Controllers\MachineApiController;

$router->post('/api/v1/machine/lots', [MachineApiController::class, 'createLot'], ['csrf' => false]);

// App API - Auth
$router->post('/api/v1/app/auth/register', [AppAuthApiController::class, 'register'], ['csrf' => false]);
$router->post('/api/v1/app/auth/login', [AppAuthApiController::class, 'login'], ['csrf' => false]);
$router->post('/api/v1/app/auth/logout', [AppAuthApiController::class, 'logout'], ['csrf' => false]);
$router->get('/api/v1/app/auth/me', [AppAuthApiController::class, 'me'], ['csrf' => false]);
$router->put('/api/v1/app/auth/profile', [AppAuthApiController::class, 'updateProfile'], ['csrf' => false]);
$router->put('/api/v1/app/auth/password', [AppAuthApiController::class, 'updatePassword'], ['csrf' => false]);
$router->post('/api/v1/app/auth/forgot-password', [AppAuthApiController::class, 'forgotPassword'], ['csrf' => false]);
$router->post('/api/v1/app/auth/reset-password', [AppAuthApiController::class, 'resetPassword'], ['csrf' => false]);

// App API - Public
$router->get('/api/v1/app/blog', [AppPublicApiController::class, 'blogIndex'], ['csrf' => false]);
$router->get('/api/v1/app/blog/{slug}', [AppPublicApiController::class, 'blogShow'], ['csrf' => false]);
$router->get('/api/v1/app/trace/lookup', [AppPublicApiController::class, 'traceLookup'], ['csrf' => false]);
$router->get('/api/v1/app/trace/{qr_token}', [AppPublicApiController::class, 'traceShow'], ['csrf' => false]);

// App API - Partner/Farmer
$router->get('/api/v1/app/partner/lots', [AppPartnerApiController::class, 'listLots'], ['csrf' => false]);
$router->post('/api/v1/app/partner/lots', [AppPartnerApiController::class, 'createLot'], ['csrf' => false]);
$router->get('/api/v1/app/partner/lots/{id}', [AppPartnerApiController::class, 'showLot'], ['csrf' => false]);
$router->put('/api/v1/app/partner/lots/{id}', [AppPartnerApiController::class, 'updateLot'], ['csrf' => false]);
$router->delete('/api/v1/app/partner/lots/{id}', [AppPartnerApiController::class, 'deleteLot'], ['csrf' => false]);
$router->post('/api/v1/app/partner/lots/{id}/regenerate-qr', [AppPartnerApiController::class, 'regenerateLotQr'], ['csrf' => false]);

$router->get('/api/v1/app/partner/packages', [AppPartnerApiController::class, 'listPackages'], ['csrf' => false]);
$router->get('/api/v1/app/partner/packages/{id}', [AppPartnerApiController::class, 'showPackage'], ['csrf' => false]);
$router->post('/api/v1/app/partner/packages', [AppPartnerApiController::class, 'createPackage'], ['csrf' => false]);
$router->put('/api/v1/app/partner/packages/{id}', [AppPartnerApiController::class, 'updatePackage'], ['csrf' => false]);
$router->delete('/api/v1/app/partner/packages/{id}', [AppPartnerApiController::class, 'deletePackage'], ['csrf' => false]);
$router->post('/api/v1/app/partner/packages/{id}/regenerate-qr', [AppPartnerApiController::class, 'regeneratePackageQr'], ['csrf' => false]);

// App API - Supply actor
$router->get('/api/v1/app/supply/assignments', [AppSupplyApiController::class, 'listAssignments'], ['csrf' => false]);
$router->get('/api/v1/app/supply/events', [AppSupplyApiController::class, 'listEvents'], ['csrf' => false]);
$router->post('/api/v1/app/supply/events', [AppSupplyApiController::class, 'createEvent'], ['csrf' => false]);
$router->put('/api/v1/app/supply/events/{id}', [AppSupplyApiController::class, 'updateEvent'], ['csrf' => false]);

// App API - Admin
$router->get('/api/v1/app/admin/dashboard', [AppAdminApiController::class, 'dashboard'], ['csrf' => false]);
$router->get('/api/v1/app/admin/users', [AppAdminApiController::class, 'users'], ['csrf' => false]);
$router->post('/api/v1/app/admin/users/{id}/status', [AppAdminApiController::class, 'updateUserStatus'], ['csrf' => false]);
$router->post('/api/v1/app/admin/users/{id}/vip', [AppAdminApiController::class, 'updateUserVip'], ['csrf' => false]);
$router->post('/api/v1/app/admin/partners/{id}/approve', [AppAdminApiController::class, 'approvePartner'], ['csrf' => false]);
$router->get('/api/v1/app/admin/settings', [AppAdminApiController::class, 'settings'], ['csrf' => false]);
$router->post('/api/v1/app/admin/settings', [AppAdminApiController::class, 'updateSettings'], ['csrf' => false]);
$router->get('/api/v1/app/admin/cms/{section_key}', [AppAdminApiController::class, 'showCms'], ['csrf' => false]);
$router->post('/api/v1/app/admin/cms/{section_key}', [AppAdminApiController::class, 'saveCms'], ['csrf' => false]);
$router->get('/api/v1/app/admin/blog', [AppAdminApiController::class, 'blogIndex'], ['csrf' => false]);
$router->post('/api/v1/app/admin/blog', [AppAdminApiController::class, 'saveBlog'], ['csrf' => false]);
$router->get('/api/v1/app/admin/api-keys', [AppAdminApiController::class, 'apiKeysIndex'], ['csrf' => false]);
$router->post('/api/v1/app/admin/api-keys', [AppAdminApiController::class, 'createApiKey'], ['csrf' => false]);
$router->post('/api/v1/app/admin/lots/{id}/publish', [AppAdminApiController::class, 'setLotPublish'], ['csrf' => false]);
$router->get('/api/v1/app/admin/trace/assignments', [AppAdminApiController::class, 'listTraceAssignments'], ['csrf' => false]);
$router->post('/api/v1/app/admin/trace/assignments', [AppAdminApiController::class, 'saveTraceAssignment'], ['csrf' => false]);
$router->delete('/api/v1/app/admin/trace/assignments/{id}', [AppAdminApiController::class, 'deleteTraceAssignment'], ['csrf' => false]);
$router->get('/api/v1/app/admin/trace/events', [AppAdminApiController::class, 'listTraceEvents'], ['csrf' => false]);
