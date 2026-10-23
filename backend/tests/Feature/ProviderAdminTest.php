<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alta de proveedores con cuenta reclamada (Parte 5).
 *
 * Un usuario solo puede tener un proveedor: la DB lo garantiza con un índice
 * único y las reglas de validación lo traducen a 422. Al editar, el propio
 * proveedor puede reenviar su misma `user_id` sin romper la unicidad.
 */
class ProviderAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function adminHeaders(): array
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);

        return ['Authorization' => 'Bearer '.$admin->createToken('admin-test', ['admin'])->plainTextToken];
    }

    private function claimUser(): array
    {
        return ['user_id' => User::factory()->create()->id];
    }

    private function createProvider(array $overrides = []): Provider
    {
        return Provider::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'business_name' => 'Proveedor de prueba',
            'type' => 'tour_operator',
            'status' => 'approved',
        ], $overrides));
    }

    public function test_creating_a_provider_for_an_claimed_user_is_a_422(): void
    {
        $user = User::factory()->create();
        $this->createProvider(['user_id' => $user->id]);

        $this->withHeaders($this->adminHeaders())
            ->postJson('/api/v1/admin/providers', [
                'user_id' => $user->id,
                'business_name' => 'Segunda cuenta para el mismo usuario',
                'type' => 'tour_operator',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');

        $this->assertSame(1, Provider::where('user_id', $user->id)->count());
    }

    public function test_an_unclaimed_user_can_be_assigned_only_once(): void
    {
        User::factory()->create(); // otro usuario sin proveedor

        $headers = $this->adminHeaders();
        $payload = [
            'user_id' => $this->createProvider()->user->id,
            'business_name' => 'Intento de reasignación',
            'type' => 'tour_operator',
        ];

        $this->withHeaders($headers)
            ->postJson('/api/v1/admin/providers', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    public function test_editing_a_provider_can_resubmit_its_own_user_id(): void
    {
        $provider = $this->createProvider();

        $this->withHeaders($this->adminHeaders())
            ->putJson("/api/v1/admin/providers/{$provider->id}", [
                'user_id' => $provider->user_id,
                'business_name' => 'Proveedor de prueba (editado)',
                'type' => 'tour_operator',
            ])
            ->assertOk()
            ->assertJsonPath('data.business_name', 'Proveedor de prueba (editado)');
    }
}
