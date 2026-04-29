<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\BlogPost;
use App\Support\PublicContent;

final class HomeController extends Controller
{
    private function preferredRedirect(): string
    {
        $redirect = trim((string) $this->request->input('redirect', '/'));
        if ($redirect === '' || $redirect[0] !== '/') {
            return '/';
        }
        if (str_starts_with($redirect, '/vi') || str_starts_with($redirect, '/en')) {
            return '/';
        }
        return $redirect;
    }

    public function index(array $params = []): void
    {
        $locale = lang();
        $sections = [];
        $defaults = PublicContent::localizedSectionDefaults($locale);
        $blogPosts = [];

        foreach (PublicContent::sectionKeys() as $sectionKey) {
            $sections[$sectionKey] = array_merge(
                $defaults[$sectionKey] ?? [],
                load_cms($sectionKey, $locale)
            );
        }

        try {
            $blogPosts = BlogPost::published(3);
        } catch (\Throwable $e) {
            error_log('HomeController blog preload failed: ' . $e->getMessage());
        }

        $this->view('public.home', [
            'locale' => $locale,
            'sections' => $sections,
            'publicContent' => PublicContent::homeViewData($locale),
            'blogPosts' => $blogPosts,
        ]);
    }

    public function dashboard(array $params = []): void
    {
        $this->redirect('/dashboard');
    }

    public function setVi(array $params = []): void
    {
        $_SESSION['lang'] = 'vi';
        $this->redirect($this->preferredRedirect());
    }

    public function setEn(array $params = []): void
    {
        $_SESSION['lang'] = 'en';
        $this->redirect($this->preferredRedirect());
    }
}
