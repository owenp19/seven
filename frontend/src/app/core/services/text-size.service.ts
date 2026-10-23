import { Injectable, signal } from '@angular/core';

/** Niveles de tamaño de texto. El orden va de menor a mayor. */
export type TextSizeId = 'small' | 'normal' | 'large' | 'xlarge';

export interface TextSizeOption {
  readonly id: TextSizeId;
  /** Clave de traducción para el nombre legible. */
  readonly labelKey: 'settings.textSmall' | 'settings.textNormal' | 'settings.textLarge' | 'settings.textXLarge';
  /**
   * Escala aplicada al `font-size` de `<html>`, en porcentaje.
   *
   * Casi todo el app mide en `rem`, así que cambiar la raíz escala de una vez
   * títulos, cuerpo, botones y tablas sin tocar 300 reglas. `1rem` sigue
   * valiendo 16px en el nivel normal, que es la referencia de diseño.
   */
  readonly scale: number;
}

const STORAGE_KEY = 'seven_text_size';
const DEFAULT_SIZE: TextSizeId = 'normal';

export const TEXT_SIZES: readonly TextSizeOption[] = [
  { id: 'small', labelKey: 'settings.textSmall', scale: 0.875 },
  { id: 'normal', labelKey: 'settings.textNormal', scale: 1 },
  { id: 'large', labelKey: 'settings.textLarge', scale: 1.125 },
  { id: 'xlarge', labelKey: 'settings.textXLarge', scale: 1.25 },
];

function isTextSize(value: string | null): value is TextSizeId {
  return value === 'small' || value === 'normal' || value === 'large' || value === 'xlarge';
}

/**
 * TextSizeService
 *
 * Tamaño de texto de la app, como opción de accesibilidad.
 *
 * No pone un `zoom` en el body ni reescribe estilos: cambia el
 * `font-size` de `<html>`, que es la raíz de toda medida en `rem`. Es la
 * diferencia entre una opción que funciona y una que deja partes de la
 * pantalla sin escalar.
 *
 * Ionic fija su propia fuente en píxeles vía `--ion-font-size`, así que
 * también se toca ese token: sin él, los componentes de la librería
 * (toasts, alerts, inputs) se quedan en el tamaño original y la opción
 * parecería medio rota.
 */
@Injectable({ providedIn: 'root' })
export class TextSizeService {
  /** Nivel activo. Signal para que el selector se repinte solo. */
  readonly textSize = signal<TextSizeId>(TextSizeService.readStored());

  readonly options = TEXT_SIZES;

  constructor() {
    this.apply(this.textSize());
  }

  isActive(id: TextSizeId): boolean {
    return this.textSize() === id;
  }

  setTextSize(id: TextSizeId): void {
    if (this.textSize() === id) {
      return;
    }

    this.textSize.set(id);
    localStorage.setItem(STORAGE_KEY, id);
    this.apply(id);
  }

  private apply(id: TextSizeId): void {
    if (typeof document === 'undefined') {
      return;
    }

    const option = TEXT_SIZES.find((item) => item.id === id);
    if (!option) {
      return;
    }

    const root = document.documentElement;
    // `rem` sigue siendo 16px en el nivel normal: es la referencia de diseño.
    root.style.fontSize = `${16 * option.scale}px`;
    // Los componentes de Ionic miden en px en su propio token.
    root.style.setProperty('--ion-font-size', `${16 * option.scale}px`);
  }

  private static readStored(): TextSizeId {
    const stored = localStorage.getItem(STORAGE_KEY);

    return isTextSize(stored) ? stored : DEFAULT_SIZE;
  }
}
