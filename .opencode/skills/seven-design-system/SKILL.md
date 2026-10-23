---
name: seven-design-system
description: Sistema de diseño SEVEN. Usar al crear o modificar pantallas, componentes, estilos, temas, iconografía, tipografía o accesibilidad. Define tokens obligatorios, paleta, tipografías Oswald/Poppins, temas azul/amarillo/rojo, targets táctiles, contraste WCAG y las reglas para no romper la identidad visual.
---

# SEVEN Design System

## Identidad
- Marca: SEVEN, turismo auténtico de isla. Tagline: **MÁS QUE UN DESTINO**.
- Tono visual: océano, naturaleza, premium accesible. Sin gradientes decorativos.
- Fuentes: **Oswald** para display/títulos; **Poppins** para cuerpo/UI.
- Idioma de UI: español por defecto; hay traducciones en `translation.service.ts`.

## Fuente de verdad
- Tokens de marca: `frontend/src/theme/variables.scss`.
- Temas seleccionables: `frontend/src/theme/themes.scss` (`html[data-seven-theme='ocean'|'sun'|'coral']`).
- Escala tipográfica y clases: `frontend/src/theme/typography.scss`.
- Componentes de tema: `frontend/src/theme/components/`.
- Iconos: `frontend/src/app/shared/icons/register-icons.ts` (Ionicons registrados explícitamente).

## Paleta base
| Token | Hex | Uso |
|-------|-----|-----|
| `--seven-deep-sea` | `#0B2D4A` | Institucional, títulos, superficies oscuras |
| `--seven-ocean-blue` | `#0077B6` | Acción, CTA, enlaces, estado activo |
| `--seven-tropical-tide` | `#00D1C5` | Acento, eco, badges |
| `--seven-lagoon-blue` | `#1CA7EC` | Información, acento secundario |
| `--seven-palm-green` | `#1F8A5C` | Éxito, confirmaciones |
| `--seven-soft-coral` | `#FF7A59` | Promociones, advertencia |
| `--seven-sun-gold` | `#FF9E45` | Estrellas/ratings (no texto sobre blanco) |
| `--seven-arena` | `#E8DCC8` | Superficies editoriales premium |

Neutros: `--seven-gray-50` a `--seven-gray-900`. Fondo `--seven-background`
(`gray-50`), superficie `--seven-surface` (`#FFFFFF`).

## Reglas obligatorias
1. Usar tokens `var(--seven-*)`. Nunca hex sueltos en componentes nuevos.
2. Los alias `--go-*` existen por compatibilidad; en código nuevo usar `--seven-*`.
3. No redefinir la paleta por pantalla. Los temas solo cambian el primario.
4. Tipografía: clases `.go-hero-title`, `.go-h1`, `.go-h2`, `.go-h3`,
   `.go-subtitle`, `.go-body`, `.go-caption`; no inventar tamaños.
5. Espaciado en múltiplos de 4 px con `--seven-space-*`.
6. Radios con `--seven-radius-*`; elevación con `--seven-shadow-*`.
7. Targets táctiles ≥ 44×44 px y separación ≥ 8 px.
8. Respetar `prefers-reduced-motion` y el atributo `data-reduce-motion`.
9. Contraste mínimo WCAG AA (4.5:1 texto normal). El primario del tema `sun`
   es `#8A5E00` precisamente para cumplir AA; no volver al amarillo saturado.
10. Safe areas: nunca pegar contenido a notch/gesture bar; usar las utilidades
    existentes y `--seven-bottom-nav-height`.

## Componentes compartidos
Reutilizar antes de crear: `shared/components/` incluye `nav-header`,
`page-header`, `bottom-nav`, `section-header`, `discovery-card`, `promo-card`,
`booking-card`, `media-carousel`, `comments-section`, `rating-stars`,
`global-loader`, `menu`, `language-selector` y la familia `seven-*` del asistente.

## Accesibilidad
- Todo control interactivo necesita nombre accesible (`aria-label`, texto visible
  o `alt`). Las imágenes decorativas usan `alt=""`.
- Estados de foco visibles con `--seven-focus-ring`.
- No comunicar estado solo por color; añadir texto/icono.
- Formularios: label asociado, mensaje de error junto al campo, `aria-invalid`.
- Verificar con lector de pantalla cuando se toque navegación o formularios.

## Antes de entregar
- [ ] Sin colores hardcodeados nuevos.
- [ ] Tema `ocean`, `sun` y `coral` probado en la pantalla tocada.
- [ ] Modo claro/oscuro (si aplica) y texto largo sin truncados raros.
- [ ] 320 px, 375 px, tablet y desktop sin overflow horizontal.
- [ ] Teclado y foco visibles.
