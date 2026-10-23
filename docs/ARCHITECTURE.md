# SEVEN — Arquitectura

Documento de referencia de decisiones técnicas y estructura del monorepo
`SEVEN-app`. Cubre backend (Laravel 11), frontend (Angular 21 + Ionic 9)
y el sistema de diseño SEVEN.

---

## 1. Resumen

SEVEN conecta turistas con servicios auténticos de la isla (gastronomía,
posadas nativas, experiencias/tours). La v1 es **anónima**: el visitante
explora, consulta disponibilidad, reserva y contacta al negocio sin crear una
cuenta. El Home y las colecciones (tours, restaurantes, posadas, promociones,
banners) se alimentan 100 % desde el **marketplace API**, sin contenido
hardcodeado en el frontend; los favoritos y visitas recientes se guardan de
forma **offline** en el dispositivo (Ionic Storage). Las cuentas completas,
pagos, reseñas y sincronización de favoritos llegan en v2.

```
SEVEN-app/
├── backend/    API REST Laravel 11  (PHP 8.2, MySQL)
├── frontend/   App Angular 21 + Ionic 9 (web responsive + Android/iOS vía Capacitor)
└── docs/       Documentación técnica
```

---

## 2. Backend — Laravel 11

### 2.1 Estructura

```
app/
├── Models/
│   ├── Concerns/HasBookings.php        Trait de la relación polimórfica bookable
│   ├── Concerns/HasMedia.php           Trait morphMany media (imágenes, logos)
│   ├── Contracts/
│   │   ├── Bookable.php                Contrato de modelos reservables
│   │   └── Searchable.php              Contrato de repositorios listables
│   ├── Category.php  Business.php  Accommodation.php  Experience.php
│   ├── Customer.php  Booking.php  User.php
│   ├── Marketplace:  Provider.php  Tour.php  TourImage.php  TourInclude.php
│   │                  TourRecommendation.php  Restaurant.php
│   │                  RestaurantProduct.php  Inn.php  InnRoom.php
│   │                  Banner.php  Promotion.php  Media.php  Notification.php
├── Http/
│   ├── Controllers/Api/V1/             Category, Business, Accommodation, Experience, Booking
│   │   └── Marketplace:                Tour, Restaurant, Inn, Banner, Promotion
│   ├── Requests/V1/                    StoreBookingRequest, UpdateBookingRequest
│   ├── Resources/V1/                   CategoryResource, BusinessResource, AccommodationResource,
│   │                                   ExperienceResource, BookingResource, CustomerResource,
│   │                                   ProviderResource, TourResource, RestaurantResource,
│   │                                   InnResource, BannerResource, PromotionResource
│   └── Middleware/ApiVersionMiddleware.php
├── Services/
│   ├── BookingService.php              Lógica de reservas (código, cliente, montos)
│   ├── AvailabilityService.php         Disponibilidad por negocio/posada/experiencia
│   ├── CustomerLookupService.php       Lookup anónimo de clientes
│   └── MediaService.php                Almacenamiento/resolución de archivos locales
├── Repositories/
│   ├── BaseRepository.php              Capa base de acceso a datos
│   ├── CategoryRepository  BusinessRepository  AccommodationRepository
│   ├── ExperienceRepository  BookingRepository
├── Exceptions/ApiExceptionHandler.php  Envelope JSON uniforme para errores API
└── Helpers/helpers.php                 apiResponse() / apiSuccess() / apiError() / mediaUrl()
```

**Roles:**
| Capa | Responsabilidad |
|------|-----------------|
| `Controllers` | Delgados: recibir request, delegar, responder |
| `Services` | Lógica de dominio reutilizable (sin HTTP) |
| `Repositories` | Consultas y filtros (acceso a datos) |
| `Resources` | Forma del JSON de salida |
| `Requests` | Validación de entrada |
| `Models` | Entidades + relaciones + scopes de publicación (`approved`) |

### 2.2 Versionamiento de API

Todas las rutas viven bajo `routes/api/v1/api.php`, montadas en
`routes/api.php` con el prefijo `config('api.prefix')` (`v1`) y el middleware
`api.version`. `ApiVersionMiddleware` valida el segmento de versión contra
`config('api.versions')`. Una v2 futura crea `routes/api/v2/api.php` sin tocar v1.

