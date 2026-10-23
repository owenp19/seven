<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * BaseController
 *
 * Shared foundation for every API v1 controller. It exposes the
 * normalized response helpers so business controllers stay focused on
 * arranging data while the response shape stays consistent.
 */
abstract class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    protected function success(mixed $data = null, string $message = 'OK', ?array $meta = null, int $status = 200): JsonResponse
    {
        return apiSuccess($data, $message, $meta, $status);
    }

    protected function error(string $message = 'Error', mixed $data = null, ?array $errors = null, int $status = 400): JsonResponse
    {
        return apiError($message, $data, $errors, $status);
    }
}
