<?php
$pageTitle = 'AGRISORT-AI';
require BASE_PATH . '/resources/views/layouts/header.php';

$sections = is_array($sections ?? null) ? $sections : \App\Support\PublicContent::localizedSectionDefaults(lang());
$publicContent = is_array($publicContent ?? null) ? $publicContent : \App\Support\PublicContent::homeViewData(lang());
$blogPosts = is_array($blogPosts ?? null) ? $blogPosts : [];

$splitLines = static function (?string $text): array {
    $rows = preg_split('/\R+/u', trim((string) $text)) ?: [];

    return array_values(array_filter(array_map('trim', $rows), static fn (string $row): bool => $row !== ''));
};

$photoLibrary = [
    'mango' => [
        'path' => app_url('/public/uploads/demo/mango-demo.jpg'),
        'alt' => lang_text('Ảnh chụp thực tế xoài sau thu hoạch trong thùng chứa', 'Real photo of harvested mangoes in a crate'),
        'caption' => lang_text('Xoài sau thu hoạch trong khâu đóng thùng', 'Harvested mangoes prepared for boxing'),
        'hero_label' => lang_text('Xoài đóng thùng', 'Boxed mangoes'),
        'note' => lang_text('Phù hợp bối cảnh HTX, trạm thu mua và cơ sở sơ chế.', 'Suitable for cooperatives, buying stations, and post-harvest facilities.'),
    ],
    'mango_harvest' => [
        'path' => app_url('/public/uploads/demo/mango-harvest-demo.jpg'),
        'alt' => lang_text('Ảnh chụp thực tế thu hoạch xoài tại vườn', 'Real photo of mango harvesting in an orchard'),
        'caption' => lang_text('Thu hoạch xoài trực tiếp tại vườn', 'Mango harvesting directly in the orchard'),
        'hero_label' => lang_text('Thu hoạch xoài', 'Mango harvest'),
        'note' => lang_text('Bổ sung ngữ cảnh đầu vào trước khâu thu mua và sơ chế.', 'Adds field context before collection and post-harvest handling.'),
    ],
    'dragonfruit' => [
        'path' => app_url('/public/uploads/demo/dragonfruit-demo.jpg'),
        'alt' => lang_text('Ảnh chụp thực tế thanh long tại quầy trưng bày', 'Real photo of dragon fruit at a market stall'),
        'caption' => lang_text('Thanh long với bề mặt và màu sắc đặc trưng', 'Dragon fruit with distinct surface and color patterns'),
        'hero_label' => lang_text('Thanh long', 'Dragon fruit'),
        'note' => lang_text('Hữu ích cho bài toán nhận diện màu sắc và khuyết tật bề mặt.', 'Useful for color and surface-defect recognition scenarios.'),
    ],
    'dragonfruit_market' => [
        'path' => app_url('/public/uploads/demo/red-dragonfruit-market-demo.jpg'),
        'alt' => lang_text('Ảnh chụp thực tế thanh long đỏ tại quầy chợ', 'Real photo of red dragon fruit at a market stall'),
        'caption' => lang_text('Thanh long đỏ trong bối cảnh thương mại ngoài chợ', 'Red dragon fruit in an outdoor market setting'),
        'hero_label' => lang_text('Thanh long đỏ', 'Red dragon fruit'),
        'note' => lang_text('Phản ánh bối cảnh tiêu thụ và trưng bày thực tế ngoài thị trường.', 'Reflects real commercial display conditions in the market.'),
    ],
    'orange' => [
        'path' => app_url('/public/uploads/demo/orange-demo.jpg'),
        'alt' => lang_text('Ảnh chụp thực tế cam trên cây', 'Real photo of oranges on the tree'),
        'caption' => lang_text('Cam tại vườn, gắn với dữ liệu vùng trồng', 'Oranges in the orchard linked to origin data'),
        'hero_label' => lang_text('Cam tại vườn', 'Oranges in orchard'),
        'note' => lang_text('Phù hợp thông tin truy xuất nguồn gốc theo vùng trồng và thời điểm thu hoạch.', 'Suitable for origin and harvest-date traceability data.'),
    ],
    'avocado' => [
        'path' => app_url('/public/uploads/demo/avocado-demo.jpg'),
        'alt' => lang_text('Ảnh chụp thực tế quả bơ trên cây', 'Real photo of avocados on the tree'),
        'caption' => lang_text('Bơ với hình dáng không đồng nhất giữa các quả', 'Avocados with non-uniform shapes across fruits'),
        'hero_label' => lang_text('Bơ trên cây', 'Avocados on tree'),
        'note' => lang_text('Hữu ích cho việc mở rộng dữ liệu huấn luyện theo từng loại nông sản.', 'Useful when extending datasets across produce types.'),
    ],
    'avocado_harvest' => [
        'path' => app_url('/public/uploads/demo/avocado-harvest-demo.jpg'),
        'alt' => lang_text('Ảnh chụp thực tế bơ vừa thu hoạch', 'Real photo of freshly harvested avocados'),
        'caption' => lang_text('Bơ sau thu hoạch với trạng thái chất lượng khác nhau', 'Freshly harvested avocados with varying quality states'),
        'hero_label' => lang_text('Bơ vừa thu hoạch', 'Harvested avocados'),
        'note' => lang_text('Phù hợp ngữ cảnh đánh giá độ đồng đều sau thu hoạch.', 'Matches post-harvest consistency assessment scenarios.'),
    ],
    'avocado_tree_multi' => [
        'path' => app_url('/public/uploads/demo/avocado-tree-multi-demo.jpg'),
        'alt' => lang_text('Ảnh chụp thực tế cây bơ sai quả', 'Real photo of an avocado tree with multiple fruits'),
        'caption' => lang_text('Cây bơ nhiều quả trong bối cảnh canh tác thực tế', 'An avocado tree with many fruits in a real farm setting'),
        'hero_label' => lang_text('Cây bơ nhiều quả', 'Heavy avocado tree'),
        'note' => lang_text('Tạo chiều sâu hình ảnh cho giai đoạn canh tác và theo dõi mùa vụ.', 'Adds visual depth for cultivation and seasonal monitoring contexts.'),
    ],
    'banana' => [
        'path' => app_url('/public/uploads/demo/banana-demo.jpg'),
        'alt' => lang_text('Ảnh chụp thực tế chuối tại quầy hàng', 'Real photo of bananas at a produce stand'),
        'caption' => lang_text('Chuối theo buồng và cụm quả trong khâu thương mại', 'Bananas grouped for market-ready handling'),
        'hero_label' => lang_text('Chuối thương mại', 'Market bananas'),
        'note' => lang_text('Gợi mở khả năng mở rộng sang các nông sản có cấu trúc cụm hoặc buồng.', 'Illustrates expansion toward clustered produce categories.'),
    ],
    'banana_tree' => [
        'path' => app_url('/public/uploads/demo/banana-tree-demo.jpg'),
        'alt' => lang_text('Ảnh chụp thực tế buồng chuối còn xanh trên cây', 'Real photo of an unripe banana bunch on the tree'),
        'caption' => lang_text('Buồng chuối trên cây trong giai đoạn phát triển quả', 'A banana bunch growing on the tree'),
        'hero_label' => lang_text('Chuối trên cây', 'Bananas on tree'),
        'note' => lang_text('Dễ nhận diện hơn cho bối cảnh vùng trồng và theo dõi trạng thái quả trước thu hoạch.', 'Clearer for orchard context and pre-harvest fruit monitoring.'),
    ],
];

