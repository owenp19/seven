# UI/UX Audit SEVEN — FASE 1.1

Fecha: 2026-10-24
Alcance: mobile/tablet/desktop | Preservar identidad (paleta, Oswald/Poppins, logotipo, temas)
Móvil: preservar (dock inferior actual es coherente). Tablet/PC: propios y profesionales.

## Skills activas
- ui-ux-pro-max
- seven-design-system  
- seven-token-efficient

## Inspección
- Shell: tabs-layout + nav-header + bottom-nav. Móvil usa bottom-nav fijo; >=1024px dock flotante centrado (ya existe).
- Home/explore usan grillas/responsive parcial (ya hay ajustes tablet/desktop en scss).
- Playwright no detectado como binario en frontend/node_modules/.bin; inspección por código.

## 5 problemas más importantes
1. **Espaciado inconsistente entre secciones** (alto): home rails usan paddings distintos; en tablet/desktop falta ritmo vertical coherente.
2. **Jerarquía poco clara en listados** (alto): filtros/buscadores carecen de agrupación visual en escritorio; títulos secundarios se diluyen.
3. **Navegación dual confusa** (medio-alto): nav-header inline en >=1024px + bottom-nav dock flotante pueden competir; falta jerarquía primaria/secundaria.
4. **Genérico en vacío/estados** (medio): estados vacíos sin ilustración/acciones claras; poco diferenciador vs plantillas genéricas.
5. **Anchos máximos sin centrado coherente** (medio): algunas páginas no usan --seven-content-max-width; en 1440px se ve estirado.

## Primer layout a mejorar
**Home (home.page)** — Prioritario. Mejorar: max-width + centrado, grid de rails en tablet/desktop, espaciados consistentes con tokens, mantener móvil intacto. Aprovechar dock flotante existente sin tocar bottom-nav móvil.
