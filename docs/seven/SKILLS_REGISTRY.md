# SEVEN — Registro de skills

## Skills locales de SEVEN
Ubicación: `.opencode/skills/<nombre>/SKILL.md`.
Estado: **CREADA LOCALMENTE Y VERIFICADA** (frontmatter `name` y `description`
revisados; contenido específico del repo).

| Skill | Propósito |
|-------|-----------|
| `seven-memory` | Memoria, orden de lectura y registro de sesiones |
| `seven-design-system` | Tokens, tipografía, temas, accesibilidad |
| `seven-audit` | Auditoría evidence-first y severidades |
| `seven-angular` | Angular 21, signals, rutas, HTTP, tests |
| `seven-ionic-capacitor` | Ionic 9, Capacitor 8, safe areas, Android |
| `seven-laravel` | Capas, contrato API, Sanctum, tests |
| `seven-database` | Esquema, relaciones, índices, migraciones |
| `seven-security` | Amenazas, controles y checklist |
| `seven-quality` | Build, lint, tests y definición de terminado |

## Skills externas instaladas
Ubicación: `.agents/skills/` (descubiertas por OpenCode).
Registro de procedencia y hash: `skills-lock.json`.
Estado: **INSTALADA Y VERIFICADA** (aparecen en `npx skills list` con
`Agents: GitHub Copilot, OpenCode`).

| Skill | Fuente | Nota |
|-------|--------|------|
| `web-design-guidelines` | `vercel-labs/agent-skills` | Diseño web y accesibilidad |
| `ui-ux-pro-max` | `nextlevelbuilder/ui-ux-pro-max-skill` | Patrones UI/UX |
| `design-system` | `nextlevelbuilder/ui-ux-pro-max-skill` | Sistemas de diseño |
| `ionic-design` | `cap-go/capgo-skills` | UI Ionic |
| `capacitor-best-practices` | `cap-go/capgo-skills` | Capacitor |
| `capacitor-security` | `cap-go/capgo-skills` | Seguridad móvil |
| `capacitor-performance` | `cap-go/capgo-skills` | Rendimiento |
| `capacitor-testing` | `cap-go/capgo-skills` | Testing |
| `capacitor-keyboard` | `cap-go/capgo-skills` | Teclado |
| `capacitor-accessibility` | `cap-go/capgo-skills` | Accesibilidad |
| `safe-area-handling` | `cap-go/capgo-skills` | Safe areas |
| `debugging-capacitor` | `cap-go/capgo-skills` | Depuración |
| `webapp-to-capacitor` | `cap-go/capgo-skills` | Migración web→móvil |
| `web-security-review` | `owasp/secure-agent-playbook` | Revisión web |
| `api-security-review` | `owasp/secure-agent-playbook` | Revisión API |
| `code-review-security` | `owasp/secure-agent-playbook` | Code review |
| `secrets-scan` | `owasp/secure-agent-playbook` | Secretos |
| `sca-audit` | `owasp/secure-agent-playbook` | Dependencias |
| `mobile-code-review` | `owasp/secure-agent-playbook` | Móvil |
| `security-guidance` | `owasp/secure-agent-playbook` | Guía general |

### Nota de riesgo
El CLI mostró una alerta de Snyk marcando `owasp/secure-agent-playbook`
(particularmente `mobile-code-review`) como **High Risk**. El contenido proviene
del repositorio oficial de OWASP; aun así, se documenta la alerta y se recomienda
revisar el `SKILL.md` antes de ejecutar instrucciones que impliquen comandos.
No se detectó ejecución automática de ese skill en esta sesión.

## Skills globales relevantes (no duplicadas en el proyecto)
En `C:\Users\Owen\.config\opencode\skills\` ya existen, entre otras:
`mysql-patterns`, `e2e-testing`, `production-audit`, `verification-loop`,
`security-review`, `security-scan`, `api-design`, `git-workflow`, `tdd-workflow`.
Por eso **no** se instalaron versiones de MySQL ni Playwright en el proyecto.

## GSD
**NO INSTALADA.** Ver `BACKLOG.md` y `DECISIONS.md` para el motivo.

## Context7
**CONFIGURADO, PENDIENTE DE AUTENTICACIÓN.** Servidor MCP remoto en
`opencode.json`; sin claves en el repositorio. Ejecutar `opencode mcp auth context7`
o usar `/mcps` para completar OAuth.

## Reglas de mantenimiento
- No instalar dos skills que cubran lo mismo; preferir la global o la local.
- Toda skill nueva debe registrarse aquí con fuente, estado y fecha.
- No copiar skills externas dentro de `.opencode/skills/` sin necesidad.
- Revisar `skills-lock.json` al actualizar.
