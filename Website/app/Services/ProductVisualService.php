<?php
declare(strict_types=1);

namespace App\Services;

final class ProductVisualService
{
    public static function resolveLotImage(array $lot, ?string $locale = null): array
    {
        $locale = $locale ?? \lang();
        $produceType = trim((string) ($lot['produce_type'] ?? ''));
        $lotCode = trim((string) ($lot['lot_code'] ?? ''));
        $imagePath = trim((string) ($lot['image_path'] ?? ''));

        if ($imagePath !== '') {
            $source = preg_match('#^https?://#i', $imagePath) === 1
                ? $imagePath
                : \app_url('/' . ltrim($imagePath, '/'));

            return [
                'src' => $source,
                'alt' => $locale === 'vi'
                    ? 'Ảnh sản phẩm ' . ($produceType !== '' ? $produceType : $lotCode)
                    : 'Product image of ' . ($produceType !== '' ? $produceType : $lotCode),
                'badge' => $locale === 'vi' ? 'Ảnh sản phẩm' : 'Product image',
                'note' => $locale === 'vi' ? 'Ảnh thật do đối tác tải lên.' : 'Real photo uploaded by partner.',
                'is_placeholder' => false,
            ];
        }

        $title = $produceType !== '' ? $produceType : ($locale === 'vi' ? 'Nông sản truy xuất' : 'Traceable produce');
        $subtitle = $lotCode !== '' ? $lotCode : ($locale === 'vi' ? 'Sẵn sàng gắn mã QR' : 'Ready for QR labeling');

        return [
            'src' => self::illustration($title, $subtitle, $produceType),
            'alt' => $locale === 'vi'
                ? 'Ảnh minh họa cho sản phẩm ' . $title
                : 'Illustration for product ' . $title,
            'badge' => $locale === 'vi' ? 'Ảnh minh họa' : 'Illustration',
            'note' => $locale === 'vi'
                ? 'Lô hàng chưa có ảnh tải lên nên hệ thống dùng ảnh minh họa.'
                : 'No uploaded photo is available, so an illustration is shown.',
            'is_placeholder' => true,
        ];
    }

