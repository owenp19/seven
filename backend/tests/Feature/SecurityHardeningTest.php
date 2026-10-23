<?php

namespace Tests\Feature;

use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Experience;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cobertura de los hallazgos de seguridad abiertos en la auditoría de v1.
 *
 * Cada test ata una arista que, antes de estos arreglos, quedaba expuesta en
 * la API pública (anónima):
 *  - IDOR: show/update/destroy de reservas sin prueba de propiedad, y un
 *    `GET /bookings?phone=` que devolvía los booking_code de cualquiera.
 *  - Máquina de estados inexistente: BookingStatusService aceptaba las 16
 *    combinaciones, incluido cancelled -> confirmed.
 *  - Moderación: los tres availability() saltaban el scope approved().
 *  - XSS almacenado: `svg` entre los mimes de upload, servido en el mismo
 *    origen que la app.
 *  - Media: mediaUrl() resolvía el disco equivocado y cuatro Resources
 *    devolvían rutas relativas crudas.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function adminHeaders(): array
    {
        return ['X-Admin-Token' => config('api.admin.token')];
    }

    /**
     * Cabeceras del dispositivo: el X-Install-Id para el scope de LISTADO y,
     * como credencial de acceso a cada reserva, el X-Booking-Token cuyo
     * SHA-256 se guardó al crearla (ver `ownedBooking`).
     */
    private function device(string $id = 'device-alpha'): array
    {
        return ['X-Install-Id' => $id, 'X-Booking-Token' => 'tok-'.$id];
    }

    private function ownedBooking(string $installId, string $status = 'pending'): Booking
    {
        return Booking::factory()->create([
            'customer_id' => Customer::factory(),
            'status' => $status,
            'install_id' => $installId,
            'booking_access_hash' => hash('sha256', 'tok-'.$installId),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | IDOR: prueba de propiedad
    |--------------------------------------------------------------------------
    */

    public function test_another_device_cannot_read_a_booking_by_code(): void
    {
        $booking = $this->ownedBooking('device-alpha');

        $this->getJson("/api/v1/bookings/{$booking->booking_code}")
            ->assertForbidden();

        $this->getJson("/api/v1/bookings/{$booking->booking_code}", $this->device('device-beta'))
            ->assertForbidden();

        $this->getJson("/api/v1/bookings/{$booking->booking_code}", $this->device('device-alpha'))
            ->assertOk()
            ->assertJsonPath('data.booking_code', $booking->booking_code);
    }

    public function test_omitting_the_device_header_does_not_grant_access(): void
    {
        $booking = $this->ownedBooking('device-alpha');

        $this->getJson("/api/v1/bookings/{$booking->booking_code}")
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_another_device_cannot_cancel_or_update_a_booking(): void
    {
        $booking = $this->ownedBooking('device-alpha');

        $this->withHeaders($this->device('device-beta'))
            ->deleteJson("/api/v1/bookings/{$booking->booking_code}")
            ->assertForbidden();

        $this->withHeaders($this->device('device-beta'))
            ->putJson("/api/v1/bookings/{$booking->booking_code}", ['status' => 'cancelled'])
            ->assertForbidden();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'pending',
        ]);
    }

    public function test_the_owning_device_can_cancel(): void
    {
        $booking = $this->ownedBooking('device-alpha');

        $this->withHeaders($this->device('device-alpha'))
            ->deleteJson("/api/v1/bookings/{$booking->booking_code}")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_listing_bookings_requires_a_device_identifier(): void
    {
        $this->ownedBooking('device-alpha');

        $this->getJson('/api/v1/bookings')
            ->assertForbidden();
    }

    public function test_a_device_only_lists_the_bookings_it_created(): void
    {
        $mine = $this->ownedBooking('device-alpha');
        $this->ownedBooking('device-beta');

        $this->getJson('/api/v1/bookings', $this->device('device-alpha'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.booking_code', $mine->booking_code);
    }

    public function test_listing_by_phone_cannot_widen_the_device_scope(): void
    {
        $theirs = $this->ownedBooking('device-beta');

        $response = $this->getJson(
            '/api/v1/bookings?phone='.$theirs->customer->phone,
            $this->device('device-alpha'),
        );

        $response->assertOk()->assertJsonCount(0, 'data');
        $response->assertJsonMissing(['booking_code' => $theirs->booking_code]);
    }

    /*
    |--------------------------------------------------------------------------
    | Máquina de estados
    |--------------------------------------------------------------------------
    */

    public function test_a_cancelled_booking_cannot_be_confirmed_again(): void
    {
        $booking = $this->ownedBooking('device-alpha', 'cancelled');

        $this->withHeaders($this->adminHeaders())
            ->patchJson("/api/v1/admin/bookings/{$booking->booking_code}/status", ['status' => 'confirmed'])
            ->assertStatus(409);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_a_completed_booking_is_final(): void
    {
        $booking = $this->ownedBooking('device-alpha', 'completed');

        $this->withHeaders($this->adminHeaders())
            ->patchJson("/api/v1/admin/bookings/{$booking->booking_code}/status", ['status' => 'cancelled'])
            ->assertStatus(409);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'completed',
        ]);
    }

    public function test_pending_cannot_jump_straight_to_completed(): void
    {
        $booking = $this->ownedBooking('device-alpha', 'pending');

        $this->withHeaders($this->adminHeaders())
            ->patchJson("/api/v1/admin/bookings/{$booking->booking_code}/status", ['status' => 'completed'])
            ->assertStatus(409);
    }

    public function test_the_documented_admin_transitions_still_work(): void
    {
        $booking = $this->ownedBooking('device-alpha', 'pending');

        $this->withHeaders($this->adminHeaders())
            ->patchJson("/api/v1/admin/bookings/{$booking->booking_code}/status", ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->withHeaders($this->adminHeaders())
            ->patchJson("/api/v1/admin/bookings/{$booking->booking_code}/status", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'completed',
        ]);
    }

    public function test_a_rejected_transition_leaves_no_history_entry(): void
    {
        $booking = $this->ownedBooking('device-alpha', 'cancelled');

        $this->withHeaders($this->adminHeaders())
            ->patchJson("/api/v1/admin/bookings/{$booking->booking_code}/status", ['status' => 'confirmed'])
            ->assertStatus(409);

        $this->assertDatabaseCount('booking_status_histories', 0);
    }

    public function test_an_anonymous_caller_cannot_confirm_a_booking(): void
    {
        $booking = $this->ownedBooking('device-alpha', 'pending');

        $this->withHeaders($this->device('device-alpha'))
            ->putJson("/api/v1/bookings/{$booking->booking_code}", ['status' => 'confirmed'])
            ->assertForbidden();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'pending',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Moderación: availability() respeta el scope approved()
    |--------------------------------------------------------------------------
    */

    public function test_availability_hides_unapproved_businesses(): void
    {
        $business = Business::factory()->create(['status' => 'pending']);

        $this->getJson("/api/v1/businesses/{$business->id}/availability")
            ->assertNotFound();
    }

    public function test_availability_hides_unapproved_accommodations(): void
    {
        $accommodation = Accommodation::factory()->create(['status' => 'pending']);

        $this->getJson("/api/v1/accommodations/{$accommodation->id}/availability")
            ->assertNotFound();
    }

    public function test_availability_hides_unapproved_experiences(): void
    {
        $experience = Experience::factory()->create(['status' => 'pending']);

        $this->getJson("/api/v1/experiences/{$experience->id}/availability")
            ->assertNotFound();
    }

    public function test_availability_still_works_for_approved_records(): void
    {
        $business = Business::factory()->create(['status' => 'approved']);

        $this->getJson("/api/v1/businesses/{$business->id}/availability")
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    /*
    |--------------------------------------------------------------------------
    | XSS almacenado por SVG
    |--------------------------------------------------------------------------
    */

    public function test_uploading_an_svg_is_rejected(): void
    {
        Storage::fake(config('api.media.disk'));

        $svg = UploadedFile::fake()->createWithContent(
            'logo.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(document.cookie)</script></svg>',
        );

        $this->withHeaders($this->adminHeaders())
            ->post('/api/v1/admin/media', ['file' => $svg], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');

        $this->assertSame(0, Media::query()->count(), 'El SVG rechazado no debe crear registro de media.');
    }

    public function test_raster_image_uploads_are_still_accepted(): void
    {
        Storage::fake(config('api.media.disk'));

        $png = UploadedFile::fake()->image('portada.png', 800, 600);

        $this->withHeaders($this->adminHeaders())
            ->post('/api/v1/admin/media', ['file' => $png], ['Accept' => 'application/json'])
            ->assertCreated();

        $this->assertSame(1, Media::query()->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Media: disco correcto y URLs absolutas
    |--------------------------------------------------------------------------
    */

    public function test_media_urls_use_the_configured_media_disk(): void
    {
        config(['api.media.disk' => 'public', 'app.url' => 'https://seven.test']);

        $this->assertSame(
            'https://seven.test/storage/media/2026/01/foto.png',
            mediaUrl('media/2026/01/foto.png'),
        );
    }

    public function test_media_urls_ignore_the_unrelated_default_disk(): void
    {
        // El bug original: mediaUrl() leía filesystems.default, así que con
        // FILESYSTEM_DISK=s3 y MEDIA_DISK=public emitía URLs de S3 para
        // archivos que vivían en el disco local.
        config([
            'api.media.disk' => 'public',
            'filesystems.default' => 's3',
            'app.url' => 'https://seven.test',
        ]);

        $this->assertStringStartsWith(
            'https://seven.test/storage/',
            (string) mediaUrl('media/2026/01/foto.png'),
        );
    }

    public function test_media_urls_keep_external_urls_untouched(): void
    {
        $this->assertSame('https://cdn.test/a.png', mediaUrl('https://cdn.test/a.png'));
    }

    public function test_business_images_are_returned_as_absolute_urls(): void
    {
        config(['app.url' => 'https://seven.test']);

        $business = Business::factory()->create([
            'status' => 'approved',
            'images' => ['media/2026/01/uno.png', 'https://cdn.test/dos.png'],
        ]);

        $this->getJson("/api/v1/businesses/{$business->id}")
            ->assertOk()
            ->assertJsonPath('data.images.0', 'https://seven.test/storage/media/2026/01/uno.png')
            ->assertJsonPath('data.images.1', 'https://cdn.test/dos.png');
    }
}
