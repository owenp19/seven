# SEVEN — Contrato de API (v1)

Base URL: `http://localhost:8000/api/v1` (dev).
Las rutas públicas de v1 son anónimas (sin autenticación) y responden JSON.
El backoffice `/admin/*` sí exige un usuario administrativo autenticado con
rol autorizado: ver [Autenticación del backoffice](#autenticación-del-backoffice).

---

## Envelope común

```json
{
  "success": true,
  "message": "…",
  "data": null,
  "errors": null,
  "meta": null
}
```

- Errores de validación → `422` con `errors: { campo: ["mensaje"] }`.
- Recurso/ruta inexistente → `404` con `success: false`.
- Falla interna → `500` (detalles solo con `APP_DEBUG=true`).

---

## Autenticación del backoffice

Solo aplica a `/admin/*`; el resto de endpoints de este contrato son públicos.

```http
POST /admin/auth/login
Content-Type: application/json
Accept: application/json

{ "email": "admin@seven.test", "password": "password", "device_name": "Panel Web" }
```

`200 OK`:

```json
{
  "success": true,
  "message": "Authenticated successfully.",
  "data": {
    "token": "5|aBcDeFgHiJkLmNoP",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Administrador SEVEN",
      "email": "admin@seven.test",
      "roles": ["SUPER_ADMIN"],
      "permissions": ["admin.access", "catalog.manage"]
    }
  }
}
```

El token de texto plano solo se devuelve en esta respuesta (en BD solo se
guarda su hash) y viaja después en `Authorization: Bearer <token>`.

| Ruta | Métodos | Descripción |
|------|---------|-------------|
| `/admin/auth/login` | POST | Login; 6 intentos/min por email + IP |
| `/admin/auth/logout` | POST | Revoca el token en uso |
| `/admin/auth/me` | GET | Usuario autenticado + roles + permisos |
| `/admin/auth/tokens` | GET, POST | Lista y crea tokens personales |
| `/admin/auth/tokens/{id}` | DELETE | Revoca un token (404 si es ajeno) |
| `/admin/auth/tokens/others` | DELETE | Revoca todos los tokens salvo el actual |

Cada token personal incluye la **ability** `admin` y el usuario debe tener rol
`SUPER_ADMIN`/`ADMIN_SEVEN`. Los recursos CRUD exigen además el **permiso**
correspondiente (`catalog.manage`, `providers.manage`, `bookings.manage`,
`comments.moderate`, ...) y `SUPER_ADMIN` tiene bypass. Un token de proveedor
(ability `provider`) nunca puede acceder a `/admin/*`, aunque su rol sea de
backoffice. Las operaciones sensibles se auditan en `audit_logs`.

- Errores: `401` credenciales o token inválidos · `403` sin rol autorizado o sin
  permiso · `422` payload inválido · `429` rate limit · `503` en modo legacy sin
  `ADMIN_API_TOKEN`, o si el fail-secure detecta `APP_DEBUG=true`/legacy activo
  en `production`.
- Roles con acceso al panel: `SUPER_ADMIN`, `ADMIN_SEVEN` (configurable en
  `ADMIN_LOGIN_ROLES`); catálogo en `backend/database/seeders/RoleSeeder.php`.
- Las rutas CRUD de `/admin/*` (catálogo, reservas, media, comentarios) usan el
  mismo Bearer, rol y permiso; el detalle está en la tabla "Panel administrativo"
  del `README.md`.
- **Transición**: la cabecera legacy `X-Admin-Token` (token compartido
  `ADMIN_API_TOKEN`) solo se acepta con `ADMIN_LEGACY_TOKEN_ENABLED=true` y
  entorno `local`/`testing`; en producción está deshabilitada por defecto. Cambiar
  la contraseña o hacer logout revoca los tokens vigentes.

### Autenticación de proveedores

El portal de proveedores usa Sanctum con la **ability** `provider` y el rol
`PROVEEDOR_TURISTICO`; cada recurso queda filtrado por su `provider_id`.

| Ruta | Métodos | Descripción |
|------|---------|-------------|
| `/provider/auth/login` | POST | Login; emite token con ability `provider` |
| `/provider/auth/logout` | POST | Revoca el token en uso |
| `/provider/auth/me` | GET | Proveedor autenticado |
| `/provider/auth/tokens` | GET, POST | Lista y crea tokens personales (siempre `provider`) |
| `/provider/auth/tokens/others` | DELETE | Revoca todos los tokens salvo el actual |
| `/provider/auth/tokens/{id}` | DELETE | Revoca un token |

Un token administrador (ability `admin`) o el token legacy compartido **no**
acceden a `/provider/*` (`403`), y a la inversa un token `provider` no accede a
`/admin/*` aunque el usuario tenga rol de backoffice.

---

## GET `/` — Bienvenida

```json
{ "success": true, "data": { "name": "SEVEN API", "version": "v1", "status": "ok" } }
```

---

## Categories

### `GET /categories`
Lista las categorías activas (ordenadas por `sort_order`), con `business_count`.

```json
{
  "success": true,
  "data": [
    {
      "id": 1, "slug": "tours", "name": "Tours",
      "icon": "boat-outline", "image": null,
      "description": "Tours y actividades para descubrir el mar Caribe",
      "sort_order": 1, "business_count": 4
    }
  ]
}
```

Categorías de producción: `tours`, `restaurantes`, `posadas-nativas`, `panaderias`
(4 verticales; las categorías legadas `comida-rapida`, `cocina-raizal` y `experiencias`
fueron eliminadas).

### `GET /categories/{slug}`
Categoría por slug (404 si no existe).

---

## Marketplace — contenido dinámico

Recursos que alimentan el Home y las colecciones del frontend. Todas las rutas
son públicas, limitadas por `throttle:api` (120/min) y devuelven el mismo
`envelope` común.

Convenciones:

- **Listados**: `index` paginan con `?per_page=` (default `15`, máx `50`); la
  colección vendrá en `meta.pagination`.
- **Detalles**: `show` se resuelve por **slug** (`[a-z0-9-]+`), no por id.
- **Imágenes**: URLs absolutas ya resueltas por `mediaUrl()` (externas o
  `{APP_URL}/storage/...`). El frontend nunca construye URLs de medios.
- **Estado**: solo se publican registros `active`/`approved`.

### `GET /tours?per_page=` · `GET /tours/{slug}`

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "slug": "johnny-cay",
      "name": "Johnny Cay",
      "subtitle": "Piscina natural a 15 minutos",
      "description": "Un paraíso de arena blanca y aguas turquesas…",
      "category": "Tours y experiencias",
      "location": "San Andrés, Colombia",
      "duration": "4-5 horas",
      "price": 170000,
      "rating": 4.8,
      "reviews": 132,
      "cover_image": "http://localhost:8000/storage/tours/johnny-cay/cover.svg",
      "status": "approved",
      "images": [
        { "id": 1, "image": "http://localhost:8000/storage/tours/johnny-cay/cover.svg", "order": 1 }
      ],
      "includes": [{ "icon": "boat-outline", "name": "Transporte" }],
      "recommendations": [{ "icon": "sunny-outline", "name": "Bloqueador solar" }],
      "provider": { "id": 1, "business_name": "SEVEN Tours" }
    }
  ]
}
```

> El modelo de frontend deriva los chips de detalle (duración / tipo /
> ubicación) de `duration`, `category` y `location`; el backend no expone un
> campo `features`.

### `GET /restaurants?per_page=` · `GET /restaurants/{slug}`

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "slug": "la-regatta",
      "name": "La Regatta",
      "logo": "http://localhost:8000/storage/restaurants/la-regatta/logo.svg",
      "cover_image": "http://localhost:8000/storage/restaurants/la-regatta/cover.svg",
      "description": "Cocina caribeña frente al mar…",
      "address": "Av. Colombia, San Andrés",
      "phone": "+57 318 555 0142",
      "category": "Cocina caribeña",
      "rating": 4.6,
      "reviews": 88,
      "opening_hours": null,
      "price_range": "$$",
      "location": { "latitude": 12.584, "longitude": -81.7 },
      "status": "approved",
      "products": [
        {
          "id": 1,
          "name": "Langosta al ajillo",
          "description": "Servida con arroz de coco…",
          "price": 95000,
          "image": "http://localhost:8000/storage/products/langosta-al-ajillo.svg",
          "category": "Platos fuertes",
          "status": "active"
        }
      ]
    }
  ]
}
```

