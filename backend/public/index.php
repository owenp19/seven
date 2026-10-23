<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// The built-in dev server (`php artisan serve`) is single-threaded and queues
// requests on Windows. Under the app's parallel calls a queued request can sit
// waiting past the default 60s and die with "Maximum execution time exceeded".
// Raise the cap so dev requests finish instead of failing (production uses the
// web server's own limits, which override this value).
$sevenMaxExecutionTime = (int) getenv('SEVEN_MAX_EXECUTION_TIME') ?: 300;
ini_set('max_execution_time', (string) $sevenMaxExecutionTime);

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
