<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Provider;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Migración a identidades individuales y Zero Trust (token legacy, fail-secure,
 * abilities, permisos, auditoría, revocación y no filtración de secretos).
 */
class ZeroTrustAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function userWithRole(string ...$roles): User
    {
        $user = User::factory()->create();
        $user->assignRole(...$roles);

        return $user;
    }

    private function adminToken(User $user, string $ability = 'admin'): string
    {
        return $user->createToken('zt-'.uniqid(), [$ability])->plainTextToken;
    }

    private function providerOwner(): Provider
    {
        $user = $this->userWithRole(Role::PROVEEDOR_TURISTICO);

        return Provider::create([
            'user_id' => $user->id,
            'business_name' => 'Zero Trust Travel',
            'type' => 'tour_operator',
            'status' => 'approved',
            'verification_status' => 'verified',
        ]);
    }

    private function refreshAuth(): void
    {
        $this->app->make('auth')->forgetGuards();
    }

    /*
    |--------------------------------------------------------------------------
    | PARTE 1/2 — Token legacy y fail-secure
    |--------------------------------------------------------------------------
    */

    public function test_legacy_token_is_rejected_in_production(): void
    {
        // Aislamos la guarda de entorno de AdminAuthMiddleware: el fail-secure
        // global se prueba por separado.
        config([
            'app.env' => 'production',
            'api.security.enforce_production' => false,
            'api.admin.legacy_enabled' => true,
        ]);

        $this->withHeaders(['X-Admin-Token' => config('api.admin.token')])
            ->getJson('/api/v1/admin/categories')
            ->assertStatus(401);

        // Sanctum sigue siendo la vía válida en producción.
        $this->refreshAuth();
        $admin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->withToken($this->adminToken($admin))
            ->getJson('/api/v1/admin/categories')
            ->assertOk();
    }

    public function test_legacy_token_works_only_outside_production(): void
    {
        config([
            'app.env' => 'local',
            'api.admin.legacy_enabled' => true,
        ]);

        $this->withHeaders(['X-Admin-Token' => config('api.admin.token')])
            ->getJson('/api/v1/admin/categories')
            ->assertOk();
    }

    public function test_fail_secure_blocks_production_with_debug_enabled(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => true,
            'api.security.enforce_production' => true,
            'api.admin.legacy_enabled' => false,
        ]);

        $this->getJson('/api/v1/categories')->assertStatus(503);
    }

    public function test_fail_secure_blocks_production_with_legacy_token_enabled(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'api.security.enforce_production' => true,
            'api.admin.legacy_enabled' => true,
        ]);

        $this->getJson('/api/v1/categories')->assertStatus(503);
    }

    public function test_fail_secure_allows_a_healthy_production_config(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'api.security.enforce_production' => true,
            'api.admin.legacy_enabled' => false,
        ]);

        $this->getJson('/api/v1/categories')->assertOk();
    }

    public function test_fail_secure_does_not_apply_outside_production(): void
    {
        config([
            'app.env' => 'local',
            'app.debug' => true,
            'api.admin.legacy_enabled' => true,
            'api.security.enforce_production' => true,
        ]);

        $this->getJson('/api/v1/categories')->assertOk();
    }

    public function test_deploy_commands_are_not_blocked_by_the_http_guard(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => true,
            'api.admin.legacy_enabled' => true,
            'api.security.enforce_production' => true,
        ]);

        // El guard es middleware HTTP: los comandos artisan no pasan por él.
        $this->artisan('list')->assertSuccessful();
    }

    /*
    |--------------------------------------------------------------------------
    | PARTE 3/4 — Abilities y contexto administrativo
    |--------------------------------------------------------------------------
    */

    public function test_provider_token_cannot_access_admin_endpoints(): void
    {
        $provider = $this->providerOwner();
        $token = $this->adminToken($provider->user, 'provider');

        $this->withToken($token)->getJson('/api/v1/admin/auth/me')->assertStatus(403);
        $this->withToken($token)->getJson('/api/v1/admin/auth/tokens')->assertStatus(403);
        $this->withToken($token)->postJson('/api/v1/admin/auth/tokens', ['name' => 'x'])->assertStatus(403);
        $this->withToken($token)->getJson('/api/v1/admin/categories')->assertStatus(403);
        $this->withToken($token)->getJson('/api/v1/admin/providers')->assertStatus(403);
    }

    public function test_provider_token_cannot_enter_admin_even_with_admin_role(): void
    {
        $user = $this->userWithRole(Role::PROVEEDOR_TURISTICO, Role::SUPER_ADMIN);

        Provider::create([
            'user_id' => $user->id,
            'business_name' => 'Doble rol',
            'type' => 'tour_operator',
            'status' => 'approved',
        ]);

        // Ability `provider` aunque el rol sea de backoffice: la ability manda.
        $this->withToken($this->adminToken($user, 'provider'))
            ->getJson('/api/v1/admin/auth/me')
            ->assertStatus(403);
    }

    public function test_admin_token_works_without_the_shared_legacy_token(): void
    {
        config([
            'api.admin.token' => '',
            'api.admin.legacy_enabled' => false,
        ]);

        $admin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->withToken($this->adminToken($admin))
            ->getJson('/api/v1/admin/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $admin->email);
    }

    public function test_admin_login_issues_an_admin_ability_token(): void
    {
        $admin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->postJson('/api/v1/admin/auth/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertOk();

        $this->assertSame(['admin'], $admin->tokens()->sole()->abilities);
    }

    /*
    |--------------------------------------------------------------------------
    | PARTE 5 — Permisos granulares
    |--------------------------------------------------------------------------
    */

    public function test_admin_seven_with_the_permission_can_access(): void
    {
        $admin = $this->userWithRole(Role::ADMIN_SEVEN);

        $this->withToken($this->adminToken($admin))
            ->getJson('/api/v1/admin/providers')
            ->assertOk();
    }

    public function test_admin_seven_without_the_permission_is_forbidden(): void
    {
        $permission = Permission::query()->where('name', 'providers.manage')->firstOrFail();
        Role::query()->where('name', Role::ADMIN_SEVEN)->firstOrFail()->permissions()->detach($permission->id);

        $admin = $this->userWithRole(Role::ADMIN_SEVEN);

        $this->withToken($this->adminToken($admin))
            ->getJson('/api/v1/admin/providers')
            ->assertStatus(403);

        // Otros permisos de ADMIN_SEVEN siguen vigentes.
        $this->withToken($this->adminToken($admin))
            ->getJson('/api/v1/admin/categories')
            ->assertOk();
    }

    public function test_super_admin_bypasses_missing_permissions(): void
    {
        Role::query()->where('name', Role::SUPER_ADMIN)->firstOrFail()->permissions()->detach();

        $admin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->withToken($this->adminToken($admin))
            ->getJson('/api/v1/admin/providers')
            ->assertOk();
    }

    public function test_provider_never_holds_global_backoffice_permissions(): void
    {
        $provider = $this->providerOwner();

        $this->assertFalse($provider->user->hasPermission('admin.access'));
        $this->assertFalse($provider->user->hasPermission('users.manage'));
    }

    /*
    |--------------------------------------------------------------------------
    | PARTE 7 — Expiración, revocación y sesiones
    |--------------------------------------------------------------------------
    */

    public function test_revoke_other_tokens_keeps_the_current_session(): void
    {
        $admin = $this->userWithRole(Role::SUPER_ADMIN);
        $current = $this->adminToken($admin);
        $other = $this->adminToken($admin);

        $this->withToken($current)
            ->deleteJson('/api/v1/admin/auth/tokens/others')
            ->assertOk()
            ->assertJsonPath('data.revoked', 1);

        $this->refreshAuth();
        $this->withToken($current)->getJson('/api/v1/admin/auth/me')->assertOk();

        $this->refreshAuth();
        $this->withToken($other)->getJson('/api/v1/admin/auth/me')->assertStatus(401);
    }

    public function test_changing_the_password_invalidates_all_sessions(): void
    {
        $admin = $this->userWithRole(Role::SUPER_ADMIN);
        $token = $this->adminToken($admin);

        $admin->update(['password' => 'a-brand-new-password']);

        $this->assertSame(0, $admin->tokens()->count());

        $this->refreshAuth();
        $this->withToken($token)->getJson('/api/v1/admin/auth/me')->assertStatus(401);
    }

    public function test_a_provider_can_only_create_provider_ability_tokens(): void
    {
        $provider = $this->providerOwner();
        $session = $provider->user->createToken('p-session', ['provider'])->plainTextToken;

        $response = $this->withToken($session)
            ->postJson('/api/v1/provider/auth/tokens', ['name' => 'Puesto de venta']);

        $response->assertStatus(201)->assertJsonPath('data.token_type', 'Bearer');

        $this->assertSame(['provider'], $provider->user->tokens()->where('name', 'Puesto de venta')->sole()->abilities);
    }

    public function test_provider_tokens_endpoints_reject_legacy_and_admin_tokens(): void
    {
        $provider = $this->providerOwner();

        // Token admin (habilidad admin): no entra al panel del proveedor.
        $this->withToken($this->adminToken($provider->user, 'admin'))
            ->getJson('/api/v1/provider/auth/tokens')
            ->assertStatus(403);

        // Token legacy compartido: tampoco (y no pertenece a una cuenta).
        $this->withHeaders(['X-Admin-Token' => config('api.admin.token')])
            ->getJson('/api/v1/provider/auth/tokens')
            ->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | PARTE 6 — Auditoría
    |--------------------------------------------------------------------------
    */

    public function test_provider_approval_is_audited(): void
    {
        $provider = Provider::create([
            'business_name' => 'Pendiente S.A.S',
            'type' => 'tour_operator',
            'status' => 'pending',
            'verification_status' => 'unverified',
        ]);

        $admin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->withToken($this->adminToken($admin))
            ->putJson("/api/v1/admin/providers/{$provider->id}", [
                'status' => 'approved',
                'verification_status' => 'verified',
            ])
            ->assertOk();

        $log = AuditLog::query()->where('action', 'provider.updated')->latest('id')->firstOrFail();

        $this->assertSame(Provider::class, $log->auditable_type);
        $this->assertSame($provider->id, $log->auditable_id);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('approved', $log->metadata['state']['status']);
        $this->assertSame('pending', $log->metadata['previous']['status']);
    }

    public function test_deletion_is_audited(): void
    {
        $category = Category::factory()->create();
        $admin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->withToken($this->adminToken($admin))
            ->deleteJson("/api/v1/admin/categories/{$category->id}")
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'category.deleted',
            'auditable_type' => Category::class,
            'auditable_id' => $category->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_audit_sanitizer_redacts_secrets(): void
    {
        $clean = app(AuditLogger::class)->sanitize([
            'password' => 'super-secret',
            'password_confirmation' => 'super-secret',
            'authorization' => 'Bearer abc.def',
            'booking_access_token' => 'xyz',
            'nested' => ['remember_token' => 'recuerdame', 'status' => 'approved'],
        ]);

        $this->assertSame(AuditLogger::REDACTED, $clean['password']);
        $this->assertSame(AuditLogger::REDACTED, $clean['password_confirmation']);
        $this->assertSame(AuditLogger::REDACTED, $clean['authorization']);
        $this->assertSame(AuditLogger::REDACTED, $clean['booking_access_token']);
        $this->assertSame(AuditLogger::REDACTED, $clean['nested']['remember_token']);
        $this->assertSame('approved', $clean['nested']['status']);
    }

    /*
    |--------------------------------------------------------------------------
    | PARTE 8 — Los secretos nunca aparecen en las respuestas
    |--------------------------------------------------------------------------
    */

    public function test_token_listing_never_returns_the_plaintext_token(): void
    {
        $admin = $this->userWithRole(Role::SUPER_ADMIN);
        $plain = $this->adminToken($admin);

        $response = $this->withToken($plain)->getJson('/api/v1/admin/auth/tokens')->assertOk();

        $this->assertStringNotContainsString($plain, $response->getContent());
        $response->assertJsonMissingPath('data.0.token');
    }

    public function test_legacy_shared_token_is_never_echoed_in_responses(): void
    {
        config(['app.env' => 'local', 'api.admin.legacy_enabled' => true]);
        $secret = (string) config('api.admin.token');

        $response = $this->withHeaders(['X-Admin-Token' => $secret])
            ->getJson('/api/v1/admin/categories')
            ->assertOk();

        $this->assertStringNotContainsString($secret, $response->getContent());
    }
}
