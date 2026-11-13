# SEVEN — Registro de sesiones

## 2026-10-23 — Fase 1.1: publicación del baseline en GitHub
- **Objetivo**: subir el repositorio a `https://github.com/owenp19/seven.git`
  (pedido explícito del usuario).
- **Cambios**:
  - `gh auth login` por OAuth web y `gh auth setup-git`; token guardado en el
    keyring del sistema.
  - Remoto `origin` (HTTPS) y `git push -u origin main` (upstream configurado).
- **Verificación ejecutada**:
  - `git ls-remote origin refs/heads/main` → `c25d1e3…`, idéntico a `HEAD`.
  - `git status -sb` → `## main...origin/main`, sin diferencias.
- **Pendiente**: mantener `origin/main` actualizado; resto igual que Fase 1.0.
- **Riesgos**: ver `CURRENT_STATE.md` y `AUDIT_REPORT.md`.

## 2026-10-23 — Fase 1.0: baseline local de control de versiones
- **Objetivo**: dejar un punto de recuperación seguro antes del rediseño UI/UX,
  sin remoto ni publicación (instrucción explícita del usuario).
- **Cambios**:
  - `git init -b main` en la raíz del monorepo.
  - Commit base `chore: baseline estable de SEVEN antes del rediseño UI UX` con
    913 archivos versionados (~9.6 MB).
  - Remoto `origin` eliminado: el repo queda solo local.
  - `.gitignore` raíz activo; documentación actualizada (AGENTS, estado,
    auditoría, backlog, seguridad, decisiones y handoff).
- **Verificación ejecutada**:
  - `git check-ignore` sobre 20 rutas sensibles → `.env`, `.env.production`,
    `auth.json`, `vendor/`, `node_modules/`, `www/`, `dist/`, `local.properties`,
    keystores, `google-services.json`, logs y cachés ignorados.
  - `git ls-files` filtrado → sin `.env` reales, claves, keystores, APK/AAB ni
    dependencias; solo `.env.example` y `.env.production.example`.
  - `git grep` de claves privadas, tokens conocidos (AWS, Google, GitHub,
    Stripe, Slack) y asignaciones `api_key/secret/token/password` → sin
    hallazgos.
  - Revisión de `.env.example` y `.env.production.example`: solo placeholders.
  - Sin archivos versionados > 5 MB.
  - `git remote -v` → vacío.
- **No ejecutado**:
  - Remoto/push a GitHub.
  - Conexión a MySQL/MariaDB, `cap sync`, Gradle/APK, despliegues.
  - Escaneo SCA/pentest.
- **Pendiente**:
  - SDK Android, sincronización Capacitor, permisos móviles y host de producción.
- **Riesgos**: ver `CURRENT_STATE.md` y `AUDIT_REPORT.md`.

## 2026-10-23 — Fase 0.9: inicialización de Git (intento de publicación)
- **Objetivo**: inicializar Git y publicar en `https://github.com/owenp19/seven.git`
  (pedido explícito del usuario en esa sesión).
- **Cambios**:
  - `git init -b main` en la raíz; 913 archivos versionados con el `.gitignore`.
  - `.gitignore`: comentario de cabecera actualizado (ya no es "propuesta").
- **Verificación ejecutada**:
  - `git check-ignore backend/.env` → ignorado.
  - Revisión de staged: sin `.env`, `auth.json`, `node_modules/`, `vendor/`,
    `www/`, APK/AAB ni archivos > 5 MB.
- **Resultado**: **no se publicó**. El push quedó bloqueado por falta de
  credenciales de GitHub y se canceló; el remoto se eliminó en la Fase 1.0.
- **Pendiente**: remoto y publicación solo si el usuario lo autoriza de nuevo.
- **Riesgos**: ver `CURRENT_STATE.md` y `AUDIT_REPORT.md`.

## 2026-10-23 — Fase 0.8: estabilización y protección del proyecto
- **Objetivo**: cerrar formato de Laravel Pint, preparar un `.gitignore` seguro
  y reverificar toda la calidad sin cambiar funcionalidad.
- **Cambios**:
  - `vendor\bin\pint` aplicado en 10 archivos (solo formato).
  - `.gitignore` raíz creado (propuesta; sin `git init` ni commit).
  - Documentación actualizada (estado, auditoría, log, backlog, handoff).
