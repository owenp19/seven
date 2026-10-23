import { Injectable, signal } from '@angular/core';

/** Temas de color disponibles. `ocean` es el de marca y el valor por defecto. */
export type ThemeId = 'ocean' | 'sun' | 'coral';

export interface ThemeOption {
  readonly id: ThemeId;
  /** Clave de traducción para el nombre legible. */
  readonly labelKey: 'settings.themeOcean' | 'settings.themeSun' | 'settings.themeCoral';
  /**
   * Color de muestra del botón. Duplica el token CSS a propósito: el botón
   * tiene que pintar el mismo hex que la app para que la vista previa no
   * sea una aproximación.
   */
  readonly swatch: string;
  /** Color del texto sobre el botón, por contraste real (medido, no supuesto). */
  readonly onSwatch: string;
}

const STORAGE_KEY = 'seven_theme';
const DEFAULT_THEME: ThemeId = 'ocean';

export const THEMES: readonly ThemeOption[] = [
  { id: 'ocean', labelKey: 'settings.themeOcean', swatch: '#0077B6', onSwatch: '#FFFFFF' },
  { id: 'sun', labelKey: 'settings.themeSun', swatch: '#8A5E00', onSwatch: '#FFFFFF' },
  { id: 'coral', labelKey: 'settings.themeCoral', swatch: '#C0392B', onSwatch: '#FFFFFF' },
];

function isTheme(value: string | null): value is ThemeId {
  return value === 'ocean' || value === 'sun' || value === 'coral';
}

/**
 * ThemeService
 *
 * Permite cambiar el color de acento de la app (azul, amarillo, rojo) sin
 * tocar la paleta de marca: los bloques de `theme/themes.scss` leen el
 * atributo `data-seven-theme` de `<html>` y reescriben los tokens.
 *
 * Se persiste en `localStorage` con la misma filosofía que el idioma
 * (`seven_language`): sin cuenta, sin servidor, y la preferencia sobrevive
 * al cierre de la app.
 *
 * Los colores viven en CSS, no en TypeScript: aquí solo se elige el id y se
 * escribe el atributo. Eso mantiene una única fuente de verdad para el color
 * (el `.scss`) y hace que cambiar un hex sea un cambio de una línea.
 */
@Injectable({ providedIn: 'root' })
export class ThemeService {
  /** Tema activo. Es un signal para que los selectores se re-pinten solos. */
  readonly theme = signal<ThemeId>(ThemeService.readStoredTheme());

  readonly options = THEMES;

  constructor() {
    this.apply(this.theme());
  }

  isActive(id: ThemeId): boolean {
    return this.theme() === id;
  }

  /**
   * Cambia el tema y lo guarda.
   *
   * Se escribe el atributo en `<html>` en vez de tocar el DOM de cada
   * componente: los tokens son variables CSS, así que el navegador
   * re-resuelve las ~200 reglas que las usan sin repintar a mano nada.
   */
  setTheme(id: ThemeId): void {
    if (this.theme() === id) {
      return;
    }

    this.theme.set(id);
    localStorage.setItem(STORAGE_KEY, id);
    this.apply(id);
  }

  private apply(id: ThemeId): void {
    if (typeof document === 'undefined') {
      return;
    }

    document.documentElement.setAttribute('data-seven-theme', id);
  }

  private static readStoredTheme(): ThemeId {
    const stored = localStorage.getItem(STORAGE_KEY);

    return isTheme(stored) ? stored : DEFAULT_THEME;
  }
}
