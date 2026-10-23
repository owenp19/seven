<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Autenticación del panel del proveedor (Sanctum con abilities).
 *
 * Cubre los escenarios pedidos para la Parte 1: login correcto/incorrecto,
 * acceso sin token, usuario autenticado sin permiso de proveedor, usuario
 * autorizado, separación de abilities (un token `admin` no entra en
 * /provider/*), rechazo del token compartido legacy y logout/me.
 */
class ProviderAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function providerOwner(string $businessName = 'Viajes Andinos'): Provider
    {
        $user = User::factory()->create();
        $user->assignRole(Role::PROVEEDOR_TURISTICO);

        return Provider::create([
            'user_id' => $user->id,
            'business_name' => $businessName,
            'type' => 'tour_operator',
            'status' => 'approved',
            'verification_status' => 'verified',
        ]);
    }

    private function tokenFor(User $user, array $abilities = ['provider'], string $name = 'test-device'): string
    {
        return $user->createToken($name, $abilities)->plainTextToken;
    }

    private function refreshAuth(): void
    {
        $this->app->make('auth')->forgetGuards();
    }

    /*
    |--------------------------------------------------------------------------
    | Login del proveedor
    |--------------------------------------------------------------------------
    */

    public function test_a_provider_can_login_with_email_and_password(): void
    {
        $provider = $this->providerOwner();

        $response = $this->postJson('/api/v1/provider/auth/login', [
            'email' => $provider->user->email,
            'password' => 'password',
            'device_name' => 'Panel Proveedor',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', $provider->user->email)
            ->assertJsonPath('data.user.roles.0', Role::PROVEEDOR_TURISTICO)
            ->assertJsonPath('data.provider.business_name', $provider->business_name);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        // El token se emite con abilidad `provider`, distinta de la `admin`
        // del backoffice.
        $this->assertSame(['provider'], $provider->user->tokens()->sole()->abilities);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $provider->user->id,
            'tokenable_type' => User::class,
            'name' => 'Panel Proveedor',
        ]);

        $this->refreshAuth();

        // El token emitido abre el panel del proveedor.
        $this->withToken($token)
            ->getJson('/api/v1/provider/businesses')
            ->assertOk();
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        $provider = $this->providerOwner();

        $this->postJson('/api/v1/provider/auth/login', [
            'email' => $provider->user->email,
            'password' => 'definitely-not-the-password',
        ])->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['email']]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_with_unknown_email_is_rejected(): void
    {
        $this->postJson('/api/v1/provider/auth/login', [
            'email' => 'nobody@seven.test',
            'password' => 'password',
        ])->assertStatus(401);
    }

    public function test_login_payload_is_validated_by_the_form_request(): void
    {
        $this->postJson('/api/v1/provider/auth/login', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['email', 'password']]);

        $this->postJson('/api/v1/provider/auth/login', [
            'email' => 'no-es-un-email',
            'password' => '',
        ])->assertStatus(422)
            ->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    public function test_login_is_denied_to_users_without_the_provider_role(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(Role::EDITOR);

        $this->postJson('/api/v1/provider/auth/login', [
            'email' => $customer->email,
            'password' => 'password',
        ])->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['role']]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_is_denied_to_a_provider_role_without_a_linked_provider(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::PROVEEDOR_TURISTICO);

        $this->postJson('/api/v1/provider/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['role']]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /*
    |--------------------------------------------------------------------------
    | Protección de /api/v1/provider/*
    |--------------------------------------------------------------------------
    */

    public function test_provider_endpoints_reject_requests_without_a_token(): void
    {
        $this->getJson('/api/v1/provider/businesses')->assertStatus(401);
        $this->getJson('/api/v1/provider/auth/me')->assertStatus(401);
        $this->postJson('/api/v1/provider/auth/logout')->assertStatus(401);
    }

    public function test_provider_endpoints_reject_an_invalid_bearer_token(): void
    {
        $this->withToken('not-a-real-sanctum-token')
            ->getJson('/api/v1/provider/businesses')
            ->assertStatus(401);
    }

    public function test_an_authenticated_user_without_the_provider_role_is_forbidden(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::EDITOR);

        $this->withToken($this->tokenFor($user))
            ->getJson('/api/v1/provider/businesses')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_an_admin_token_cannot_enter_the_provider_panel(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);

        // Token del backoffice (abilidad `admin`): aunque resuelva un usuario
        // autenticado, no abre el panel del proveedor.
        $this->withToken($this->tokenFor($admin, ['admin']))
            ->getJson('/api/v1/provider/businesses')
            ->assertStatus(403);

        $this->withToken($this->tokenFor($admin, ['admin']))
            ->getJson('/api/v1/provider/auth/me')
            ->assertStatus(403);
    }

    public function test_a_token_without_provider_ability_is_forbidden(): void
    {
        $provider = $this->providerOwner();

        $this->withToken($this->tokenFor($provider->user, []))
            ->getJson('/api/v1/provider/businesses')
            ->assertStatus(403);
    }

    public function test_the_legacy_shared_token_cannot_enter_the_provider_panel(): void
    {
        $this->withHeaders(['X-Admin-Token' => config('api.admin.token')])
            ->getJson('/api/v1/provider/businesses')
            ->assertForbidden();
    }

    public function test_an_authorized_provider_can_access_the_panel(): void
    {
        $provider = $this->providerOwner();

        $this->refreshAuth();
        $this->withToken($this->tokenFor($provider->user))
            ->getJson('/api/v1/provider/businesses')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Logout y me
    |--------------------------------------------------------------------------
    */

    public function test_provider_can_read_me_with_its_provider_profile(): void
    {
        $provider = $this->providerOwner();

        $this->withToken($this->tokenFor($provider->user))
            ->getJson('/api/v1/provider/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.email', $provider->user->email)
            ->assertJsonPath('data.user.roles.0', Role::PROVEEDOR_TURISTICO)
            ->assertJsonPath('data.provider.id', $provider->id)
            ->assertJsonPath('data.provider.business_name', $provider->business_name);
    }

    public function test_provider_logout_revokes_the_current_token(): void
    {
        $provider = $this->providerOwner();
        $token = $this->tokenFor($provider->user);

        $this->withToken($token)
            ->postJson('/api/v1/provider/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->refreshAuth();

        $this->withToken($token)
            ->getJson('/api/v1/provider/auth/me')
            ->assertStatus(401);
    }
}
