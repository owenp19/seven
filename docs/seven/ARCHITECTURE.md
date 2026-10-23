# SEVEN — Arquitectura

> Documento de continuidad. La referencia extensa del repo está en
> `docs/ARCHITECTURE.md`; aquí se resume lo que un agente necesita para trabajar.

## Vista general
```
seven-app/
  backend/    API REST Laravel 11 (PHP 8.2, MySQL/MariaDB)
  frontend/   Angular 21 + Ionic 9 + Capacitor 8 (web y móvil)
  docs/       Documentación técnica
  .opencode/  Skills locales de SEVEN
  .agents/    Skills externas instaladas por el CLI
```

## Backend
- Entrada: `backend/routes/api.php` monta `routes/api/v1/api.php` bajo
  `config('api.prefix')` (`v1`) con `api.version` y `throttle:api`.
- Capas: Controller → FormRequest → Service → Repository → Model; salida por
  API Resources y envelope uniforme.
- Dominios: catálogo clásico (`businesses`, `accommodations`, `experiences`),
  marketplace (`tours`, `restaurants`, `inns`, `bakeries`, `promotions`,
  `news`), reservas, comentarios, media, assistant, admin y provider.
- Autenticación: Sanctum con abilities `admin` y `provider`; roles y permisos
  propios; middleware de tenant `provider.scope`.
- Asistente: orquestador server-side que solo lee catálogo aprobado; la clave
  del proveedor vive en el backend.

## Frontend
- Bootstrap standalone (`app.config.ts`) con router, Ionic, HTTP + interceptores
  y Storage.
- Rutas lazy por feature; tab shell con Home, Explorar, Reservas, Asistente,
  Guardados y Contacto; detalles fuera del shell.
- Estado con signals; servicios de core para API, caché, offline, red, tema,
  idioma, hápticos y datos nativos.
- Diseño con tokens CSS `--seven-*` y temas `ocean`/`sun`/`coral`.
- Capacitor envuelve el build `www`; proyecto Android/iOS ya creado.

## Flujo de una petición
1. Página llama a un servicio de `core/services` o `services`.
2. `api-client.service.ts` compone `environment.apiUrl` + `apiVersion`.
3. Interceptores añaden headers, loader y normalizan errores.
4. Laravel valida, autoriza, delega en servicio/repositorio y responde envelope.
5. El frontend mapea a modelos y actualiza signals; caché/offline si aplica.

## Flujo de reserva
1. Detalle → `booking/new` con recurso, fecha y huéspedes.
2. `POST /api/v1/bookings` crea/reutiliza `customers`, genera `booking_code` y
   calcula total.
3. El token de acceso de la reserva se guarda en el dispositivo (Preferences +
   localStorage) para consultar/editar sin cuenta.
4. `bookings` usa `bookable_type/id` para apuntar a Business, Accommodation o
   Experience.

## Límites y decisiones
- No hay capa de autenticación de turista en v1.
- El marketplace es de solo lectura en la API pública; se administra por
  `/admin/*` o `/provider/*`.
- La API está versionada por prefijo; una v2 se añade sin tocar v1.
- El frontend no guarda secretos ni claves de IA.
