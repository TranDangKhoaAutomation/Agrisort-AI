<?php
declare(strict_types=1);

if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

require_once __DIR__ . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

require_once __DIR__ . '/app/bootstrap.php';
