---
name: seven-memory
description: Memoria persistente del proyecto SEVEN. Usar al iniciar cualquier sesión, al cerrar trabajo, o cuando haya que decidir qué documento leer/actualizar. Define el orden de lectura de docs/seven, qué archivo es fuente de verdad para cada tema y cómo registrar decisiones y sesiones sin duplicar información.
---

# SEVEN Memory

## Propósito
Mantener continuidad entre sesiones de OpenCode. La memoria vive en `docs/seven/` y
`AGENTS.md`; no en la cabeza del agente ni en historiales de chat.

## Orden de lectura al iniciar sesión
1. `AGENTS.md` — reglas no negociables y mapa de documentos.
2. `docs/seven/CURRENT_STATE.md` — qué funciona, qué está roto, qué está pendiente.
3. `docs/seven/SESSION_LOG.md` — últimas entradas, en orden inverso.
4. Solo el documento del área a tocar:
   - Diseño/UI → `DESIGN_SYSTEM.md`
   - Angular/Ionic → `ARCHITECTURE.md`, `TECH_STACK.md`
   - Laravel/API → `API_CONTRACTS.md`, `SECURITY.md`
   - Base de datos → `DATABASE_MAP.md`
   - Móvil → `MOBILE_READINESS.md`
   - Planificación → `BACKLOG.md`

## Fuentes de verdad
| Tema | Documento |
|------|-----------|
| Visión y alcance | `PROJECT_OVERVIEW.md` |
| Arquitectura y capas | `ARCHITECTURE.md` |
| Stack y versiones | `TECH_STACK.md` |
| Estado real y riesgos | `CURRENT_STATE.md` |
| Funcionalidades | `FEATURES.md` |
| Endpoints y envelopes | `API_CONTRACTS.md` |
| Tablas, relaciones e índices | `DATABASE_MAP.md` |
| Tokens, tipografía, temas | `DESIGN_SYSTEM.md` |
| Seguridad y amenazas | `SECURITY.md` |
| Android/iOS/Capacitor | `MOBILE_READINESS.md` |
| Trabajo pendiente | `BACKLOG.md` |
| Skills y procedencia | `SKILLS_REGISTRY.md` |
| Decisiones técnicas | `DECISIONS.md` |
| Historial de sesiones | `SESSION_LOG.md` |
| Diagnóstico técnico | `AUDIT_REPORT.md` |
| Traspaso a otra IA | `HANDOFF_CHATGPT.md` |

## Reglas
- Toda afirmación técnica debe poder rastrearse a un archivo o comando. Si no se
  verificó, se escribe "no verificado".
- No duplicar contenido: si un dato pertenece a otro documento, enlazarlo.
- No guardar secretos, tokens, claves API ni valores de `.env` reales.
- Al terminar una sesión, añadir una entrada breve a `SESSION_LOG.md` y actualizar
  `CURRENT_STATE.md` si cambió el estado.
- Las decisiones con alternativas van a `DECISIONS.md` con fecha, contexto,
  opciones y consecuencia. No se reescribe una decisión: se supersede.
- Si se detecta una contradicción entre documentos, gana la evidencia del código;
  corregir el documento y anotarlo en `SESSION_LOG.md`.

## Formato de entrada de sesión
```markdown
## YYYY-MM-DD — Título corto
- Objetivo:
- Cambios:
- Verificación ejecutada:
- Pendiente:
- Riesgos:
```

## No hacer
- No crear documentos nuevos fuera de `docs/seven/` sin necesidad.
- No borrar entradas antiguas del log; se añade, no se reescribe la historia.
- No usar este skill para cambiar código; solo para orientar y registrar.
