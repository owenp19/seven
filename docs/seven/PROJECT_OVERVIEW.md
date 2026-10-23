# SEVEN — Visión del proyecto

## Qué es
SEVEN es una aplicación móvil y web de turismo que conecta visitantes con
servicios auténticos de la isla: gastronomía, posadas nativas, tours,
experiencias y promociones. La propuesta es descubrir y reservar sin fricción,
con identidad caribeña y foco en el comercio local.

Tagline: **MÁS QUE UN DESTINO**.

## Alcance de la v1
- Exploración anónima del catálogo: inicio, explorar, tours, restaurantes,
  posadas, promociones, noticias y detalle de cada recurso.
- Reserva anónima con código de reserva y consulta posterior.
- Asistente turístico (SEVEN AI) que recomienda solo catálogo aprobado.
- Favoritos, historial y catálogo offline en el dispositivo.
- Comentarios por dispositivo con moderación.
- Backoffice administrativo y portal de proveedores (multi-tenant).
- App web responsive + envoltorio Capacitor para Android/iOS.

## Fuera de alcance (v1)
- Cuentas de turista, pagos en línea, reseñas verificadas y sincronización de
  favoritos entre dispositivos. Los feature flags `payments`, `reviews` y
  `accounts` están en `false`.
- Recomendación personalizada con historial de usuario.
- Notificaciones push transaccionales.

## Usuarios
| Actor | Necesidad |
|-------|-----------|
| Turista | Descubrir, comparar, guardar, reservar y contactar sin registrarse |
| Proveedor | Gestionar su catálogo, disponibilidad y reservas de su negocio |
| Administrador | Moderar contenido, catálogo, reservas, comentarios y proveedores |

## Principios de producto
1. Anónimo primero: pedir datos solo al reservar.
2. Offline útil: favoritos y catálogo disponibles sin red.
3. Identidad SEVEN: océano, naturaleza, tipografía Oswald/Poppins.
4. Datos reales: el catálogo viene del API, no está hardcodeado en el frontend.
5. Seguridad por defecto: el backoffice y el portal de proveedor son cerrados.

## Documentos relacionados
- `ARCHITECTURE.md` — capas y decisiones estructurales.
- `FEATURES.md` — inventario funcional.
- `CURRENT_STATE.md` — estado real verificado.
- `BACKLOG.md` — trabajo pendiente priorizado.
