<?php

// Hosting sin tmpfile() habilitado
if (! function_exists('tmpfile')) {
    function tmpfile()
    {
        $temp = tempnam(sys_get_temp_dir(), 'tmp_');

        return fopen($temp, 'w+');
    }
}

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Raíz de la app: Neubox (public_html + ../Hikari) o local (../)
$base = is_dir(__DIR__.'/../Hikari/bootstrap') ? __DIR__.'/../Hikari' : __DIR__.'/..';

if (file_exists($maintenance = $base.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $base.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $base.'/bootstrap/app.php';

// Carpeta pública real
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
