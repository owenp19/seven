# Handoff — SEVEN para la siguiente IA

## Contexto
SEVEN es una app de turismo (Angular 21 + Ionic 9 + Capacitor 8, Laravel 11,
MySQL/MariaDB). La v1 es anónima: catálogo, reservas, assistant, offline,
backoffice y portal de proveedor. El repo está en
`C:\Users\Owen\Documents\Sueños\Desarrollo Software Apps\seven-app`.

La Fase 0 fue auditoría y preparación. La **Fase 0.8** cerró el formato de
Laravel Pint, preparó el `.gitignore` y reverificó la calidad. La **Fase 1.0**
creó el repositorio Git local (`main`) con el commit base
`chore: baseline estable de SEVEN antes del rediseño UI UX` y la **Fase 1.1** lo
publicó en `https://github.com/owenp19/seven.git` (`origin`). No
se modificó lógica, pantallas, rutas, base de datos ni dependencias. No se
generó APK.

## Lee primero
1. `docs/seven/CURRENT_STATE.md`
2. `docs/seven/AUDIT_REPORT.md`
3. `docs/seven/ARCHITECTURE.md`
4. `docs/seven/SECURITY.md`
5. `docs/seven/MOBILE_READINESS.md`
6. El área a tocar: `API_CONTRACTS.md`, `DATABASE_MAP.md`, `DESIGN_SYSTEM.md`,
   `FEATURES.md`.

`AGENTS.md` contiene las reglas operativas. `.opencode/skills/` contiene 9 skills
locales; úsalas según la tarea.

## Estado verificado
- Backend con rutas v1, admin/provider, reservas, comentarios, assistant,
  noticias, health y 40 tablas.
- Frontend con todas las pantallas principales, rutas lazy, interceptores,
  signals y servicios de offline/red/tema/idioma.
- 20 skills externas en `.agents/skills/`; `npx skills list` las muestra para
  OpenCode. Registro en `skills-lock.json` y `docs/seven/SKILLS_REGISTRY.md`.
- Context7 configurado como MCP remoto en `opencode.json`; falta OAuth.
- Android configurado (`com.seven.app`, minSdk 24, compile/target 36), pero
  desincronizado con 5 plugins y sin permisos de geolocalización/notificaciones.

## Verificación ejecutada
- `npm run lint` → pasa.
- `npm test -- --watch=false` → 206 tests pasan.
- `php artisan test` → 276 tests pasan (1064 assertions).
- `npm run build` → pasa y genera `www` (se corrigió una llave SCSS sobrante en
  `nav-header.component.scss:92`).
- `vendor\bin\pint --test` → pasa (tras aplicar formato en 10 archivos).
- `.gitignore` raíz → validado en repo temporal; secretos, dependencias, builds
  y logs excluidos.
- Git → `main` con 1 commit base sincronizado con `origin/main`; escaneo de
  secretos sin hallazgos.

## No verificado (no afirmar que funciona)
- Conexión a MySQL/MariaDB.
- `npx cap sync`, Gradle, build Android/iOS.
- Escaneo de dependencias/pentest.

## Riesgos críticos
1. **Remoto en GitHub.** El código está publicado en `origin`; revisar
   visibilidad, accesos y 2FA de la cuenta.
2. **Android desincronizado.** `capacitor.plugins.json` solo tiene 3 de 8 plugins.
3. **Permisos Android incompletos.** Solo `INTERNET`.
4. **Host de producción placeholder.** La app lanza al arrancar en prod.
5. **Sin `ANDROID_HOME`.** El build nativo puede fallar.

## Reglas para la siguiente IA
- No cambies pantallas, lógica de negocio, rutas ni controladores sin pedido
  explícito.
- No crees migraciones, seeders ni ejecutes `migrate:fresh`.
- No actualices dependencias masivamente.
- No ejecutes `cap sync`, `cap run`, APK ni Android Studio sin aprobación.
- No leas `.env` ni imprimas secretos. Usa `.env.example` y nombres de variables.
- No instales GSD ni otros sistemas que puedan sobrescribir configuración.
- No declares una verificación que no ejecutaste.
- No hagas commit salvo pedido explícito.

## Próximos pasos recomendados
1. ~~Git + commit base~~ (hecho en Fase 1.0 y publicado en Fase 1.1).
2. Configurar `ANDROID_HOME` y `npx cap sync android`.
3. Añadir permisos Android y probar en dispositivo.
4. Configurar host real de producción.
5. Auditar dependencias (SCA).

## Preguntas abiertas
- ¿La visibilidad del repo en GitHub (pública/privada) es la deseada?
- ¿Qué host/API de producción se usará?
- ¿Se instala GSD Core o se mantiene el backlog local?
- ¿Se prioriza release Android o completar verificación web primero?
- ¿Hay presupuesto/límites para el assistant en producción?

## Archivos clave
- `frontend/src/app/routes.ts`, `app.config.ts`, `core/services/`.
- `frontend/src/theme/variables.scss`, `themes.scss`, `typography.scss`.
- `frontend/capacitor.config.ts`, `frontend/android/`.
- `backend/routes/api/v1/api.php`, `backend/app/Http/Controllers/Api/V1/`.
- `backend/config/api.php`, `cors.php`, `sanctum.php`.
- `backend/database/migrations/`.
- `docs/ARCHITECTURE.md`, `docs/API.md`, `README.md`.
- `opencode.json`, `.opencode/skills/`, `.agents/skills/`, `skills-lock.json`.
