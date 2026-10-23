<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    /**
     * Public API: no authentication is required on v1.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza el identificador del dispositivo ANTES de validar.
     *
     * La cabecera `X-Install-Id` manda (va en un header, no contamina el
     * cuerpo ni queda en logs de aplicación); el campo `install_id` del body
     * se acepta como respaldo de clientes que aún no mandan el header. En
     * ambos casos el valor termina en `install_id` para que la validación
     * (y BookingService) lo traten igual.
     */
    protected function prepareForValidation(): void
    {
        $deviceId = null;

        $fromHeader = $this->header('X-Install-Id');
        if (is_string($fromHeader) && trim($fromHeader) !== '') {
            $deviceId = trim($fromHeader);
        } else {
            $fromBody = $this->input('install_id');
            if (is_string($fromBody) && trim($fromBody) !== '') {
                $deviceId = trim($fromBody);
            }
        }

        $this->merge(['install_id' => $deviceId]);
    }

    public function rules(): array
    {
        return [
            'bookable_type' => ['required', 'in:business,accommodation,experience,tour,restaurant,inn'],
            'bookable_id' => ['required', 'integer'],
            'type' => ['required', 'in:gastronomic,accommodation,experience'],
            'start_at' => ['required', 'date', 'after:now'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'guests' => ['required', 'integer', 'min:1', 'max:20'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
            'currency' => ['nullable', 'string', 'size:3', 'uppercase'],
            /*
             * Obligatorio desde el endurecimiento de v1: sin él no hay forma
             * de acreditar que una reserva anónima es de este dispositivo.
             *
             * Formato: UUID RFC 4122 (clientes actuales) o el token legacy de
             * la generación anterior de la app (`SEVEN-<epoch>-<rand>` y el
             * patrón `dev-*` de los tests), acotado a 64 caracteres.
             */
            'install_id' => [
                'required',
                'string',
                'max:64',
                'regex:/^(?:[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}|[a-z0-9][a-z0-9_-]{7,63})$/i',
            ],
            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string', 'max:120'],
            'customer.phone' => ['required', 'string', 'max:30'],
            'customer.email' => ['nullable', 'email', 'max:190'],
        ];
    }

    public function messages(): array
    {
        return [
            'bookable_type.in' => 'The bookable_type must be either business, accommodation, experience, tour, restaurant or inn.',
            'start_at.after' => 'The reservation must start in the future.',
            'install_id.required' => 'A device identifier (X-Install-Id) is required to book anonymously.',
            'install_id.regex' => 'The device identifier must be a valid UUID (RFC 4122) or a SEVEN device token.',
            'install_id.max' => 'The device identifier cannot exceed 64 characters.',
        ];
    }
}
