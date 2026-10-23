<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Provider;
use App\Models\Restaurant;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Cobertura de los defectos de integridad de datos encontrados al auditar.
 *
 * Cada prueba reproduce un bug que llegó a existir: un seeder que borraba
 * negocios en cascada, un PATCH que respondía 200 sin ocultar nada, un
 * morph duplicado que nunca funcionó, y un flush de caché que era no-op
 * tras un arranque en frío.
 */
class DataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function adminHeaders(): array
    {
        return ['X-Admin-Token' => config('api.admin.token')];
    }

    private function provider(string $type = 'restaurants'): Provider
    {
        return Provider::create([
            'business_name' => 'Proveedor de prueba',
            'type' => $type,
            'status' => 'approved',
            'verification_status' => 'verified',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* #6 CategorySeeder destruía datos en cascada */
    /* ------------------------------------------------------------------ */

    public function test_category_seeder_never_deletes_businesses(): void
    {
        $provider = $this->provider();
        $business = Business::factory()->create([
            'status' => 'approved',
            'provider_id' => $provider->id,
        ]);

        $this->assertSame(1, Business::query()->where('category_id', $business->category_id)->count());

        $this->seed(CategorySeeder::class);

        // La categoría legacy queda desactivada, no borrada: el negocio y su
        // categoría sobreviven al seed.
        $this->assertNotNull($business->fresh());
        $this->assertNotNull($business->category()->first());
        $this->assertDatabaseHas('businesses', ['id' => $business->id]);
    }

    public function test_retired_category_disappears_from_the_public_catalog(): void
    {
        $legacy = Category::create(['slug' => 'cocina-raizal', 'name' => 'Cocina raizal', 'icon' => 'fish']);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'cocina-raizal');

        $this->seed(CategorySeeder::class);

        $this->assertSame(Category::INACTIVE, $legacy->fresh()->status);

        // Solo quedan las 4 canónicas que el propio seeder crea.
        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonMissing(['slug' => 'cocina-raizal']);

        $this->getJson('/api/v1/categories/cocina-raizal')->assertNotFound();
    }

    public function test_retiring_a_category_keeps_its_bookings(): void
    {
        $business = Business::factory()->create(['status' => 'approved']);
        $customer = Customer::factory()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'bookable_type' => $business::class,
            'bookable_id' => $business->id,
            'status' => 'confirmed',
        ]);

        $this->seed(CategorySeeder::class);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);
    }

    /* ------------------------------------------------------------------ */
    /* #7 Doble verdad status / is_active */
    /* ------------------------------------------------------------------ */

    public function test_patching_status_to_inactive_actually_hides_the_category(): void
    {
        $category = Category::create(['slug' => 'tours', 'name' => 'Tours', 'icon' => 'boat']);

        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(1, 'data');

        $this->patchJson(
            "/api/v1/admin/categories/{$category->id}",
            ['name' => 'Tours', 'slug' => 'tours', 'status' => 'inactive'],
            $this->adminHeaders(),
        )->assertOk();

        // Antes respondía 200 y la categoría seguía en el listado, porque el
        // listado filtraba por `is_active` y el PATCH escribía `status`.
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/categories/tours')->assertNotFound();

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'status' => 'inactive']);
    }

    public function test_patching_is_active_to_false_hides_the_category_too(): void
    {
        $category = Category::create([
            'slug' => 'panaderias',
            'name' => 'Panaderías',
            'icon' => 'cafe',
            'is_active' => false,
        ]);

        // La columna legacy se adopta como `status`: las dos puertas dicen
        // lo mismo.
        $this->assertSame(Category::INACTIVE, $category->fresh()->status);
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_status_and_legacy_columns_stay_in_sync(): void
    {
        $category = Category::create(['slug' => 'tours', 'name' => 'Tours', 'icon' => 'boat', 'order' => 3]);

        $this->assertSame(3, $category->fresh()->sort_order);
        $this->assertTrue($category->fresh()->is_active);

        $category->update(['status' => Category::INACTIVE]);
        $this->assertFalse($category->fresh()->is_active);

        $category->update(['order' => 9]);
        $this->assertSame(9, $category->fresh()->sort_order);
    }

    public function test_admin_rejects_a_null_status(): void
    {
        $category = Category::create(['slug' => 'tours', 'name' => 'Tours', 'icon' => 'boat']);

        // Un `status` NULL salía del listado sin avisar a nadie.
        $this->patchJson(
            "/api/v1/admin/categories/{$category->id}",
            ['name' => 'Tours', 'slug' => 'tours', 'status' => null],
            $this->adminHeaders(),
        )->assertUnprocessable();
    }

    /* ------------------------------------------------------------------ */
    /* #8 Morph duplicado service_type / service_id */
    /* ------------------------------------------------------------------ */

    public function test_duplicate_service_morph_is_gone(): void
    {
        $this->assertFalse(
            Schema::hasColumns('bookings', ['service_type', 'service_id']),
            'service_type/service_id se reintrodujeron: el morph de bookings debe ser solo bookable_*.',
        );
    }

    public function test_booking_exposes_a_single_service_relation(): void
    {
        $this->assertTrue(method_exists(Booking::class, 'bookable'));
        $this->assertFalse(
            method_exists(Booking::class, 'service'),
            'Booking::service() resolvía contra type/id y devolvía null.',
        );
    }

    public function test_marketplace_booking_resolves_its_provider(): void
    {
        $provider = $this->provider();
        $restaurant = Restaurant::create([
            'provider_id' => $provider->id,
            'name' => 'Casa del Puerto',
            'slug' => 'casa-del-puerto',
            'status' => 'approved',
        ]);
        $customer = Customer::factory()->create();

        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'bookable_type' => $restaurant::class,
            'bookable_id' => $restaurant->id,
            'provider_id' => $provider->id,
        ]);

        // El proveedor se deriva de bookable, la única relación real: sin
        // esto la reserva se creaba sin destinatario y no notificaba.
        $this->assertSame($provider->id, $booking->fresh()->provider_id);
        $this->assertTrue($booking->fresh()->bookable->is($restaurant));
    }

    /* ------------------------------------------------------------------ */
    /* #9 Flush de caché no-op tras arranque en frío */
    /* ------------------------------------------------------------------ */

    public function test_flush_invalidates_a_cold_cache(): void
    {
        // Arranque en frío real: sin contador, y sin que ningún guardado lo
        // haya creado por el camino. `saveQuietly` salta los eventos de
        // Category::booted que dispararían el flush, así que el contador
        // sigue ausente como en un proceso recién levantado.
        Cache::forget('catalog:categories:version');

        $category = new Category(['slug' => 'tours', 'name' => 'Tours', 'icon' => 'boat']);
        $category->saveQuietly();

        // Primera lectura: puebla el bucket con la versión que `version()`
        // resuelve en frío.
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(1, 'data');

        $this->assertNull(
            Cache::get('catalog:categories:version'),
            'El contador no debería existir todavía: así es como arranca un proceso nuevo.',
        );

        $category->update(['status' => Category::INACTIVE]);

        // `increment()` sobre una clave ausente escribe 1, no 2. Si el
        // default de `version()` fuera 1, el bucket seguiría siendo v1 y la
        // entrada vieja se serviría hasta que expirara su TTL.
        $this->assertNotNull(
            Cache::get('catalog:categories:version'),
            'El flush debe crear el contador aunque no esté cacheado.',
        );

        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_catalog_cache_serves_fresh_entries_after_a_flush(): void
    {
        Cache::forget('catalog:categories:version');

        $category = Category::create(['slug' => 'tours', 'name' => 'Tours', 'icon' => 'boat']);

        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(1, 'data');

        $category->update(['status' => Category::INACTIVE]);

        // El guard de Category::booted() dispara el flush; si el contador no
        // se moviera, el listado seguiría sirviendo la copia cacheada.
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(0, 'data');
    }
}
