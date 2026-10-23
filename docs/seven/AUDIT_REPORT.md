# SEVEN — Reporte de auditoría (Fase 0 / 0.8 / 1.0)

Fecha: 2026-10-22, actualizado: 2026-10-23
Alcance: estructura, stack, API, base de datos, frontend, móvil, seguridad
básica, skills y entorno OpenCode.
Modo: auditoría con estabilización. Solo formato (Pint), `.gitignore` y
documentación; sin cambios de lógica, pantallas, rutas, BD ni dependencias.

> Actualización Fase 1.0 (2026-10-23): el hallazgo crítico de ausencia de Git
> quedó resuelto con un repositorio local en `main` y el commit base
> `chore: baseline estable de SEVEN antes del rediseño UI UX`. No hay remoto.

## Resumen ejecutivo
El proyecto está bien estructurado y tiene una base funcional amplia, con
documentación previa y una suite de tests sólida (276 backend, 206 frontend).
Los bloqueos principales están en **release y entorno**: Android no está
sincronizado con todos los plugins, faltan permisos móviles y el host de
producción es placeholder. Git ya está inicializado localmente (sin remoto). El
build web se reparó durante la auditoría y Pint quedó en verde.

## Hallazgos

### [RESUELTO en Fase 1.0] No había repositorio Git
- Evidencia original: `git status` → `fatal: not a git repository`.
- Estado actual: repo local en `main`, commit base creado, 913 archivos
  versionados, sin remoto.
- Verificación: `git log --oneline`, `git remote -v` (vacío) y escaneo de
  secretos sobre el árbol versionado.
- Verificado: sí.

### [ALTO] Proyecto Android desincronizado con los plugins
- Evidencia: `frontend/android/app/src/main/assets/capacitor.plugins.json` solo
  lista `@capacitor/haptics`, `@capacitor/splash-screen` y
  `@capacitor/status-bar`; `frontend/package.json` declara además geolocation,
  local-notifications, network, preferences y share.
- Impacto: esos plugins pueden no estar disponibles en Android.
- Recomendación: `npm run build` + `npx cap sync android`, revisar el diff y
  probar en dispositivo.
- Verificado: sí (contenido del archivo).

### [ALTO] Permisos Android incompletos
- Evidencia: `frontend/android/app/src/main/AndroidManifest.xml` solo declara
  `android.permission.INTERNET`.
- Impacto: geolocalización y notificaciones locales pueden fallar o no solicitar
  permiso en runtime.
- Recomendación: añadir permisos justificados y flujo runtime; probar en
  Android 13+.
- Verificado: sí.

### [ALTO] Host de producción placeholder
- Evidencia: `frontend/src/environments/environment.prod.ts` →
  `apiUrl: 'https://api.seven.app/api'`; `assertApiConfigured()` lanza al arrancar.
- Impacto: la app de producción no puede arrancar hasta configurar el host real.
- Recomendación: provisionar dominio/API y actualizar el environment.
- Verificado: sí (lectura).

### [MEDIO] SDK de Android no detectado
- Evidencia: `ANDROID_HOME` y `ANDROID_SDK_ROOT` vacíos; `java -version` sí
  devuelve Temurin 21.
- Impacto: el build nativo no es reproducible en este entorno.
- Recomendación: instalar/declarar Android SDK (API 36, build-tools) y verificar
  `sdkmanager`.
- Verificado: sí.

### [RESUELTO] El build web de producción fallaba
- Evidencia inicial: `npm run build` →
  `src/app/shared/components/nav-header/nav-header.component.scss 170:76`
  `expected "{"`.
- Causa: una llave `}` de más en `nav-header.component.scss:92` cerraba el
  bloque `.nav-header` antes de tiempo y dejaba las reglas siguientes fuera de
  contexto.
- Corrección: se eliminó la llave sobrante (corrección de sintaxis, sin cambio
  de diseño).
- Verificación: `npm run build` pasa y genera `www`. Quedan dos warnings:
  `restaurant-detail.page.scss` excede el presupuesto en 245 bytes y
  `localforage` no es ESM.
- Verificado: sí.

