<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\BlogPost;

final class BlogController extends Controller
{
    public function index(array $params = []): void
    {
        $posts = BlogPost::published(100);
        $this->view('public.blog_index', [
            'posts' => $posts,
            'locale' => lang(),
        ]);
    }

    public function show(array $params): void
    {
        $slug = (string) ($params['slug'] ?? '');
        $post = BlogPost::bySlug($slug);
        if (!$post || $post['status'] !== 'published') {
            $this->view('public.not_found', [], 404);
            return;
        }

        $this->view('public.blog_show', [
            'post' => $post,
            'locale' => lang(),
        ]);
    }
}
