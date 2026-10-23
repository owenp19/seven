<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Asistente turístico de SEVEN — proveedor de IA
    |--------------------------------------------------------------------------
    | El asistente NUNCA habla directamente con la base de datos: el backend
    | consulta el catálogo con servicios internos y le entrega al modelo
    | únicamente los resultados de herramientas de solo lectura (allowlist).
    | Aquí se configura QUÉ proveedor se usa y CÓMO.
    |
    | `provider` selecciona el adaptador concreto tras
    | App\Contracts\Ai\AiProviderInterface. Hoy hay dos:
    |   - gemini : Google AI (Generative Language API).
    |   - fake   : determinista, sin red. Solo para tests / demos offline.
    |
    | La API key vive EXCLUSIVAMENTE aquí (variables de entorno del backend).
    | Nunca se expone al frontend ni se incluye en ninguna respuesta.
    */
    'provider' => env('AI_PROVIDER', 'gemini'),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', ''),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'timeout' => (int) env('AI_TIMEOUT', 30),
        'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 1024),
        'temperature' => (float) env('AI_TEMPERATURE', 0.4),
    ],

    /*
    |--------------------------------------------------------------------------
    | Límites del orquestador
    |--------------------------------------------------------------------------
    | `max_candidates`      cuántos servicios reales como máximo ve el modelo
    |                       por llamada a una herramienta (coste/contexto).
    | `max_recommendations` tope de tarjetas devueltas al cliente.
    | `max_tool_iterations` rondas de "el modelo pide herramienta → backend
    |                       responde" antes de forzar una respuesta final.
    | `max_message_chars`   tamaño máximo de un mensaje de usuario entrante.
    */
    'max_candidates' => (int) env('AI_MAX_CANDIDATES', 15),
    'max_recommendations' => (int) env('AI_MAX_RECOMMENDATIONS', 3),
    'max_tool_iterations' => (int) env('AI_MAX_TOOL_ITERATIONS', 4),
    'max_message_chars' => (int) env('AI_MAX_MESSAGE_CHARS', 1000),

    /*
    |--------------------------------------------------------------------------
    | Persistencia de conversaciones
    |--------------------------------------------------------------------------
    | `ttl_hours`    horas de vida de una conversación desde su última
    |                actividad (expira y se deja de entregar aunque el id
    |                siga existiendo; la limpieza física es opcional).
    | `max_messages` tope de mensajes persistidos por conversación; al
    |                superarlo se recortan los más antiguos.
    | `history_turns` cuántos turnos previos (user+assistant) se mandan al
    |                modelo como contexto, para acotar coste.
    */
    'conversation' => [
        'ttl_hours' => (int) env('AI_CONVERSATION_TTL_HOURS', 24),
        'max_messages' => (int) env('AI_CONVERSATION_MAX_MESSAGES', 40),
        'history_turns' => (int) env('AI_HISTORY_TURNS', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate limiting del asistente
    |--------------------------------------------------------------------------
    | Cada mensaje dispara una o varias llamadas facturables al modelo, así
    | que el control es más estricto que el de la API normal. El contador va
    | por install_id (dispositivo) y, además, por IP para cortar ráfagas.
    */
    'rate_limit' => [
        'guest' => (int) env('AI_RATE_LIMIT_GUEST', 20),
        'authenticated' => (int) env('AI_RATE_LIMIT_AUTHENTICATED', 60),
        'burst_per_minute' => (int) env('AI_RATE_LIMIT_BURST', 10),
    ],

];
