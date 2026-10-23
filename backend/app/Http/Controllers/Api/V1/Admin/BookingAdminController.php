<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\BookingAdminResource;
use App\Models\Accommodation;
use App\Models\Bakery;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Experience;
use App\Models\Inn;
use App\Models\Restaurant;
use App\Models\Tour;
use App\Repositories\BookingRepository;
use App\Services\AuditLogger;
use App\Services\BookingStatusService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * BookingAdminController
 *
 * Panel de solicitudes del dueño: listado con filtros por rango de fechas,
 * categoría, proveedor y estado; detalle con trazabilidad; y transición de
 * estado (confirmar / cancelar) que pasa por BookingStatusService (notifica
 * y guarda historial) en vez de tocar `status` directamente.
 */
class BookingAdminController extends BaseController
{
    public function __construct(
        private readonly BookingRepository $bookings,
        private readonly BookingStatusService $statusService,
    ) {}

    /**
     * GET /api/v1/admin/bookings — solicitudes con filtros.
     *
     * En /provider/* el tenant manda: el ?provider_id del request se
     * ignora y el listado sale siempre limitado al proveedor activo.
     */
    public function index(Request $request)
    {
        $query = Booking::query()
            ->with(['bookable', 'customer', 'provider'])
            ->latest('start_at');

        $this->applyFilters($request, $query);

        $result = $query->paginate(apiPerPage($request))->withQueryString();

        return $this->success(
            data: BookingAdminResource::collection($result),
            message: 'Booking requests retrieved successfully.',
            meta: [
                'pagination' => [
                    'total' => $result->total(),
                    'per_page' => $result->perPage(),
                    'current_page' => $result->currentPage(),
                    'last_page' => $result->lastPage(),
                ],
            ],
        );
    }

    /**
     * GET /api/v1/admin/bookings/{bookingCode} — detalle con historial.
     */
    public function show(string $bookingCode)
    {
        $booking = $this->bookings->findByCode($bookingCode);

        abort_unless($booking, 404, 'Booking request not found.');
        $this->authorizeBooking($booking, 'view');

        return $this->success(
            data: new BookingAdminResource($booking->loadMissing('histories')),
            message: 'Booking request retrieved successfully.',
        );
    }

    /**
     * PATCH /api/v1/admin/bookings/{bookingCode}/status — confirmar/cancelar.
     *
     * @bodyParam status string required pending|confirmed|cancelled|completed
     */
    public function updateStatus(string $bookingCode, Request $request)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Booking::STATUSES)],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $booking = $this->bookings->findByCode($bookingCode);

        abort_unless($booking, 404, 'Booking request not found.');
        $this->authorizeBooking($booking, 'update');

        $previousStatus = $booking->status;

        $this->statusService->transition($booking, $data['status'], 'admin', $data['note'] ?? null);

        app(AuditLogger::class)->recordFor($request, 'booking.status_changed', $booking, [
            'from' => $previousStatus,
            'to' => $data['status'],
        ]);

        return $this->success(
            data: new BookingAdminResource($booking->loadMissing('histories')),
            message: 'Booking request updated successfully.',
        );
    }

    /**
     * En /provider/* solo entra el dueño de la reserva (BookingPolicy);
     * el backoffice global y el token legacy no se tocan. Un deny responde
     * 404 para no revelar que el código de reserva existe.
     */
    private function authorizeBooking(Booking $booking, string $ability): void
    {
        if (tenantProvider() === null) {
            return;
        }

        $user = request()->user('sanctum') ?? request()->user();

        if ($user === null || ! Gate::forUser($user)->allows($ability, $booking)) {
            abort(404, 'Booking request not found.');
        }
    }

    private function applyFilters(Request $request, Builder $query): void
    {
        $status = $request->input('status');
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        if ($from = $request->input('from')) {
            $query->whereDate('start_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('start_at', '<=', $to);
        }

        // En el panel del proveedor el filtro por dueño no es opcional:
        // manda el tenant y se descarta cualquier provider_id del request.
        $providerId = tenantProvider()?->id ?? $request->integer('provider_id', 0);

        if ($providerId) {
            $query->where(function (Builder $sub) use ($providerId) {
                $sub->where('provider_id', $providerId)
                    ->orWhereHasMorph(
                        'bookable',
                        [Business::class, Accommodation::class, Experience::class, Tour::class, Restaurant::class, Inn::class, Bakery::class],
                        fn (Builder $q) => $q->where('provider_id', $providerId)
                    );
            });
        }

        // Filtro por categoría: aplica a bookables con category_id (FK).
        // Tours/restaurantes/panaderías usan categorías de texto y no entran
        // en este filtro por ahora (ver migración 2026_09_20_170953+).
        if ($categoryId = $request->integer('category_id', 0)) {
            $businessIds = Business::where('category_id', $categoryId)->pluck('id');
            $experienceIds = Experience::where('category_id', $categoryId)->pluck('id');

            if ($businessIds->isNotEmpty() || $experienceIds->isNotEmpty()) {
                $query->where(function (Builder $sub) use ($businessIds, $experienceIds) {
                    if ($businessIds->isNotEmpty()) {
                        $sub->orWhere(fn (Builder $q) => $q
                            ->where('bookable_type', Business::class)
                            ->whereIn('bookable_id', $businessIds));
                    }

                    if ($experienceIds->isNotEmpty()) {
                        $sub->orWhere(fn (Builder $q) => $q
                            ->where('bookable_type', Experience::class)
                            ->whereIn('bookable_id', $experienceIds));
                    }
                });
            } else {
                // Categoría sin bookables: no devolver nada.
                $query->whereRaw('1 = 0');
            }
        }

        if ($q = $request->input('q')) {
            $like = "%{$q}%";
            $query->where(function (Builder $sub) use ($like) {
                $sub->where('booking_code', 'like', $like)
                    ->orWhereHas('customer', fn (Builder $c) => $c
                        ->where('name', 'like', $like)
                        ->orWhere('phone', 'like', $like));
            });
        }
    }
}
