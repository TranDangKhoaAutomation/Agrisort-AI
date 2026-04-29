<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Database;
use App\Core\Env;
use App\Core\Request;
use App\Core\Router;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/app/Core/functions.php';

Env::load(BASE_PATH . '/.env');
Config::setAll(require BASE_PATH . '/config/app.php');
Database::init();

$request = Request::capture();
$router = new Router($request);

require BASE_PATH . '/routes/web.php';
require BASE_PATH . '/routes/api.php';

$router->dispatch();
