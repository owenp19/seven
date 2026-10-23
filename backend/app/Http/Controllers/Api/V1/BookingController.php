<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\V1\StoreBookingRequest;
use App\Http\Requests\V1\UpdateBookingRequest;
use App\Http\Resources\V1\BookingResource;
use App\Models\Booking;
use App\Repositories\BookingRepository;
use App\Services\BookingOwnershipService;
use App\Services\BookingService;
use App\Services\BookingStatusService;
use Illuminate\Http\Request;

class BookingController extends BaseController
{
    public function __construct(
        private readonly BookingRepository $bookings,
        private readonly BookingService $bookingService,
        private readonly BookingStatusService $statusService,
        private readonly BookingOwnershipService $ownership,
    ) {}

    /**
     * GET /api/bookings — bookings created by this device.
     *
     * El ámbito es el install_id de quien llama, no un `phone` en query
     * string: consultar por teléfono abierto devolvía los booking_code de
     * cualquier cliente y servía como oráculo para el resto de endpoints.
     *
     * @OA\Get(
     *     path="/bookings",
     *     operationId="listBookings",
     *     tags={"Reservas"},
     *     summary="Lista las reservas del dispositivo",
     *     description="Lista las reservas creadas desde este dispositivo, identificadas por la cabecera X-Install-Id. Sin ese identificador la petición se rechaza.",
     *
     *     @OA\Parameter(
     *         name="X-Install-Id",
     *         in="header",
     *         required=true,
     *         description="UUID del dispositivo (mismo valor que se envía al crear la reserva).",
     *
     *         @OA\Schema(type="string", example="5f0a1b3c-6d2e-4a7b-9c8d-1e2f3a4b5c6d")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Reservas obtenidas correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bookings retrieved successfully."),
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="booking_code", type="string"),
     *                     @OA\Property(property="type", type="string"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="bookable", type="object",
     *                         @OA\Property(property="type", type="string"),
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string"),
     *                     ),
     *                     @OA\Property(property="start_at", type="string", format="date-time"),
     *                     @OA\Property(property="end_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="guests", type="integer"),
     *                     @OA\Property(property="total_amount", type="integer"),
     *                     @OA\Property(property="currency", type="string"),
     *                     @OA\Property(property="special_requests", type="string", nullable=true),
     *                     @OA\Property(property="customer", type="object"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                 )
     *             ),
     *             @OA\Property(property="errors", type="object", example={}),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="total", type="integer"),
     *                     @OA\Property(property="per_page", type="integer"),
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="last_page", type="integer"),
     *                 )
     *             ),
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="Falta el X-Install-Id o la reserva pertenece a otro dispositivo."),
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite 120/min).")
     * )
     */
    public function index(Request $request)
    {
        $installId = $this->ownership->requireDeviceScope($request);

        $result = $this->bookings
            ->forDevice($installId)
            ->paginate(config('api.pagination.per_page'))
            ->withQueryString();

        return $this->success(
            data: BookingResource::collection($result),
            message: 'Bookings retrieved successfully.',
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
     * POST /api/bookings — create a booking.
     *
     * @OA\Post(
     *     path="/bookings",
     *     operationId="storeBooking",
     *     tags={"Reservas"},
     *     summary="Crea una reserva",
     *     description="Crea una reserva asociada a un bookable (business, accommodation, experience, tour, restaurant o inn). El cliente se localiza por teléfono o se crea automáticamente.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"bookable_type", "bookable_id", "type", "start_at", "guests", "customer"},
     *
     *             @OA\Property(property="bookable_type", type="string", enum={"business","accommodation","experience","tour","restaurant","inn"}, example="experience"),
     *             @OA\Property(property="bookable_id", type="integer", example=1),
     *             @OA\Property(property="type", type="string", enum={"gastronomic","accommodation","experience"}, example="experience"),
     *             @OA\Property(property="start_at", type="string", format="date-time", example="2026-10-01T09:00:00"),
     *             @OA\Property(property="end_at", type="string", format="date-time", nullable=true),
     *             @OA\Property(property="guests", type="integer", minimum=1, maximum=20, example=2),
     *             @OA\Property(property="special_requests", type="string", nullable=true, example="Quisiera compartir vehículo."),
     *             @OA\Property(property="currency", type="string", maxLength=3, example="COP"),
     *             @OA\Property(property="customer", type="object",
     *                 required={"name", "phone"},
     *                 @OA\Property(property="name", type="string", example="Ana Martínez"),
     *                 @OA\Property(property="phone", type="string", example="+573001234567"),
     *                 @OA\Property(property="email", type="string", format="email", example="ana@example.com"),
     *             ),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Reserva creada correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Booking created successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="booking_code", type="string", example="SVN-9F2K7Q"),
     *                 @OA\Property(property="status", type="string", example="confirmed"),
     *             ),
     *             @OA\Property(property="errors", type="object", example={}),
     *             @OA\Property(property="meta", type="object", example={}),
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Error de validación.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="bookable_type", type="array", @OA\Items(type="string", example="The bookable_type must be either business, accommodation, experience, tour, restaurant or inn.")),
     *             ),
     *         )
     *     ),
     *
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite 120/min).")
     * )
     */
    public function store(StoreBookingRequest $request)
    {
        $booking = $this->bookingService->create(
            data: $request->safe()->except('customer'),
            customerData: $request->input('customer'),
        );

        return $this->success(
            data: new BookingResource($booking->load(['bookable', 'customer', 'provider'])),
            message: 'Booking created successfully.',
            status: 201,
        );
    }

    /**
     * GET /api/bookings/{bookingCode} — booking detail by code.
     *
     * @OA\Get(
     *     path="/bookings/{bookingCode}",
     *     operationId="showBooking",
     *     tags={"Reservas"},
     *     summary="Consulta una reserva por código",
     *     description="Devuelve el detalle de una reserva a partir de su código público. Solo el dispositivo que la creó puede consultarlo.",
     *
     *     @OA\Parameter(
     *         name="bookingCode",
     *         in="path",
     *         required=true,
     *         description="Código de la reserva (ej: SVN-9F2K7Q).",
     *
     *         @OA\Schema(type="string", example="SVN-9F2K7Q")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Reserva obtenida correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Booking retrieved successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="booking_code", type="string", example="SVN-9F2K7Q"),
     *                 @OA\Property(property="status", type="string", example="confirmed"),
     *             ),
     *             @OA\Property(property="errors", type="object", example={}),
     *             @OA\Property(property="meta", type="object", example={}),
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="La reserva es de otro dispositivo."),
     *     @OA\Response(response=404, description="Reserva no encontrada."),
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite 120/min).")
     * )
     */
    public function show(string $bookingCode, Request $request)
    {
        $booking = $this->bookings->findByCode($bookingCode);

        abort_unless($booking, 404, 'Booking not found.');

        $this->ownership->assertOwner($booking, $request);

        return $this->success(
            data: new BookingResource($booking),
            message: 'Booking retrieved successfully.',
        );
    }

    /**
     * PUT /api/bookings/{bookingCode} — update booking status.
     *
     *     @OA\Put(
     *     path="/bookings/{bookingCode}",
     *     operationId="updateBooking",
     *     tags={"Reservas"},
     *     summary="Actualiza el estado de una reserva",
     *     description="Endpoint público: solo permite pasar la reserva a cancelled, y únicamente si pertenece al dispositivo que la creó. Confirmar o completar se hace desde /admin/bookings/{code}/status.",
     *
     *     @OA\Parameter(
     *         name="bookingCode",
     *         in="path",
     *         required=true,
     *         description="Código de la reserva.",
     *
     *         @OA\Schema(type="string", example="SVN-9F2K7Q")
     *     ),
     *
     *     @OA\RequestBody(
     *     required=true,
     *
     *         @OA\JsonContent(
     *             required={"status"},
     *
     *             @OA\Property(property="status", type="string", enum={"cancelled"}, example="cancelled"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Reserva actualizada correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Booking updated successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="booking_code", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *             ),
     *             @OA\Property(property="errors", type="object", example={}),
     *             @OA\Property(property="meta", type="object", example={}),
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="La reserva es de otro dispositivo, o el cliente intenta fijar un estado que no le corresponde."),
     *     @OA\Response(response=404, description="Reserva no encontrada."),
     *     @OA\Response(
     *         response=409,
     *         description="Transición de estado no permitida (p. ej. la reserva ya está cancelada o completada).",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="A booking in status [cancelled] is final and cannot change."),
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Error de validación."),
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite 120/min).")
     * )
     */
    public function update(string $bookingCode, UpdateBookingRequest $request)
    {
        $booking = $this->bookings->findByCode($bookingCode);

        abort_unless($booking, 404, 'Booking not found.');

        $this->ownership->assertOwner($booking, $request);

        $status = $request->validated()['status'];

        // Confirmar o completar es una decisión del negocio y se hace desde
        // el backoffice. Un cliente anónimo solo puede retirar su reserva.
        abort_unless(
            in_array($status, Booking::CUSTOMER_STATUSES, true),
            403,
            "A customer cannot set a booking to [{$status}]. Allowed: ".implode(', ', Booking::CUSTOMER_STATUSES).'.',
        );

        $this->statusService->transition($booking, $status, 'customer');

        return $this->success(
            data: new BookingResource($booking),
            message: 'Booking updated successfully.',
        );
    }

    /**
     * DELETE /api/bookings/{bookingCode} — cancel a booking.
     *
     * @OA\Delete(
     *     path="/bookings/{bookingCode}",
     *     operationId="cancelBooking",
     *     tags={"Reservas"},
     *     summary="Cancela una reserva",
     *     description="Marca la reserva como cancelada (status = cancelled).",
     *
     *     @OA\Parameter(
     *         name="bookingCode",
     *         in="path",
     *         required=true,
     *         description="Código de la reserva.",
     *
     *         @OA\Schema(type="string", example="SVN-9F2K7Q")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Reserva cancelada correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Booking cancelled successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="booking_code", type="string"),
     *                 @OA\Property(property="status", type="string", example="cancelled"),
     *             ),
     *             @OA\Property(property="errors", type="object", example={}),
     *             @OA\Property(property="meta", type="object", example={}),
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="La reserva es de otro dispositivo."),
     *     @OA\Response(response=404, description="Reserva no encontrada."),
     *     @OA\Response(
     *         response=409,
     *         description="La reserva ya está en un estado final (cancelada o completada).",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="A booking in status [cancelled] is final and cannot change."),
     *         )
     *     ),
     *
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite 120/min).")
     * )
     */
    public function destroy(string $bookingCode, Request $request)
    {
        $booking = $this->bookings->findByCode($bookingCode);

        abort_unless($booking, 404, 'Booking not found.');

        $this->ownership->assertOwner($booking, $request);

        $this->statusService->transition($booking, 'cancelled', 'customer');

        return $this->success(
            data: new BookingResource($booking),
            message: 'Booking cancelled successfully.',
        );
    }
}
