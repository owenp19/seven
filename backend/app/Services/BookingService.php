<?php

namespace App\Services;

use App\Events\BookingStatusChanged;
use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\Business;
use App\Models\Contracts\Bookable;
use App\Models\Customer;
use App\Models\Experience;
use App\Models\Inn;
use App\Models\Restaurant;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * BookingService
 *
 * Contains the domain logic for creating and cancelling bookings:
 * customer resolution/dedup, polymorphic bookable resolution and
 * booking code generation. Controllers stay thin by delegating here.
 *
 * Concurrency guarantees:
 * - Customer dedup is serialized with a row lock (unique phone index as
 *   final backstop) so two simultaneous requests never create duplicates.
 * - Availability is double-checked inside the same transaction under
 *   row locks, preventing overselling when many users book in parallel.
 * - The booking code collision retry catches DB unique-constraint
 *   violations instead of failing with a 500.
 */
class BookingService
{
    public function create(array $data, array $customerData): Booking
    {
        $lastError = null;
        $created = null;

        // Credencial de acceso a ESTA reserva (anónima, sin cuenta): se
        // entrega en claro una única vez en el response de creación y se
        // persiste solo su SHA-256, así que la base de datos nunca contiene
        // el secreto ni se puede filtrar en logs o Resources.
        $accessToken = bin2hex(random_bytes(24));

        // Retry loop for the (rare) booking-code collision. The insert
        // aborts the current transaction, so we retry at the top level.
        // El token se reutiliza entre reintentos: una sola retirada de la
        // credencial por reserva, aunque el primer INSERT haya chocado.
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $created = DB::transaction(function () use ($data, $customerData, $accessToken) {
                    $bookable = $this->resolveBookable($data['bookable_type'], $data['bookable_id']);

                    $customer = $this->resolveCustomer($customerData);

                    if ($data['type'] !== 'gastronomic') {
                        $this->guardAvailability($bookable, $data['type'], $data['start_at'], $data['end_at'] ?? null);
                    }

                    $booking = Booking::create([
                        'customer_id' => $customer->id,
                        'booking_code' => $this->generateCode(),
                        'bookable_type' => $bookable::class,
                        'bookable_id' => $bookable->id,
                        'provider_id' => $this->providerId($bookable),
                        'install_id' => $data['install_id'] ?? null,
                        'booking_access_hash' => hash('sha256', $accessToken),
                        'type' => $data['type'],
                        'start_at' => $data['start_at'],
                        'end_at' => $data['end_at'] ?? null,
                        'guests' => $data['guests'],
                        'total_amount' => $this->estimateAmount($bookable, $data['type'], $data['start_at'], $data['end_at'] ?? null, $data['guests']),
                        'currency' => $data['currency'] ?? 'COP',
                        'special_requests' => $data['special_requests'] ?? null,
                        'status' => Booking::STATUSES[0], // pending
                    ]);

                    BookingStatusHistory::create([
                        'booking_id' => $booking->id,
                        'from_status' => null,
                        'to_status' => Booking::STATUSES[0],
                        'changed_by' => 'customer',
                    ]);

                    return $booking;
                }, attempts: 5);
            } catch (UniqueConstraintViolationException $e) {
                $lastError = $e;
            }