    public static function illustration(string $title, string $subtitle = '', string $produceHint = ''): string
    {
        $palette = self::paletteFor($produceHint !== '' ? $produceHint : $title);
        $label = self::shortText($title !== '' ? $title : 'AGRISORT-AI', 36);
        $shape = self::shapeMarkup(self::keyword($produceHint !== '' ? $produceHint : $title), $palette);

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 900" role="img" aria-label="{$label}">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$palette['bg_start']}"/>
      <stop offset="100%" stop-color="{$palette['bg_end']}"/>
    </linearGradient>
    <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="20" stdDeviation="22" flood-color="{$palette['shadow']}" flood-opacity="0.22"/>
    </filter>
    <filter id="soft" x="-20%" y="-20%" width="140%" height="140%">
      <feGaussianBlur stdDeviation="18"/>
    </filter>
  </defs>
  <rect width="1200" height="900" fill="url(#bg)"/>
  <rect width="1200" height="900" fill="#ffffff" opacity="0.04"/>
  <circle cx="1080" cy="120" r="180" fill="{$palette['orb_one']}" opacity="0.28" filter="url(#soft)"/>
  <circle cx="110" cy="770" r="220" fill="{$palette['orb_two']}" opacity="0.18" filter="url(#soft)"/>
  <circle cx="945" cy="740" r="120" fill="#ffffff" opacity="0.08"/>
  <g opacity="0.22" fill="none" stroke="#ffffff" stroke-width="2">
    <path d="M78 642c140-70 280-76 420-20s280 54 420-4" />
    <path d="M40 700c170-84 332-92 486-24 154 68 316 64 486-14" />
  </g>
  <g filter="url(#shadow)">
    <rect x="108" y="110" width="560" height="638" rx="52" fill="#ffffff" opacity="0.10" stroke="#ffffff" stroke-opacity="0.24"/>
  </g>
  <g transform="translate(132 134)">
    <rect x="0" y="0" width="510" height="590" rx="42" fill="#ffffff" opacity="0.10"/>
    <rect x="38" y="38" width="434" height="514" rx="34" fill="#ffffff" opacity="0.06"/>
    <circle cx="84" cy="84" r="12" fill="#ffffff" opacity="0.42"/>
    <circle cx="124" cy="84" r="12" fill="#ffffff" opacity="0.26"/>
    <circle cx="164" cy="84" r="12" fill="#ffffff" opacity="0.18"/>
  </g>
  <g transform="translate(192 212)">
    {$shape}
  </g>
  <g transform="translate(714 154)" filter="url(#shadow)">
    <rect x="0" y="0" width="352" height="286" rx="38" fill="#ffffff" opacity="0.12" stroke="#ffffff" stroke-opacity="0.22"/>
    <rect x="26" y="24" width="120" height="24" rx="12" fill="#ffffff" opacity="0.18"/>
    <rect x="248" y="38" width="74" height="74" rx="20" fill="#ffffff" opacity="0.16"/>
    <g transform="translate(58 78)">
      <rect x="0" y="0" width="186" height="186" rx="26" fill="#ffffff" opacity="0.94"/>
      <g fill="{$palette['ink']}">
        <rect x="22" y="22" width="42" height="42" rx="8"/>
        <rect x="122" y="22" width="42" height="42" rx="8"/>
        <rect x="22" y="122" width="42" height="42" rx="8"/>
        <rect x="78" y="20" width="18" height="18" rx="4"/>
        <rect x="78" y="52" width="18" height="18" rx="4"/>
        <rect x="78" y="84" width="18" height="18" rx="4"/>
        <rect x="110" y="52" width="18" height="18" rx="4"/>
        <rect x="142" y="52" width="18" height="18" rx="4"/>
        <rect x="78" y="116" width="18" height="18" rx="4"/>
        <rect x="110" y="116" width="18" height="18" rx="4"/>
        <rect x="142" y="116" width="18" height="18" rx="4"/>
        <rect x="78" y="148" width="18" height="18" rx="4"/>
        <rect x="110" y="148" width="18" height="18" rx="4"/>
        <rect x="142" y="148" width="18" height="18" rx="4"/>
      </g>
    </g>
  </g>
  <g transform="translate(648 506)" filter="url(#shadow)">
    <rect x="0" y="0" width="412" height="224" rx="36" fill="#ffffff" opacity="0.10" stroke="#ffffff" stroke-opacity="0.22"/>
    <rect x="34" y="34" width="132" height="16" rx="8" fill="#ffffff" opacity="0.16"/>
    <g transform="translate(38 78)">
      <rect x="0" y="48" width="44" height="58" rx="14" fill="#ffffff" opacity="0.88"/>
      <rect x="62" y="14" width="44" height="92" rx="14" fill="#ffffff" opacity="0.74"/>
      <rect x="124" y="0" width="44" height="106" rx="14" fill="#ffffff" opacity="0.58"/>
      <rect x="186" y="30" width="44" height="76" rx="14" fill="#ffffff" opacity="0.80"/>
      <path d="M284 92c20-40 48-62 84-66" fill="none" stroke="#ffffff" stroke-opacity="0.88" stroke-width="12" stroke-linecap="round"/>
      <path d="M284 92c40-18 70-8 96 28" fill="none" stroke="{$palette['accent_soft']}" stroke-width="12" stroke-linecap="round"/>
      <circle cx="284" cy="92" r="12" fill="#ffffff"/>
      <circle cx="368" cy="26" r="12" fill="{$palette['accent_soft']}"/>
      <circle cx="380" cy="120" r="12" fill="#ffffff" opacity="0.86"/>
    </g>
  </g>
  <g transform="translate(820 724)">
    <rect x="0" y="0" width="240" height="22" rx="11" fill="#ffffff" opacity="0.20"/>
    <rect x="0" y="36" width="188" height="22" rx="11" fill="#ffffff" opacity="0.14"/>
  </g>
</svg>
SVG;

        return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
    }

