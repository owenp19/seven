# SEVEN — Auditoría del entorno de desarrollo (Full Stack + Playwright)

Fecha: 2026-11-13
Estado: **ENTORNO PREPARADO** para rediseño UI/UX de escritorio y tableta (móvil preservado).
Baseline Git: `main` (commit `45fda1a`); worktree con cambios de rediseño previos sin publicar.

---

## 1. Memoria y contexto recuperado

- `AGENTS.md` leído: monorepo (backend Laravel 11 + frontend Angular 21/Ionic 9/Capacitor 8), reglas no negociables, identidad visual obligatoria (regla 11).
- `docs/seven/CURRENT_STATE.md` leído: verificado hasta Fase 1.1; frontend 206 tests OK, lint OK, build OK; backend 276 tests OK.
- `docs/seven/DESIGN_SYSTEM.md` leído: paleta, Oswald/Poppins, temas ocean/sun/coral, reglas de implementación.
- `docs/seven/SESSION_LOG.md` leído: historial Fases 0→2.0; punto de recuperación `d39a8be`.
- No se repitieron auditorías ya vigentes (lint, build, tests de unidad).

---

## 2. Auditoría de skills

### Skills locales (`.opencode/skills/<nombre>/SKILL.md`) — 9, todas con frontmatter válido

| Skill | Estado | Compatibilidad |
|-------|--------|----------------|
| seven-memory | Instalada y verificable | Memoria/sesiones |
| seven-design-system | Instalada y verificable | Tokens, paleta, temas, a11y |
| seven-audit | Instalada y verificable | Auditoría evidence-first |
| seven-angular | Instalada y verificable | Angular 21, señales, rutas |
| seven-ionic-capacitor | Instalada y verificable | Ionic 9, Capacitor 8, safe areas |
| seven-laravel | Instalada y verificable | Capas, API, Sanctum |
| seven-database | Instalada y verificable | Esquema, índices, migraciones |
| seven-security | Instalada y verificable | Amenazas, checklist |
| seven-quality | Instalada y verificable | Build, lint, tests, Def. de terminado |

Todas se cargan por la herramienta nativa `skill()`; no se afirmó uso de ninguna no cargada.

### Skills externas (`.agents/skills/`) — 20, instaladas y verificadas

Categorías cubiertas: diseño web (web-design-guidelines, ui-ux-pro-max, design-system, ionic-design), móvil (capacitor-*), seguridad (owasp/secure-agent-playbook: web-security-review, api-security-review, code-review-security, secrets-scan, sca-audit, mobile-code-review, security-guidance).

**Nota de riesgo documentada en SKILLS_REGISTRY.md**: `owasp/secure-agent-playbook` marcó alerta Snyk High Risk en `mobile-code-review`. Se revisa SKILL.md antes de ejecutar comandos de ese skill. No se ejecutó automáticamente.

### Skills globales (`.config\opencode\skills\`) — 23 detectadas

Relevantes para esta sesión: `e2e-testing`, `playwright-professional`, `verification-loop`, `mysql-patterns`, `production-audit`, `security-review`, `security-scan`, `api-design`, `git-workflow`, `tdd-workflow`. No se instalaron duplicados en el proyecto.

### Faltante detectado
- **`seven-design-research`**: mencionada en la misión (sección 7) pero **no existe** ni local ni global. Registrada como necesidad. No se inventó ni se creó sin autorización.

### Incompatibles / no cargadas por diseño
- Ninguna skill instalada se declaró incompatible. No se cargaron todas simultáneamente (regla de token-efficient).

---

## 3. Playwright Test (instalación obligatoria)

| Dato | Valor |
|------|-------|
| Gestor de paquetes | npm (frontend/package.json) |
| Node.js | v26.13.0 |
| npm | 11.19.1 |
| Paquete | `@playwright/test` ^1.65.0 (devDependency) |
| Versión instalada | 1.65.0 |
| Navegador | Chromium 158.0.8127.18 (revisión v1254) en `LOCALAPPDATA\ms-playwright\chromium-1254` |
| Otros navegadores | Firefox y WebKit ya estaban en caché (no se descargaron de nuevo) |
| Instalación global | No (solo local, --save-dev) |
| Playwright MCP | No instalado (verificado que @playwright/test cubre las necesidades) |
| @vitest/browser-playwright | Mantenido (no se eliminó) |