```
GET  /api/v1/                    Bienvenida del API
GET  /api/v1/categories
GET  /api/v1/categories/{slug}
GET  /api/v1/banners
GET  /api/v1/promotions
GET  /api/v1/tours?per_page=
GET  /api/v1/tours/{slug}
GET  /api/v1/restaurants?per_page=
GET  /api/v1/restaurants/{slug}
GET  /api/v1/inns?per_page=
GET  /api/v1/inns/{slug}
GET  /api/v1/businesses?category=&q=&featured=
GET  /api/v1/businesses/{id}
GET  /api/v1/businesses/{id}/availability?date=
GET  /api/v1/accommodations?guests=&q=
GET  /api/v1/accommodations/{id}
GET  /api/v1/accommodations/{id}/availability?date=
GET  /api/v1/experiences?guests=&category=&q=
GET  /api/v1/experiences/{id}
GET  /api/v1/experiences/{id}/availability?date=
GET  /api/v1/bookings?customer_id=&phone=
POST /api/v1/bookings
GET  /api/v1/bookings/{bookingCode}
PUT  /api/v1/bookings/{bookingCode}
DELETE /api/v1/bookings/{bookingCode}
```

Los `show` del marketplace usan **slug** (`Route::get('{tour}')` con restricción
`[a-z0-9-]+`); los recursos clásicos (`businesses`, `accommodations`,
`experiences`) siguen por id numérico.

### 2.3 Envelope de respuesta

Toda respuesta (éxito o error) usa la misma forma:

```json
{
  "success": true,
  "message": "Businesses retrieved successfully.",
  "data": [],
  "errors": null,
  "meta": { "pagination": { "total": 10, "per_page": 15, "current_page": 1, "last_page": 1 } }
}
```

`ApiExceptionHandler` garantiza el mismo contrato para errores
(422 validación, 404 recurso/ruta, 401 no autenticado, 500)
y oculta stack traces cuando `APP_DEBUG=false`.

### 2.4 Modelo de datos

```
categories ──< businesses
               experiences (category_id nullable)

businesses / accommodations / experiences —(morphMany bookable)── bookings → customers

bookings: bookable_type (class), bookable_id, type (gastronomic|accommodation|experience),
          booking_code (SEV-XXXXXX), start_at, end_at, guests, total_amount, currency, status

-- Marketplace (contenido dinámico) --
providers ──< tours ──< tour_images / tour_includes / tour_recommendations
providers ──< restaurants ──< restaurant_products
providers ──< inns ──< inn_rooms
providers ──< promotions
media            (morphMany: tours, restaurants, inns, banners…)
banners · notifications · users.role (admin|provider|customer)
```

Los tres modelos reservables implementan `Contracts\Bookable` y usan el trait
`Concerns\HasBookings`, de modo que `BookingService` y `AvailabilityService`
trabajan contra un mismo contrato. El marketplace (tours/restaurantes/inns)
es de solo lectura en v1 y se sirve por slug con scopes de publicación.

### 2.5 Lógica de reservas (`BookingService`)

- Resuelve el bookable por `bookable_type` (`business` | `accommodation` | `experience`) y lo filtra con `approved()`.
- Dedup el cliente por teléfono y correo.
- Genera códigos `SEV-XXXXXX` únicos.
- Estima el monto: alojamiento → `price_per_night × noches`; experiencia → `price_per_person × guests`; gastronómico → 0 en v1 (lo define el negocio luego).

### 2.6 Nombres y convenciones

- Mesas en *Inglés*: `businesses`, `accommodations`, `experiences`, `bookings`, `customers`, `providers`, `tours`, `restaurants`, `inns`, `banners`, `promotions`.
- El **contenido** (nombres de categorías/negocios) es español, es contenido visible al usuario.
- `resources` envuelve los nombres de columnas con `mediaUrl()` para entregar **URLs absolutas** de medios.

### 2.7 Marketplace API (contenido dinámico)

- `index` paginan con `per_page` (15 máx 50); `show` por slug.
- Solo se publican registros `approved` (ofertas) / `active` (banners, promos, categorías).
- `mediaUrl()` resuelve rutas locales al disco público (`storage/app/public`) y pasa URLs
  externas/`data:` tal cual. `scripts/import-media.ps1` descarga fotos reales (CC BY-SA) desde
  Wikimedia Commons y el `MarketplaceSeeder` genera SVGs de marca solo como fallback:
  ninguna URL de media queda rota y no se depende de servicios externos en runtime.
