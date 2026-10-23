import { Component } from '@angular/core';
import { TestBed } from '@angular/core/testing';

import { beforeEach, describe, expect, it } from 'vitest';

import { DEFAULT_FALLBACK_IMAGE, SafeImgDirective } from './safe-img.directive';

/** Host con `[fallback]` explicito. */
@Component({
  standalone: true,
  imports: [SafeImgDirective],
  template: `<img appSafeImg [fallback]="custom" [attr.src]="src" />`,
})
class HostComponent {
  src = 'assets/img/missing.png';
  custom = 'assets/img/placeholders/custom.svg';
}

/** Host con el uso real en la app: el atributo sin valor. */
@Component({
  standalone: true,
  imports: [SafeImgDirective],
  template: `<img appSafeImg [attr.src]="src" />`,
})
class BareHostComponent {
  src = 'assets/img/missing.png';
}

/**
 * El DOM de test no resuelve URLs relativas, asi que `img.src` llega vacio.
 * Se espia la propiedad para observar exactamente lo que escribe la directiva.
 */
function img(type: typeof HostComponent | typeof BareHostComponent): { element: HTMLImageElement; assigned: () => string } {
  const fixture = TestBed.createComponent(type);
  fixture.detectChanges();
  const element = fixture.nativeElement.querySelector('img') as HTMLImageElement;
  // Parte del valor ya enlazado por la plantilla para que la directiva vea una
  // fuente real y no una cadena vacia.
  let last = element.getAttribute('src') ?? '';
  Object.defineProperty(element, 'src', {
    configurable: true,
    get: () => last,
    set: (value: string) => {
      last = value;
    },
  });
  return { element, assigned: () => last };
}

function fail(element: HTMLImageElement): void {
  element.dispatchEvent(new Event('error'));
}

describe('SafeImgDirective', () => {
  beforeEach(async () => {
    await TestBed.configureTestingModule({ imports: [HostComponent, BareHostComponent] }).compileComponents();
  });

  it('usa el fallback por defecto cuando el atributo no lleva valor', () => {
    const { element, assigned } = img(BareHostComponent);
    fail(element);

    // Esta es la forma que usan las 10 plantillas de la app.
    expect(assigned()).toBe(DEFAULT_FALLBACK_IMAGE);
  });

  it('respeta el [fallback] pasado por la plantilla', () => {
    const { element, assigned } = img(HostComponent);
    fail(element);

    // Con el alias anterior (`appSafeImg`) un `[fallback]="x"` no era un input
    // reconocido: Angular lo asignaba como propiedad DOM y el placeholder
    // personalizado nunca se aplicaba.
    expect(assigned()).toBe('assets/img/placeholders/custom.svg');
  });

  it('no entra en bucle si el propio fallback falla', () => {
    const { element, assigned } = img(BareHostComponent);
    fail(element);
    const afterFirst = assigned();

    fail(element);
    fail(element);

    expect(assigned()).toBe(afterFirst);
  });
});