            if ($created !== null) {
                // Atributo transitorio (nunca se persiste): BookingResource lo
                // expone únicamente en este response de creación.
                $created->plain_access_token = $accessToken;

                event(new BookingStatusChanged($created));

                return $created;
            }
        }

        throw $lastError;
    }

    /** Provider dueño del bookable (si lo tiene) para notificaciones. */
    private function providerId(Bookable $bookable): ?int
    {
        if (isset($bookable->provider_id) && $bookable->provider_id !== null) {
            return (int) $bookable->provider_id;
        }

        return null;
    }

    /**
     * Serializes competing booking attempts per bookable+window and
     * rejects the request when that slot is already taken. `lockForUpdate`
     * locks the scanned rows (or the gap on an empty range), so two
     * parallel bookings for the same date queue up instead of racing.
     */
    private function guardAvailability(Bookable $bookable, string $type, string $startAt, ?string $endAt): void
    {
        $start = Carbon::parse($startAt)->startOfDay();
        $end = $type === 'accommodation' && $endAt
            ? Carbon::parse($endAt)->startOfDay()
            : Carbon::parse($startAt)->copy()->endOfDay();

        $query = Booking::query()
            ->where('bookable_type', $bookable::class)
            ->where('bookable_id', $bookable->id)
            ->where('status', '!=', 'cancelled');

        $query->where(function (Builder $query) use ($start, $end, $type) {
            if ($type === 'accommodation') {
                $query->where(fn (Builder $q) => $q
                    ->whereNull('end_at')->whereBetween('start_at', [$start, $end])
                    ->orWhere(fn (Builder $q) => $q
                        ->whereNotNull('end_at')
                        ->where('start_at', '<', $end->copy()->endOfDay())
                        ->where('end_at', '>', $start)));
            } else {
                $query->whereBetween('start_at', [$start, $end]);
            }
        });

        $taken = $query->lockForUpdate()->value('id') !== null;

        if ($taken) {
            throw ValidationException::withMessages([
                'start_at' => ['Este horario ya no se encuentra disponible.'],
            ]);
        }
    }

    /**
     * Resolves the polymorphic bookable behind a `bookable_type`/`bookable_id`
     * pair.
     *
     * `bookable_type` is a plain string column, so the marketplace verticals
     * (Restaurant, Inn) live side by side with the classic ones (Business,
     * Accommodation, Experience, Tour) without a migration. Every one of them
     * implements the `Bookable` contract and is resolved through
     * `scopeApproved()`, so an unreviewed listing can never be booked.
     */
    private function resolveBookable(string $type, int $id): Bookable
    {
        $model = match ($type) {
            'accommodation' => Accommodation::class,
            'experience' => Experience::class,
            'tour' => Tour::class,
            'restaurant' => Restaurant::class,
            'inn' => Inn::class,
            default => Business::class,
        };

        return $model::approved()->findOrFail($id);
    }

    /**
     * Resolves or creates the customer for a booking.
     *
     * Inside a transaction, rows with the same phone (or email) are
     * locked so concurrent requests see each other's write. The fresh
     * unique index on `phone` is the final backstop: if two sessions
     * still slip through, the winner's insert wins and the loser's
     * unique-violation is caught and re-resolved to the existing row.
     */
    private function resolveCustomer(array $data): Customer
    {
        $phone = $data['phone'] ?? null;
        $email = $data['email'] ?? null;
        $phone = is_string($phone) ? trim($phone) : null;
        $email = is_string($email) && $email !== '' ? strtolower(trim($email)) : null;

        return DB::transaction(function () use ($data, $phone, $email) {
            $lookup = $phone !== null && $phone !== ''
                ? Customer::where('phone', $phone)
                : ($email ? Customer::where('email', $email) : null);

            if ($lookup === null) {
                return Customer::create([
                    'name' => $data['name'],
                    'phone' => null,
                    'email' => null,
                ]);
            }

            $customer = $lookup->lockForUpdate()->first();

            if ($customer) {
                if ($email && is_null($customer->email)) {
                    $customer->update(['email' => $email]);
                }

                return $customer;
            }

            try {
                return Customer::create([
                    'name' => $data['name'],
                    'phone' => $phone,
                    'email' => $email,
                ]);
            } catch (UniqueConstraintViolationException $e) {
                return $lookup->lockForUpdate()->firstOrFail();
            }
        }, attempts: 5);
    }

    /**
     * Server-side estimate of what the booking is worth.
     *
     * Per-night bookables (Accommodation, Inn) are billed by the number of
     * nights; per-person ones (Experience, Tour) by the guest count. The
     * gastronomic verticals carry no price on the listing itself, so their
     * total is left at 0 for the business to confirm from the backoffice.
     */
    private function estimateAmount(Bookable $bookable, string $type, string $startAt, ?string $endAt, int $guests = 1): int
    {
        $nightly = match (true) {
            $bookable instanceof Accommodation => $bookable->price_per_night,
            $bookable instanceof Inn => $bookable->price,
            default => null,
        };

        if ($nightly !== null) {
            $nights = max(1, (int) ceil(
                (strtotime((string) ($endAt ?? $startAt)) - strtotime($startAt)) / 86400
            ));

            return $nightly * $nights;
        }

        if ($bookable instanceof Experience) {
            return $bookable->price_per_person * max(1, $guests);
        }

        if ($bookable instanceof Tour) {
            return $bookable->price * max(1, $guests);
        }

        return 0; // Gastronomic amounts are set by the business in a later version.
    }

    private function generateCode(): string
    {
        return 'SEV-'.strtoupper(Str::random(6));
    }
}