- El Dashboard/gestión de proveedores y la base para reservas v2 están preparados:
  `users.role`, tabla `notifications` y modelos de marketplace son la base.

---

## 3. Frontend — Angular 21 + Ionic 9

### 3.1 Estructura

```
src/
├── app/
│   ├── core/
│   │   ├── config/        AppConfig interface + constantes
│   │   ├── constants/     APP_CONSTANTS (storage keys, estados de reserva)
│   │   ├── tokens/        InjectionToken APP_CONFIG y STORAGE (Ionic Storage)
│   │   └── services/      ApiClientService (única puerta HTTP) + FavoritesService (offline)
│   ├── shared/            Componentes reutilizables
│   │   ├── components/    service-card, section-header, rating-stars, catalog-card,
│   │   │                  accommodation-card, experience-card, tourist-news-carousel,
│   │   │                  fallback cover image
│   │   ├── directives/    safe-img (fallback de imágenes)
│   │   └── pipes/         currencyCOP (CurrencyPipe), initials
│   ├── features/          Páginas por dominio (lazy loaded)
│   │   ├── home/  explore/  bookings/  business-detail/  tour-detail/
│   │   ├── tours/  restaurants/  inns/  promotions/
│   │   └── restaurant-detail/  inn-detail/
│   ├── layouts/           tabs-layout (navegación inferior)
│   ├── services/          category, business, accommodation, experience, booking,
│   │   │                  tour, restaurant, inn, banner, promotion
│   ├── models/            Clases con fromApi() + Payload interfaces
│   │   └── (banner, promotion, provider, restaurant, inn + tour/category/business…)
│   ├── interfaces/        ApiResponse, Paginated, ApiError, GeoPoint, OpeningHours
│   ├── guards/            featureFlagGuard (payments/reviews/accounts a futuro)
│   ├── interceptors/      api-request (headers) + error (normaliza ApiError)
│   └── utils/             formatters.ts (moneda, fechas, WhatsApp, iniciales)
│
├── assets/                favicon, fallback-service.svg, shapes.svg
├── environments/          environment.ts / environment.prod.ts
└── theme/                 variables.scss (tokens SEVEN) + global.scss
```

**Responsabilidad de cada carpeta:**

| Carpeta | Responsabilidad |
|---------|-----------------|
| `core/` | Infraestructura: config, tokens, cliente HTTP. No sabe de dominios. |
| `shared/` | Componentes/pipes/directivas 100% reutilizables, sin lógica de negocio. |
| `features/` | Páginas por vertical, lazy-loaded. Sólo aquí se usa el router de páginas. |
| `layouts/` | Estructuras de navegación (tabs). |
| `services/` | Llamadas a la API por dominio (usan ApiClientService + mapean a modelos). |
| `models/` | Clases de dominio con `fromApi()` (Data Transfer Objects). |
| `interfaces/` | Tipos compartidos: respuesta API, paginación, errores. |
| `guards/` | Guardas de ruta (feature flags). |
| `interceptors/` | Transversal HTTP: headers y errores. |
| `utils/` | Funciones puras de formato. |

### 3.2 Decisiones arquitectónicas frontend

- **Standalone components + lazy loading** (práctica actual de Angular): `routes.ts` usa `loadComponent()` por feature. El router usa `withComponentInputBinding()`: las páginas de detalle reciben el slug vía `@Input() slug`.
- **Capa HTTP única**: `ApiClientService` compone `{apiUrl}/{apiVersion}`, desenvuelve `data` y preserva paginación; los services de dominio solo mapean a modelos.
- **Mappers `fromApi`**: los modelos convierten el payload API (snake_case) a propiedades camelCase; la UI nunca ve el payload crudo.
- **Modelos inmutables**: propiedades `readonly`, con una clase + una interfaz `Payload`.
- **Feature flags**: `environment.features.*` controla qué verticales están activas (`featureFlagGuard`).
- **Caché por colección**: los services consultan una vez por sesión y cachean en `switchMap/shareReplay`; pull-to-refresh fuerza nuevamente la red.
- **Favoritos offline**: `FavoritesService` persiste en Ionic Storage (keys `seven:favorites`, `seven:recent`, `seven:preferences`) y expone `BehaviorSubject` para que Home y detalle se mantengan en sincronía sin re-consulta.

