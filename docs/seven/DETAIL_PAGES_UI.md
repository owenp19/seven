# DETAIL_PAGES_UI — Reglas reutilizables para páginas de detalle

## Objetivo
Unificar layout, espaciados, jerarquía y responsive para tour-detail, restaurant-detail, inn-detail y business-detail sin romper móvil.

## Layout desktop/tablet
- Máximo de lectura: usar ar(--seven-content-max-width) o ar(--seven-reading-width) según sección.
- En PC: permitir layout con contenido principal + panel lateral cuando aplique (reserva/información clave). Solo si ya existe acción de reserva funcional.
- Tablet: adaptar grid a 2 columnas cuando hay panel lateral; vertical mantener flujo.
- Hero: conservar altura inmersiva pero sin solapar texto. Usar clamp() para tipografía.
- Galería: en desktop usar grid organizado (no carrusel gigante). Mantener imágenes existentes.
- Precio/acción: mantener barra fija solo si existe hoy; nunca duplicar CTAs.

## Espaciados
- Usar tokens --seven-space-*, gaps consistentes.
- Corregir desbordes laterales con overflow-x: hidden solo si necesario y sin recortar contenido.
- Evitar márgenes excesivos entre secciones.

## Identidad
- Colores únicamente tokens SEVEN. Oswald/Poppins obligatorios.
- Logotipo sin cambios. Temas respetados.

## Accesibilidad
- Alt descriptivo, ria-label en botones.
- Foco visible.
- Contraste AA.
- Navegación por teclado.

## Reutilización
- Usar componentes existentes: MediaCarouselComponent, CommentsSectionComponent, etc.
- No inventar datos/servios/rutas.
- Mantener header/footer/navegación.
