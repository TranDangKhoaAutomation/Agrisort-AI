<?php
$title = lang_text((string) $post['title_vi'], (string) ($post['title_en'] ?? ''));
$content = lang_text((string) $post['content_vi'], (string) ($post['content_en'] ?? ''));
$pageTitle = $title;
require BASE_PATH . '/resources/views/layouts/public_header.php';
?>
<section class="container page-section">
  <a class="ghost-link" href="<?= e(app_url('/blog')) ?>">&larr; <?= e(lang_text('Quay lại blog', 'Back to blog')) ?></a>
  <article class="card blog-article-card">
    <div class="blog-article-head">
      <span class="blog-card-tag"><?= e(lang_text('Bài viết nổi bật', 'Featured article')) ?></span>
      <p class="muted"><?= e((string) ($post['published_at'] ?? $post['created_at'])) ?></p>
    </div>
    <h1><?= e($title) ?></h1>
    <div class="post-content"><?= nl2br(e($content)) ?></div>
  </article>
</section>
<?php require BASE_PATH . '/resources/views/layouts/public_footer.php'; ?>
