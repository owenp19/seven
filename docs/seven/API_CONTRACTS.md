# SEVEN — Contrato del API v1

Base: `http://127.0.0.1:8000/api/v1` (dev).
Contrato detallado previo: `docs/API.md`. Este documento es el mapa operativo.

## Envelope
```json
{
  "success": true,
  "message": "…",
  "data": null,
  "errors": null,
  "meta": null
}
```
- Validación → `422` con `errors: { campo: ["mensaje"] }`.
- No encontrado → `404`; no autenticado → `401`; sin permiso → `403`;
  rate limit → `429`; error interno → `500` (sin stack en producción).

## Paginación
- `per_page` por defecto 15; máximo 50 (`config/api.php` y `environment.maxPerPage`).
- `meta.pagination`: `total`, `per_page`, `current_page`, `last_page`.

## Público
| Método | Ruta | Notas |
|--------|------|-------|
| GET | `/` | Bienvenida/estado del API |
| GET | `/health` | Health check |
| GET | `/categories` | Lista; `http.cache` |
| GET | `/categories/{category}` | Detalle |
| GET | `/tours` | Filtros/paginación |
| GET | `/tours/{slug}` | Detalle por slug |
| GET | `/tours/{slug}/comments` | Comentarios aprobados |
| POST | `/tours/{slug}/comments` | Crear comentario (`throttle:comments`) |
| GET | `/restaurants` | Lista |
| GET | `/restaurants/{slug}` | Detalle por slug |
| GET | `/restaurants/{slug}/comments` | Comentarios |
| POST | `/restaurants/{slug}/comments` | Crear comentario |
| GET | `/inns` | Lista |
| GET | `/inns/{slug}` | Detalle por slug |
| GET | `/inns/{slug}/comments` | Comentarios |
| POST | `/inns/{slug}/comments` | Crear comentario |
| GET | `/bakeries` | Lista |
| GET | `/bakeries/{slug}` | Detalle por slug |
| GET | `/banners` | Lista; `http.cache` |
| GET | `/promotions` | Lista; `http.cache` |
| GET | `/news` | Lista; `http.cache` |
| GET | `/news/{slug}` | Detalle por slug |
| GET | `/businesses` | Filtros `category`, `q`, `featured` |
| GET | `/businesses/{id}` | Detalle por id |
| GET | `/businesses/{id}/availability` | `date` |
| GET | `/accommodations` | Filtros `guests`, `q` |
| GET | `/accommodations/{id}` | Detalle |
| GET | `/accommodations/{id}/availability` | `date` |
| GET | `/experiences` | Filtros `guests`, `category`, `q` |
| GET | `/experiences/{id}` | Detalle |
| GET | `/experiences/{id}/availability` | `date` |
| GET | `/bookings` | Filtros `customer_id`, `phone` |
| POST | `/bookings` | Crear reserva anónima |
| GET | `/bookings/{bookingCode}` | Consultar |
| PUT | `/bookings/{bookingCode}` | Actualizar |
| DELETE | `/bookings/{bookingCode}` | Cancelar/eliminar |
| POST | `/assistant/messages` | Asistente (`throttle:assistant`) |

## Admin (`/admin/*`)
Autenticación: `Authorization: Bearer <token>` con ability `admin` y rol
`SUPER_ADMIN` o `ADMIN_SEVEN`; además permiso por recurso.
| Método | Ruta |
|--------|------|
| POST | `/admin/auth/login` (público, throttle 6/min) |
| POST | `/admin/auth/logout` |
| GET | `/admin/auth/me` |
| GET/POST | `/admin/auth/tokens` |
| DELETE | `/admin/auth/tokens/{tokenId}` |
| DELETE | `/admin/auth/tokens/others` |
| CRUD | `/admin/providers` (`providers.manage`) |
| CRUD | `/admin/categories`, `/banners`, `/promotions`, `/media` (`catalog.manage`) |
| CRUD | `/admin/news`, `/businesses`, `/tours`, `/inns`, `/restaurants`, `/bakeries`, `/experiences`, `/accommodations` (`content.manage`) |
| GET | `/admin/bookings`, `/admin/bookings/{bookingCode}` (`bookings.manage`) |
| PATCH | `/admin/bookings/{bookingCode}/status` |
| GET | `/admin/comments` (`comments.moderate`) |
| PATCH | `/admin/comments/{comment}/status` |
| DELETE | `/admin/comments/{comment}` |

## Provider (`/provider/*`)
Autenticación: Bearer con ability `provider`, rol `PROVEEDOR_TURISTICO` y
`provider.scope`.
| Método | Ruta |
|--------|------|
| POST | `/provider/auth/login` (público) |
| POST | `/provider/auth/logout` |
| GET | `/provider/auth/me` |
| GET/POST | `/provider/auth/tokens` |
| DELETE | `/provider/auth/tokens/{tokenId}` / `/others` |
| GET | `/provider/providers`, `/provider/providers/{id}` |
| PUT | `/provider/providers/{id}` |
| CRUD | `/provider/businesses`, `/accommodations`, `/experiences`, `/tours`, `/restaurants`, `/inns`, `/bakeries`, `/promotions`, `/news`, `/media` |
| GET | `/provider/bookings`, `/provider/bookings/{bookingCode}` |
| PATCH | `/provider/bookings/{bookingCode}/status` |

## Límites
| Tier | Límite |
|------|--------|
| Invitado | 120/min |
| Autenticado | 600/min |
| Admin | 60/min |
| Login admin/provider | 6/min por email + IP |
| Comentarios | 5/hora por dispositivo |
| Assistant | 20/min invitado, 60/min autenticado, ráfaga 10 |

## Reglas de contrato
- No cambiar nombres de campos existentes; añadir campos es compatible.
- Mantener el envelope y los códigos HTTP.
- Los `show` de marketplace usan slug; los clásicos usan id numérico.
- Documentar cualquier endpoint nuevo en este archivo y en `docs/API.md`.
