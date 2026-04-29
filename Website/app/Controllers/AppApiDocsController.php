<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\DocsService;

final class AppApiDocsController extends Controller
{
    public function index(array $params = []): void
    {
        $service = new DocsService();
        $locale = lang();
        $portal = $service->portalData($locale, 'app-api');

        $this->view('public.app_api_docs', [
            'docsVersion' => (string) ($portal['docsVersion'] ?? 'v1'),
            'docsLocale' => $locale,
            'appApiHtml' => (string) ($portal['activeSection']['html'] ?? ''),
            'appRouteMatrix' => $service->routeMatrix($locale, '/api/v1/app/'),
        ]);
    }

    public function openapi(array $params = []): void
    {
        $service = new DocsService();
        $this->json($service->buildOpenApi('vi', '/api/v1/app/'));
    }

    public function postman(array $params = []): void
    {
        $service = new DocsService();
        $this->json($service->buildPostmanCollection('vi', '/api/v1/app/'));
    }
}
