---
name: seven-database
description: Modelo de datos y convenciones de base de datos de SEVEN (MySQL/MariaDB). Usar al consultar tablas, relaciones, índices, migraciones, reservas, catálogo, media, roles o auditoría. Explica el esquema real, el patrón polimórfico y las reglas para no romper datos ni crear migraciones no aprobadas.
---

# SEVEN Database

## Motor y configuración
- MySQL/MariaDB; local `seven_db` en `127.0.0.1:3306`.
- Migraciones en `backend/database/migrations/`.
- Modelos en `backend/app/Models/`.
- En Fase 0 **no** se ejecutan migraciones, seeders ni cambios de esquema.

## Dominios

### Usuarios, acceso y auditoría
`users`, `password_reset_tokens`, `sessions`, `personal_access_tokens`,
`roles`, `permissions`, `role_user`, `permission_role`, `audit_logs`.
- Roles: `SUPER_ADMIN`, `ADMIN_SEVEN`, `PROVEEDOR_TURISTICO` (según seeders).
- `audit_logs`: `user_id`, `action`, `auditable_type/id`, `metadata`, `created_at`.

### Catálogo clásico
`categories` → `businesses`, `experiences` (`category_id`).
`accommodations` y `experiences` tienen `user_id` de propietario.
Estados habituales: `pending|approved|rejected|inactive`.

### Reservas
`customers` 1—N `bookings`.
`bookings` usa **polimorfismo**: `bookable_type` + `bookable_id` hacia
`Business`, `Accommodation` o `Experience`; además `type`
(`gastronomic|accommodation|experience`), `booking_code` (`SEV-XXXXXX`),
`start_at`, `end_at`, `guests`, `total_amount`, `currency`,
`status` (`pending|confirmed|cancelled|completed`).
`booking_status_histories` registra transiciones (`from_status`, `to_status`,
`changed_by`, `note`).

### Marketplace
`providers` es el tenant:
- `tours` → `tour_images`, `tour_includes`, `tour_recommendations`
- `restaurants` → `restaurant_products`
- `inns` → `inn_rooms`
- `bakeries` → `bakery_products`
- `promotions`, `news` (provider opcional)
- `media` es polimórfica (`model_type` + `model_id`) con `type`, `order`.

### Contenido y engagement
`banners`, `promotions`, `news`, `comments`, `notifications`.
`comments` es polimórfica (`commentable`) y usa `install_id` para limitar a un
comentario por dispositivo (`comments_one_comment_per_device`).

### Asistente
`assistant_conversations` (`id` ULID de 26 chars, `install_id`, `expires_at`)
y `assistant_messages` (`conversation_id`, `role`, `content`, `payload`).

### Infraestructura Laravel
`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.

## Índices relevantes
- `bookings`: `(status, start_at)`.
- `businesses`: `(status, is_verified)`.
- `accommodations`: `(status, capacity)`.
- `experiences`: `(status, max_capacity)`.
- `restaurants`/`inns`/`tours`/`bakeries`/`promotions`: `(provider_id, status)`.
- `comments`: único `(commentable_type, commentable_id, install_id)` y
  `(commentable_type, commentable_id, status, created_at)`.
- `news`: `(status, published_at)`, `(is_featured, order)`.
- `media`: `(model_type, model_id, type, order)`.

## Reglas
1. No cambiar tipos ni longitudes existentes sin una migración aprobada.
2. No borrar tablas/columnas; deprecar y migrar datos primero.
3. Toda FK nueva debe declarar `onDelete` explícito y un índice.
4. Usar `decimal(10,7)` para lat/long y `unsignedBigInteger` para dinero en COP
   (no floats).
5. Fechas de negocio en UTC; la app muestra `America/Bogota`.
6. Revisiones de rendimiento: `EXPLAIN` antes de añadir índices; evitar índices
   redundantes.
7. Consultas multi-tenant siempre filtran por `provider_id` en la capa de scope.
8. No ejecutar `migrate:fresh` contra una BD con datos.

## Comandos de solo lectura
```powershell
cd backend
php artisan db:show
php artisan db:table bookings
php artisan migrate:status
```
`db:show`/`db:table` requieren conexión; si no hay BD, documentarlo como no verificado.
