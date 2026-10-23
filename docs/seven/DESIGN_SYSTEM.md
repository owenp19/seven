# SEVEN — Sistema de diseño

## Identidad
- Tagline: **MÁS QUE UN DESTINO**.
- Sensación: Caribe auténtico, océano profundo, naturaleza, servicio premium.
- Sin gradientes decorativos; superficies sólidas y fotografía protagonista.
- Voz: cercana, clara, en español; evita tecnicismos.

## Tipografía
| Rol | Fuente | Uso |
|-----|--------|-----|
| Display | **Oswald** | Títulos, destinos, secciones |
| Body/UI | **Poppins** | Texto, botones, menús, formularios |

Clases: `.go-hero-title`, `.go-h1`, `.go-h2`, `.go-h3`, `.go-subtitle`,
`.go-body`, `.go-caption`.
Tokens: `--seven-font-display`, `--seven-font-body`, `--seven-font-ui`.

## Paleta
| Token | Hex | Uso |
|-------|-----|-----|
| `--seven-deep-sea` | `#0B2D4A` | Institucional, títulos, fondos oscuros |
| `--seven-ocean-blue` | `#0077B6` | CTA, enlaces, estado activo |
| `--seven-tropical-tide` | `#00D1C5` | Acento, eco, badges |
| `--seven-lagoon-blue` | `#1CA7EC` | Información |
| `--seven-palm-green` | `#1F8A5C` | Éxito |
| `--seven-soft-coral` | `#FF7A59` | Promo/advertencia |
| `--seven-sun-gold` | `#FF9E45` | Estrellas/ratings |
| `--seven-arena` | `#E8DCC8` | Editorial premium |
| `--seven-gray-50…900` | — | Neutros |
| `--seven-danger` | `#D64550` | Errores |

## Temas
Archivo `frontend/src/theme/themes.scss`; atributo en `<html data-seven-theme>`:
| Tema | Primario | Contraste con blanco |
|------|----------|----------------------|
| `ocean` (defecto) | `#0077B6` | 4.87:1 |
| `sun` | `#8A5E00` | 5.70:1 |
| `coral` | `#C0392B` | 5.44:1 |

El amarillo saturado `#FF9E45` **no** se usa como color de acción con texto
blanco (1.9:1); se reserva para ratings.

## Espaciado, radios y sombras
- Escala 4 px: `--seven-space-1..8` (4, 8, 12, 16, 24, 32, 48).
- Radios: `--seven-radius-sm/md/lg/xl/pill`.
- Sombras: `--seven-shadow-soft/card/raised`.
- Layout: `--seven-page-padding`, `--seven-section-gap`, `--seven-card-gap`,
  `--seven-bottom-nav-height`, `--seven-content-max-width`,
  `--seven-reading-width`, `--seven-detail-bar-height`.

## Componentes
Compartidos en `frontend/src/app/shared/components/`:
`nav-header`, `page-header`, `bottom-nav`, `section-header`, `discovery-card`,
`promo-card`, `booking-card`, `media-carousel`, `comments-section`,
`rating-stars`, `global-loader`, `menu`, `language-selector`,
`seven-assistant-*`, `tourist-news-carousel`.

Regla: reutilizar y extender; no crear una tarjeta nueva si `discovery-card` o
`promo-card` cubren el caso.

## Accesibilidad
- Contraste AA mínimo 4.5:1 en texto normal.
- Targets táctiles ≥ 44×44 px.
- Foco visible con `--seven-focus-ring`.
- No depender solo del color para estados.
- `alt` descriptivo en imágenes de contenido; `alt=""` en decorativas.
- Respetar `prefers-reduced-motion` y `data-reduce-motion`.
- Formularios con label, ayuda y error asociados.

## Reglas de implementación
1. Tokens `--seven-*` siempre; prohibido hex suelto en componentes nuevos.
2. Los alias `--go-*` son legado; no usarlos en código nuevo.
3. No cambiar la paleta base por pantalla; los temas solo cambian el primario.
4. Mantener ritmo vertical y alineación con `--seven-page-padding`.
5. Probar en 320, 375, tablet y desktop.
6. Probar los tres temas en la pantalla modificada.
7. No introducir una segunda familia tipográfica.

## Archivos
- `frontend/src/theme/variables.scss`
- `frontend/src/theme/themes.scss`
- `frontend/src/theme/typography.scss`
- `frontend/src/theme/components/`
