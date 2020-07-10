<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. We just need to utilize it! We'll simply require it
| into the script here so that we don't have to worry about manual
| loading any of our classes later on. It feels great to relax.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Turn On The Lights
|--------------------------------------------------------------------------
|
| We need to illuminate PHP development, so let us turn on the lights.
| This bootstraps the framework and gets it ready for use, then it
| will load up this application so that we can run it and send
| the responses back to the browser and delight our users.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

if (!defined('DOMAIN')) {
    // 域名定义
    $serverName = $_SERVER['HTTP_HOST'] ?? null;
    if (false !== strpos($serverName, ':')) {
        $serverName = explode(':', $serverName, 2)[0];
    }

    $serverArr = explode('.', $serverName, 2);
    define('DOMAIN', $serverArr[1] ?? $serverName);
}
defined('FULL_DOMAIN') || define('FULL_DOMAIN', $serverName);

if (!defined('COUNTRY')) {
    // 国家简码(从CDN上面传入)
    $country = $_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY'] ?? 'CN';
    define('COUNTRY', $country);
}

// 正式环境调试模式,主要用于日志输出
define('GES_ENABLE_TRACK_LOG', (isset($_GET['enable_track_log']) || isset($_COOKIE['enable_track_log'])));

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
*/

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
