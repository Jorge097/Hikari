<?php

if (!function_exists('tmpfile')) {
    function tmpfile() {
        $temp = tempnam(sys_get_temp_dir(), 'tmp_');
        return fopen($temp, 'w+');
    }
}

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../Hikari/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../Hikari/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../Hikari/bootstrap/app.php';

// 2. AVISARLE A LARAVEL DONDE ESTA LA NUEVA CARPETA PUBLICA
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
