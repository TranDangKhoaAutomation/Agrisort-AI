<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Database;
use App\Core\Env;
use App\Models\CmsSection;
use App\Models\Setting;
use App\Services\TranslateService;
use App\Support\PublicContent;

date_default_timezone_set('Asia/Ho_Chi_Minh');

define('BASE_PATH', dirname(__DIR__));

if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
} else {
    require BASE_PATH . '/app/Core/Autoloader.php';
    \App\Core\Autoloader::register();
}

require BASE_PATH . '/app/Core/functions.php';

Env::load(BASE_PATH . '/.env');
Config::setAll(require BASE_PATH . '/config/app.php');
Database::init();

$shouldTranslateEn = in_array('--translate-en', $argv, true);
$translateKey = trim((string) Setting::get('google_translate_api_key', (string) Config::get('services.google_translate_api_key', '')));
$canTranslateEn = $shouldTranslateEn && $translateKey !== '';

foreach (PublicContent::cmsSections() as $sectionKey => $payload) {
    $vi = is_array($payload['vi'] ?? null) ? $payload['vi'] : [];
    $en = is_array($payload['en'] ?? null) ? $payload['en'] : $vi;

    if ($canTranslateEn) {
        $en['title'] = TranslateService::toEnglish((string) ($vi['title'] ?? ''));
        $en['body'] = TranslateService::toEnglish((string) ($vi['body'] ?? ''));
    }

    CmsSection::save($sectionKey, $vi, $en);
    echo '[OK] Synced CMS section: ' . $sectionKey . PHP_EOL;
}

if ($shouldTranslateEn && !$canTranslateEn) {
    echo '[INFO] Google Translate API key is not configured. Bundled EN fallback was kept unchanged.' . PHP_EOL;
}

echo '[DONE] Public content synchronization completed.' . PHP_EOL;
