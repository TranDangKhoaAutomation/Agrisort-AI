<?php
$pageTitle = lang_text('Blog AGRISORT-AI', 'AGRISORT-AI Blog');
$postCount = count(is_array($posts) ? $posts : []);
require BASE_PATH . '/resources/views/layouts/public_header.php';
?>
<section class="container page-section">
  <div class="section-head section-head-inline">
    <div>
      <p class="section-kicker"><?= e(lang_text('Nội dung', 'Insights')) ?></p>
      <h1><?= e($pageTitle) ?></h1>
      <p class="page-lead"><?= e(lang_text('Tin tức, cập nhật sản phẩm và chia sẻ vận hành được trình bày với phong cách trực quan hơn.', 'News, product updates, and operations insights presented in a more vivid visual style.')) ?></p>
    </div>
    <span class="status-pill blog-count-pill"><?= e((string) $postCount) ?> <?= e(lang_text('bài viết', 'posts')) ?></span>
  </div>
  <div class="card-list blog-grid">
    <?php if (!$posts): ?>
      <article class="card empty-state-card">
        <span class="empty-state-code">01</span>
        <h2><?= e(lang_text('Chưa có bài viết công khai', 'No published posts yet')) ?></h2>
        <p><?= e(lang_text('Khi có bài viết mới, khu vực này sẽ hiển thị theo dạng thẻ nổi bật.', 'When new articles are published, they will appear here as featured cards.')) ?></p>
      </article>
    <?php else: ?>
      <?php foreach ($posts as $post): ?>
        <article class="card blog-card">
          <div class="blog-card-meta">
            <span class="blog-card-date"><?= e((string) ($post['published_at'] ?? $post['created_at'])) ?></span>
            <span class="blog-card-tag">AGRISORT-AI</span>
          </div>
          <h2><a href="<?= e(app_url('/blog/' . $post['slug'])) ?>"><?= e(lang_text((string) $post['title_vi'], (string) ($post['title_en'] ?? ''))) ?></a></h2>
          <p><?= e(lang_text((string) $post['excerpt_vi'], (string) ($post['excerpt_en'] ?? ''))) ?></p>
          <a class="ghost-link blog-readmore" href="<?= e(app_url('/blog/' . $post['slug'])) ?>"><?= e(lang_text('Đọc chi tiết', 'Read more')) ?></a>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>
<?php require BASE_PATH . '/resources/views/layouts/public_footer.php'; ?>
