<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/v1/admin/auth/tokens — genera un token personal adicional.
 */
class AdminTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The token name field is required.',
            'name.max' => 'The token name may not be longer than 100 characters.',
        ];
    }
}
