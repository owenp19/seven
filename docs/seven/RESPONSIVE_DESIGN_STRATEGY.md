# SEVEN — Estrategia de Diseño Responsive (Fase 1)

## Principios
- Preservar móvil: la experiencia móvil existente es referencia; solo mejoras puntuales justificadas.
- Identidad obligatoria: conservar paleta, Oswald/Poppins, logotipo, temas. Adaptar solo jerarquía/espaciados/grillas/layouts.
- Evolución, no rediseño: tablet y escritorio son experiencias nuevas pero coherentes con móvil.
- Sin duplicación: compartir lógica/componente, usar variantes CSS/Angular.
- Tokens primero: usar --seven-* siempre.

## Breakpoints
| Contexto | Breakpoints | Notas |
|----------|-------------|-------|
| Móvil | 320, 375, 390, 430 | Base actual; conservar navegación inferior |
| Tablet | 600, 768, 820, 1024 | Aprovechar espacio: grillas 2 columnas, filtros laterales si aplica |
| Escritorio | 1280, 1440, 1920 | Header horizontal, contenido centrado con --seven-content-max-width, nav lateral/dock según área |

## Layout compartido
- Shell (tabs): layouts/tabs/tabs-layout.component mantiene header inferior en móvil; en >=1024px bottom-nav pasa a dock flotante centrado. Evaluar header superior adaptativo.
- Nav header: ya muestra enlaces inline en >=1024px. Mantener comportamiento.
- Bottom nav: dock centrado en desktop; ocultar o mantener según área si necesario.

## Reglas
- Sin overflow horizontal
- Targets táctiles >=44x44px
- Safe areas respetadas
- prefers-reduced-motion respetado
- No depender solo de hover
- Probar 3 temas

## Plan de trabajo
1. Auditoría visual por área (home, explore, business detail, bookings)
2. Mejoras móviles puntuales
3. Layouts tablet (grillas 2 columnas, formularios reorganizados)
4. Layouts escritorio (header horizontal + contenido amplio, sidebars cuando aplique)
5. Documentar en DESIGN_SYSTEM/DECISIONS/SESSION_LOG
