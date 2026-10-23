<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Provider;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * Regresión de la segunda ronda de la auditoría de seguridad.
 *
 * Tmata los hallazgos abiertos tras el endurecimiento inicial:
 *  - Escalada en el panel del proveedor: con PROVEEDOR_TURISTICO se podía
 *    aprobar el propio negocio, marcarse verificado, manipular ratings y
 *    reasignar la cuenta dueña de la ficha (ahora campos admin-only).
 *  - Filtración de stack traces en el body de los 500 (ahora opt-in).
 *  - Endpoint /health y documentación Swagger expuestos (fingerprinting).
 *  - Ausencia de cabeceras de seguridad y CORS demasiado abierto.
 */
class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function adminHeaders(): array
    {
        return ['X-Admin-Token' => config('api.admin.token')];
    }

    private function providerOwner(): Provider
    {
        $user = User::factory()->create();
        $user->assignRole(Role::PROVEEDOR_TURISTICO);

        $provider = Provider::create([
            'user_id' => $user->id,
            'business_name' => 'Viajes Andinos',
            'type' => 'tour_operator',
            'status' => 'pending',
            'verification_status' => 'unverified',
        ]);

        return $provider;
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('audit-device', ['provider'])->plainTextToken;
    }

    private function refreshAuth(): void
    {
        $this->app->make('auth')->forgetGuards();
    }

    /*
    |--------------------------------------------------------------------------
    | Escalada en el panel del proveedor (auto-aprobación)
    |--------------------------------------------------------------------------
    */

    public function test_a_provider_cannot_approve_or_verify_its_own_record(): void
    {
        $provider = $this->providerOwner();

        $this->withToken($this->tokenFor($provider->user))
            ->putJson("/api/v1/provider/providers/{$provider->id}", [
                'business_name' => 'Viajes Andinos Actualizados',
                'status' => 'approved',
                'verification_status' => 'verified',
            ])
            ->assertOk();

        $provider->refresh();

        $this->assertSame('pending', $provider->status);
        $this->assertSame('unverified', $provider->verification_status);
        $this->assertSame('Viajes Andinos Actualizados', $provider->business_name);
    }

    public function test_a_provider_cannot_transfer_its_record_to_another_account(): void
    {
        $provider = $this->providerOwner();
        $stranger = User::factory()->create();

        $this->withToken($this->tokenFor($provider->user))
            ->putJson("/api/v1/provider/providers/{$provider->id}", [
                'business_name' => $provider->business_name,
                'user_id' => $stranger->id,
            ])
            ->assertOk();

        $this->assertSame($provider->user_id, $provider->refresh()->user_id);
    }

    public function test_a_provider_cannot_approve_or_boost_ratings_on_its_own_services(): void
    {
        $provider = $this->providerOwner();

        $business = Business::factory()->create([
            'provider_id' => $provider->id,
            'status' => 'pending',
            'is_verified' => false,
            'rating_avg' => 0,
            'rating_count' => 0,
        ]);

        $this->withToken($this->tokenFor($provider->user))
            ->putJson("/api/v1/provider/businesses/{$business->id}", [
                'name' => 'Negocio mejorado',
                'status' => 'approved',
                'is_verified' => true,
                'rating_avg' => 9.9,
                'rating_count' => 500,
            ])
            ->assertOk();

        $business->refresh();

        $this->assertSame('pending', $business->status);
        $this->assertFalse($business->is_verified);
        $this->assertEquals(0, $business->rating_avg);
        $this->assertSame(0, $business->rating_count);
    }

    public function test_the_global_backoffice_can_still_approve_a_business(): void
    {
        $business = Business::factory()->create(['status' => 'pending']);

        $this->withHeaders($this->adminHeaders())
            ->putJson("/api/v1/admin/businesses/{$business->id}", [
                'status' => 'approved',
                'is_verified' => true,
            ])
            ->assertOk();

        $business->refresh();

        $this->assertSame('approved', $business->status);
        $this->assertTrue($business->is_verified);
    }

    public function test_the_legacy_shared_token_cannot_enter_the_provider_panel(): void
    {
        $this->withHeaders($this->adminHeaders())
            ->getJson('/api/v1/provider/businesses')
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Fuga de stack traces en errores 500
    |--------------------------------------------------------------------------
    */

    public function test_unexpected_errors_do_not_leak_stack_traces_by_default(): void
    {
        config(['app.debug' => true, 'api.debug_trace' => false]);

        Route::get('/api/audit-boom', fn () => throw new RuntimeException('credenciales-secretas'));

        $this->getJson('/api/audit-boom')
            ->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonMissingPath('data.trace')
            ->assertJsonMissingPath('data.file')
            ->assertJsonMissingPath('data.line')
            ->assertJsonMissing(['message' => 'credenciales-secretas']);
    }

    public function test_debug_trace_is_only_available_when_explicitly_enabled(): void
    {
        config(['app.debug' => true, 'api.debug_trace' => true]);

        Route::get('/api/audit-boom-trace', fn () => throw new RuntimeException('trace-visible'));

        $this->getJson('/api/audit-boom-trace')
            ->assertStatus(500)
            ->assertJsonPath('data.exception', RuntimeException::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Fingerprinting
    |--------------------------------------------------------------------------
    */

    public function test_health_endpoint_does_not_leak_infrastructure_in_production(): void
    {
        config(['app.env' => 'production']);

        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonMissingPath('data.environment')
            ->assertJsonMissingPath('data.queue_driver')
            ->assertJsonMissingPath('data.cache_store');
    }

    public function test_health_endpoint_keeps_runtime_context_outside_production(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('data.environment', 'testing');
    }

    public function test_swagger_docs_are_disabled_in_production(): void
    {
        config(['app.env' => 'production']);

        $this->getJson('/api/documentation')->assertNotFound();
        $this->getJson('/docs')->assertNotFound();
    }

    public function test_swagger_docs_are_available_outside_production(): void
    {
        $this->getJson('/api/documentation')->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | CORS
    |--------------------------------------------------------------------------
    */

    public function test_an_arbitrary_origin_cannot_call_the_api(): void
    {
        $this->options('/api/v1/categories', [], [
            'Origin' => 'https://evil.example',
            'Access-Control-Request-Method' => 'GET',
        ])
            ->assertStatus(204)
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_an_expected_development_origin_is_still_allowed(): void
    {
        $this->options('/api/v1/categories', [], [
            'Origin' => 'http://localhost:9999',
            'Access-Control-Request-Method' => 'GET',
        ])
            ->assertStatus(204)
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:9999');
    }

    /*
    |--------------------------------------------------------------------------
    | Cabeceras de seguridad
    |--------------------------------------------------------------------------
    */

    public function test_api_responses_carry_security_headers(): void
    {
        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}
