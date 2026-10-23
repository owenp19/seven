<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SEVEN API Routes
|--------------------------------------------------------------------------
|
| Public, versioned REST API. Route files are grouped by version inside
| routes/api/v1 so a future v2 can be added without touching v1.
|
| Final URL shape: /api/v1/{resource}
|
*/

Route::prefix(config('api.prefix'))
    ->middleware(['api.version', 'throttle:api'])
    ->group(base_path('routes/api/v1/api.php'));
