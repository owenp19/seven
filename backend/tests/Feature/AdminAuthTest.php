<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Autenticación administrativa con Laravel Sanctum.
 *
 * Cubre los cinco escenarios pedidos:
 *  - login correcto, login incorrecto, acceso sin token,
 *  - usuario autenticado sin permisos (rol), usuario autorizado.
 *
 * Además fija la compatibilidad con el token compartido legacy mientras
 * ADMIN_LEGACY_TOKEN_ENABLED siga activo.
 */
class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function userWithRole(string $role = Role::SUPER_ADMIN): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function tokenFor(User $user, string $name = 'test-device'): string
    {
        return $user->createToken($name, ['admin'])->plainTextToken;
    }

    /**
     * El guard de Sanctum cachea el usuario que resuelve, y dentro de un
     * mismo test todas las peticiones comparten la instancia de la app
     * (en producción cada request arranca limpio). Este helper repone ese
     * arranque limpio cuando un test cambia credenciales entre peticiones.
     */
    private function freshAuth(): void
    {
        $this->app->make('auth')->forgetGuards();
    }

    /*
    |--------------------------------------------------------------------------
    | Login
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_login_with_email_and_password(): void
    {
        $user = $this->userWithRole(Role::SUPER_ADMIN);

        $response = $this->postJson('/api/v1/admin/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Panel Web',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.user.roles.0', Role::SUPER_ADMIN)
            ->assertJsonPath('data.user.permissions.0', 'admin.access');

        $token = $response->json('data.token');

        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'Panel Web',
        ]);

        // El token emitido abre el backoffice.
        $this->withToken($token)
            ->getJson('/api/v1/admin/categories')
            ->assertOk();
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        $user = $this->userWithRole(Role::ADMIN_SEVEN);

        $this->postJson('/api/v1/admin/auth/login', [
            'email' => $user->email,
            'password' => 'definitely-not-the-password',
        ])->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['email']]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_with_unknown_email_is_rejected(): void
    {
        $this->postJson('/api/v1/admin/auth/login', [
            'email' => 'nobody@seven.test',
            'password' => 'password',
        ])->assertStatus(401);
    }

    public function test_login_payload_is_validated_by_the_form_request(): void
    {
        $this->postJson('/api/v1/admin/auth/login', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['email', 'password']]);

        $this->postJson('/api/v1/admin/auth/login', [
            'email' => 'no-es-un-email',
            'password' => '',
        ])->assertStatus(422)
            ->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    public function test_login_is_denied_to_users_without_an_administrative_role(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(Role::EDITOR);

        $this->postJson('/api/v1/admin/auth/login', [
            'email' => $customer->email,
            'password' => 'password',
        ])->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['role']]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /*
    |--------------------------------------------------------------------------
    | Protección de /api/v1/admin/*
    |--------------------------------------------------------------------------
    */

    public function test_admin_endpoints_reject_requests_without_a_token(): void
    {
        $this->getJson('/api/v1/admin/categories')->assertStatus(401);
        $this->postJson('/api/v1/admin/categories', ['name' => 'X'])->assertStatus(401);
        $this->getJson('/api/v1/admin/auth/me')->assertStatus(401);
        $this->postJson('/api/v1/admin/auth/logout')->assertStatus(401);
    }

    public function test_admin_endpoints_reject_an_invalid_bearer_token(): void
    {
        $this->withToken('not-a-real-sanctum-token')
            ->getJson('/api/v1/admin/categories')
            ->assertStatus(401);
    }

    public function test_authenticated_user_without_roles_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->withToken($this->tokenFor($user))
            ->getJson('/api/v1/admin/categories')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_authenticated_user_with_an_unauthorized_role_is_forbidden(): void
    {
        $editor = $this->userWithRole(Role::EDITOR);

        $this->withToken($this->tokenFor($editor))
            ->postJson('/api/v1/admin/categories', ['slug' => 'x', 'name' => 'X'])
            ->assertStatus(403);

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_authorized_admins_can_access_the_backoffice(): void
    {
        foreach ([Role::SUPER_ADMIN, Role::ADMIN_SEVEN] as $role) {
            $admin = $this->userWithRole($role);

            $this->freshAuth();

            $this->withToken($this->tokenFor($admin, 'device-'.$role))
                ->getJson('/api/v1/admin/categories')
                ->assertOk()
                ->assertJsonPath('success', true);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Logout y revocación de tokens
    |--------------------------------------------------------------------------
    */

    public function test_logout_revokes_the_current_token(): void
    {
        $admin = $this->userWithRole(Role::SUPER_ADMIN);
        $token = $this->tokenFor($admin);

        $this->withToken($token)
            ->postJson('/api/v1/admin/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->freshAuth();

        $this->withToken($token)
            ->getJson('/api/v1/admin/auth/me')
            ->assertStatus(401);
    }

    public function test_user_can_list_and_revoke_his_personal_tokens(): void
    {
        $admin = $this->userWithRole(Role::ADMIN_SEVEN);
        $phone = $this->tokenFor($admin, 'phone');
        $laptop = $this->tokenFor($admin, 'laptop');

        $this->withToken($phone)
            ->getJson('/api/v1/admin/auth/tokens')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'laptop');

        $phoneId = (int) $admin->tokens()->where('name', 'phone')->value('id');

        $this->withToken($phone)
            ->deleteJson("/api/v1/admin/auth/tokens/{$phoneId}")
            ->assertOk();

        $this->freshAuth();

        // El token revocado deja de servir; el otro sigue vivo.
        $this->withToken($phone)->getJson('/api/v1/admin/auth/me')->assertStatus(401);

        $this->freshAuth();

        $this->withToken($laptop)->getJson('/api/v1/admin/auth/me')->assertOk();

        // Un id que no pertenece al usuario responde 404, sin sonar existencia.
        $stranger = $this->userWithRole(Role::SUPER_ADMIN);
        $this->tokenFor($stranger, 'stranger');
        $strangerId = (int) $stranger->tokens()->where('name', 'stranger')->value('id');

        $this->freshAuth();

        $this->withToken($laptop)
            ->deleteJson("/api/v1/admin/auth/tokens/{$strangerId}")
            ->assertNotFound();
    }

    public function test_user_can_create_an_additional_named_token(): void
    {
        $admin = $this->userWithRole(Role::SUPER_ADMIN);
        $session = $this->tokenFor($admin);

        $response = $this->withToken($session)
            ->postJson('/api/v1/admin/auth/tokens', ['name' => 'CI Windows']);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'CI Windows')
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->freshAuth();

        $this->withToken($response->json('data.token'))
            ->getJson('/api/v1/admin/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $admin->email);

        $this->freshAuth();

        $this->withToken($session)
            ->postJson('/api/v1/admin/auth/tokens', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name']]);
    }

    /*
    |--------------------------------------------------------------------------
    | Compatibilidad con el token compartido legacy
    |--------------------------------------------------------------------------
    */

    public function test_the_legacy_shared_token_still_works_while_migration_is_open(): void
    {
        $this->withHeaders(['X-Admin-Token' => config('api.admin.token')])
            ->getJson('/api/v1/admin/categories')
            ->assertOk();

        $this->withHeaders(['X-Admin-Token' => 'wrong-token'])
            ->getJson('/api/v1/admin/categories')
            ->assertStatus(401);
    }

    public function test_disabling_the_legacy_token_closes_the_shared_token_path(): void
    {
        config(['api.admin.legacy_enabled' => false]);

        $this->withHeaders(['X-Admin-Token' => config('api.admin.token')])
            ->getJson('/api/v1/admin/categories')
            ->assertStatus(401);

        // Sanctum sigue siendo la vía de entrada.
        $admin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->freshAuth();

        $this->withToken($this->tokenFor($admin))
            ->getJson('/api/v1/admin/categories')
            ->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | Migración de datos
    |--------------------------------------------------------------------------
    */

    public function test_role_seeder_backfills_the_legacy_role_column_once(): void
    {
        $legacyAdmin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $this->seed(RoleSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->assertTrue($legacyAdmin->refresh()->hasRole(Role::SUPER_ADMIN));
        $this->assertFalse($customer->refresh()->hasRole(Role::SUPER_ADMIN));
        $this->assertSame(1, $legacyAdmin->roles()->count());
    }
}
