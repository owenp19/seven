<?php

namespace App\Swagger;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="SEVEN API",
 *     description="API pública de SEVEN — San Andrés, más que un destino. Ofrece tours, experiencias, restaurantes, posadas nativas, panaderías, promociones y reservas de San Andrés Isla. Todas las respuestas usan el mismo sobre: success, message, data, errors y meta. Códigos de estado comunes: 200 OK, 201 Creado, 404 No encontrado, 422 Validación, 429 Límite superado.",
 *     contact={
 *         "name": "SEVEN App",
 *     },
 *     license={
 *         "name": "Datos abiertos — fotos Wikimedia Commons (CC BY-SA)",
 *     }
 * )
 *
 * @OA\Tag(name="Categorías", description="Categorías activas del marketplace")
 * @OA\Tag(name="Tours", description="Tours y experiencias náuticas aprobados")
 * @OA\Tag(name="Experiencias", description="Experiencias del marketplace con disponibilidad")
 * @OA\Tag(name="Restaurantes", description="Restaurantes aprobados con su menú")
 * @OA\Tag(name="Posadas nativas", description="Posadas nativas aprobadas con sus habitaciones")
 * @OA\Tag(name="Panaderías", description="Panaderías y cafeterías aprobadas con su catálogo")
 * @OA\Tag(name="Negocios", description="Negocios del marketplace por categoría")
 * @OA\Tag(name="Alojamientos", description="Alojamientos con disponibilidad y búsqueda")
 * @OA\Tag(name="Banners", description="Banners activos dentro de su rango de fechas")
 * @OA\Tag(name="Promociones", description="Promociones activas")
 * @OA\Tag(name="Reservas", description="Creación, consulta y gestión de reservas por código")
 * @OA\Tag(name="Raíz", description="Información general de la API")
 *
 * @OA\Get(
 *     path="/",
 *     operationId="apiRoot",
 *     tags={"Raíz"},
 *     summary="Información de la API",
 *     description="Devuelve el nombre, la versión y el estado de la API.",
 *
 *     @OA\Response(
 *         response=200,
 *         description="Bienvenida de la API.",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Welcome to the SEVEN API"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="name", type="string", example="SEVEN API"),
 *                 @OA\Property(property="version", type="string", example="v1"),
 *                 @OA\Property(property="status", type="string", example="ok"),
 *             ),
 *             @OA\Property(property="errors", type="object", example={}),
 *             @OA\Property(property="meta", type="object", example={}),
 *         )
 *     ),
 *
 *     @OA\Response(response=429, description="Demasiadas peticiones (límite 120/min).")
 * )
 */
class OpenApiSpec {}