### Archivos creados
- `frontend/playwright.config.ts` — 7 proyectos/viewports (390, 430, 768, 1024, 1366, 1440, 1920), reporter list + html, screenshots solo en fallo, traces en reintento, webServer condicionado a `SEVEN_PLAYWRIGHT_WEBSERVER=1`, baseURL `http://localhost:4200`.
- `frontend/e2e/welcome-home.spec.ts` — 17 pruebas (Welcome + Home en 7 viewports + header desktop + footer tablet + logo sin rotura).
- Scripts npm: `e2e`, `e2e:headed`, `e2e:report`, `e2e:ci`.

### .gitignore actualizado
`frontend/.gitignore` ignora `/e2e/results`, `/e2e/report`, `/e2e/screenshots`, `playwright-report`, `test-results`.

---

## 4. Pruebas visuales automatizadas

### Servidor verificado
- Angular `ng serve` en `http://localhost:4200` (puerto por defecto de angular.json, no se presupuso).
- HTML de respuesta: título "SEVEN · Más que un destino", Oswald/Poppins vía Google Fonts.

### Pruebas ejecutadas y PASADAS (17/17)

| Prueba | Viewports | Resultado |
|--------|-----------|-----------|
| Welcome carga sin errores | 390, 430, 768, 1024, 1366, 1440, 1920 | 7/7 pasadas |
| Home carga sin errores | 390, 430, 768, 1024, 1366, 1440, 1920 | 7/7 pasadas |
| Header y navegación desktop | 1440×900 | Pasada |
| Footer premium tablet | 1024×768 | Pasada |
| Logo sin rotura | 1366×768 | Pasada |

### Comprobaciones por prueba
Layout, desbordamiento horizontal (body.scrollWidth ≤ viewport + 4), header, navegación, footer, contenedores, imágenes (logo naturalWidth > 0), botones/CTA, textos, errores de consola capturados (no se bloquean), funcionalidad esencial de carga.

### Capturas generadas (14)
`frontend/e2e/screenshots/`: welcome-* y home-* para los 7 viewports. Para comparar diseño anterior/nuevo.

### Reporte
`frontend/playwright-report/` (HTML, `open: never`).

### Limitaciones registradas
- Las categorías y banners de Home se poblan desde el API Laravel (`:8000`). El backend **no está levantado** y la BD no está verificada, por lo que en Home aparecen skeletons; no se validaron categorías/banners con datos reales. Prueba de humo del shell superada.
- No se automatizaron reservas, WhatsApp ni cambios de datos (regla de misión).
- Si el servidor Angular no está disponible, las pruebas fallan con `ERR_CONNECTION_REFUSED` y se registran como no ejecutadas.

---

## 5. Auditoría de MCP

| MCP | Estado |
|-----|--------|
| context7 | Configurado en `opencode.json` (remoto, OAuth), **desactivado** (`enabled: false`). Se activa solo cuando se necesite documentación actualizada. |
| Git | No hay servidor MCP de Git; se usa CLI nativa. |
| Pruebas visuales | Cubierto por `@playwright/test` (npm), sin duplicar con MCP. |

No se habilitaron servidores innecesarios. No se duplica capacidad entre MCP y npm.

---

## 6. Reglas permanentes de UI/UX (verificadas en AGENTS.md y DESIGN_SYSTEM.md)

- [x] Conservar paleta actual de SEVEN.
- [x] Conservar Oswald y Poppins.
- [x] Conservar logotipo.
- [x] Conservar temas existentes (ocean/sun/coral).
- [x] No utilizar degradados.
- [x] Evitar componentes genéricos de IA.
- [x] Evitar glassmorphism decorativo.
- [x] No instalar nuevas fuentes o paletas.
- [x] Priorizar experiencia del turista.
- [x] Diseñar versiones específicas para PC y tableta.
- [x] Mantener interfaz móvil aprobada.
- [x] Mejoras móviles solo cuando justificadas.
- [x] Priorizar accesibilidad, rendimiento y claridad.