    private static function shortText(string $value, int $width): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_strimwidth')) {
            $value = mb_strimwidth($value, 0, $width, '...', 'UTF-8');
        } elseif (strlen($value) > $width) {
            $value = substr($value, 0, max(0, $width - 3)) . '...';
        }

        return self::escape($value);
    }

    private static function paletteFor(string $produceType): array
    {
        return match (self::keyword($produceType)) {
            'mango' => [
                'bg_start' => '#0c5b3f',
                'bg_end' => '#d48224',
                'accent' => '#ffc95c',
                'accent_soft' => '#ffd88e',
                'leaf' => '#5bd07b',
                'ink' => '#184734',
                'orb_one' => '#ffe6af',
                'orb_two' => '#fff5d5',
                'shadow' => '#173224',
            ],
            'banana' => [
                'bg_start' => '#1f5a3f',
                'bg_end' => '#9a7a0b',
                'accent' => '#ffd94b',
                'accent_soft' => '#ffef97',
                'leaf' => '#7ee081',
                'ink' => '#214735',
                'orb_one' => '#fff0b6',
                'orb_two' => '#fff6d8',
                'shadow' => '#1a3023',
            ],
            'orange' => [
                'bg_start' => '#13583f',
                'bg_end' => '#d95a1f',
                'accent' => '#ff9a3d',
                'accent_soft' => '#ffc57f',
                'leaf' => '#6ed38a',
                'ink' => '#1f4837',
                'orb_one' => '#ffd0aa',
                'orb_two' => '#fff0df',
                'shadow' => '#1a3126',
            ],
            'avocado' => [
                'bg_start' => '#0d513a',
                'bg_end' => '#577d33',
                'accent' => '#93c65d',
                'accent_soft' => '#c6e39e',
                'leaf' => '#86d89f',
                'ink' => '#204332',
                'orb_one' => '#d5edc0',
                'orb_two' => '#eff9df',
                'shadow' => '#132c21',
            ],
            'dragonfruit' => [
                'bg_start' => '#104f46',
                'bg_end' => '#d24783',
                'accent' => '#ff7bb1',
                'accent_soft' => '#ffb2cf',
                'leaf' => '#78db85',
                'ink' => '#214139',
                'orb_one' => '#ffd2e4',
                'orb_two' => '#fff0f5',
                'shadow' => '#192c26',
            ],
            default => [
                'bg_start' => '#0f5f3f',
                'bg_end' => '#b97722',
                'accent' => '#f4c158',
                'accent_soft' => '#ffe19e',
                'leaf' => '#67cf86',
                'ink' => '#1d4736',
                'orb_one' => '#ffe2b5',
                'orb_two' => '#fff3dc',
                'shadow' => '#173126',
            ],
        };
    }

    private static function keyword(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'default';
        }

        $ascii = function_exists('iconv')
            ? (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value)
            : $value;
        $ascii = strtolower($ascii);

        return match (true) {
            str_contains($ascii, 'xoai'), str_contains($ascii, 'mango') => 'mango',
            str_contains($ascii, 'chuoi'), str_contains($ascii, 'banana') => 'banana',
            str_contains($ascii, 'cam'), str_contains($ascii, 'orange'), str_contains($ascii, 'citrus') => 'orange',
            str_contains($ascii, 'bo 034'), str_contains($ascii, 'bo'), str_contains($ascii, 'avocado') => 'avocado',
            str_contains($ascii, 'thanh long'), str_contains($ascii, 'dragon') => 'dragonfruit',
            default => 'default',
        };
    }

    private static function shapeMarkup(string $keyword, array $palette): string
    {
        return match ($keyword) {
            'banana' => <<<SVG
  <path d="M146 352c70-110 184-184 314-206 28-4 44 32 20 46-118 72-194 160-236 274-10 30-54 20-50-14 4-36 16-68 34-100-40 22-72 54-96 96-16 28-54 8-44-24 12-34 32-58 58-72z" fill="{$palette['accent']}"/>
  <path d="M202 372c74-86 164-144 278-180" fill="none" stroke="{$palette['accent_soft']}" stroke-width="24" stroke-linecap="round"/>
  <ellipse cx="332" cy="382" rx="108" ry="60" fill="#ffffff" opacity="0.14"/>
  <path d="M382 154c16-30 56-44 92-28-10 40-38 68-82 84-16-16-20-34-10-56z" fill="{$palette['leaf']}"/>
SVG,
            'orange' => <<<SVG
  <circle cx="282" cy="282" r="150" fill="{$palette['accent']}"/>
  <circle cx="282" cy="282" r="118" fill="{$palette['accent_soft']}" opacity="0.76"/>
  <path d="M282 164v236M164 282h236M198 198l168 168M198 366l168-168" stroke="#ffffff" stroke-opacity="0.55" stroke-width="16" stroke-linecap="round"/>
  <path d="M314 124c18-38 60-54 106-40-12 44-44 74-94 90-18-14-22-30-12-50z" fill="{$palette['leaf']}"/>
SVG,
            'avocado' => <<<SVG
  <path d="M296 150c90 0 162 72 162 162 0 116-86 210-162 210s-162-94-162-210c0-90 72-162 162-162z" fill="{$palette['accent']}"/>
  <path d="M296 194c62 0 112 50 112 112 0 86-62 156-112 156s-112-70-112-156c0-62 50-112 112-112z" fill="{$palette['accent_soft']}"/>
  <circle cx="296" cy="338" r="48" fill="#7b4f1d"/>
  <path d="M324 130c12-36 48-62 88-64-6 42-28 76-70 102-20-10-26-22-18-38z" fill="{$palette['leaf']}"/>
SVG,
            'dragonfruit' => <<<SVG
  <ellipse cx="296" cy="300" rx="146" ry="128" fill="{$palette['accent']}"/>
  <ellipse cx="296" cy="300" rx="116" ry="100" fill="{$palette['accent_soft']}" opacity="0.82"/>
  <path d="M172 240c-26-42-22-86 10-132 42 24 66 62 74 116" fill="none" stroke="{$palette['leaf']}" stroke-width="22" stroke-linecap="round"/>
  <path d="M408 230c28-34 38-76 26-126-44 18-74 50-92 98" fill="none" stroke="{$palette['leaf']}" stroke-width="22" stroke-linecap="round"/>
  <path d="M160 340c-44 10-74 36-92 80 44 10 84 4 120-18" fill="none" stroke="{$palette['leaf']}" stroke-width="22" stroke-linecap="round"/>
  <path d="M420 338c48 6 84 30 108 72-42 16-86 16-132 0" fill="none" stroke="{$palette['leaf']}" stroke-width="22" stroke-linecap="round"/>
SVG,
            'mango', 'default' => <<<SVG
  <ellipse cx="286" cy="300" rx="136" ry="176" transform="rotate(-18 286 300)" fill="{$palette['accent']}"/>
  <ellipse cx="304" cy="284" rx="98" ry="134" transform="rotate(-18 304 284)" fill="{$palette['accent_soft']}" opacity="0.78"/>
  <path d="M356 132c18-44 60-72 106-74-10 52-40 90-92 116-18-14-24-28-14-42z" fill="{$palette['leaf']}"/>
  <path d="M204 424c40 36 88 54 144 50" fill="none" stroke="#ffffff" stroke-opacity="0.35" stroke-width="18" stroke-linecap="round"/>
SVG,
        };
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
