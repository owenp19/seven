<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Provider;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Borrado de servicios en el panel del proveedor (Parte 4).
 *
 * El proveedor puede eliminar sus propios servicios mientras no exista
 * historial: un borrado que dejaría reservas huérfanas se rechaza con 422
 * AUNQUE el payload pida `?force=1`. El override forzado es exclusivo del
 * operador (backoffice), donde se conserva para retirar registros con
 * historial.
 */
class ProviderServiceDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function providerOwner(string $businessName = 'Borrado S.A.S'): Provider
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

    private function ownedBusiness(Provider $provider): Business
    {
        return Business::factory()->create(['provider_id' => $provider->id]);
    }

    private function bookGame(Business $business): Booking
    {
        return Booking::factory()->create([
            'bookable_type' => $business::class,
            'bookable_id' => $business->id,
        ]);
    }

    private function actAsProvider(Provider $provider): void
    {
        $this->withToken($provider->user->createToken('delete-test', ['provider'])->plainTextToken);
    }

    private function adminToken(): string
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);

        return $admin->createToken('delete-admin', ['admin'])->plainTextToken;
    }

    public function test_a_provider_can_delete_an_owned_service_without_history(): void
    {
        $provider = $this->providerOwner();
        $business = $this->ownedBusiness($provider);

        $this->actAsProvider($provider);

        $this->deleteJson("/api/v1/provider/businesses/{$business->id}?force=1")
            ->assertOk();

        $this->assertDatabaseMissing('businesses', ['id' => $business->id]);
    }

    public function test_a_provider_cannot_force_delete_a_service_with_bookings(): void
    {
        $provider = $this->providerOwner();
        $business = $this->ownedBusiness($provider);
        $this->bookGame($business);

        $this->actAsProvider($provider);

        // El proveedor pide el override, pero el historial sigue bloqueando.
        $this->deleteJson("/api/v1/provider/businesses/{$business->id}?force=1")
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['bookings']]);

        $this->assertDatabaseHas('businesses', ['id' => $business->id]);
    }

    public function test_the_force_override_still_works_for_the_operator(): void
    {
        $provider = $this->providerOwner();
        $business = $this->ownedBusiness($provider);
        $this->bookGame($business);

        $this->withToken($this->adminToken())
            ->deleteJson("/api/v1/admin/businesses/{$business->id}?force=1")
            ->assertOk();

        $this->assertDatabaseMissing('businesses', ['id' => $business->id]);
    }
}
