<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\V1\AssistantMessageRequest;
use App\Services\Ai\AssistantService;
use Illuminate\Http\JsonResponse;

/**
 * AssistantController
 *
 * Endpoint público del asistente turístico. Recibe el mensaje del usuario,
 * lo pasa al orquestador (que consulta el catálogo real mediante herramientas
 * de solo lectura) y devuelve la respuesta junto con recomendaciones y
 * paquetes listos para pintar como tarjetas.
 *
 * El rate limit vive en la ruta (`throttle:assistant`); la identidad del
 * dispositivo y su ownership se resuelven en la conversación.
 */
class AssistantController extends BaseController
{
    public function __construct(
        private readonly AssistantService $assistant,
    ) {}

    /**
     * POST /api/v1/assistant/messages
     */
    public function message(AssistantMessageRequest $request): JsonResponse
    {
        $data = $this->assistant->handle(
            message: $request->string('message')->toString(),
            installId: $request->string('install_id')->toString(),
            conversationId: $request->input('conversation_id'),
        );

        return $this->success($data, 'Assistant reply.');
    }
}
