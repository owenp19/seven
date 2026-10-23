<?php

namespace Tests\Feature;

use App\Events\BookingStatusChanged;
use App\Mail\BookingStatusMail;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Provider;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function bookable(): Business
    {
        return Business::factory()->create();
    }

    private function adminHeaders(): array
    {
        return ['X-Admin-Token' => config('api.admin.token')];
    }

    public function test_booking_event_sends_mail_and_notifies_provider(): void
    {
        Mail::fake();

        $provider = Provider::create([
            'business_name' => 'Seven Excursions',
            'owner_name' => 'Carlos Archbold',
            'email' => 'excursions@seven.test',
            'phone' => '+57 300 000 0000',
            'type' => 'tour_operator',
            'status' => 'approved',
        ]);

        $customer = Customer::create([
            'name' => 'Ana Martínez',
            'phone' => '+573001234567',
            'email' => 'ana@example.com',
        ]);

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'booking_code' => 'SEV-TEST01',
            'bookable_type' => Business::class,
            'bookable_id' => $this->bookable()->id,
            'provider_id' => $provider->id,
            'type' => 'gastronomic',
            'start_at' => now()->addDay(),
            'guests' => 2,
            'total_amount' => 100000,
            'currency' => 'COP',
            'status' => 'confirmed',
        ]);

        event(new BookingStatusChanged($booking, 'pending'));

        Mail::assertSent(BookingStatusMail::class, fn ($mail) => $mail->hasTo('ana@example.com'));

        $this->assertDatabaseHas('notifications', [
            'provider_id' => $provider->id,
            'type' => 'booking',
        ]);
    }

    public function test_booking_event_resolves_provider_via_bookable(): void
    {
        Mail::fake();

        $provider = Provider::create([
            'business_name' => 'Seven Excursions',
            'type' => 'tour_operator',
            'status' => 'approved',
        ]);

        $tour = Tour::create([
            'provider_id' => $provider->id,
            'name' => 'Johnny Cay',
            'slug' => 'johnny-cay',
            'price' => 170000,
            'status' => 'approved',
        ]);

        $booking = Booking::create([
            'customer_id' => Customer::create(['name' => 'Ana', 'phone' => '+573001234567'])->id,
            'booking_code' => 'SEV-TEST02',
            'bookable_type' => Tour::class,
            'bookable_id' => $tour->id,
            'type' => 'gastronomic',
            'start_at' => now()->addDay(),
            'guests' => 1,
            'status' => 'pending',
        ]);

        event(new BookingStatusChanged($booking));

        $this->assertDatabaseHas('notifications', [
            'provider_id' => $provider->id,
            'type' => 'booking',
        ]);
    }

    public function test_api_booking_update_fires_notification(): void
    {
        Mail::fake();

        $customer = Customer::create([
            'name' => 'Ana Martínez',
            'phone' => '+573001234567',
            'email' => 'ana@example.com',
        ]);

        Booking::create([
            'customer_id' => $customer->id,
            'booking_code' => 'SEV-TEST03',
            'bookable_type' => Business::class,
            'bookable_id' => $this->bookable()->id,
            'type' => 'gastronomic',
            'start_at' => now()->addDay(),
            'guests' => 2,
            'total_amount' => 0,
            'status' => 'pending',
        ]);

        // Confirmar es competencia del negocio, no del cliente anónimo.
        $this->withHeaders($this->adminHeaders())
            ->patchJson('/api/v1/admin/bookings/SEV-TEST03/status', ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('bookings', ['booking_code' => 'SEV-TEST03', 'status' => 'confirmed']);

        Mail::assertSent(BookingStatusMail::class);
    }

    public function test_booking_without_customer_email_skips_mail_but_still_notifies(): void
    {
        Mail::fake();

        $provider = Provider::create([
            'business_name' => 'Seven',
            'type' => 'restaurant',
            'status' => 'approved',
        ]);

        $booking = Booking::create([
            'customer_id' => Customer::create(['name' => 'Sin mail', 'phone' => '+573001234567'])->id,
            'booking_code' => 'SEV-TEST04',
            'bookable_type' => Business::class,
            'bookable_id' => $this->bookable()->id,
            'provider_id' => $provider->id,
            'type' => 'gastronomic',
            'start_at' => now()->addDay(),
            'guests' => 1,
            'status' => 'confirmed',
        ]);

        event(new BookingStatusChanged($booking, 'pending'));

        Mail::assertNothingSent();
        $this->assertDatabaseHas('notifications', ['provider_id' => $provider->id]);
    }
}