- **Verificación ejecutada**:
  - `vendor\bin\pint --test` → pasa.
  - `npm run lint` → pasa.
  - `npm test -- --watch=false` → 206 tests pasan (21 archivos).
  - `php artisan test` → 276 tests pasan (1064 assertions).
  - `npm run build` → pasa, genera `www` (307 s; warnings de presupuesto SCSS
    y `localforage` CommonJS).
  - `.gitignore` probado en repo temporal: `.env`, `auth.json`, `vendor/`,
    `node_modules/`, `www/`, logs y builds ignorados; `.env.example`,
    `.env.production.example`, `.agents/`, `.opencode/`, `docs/` y
    `skills-lock.json` versionables.
  - Búsqueda de `.git` en raíz, subcarpetas y padres → no existe repositorio.
- **No ejecutado**:
  - `git init`, commit, remoto/push.
  - Conexión a MySQL/MariaDB, `cap sync`, Gradle/APK, despliegues.
  - Escaneo SCA/pentest.
- **Pendiente**:
  - Autorización para inicializar Git.
  - SDK Android, sincronización Capacitor, permisos móviles y host de producción.
- **Riesgos**: ver `CURRENT_STATE.md` y `AUDIT_REPORT.md`.

## 2026-10-22 — Fase 0: auditoría y preparación del entorno
- **Objetivo**: auditar el monorepo, instalar skills verificadas, crear skills
  locales y memoria persistente, diagnosticar Android, dejar handoff.
- **Cambios**:
  - `opencode.json` con MCP remoto `context7` (sin claves).
  - `.opencode/skills/seven-*/SKILL.md` (9 skills locales).
  - `docs/seven/*` (16 documentos).
  - `AGENTS.md` en la raíz.
  - Instalación previa de 20 skills externas en `.agents/skills/` y
    `skills-lock.json`.
- **Verificación ejecutada**:
  - `npx skills list` → 20 skills visibles para OpenCode.
  - Lectura de `package.json`, `angular.json`, `capacitor.config.ts`,
    `AndroidManifest.xml`, `variables.gradle`, `build.gradle`, rutas y migraciones.
  - `java -version` → Temurin 21.
  - `opencode.json` validado como JSON y sin duplicar `context7`.
  - `npm run lint` → pasa.
  - `npm test -- --watch=false` → 206 tests pasan.
  - `php artisan test` → 276 tests pasan (1064 assertions).
  - `npm run build` → fallaba por una llave SCSS sobrante en
    `nav-header.component.scss:92`; tras corregirla, pasa y genera `www`.
  - `vendor\bin\pint --test` → **falla** en 10 archivos.
- **No ejecutado**:
  - Conexión a MySQL/MariaDB.
  - `cap sync`, Gradle, APK.
  - Escaneo SCA/pentest.
- **Pendiente**:
  - Git, SDK Android, sincronización Capacitor, permisos móviles y host de
    producción.
- **Riesgos**: ver `CURRENT_STATE.md` y `AUDIT_REPORT.md`.

## Plantilla para próximas sesiones
```markdown
## YYYY-MM-DD — Título
- Objetivo:
- Cambios:
- Verificación ejecutada:
- No ejecutado:
- Pendiente:
- Riesgos:
```

## 2026-10-23 � Fase 1: auditor�a visual multiplataforma + preservaci�n identidad
- **Objetivo**: iniciar Fase 1 (m�vil/refino + tablet + escritorio) preservando identidad visual.
- **Cambios**:
  - AGENTS.md: a�adida regla 11 (identidad visual obligatoria).
  - DESIGN_SYSTEM.md: a�adida secci�n de identidad obligatoria.
  - .opencode/skills/seven-design-system/SKILL.md: a�adida regla 11.
  - DECISIONS.md: registrada decisi�n de preservaci�n de identidad.
- **Verificaci�n ejecutada**:
  - Lectura de AGENTS, CURRENT_STATE, DESIGN_SYSTEM, DECISIONS, SESSION_LOG.
  - Lectura de variables.scss/themes.scss/typography.scss.
  - Inspecci�n de app shell (app.component, routes, tabs-layout, nav-header, bottom-nav).
- **No ejecutado**:
  - Cambios visuales a�n no aplicados.
- **Pendiente**:
  - Auditor�a visual detallada por �rea (home, explore, detail pages, admin/provider).
  - Mejoras puntuales m�vil + layouts tablet/escritorio con tokens existentes.
- **Riesgos**: no romper m�vil; respetar Oswald/Poppins y paleta actual.

