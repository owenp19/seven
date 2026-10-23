import { Component, ChangeDetectionStrategy, effect, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import {
  IonContent,
  IonIcon,
  IonToggle,
} from '@ionic/angular';

import { APP_CONFIG } from '../../core/tokens';
import type { AppConfig } from '../../core/config/app-config.interface';
import { ConnectivityService } from '../../core/services/connectivity.service';
import { DataCacheService } from '../../core/services/data-cache.service';
import { HapticsService } from '../../core/services/haptics.service';
import { NativeService } from '../../core/services/native.service';
import { OfflineDownloadService } from '../../core/services/offline-download.service';
import { TextSizeService } from '../../core/services/text-size.service';
import type { TextSizeId } from '../../core/services/text-size.service';
import { ThemeService } from '../../core/services/theme.service';
import type { ThemeId } from '../../core/services/theme.service';
import type { TranslationKey } from '../../core/services/translation.service';
import { LanguageSelectorComponent } from '../../shared/components/language-selector/language-selector.component';
import { PageHeaderComponent } from '../../shared/components/page-header/page-header.component';
import { TranslatePipe } from '../../shared/pipes/translate/translate.pipe';

const MOTION_KEY = 'seven_reduce_motion';

/**
 * Recorta un identificador largo por el centro: `3f2a9c1b-…-0e7d12ab`.
 *
 * Se descartan los grupos intermedios porque no sirven para reconocer el
 * dispositivo y son los que occupies el ancho. Solo tiene sentido en valores
 * largos; con menos de 20 caracteres el identificador cabe en pantalla y se
 * devuelve tal cual, sin puntos suspensivos que solo confunden.
 */
function shortenInstallId(value: string): string {
  if (value.length <= 20) {
    return value;
  }

  return `${value.slice(0, 8)}\u2026${value.slice(-8)}`;
}

/**
 * Configuración de la app.
 *
 * Mobile-first: en teléfono es una sola columna de tarjetas y todo se ve
 * deslizando. A partir de `768px` las tarjetas se reparten en dos columnas
 * y a partir de `1100px` en tres, para que en tablet y escritorio la página
 * no quede como una tira de texto enorme en el centro de la pantalla.
 *
 * Cada opción hace algo real:
 *  - Idioma y tema se aplican en caliente (signal → atributo en `<html>`).
 *  - El modo sin conexión no es decorativo: `DataCacheService` deja de
 *    caducar entradas y `ConnectivityService` fuerza el estado de red.
 *  - Reducir animaciones escribe `data-reduce-motion`, que `global.scss`
 *    replica a todo el árbol.
 */
@Component({
  selector: 'app-settings',
  templateUrl: './settings.page.html',
  styleUrls: ['./settings.page.scss'],
  standalone: true,
  imports: [
    IonContent,
    IonIcon,
    IonToggle,
    LanguageSelectorComponent,
    PageHeaderComponent,
    RouterLink,
    TranslatePipe,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SettingsPage {
  private readonly dataCache = inject(DataCacheService);
  private readonly haptics = inject(HapticsService);
  private readonly native = inject(NativeService);
  private readonly config = inject<AppConfig>(APP_CONFIG);
  readonly themeService = inject(ThemeService);
  readonly connectivity = inject(ConnectivityService);
  readonly textSizeService = inject(TextSizeService);
  readonly download = inject(OfflineDownloadService);

  readonly appName = this.config.appName;
  readonly appVersion = this.config.appVersion;
  readonly apiEndpoint = `${this.config.apiUrl}/${this.config.apiVersion}`;

/** El backend scopea las reservas de este dispositivo con este identificador. */
  readonly installId = this.native.installId();

  /**
   * El identificador con los bloques intermedios recortados. Un UUID son 36
   * caracteres: a pantalla completa se come tres líneas de la sección Datos y
   * deja la tarjeta más alta que Preferencias y Conectividad juntas. Se
   * recortan los guionajos del medio (que no distinguishes) y se conservan los
   * cuatro primeros y los cuatro últimos, que son los que se comparan cuando
   * hay que confirmar a mano qué dispositivo es. El valor íntegro sigue en el
   * `title`, así que no se pierde para copiarlo.
   */
  readonly shortInstallId = shortenInstallId(this.installId);

  readonly reduceMotion = signal<boolean>(localStorage.getItem(MOTION_KEY) === '1');

  /** Catálogo de temas, para pintar las muestras de color. */
  readonly themes = this.themeService.options;

  /** Niveles de tamaño de texto, de menor a mayor. */
  readonly textSizes = this.textSizeService.options;

  /** Estado real de la red, y el efectivo tras aplicar el modo manual. */
  readonly isOnline = this.connectivity.isOnline;
  readonly isReallyOnline = this.connectivity.isReallyOnline;
  readonly manualOffline = this.connectivity.manualOffline;
  readonly autoOffline = this.connectivity.autoOffline;

  /** Entradas vivas en caché, para que "limpiar caché" tenga un tamaño visible. */
  readonly cacheSize = signal(this.dataCache.size);

  constructor() {
    effect(() => this.applyReduceMotion(this.reduceMotion()));
    void this.download.refresh();
    void this.syncCacheSize();
  }

  onMotionChange(event: Event): void {
    const enabled = (event.target as HTMLIonToggleElement).checked;
    this.reduceMotion.set(enabled);
    void this.haptics.selection();
  }

  onThemeSelect(id: ThemeId): void {
    this.themeService.setTheme(id);
    void this.haptics.selection();
  }

  onTextSizeSelect(id: TextSizeId): void {
    this.textSizeService.setTextSize(id);
    void this.haptics.selection();
  }

  /**
   * Activa/desactiva el modo sin conexión.
   *
   * Al activarlo se empujan las caducidades de la caché para que el catálogo
   * ya descargado siga disponible: si no, el interruptor prometería datos
   * offline que no existirían.
   */
  onOfflineModeChange(event: Event): void {
    const enabled = (event.target as HTMLIonToggleElement).checked;

    this.connectivity.setManualOffline(enabled);
    if (enabled) {
      this.dataCache.keepForOffline();
    }

    void this.haptics.selection();
  }

  onAutoOfflineChange(event: Event): void {
    const enabled = (event.target as HTMLIonToggleElement).checked;
    this.connectivity.setAutoOffline(enabled);
    void this.haptics.selection();
  }

  onClearCache(): void {
    void this.haptics.selection();
    this.dataCache.clear();
    this.cacheSize.set(0);
    void this.download.refresh();
  }

  /**
   * Descarga el catálogo para usarlo sin conexión.
   *
   * Va secuencial a propósito (ver `OfflineDownloadService`): en un móvil con
   * datos limitados, siete peticiones simultáneas solo producen siete
   * timeouts y una barra de progreso que miente.
   */
  async onDownloadOffline(): Promise<void> {
    if (this.download.isRunning) {
      return;
    }

    void this.haptics.selection();
    const ok = await this.download.download();

    if (ok) {
      void this.haptics.success();
    }

    await this.syncCacheSize();
  }

  async onRemoveOffline(): Promise<void> {
    void this.haptics.selection();
    await this.download.remove();
    await this.syncCacheSize();
  }

  /** Tamaño de la copia descargada, en MB con un decimal. */
  offlineSizeMb(): string {
    return (this.download.sizeBytes() / (1024 * 1024)).toFixed(1);
  }

  /**
   * Sincroniza el contador de entradas.
   *
   * El "seeding" de la home ocurre en segundo plano, así que leer `size` en
   * el constructor daba 0 y se quedaba en 0: el usuario veía "0 en caché"
   * con el catálogo ya descargado.
   */
  private async syncCacheSize(): Promise<void> {
    await Promise.resolve();

    this.cacheSize.set(this.dataCache.size);
  }

  /**
   * Texto del estado de red.
   *
   * Distingue tres casos porque la acción que corresponde es distinta en
   * cada uno: sin red real, sin red por preferencia, o todo bien.
   */
  statusLabel(): TranslationKey {
    if (this.manualOffline()) {
      return 'settings.statusManual';
    }

    return this.isReallyOnline() ? 'settings.statusOnline' : 'settings.statusOffline';
  }

  private applyReduceMotion(enabled: boolean): void {
    const root = document.documentElement;

    if (enabled) {
      root.setAttribute('data-reduce-motion', 'true');
      localStorage.setItem(MOTION_KEY, '1');
    } else {
      root.removeAttribute('data-reduce-motion');
      localStorage.removeItem(MOTION_KEY);
    }
  }
}
