# AGENTS.md — SEVEN

## Entrada obligatoria
Antes de trabajar, lee `docs/seven/CURRENT_STATE.md` y la skill que corresponda
en `.opencode/skills/`. La documentación de continuidad vive en `docs/seven/`;
no la dupliques.

## Contexto
Monorepo de turismo:
- `backend/`: Laravel 11 (PHP 8.2), API `/api/v1`, MySQL/MariaDB.
- `frontend/`: Angular 21 + Ionic 9 + Capacitor 8, build a `www`.
- `docs/`: documentación; `docs/seven/`: memoria persistente.
- `.opencode/skills/`: 9 skills locales de SEVEN.
- `.agents/skills/`: 20 skills externas instaladas con `npx skills`.

## Reglas no negociables
1. No rediseñar pantallas ni cambiar lógica, rutas o controladores sin pedido.
2. No crear ni ejecutar migraciones/seeders; no tocar la BD en Fase 0.
3. No actualizar dependencias masivamente ni cambiar lockfiles sin aprobación.
4. No leer `.env` ni exponer secretos, tokens o claves. Usa `.env.example`.
5. No ejecutar `cap sync`, `cap run`, APK ni Android Studio sin aprobación.
6. No instalar GSD ni herramientas que sobrescriban configuración.
7. No hacer commit salvo pedido explícito. El repo Git local existe (`main`,
   baseline en `chore: baseline estable de SEVEN antes del rediseño UI UX`) y su
   remoto es `origin` → `github.com/owenp19/seven`; el `.gitignore` raíz está
   activo. No hacer force-push ni reescribir el historial publicado.
8. No afirmar que algo funciona si no se ejecutó la verificación.
9. Reutilizar componentes/servicios existentes antes de crear nuevos.
10. Documentar cambios de comportamiento en `docs/seven/` y `SESSION_LOG.md`.

## Comandos
```powershell
# Frontend
cd frontend; npm start
cd frontend; npm run build
cd frontend; npm run lint
cd frontend; npm test

# Backend
cd backend; php artisan serve --port=8000
cd backend; php artisan test
cd backend; vendor\bin\pint --test

# Raíz
npm run dev
npm test
```

## Context7
Está configurado en `opencode.json` como MCP remoto. Si necesitas documentación
actualizada de una librería, pide usar `context7`. La primera vez requiere
`opencode mcp auth context7` o completar OAuth desde `/mcps`.

## Dónde está cada cosa
| Tema | Documento |
|------|-----------|
| Visión | `docs/seven/PROJECT_OVERVIEW.md` |
| Arquitectura | `docs/seven/ARCHITECTURE.md` |
| Estado y riesgos | `docs/seven/CURRENT_STATE.md` |
| Stack | `docs/seven/TECH_STACK.md` |
| Features | `docs/seven/FEATURES.md` |
| API | `docs/seven/API_CONTRACTS.md` |
| BD | `docs/seven/DATABASE_MAP.md` |
| Diseño | `docs/seven/DESIGN_SYSTEM.md` |
| Seguridad | `docs/seven/SECURITY.md` |
| Móvil | `docs/seven/MOBILE_READINESS.md` |
| Backlog | `docs/seven/BACKLOG.md` |
| Skills | `docs/seven/SKILLS_REGISTRY.md` |
| Decisiones | `docs/seven/DECISIONS.md` |
| Historial | `docs/seven/SESSION_LOG.md` |
| Auditoría | `docs/seven/AUDIT_REPORT.md` |
| Handoff | `docs/seven/HANDOFF_CHATGPT.md` |

## Skills locales
`seven-memory`, `seven-design-system`, `seven-audit`, `seven-angular`,
`seven-ionic-capacitor`, `seven-laravel`, `seven-database`, `seven-security`,
`seven-quality`.
