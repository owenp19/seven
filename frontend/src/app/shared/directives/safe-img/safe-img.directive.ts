import { Directive, ElementRef, Input, inject } from '@angular/core';

export const DEFAULT_FALLBACK_IMAGE = 'assets/img/placeholders/fallback-service.svg';

/**
 * safeImg
 *
 * Swaps the source for a branded placeholder when an image fails to
 * load (broken URL, offline cache, missing media).
 *
 *   <img appSafeImg [fallback]="fallbackUrl" [src]="images[0]">
 *
 * La propiedad de template `[fallback]` se asignaba como atributo DOM porque
 * el alias del @Input era el mismo selector. Esto dejaba sin efecto el
 * fallback en todas las plantillas que lo intentaban pasar. Ahora el input
 * tiene un nombre explícito (`fallback`) para que Angular lo reconozca cuando
 * se escribe como `[fallback]` (y sigue aceptando el selector `appSafeImg`).
 */
@Directive({ selector: '[appSafeImg]', standalone: true })
export class SafeImgDirective {
  private readonly el = inject<ElementRef<HTMLImageElement>>(ElementRef);
  private currentSrc = '';

  private fallbackSrc = DEFAULT_FALLBACK_IMAGE;

  /**
   * Nombre de template explicito. Con el alias anterior (`appSafeImg`) un
   * `[fallback]="x"` no era un input recognized y Angular lo escribia como
   * propiedad DOM, dejando el placeholder sin efecto.
   */
  @Input()
  set fallback(value: string) {
    this.fallbackSrc = value || DEFAULT_FALLBACK_IMAGE;
  }

  constructor() {
    const img = this.el.nativeElement;

    img.addEventListener('error', () => {
      const src = img.src;
      if (!src || src.endsWith(this.fallbackSrc)) {
        return;
      }
      // Evita bucle infinito si el fallback también falla.
      img.onerror = null;
      img.src = this.fallbackSrc;
    });
  }
}