### 3.3 Marketplace dinámico (Home y colecciones)

Los datos visibles en el Home y las colecciones provienen **exclusivamente de la API**:

- **Home**: banners (`GET /banners`), categorías (`GET /categories`) y destacados mezclan negocios/tours/restaurantes/posadas (`per_page: 3`) en tarjetas tipadas; las promociones (`GET /promotions`) se muestran en su propia sección. El `button_url` de cada banner navega rutas internas o abre externos.
- **Colecciones**: `tours`, `restaurants`, `inns`, `promotions` con skeletons, empty states y pull-to-refresh; tarjetas reutilizables vía `catalog-card`.
- **Detalles por slug**: `tour/:slug`, `restaurant/:slug`, `inn/:slug` se resuelven con `@Input() slug` + `shareReplay`; el botón de favorito llama a `FavoritesService`. Los features del tour se derivan de `duration/category/location` en el modelo (el backend no expone `features`).
- **Explorar**: las fichas de categoría mapean slugs conocidos a las colecciones reales (`/tours`, `/restaurants`, `/inns`) y el resto al explorador clásico de negocios.

### 3.4 Sistema de diseño SEVEN

Tokens en `src/theme/variables.scss`: `--seven-*` (canónicos) con aliases
`--go-*` compatibles hacia atrás.

| Token | Valor | Uso |
|-------|-------|-----|
| `--seven-deep-sea` | `#0B2D4A` | Institucional: headings, navegación, UI oscura |
| `--seven-ocean-blue` | `#0077B6` | Interactivos: CTAs, enlaces, botones |
| `--seven-tropical-tide` | `#00D1C5` | Acento: highlights, eco, estados activos |
| `--seven-lagoon-blue` | `#1CA7EC` | Info / acento secundario |
| `--seven-palm-green` | `#1F8A5C` | Éxito, naturaleza |
| `--seven-soft-coral` | `#FF7A59` | Promos, destacados, warnings |
| `--seven-sun-gold` | `#FF9E45` | Estrellas / ratings, gradientes cálidos |
| `--seven-arena` | `#E8DCC8` | Secciones premium / editoriales |
| `--seven-gray-50..900` | escala | Neutros slate con subtono azul |

Escalas 50..900 por anchor, neutros, semánticos, backgrounds (`ocean`,
`caribbean`, `sunset`), textos, bordes, radii, sombras y espaciados viven
centralizados en `variables.scss`. Redes plenas (hero, menú, splash) usan
los backgrounds `--seven-background-*`.

- **Tipografías**: `Poppins` (UI) + `Oswald` (títulos/display).
- **Escala de espaciado**: múltiplos de 4px; radios y sombras definidos como tokens.
- **Componentes base**: `service-card`, `accommodation-card`, `experience-card`, `rating-stars`, `section-header`, `safe-img` con fallback SVG de marca.

### 3.5 Capacitor (móvil)

Config en `frontend/capacitor.config.ts` (`appId: com.seven.app`).
Plataformas `android/` e `ios/` ya generadas. Workflow:

```bash
cd frontend
npm run build          # compila a www/
npx cap sync           # copia www a las plataformas nativas
npx cap open android   # o: npx cap open ios
```

---

## 4. Base de datos MySQL

Migraciones en `backend/database/migrations` (orden de creación):

