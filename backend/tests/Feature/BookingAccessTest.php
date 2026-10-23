<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Provider;
use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pruebas de la propiedad de reservas anónimas (v1).
 *
 * Cada reserva nueva recibe un `booking_access_token` que se entrega una
 * única vez al crearla y se guarda solo como SHA-256. Consultar/cancelar por
 * `booking_code` exige ese token. Las filas legacy (sin hash) quedan
 * bloqueadas en la API pública y solo las abre el panel (admin/proveedor).
 * El `install_id` ya no es credencial: solo sostiene el listado del
 * dispositivo y el rate limit de comentarios.
 */
class BookingAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function adminHeaders(): array
    {
        return ['X-Admin-Token' => config('api.admin.token')];
    }

    private function deviceHeaders(string $id = 'SEVEN-DEVICE-A'): array
    {
        return ['X-Install-Id' => $id];
    }

    private function tokenHeaders(string $token): array
    {
        return ['X-Booking-Token' => $token];
    }

    /** Reserva con su token conocido: el SHA-256 va a la base. */
    private function bookingWithToken(string $token, array $overrides = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'customer_id' => Customer::factory(),
            'booking_access_hash' => hash('sha256', $token),
        ], $overrides));
    }

    private function approvedBusiness(): Business
    {
        return Business::factory()->create(['status' => 'approved']);
    }

    /**
     * 1. Crear una reserva sin identificador de dispositivo se rechaza.
     */
    public function test_creating_without_a_device_identifier_is_rejected(): void
    {
        $business = $this->approvedBusiness();

        $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'business',
            'bookable_id' => $business->id,
            'type' => 'gastronomic',
            'start_at' => now()->addDays(2)->toDateTimeString(),
            'guests' => 2,
            'customer' => ['name' => 'Marta Linares', 'phone' => '+57 300 555 0101'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('install_id');

        $this->assertDatabaseCount('bookings', 0);
    }

    /**
     * 2. Un tercero con un token ajeno no puede leer la reserva.
     */
    public function test_a_third_party_cannot_read_a_booking_by_code(): void
    {
        $booking = $this->bookingWithToken('tok-owner-a', ['status' => 'confirmed']);

        $this->getJson(
            "/api/v1/bookings/{$booking->booking_code}",
            $this->tokenHeaders('tok-intruder-b'),
        )->assertForbidden();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);
    }

    /**
     * 3. Un tercero con un token ajeno no puede cancelar la reserva.
     */
    public function test_a_third_party_cannot_cancel_a_booking(): void
    {
        $booking = $this->bookingWithToken('tok-owner-a', ['status' => 'pending']);

        $this->deleteJson(
            "/api/v1/bookings/{$booking->booking_code}",
            [],
            $this->tokenHeaders('tok-intruder-b'),
        )->assertForbidden();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'pending']);
    }

    /**
     * 4. El booking_code por sí solo (sin token) no abre la reserva; y el
     * install_id tampoco es una credencial: aportarlo sin token da 403.
     */
    public function test_a_booking_code_alone_is_not_a_credential(): void
    {
        $booking = $this->bookingWithToken('tok-owner-a');

        $this->getJson("/api/v1/bookings/{$booking->booking_code}")
            ->assertForbidden();

        $this->getJson(
            "/api/v1/bookings/{$booking->booking_code}",
            $this->deviceHeaders('SEVEN-DEVICE-A'),
        )->assertForbidden();
    }

    /**
     * 5. Un token incorrecto se rechaza (comparación contra el hash).
     */
    public function test_an_incorrect_token_is_rejected(): void
    {
        $booking = $this->bookingWithToken('tok-owner-a');

        $this->getJson(
            "/api/v1/bookings/{$booking->booking_code}",
            $this->tokenHeaders('tok-wrong-0000'),
        )->assertForbidden();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'pending']);
    }

    /**
     * 6. El titular con su token lee la reserva.
     */
    public function test_the_correct_token_reads_the_booking(): void
    {
        $booking = $this->bookingWithToken('tok-owner-a');

        $this->getJson(
            "/api/v1/bookings/{$booking->booking_code}",
            $this->tokenHeaders('tok-owner-a'),
        )->assertOk()
            ->assertJsonPath('data.booking_code', $booking->booking_code)
            ->assertJsonPath('data.status', $booking->status);
    }

    /**
     * 7. El hash jamás se serializa; el token en claro solo se entrega en
     * la creación y no reaparece en el resto de respuestas.
     */
    public function test_the_access_hash_is_never_serialized_and_the_token_is_delivered_once(): void
    {
        $business = $this->approvedBusiness();

        $created = $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'business',
            'bookable_id' => $business->id,
            'type' => 'gastronomic',
            'start_at' => now()->addDays(2)->toDateTimeString(),
            'guests' => 2,
            'customer' => ['name' => 'Ana Ruiz', 'phone' => '+57 300 555 0102'],
        ], $this->deviceHeaders('SEVEN-DEVICE-AAA'))
            ->assertCreated();

        $token = $created->json('data.booking_access_token');
        $this->assertIsString($token);
        // random_bytes(24) codificado en hex: 48 caracteres [0-9a-f].
        $this->assertMatchesRegularExpression('/^[0-9a-f]{48}$/', $token);

        // El token original no viaja hacia la base: solo su hash.
        $stored = Booking::sole();
        $this->assertSame(hash('sha256', $token), $stored->booking_access_hash);
        $this->assertNotSame($token, $stored->booking_access_hash);

        // Ni la creación ni el detail exponen el hash (o el token de nuevo).
        $this->assertStringNotContainsString('booking_access_hash', (string) $created->getContent());

        $detail = $this->getJson(
            "/api/v1/bookings/{$stored->booking_code}",
            $this->tokenHeaders($token),
        )->assertOk();

        $this->assertStringNotContainsString('booking_access_hash', (string) $detail->getContent());
        $this->assertStringNotContainsString('booking_access_token', (string) $detail->getContent());
    }

    /**
     * 8. El backoffice consulta cualquier reserva sin necesitar el token
     * público del viajero.
     */
    public function test_an_admin_can_read_any_booking_without_the_public_token(): void
    {
        $booking = $this->bookingWithToken('tok-owner-a', ['status' => 'confirmed']);

        $this->getJson(
            "/api/v1/admin/bookings/{$booking->booking_code}",
            $this->adminHeaders(),
        )->assertOk()
            ->assertJsonPath('data.booking_code', $booking->booking_code)
            ->assertJsonPath('data.status', 'confirmed');
    }

    /**
     * 9. El proveedor solo ve (y gestiona) sus propias reservas.
     */
    public function test_a_provider_only_manages_its_own_bookings(): void
    {
        $providerA = $this->providerOwner('Proveedor A');
        $providerB = $this->providerOwner('Proveedor B');

        $mine = $this->bookingWithToken('tok-provider-a', ['provider_id' => $providerA->id]);
        $theirs = $this->bookingWithToken('tok-provider-b', ['provider_id' => $providerB->id]);

        $this->refreshAuth();
        $this->withToken($this->tokenFor($providerA->user))
            ->getJson('/api/v1/provider/bookings')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.booking_code', $mine->booking_code);

        // El código de una reserva ajena responde 404, no 403: no revela que
        // la reserva existe.
        $this->refreshAuth();
        $this->withToken($this->tokenFor($providerA->user))
            ->getJson("/api/v1/provider/bookings/{$theirs->booking_code}")
            ->assertNotFound();

        $this->refreshAuth();
        $this->withToken($this->tokenFor($providerA->user))
            ->getJson("/api/v1/provider/bookings/{$mine->booking_code}")
            ->assertOk();
    }

    /**
     * 10. Las reservas legacy (sin instalador de token: `booking_access_hash`
     * NULL y a menudo sin install_id) dejan de ser accesibles por el código
     * público —ni con token adivinado— pero el panel las sigue viendo.
     */
    public function test_legacy_bookings_without_hash_are_locked_to_the_public_but_open_to_admins(): void
    {
        $legacy = Booking::factory()->create([
            'customer_id' => Customer::factory(),
            'install_id' => null,
            'booking_access_hash' => null,
            'status' => 'confirmed',
        ]);

        $this->getJson("/api/v1/bookings/{$legacy->booking_code}")->assertForbidden();

        $this->getJson(
            "/api/v1/bookings/{$legacy->booking_code}",
            $this->tokenHeaders('token-inventado'),
        )->assertForbidden();

        $this->deleteJson(
            "/api/v1/bookings/{$legacy->booking_code}",
            [],
            $this->tokenHeaders('token-inventado'),
        )->assertForbidden();

        // El panel no necesita el token del viajero.
        $this->getJson(
            "/api/v1/admin/bookings/{$legacy->booking_code}",
            $this->adminHeaders(),
        )->assertOk()
            ->assertJsonPath('data.booking_code', $legacy->booking_code);

        $this->assertDatabaseHas('bookings', ['id' => $legacy->id, 'status' => 'confirmed']);
    }

    /**
     * 11. Rotar el X-Install-Id no elude el control combinado: el tope por IP
     * sigue contando, sin convertir la IP en un identificador permanente.
     */
    public function test_rotating_the_install_id_cannot_escape_the_ip_rate_limit(): void
    {
        config(['api.rate_limit.comments.per_ip' => 3]);

        $provider = Provider::create(['business_name' => 'Operador Isleño', 'type' => 'experiences', 'status' => 'approved', 'verification_status' => 'verified']);
        $tour = Tour::create([
            'provider_id' => $provider->id,
            'name' => 'Johnny Cay en lancha',
            'slug' => 'johnny-cay-en-lancha',
            'status' => 'approved',
        ]);

        $payload = fn (int $n) => [
            'author_name' => 'Viajera '.$n,
            'body' => 'Salida puntual y guia excelente. Repetiria con ellos sin dudar.',
        ];

        // Los tres primeros pasan (identidades distintas, misma IP).
        for ($n = 1; $n <= 3; $n++) {
            $this->postJson(
                "/api/v1/tours/{$tour->slug}/comments",
                $payload($n),
                $this->deviceHeaders('SEVEN-DEVICE-'.$n),
            )->assertCreated();
        }

        // El cuarto y el quinto caen en el tope por hora de la IP.
        $this->postJson(
            "/api/v1/tours/{$tour->slug}/comments",
            $payload(4),
            $this->deviceHeaders('SEVEN-DEVICE-4'),
        )->assertStatus(429)->assertJsonPath('success', false);

        $this->postJson(
            "/api/v1/tours/{$tour->slug}/comments",
            $payload(5),
            $this->deviceHeaders('SEVEN-DEVICE-5'),
        )->assertStatus(429);

        $this->assertDatabaseCount('comments', 3);
    }

    private function providerOwner(string $businessName): Provider
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

    private function tokenFor(User $user): string
    {
        return $user->createToken('access-test-'.Str::random(4), ['provider'])->plainTextToken;
    }

    private function refreshAuth(): void
    {
        $this->app->make('auth')->forgetGuards();
    }
}
