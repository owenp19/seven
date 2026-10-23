# SEVEN — Decisiones

Formato: fecha, contexto, decisión, alternativas, consecuencia.

## 2026-10-22 — Mantener skills externas en `.agents/skills`
- **Contexto**: OpenCode descubre skills en `.agents/skills` y `.opencode/skills`.
  El CLI `npx skills` instala en `.agents/skills` y genera `skills-lock.json`.
- **Decisión**: dejar las skills externas donde el CLI las instala y crear las
  skills propias de SEVEN en `.opencode/skills`.
- **Alternativas**: copiar todo a `.opencode/skills` (duplica y rompe hashes);
  instalar globalmente (contamina otros proyectos).
- **Consecuencia**: dos ubicaciones, pero con responsabilidades claras y
  procedencia verificable.

## 2026-10-22 — No instalar GSD en esta fase
- **Contexto**: GSD tiene múltiples forks y ports para OpenCode; su instalador
  puede escribir agentes/comandos y modificar configuración.
- **Decisión**: no instalarlo. Documentar evaluación y alternativa.
- **Alternativas**: instalar `open-gsd/gsd-core` local; usar un port de terceros.
- **Consecuencia**: se evita sobrescribir configuración sin aprobación. La
  planificación queda en `BACKLOG.md` hasta que el usuario decida.

## 2026-10-22 — Context7 como MCP remoto único
- **Contexto**: Context7 tiene integración oficial con OpenCode vía MCP remoto u
  plugin; el proyecto no tenía configuración MCP.
- **Decisión**: un solo servidor `context7` en `opencode.json`, remoto, con OAuth.
  Sin `CONTEXT7_API_KEY` en el repositorio.
- **Alternativas**: plugin global; servidor local con `npx`; clave en el repo
  (rechazada por seguridad).
- **Consecuencia**: requiere autenticación OAuth la primera vez. Si se define una
  clave, debe ir por variable de entorno, nunca en Git.

## 2026-10-22 — No modificar lógica, BD ni dependencias
- **Contexto**: fase de preparación y auditoría.
- **Decisión**: solo documentación, skills y configuración MCP. Sin migraciones,
  seeders, `cap sync`, builds de APK ni actualizaciones masivas.
- **Consecuencia**: el estado funcional no cambia; los riesgos quedan
  documentados para una fase de implementación.

## 2026-10-22 — No inicializar Git automáticamente
- **Contexto**: la raíz no es un repositorio; inicializarlo es una acción con
  impacto y el usuario no lo pidió.
- **Decisión**: documentar el riesgo y recomendar Git como P0, sin ejecutarlo.
- **Consecuencia**: no hay red de seguridad todavía; cualquier cambio futuro
  debe hacerse con copias o tras aprobar Git.

## 2026-10-23 — Control de versiones local sin remoto
- **Contexto**: la Fase 0.9 intentó publicar en GitHub, pero el push no se
  completó por falta de credenciales. La Fase 1.0 pide un baseline local antes
  del rediseño UI/UX, sin remoto ni publicación.
- **Decisión**: repositorio Git local en `main` con un único commit base
  (`chore: baseline estable de SEVEN antes del rediseño UI UX`), sin remotos.
  Supersede la decisión del 2026-10-22 de no inicializar Git.
- **Alternativas**: publicar en GitHub (descartado en esta fase); seguir sin Git
  (rechazado por falta de rollback).
- **Consecuencia**: hay historial y punto de recuperación local, pero no copia
  externa. Definir remoto solo con autorización explícita.

## 2026-10-23 — Publicar el baseline en GitHub
- **Contexto**: tras crear el baseline local, el usuario pidió subirlo a
  `https://github.com/owenp19/seven.git`.
- **Decisión**: remoto `origin` por HTTPS con autenticación OAuth de GitHub CLI
  y `main` con upstream. Supersede la decisión de mantener el repo solo local.
- **Alternativas**: seguir sin remoto (descartado por falta de copia externa);
  token manual (descartado por manejo de secretos).
- **Consecuencia**: el código queda publicado en GitHub y la autenticación en el
  keyring del sistema. No se reescribe historial publicado.

## 2026-10-22 — Mantener la documentación existente
- **Contexto**: ya existen `docs/ARCHITECTURE.md`, `docs/API.md` y `README.md`.
- **Decisión**: no reescribirlos; `docs/seven/` los resume y enlaza.
- **Consecuencia**: se evita duplicación y se conserva el conocimiento previo.