| Tabla | Propósito |
|-------|-----------|
| `users`, `sessions`, `cache`, `jobs` | Infraestructura Laravel/Sanctum |
| `personal_access_tokens` | Sanctum (preparada para v2) |
| `categories` | Verticales (tours, restaurantes, posadas nativas, panaderías — 4 en producción) con `sort_order` y `status` |
| `businesses` | Restaurantes/panaderías/street food con horarios JSON, tags, verificación |
| `accommodations` | Posadas nativas: capacidad, precio/noche COP |
| `experiences` | Actividades locales: duración, precio/persona, capacidad |
| `customers` | Visitantes anónimos, dedup por teléfono/correo, `guest_token` |
| `bookings` | Reservas polimórficas (`bookable`) con tipo, fechas, montos y estados |
| `providers` | Proveedores del marketplace (operadores de tours/restaurantes/posadas/panaderías), `type`, `verification_status` |
| `tours` (+ `tour_images`, `tour_includes`, `tour_recommendations`) | Experiencias turísticas vendibles con slug, precio, rating y secciones |
| `restaurants` (+ `restaurant_products`) | Gastronomía con menú de productos |
| `bakeries` (+ `bakery_products`) | Panaderías con catálogo de productos |
| `inns` (+ `inn_rooms`) | Posadas con habitaciones y servicios |
| `banners` | Carusel del Home (imagen, texto, CTA, fechas) |
| `promotions` | Ofertas de proveedores (descuento, precio, fechas) |
| `media` | Archivos (morphMany) para imágenes/portadas/logos |
| `notifications` | Base para notificaciones (futura entrega) |

Seeders (orquestados por `DatabaseSeeder`):
- `CategorySeeder` — 4 categorías base (tours, restaurantes, posadas nativas, panaderías); elimina verticales legadas.
- `DemoDataSeeder` — negocios, posadas, experiencias, clientes y reservas de ejemplo, con *fallbacks* sobre las 4 categorías vigentes.
- `MarketplaceSeeder` — datos **reales** de San Andrés: 12 providers (operadores, restaurantes, posadas, panaderías), 3 tours, 3 restaurantes (18 productos), 3 posadas (6 habitaciones), 3 panaderías (18 productos), 5 banners y 2 promos. Portadas/galerías: fotos reales con nombres SEO en `storage/app/public` (descargadas con `scripts/import-media.ps1` desde Wikimedia Commons, CC BY-SA); cualquier slot faltante cae a un SVG de marca para que ninguna URL quede rota.

---

## 5. Testing

- **Backend**: PHPUnit 11, suite `Feature` contra SQLite en memoria
  (`phpunit.xml` → `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`).
  Cobertura: categories, businesses, accommodations, experiences, bookings,
  tours, restaurants, inns, bakeries, banners y promotions.
  Comando: `cd backend && vendor/bin/phpunit` (39 tests, 125 assertions).
- **Frontend**: Vitest (`@angular/build:unit-test`). Cobertura: formatters,
  modelos (incluidos los del marketplace), interfaces, `ApiClientService`
  (mock de Http/testing) e interceptors.
  Comandos: `cd frontend && npm test` (39 tests) · `npm run lint` · `npm run build`.

---

## 6. Decisiones y deuda técnica

**Hechas:**
- Reservas polimórficas (`bookable_type`/`bookable_id`) desde la primera versión — un solo CRUD para gastronomía, posadas y experiencias.
- Sin autenticación obligatoria en v1: experiencia de “menos pasos posible”.
- Contratos (`Bookable`, `Searchable`) para que v2 (pagos, reseñas, panel admin, IA, multilenguaje) se apoye en interfaces estables.
- Contenido del Home y colecciones 100 % dinámico desde la API; eliminado todo dato turístico estático del frontend (se mantiene solo la copia editorial de marca: stats/valores/cultura raizal, que no tienen fuente admin).
- Favoritos y visitas recientes **offline** en el dispositivo vía Ionic Storage (`FavoritesService`), preparados para sincronizar con cuentas en v2.
- Media propia: fotos reales (CC BY-SA, Wikimedia Commons) con nombres SEO descargadas por `backend/scripts/import-media.ps1` hacia `storage/app/public`, con **fallback SVG** de marca generado por el `MarketplaceSeeder` cuando una descarga falta; el marketplace ya no depende de `picsum.photos`.

**Deuda pendiente (v2):**
- Pagos online (monto gastronómico real por negocio).
- Cuentas de usuario y sincronización de favoritos; panel administrativo para operadores y moderación.
- Reserva de tours/restaurantes/posadas del marketplace (hoy son catálogo + WhatsApp).
- Estado de disponibilidad en tiempo real para posadas del marketplace.
- Multilenguaje (los textos visibles hoy están en español).
- Endpoint `/api/v2/*` cuando cambie el contrato (las rutas v1 se mantienen).
- Algunas imágenes del `DemoDataSeeder` clásico aún apuntan a `picsum.photos` → migrar al repositorio de medios propio.