Están vigentes en `AGENTS.md` (regla 11) y `docs/seven/DESIGN_SYSTEM.md` (regla 11 de identidad inmutable). No hubo cambios de identidad.

---

## 7. Skill de investigación visual (`seven-design-research`)

**No existe**. No se activó porque no está instalada. Registrada como necesidad para futuros rediseños (investigar referencias web reales, buenas prácticas, soluciones originales; sin copiar diseños de terceros).

---

## 8. Buenas prácticas full stack aplicadas

- Angular/Ionic: componentes existentes reutilizados (nav-header, bottom-nav, discovery-card, etc.), TypeScript, accesibilidad (targets ≥44×44, contraste AA).
- Laravel: no se tocó (sin migraciones, sin controladores, sin API).
- MySQL: no se tocó (Fase 0, sin BD).
- Capacitor: no se ejecutó `cap sync` ni builds nativos (requieren autorización).
- Seguridad: no se leyeron `.env` ni secretos; se usó `.env.example`; no se expusieron claves.
- Testing: pruebas de humo antes de considerar cambios de diseño.

Principios SOLID, DRY, KISS, YAGNI respetados (una sola especificación de prueba, sin abstracciones innecesarias).

---

## 9. Optimización de tokens

- `opencode.json`: compaction `auto: true`, `prune: true`, `reserved: 10000`. Conservado.
- Skills cargadas solo las necesarias para esta sesión (diseño, testing, seguridad, memoria).
- Archivos leídos por secciones; documentación reutilizada (no se reanalizó).
- Salidas breves. No se repitieron investigaciones.

---

## 10. Seguridad y protección

- Git revisado antes de editar: working tree con cambios previos de rediseño sin publicar; no se tocó funcionalidad, solo se añadieron archivos de testing y documentación.
- Cambios reversibles (archivos nuevos en `frontend/e2e/`, `frontend/playwright.config.ts`, scripts npm, `.gitignore`).
- No se ejecutaron migraciones, borrados, despliegues, commits automáticos, comandos destructivos ni pruebas contra producción.
- Instalación de `@playwright/test` y Chromium dentro del frontend, autorizada por la misión.

---

## 11. Validación final

| Punto | Estado |
|-------|--------|
| Skills instaladas y reconocidas | 9 locales + 20 externas + 23 globales, verificadas |
| Skills prioritarias cargables | seven-design-system, seven-angular, seven-quality, seven-token-efficient, seven-audit (verificadas) |
| Playwright Test vía npm | 1.65.0, devDependency |
| Chromium ejecutable | 158.0.8127.18 (v1254) |
| Config E2E válida | `playwright.config.ts` sin errores de configuración |
| Prueba de humo Welcome + Home | 17/17 pasadas (servidor Angular :4200) |
| Capturas y reportes | 14 capturas + reporte HTML generados |
| Config OpenCode válida | `opencode.json` (context7 desactivado, compaction activa) |
| AGENTS.md coherente | Reglas de identidad vigentes, sin cambios |
| Memoria persistente | SESSION_LOG.md y CURRENT_STATE.md actualizadas (esta sesión) |
| Sin modificaciones funcionales no autorizadas | Solo archivos de testing/docs; ningún cambio a rutas, controladores, BD |

---

## 12. Riesgos pendientes

1. Backend Laravel y MySQL no verificados en ejecución (API :8000 no disponible; Home muestra skeletons). Necesario para capturas con datos reales de categorías/banners.
2. Android no sincronizado (Capacitor) y `ANDROID_HOME` ausente; build nativo no verificado.
3. `environment.prod.ts` con host de producción placeholder.
4. `seven-design-research` no instalada (necesidad registrada).
5. Auditoría SCA / pentest de dependencias no ejecutada.
6. Escaneo de dependencias/pentest pendiente antes de release.

---

## Próximo paso recomendado

Las pantallas **Welcome y Home** están listas para comenzar el rediseño de PC y tableta (pruebas visuales operativas en 7 viewports). A continuación: `booking-form`, `saved`, `news-detail` (Fase 2.1+), validando con Playwright tras cada cambio. Levantar el backend para capturas con datos reales cuando se autorice.
