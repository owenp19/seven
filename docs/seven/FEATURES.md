# SEVEN — Funcionalidades

> Estado basado en inspección de código. No se ejecutó la app; "implementado"
> significa que existe la ruta/controlador/pantalla, no que esté probado en vivo.

## Turista (app)
| Función | Ruta / archivo | Estado |
|---------|----------------|--------|
| Bienvenida | `/welcome` → `features/welcome` | Implementado |
| Home | `/home` → `features/home` | Implementado |
| Explorar | `/explore` → `features/explore` | Implementado |
| Tours (colección) | `/tours` → `features/tours` | Implementado |
| Detalle de tour | `/tour/:slug` → `features/tour-detail` | Implementado |
| Restaurantes | `/restaurants` → `features/restaurants` | Implementado |
| Detalle de restaurante | `/restaurant/:slug` → `features/restaurant-detail` | Implementado |
| Posadas | `/inns` → `features/inns` | Implementado |
| Detalle de posada | `/inn/:slug` → `features/inn-detail` | Implementado |
| Promociones | `/promotions` → `features/promotions` | Implementado |
| Reservas | `/bookings` → `features/bookings` | Implementado |
| Formulario de reserva | `/booking/new` → `features/booking-form` | Implementado |
| Asistente SEVEN | `/assistant` → `features/assistant` | Implementado |
| Guardados | `/saved` → `features/saved` | Implementado |
| Contacto | `/contact` → `features/contact` | Implementado |
| Noticias | `/noticias` y `/noticias/:slug` | Implementado |
| Configuración | `/configuracion` | Implementado |
| Acerca de | `/acerca-de` | Implementado |
| Privacidad | `/privacidad` | Implementado |
| Detalle de negocio clásico | `/business/:id` | Implementado |

## Capacidades transversales
- **Offline**: favoritos, historial, caché de catálogo y descarga selectiva.
- **Idioma**: español/inglés/portugués en `translation.service.ts`.
- **Tema**: azul, amarillo, coral; persistido en el dispositivo.
- **Tamaño de texto**: ajuste de accesibilidad.
- **Hápticos**: feedback en acciones táctiles.
- **Compartir**: share sheet nativo con fallback web.
- **Red**: detección de conectividad y aviso offline.
- **Privacidad**: pantalla con detalle de datos y canales de contacto.
- **Comentarios**: por dispositivo, con moderación.
- **Reserva anónima**: token local para consultar/editar la reserva.

## Backoffice (`/api/v1/admin/*`)
- Login/logout/me con Sanctum y tokens personales.
- CRUD de proveedores, categorías, banners, promociones, media, noticias,
  businesses, tours, inns, restaurants, bakeries, experiences, accommodations.
- Reservas: listar, ver y cambiar estado.
- Comentarios: listar, moderar y eliminar.
- Auditoría de operaciones sensibles en `audit_logs`.
- Roles: `SUPER_ADMIN` (bypass) y `ADMIN_SEVEN`.
- Permisos: `providers.manage`, `catalog.manage`, `content.manage`,
  `bookings.manage`, `comments.moderate`.

## Portal de proveedor (`/api/v1/provider/*`)
- Login/logout/me y tokens con ability `provider`.
- CRUD de su catálogo: businesses, accommodations, experiences, tours,
  restaurants, inns, bakeries, promotions, news, media.
- Reservas de su tenant: listar, ver y cambiar estado.
- Aislamiento estricto: recurso ajeno → 404.
- Recursos platform-only (categorías, banners, comentarios) no accesibles.

## Asistente SEVEN
- Endpoint público `POST /api/v1/assistant/messages`.
- Rate limit por dispositivo/IP y por ráfaga.
- Solo lee catálogo aprobado mediante herramientas internas.
- Persistencia de conversación con TTL y límite de mensajes.
- Proveedor configurable: `gemini` o `fake` (tests).
- La API key vive solo en el backend.

## Feature flags
```ts
features: {
  explore: true,
  bookings: true,
  payments: false,
  reviews: false,
  accounts: false,
}
```
Los flags permiten ocultar rutas con `feature-flag.guard.ts`.
