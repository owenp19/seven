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