$heroPhotoLead = $photoLibrary['mango_harvest'];
$heroPhotoThumbs = [
    ['photo' => $photoLibrary['banana_tree'], 'class' => ' hero-photo-card-thumb-wide'],
    ['photo' => $photoLibrary['orange'], 'class' => ''],
    ['photo' => $photoLibrary['dragonfruit_market'], 'class' => ''],
];

$capabilityBadgeMap = [
    'solution' => lang_text('HTX / sơ chế', 'Co-ops / processing'),
    'technology' => '<= 200 ms',
    'impact' => '93-96%',
    'swot' => lang_text('QR theo lô', 'Lot-level QR'),
    'roadmap' => '0-6 / 6-24 / 24+',
    'team' => lang_text('5 SV + 2 cố vấn', '5 students + 2 advisors'),
];

$galleryPhotoKeys = [
    'mango',
    'dragonfruit',
    'avocado_harvest',
    'avocado',
    'banana',
    'avocado_tree_multi',
];
$galleryGridClass = count($galleryPhotoKeys) === 2 ? ' real-photo-grid-duo' : '';

$heroSection = $sections['hero'] ?? [];
$feasibilitySection = $sections['feasibility'] ?? [];
$contactSection = $sections['contact'] ?? [];

$heroTitle = (string) ($heroSection['title'] ?? 'AGRISORT-AI');
$heroDisplayTitle = preg_replace('/^AGRISORT-AI:\s*/u', '', $heroTitle) ?: $heroTitle;
$heroLines = $splitLines((string) ($heroSection['body'] ?? ''));

