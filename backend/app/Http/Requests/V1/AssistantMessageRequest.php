<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class AssistantMessageRequest extends FormRequest
{
    /**
     * API pública: el asistente es anónimo, sin login.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza el identificador del dispositivo antes de validar, igual que
     * las reservas: la cabecera `X-Install-Id` manda y el `install_id` del
     * cuerpo sirve de respaldo.
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
            'message' => ['required', 'string', 'min:1', 'max:'.(int) config('ai.max_message_chars', 1000)],
            // ULID opaco de una conversación previa. Opcional: sin él se abre
            // una conversación nueva.
            'conversation_id' => ['nullable', 'string', 'size:26'],
            'install_id' => [
                'required',
                'string',
                'max:64',
                'regex:/^(?:[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}|[a-z0-9][a-z0-9_-]{7,63})$/i',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Escribe un mensaje para el asistente.',
            'message.max' => 'El mensaje es demasiado largo.',
            'conversation_id.size' => 'El identificador de conversación no es válido.',
            'install_id.required' => 'A device identifier (X-Install-Id) is required.',
            'install_id.regex' => 'The device identifier must be a valid UUID (RFC 4122) or a SEVEN device token.',
        ];
    }
}
