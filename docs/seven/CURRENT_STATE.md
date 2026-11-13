# SEVEN — Estado actual

Fecha de auditoría: 2026-10-22. Actualizada: 2026-10-23 (Fase 1.0 — control de
versiones local) y 2026-11-13 (entorno de desarrollo + Playwright).

## Resumen
El repositorio contiene una aplicación funcional en código (API Laravel 11,
frontend Angular 21/Ionic 9 y proyectos Capacitor), con documentación previa y
un conjunto amplio de tests de backend. En la Fase 0.8 **no se modificó lógica,
pantallas, rutas, base de datos ni dependencias**: solo se aplicó formato de
Pint, se añadió la propuesta de `.gitignore` y se actualizó la documentación.
En la Fase 1.0 se creó el baseline local (`main`, commit
`chore: baseline estable de SEVEN antes del rediseño UI UX`) y en la Fase 1.1 se
publicó en `https://github.com/owenp19/seven.git` (`origin/main`).

## Verificado
- Monorepo con `backend/`, `frontend/`, `docs/` y scripts raíz en `package.json`.
- Backend Laravel 11 con rutas v1, admin/provider, reservas, comentarios,
  assistant, noticias y health.
- 37 migraciones que crean las tablas del dominio y de infraestructura.
- Frontend Angular 21 standalone, Ionic 9, Capacitor 8, build a `www`.
- 20 skills externas instaladas en `.agents/skills/` y registradas en
  `skills-lock.json`.
- 9 skills locales creadas en `.opencode/skills/`.
- MCP `context7` configurado una sola vez en `opencode.json` (remoto, OAuth).
- Proyecto Android presente: `com.seven.app`, minSdk 24, compile/target 36.
- JDK 21 (Temurin) disponible.
- `npm run lint`: pasa.
- `npm test -- --watch=false`: 21 archivos, 206 tests pasan.
- `php artisan test`: 276 tests pasan (1064 assertions).
- `npm run build`: pasa y genera `www` (con warnings de presupuesto SCSS y
  `localforage` CommonJS). Se corrigió una llave extra en
  `nav-header.component.scss:92` que rompía la compilación.
- `vendor\bin\pint --test`: **pasa** tras aplicar formato en 10 archivos.
- `.gitignore` raíz creado y validado en un repo temporal: excluye `.env`,
  secretos, dependencias, builds, logs y artefactos nativos; conserva
  `.env.example`, `.env.production.example`, `skills-lock.json`, `.agents/`,
  `.opencode/`, `docs/` y el código fuente.
- Repositorio Git local en `main` con un único commit base, 913 archivos
  versionados (~9.6 MB) y remoto `origin` en GitHub (`main` sigue `origin/main`).
- Escaneo del contenido versionado: sin claves privadas, tokens, `.env` reales,
  keystores, `local.properties` ni dependencias/builds.

## No verificado
- Conexión real a MySQL/MariaDB y estado de datos.
- Build Android/iOS; `ANDROID_HOME`/`ANDROID_SDK_ROOT` no detectados.
- Respuesta real del API en ejecución (backend Laravel `:8000` no levantado en esta sesión;
  Playwright valida el shell de Home sin datos reales de categorías/banners).
- Escaneo de dependencias/pentest.

## Riesgos principales
| Severidad | Riesgo | Evidencia |
|-----------|--------|-----------|
| Alto | Android no está sincronizado con 5 plugins instalados | `capacitor.plugins.json` solo lista haptics/splash/status-bar |
| Alto | Faltan permisos Android de geolocalización/notificaciones | `AndroidManifest.xml` solo declara `INTERNET` |
| Alto | Host de producción es placeholder | `frontend/src/environments/environment.prod.ts` |
| Medio | Sin `ANDROID_HOME`; el build nativo puede fallar | variables de entorno vacías |
| Medio | Logs y artefactos sueltos en el repo | `frontend/*.log`, `chrome_err.log` |
| Medio | Documentación previa con posibles problemas de codificación | acentos visibles como `�` en consola |
| Bajo | Dependencias y lockfiles no auditados en esta fase | no se ejecutó auditoría SCA |

## Lo que funciona (verificado)
- Backend: 276 tests pasan, incluidos auth, reservas, comentarios, assistant,
  aislamiento de tenant, seguridad y regresión.
