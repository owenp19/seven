<?php

namespace Tests\Feature;

use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Experience;
use App\Models\Inn;
use App\Models\Provider;
use App\Models\Restaurant;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingsApiTest extends TestCase
{
    use RefreshDatabase;

    private const DEVICE_ID = 'SEVEN-DEVICE-0001';

    private function deviceHeaders(string $id = self::DEVICE_ID): array
    {
        return ['X-Install-Id' => $id];
    }

    private function bookingToken(): string
    {
        return 'tok-'.Str::random(16);
    }

    private function customerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Laura Gómez',
            'phone' => '+57 300 210 3344',
            'email' => 'laura@mail.test',
        ], $overrides);
    }

    public function test_creates_gastronomic_booking(): void
    {
        $business = Business::factory()->create(['status' => 'approved']);

        $response = $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'business',
            'bookable_id' => $business->id,
            'type' => 'gastronomic',
            'start_at' => now()->addDays(2)->setTime(19, 0)->toDateTimeString(),
            'guests' => 4,
            'customer' => $this->customerPayload(),
        ], $this->deviceHeaders());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'gastronomic')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.guests', 4)
            // La credencial se entrega una única vez, en la creación.
            ->assertJsonStructure(['data' => ['booking_access_token']]);

        $this->assertDatabaseHas('bookings', [
            'type' => 'gastronomic',
            'install_id' => self::DEVICE_ID,
        ]);
        $this->assertDatabasehas('customers', ['phone' => '+57 300 210 3344']);
    }

    public function test_creates_accommodation_booking_with_amount(): void
    {
        $accommodation = Accommodation::factory()->create(['status' => 'approved', 'price_per_night' => 200000]);

        $response = $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'accommodation',
            'bookable_id' => $accommodation->id,
            'type' => 'accommodation',
            'start_at' => now()->addDays(2)->setTime(15, 0)->toDateTimeString(),
            'end_at' => now()->addDays(4)->setTime(12, 0)->toDateTimeString(),
            'guests' => 2,
            'customer' => $this->customerPayload(),
        ], $this->deviceHeaders());

        $response->assertCreated()
            ->assertJsonPath('data.total_amount', 400000)
            ->assertJsonPath('data.bookable.type', 'accommodation');
    }

    public function test_creates_experience_booking_with_amount(): void
    {
        $experience = Experience::factory()->create(['status' => 'approved', 'price_per_person' => 120000]);

        $response = $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'experience',
            'bookable_id' => $experience->id,
            'type' => 'experience',
            'start_at' => now()->addDays(3)->setTime(9, 0)->toDateTimeString(),
            'guests' => 3,
            'customer' => $this->customerPayload(),
        ], $this->deviceHeaders());

        $response->assertCreated()
            ->assertJsonPath('data.total_amount', 360000)
            ->assertJsonPath('data.bookable.type', 'experience');
    }

    public function test_rejects_booking_for_pending_business(): void
    {
        $business = Business::factory()->create(['status' => 'pending']);

        $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'business',
            'bookable_id' => $business->id,
            'type' => 'gastronomic',
            'start_at' => now()->addDays(2)->toDateTimeString(),
            'guests' => 2,
            'customer' => $this->customerPayload(),
        ], $this->deviceHeaders())->assertNotFound();
    }

    public function test_creates_tour_booking_with_install_id_in_body(): void
    {
        $provider = Provider::create(['business_name' => 'Operador Isleño', 'type' => 'experiences', 'status' => 'approved', 'verification_status' => 'verified']);
        $tour = Tour::create([
            'provider_id' => $provider->id,
            'name' => 'Johnny Cay en lancha',
            'slug' => 'johnny-cay-en-lancha',
            'price' => 95000,
            'category' => 'Aventura',
            'location' => 'Muelle Principal',
            'duration' => '4 horas',
            'status' => 'approved',
        ]);

        // Sin cabecera X-Install-Id: el `install_id` del body sigue siendo
        // aceptado como respaldo del header (compatibilidad con clientes y
        // tests previos).
        $response = $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'tour',
            'bookable_id' => $tour->id,
            'type' => 'experience',
            'start_at' => now()->addDays(3)->setTime(9, 0)->toDateTimeString(),
            'guests' => 2,
            'install_id' => 'SEVEN-DEVICE-0001',
            'customer' => $this->customerPayload(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.total_amount', 190000)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('bookings', [
            'bookable_type' => $tour::class,
            'install_id' => 'SEVEN-DEVICE-0001',
            'total_amount' => 190000,
        ]);
    }

    public function test_header_install_id_takes_precedence_over_body(): void
    {
        $provider = Provider::create(['business_name' => 'Operador Isleño 2', 'type' => 'experiences', 'status' => 'approved', 'verification_status' => 'verified']);
        $tour = Tour::create([
            'provider_id' => $provider->id,
            'name' => 'Vuelta por el manglar',
            'slug' => 'vuelta-por-el-manglar',
            'price' => 60000,
            'status' => 'approved',
        ]);

        $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'tour',
            'bookable_id' => $tour->id,
            'type' => 'experience',
            'start_at' => now()->addDays(2)->setTime(8, 0)->toDateTimeString(),
            'guests' => 1,
            // El header manda: el body con identidad distinta se descarta.
            'install_id' => 'SEVEN-OTRO-DEVICE',
            'customer' => $this->customerPayload(),
        ], $this->deviceHeaders('SEVEN-HEADER-GANA'))->assertCreated();

        $this->assertDatabaseHas('bookings', [
            'bookable_type' => $tour::class,
            'install_id' => 'SEVEN-HEADER-GANA',
        ]);
    }

    public function test_rejects_booking_without_install_id(): void
    {
        $provider = Provider::create(['business_name' => 'Operador Isleño 3', 'type' => 'experiences', 'status' => 'approved', 'verification_status' => 'verified']);
        $tour = Tour::create([
            'provider_id' => $provider->id,
            'name' => 'Vuelta a la isla',
            'slug' => 'vuelta-a-la-isla',
            'price' => 60000,
            'status' => 'approved',
        ]);

        $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'tour',
            'bookable_id' => $tour->id,
            'type' => 'experience',
            'start_at' => now()->addDays(2)->setTime(8, 0)->toDateTimeString(),
            'guests' => 1,
            'customer' => $this->customerPayload(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('install_id');

        $this->assertDatabaseMissing('bookings', ['bookable_type' => $tour::class]);
    }

    public function test_rejects_a_malformed_install_id(): void
    {
        $provider = Provider::create(['business_name' => 'Operador Isleño 4', 'type' => 'experiences', 'status' => 'approved', 'verification_status' => 'verified']);
        $tour = Tour::create([
            'provider_id' => $provider->id,
            'name' => 'Manglar en kayak',
            'slug' => 'manglar-en-kayak',
            'price' => 70000,
            'status' => 'approved',
        ]);

        // Caracteres fuera del alfabeto permitido y longitud sin tope.
        $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'tour',
            'bookable_id' => $tour->id,
            'type' => 'experience',
            'start_at' => now()->addDays(2)->setTime(8, 0)->toDateTimeString(),
            'guests' => 1,
            'install_id' => '!nvalid id with spaces!!',
            'customer' => $this->customerPayload(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('install_id');
    }

    public function test_creates_restaurant_booking(): void
    {
        $restaurant = $this->restaurant();

        $response = $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'restaurant',
            'bookable_id' => $restaurant->id,
            'type' => 'gastronomic',
            'start_at' => now()->addDays(2)->setTime(19, 30)->toDateTimeString(),
            'guests' => 2,
            'customer' => $this->customerPayload(),
        ], $this->deviceHeaders());

        // El restaurante no tiene precio propio en el listado: el monto queda
        // en 0 hasta que el negocio lo confirme desde el panel.
        $response->assertCreated()
            ->assertJsonPath('data.bookable.type', 'restaurant')
            ->assertJsonPath('data.bookable.id', $restaurant->id)
            ->assertJsonPath('data.total_amount', 0);

        $this->assertDatabaseHas('bookings', [
            'bookable_type' => $restaurant::class,
            'bookable_id' => $restaurant->id,
            'provider_id' => $restaurant->provider_id,
        ]);
    }

    public function test_creates_inn_booking_billed_by_nights(): void
    {
        $inn = $this->inn(['price' => 180000]);

        $response = $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'inn',
            'bookable_id' => $inn->id,
            'type' => 'accommodation',
            'start_at' => now()->addDays(2)->setTime(15, 0)->toDateTimeString(),
            'end_at' => now()->addDays(4)->setTime(12, 0)->toDateTimeString(),
            'guests' => 2,
            'customer' => $this->customerPayload(),
        ], $this->deviceHeaders());

        $response->assertCreated()
            ->assertJsonPath('data.bookable.type', 'inn')
            ->assertJsonPath('data.total_amount', 360000);
    }

    public function test_inn_booking_without_end_at_is_charged_one_night(): void
    {
        $inn = $this->inn(['price' => 180000]);

        $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'inn',
            'bookable_id' => $inn->id,
            'type' => 'accommodation',
            'start_at' => now()->addDays(2)->setTime(15, 0)->toDateTimeString(),
            'guests' => 2,
            'customer' => $this->customerPayload(),
        ], $this->deviceHeaders())->assertCreated()
            ->assertJsonPath('data.total_amount', 180000);
    }

    public function test_rejects_booking_for_pending_marketplace_bookables(): void
    {
        $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'restaurant',
            'bookable_id' => $this->restaurant(['status' => 'pending'])->id,
            'type' => 'gastronomic',
            'start_at' => now()->addDays(2)->toDateTimeString(),
            'guests' => 2,
            'customer' => $this->customerPayload(),
        ], $this->deviceHeaders())->assertNotFound();

        $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'inn',
            'bookable_id' => $this->inn(['status' => 'pending'])->id,
            'type' => 'accommodation',
            'start_at' => now()->addDays(2)->toDateTimeString(),
            'guests' => 2,
            'customer' => $this->customerPayload(),
        ], $this->deviceHeaders())->assertNotFound();
    }

    public function test_marketplace_bookables_expose_their_booking_relation(): void
    {
        $restaurant = $this->restaurant();
        $inn = $this->inn();

        $this->assertCount(0, $restaurant->bookings);
        $this->assertCount(0, $inn->bookings);
    }

    private function restaurant(array $attributes = []): Restaurant
    {
        $provider = Provider::create([
            'business_name' => 'Restaurante del Puerto',
            'type' => 'restaurants',
            'status' => 'approved',
            'verification_status' => 'verified',
        ]);

        return Restaurant::create(array_merge([
            'provider_id' => $provider->id,
            'name' => 'Casa del Puerto',
            'slug' => 'casa-del-puerto',
            'phone' => '+57 300 111 2233',
            'status' => 'approved',
        ], $attributes));
    }

    private function inn(array $attributes = []): Inn
    {
        $provider = Provider::create([
            'business_name' => 'Posada Nativa',
            'type' => 'inns',
            'status' => 'approved',
            'verification_status' => 'verified',
        ]);

        return Inn::create(array_merge([
            'provider_id' => $provider->id,
            'name' => 'Cabaña Kokolo',
            'slug' => 'cabana-kokolo',
            'price' => 180000,
            'status' => 'approved',
        ], $attributes));
    }

    public function test_validates_required_fields(): void
    {
        $this->postJson('/api/v1/bookings', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['bookable_type', 'start_at', 'customer', 'install_id']);
    }

    public function test_validates_invalid_bookable_type(): void
    {
        $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'car',
            'bookable_id' => 1,
            'type' => 'gastronomic',
            'start_at' => now()->addDays(2)->toDateTimeString(),
            'guests' => 2,
            'customer' => $this->customerPayload(),
        ], $this->deviceHeaders())->assertUnprocessable()
            ->assertJsonValidationErrors('bookable_type');
    }

    public function test_dedups_customer_by_phone(): void
    {
        $business = Business::factory()->create(['status' => 'approved']);
        $payload = ['bookable_type' => 'business', 'bookable_id' => $business->id, 'type' => 'gastronomic', 'start_at' => now()->addDays(2)->toDateTimeString(), 'guests' => 2, 'customer' => $this->customerPayload()];

        $this->postJson('/api/v1/bookings', $payload, $this->deviceHeaders())->assertCreated();

        $customer = Customer::where('phone', '+57 300 210 3344')->first();
        $this->assertNotNull($customer);

        $this->postJson('/api/v1/bookings', array_merge($payload, [
            'start_at' => now()->addDays(5)->toDateTimeString(),
            'customer' => $this->customerPayload(['email' => 'otro-correo@mail.test']),
        ]), $this->deviceHeaders())->assertCreated();

        $this->assertDatabaseCount('customers', 1);
    }

    public function test_looks_up_booking_by_code(): void
    {
        $customer = Customer::factory()->create();
        $business = Business::factory()->create(['status' => 'approved']);
        $token = $this->bookingToken();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'bookable_type' => $business::class,
            'bookable_id' => $business->id,
            'booking_access_hash' => hash('sha256', $token),
            'status' => 'confirmed',
        ]);

        $this->withHeaders(['X-Booking-Token' => $token])
            ->getJson("/api/v1/bookings/{$booking->booking_code}")
            ->assertOk()
            ->assertJsonPath('data.booking_code', $booking->booking_code)
            ->assertJsonPath('data.status', 'confirmed');
    }

    public function test_cancels_booking_with_access_token(): void
    {
        $customer = Customer::factory()->create();
        $business = Business::factory()->create(['status' => 'approved']);
        $token = $this->bookingToken();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'bookable_type' => $business::class,
            'bookable_id' => $business->id,
            'booking_access_hash' => hash('sha256', $token),
            // El factory sortea el estado: fijarlo aquí evita que la prueba
            // dependa del azar (completed es terminal y no admite cambios).
            'status' => 'pending',
        ]);

        $this->withHeaders(['X-Booking-Token' => $token])
            ->deleteJson("/api/v1/bookings/{$booking->booking_code}")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
    }
}