## 2026-10-23 � Fase 1: mejoras responsive tablet/escritorio (m�vil preservado)
- **Objetivo**: aplicar mejoras adaptativas sin redise�ar m�vil desde cero, respetando identidad.
- **Cambios**:
  - docs/seven/RESPONSIVE_DESIGN_STRATEGY.md creado.
  - frontend/src/app/features/home/home.page.scss: rails pasan a grid en tablet/escritorio (2/3/4 columnas), cards adaptadas.
  - frontend/src/app/features/explore/explore.page.scss: resultados centrados con max-width y grid adaptativo.
  - Identidad registrada en AGENTS/DESIGN_SYSTEM/skill seven-design-system/DECISIONS.
- **Verificaci�n ejecutada**:
  - Inspecci�n de identidad (variables.scss/themes.scss/typography.scss).
  - Inspecci�n de shell/layouts/nav-header/bottom-nav.
- **No ejecutado**:
  - Tests/build tras cambios (pendiente verificaci�n).
- **Pendiente**:
  - Ampliar a otras p�ginas (detail pages, bookings) con mismo enfoque.
  - Validar visual con Playwright si disponible.
- **Riesgos**: verificar que m�vil no se rompe en 320-430px.
## 2026-10-24 - FASE 1.2: Welcome + Home (tablet/PC) + centrado
- Objetivo: transformar Welcome/Home para PC/tablet manteniendo móvil. Preservar colores, Oswald/Poppins, logotipo, temas.
- Cambios:
  - frontend/src/app/features/welcome/welcome.page.scss: max-width centrado + márgenes adaptativos tablet/desktop; brand/content con espaciados escalados.
  - frontend/src/app/features/home/home.page.scss: intro/banners/blocks centrados con --seven-content-max-width; grids tablet/desktop con gaps mayores; padding-bottom ajustado.
- Verificación: lint OK; tests 21 archivos/206 OK; build iniciado (www existente). Sin cambios a rutas/Laravel/DB.
- Pendiente: validación visual 390/768/1024/1440/1920; revisar transición Welcome->Home si aplica.
## 2026-10-24 - FASE 1.2A: Header full-width + nav horizontal PC + footer + hide bottom-nav PC/tablet
- Objetivo: layout profesional PC/tablet sin afectar móvil/Welcome.
- Cambios:
  - nav-header: header full-width con inner max-width 1440px; nav horizontal PC (sin hamburguesa), tablet ajustado.
  - bottom-nav: oculto en >=1024px.
  - app-footer: nuevo componente (solo tablet/PC), añadido a Home.
- Verificación: lint OK; tests 206 OK. Build en ejecución; www existente.
- Pendiente: verificación visual 390/768/1024/1440/1920.
## 2026-10-24 - FASE 1.2B: Home banner/hero + centrado
- Objetivo: mejorar banner hero para PC/tablet sin tocar móvil/welcome.
- Cambios:
  - tourist-news-carousel: max-width 1440px, alturas mayores en >=1024px, contenido con max-width y padding ampliado, título escalado.
- Verificación: lint OK; tests 206 OK.
- Pendiente: ajustes a sección ofertas si hay agrupación desequilibrada.
## 2026-10-24 - FASE 1.3: Header centrado grid + sin Inicio + footer premium
- Objetivo: header logo centrado con nav alrededor, sin Inicio; footer premium PC/tablet.
- Cambios:
  - nav-header: grid 5 columnas, enlaces Explorador/Reservas/SEVEN AI/Contacto, sin Inicio; menú solo secundario.
  - app-footer-premium: nuevo footer en Home (excluye móvil) con Marca/Explorar/Legal/Información.
- Verificación: lint OK; tests OK. Build en curso.
- Pendiente: validar superposiciones tablet vertical/horizontal y enlaces legales existentes.
## 2026-10-24 - FASE 1.4: Header centrado con grid + Welcome premium PC/tablet
- Objetivo: header logo centrado sin Inicio; Welcome cinematográfico PC/tablet manteniendo móvil.
- Cambios:
  - nav-header: grid centrado, enlaces Explorador/Reservas/SEVEN AI/Contacto, sin Inicio.
  - welcome: grid editorial PC/tablet, logo/CTA escalados, tablet vertical/horizontal adaptado, móvil preservado.
- Verificación: lint OK. Tests/build en ejecución.
- Pendiente: validación visual 390/768/1024/1440/1920.