- Frontend: 206 tests pasan y el lint pasa.
- Playwright Test 1.65.0 instalado localmente (`@playwright/test` devDependency);
  Chromium 158.0.8127.18 ejecutable; `playwright.config.ts` y `e2e/welcome-home.spec.ts`
  creados; **17/17 pruebas pasadas** (Welcome y Home en 390/430/768/1024/1366/1440/1920
  + header desktop + footer tablet + logo sin rotura); 14 capturas y reporte HTML
  generados en `frontend/e2e/`.
- Catálogo público, reservas anónimas, comentarios, noticias, banners,
  promociones, assistant y health están implementados en rutas/controladores.
- Backoffice y portal de proveedor tienen autenticación, roles, permisos,
  aislamiento por tenant y auditoría.
- El frontend tiene todas las pantallas principales y servicios de soporte.

## Formato resuelto (Fase 0.8)
- `vendor\bin\pint` aplicó solo reglas de estilo en 10 archivos (alineación de
  PHPDoc, orden de imports, paréntesis de `new`, espacios de operadores,
  posición de llaves y tipos FQCN). No cambió lógica, nombres ni APIs.
- `vendor\bin\pint --test` vuelve a pasar.
- Archivos afectados: `app/Contracts/Ai/AiProviderInterface.php`,
  `app/Contracts/Ai/AssistantTool.php`,
  `app/Http/Middleware/EnsureUserHasPermission.php`,
  `app/Providers/AppServiceProvider.php`,
  `app/Services/Ai/AssistantService.php`,
  `app/Services/Ai/PackageRecommendationService.php`,
  `app/Services/Catalog/CatalogSearchService.php`,
  `tests/Feature/AssistantApiTest.php`,
  `tests/Feature/CatalogSearchServiceTest.php`,
  `tests/Unit/GeminiProviderTest.php`.

## Correcciones aplicadas
- Se eliminó una llave `}` de más en `nav-header.component.scss:92` que impedía
  compilar el SCSS. Fue una corrección de sintaxis, sin cambio de diseño.
- Se aplicó Laravel Pint (solo formato) en los 10 archivos anteriores.

## Cambios de esta sesión
- Fase 1.0: `git init -b main` y commit base
  `chore: baseline estable de SEVEN antes del rediseño UI UX`.
- Fase 1.1: remoto `origin` y push de `main` a GitHub (upstream configurado).
- `.gitignore` raíz activo (ya no es propuesta) y verificado por patrones.
- Formato Pint en 10 archivos de `backend/`.
- `docs/seven/*` actualizados (estado, auditoría, log, backlog y handoff).
- Fase 0: `opencode.json` con Context7, 9 skills locales, 16 documentos,
  `AGENTS.md`, `skills-lock.json` y `.agents/skills/`.

## Rediseño UI/UX (en curso, sin commit aún)
Trabajo local sobre el baseline `45fda1a` (worktree modificado, no publicado):
- Hecho: identidad preservada; Welcome, Home (intro, rails, banners, hero),
  header con nav centrada, footer premium en Home y detalles de tour/posada/
  restaurante/business con ajustes tablet/escritorio.
- Fase 2.0 (2026-10-25): Reservas (`bookings`) con header/página centrados,
  grid de 2 columnas y hero de 2 columnas en tablet/escritorio; móvil intacto.
- Punto de recuperación Fase 2.0: stash dangling `d39a8be`.
- Pendiente siguiente: FASE 2.1 `booking-form` (nueva reserva) tablet/escritorio;
  después `saved` y `news-detail`. Falta validación visual real por breakpoints.

## Siguiente paso recomendado
1. ~~Inicializar Git y crear el commit base~~ (hecho en Fase 1.0).
2. ~~Configurar entorno de desarrollo + Playwright~~ (hecho 2026-11-13: 17/17 pruebas
   pasadas en Welcome y Home; 7 viewports; capturas y reporte generados).
3. FASE 2.1: adaptar `booking-form` a tablet/escritorio (una fase por sesión);
   validar visualmente con Playwright tras cada cambio.
4. Después: `saved` y `news-detail`. Pantallas listas para rediseño PC/tablet:
   **Welcome y Home** (pruebas visuales operativas).
5. Sincronizar Capacitor y añadir permisos Android cuando se autorice.
6. Configurar el host real de producción antes de cualquier release.
7. Ejecutar auditoría SCA antes de publicar.
8. Mantener `origin/main` actualizado tras cada cambio aprobado.