$heroMetrics = is_array($publicContent['hero_metrics'] ?? null) ? $publicContent['hero_metrics'] : [];
$heroCards = is_array($publicContent['hero_cards'] ?? null) ? $publicContent['hero_cards'] : [];
$workflowSteps = is_array($publicContent['workflow_steps'] ?? null) ? $publicContent['workflow_steps'] : [];
$capabilityOrder = is_array($publicContent['capability_order'] ?? null) ? $publicContent['capability_order'] : [];
$capabilityConfigs = is_array($publicContent['capability_configs'] ?? null) ? $publicContent['capability_configs'] : [];
$feasibilityCopy = is_array($publicContent['feasibility'] ?? null) ? $publicContent['feasibility'] : [];
$feasibilityLines = $splitLines((string) ($feasibilitySection['body'] ?? ''));
$feasibilityBadges = is_array($feasibilityCopy['badges'] ?? null) ? $feasibilityCopy['badges'] : [];
$blogCopy = is_array($publicContent['blog'] ?? null) ? $publicContent['blog'] : [];
$consultCopy = is_array($publicContent['consult'] ?? null) ? $publicContent['consult'] : [];
$contactCopy = is_array($publicContent['contact'] ?? null) ? $publicContent['contact'] : [];
?>
<section class="hero">
  <div class="container">
    <div class="hero-panel home-hero-grid home-hero-grid-v2">
      <div class="hero-copy-column">
        <p class="hero-kicker"><?= e((string) ($publicContent['hero_kicker'] ?? 'AGRISORT-AI')) ?></p>
        <span class="hero-brand-mark">AGRISORT-AI</span>
        <h1 class="hero-display-title hero-display-title-wide"><?= e($heroDisplayTitle) ?></h1>
        <div class="hero-copy hero-copy-stack">
          <?php foreach ($heroLines as $line): ?>
            <p><?= e($line) ?></p>
          <?php endforeach; ?>
        </div>
        <div class="hero-actions">
          <a href="#consult" class="btn primary"><?= e(t('demo_title')) ?></a>
          <a href="#real-gallery" class="btn secondary">
            <?= e(lang_text('Xem thư viện ảnh thực tế', 'Explore the real-photo gallery')) ?>
          </a>
        </div>
        <div class="metric-grid metric-grid-wide">
          <?php foreach ($heroMetrics as $metric): ?>
            <article class="metric-card">
              <span class="metric-number"><?= e((string) ($metric['value'] ?? '')) ?></span>
              <span class="metric-label"><?= e((string) ($metric['label'] ?? '')) ?></span>
            </article>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="hero-showcase hero-showcase-rich">
        <div class="hero-visual-frame hero-visual-stage hero-visual-stage-photos">
          <div class="hero-photo-mosaic">
            <figure class="hero-photo-card hero-photo-card-lead">
              <img src="<?= e($heroPhotoLead['path']) ?>" alt="<?= e($heroPhotoLead['alt']) ?>" class="hero-photo-image">
              <figcaption class="hero-photo-caption"><?= e($heroPhotoLead['caption']) ?></figcaption>
            </figure>
            <div class="hero-photo-stack">
              <?php foreach ($heroPhotoThumbs as $entry): ?>
                <?php
                $photo = is_array($entry['photo'] ?? null) ? $entry['photo'] : [];
                $thumbClass = (string) ($entry['class'] ?? '');
                ?>
                <figure class="hero-photo-card hero-photo-card-thumb<?= e($thumbClass) ?>">
                  <img src="<?= e((string) ($photo['path'] ?? '')) ?>" alt="<?= e((string) ($photo['alt'] ?? '')) ?>" class="hero-photo-image" loading="lazy">
                  <figcaption class="hero-photo-tag"><?= e((string) ($photo['hero_label'] ?? $photo['caption'] ?? '')) ?></figcaption>
                </figure>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="hero-visual-chip is-top-left"><?= e(lang_text('Ảnh chụp thực tế', 'Real photography')) ?></div>
        </div>

        <div class="hero-process-strip">
          <?php foreach ($workflowSteps as $step): ?>
            <div class="hero-process-step"><?= e((string) $step) ?></div>
          <?php endforeach; ?>
        </div>

        <div class="hero-insight-grid">
          <?php foreach ($heroCards as $card): ?>
            <article class="hero-insight-card">
              <h3><?= e((string) ($card['label'] ?? '')) ?></h3>
              <p><?= e((string) ($card['text'] ?? '')) ?></p>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="capabilities" class="sections-grid">
  <div class="container">
    <div class="section-head">
      <p class="section-kicker"><?= e(lang_text('Năng lực nổi bật', 'Core strengths')) ?></p>
      <h2><?= e(lang_text('Các năng lực cốt lõi của AGRISORT-AI', 'Core capabilities of AGRISORT-AI')) ?></h2>
    </div>

    <div class="capability-grid capability-grid-rich">
      <?php foreach ($capabilityOrder as $key): ?>
        <?php
        $config = is_array($capabilityConfigs[$key] ?? null) ? $capabilityConfigs[$key] : [];
        $section = is_array($sections[$key] ?? null) ? $sections[$key] : [];
        $lines = $splitLines((string) ($section['body'] ?? ''));
        $badge = (string) ($capabilityBadgeMap[$key] ?? '');
        ?>
        <article class="card feature-card feature-card-rich feature-card-text-only">
          <div class="feature-card-banner">
            <span class="feature-card-kicker-inline"><?= e((string) ($config['kicker'] ?? '')) ?></span>
            <?php if ($badge !== ''): ?>
              <span class="feature-card-badge"><?= e($badge) ?></span>
            <?php endif; ?>
          </div>
          <div class="feature-card-body">
            <h3><?= e((string) ($section['title'] ?? '')) ?></h3>
            <ul class="feature-points">
              <?php foreach ($lines as $line): ?>
                <li><?= e($line) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="real-gallery" class="home-real-gallery">
  <div class="container">
    <div class="section-head">
      <p class="section-kicker"><?= e(lang_text('Thư viện ảnh thực tế', 'Real-photo gallery')) ?></p>
      <h2><?= e(lang_text('Hình ảnh tham khảo thực tế cho bối cảnh nông sản và sau thu hoạch', 'Real photo references for produce and post-harvest contexts')) ?></h2>
    </div>
    <p class="real-photo-note">
      <?= e(lang_text('Trang chủ ưu tiên dùng ảnh chụp thực tế để tăng cảm giác tin cậy và gần với bối cảnh triển khai.', 'The homepage prioritizes real photography for credibility and deployment realism.')) ?>
    </p>
    <div class="real-photo-grid<?= e($galleryGridClass) ?>">
      <?php foreach ($galleryPhotoKeys as $index => $photoKey): ?>
        <?php $photo = $photoLibrary[$photoKey]; ?>
        <figure class="card real-photo-card<?= ($index === 0 && count($galleryPhotoKeys) > 2) ? ' is-featured' : '' ?>">
          <img src="<?= e($photo['path']) ?>" alt="<?= e($photo['alt']) ?>" class="real-photo-image" loading="lazy">
          <figcaption class="real-photo-body">
            <strong><?= e($photo['caption']) ?></strong>
            <p><?= e($photo['note']) ?></p>
          </figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="home-story-band">
  <div class="container grid-2">
    <article class="card spotlight-card">
      <div class="section-head section-head-inline">
        <p class="section-kicker"><?= e((string) ($feasibilityCopy['kicker'] ?? lang_text('Tính khả thi kỹ thuật', 'Technical feasibility'))) ?></p>
        <h2><?= e((string) ($feasibilitySection['title'] ?? lang_text('Tính khả thi kỹ thuật', 'Technical feasibility'))) ?></h2>
      </div>
      <p><?= e((string) ($feasibilityCopy['summary'] ?? '')) ?></p>
      <ul class="feature-points">
        <?php foreach ($feasibilityLines as $line): ?>
          <li><?= e($line) ?></li>
        <?php endforeach; ?>
      </ul>
      <div class="spotlight-badges">
        <?php foreach ($feasibilityBadges as $badge): ?>
          <span><?= e((string) $badge) ?></span>
        <?php endforeach; ?>
      </div>
    </article>

    <article class="card spotlight-card spotlight-card-alt">
      <div class="section-head section-head-inline">
        <p class="section-kicker"><?= e((string) ($blogCopy['kicker'] ?? lang_text('Bài viết dự án', 'Project posts'))) ?></p>
        <h2><?= e((string) ($blogCopy['title'] ?? lang_text('Tin tức công khai về AGRISORT-AI', 'Public updates about AGRISORT-AI'))) ?></h2>
      </div>
      <?php if ($blogPosts === []): ?>
        <p><?= e((string) ($blogCopy['empty'] ?? lang_text('Chưa có bài viết nào được xuất bản.', 'No posts have been published yet.'))) ?></p>
      <?php else: ?>
        <div class="story-list">
          <?php foreach ($blogPosts as $post): ?>
            <article class="story-item">
              <h3>
                <a href="<?= e(app_url('/blog/' . $post['slug'])) ?>">
                  <?= e(lang_text((string) $post['title_vi'], (string) ($post['title_en'] ?? ''))) ?>
                </a>
              </h3>
              <p><?= e(lang_text((string) $post['excerpt_vi'], (string) ($post['excerpt_en'] ?? ''))) ?></p>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </article>
  </div>
