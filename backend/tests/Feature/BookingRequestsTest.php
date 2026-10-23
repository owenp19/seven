<?php

namespace Tests\Feature;

use App\Mail\BookingStatusMail;
use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Experience;
use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingRequestsTest extends TestCase
{
    use RefreshDatabase;

    private function adminHeaders(): array
    {
        return ['X-Admin-Token' => config('api.admin.token')];
    }

    private function customerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Laura Gómez',
            'phone' => '+57 300 210 3344',
            'email' => 'laura@mail.test',
        ], $overrides);
    }

    private function provider(array $overrides = []): Provider
    {
        return Provider::create(array_merge([
            'business_name' => 'Seven Excursions',
            'owner_name' => 'Carlos Archbold',
            'email' => 'excursions@seven.test',
            'phone' => '+57 300 000 0000',
            'type' => 'tour_operator',
            'status' => 'approved',
        ], $overrides));
    }

    public function test_booking_response_includes_whatsapp_link_with_code(): void
    {
        $business = Business::factory()->create([
            'status' => 'approved',
            'whatsapp' => '+57 301 555 0100',
        ]);

        $response = $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'business',
            'bookable_id' => $business->id,
            'type' => 'gastronomic',
            'start_at' => now()->addDays(2)->setTime(19, 0)->toDateTimeString(),
            'guests' => 4,
            'customer' => $this->customerPayload(),
        ], ['X-Install-Id' => 'SEVEN-DEVICE-0001']);

        $response->assertCreated();
        $code = $response->json('data.booking_code');

        $this->assertNotNull($response->json('data.whatsapp'));
        $this->assertStringContainsString('wa.me/573015550100', $response->json('data.whatsapp.url'));
        $this->assertStringContainsString($code, $response->json('data.whatsapp.message'));
        $this->assertStringContainsString($code, $response->json('data.whatsapp.url'));
    }

    public function test_booking_whatsapp_falls_back_to_provider_phone(): void
    {
        $provider = $this->provider(['phone' => '+57 310 999 8877']);

        $accommodation = Accommodation::factory()->create([
            'status' => 'approved',
            'provider_id' => $provider->id,
        ]);

        $response = $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'accommodation',
            'bookable_id' => $accommodation->id,
            'type' => 'accommodation',
            'start_at' => now()->addDays(3)->toDateTimeString(),
            'end_at' => now()->addDays(5)->toDateTimeString(),
            'guests' => 2,
            'customer' => $this->customerPayload(),
        ], ['X-Install-Id' => 'SEVEN-DEVICE-0001']);

        $response->assertCreated();
        $this->assertStringContainsString('wa.me/573109998877', $response->json('data.whatsapp.url'));
        $this->assertSame('+57 310 999 8877', $response->json('data.whatsapp.to'));
    }

    public function test_booking_whatsapp_is_null_without_contact(): void
    {
        $business = Business::factory()->create([
            'status' => 'approved',
            'whatsapp' => null,
            'phone' => null,
        ]);

        $response = $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'business',
            'bookable_id' => $business->id,
            'type' => 'gastronomic',
            'start_at' => now()->addDays(2)->toDateTimeString(),
            'guests' => 2,
            'customer' => $this->customerPayload(),
        ], ['X-Install-Id' => 'SEVEN-DEVICE-0001']);

        $response->assertCreated()
            ->assertJsonPath('data.whatsapp', null);
    }

    public function test_booking_resolves_provider_from_bookable(): void
    {
        $provider = $this->provider();

        $business = Business::factory()->create([
            'status' => 'approved',
            'provider_id' => $provider->id,
        ]);

        $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'business',
            'bookable_id' => $business->id,
            'type' => 'gastronomic',
            'start_at' => now()->addDays(2)->toDateTimeString(),
            'guests' => 2,
            'customer' => $this->customerPayload(['phone' => '+57 320 111 2222']),
        ], ['X-Install-Id' => 'SEVEN-DEVICE-0001'])->assertCreated();

        $booking = Booking::latest('id')->firstOrFail();
        $this->assertSame($provider->id, $booking->provider_id);
    }

    public function test_public_transitions_record_history(): void
    {
        $customer = Customer::factory()->create(['email' => null]);
        $business = Business::factory()->create(['status' => 'approved']);

        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'booking_code' => 'SEV-FLOW01',
            'bookable_type' => $business::class,
            'bookable_id' => $business->id,
            'status' => 'pending',
            'booking_access_hash' => hash('sha256', 'tok-flow01'),
        ]);

        // Confirmar es una decisión del negocio: el endpoint público la rechaza.
        $this->putJson('/api/v1/bookings/SEV-FLOW01', ['status' => 'confirmed'])
            ->assertForbidden();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'pending']);

        $this->withHeaders($this->adminHeaders())
            ->patchJson('/api/v1/admin/bookings/SEV-FLOW01/status', ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'from_status' => 'pending',
            'to_status' => 'confirmed',
            'changed_by' => 'admin',
        ]);

        // Cancelar sí es competencia del cliente.
        $this->withHeaders(['X-Booking-Token' => 'tok-flow01'])
            ->deleteJson('/api/v1/bookings/SEV-FLOW01')
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'from_status' => 'confirmed',
            'to_status' => 'cancelled',
            'changed_by' => 'customer',
        ]);
    }

    public function test_admin_booking_panel_requires_token(): void
    {
        $this->getJson('/api/v1/admin/bookings')->assertStatus(401);
        $this->patchJson('/api/v1/admin/bookings/SEV-X/status', ['status' => 'confirmed'])->assertStatus(401);
    }

    public function test_admin_booking_panel_lists_and_filters(): void
    {
        $category = Category::create(['slug' => 'natacion', 'name' => 'Navegación']);
        $provider = $this->provider();

        $experience = Experience::factory()->create([
            'status' => 'approved',
            'category_id' => $category->id,
            'provider_id' => $provider->id,
        ]);

        $customer = Customer::factory()->create(['name' => 'Leidy Baena', 'phone' => '+57 300 444 5555']);

        $pending = Booking::factory()->create([
            'customer_id' => $customer->id,
            'booking_code' => 'SEV-FLT01',
            'bookable_type' => $experience::class,
            'bookable_id' => $experience->id,
            'provider_id' => $provider->id,
            'status' => 'pending',
            'start_at' => now()->addDays(2),
            'guests' => 3,
        ]);

        Booking::factory()->create([
            'customer_id' => $customer->id,
            'booking_code' => 'SEV-FLT02',
            'bookable_type' => $experience::class,
            'bookable_id' => $experience->id,
            'provider_id' => $provider->id,
            'status' => 'confirmed',
            'start_at' => now()->addDays(20),
            'guests' => 2,
        ]);

        $this->withHeaders($this->adminHeaders())
            ->getJson('/api/v1/admin/bookings')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.pagination.total', 2);

        $this->withHeaders($this->adminHeaders())
            ->getJson('/api/v1/admin/bookings?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.booking_code', 'SEV-FLT01');

        $this->withHeaders($this->adminHeaders())
            ->getJson("/api/v1/admin/bookings?provider_id={$provider->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->withHeaders($this->adminHeaders())
            ->getJson("/api/v1/admin/bookings?category_id={$category->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->withHeaders($this->adminHeaders())
            ->getJson('/api/v1/admin/bookings?from='.now()->addDays(1)->toDateString().'&to='.now()->addDays(5)->toDateString())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.booking_code', 'SEV-FLT01');

        $this->withHeaders($this->adminHeaders())
            ->getJson('/api/v1/admin/bookings?q=SEV-FLT02')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withHeaders($this->adminHeaders())
            ->get('/api/v1/admin/bookings/'.$pending->booking_code)
            ->assertOk()
            ->assertJsonPath('data.status_label', 'Nueva');
    }

    public function test_admin_booking_show_includes_trace_history(): void
    {
        $provider = $this->provider();
        $experience = Experience::factory()->create(['status' => 'approved', 'provider_id' => $provider->id]);

        $booking = Booking::factory()->create([
            'customer_id' => Customer::factory()->create()->id,
            'booking_code' => 'SEV-TRCE01',
            'bookable_type' => $experience::class,
            'bookable_id' => $experience->id,
            'provider_id' => $provider->id,
            'status' => 'pending',
        ]);

        $this->withHeaders($this->adminHeaders())
            ->patchJson('/api/v1/admin/bookings/SEV-TRCE01/status', ['status' => 'confirmed', 'note' => 'Confirmado vía WhatsApp'])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.status_label', 'Confirmada');

        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'from_status' => 'pending',
            'to_status' => 'confirmed',
            'changed_by' => 'admin',
            'note' => 'Confirmado vía WhatsApp',
        ]);

        $this->withHeaders($this->adminHeaders())
            ->getJson('/api/v1/admin/bookings/SEV-TRCE01')
            ->assertOk()
            ->assertJsonCount(1, 'data.histories')
            ->assertJsonPath('data.histories.0.to_status', 'confirmed');
    }

    public function test_admin_booking_patch_validates_and_notifies(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create(['email' => 'cliente@mail.test']);
        $business = Business::factory()->create(['status' => 'approved']);

        Booking::factory()->create([
            'customer_id' => $customer->id,
            'booking_code' => 'SEV-NTF01',
            'bookable_type' => $business::class,
            'bookable_id' => $business->id,
            'status' => 'pending',
        ]);

        $this->withHeaders($this->adminHeaders())
            ->patchJson('/api/v1/admin/bookings/SEV-NTF01/status', ['status' => 'invalid'])
            ->assertStatus(422);

        $this->withHeaders($this->adminHeaders())
            ->patchJson('/api/v1/admin/bookings/SEV-NTF01/status', ['status' => 'confirmed'])
            ->assertOk();

        $this->assertDatabaseHas('bookings', ['booking_code' => 'SEV-NTF01', 'status' => 'confirmed']);

        Mail::assertSent(BookingStatusMail::class);
    }

    public function test_admin_manages_providers_and_links_services(): void
    {
        $this->withHeaders($this->adminHeaders())
            ->postJson('/api/v1/admin/providers', [
                'business_name' => 'Pesca y Buceo San Andrés',
                'owner_name' => 'Yanis Pomare',
                'phone' => '+57 300 111 2233',
                'type' => 'experience_provider',
                'status' => 'approved',
            ])
            ->assertCreated()
            ->assertJsonPath('data.business_name', 'Pesca y Buceo San Andrés');

        $provider = Provider::firstOrFail();

        $this->withHeaders($this->adminHeaders())
            ->getJson('/api/v1/admin/providers?type=experience_provider')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $category = Category::create(['slug' => 'buceo', 'name' => 'Buceo']);

        $this->withHeaders($this->adminHeaders())
            ->postJson('/api/v1/admin/businesses', [
                'provider_id' => $provider->id,
                'name' => 'Buceo Coco',
                'slug' => 'buceo-coco',
                'category_id' => $category->id,
                'status' => 'approved',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Buceo Coco');

        $this->assertDatabaseHas('businesses', ['provider_id' => $provider->id]);
    }
}