## 2026-10-25 - FASE 2.0: Reservas (bookings) tablet/escritorio
- **Objetivo**: adaptar "Mis reservas" a tablet/PC con layout profesional,
  preservando móvil, paleta, Oswald/Poppins, logotipo y temas.
- **Punto de recuperación**: `git stash create "pre-fase reservas tablet desktop"`
  → `d39a8be` (dangling, worktree intacto; HEAD `45fda1a`).
- **Cambios**:
  - `bookings.page.scss`: header y página centrados con `--seven-reading-width`
    (móvil) y `--seven-content-max-width` (>=1024px); listas en grid de 2
    columnas desde 1024px; hero y títulos de sección a ancho completo; filtro
    Activas/Historial acotado; estados vacío/error centrados; targets de 44px.
  - `booking-card.component.scss`: la reserva destacada pasa a layout de 2
    columnas (datos + código/acciones) desde 768px; tarjetas de la lista se
    igualan en altura desde 1024px. Sin cambios de lógica, datos ni rutas.
- **Verificación ejecutada**:
  - `npm run lint` → pasa.
  - `npm run build` → pasa (warning conocido de presupuesto SCSS de
    `restaurant-detail` y `localforage` CommonJS).
  - `npx ng test --watch=false --runner-config=vitest.temp.config.ts` → 21
    archivos, 206 tests pasan. El pool `forks` por defecto agotó timeout al
    arrancar workers en esta máquina; con `pool: 'threads'` (config temporal,
    eliminada) pasa completo.
- **No verificado**: validación visual en 390/768/1024/1440/1920; tema
  ocean/sun/coral en navegador real.
- **Pendiente / siguiente paso**: FASE 2.1 — `booking-form` (nueva reserva)
  para tablet/escritorio; después `saved` y `news-detail`.

## 2026-11-13 — Auditoría maestro full stack + Playwright (entorno de desarrollo)
- **Objetivo**: preparar entorno profesional para rediseño UI/UX de escritorio y tableta (móvil preservado), instalar Playwright, verificar skills y ejecutar pruebas visuales de humo. Sin rediseño masivo aún.
- **Cambios**:
  - `frontend/package.json`: añadido `@playwright/test` ^1.65.0 (devDependency) y scripts `e2e`, `e2e:headed`, `e2e:report`, `e2e:ci`.
  - `frontend/playwright.config.ts`: creado (7 viewports 390/430/768/1024/1366/1440/1920, reporter list+html, baseURL localhost:4200, webServer condicionado).
  - `frontend/e2e/welcome-home.spec.ts`: creado (17 pruebas: Welcome + Home en 7 viewports + header desktop + footer tablet + logo sin rotura).
  - `frontend/.gitignore`: añadidos `/e2e/results`, `/e2e/report`, `/e2e/screenshots`, `playwright-report`, `test-results`.
  - `docs/seven/DEVELOPMENT_ENV_AUDIT.md`: creado (informe completo de auditoría).
  - `docs/seven/CURRENT_STATE.md` y `docs/seven/SESSION_LOG.md`: actualizados (esta sesión).
- **Verificación ejecutada**:
  - Node v26.13.0, npm 11.19.1.
  - `npx playwright --version` → 1.65.0.
  - Chromium 158.0.8127.18 (v1254) instalado y ejecutable en `LOCALAPPDATA\ms-playwright`.
  - Servidor Angular verificado en `http://localhost:4200` (HTML "SEVEN · Más que un destino").
  - `npx playwright test` → **17/17 pasadas** (Welcome y Home en 390/430/768/1024/1366/1440/1920 + header/footer/logo).
  - 14 capturas generadas en `frontend/e2e/screenshots/`; reporte HTML en `frontend/playwright-report/`.
  - Skills verificadas: 9 locales (frontmatter válido), 20 externas, 23 globales; `seven-design-research` no existe (registrada como falta).
- **No ejecutado**:
  - Backend Laravel / MySQL (API :8000 no disponible; Home muestra skeletons; categorías/banners no validadas con datos reales).
  - `cap sync`, build Android/iOS, despliegues, commits.
  - Auditoría SCA / pentest.
- **Pendiente**: levantar backend para capturas con datos reales; FASE 2.1 `booking-form`; auditoría SCA; host de producción real.
- **Riesgos**: ver `docs/seven/DEVELOPMENT_ENV_AUDIT.md` (sección riesgos pendientes).
