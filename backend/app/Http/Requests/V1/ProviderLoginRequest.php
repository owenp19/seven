<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/v1/provider/auth/login
 *
 * El acceso a credenciales se rate-limita por email + IP en la ruta
 * (`throttle:admin-login`); aquí solo se validan los campos. La comprobación
 * de rol de proveedor y de cuenta vinculada ocurre en el controlador,
 * antes de emitir ningún token.
 */
class ProviderLoginRequest extends FormRequest
{
    /**
     * Endpoint público: cualquiera puede intentar autenticarse (el rate
     * limit y la comprobación de rol ocurren antes de emitir un token).
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:190'],
            'password' => ['required', 'string', 'max:255'],
            // Nombre del dispositivo que recibe el token (aparece en la
            // lista de sesiones del usuario para poder revocarlo).
            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'password.required' => 'The password field is required.',
        ];
    }
}
