---
name: seven-quality
description: Puertas de calidad de SEVEN. Usar al terminar una tarea, antes de un commit o handoff, al añadir tests o al investigar un fallo de build/lint. Define comandos verificables, matriz de pruebas, definición de terminado y cómo reportar resultados sin exagerar lo verificado.
---

# SEVEN Quality

## Comandos canónicos
```powershell
# Frontend
cd frontend
npm run lint
npm run build
npm test

# Backend
cd backend
vendor\bin\pint --test
php artisan test

# Ambos desde la raíz
npm test
```

## Matriz mínima de pruebas
| Área | Qué probar |
|------|------------|
| API pública | 200, filtros, paginación, 404, envelope |
| Reservas | creación, código, cliente, total, estados, permisos por token |
| Admin | login, rol, permiso, token revocado, 401/403/429 |
| Provider | aislamiento por tenant, 404 en recurso ajeno, CRUD permitido |
| Comentarios | validación, rate limit, un comentario por dispositivo, moderación |
| Assistant | catálogo aprobado, límites, errores del proveedor, sin claves |
| Frontend | servicios, guards, interceptores, estados carga/error/offline |
| Móvil | safe areas, teclado, back button, permisos, offline |

## Definición de terminado
- [ ] El cambio resuelve el objetivo y no añade alcance no pedido.
- [ ] `lint` pasa en el área tocada.
- [ ] `build` pasa si se tocó frontend.
- [ ] Tests nuevos/actualizados pasan; si no se pudieron ejecutar, se dice.
- [ ] No hay secretos, logs de depuración ni `console.log` olvidados.
- [ ] No se rompió el contrato de API ni el envelope.
- [ ] Documentación de `docs/seven/` actualizada si cambió el comportamiento.
- [ ] `SESSION_LOG.md` con entrada breve.

## Reglas de reporte
- Distinguir **ejecutado y pasado**, **ejecutado y fallido**, **no ejecutado**.
- Nunca escribir "todo funciona" sin comando y salida.
- Si un test falla por entorno (BD, SDK, red), indicar la causa y qué falta.
- Un warning no es un fallo, pero se reporta si afecta build, seguridad o UX.

## Deuda conocida a vigilar
- No hay repositorio Git en la raíz: no hay red de seguridad para revertir.
- `vendor\bin\pint --test` falla en 10 archivos (solo estilo).
- `npm run build` muestra avisos de presupuesto SCSS y `localforage` CommonJS.
- El proyecto Android no está sincronizado con todos los plugins instalados.
- Faltan permisos Android para geolocalización/notificaciones.
- El entorno no tiene `ANDROID_HOME`; el build nativo no está verificado.
- Hay logs y artefactos sueltos en `frontend/` y raíz; documentar antes de limpiar.
- `environment.prod.ts` usa un host placeholder; la app lanza al arrancar en producción hasta configurar un host real.
