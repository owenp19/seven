<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * HealthController
 *
 * Endpoint de disponibilidad para monitoreo (load balancers, uptime bots,
 * paneles). Un único GET que verifica los tres servicios críticos:
 * base de datos, caché y cola. Devuelve 200 solo si los tres están OK,
 * 503 en caso contrario. Jamás se cachea (Cache-Control: no-store).
 */
class HealthController extends BaseController
{
    /**
     * GET /api/v1/health — readiness probe.
     *
     * @OA\Get(
     *     path="/health",
     *     operationId="healthCheck",
     *     tags={"Sistema"},
     *     summary="Estado de los servicios críticos",
     *     description="Verifica base de datos, caché y cola. 200 solo si los tres están OK, 503 en caso contrario.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Todos los servicios operativos.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="OK"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="status", type="string", example="ok"),
     *                 @OA\Property(property="environment", type="string", example="production"),
     *                 @OA\Property(property="checks", type="object"),
     *             ),
     *         )
     *     ),
     *
     *     @OA\Response(response=503, description="Algún servicio está degradado.")
     * )
     */
    public function __invoke(Request $request): JsonResponse
    {
        $checks = [
            'database' => $this->probe(fn () => DB::select('select 1')),
            'cache' => $this->probe(function () {
                Cache::store()->put('health:check', 'ok', 10);
                Cache::store()->get('health:check');
            }),
            'queue' => $this->probe(fn () => $this->queueOk()),
        ];

        $healthy = collect($checks)->every(fn (array $check) => $check['status'] === 'ok');

        $data = [
            'status' => $healthy ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ];

        // Fingerprinting: el entorno y los drivers no se publican en
        // producción, donde el endpoint es alcanzable sin autenticación.
        if (config('app.env') !== 'production') {
            $data = [
                'status' => $healthy ? 'ok' : 'degraded',
                'environment' => config('app.env'),
                'queue_driver' => config('queue.default'),
                'cache_store' => config('cache.default'),
                'timestamp' => now()->toIso8601String(),
                'checks' => $checks,
            ];
        }

        return apiSuccess(
            data: $data,
            status: $healthy ? 200 : 503,
        )->header('Cache-Control', 'no-store');
    }

    private function probe(callable $check): array
    {
        $start = microtime(true);

        try {
            $check();

            return ['status' => 'ok', 'latency_ms' => $this->latency($start)];
        } catch (Throwable $e) {
            return ['status' => 'error', 'latency_ms' => $this->latency($start)];
        }
    }

    private function queueOk(): void
    {
        $driver = config('queue.default');

        if ($driver === 'sync') {
            return;
        }

        if ($driver === 'redis') {
            $connection = Redis::connection((string) config('queue.connections.redis.connection', 'queue'));
            $ping = $connection->ping();

            if ($ping !== true && $ping !== 'PONG') {
                throw new \RuntimeException('Redis queue ping failed.');
            }

            return;
        }

        if ($driver === 'database') {
            DB::select('select 1 from jobs limit 1');

            return;
        }
    }

    private function latency(float $start): float
    {
        return (float) round((microtime(true) - $start) * 1000, 2);
    }
}