### `GET /inns?per_page=` · `GET /inns/{slug}`

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "slug": "posada-el-coco-loco",
      "name": "Posada El Coco Loco",
      "description": "Frente al mar con desayuno incluido…",
      "cover_image": "http://localhost:8000/storage/inns/posada-el-coco-loco/cover.svg",
      "location": "San Luis, San Andrés",
      "price": 280000,
      "services": ["desayuno incluido", "wifi", "aire acondicionado"],
      "availability": true,
      "rating": 4.7,
      "status": "approved",
      "rooms": [
        {
          "id": 1,
          "name": "Habitación doble",
          "description": "Balcón con vista al mar…",
          "price": 280000,
          "image": "http://localhost:8000/storage/inns/posada-el-coco-loco/room.svg",
          "capacity": 2
        }
      ]
    }
  ]
}
```

### `GET /bakeries?per_page=` · `GET /bakeries/{slug}`

Vertical de panaderías (mismo contrato que restaurantes): `logo`, `cover_image`,
`opening_hours`, `price_range`, `location` y catálogo de `products`.

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "slug": "panaderia-suzette",
      "name": "Panadería y Repostería Suzette",
      "logo": "http://localhost:8000/storage/bakeries/panaderia-suzette/logo.svg",
      "cover_image": "http://localhost:8000/storage/bakeries/panaderia-suzette/cover.jpg",
      "description": "Pan de coco, bizcochos y tortas tradicionales…",
      "address": "Avenida Colombia, Edificio Osaka Sarie Bay, San Andrés",
      "phone": "+57 8 512 7867",
      "category": "Panadería y repostería",
      "rating": 4.6,
      "reviews": 420,
      "price_range": "$$",
      "status": "approved",
      "products": [
        {
          "id": 1,
          "name": "Pan de coco",
          "description": "El pan emblemático de la isla, horneado al carbón.",
          "price": 5000,
          "image": "http://localhost:8000/storage/bakeries/panaderia-suzette/products/pan-de-coco.svg",
          "category": "Panes",
          "status": "active"
        }
      ]
    }
  ]
}
```