</section>

<section id="consult" class="form-section">
  <div class="container grid-2">
    <div class="panel-stack">
      <div class="section-head section-head-inline">
        <p class="section-kicker"><?= e((string) ($consultCopy['kicker'] ?? lang_text('Đăng ký tư vấn', 'Consultation request'))) ?></p>
        <h2><?= e(t('demo_title')) ?></h2>
      </div>
      <p><?= e((string) ($consultCopy['intro'] ?? '')) ?></p>
      <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/demo-request')) ?>" class="form-card">
        <?= csrf_field() ?>
        <label><?= e(lang_text('Họ và tên', 'Full name')) ?> <input type="text" name="name" required></label>
        <label><?= e(lang_text('Tổ chức', 'Organization')) ?> <input type="text" name="organization" required></label>
        <label>Email <input type="email" name="email" required></label>
        <label><?= e(lang_text('Điện thoại', 'Phone')) ?> <input type="text" name="phone"></label>
        <label><?= e(lang_text('Tỉnh / Thành phố', 'Province / City')) ?> <input type="text" name="province"></label>
        <label><?= e(lang_text('Nhu cầu triển khai', 'Deployment need')) ?> <textarea name="message" rows="4"></textarea></label>
        <button type="submit" class="btn primary"><?= e(lang_text('Gửi yêu cầu', 'Submit request')) ?></button>
      </form>
    </div>

    <div class="panel-stack">
      <div class="section-head section-head-inline">
        <p class="section-kicker"><?= e((string) ($contactCopy['kicker'] ?? lang_text('Liên hệ hợp tác', 'Partnership contact'))) ?></p>
        <h2><?= e((string) ($contactSection['title'] ?? t('contact_title'))) ?></h2>
      </div>
      <p><?= nl2br(e((string) ($contactSection['body'] ?? ''))) ?></p>
      <p><?= e((string) ($contactCopy['privacy_note'] ?? '')) ?></p>
      <form method="post" accept-charset="UTF-8" action="<?= e(app_url('/contact')) ?>" class="form-card">
        <?= csrf_field() ?>
        <label><?= e(lang_text('Họ và tên', 'Full name')) ?> <input type="text" name="name" required></label>
        <label>Email <input type="email" name="email" required></label>
        <label><?= e(lang_text('Điện thoại', 'Phone')) ?> <input type="text" name="phone"></label>
        <label><?= e(lang_text('Chủ đề', 'Subject')) ?> <input type="text" name="subject"></label>
        <label><?= e(lang_text('Tin nhắn', 'Message')) ?> <textarea name="message" rows="4" required></textarea></label>
        <button type="submit" class="btn primary"><?= e(lang_text('Gửi tin nhắn', 'Send message')) ?></button>
      </form>
    </div>
  </div>
</section>

<?php require BASE_PATH . '/resources/views/layouts/footer.php'; ?>
