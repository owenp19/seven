---
name: seven-security
description: Seguridad de SEVEN para API, web y móvil. Usar al tocar autenticación, autorización, CORS, subida de archivos, asistente IA, reservas, pagos, datos personales, secretos o configuración de producción. Incluye modelo de amenazas, controles existentes y checklist de revisión.
---

# SEVEN Security

## Modelo de amenazas
- API pública anónima para catálogo y reservas: abuso, scraping, spam de
  comentarios, fuerza bruta y consumo del asistente.
- Backoffice y portal de proveedor: robo de token, escalada de rol, acceso
  cruzado entre tenants.
- Móvil: WebView, enlaces profundos, almacenamiento local, permisos nativos.
- Asistente IA: prompt injection, filtración de claves, costo por mensaje.

## Controles existentes
- Sanctum con tokens personales; abilities `admin` y `provider`.
- Rutas `/admin/*`: `admin.auth`, `ability:admin`, `role:SUPER_ADMIN,ADMIN_SEVEN`,
  permisos por recurso y `throttle:admin`.
- Rutas `/provider/*`: `provider.auth`, `role:PROVEEDOR_TURISTICO`,
  `provider.scope` para aislar por `provider_id`.
- Login con `throttle:admin-login` (6/min por email + IP).
- Rate limits: invitado 120, autenticado 600, admin 60; comentarios 5/hora;
  assistant por dispositivo/IP.
- CORS configurado en `backend/config/cors.php`; revisar orígenes antes de prod.
- `SECURITY_ENFORCE_PRODUCTION=true` aborta si `APP_DEBUG=true` o el token legacy
  está activo en producción.
- Token legacy `ADMIN_API_TOKEN` deshabilitado por defecto; solo local/testing.
- Swagger deshabilitado por defecto (`API_DOCS_ENABLED=false`).
- Validación con FormRequests; envelope de error sin stack trace en producción.
- Contraseñas con bcrypt (12 rounds); tokens Sanctum hasheados en BD.

## Reglas no negociables
1. Ningún secreto en el frontend, en `docs/`, en logs ni en Git. Las claves de IA
   viven solo en el backend (`.env`).
2. No imprimir `.env` ni valores de variables; usar `.env.example`.
3. No añadir `Access-Control-Allow-Origin: *` en producción.
4. No confiar en `install_id`, `provider_id` ni `booking_code` enviados por el
   cliente para autorizar; validar contra la sesión/token.
5. Toda consulta de proveedor debe pasar por el scope de tenant.
6. Subidas: validar MIME real, tamaño (`MEDIA_MAX_KB`), extensión y nombre;
   almacenar fuera del webroot o en disco público controlado.
7. Comentarios: sanear texto, limitar longitud y aplicar rate limit + moderación.
8. Asistente: solo lectura del catálogo aprobado; nunca SQL libre ni credenciales;
   limitar iteraciones, tokens y longitud de entrada.
9. No habilitar el token legacy en producción.
10. No desactivar `SECURITY_ENFORCE_PRODUCTION`.

## Checklist de revisión
- [ ] ¿El endpoint nuevo exige el middleware/rol/permiso correcto?
- [ ] ¿Se valida y autoriza el recurso por ID y por tenant?
- [ ] ¿Hay rate limit donde hay costo o abuso posible?
- [ ] ¿Se filtran datos personales en respuestas y logs?
- [ ] ¿Los errores no revelan rutas, SQL ni versiones?
- [ ] ¿Los tokens se revocan al logout/cambio de contraseña?
- [ ] ¿Los archivos subidos se validan y sirven con headers seguros?
- [ ] ¿La configuración de producción tiene `APP_DEBUG=false`?
- [ ] ¿Se actualizaron tests de seguridad/regresión?

## Comandos de verificación
```powershell
# Buscar patrones de riesgo (solo lectura)
Select-String -Path backend/routes/api/v1/api.php -Pattern "middleware"
Select-String -Path backend/config/*.php -Pattern "origin|debug|token"
Select-String -Path frontend/src -Pattern "apiKey|secret|password" -Recurse
```
No ejecutar escaneos que envíen código a servicios externos sin autorización.
