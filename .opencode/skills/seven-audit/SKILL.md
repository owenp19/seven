---
name: seven-audit
description: Auditoría evidence-first de SEVEN. Usar antes de planificar cambios, al revisar salud del repo, al diagnosticar un fallo o al preparar un handoff. Define cómo inspeccionar sin modificar, qué comandos ejecutar, cómo clasificar hallazgos por severidad y cómo reportarlos con evidencia.
---

# SEVEN Audit

## Principio
Auditar es **observar y documentar**, no arreglar. En una auditoría no se cambian
pantallas, lógica, rutas, controladores, migraciones ni dependencias.

## Flujo
1. **Alcance**: escribir qué se va a auditar y qué queda fuera.
2. **Evidencia**: leer archivos y ejecutar solo comandos de lectura/verificación.
3. **Clasificación**: Crítico / Alto / Medio / Bajo / Informativo.
4. **Reporte**: hallazgo, evidencia (`archivo:línea` o salida de comando), impacto,
   recomendación y si se verificó o no.
5. **Registro**: actualizar `docs/seven/AUDIT_REPORT.md`, `CURRENT_STATE.md` y
   `SESSION_LOG.md`.

## Comandos seguros
```powershell
# Estructura y archivos
Get-ChildItem -Force
Get-ChildItem frontend/src/app -Recurse -Directory
Get-ChildItem backend/app -Recurse -Filter *.php

# Búsquedas
Select-String -Path <archivos> -Pattern <patrón>
git status            # solo si existe .git

# Versiones
node --version; npm --version; php --version; composer --version; java -version; mysql --version

# Verificación de build/tests (no destructivo)
cd frontend; npm run build
cd frontend; npm run lint
cd backend; php artisan test
```

## Comandos prohibidos en auditoría
- `php artisan migrate`, `migrate:fresh`, `db:seed`, `db:wipe`.
- `npm audit fix`, `composer update`, `npm update`, cambios de lockfiles.
- `npx cap sync`, `cap add`, `cap run`, builds de APK.
- Cualquier `git reset`, `checkout`, `clean`, `push`.
- Leer `.env` real o imprimir claves. Solo `.env.example` y nombres de variables.

## Checklist mínimo
- [ ] ¿Existe control de versiones? ¿Hay `.gitignore`?
- [ ] ¿El build de frontend pasa? ¿El lint pasa?
- [ ] ¿Los tests de backend pasan? ¿Requieren BD?
- [ ] ¿El API responde? ¿Health check?
- [ ] ¿Hay secretos versionados o logs con datos?
- [ ] ¿Dependencias principales y versiones coinciden con los docs?
- [ ] ¿Android/iOS están sincronizados con los plugins instalados?
- [ ] ¿Hay archivos huérfanos, logs o artefactos de build?
- [ ] ¿Las rutas documentadas existen en `routes/api/v1/api.php`?
- [ ] ¿Las tablas documentadas existen en migraciones?

## Severidad
- **Crítico**: pérdida de datos, secreto expuesto, auth bypass, build imposible.
- **Alto**: funcionalidad principal rota, migración riesgosa, dependencia vulnerable.
- **Medio**: deuda que frena features, tests ausentes en área crítica, UX rota.
- **Bajo**: limpieza, nombres, duplicación, documentación desactualizada.
- **Informativo**: observación sin acción inmediata.

## Plantilla de hallazgo
```markdown
### [SEVERIDAD] Título
- Evidencia: `ruta/archivo:línea` o salida de comando
- Impacto:
- Recomendación:
- Verificado: sí/no (comando/fecha)
```

## Regla de honestidad
Si no se ejecutó una verificación, se escribe **"no verificado"**. Nunca declarar
"funciona", "instalado" o "seguro" sin evidencia comprobable.
