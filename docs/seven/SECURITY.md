# SEVEN — Seguridad

> Revisión basada en configuración y código. No se ejecutó un pentest ni un
> escaneo de dependencias en esta fase.

## Superficies
| Superficie | Riesgo principal |
|------------|------------------|
| API pública | Scraping, spam, fuerza bruta, abuso del assistant |
| Reservas anónimas | Enumeración de códigos, acceso a datos de terceros |
| Comentarios | Spam, XSS, suplantación |
| Backoffice | Robo de token, escalada de rol |
| Portal proveedor | Acceso cruzado entre tenants |
| Asistente IA | Prompt injection, costo, fuga de claves |
| App móvil | WebView, almacenamiento local, permisos, deep links |

## Controles implementados
- Sanctum; tokens personales hasheados; abilities `admin` y `provider`.
- `/admin/*`: `admin.auth` + `ability:admin` + rol + permiso + throttle.
- `/provider/*`: `provider.auth` + rol + `provider.scope` + throttle.
- Login con `throttle:admin-login` (6/min por email + IP).
- Rate limits por tier; comentarios y assistant con límites propios.
- Token legacy deshabilitado por defecto; solo local/testing.
- Fail-secure de producción (`SECURITY_ENFORCE_PRODUCTION=true`).
- Swagger deshabilitado por defecto.
- CORS configurable; no se encontró comodín en la configuración revisada.
- Validación con FormRequests; errores sin stack en producción.
- Auditoría en `audit_logs`.
- API key de IA solo en backend.
- `environment.prod.ts` bloquea el arranque con host placeholder.

## Riesgos y pendientes
| Severidad | Hallazgo | Acción |
|-----------|----------|--------|
| Medio | El código está publicado en GitHub (`origin`) | Revisar visibilidad, accesos y 2FA |
| Alto | Permisos Android incompletos para geolocalización/notificaciones | Añadir y probar runtime permissions |
| Medio | Sin escaneo SCA ejecutado | Ejecutar auditoría de dependencias autorizada |
| Medio | Sin pruebas de carga/abuso del assistant | Definir presupuesto y límites |
| Medio | Datos personales en reservas anónimas | Revisar retención y acceso por token |
| Bajo | Logs/artefactos pueden contener rutas internas | Limpiar antes de publicar |

## Reglas
1. Nunca commitear `.env`, claves, tokens ni `google-services.json` con secretos.
2. No imprimir valores de entorno; solo nombres de variables.
3. No exponer `APP_DEBUG=true` en producción.
4. No habilitar `ADMIN_LEGACY_TOKEN_ENABLED` en producción.
5. No usar `*` en CORS de producción.
6. Validar MIME, tamaño y nombre en subidas; servir con headers seguros.
7. Sanear comentarios y limitar longitud/velocidad.
8. El assistant no ejecuta SQL libre ni recibe credenciales.
9. Toda consulta de proveedor pasa por `provider.scope`.
10. Revisar permisos y abilities al añadir una superficie nueva.

## Checklist antes de release
- [ ] `APP_DEBUG=false`, `APP_ENV=production`.
- [ ] `SECURITY_ENFORCE_PRODUCTION=true`.
- [ ] `ADMIN_API_TOKEN` vacío y legacy deshabilitado.
- [ ] CORS solo con orígenes reales.
- [ ] HTTPS obligatorio; sin cleartext en Android.
- [ ] Host de API real en `environment.prod.ts`.
- [ ] Tokens con expiración razonable (`SANCTUM_TOKEN_EXPIRATION`).
- [ ] Backups y plan de restauración de BD.
- [ ] Dependencias auditadas.
- [ ] Permisos móviles mínimos y justificados.

## Referencias
- `backend/config/cors.php`, `sanctum.php`, `api.php`.
- `backend/routes/api/v1/api.php`.
- `backend/app/Http/Middleware/`.
- `frontend/src/app/core/services/data-privacy.service.ts`.
