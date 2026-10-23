<?php

namespace Tests\Feature;

use App\Models\Accommodation;
use App\Models\Bakery;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Experience;
use App\Models\Inn;
use App\Models\News;
use App\Models\Promotion;
use App\Models\Provider;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aislamiento multi-tenant del panel del proveedor (Parte 6).
 *
 * Para cada recurso tenant, el id de otro proveedor responde 404 en show,
 * update y delete, y el listado solo trae las filas del dueño. Se cubren
 * las 7 verticales, promotions y news en una matriz; providers y bookings
 * tienen su propio caso porque su "columna de tenancy" no es provider_id.
 */
class ProviderTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public static function isolationMatrix(): array
    {
        $record = function (string $entity, callable $make): array {
            return [$entity, $make];
        };

        return [
            $record('businesses', fn (Provider $p) => Business::factory()->create(['provider_id' => $p->id])),
            $record('accommodations', fn (Provider $p) => Accommodation::factory()->create(['provider_id' => $p->id])),
            $record('experiences', fn (Provider $p) => Experience::factory()->create(['provider_id' => $p->id])),
            $record('tours', fn (Provider $p) => Tour::create(['provider_id' => $p->id, 'name' => 'Tour isla', 'slug' => 'tour-isla-'.uniqid()])),
            $record('restaurants', fn (Provider $p) => Restaurant::create(['provider_id' => $p->id, 'name' => 'Restaurante', 'slug' => 'restaurante-'.uniqid()])),
            $record('inns', fn (Provider $p) => Inn::create(['provider_id' => $p->id, 'name' => 'Posada', 'slug' => 'posada-'.uniqid()])),
            $record('bakeries', fn (Provider $p) => Bakery::create(['provider_id' => $p->id, 'name' => 'Panadería', 'slug' => 'panaderia-'.uniqid()])),
            $record('promotions', fn (Provider $p) => Promotion::create(['provider_id' => $p->id, 'title' => 'Promo de prueba'])),
            $record('news', fn (Provider $p) => News::create(['provider_id' => $p->id, 'title' => 'Nota de prueba', 'slug' => 'nota-'.uniqid()])),
        ];
    }

    private function providerOwner(string $businessName): array
    {
        $user = User::factory()->create();
        $user->assignRole(Role::PROVEEDOR_TURISTICO);

        $provider = Provider::create([
            'user_id' => $user->id,
            'business_name' => $businessName,
            'type' => 'tour_operator',
            'status' => 'approved',
            'verification_status' => 'verified',
        ]);

        return [$provider, $user->createToken('isolation', ['provider'])->plainTextToken];
    }

    private function actAs(string $token): static
    {
        // El guard de Sanctum memoiza al usuario resuelto en la primera
        // llamada del test; sin olvidarlo, cambiar de token no cambia de
        // proveedor.
        $this->app->make('auth')->forgetGuards();
        $this->withToken($token);

        return $this;
    }

    /**
     * @dataProvider isolationMatrix
     */
    public function test_a_provider_cannot_touch_another_providers_resource(string $route, callable $make): void
    {
        [$ownerA, $tokenA] = $this->providerOwner('Aislado A');
        [$ownerB, $tokenB] = $this->providerOwner('Aislado B');

        $own = $make($ownerA);
        $foreign = $make($ownerB);

        // El listado del dueño solo trae su fila.
        $this->actAs($tokenA)
            ->getJson("/api/v1/provider/{$route}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id);

        // Un id ajeno responde 404 en show, update y delete.
        $this->actAs($tokenA)
            ->getJson("/api/v1/provider/{$route}/{$foreign->id}")
            ->assertNotFound();

        $this->actAs($tokenA)
            ->putJson("/api/v1/provider/{$route}/{$foreign->id}", [])
            ->assertNotFound();

        $this->actAs($tokenA)
            ->deleteJson("/api/v1/provider/{$route}/{$foreign->id}")
            ->assertNotFound();

        $this->assertDatabaseHas($foreign->getTable(), ['id' => $foreign->id]);

        // El dueño puede ver, editar y borrar SU fila.
        $this->actAs($tokenB)
            ->getJson("/api/v1/provider/{$route}/{$foreign->id}")
            ->assertOk();
    }

    public function test_a_provider_only_sees_its_own_provider_record(): void
    {
        [$ownerA, $tokenA] = $this->providerOwner('Proveedor A');
        [$ownerB, $tokenB] = $this->providerOwner('Proveedor B');

        $this->actAs($tokenA)
            ->getJson('/api/v1/provider/providers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownerA->id);

        $this->actAs($tokenA)
            ->getJson('/api/v1/provider/providers/'.$ownerB->id)
            ->assertNotFound();

        $this->actAs($tokenA)
            ->putJson('/api/v1/provider/providers/'.$ownerB->id, [])
            ->assertNotFound();
    }

    public function test_a_provider_only_sees_its_own_bookings(): void
    {
        [$ownerA, $tokenA] = $this->providerOwner('Reservas A');
        [$ownerB] = $this->providerOwner('Reservas B');

        $own = Booking::factory()->create([
            'provider_id' => $ownerA->id,
            'bookable_type' => Business::class,
            'bookable_id' => Business::factory()->create(['provider_id' => $ownerA->id])->id,
        ]);
        $foreign = Booking::factory()->create([
            'provider_id' => $ownerB->id,
            'bookable_type' => Business::class,
            'bookable_id' => Business::factory()->create(['provider_id' => $ownerB->id])->id,
        ]);

        $this->actAs($tokenA)
            ->getJson('/api/v1/provider/bookings')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.booking_code', $own->booking_code);

        $this->actAs($tokenA)
            ->getJson('/api/v1/provider/bookings/'.$foreign->booking_code)
            ->assertNotFound();

        $this->actAs($tokenA)
            ->patchJson('/api/v1/provider/bookings/'.$foreign->booking_code.'/status', ['status' => 'confirmed'])
            ->assertNotFound();
    }
}
