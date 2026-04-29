<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\DocsService;

final class DocsController extends Controller
{
    public function index(array $params = []): void
    {
        $service = new DocsService();
        $section = trim((string) $this->request->input('section', ''));
        $locale = lang();

        $this->view('public.docs', $service->portalData($locale, $section));
    }

    public function openapi(array $params = []): void
    {
        $service = new DocsService();
        $this->json($service->buildOpenApi('vi'));
    }

    public function postman(array $params = []): void
    {
        $service = new DocsService();
        $this->json($service->buildPostmanCollection('vi'));
    }
}
