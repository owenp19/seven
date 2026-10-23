---
name: seven-laravel
description: Convenciones Laravel 11 del backend SEVEN. Usar al crear o modificar endpoints, controladores, requests, resources, servicios, repositorios, middleware, autenticación Sanctum o tests. Define la arquitectura por capas, envelope JSON, versionado v1, roles/abilities y las reglas para no romper el contrato del API.
---

# SEVEN Laravel

## Stack
- Laravel `^11.31`, PHP `^8.2`, Sanctum `^4.0`, L5-Swagger `^11.1`.
- Base de datos: MySQL/MariaDB (`seven_db` en local).
- Tests: PHPUnit 11; estilo: Pint.
- Prefijo API: `config('api.prefix')` = `v1`; rutas en `routes/api/v1/api.php`.

## Capas
```
routes → middleware → Controller → Request (validación) → Service → Repository → Model
                                   ↘ Resource (salida JSON)
```
- Controllers delgados: reciben, delegan, responden.
- Lógica de dominio en `app/Services/`.
- Consultas/filtros en `app/Repositories/`.
- Validación en `app/Http/Requests/V1/`.
- Forma del JSON en `app/Http/Resources/V1/`.
- Errores uniformes vía `app/Exceptions/ApiExceptionHandler.php`.
- Helpers `apiResponse()`, `apiSuccess()`, `apiError()`, `mediaUrl()`.

## Contrato de respuesta
```json
{
  "success": true,
  "message": "...",
  "data": {},
  "errors": null,
  "meta": null
}
```
- 422 validación con `errors: { campo: ["mensaje"] }`.
- 404 recurso/ruta, 401 no autenticado, 403 sin rol/permiso, 429 rate limit.
- No cambiar el envelope sin actualizar `docs/seven/API_CONTRACTS.md` y el frontend.
- Ocultar stack traces cuando `APP_DEBUG=false` y `API_DEBUG_TRACE=false`.

## Rutas y versionado
- Toda ruta nueva va en `routes/api/v1/api.php`; no en `routes/api.php`.
- Público: catálogo, disponibilidad, reservas anónimas, comentarios, noticias,
  assistant, health.
- `/admin/*`: Sanctum + ability `admin` + rol `SUPER_ADMIN|ADMIN_SEVEN` + permiso.
- `/provider/*`: Sanctum + ability `provider` + rol `PROVEEDOR_TURISTICO` +
  `provider.scope` (aislamiento por `provider_id`).
- Respetar `throttle:api`, `throttle:admin-login`, `throttle:comments`,
  `throttle:assistant`, `http.cache` y `api.version`.
- `show` de marketplace por slug; recursos clásicos por id numérico.

## Reglas
1. No meter consultas Eloquent complejas en controllers.
2. Validar todo input con FormRequest; no confiar en el cliente.
3. Usar transacciones para reservas y operaciones multi-tabla.
4. Evitar N+1 con `with()`/`load()`; revisar `DB::listen` o tests.
5. No exponer modelos completos: usar Resources.
6. No registrar secretos ni datos personales en logs.
7. Nuevas migraciones solo con aprobación explícita; en Fase 0 están prohibidas.
8. No romper compatibilidad de campos existentes; añadir, no renombrar.
9. Actualizar Swagger/`docs` cuando cambie un endpoint.

## Tests
- Feature tests por dominio en `backend/tests/Feature/`.
- Unit tests en `backend/tests/Unit/`.
- Usar factories y `RefreshDatabase`; nunca apuntar a la BD real.
- Cubrir: happy path, validación, 404, auth/roles, aislamiento de tenant,
  rate limit y regresión de seguridad cuando aplique.

## Comandos
```powershell
cd backend
php artisan route:list
php artisan test
vendor\bin\pint --test
php artisan serve --port=8000
```