### [RESUELTO] Pint fallaba en 10 archivos
- Evidencia inicial: `vendor\bin\pint --test` reportaba `phpdoc_align`,
  `ordered_imports`, `new_with_parentheses`, `fully_qualified_strict_types`,
  `braces_position`, `not_operator_with_successor_space`,
  `single_line_empty_body`, `lambda_not_used_import`,
  `spaces_inside_parentheses`, `class_definition` y `unary_operator_spaces`.
- Corrección: se ejecutó `vendor\bin\pint`; todas las reglas aplicadas son de
  formato y no alteran comportamiento, nombres, APIs ni lógica.
- Verificación: `vendor\bin\pint --test` → `passed`; 276 tests de Laravel pasan.
- Archivos: 7 en `app/` y 3 en `tests/`.
- Verificado: sí.

### [MEDIO] Sin control de dependencias en esta fase
- Evidencia: no se ejecutó auditoría SCA.
- Impacto: vulnerabilidades conocidas podrían estar presentes.
- Recomendación: auditar con una herramienta aprobada y revisar resultados.
- Verificado: no.

### [MEDIO] Archivos de log/artefactos en el repo
- Evidencia: `chrome_err.log` (0 bytes) en la raíz;
  `frontend/build-check.log`, `opencode_build.log`, `serve*.log`,
  `tsc_err*.log`.
- Impacto: ruido, posible filtración de rutas internas.
- Recomendación: documentar y limpiar con aprobación; añadir a `.gitignore`.
- Verificado: sí (listado).

### [BAJO] Codificación visible en consola
- Evidencia: textos con acentos aparecen como `�` en la salida de PowerShell.
- Impacto: probablemente solo consola, no archivos; verificar con editor UTF-8.
- Recomendación: confirmar codificación antes de editar documentos.
- Verificado: parcial.

### [INFORMATIVO] Alerta de Snyk sobre skill externa
- Evidencia: el CLI mostró "High Risk" al instalar
  `owasp/secure-agent-playbook` (`mobile-code-review`).
- Impacto: el contenido es de OWASP, pero conviene revisar instrucciones antes
  de ejecutar comandos sugeridos.
- Recomendación: revisar el `SKILL.md` y no ejecutar instrucciones a ciegas.
- Verificado: sí (salida del CLI).

## Lo que está bien
- Separación clara backend/frontend/docs y scripts raíz.
- API versionada, envelope uniforme y capas definidas.
- Autenticación robusta en diseño: abilities, roles, permisos, tenant scope,
  rate limits, auditoría y fail-secure.
- Esquema de datos coherente, con índices y polimorfismo justificado.
- Frontend standalone, lazy, con servicios transversales y diseño tokenizado.
- 20 skills externas verificadas y 9 skills locales específicas.
- Documentación previa (`docs/ARCHITECTURE.md`, `docs/API.md`, `README.md`).
- Backend: 276 tests pasan (1064 assertions).
- Frontend: 206 tests pasan; lint sin errores.

## Comandos ejecutados
| Comando | Resultado |
|---------|-----------|
| `git status` | Repo local en `main`, sin remoto |
| `git log` | 1 commit base (`chore: baseline estable de SEVEN antes del rediseño UI UX`) |
| `node --version` | v22.15.0 |
| `npm --version` | 11.19.1 |
| `php --version` | 8.2.12 |
| `composer --version` | 2.8.8 |
| `java -version` | Temurin 21.0.12 |
| `mysql --version` | MariaDB 10.4.32 |
| `npx skills list` | 20 skills de proyecto |
| Lectura de JSON/config | `opencode.json` válido |
| `npm run build` | Pasa y genera `www` (307 s; solo warnings conocidos) |
| `npm run lint` | Pasa |
| `npm test -- --watch=false` | 206 tests pasan (21 archivos) |
| `php artisan test` | 276 tests pasan (1064 assertions) |
| `vendor\bin\pint` | Aplica formato en 10 archivos |
| `vendor\bin\pint --test` | **Pasa** |
| Prueba de `.gitignore` | 13 rutas versionables / 15 ignoradas correctamente |

## Recomendación de siguiente fase
1. ~~`git init` + commit base~~ (hecho en Fase 1.0; sin remoto).
2. SDK Android + `cap sync` + permisos.
3. Host de producción.
4. SCA y prueba de abuso del assistant.
