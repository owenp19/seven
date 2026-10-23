<?php

namespace App\Providers;

use App\Contracts\Ai\AiProviderInterface;
use App\Events\BookingStatusChanged;
use App\Listeners\SendBookingNotification;
use App\Models\Booking;
use App\Models\News;
use App\Models\Promotion;
use App\Models\Provider;
use App\Models\Role;
use App\Models\User;
use App\Policies\BookingPolicy;
use App\Policies\NewsPolicy;
use App\Policies\PromotionPolicy;
use App\Policies\ProviderPolicy;
use App\Policies\ServicePolicy;
use App\Services\Ai\Providers\FakeAiProvider;
use App\Services\Ai\Providers\GeminiProvider;
use App\Services\Ai\ToolRegistry;
use App\Services\Ai\Tools\BuildPackageTool;
use App\Services\Ai\Tools\FindByBudgetTool;
use App\Services\Ai\Tools\GetServiceDetailTool;
use App\Services\Ai\Tools\SearchCatalogTool;
use App\Swagger\SevenGeneratorFactory;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use L5Swagger\ConfigFactory;
use L5Swagger\GeneratorFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // La config de l5-swagger declara el analyser como null porque
        // `config:cache` no puede serializar instancias. SevenGeneratorFactory
        // entrega un generator con analyser de docblocks + atributos.
        $this->app->bind(GeneratorFactory::class, function ($app) {
            return new SevenGeneratorFactory($app->make(ConfigFactory::class));
        });

        // Proveedor de IA del asistente, seleccionado por config (Gemini por
        // defecto, `fake` en tests/demos). El resto del código solo conoce la
        // interfaz: cambiar de proveedor no toca el dominio.
        $this->app->bind(AiProviderInterface::class, function () {
            return match (config('ai.provider', 'gemini')) {
                'fake' => new FakeAiProvider,
                'gemini' => new GeminiProvider,
                default => throw new \InvalidArgumentException('Proveedor de IA desconocido: '.config('ai.provider')),
            };
        });

        // Allowlist de herramientas del asistente. Ninguna escribe datos.
        $this->app->singleton(ToolRegistry::class, function ($app) {
            return new ToolRegistry([
                $app->make(SearchCatalogTool::class),
                $app->make(GetServiceDetailTool::class),
                $app->make(FindByBudgetTool::class),
                $app->make(BuildPackageTool::class),
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerAuthorization();

        RateLimiter::for('api', function (Request $request) {
            // Tier autenticado vs invitado: detrás de NAT varios clientes comparten
            // IP, así que los invitados van por IP y los autenticados por user id.
            // El contador vive en el cache store activo, así que con
            // CACHE_STORE=redis en producción el límite es compartido entre nodos.
            $limit = $request->user()
                ? (int) config('api.rate_limit.authenticated', 600)
                : (int) config('api.rate_limit.guest', 120);

            return Limit::perMinute($limit)
                ->by($request->user()?->id ?: ($request->ip() ?: 'unknown'))
                ->response(fn () => apiError(
                    message: 'Too many requests. Please slow down.',
                    status: 429,
                ));
        });

        // Límite para el panel de administración. Va por usuario autenticado
        // cuando lo hay (un operador no puede expulsar a otro detrás de la
        // misma IP) y por IP para las llamadas con el token compartido legacy.
        RateLimiter::for('admin', function (Request $request) {
            $user = $request->user('sanctum') ?? $request->user();

            return Limit::perMinute((int) config('api.rate_limit.admin', 60))
                ->by('admin:'.($user?->id ?: ($request->ip() ?: 'unknown')))
                ->response(fn () => apiError(
                    message: 'Too many requests. Please slow down.',
                    status: 429,
                ));
        });

        // Límite del login administrativo: 6 intentos por minuto por email e
        // IP combinados. Cubre fuerza bruta contra una cuenta concreta sin
        // que un solo atacante pueda tumbar el login de todos los usuarios
        // desde una misma IP.
        RateLimiter::for('admin-login', function (Request $request) {
            $email = Str::lower((string) $request->input('email', ''));

            return Limit::perMinute(6)
                ->by('admin-login:'.$email.'|'.($request->ip() ?: 'unknown'))
                ->response(fn () => apiError(
                    message: 'Too many login attempts. Please try again later.',
                    status: 429,
                ));
        });

        // Límite para publicar comentarios. Es un control COMBINADO:
        //   por dispositivo (install_id): la regla de negocio "una opinión
        //     por destino" la impone el índice único; esto topa bots que
        //     reinician la app y reclaman otra identidad.
        //   por IP: detrás de NAT varios viajeros comparten IP, así que el
        //     tope es generoso (no expulsa a nadie legítimo); pero rotar el
        //     `X-Install-Id` para fabricar reseñas ya no basta: la IP cuenta.
        //   ráfaga: evita que un script apalanque el endpoint con cientos de
        //     peticiones inválidas por minuto mientras no llega al tope/hora.
        // El throttle aplica el primera de los tres que se agote (429).
        RateLimiter::for('comments', function (Request $request) {
            $device = trim((string) $request->header('X-Install-Id'));
            $deviceKey = $device !== '' ? $device : ($request->ip() ?: 'unknown');
            $ip = $request->ip() ?: 'unknown';

            $tooMany = fn (string $message) => fn () => apiError(
                message: $message,
                status: 429,
            );

            return [
                Limit::perHour((int) config('api.rate_limit.comments.per_device', 5))
                    ->by('comments:device:'.$deviceKey)
                    ->response($tooMany('Too many comments from this device. Please try again later.')),
                Limit::perHour((int) config('api.rate_limit.comments.per_ip', 30))
                    ->by('comments:ip:'.$ip)
                    ->response($tooMany('Too many comments from this network. Please try again later.')),
                Limit::perMinute((int) config('api.rate_limit.comments.per_minute', 10))
                    ->by('comments:burst:'.$ip)
                    ->response($tooMany('Too many requests. Please slow down.')),
            ];
        });

        // Asistente turístico: cada mensaje puede disparar varias llamadas
        // facturables al modelo, así que el control es propio y más estricto.
        // Va por install_id (dispositivo) y, además, por IP para cortar ráfagas
        // (rotar el X-Install-Id no basta para saltarse el tope por minuto).
        RateLimiter::for('assistant', function (Request $request) {
            $device = trim((string) $request->header('X-Install-Id'));
            $deviceKey = $device !== '' ? $device : ($request->ip() ?: 'unknown');

            $tooMany = fn (string $message) => fn () => apiError(
                message: $message,
                status: 429,
            );

            return [
                Limit::perMinute((int) config('ai.rate_limit.guest', 20))
                    ->by('assistant:device:'.$deviceKey)
                    ->response($tooMany('Demasiados mensajes al asistente. Inténtalo más tarde.')),
                Limit::perMinute((int) config('ai.rate_limit.burst_per_minute', 10))
                    ->by('assistant:burst:'.($request->ip() ?: 'unknown'))
                    ->response($tooMany('Too many requests. Please slow down.')),
            ];
        });

        // Notificaciones de reservas: el envío (mail a cliente + notificación
        // en BD al provider) ocurre en una cola, nunca en el request.
        Event::listen(BookingStatusChanged::class, SendBookingNotification::class);

        // Guard: jamás arrancar producción con APP_DEBUG=true.
        if ($this->app->environment('production') && config('app.debug')) {
            Log::error('APP_DEBUG está ACTIVADO en producción — fuera de servicio intencional.');
        }
    }

    /**
     * Policies del tenancy por proveedor.
     *
     * Los admins globales (SUPER_ADMIN / ADMIN_SEVEN) pasan cualquier
     * comprobación por Gate::before; para el resto la regla es ser el
     * dueño del proveedor. ServicePolicy se registra contra las siete
     * verticales de servicio para que una sola clase cubra todo el
     * catálogo.
     */
    private function registerAuthorization(): void
    {
        Gate::before(function (User $user) {
            return $user->hasAnyRole(Role::ADMIN) ? true : null;
        });

        Gate::policy(Provider::class, ProviderPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Promotion::class, PromotionPolicy::class);
        Gate::policy(News::class, NewsPolicy::class);

        foreach (Provider::serviceModels() as $model) {
            Gate::policy($model, ServicePolicy::class);
        }
    }
}
