---
name: seven-token-efficient
description: Optimización de tokens para SEVEN. Cargar solo skills necesarias, leer por secciones, evitar análisis repetidos, limitar salidas, reutilizar memoria y responder brevemente.
---

# SEVEN Token Efficient

## Reglas
- **Skills mínimas**: cargar únicamente la skill del área a tocar.
- **Lectura por secciones**: usar offset/limit en Read; evitar leer archivos completos innecesarios.
- **Sin análisis repetidos**: reutilizar hallazgos ya documentados en docs/seven/.
- **Salidas limitadas**: respuestas concisas; detalles solo si se piden.
- **Memoria reutilizada**: seguir orden de seven-memory; no duplicar en contexto.
- **Respuestas breves**: máximo necesario, sin preámbulos extensos.
- **Identidad**: conservar paleta, fuentes (Oswald/Poppins), logotipo y diseño móvil existente.
