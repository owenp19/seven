<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;

/**
 * GET /api — raíz de la API v1.
 *
 * Invocable (no closure) para que `php artisan route:cache` funcione
 * sin problemas en despliegues.
 */
class ApiIntroController extends BaseController
{
    public function __invoke()
    {
        return $this->success(
            data: [
                'name' => 'SEVEN API',
                'version' => 'v1',
                'status' => 'ok',
            ],
            message: 'Welcome to the SEVEN API',
        );
    }
}