### `GET /banners`

Banners `active` ordenados por `order` (sin paginar). Alimentan el carousel
del Home; `button_url` puede ser una ruta interna (`/tours`) o URL externa.

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "San Andrés te espera",
      "subtitle": "Vive el Caribe como nunca antes",
      "description": "Tours, gastronomía y posadas nativas…",
      "image": "http://localhost:8000/storage/banners/san-andres.svg",
      "button_text": "Ver tours",
      "button_url": "/tours",
      "status": "active",
      "order": 1,
      "start_date": null,
      "end_date": null
    }
  ]
}
```

### `GET /promotions`

Promociones `active` (vigentes por fechas) con su `provider`.

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "provider_id": 1,
      "title": "20% descuento cena romántica",
      "description": "Cena para dos en La Regatta…",
      "image": "http://localhost:8000/storage/promotions/cena-romantica.svg",
      "discount": 20,
      "price": 200000,
      "start_date": null,
      "end_date": "2026-12-31",
      "status": "active",
      "provider": { "id": 1, "business_name": "La Regatta", "type": "restaurant" }
    }
  ]
}
```

---

## Businesses

### `GET /businesses?category=&q=&featured=&page=`
Solo negocios `approved`.

Query params:
- `category` — slug de categoría.
- `q` — búsqueda por nombre/descripción.
- `featured` — `1` filtra solo verificados.
- `page` — paginación (15 por página por defecto).

```json
{
  "success": true,
  "data": [
    {
      "id": 1, "slug": "restaurante-el-rincon-1", "name": "Restaurante El Rincón Isleño",
      "description": "Cocina caribeña…",
      "category": { "id": 1, "slug": "restaurantes", "name": "Restaurantes", "icon": "…", "image": null, "description": "…", "sort_order": 1, "business_count": null },
      "address": "San Andrés Isla, Centro",
      "location": { "latitude": "12.5847", "longitude": "-81.7005" },
      "phone": "+57 318 555 0142", "whatsapp": "+573185550142", "email": "…",
      "images": ["https://…"], 
      "opening_hours": { "monday": { "open": "10:00", "close": "22:00" }, "sunday": null },
      "tags": ["mariscos", "familiar"],
      "features": ["wifi", "terraza"],
      "is_verified": true, "rating_avg": 0.0, "rating_count": 0
    }
  ],
  "meta": { "pagination": { "total": 10, "per_page": 15, "current_page": 1, "last_page": 1 } }
}
```

### `GET /businesses/{id}`
Detalle (404 si no está `approved`).

### `GET /businesses/{id}/availability?date=YYYY-MM-DD`
Disponibilidad para una fecha (default: hoy).

```json
{
  "data": {
    "business_id": 1, "date": "2026-09-07",
    "is_open": true,
    "opening_hours": { "open": "10:00", "close": "22:00" },
    "slots_taken": 0
  }
}
```

---

## Accommodations

### `GET /accommodations?guests=&q=&page=`
- `guests` — solo posadas con capacidad >= ese número.

```json
{
  "data": [
    {
      "id": 1, "slug": "posada-el-coco-loco-1", "name": "Posada El Coco Loco",
      "description": "…", "capacity": 4,
      "price_per_night": 280000, "currency": "COP",
      "address": "…", "location": { "latitude": "…", "longitude": "…" },
      "images": ["…"], "included_services": ["Desayuno incluido", "Wi-Fi"],
      "rating_avg": 0.0, "rating_count": 0
    }
  ]
}
```

