# SEVEN — Backlog priorizado

## P0 — Antes de seguir desarrollando
| # | Tarea | Motivo | Criterio de done |
|---|-------|--------|------------------|
| 1 | ~~Inicializar Git y commit base~~ **Hecho (Fase 1.0)** | Sin remoto; baseline local disponible | Commit base `chore: baseline estable de SEVEN antes del rediseño UI UX` |
| 2 | Configurar `ANDROID_HOME` | El build Android no es reproducible | `sdkmanager` y Gradle funcionan |
| 3 | Sincronizar Capacitor | 5 plugins no están en Android | `capacitor.plugins.json` lista los 8 plugins |
| 4 | Permisos Android de geolocalización/notificaciones | Funciones nativas pueden fallar | Permisos + solicitud runtime probados |
| 5 | Definir host real de producción | La app bloquea el arranque en prod | `environment.prod.ts` con URL real |

## P1 — Calidad y estabilidad
| # | Tarea | Motivo |
|---|-------|--------|
| 6 | Verificar contrato API contra frontend | Evitar drift de campos |
| 7 | Tests de regresión de reservas y tenant isolation | Áreas críticas |
| 8 | Probar offline real (avión/red lenta) | Promesa de producto |
| 9 | Revisar accesibilidad de formularios y navegación | WCAG AA |
| 10 | Probar los 3 temas y tamaños de texto | Consistencia visual |
| 11 | Limpiar logs/artefactos sueltos | Higiene del repo |
| 12 | Documentar variables de entorno por entorno | Onboarding |
| 13 | Auditar dependencias (SCA) | Vulnerabilidades conocidas |
| 14 | Bajar el presupuesto SCSS de `restaurant-detail` | Warning de build (245 bytes) |

## P2 — Producto
| # | Tarea | Nota |
|---|-------|------|
| 15 | Cuentas de turista | Requiere diseño de auth y migración |
| 16 | Pagos | Requiere pasarela, cumplimiento y conciliación |
| 17 | Reseñas verificadas | Requiere compra/reserva verificada |
| 18 | Push transaccionales | Requiere FCM/APNs y backend |
| 19 | Panel de métricas | Analítica de embudo |
| 20 | Internacionalización de contenido | Hoy se traduce UI, no catálogo |

## GSD (Get Shit Done)
**Estado: NO INSTALADO.**
Motivo: existen múltiples forks/ports para OpenCode y el instalador puede
sobrescribir configuración o instalar agentes globales. En esta fase se prioriza
no alterar el entorno sin aprobación. La planificación se mantiene en este
backlog y en `docs/seven/`.

Alternativa si se aprueba: evaluar `open-gsd/gsd-core` en un branch limpio,
instalación local, y revisar cada archivo antes de aceptar.

## Context7
Configurado en `opencode.json` como MCP remoto (`https://mcp.context7.com/mcp`),
sin API key en el repo. Requiere autenticación OAuth la primera vez que se use.