### `GET /accommodations/{id}` · `GET /accommodations/{id}/availability?date=`

```json
{
  "data": {
    "accommodation_id": 1, "date": "2026-09-07",
    "is_available": true, "capacity": 4, "price_per_night": 280000
  }
}
```

---

## Experiences

### `GET /experiences?guests=&category=&q=&page=`
- `guests` — solo experiencias con `max_capacity` >= ese número.
- `category` — slug de categoría.

```json
{
  "data": [
    {
      "id": 1, "slug": "tour-en-lancha-por-los-cayos-1", "name": "Tour en lancha por los cayos",
      "description": "…",
      "category": { "id": 6, "slug": "experiencias", "name": "Experiencias", "…": "…" },
      "duration_minutes": 240, "price_per_person": 180000, "currency": "COP",
      "max_capacity": 8, "address": "…",
      "location": { "latitude": "…", "longitude": "…" },
      "includes": ["Guía local", "Snorkel", "Transporte", "Agua"],
      "images": ["…"], "rating_avg": 0.0, "rating_count": 0
    }
  ]
}
```

### `GET /experiences/{id}` · `GET /experiences/{id}/availability?date=`

```json
{
  "data": {
    "experience_id": 1, "date": "2026-09-07",
    "is_available": true, "max_capacity": 8, "price_per_person": 180000
  }
}
```

---

## Bookings

### `POST /bookings` — crear reserva

`bookable_type` acepta `business` | `accommodation` | `experience`.

```json
{
  "bookable_type": "experience",
  "bookable_id": 1,
  "type": "experience",
  "start_at": "2026-09-04T09:00:00+00:00",
  "end_at": null,
  "guests": 4,
  "special_requests": "Solicito transporte desde el hotel",
  "currency": "COP",
  "customer": {
    "name": "Laura Gómez",
    "phone": "+57 300 210 3344",
    "email": "laura@mail.test"
  }
}
```

Reglas:
- `start_at` debe ser futura; `end_at` (si existe) posterior a `start_at`.
- `guests` entre 1 y 20.
- `type` = `gastronomic` → bookable `business`; `accommodation` → `accommodation`; `experience` → `experience`.
- El cliente se deduplica por teléfono o correo (no se crea duplicado).

Respuesta `201`:

```json
{
  "success": true,
  "message": "Booking created successfully.",
  "data": {
    "id": 12, "booking_code": "SEV-K3F9QP", "type": "experience", "status": "pending",
    "bookable": { "type": "experience", "id": 1, "name": "Tour en lancha por los cayos" },
    "start_at": "…", "end_at": null, "guests": 4,
    "total_amount": 720000, "currency": "COP",
    "special_requests": null,
    "customer": { "id": 3, "name": "Laura Gómez", "phone": "+57 300 210 3344" },
    "created_at": "…"
  }
}
```

El monto se estima automáticamente:
- Alojamiento → `price_per_night × noches` (noches = ceil de días entre fechas).
- Experiencia → `price_per_person × guests`.
- Gastronómico → `0` en v1.

### `GET /bookings?customer_id=&phone=`
Historial de un cliente por id o por teléfono.

### `GET /bookings/{bookingCode}`
Consulta una reserva por código público `SEV-XXXXXX`.

### `PUT /bookings/{bookingCode}`
Actualiza el `status` (`pending|confirmed|cancelled|completed`).

### `DELETE /bookings/{bookingCode}`
Cancela la reserva (no la elimina): pone `status = cancelled`.

---

## Estados y tipos

| Concepto | Valores |
|----------|---------|
| `bookings.type` | `gastronomic`, `accommodation`, `experience` |
| `bookings.status` | `pending`, `confirmed`, `cancelled`, `completed` |
| `businesses.status` | `pending`, `approved`, `rejected`, `inactive` |
| `accommodations.status` / `experiences.status` | `pending`, `approved`, `rejected`, `inactive` |
| `tours.status` / `restaurants.status` / `inns.status` | `pending`, `approved`, `rejected`, `inactive` |
| `banners.status` / `promotions.status` | `active`, `inactive` |

---

## Notas de integración frontend

- Usar siempre el `envelope` y leer `data` (el frontend auto-desenvuelve vía `ApiClientService`).
- La paginación viene en `meta.pagination`.
- Los códigos de reserva (`bookingCode`) son el identificador público para WhatsApp/soporte.
- Los detalles del marketplace se navegan por slug (`/tour/:slug`, `/restaurant/:slug`, `/inn/:slug`), nunca por id.
- Los favoritos y "recientes" son locales (Ionic Storage, claves `seven:*`); no existe todavía un endpoint de favoritos en v1.