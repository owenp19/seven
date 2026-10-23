import { Injectable, inject, signal } from '@angular/core';

import { firstValueFrom } from 'rxjs';

import { BannerService } from '../../services/banner.service';
import { BusinessService } from '../../services/business.service';
import { CategoryService } from '../../services/category.service';
import { InnService } from '../../services/inn.service';
import { PromotionService } from '../../services/promotion.service';
import { RestaurantService } from '../../services/restaurant.service';
import { TourService } from '../../services/tour.service';
import { ConnectivityService } from './connectivity.service';
import { DataCacheService } from './data-cache.service';
import type { TranslationKey } from './translation.service';

/** Una fuente de datos a descargar, con su etiqueta para el progreso. */
interface DownloadTask {
  readonly key: string;
  /** Traducción para mostrar "Negocios, 3 de 7". */
  readonly labelKey: TranslationKey;
  readonly load: () => Promise<unknown>;
}

export type DownloadState = 'idle' | 'running' | 'done' | 'error';

/** 30 días: tiempo de sobra para un viaje, y se refresca al volver. */
const OFFLINE_TTL_MS = 30 * 24 * 60 * 60 * 1000;

/**
 * OfflineDownloadService
 *
 * Descarga el catálogo visible de la app (portada, categorías, negocios,
 * tours, restaurantes, hostales, promociones) y lo deja en disco.
 *
 * El botón de Configuración no es un adorno: es la diferencia entre abrir
 * la app en el ferry o mirar el mar. Los servicios ya caches cada respuesta
 * con un TTL de 5 minutos, así que este servicio solo fuerza la lectura de
 * red, agrupa las claves y delega el volcado a `DataCacheService.persist()`.
 *
 * Va secuencial a propósito. Lanzar siete peticiones a la vez en un móvil
 * con datos limitados lo que produce es siete timeouts; una de detrás de
 * otra se puede cancelar y el contador de progreso significa algo real.
 */
@Injectable({ providedIn: 'root' })
export class OfflineDownloadService {
  private readonly cache = inject(DataCacheService);
  private readonly connectivity = inject(ConnectivityService);
  private readonly categories = inject(CategoryService);
  private readonly businesses = inject(BusinessService);
  private readonly tours = inject(TourService);
  private readonly restaurants = inject(RestaurantService);
  private readonly inns = inject(InnService);
  private readonly banners = inject(BannerService);
  private readonly promotions = inject(PromotionService);

  readonly state = signal<DownloadState>('idle');
  /** 0-100, para la barra de progreso. */
  readonly progress = signal(0);
  /** Clave i18n de la fuente en curso, para el texto "Descargando negocios…". */
  readonly currentLabel = signal<TranslationKey | null>(null);
  readonly lastError = signal<TranslationKey | null>(null);
  /** Bytes de la copia en disco, para "12 MB guardados". */
  readonly sizeBytes = signal(0);
  /** Fecha de la última descarga completada. */
  readonly downloadedAt = signal<number | null>(null);

  private readonly tasks: DownloadTask[] = [
    { key: 'banners', labelKey: 'settings.dlBanners', load: () => firstValueFrom(this.banners.getAll()) },
    { key: 'categories', labelKey: 'settings.dlCategories', load: () => firstValueFrom(this.categories.getAll()) },
    { key: 'businesses:featured', labelKey: 'settings.dlBusinesses', load: () => firstValueFrom(this.businesses.getFeatured()) },
    { key: 'tours:featured', labelKey: 'settings.dlTours', load: () => firstValueFrom(this.tours.getFeatured()) },
    { key: 'restaurants:featured', labelKey: 'settings.dlRestaurants', load: () => firstValueFrom(this.restaurants.getFeatured()) },
    { key: 'inns:featured', labelKey: 'settings.dlInns', load: () => firstValueFrom(this.inns.getFeatured()) },
    { key: 'promotions', labelKey: 'settings.dlPromotions', load: () => firstValueFrom(this.promotions.getAll()) },
  ];

  /** Lee el estado persistido al entrar en Configuración. */
  async refresh(): Promise<void> {
    this.downloadedAt.set(this.cache.lastDownloadedAt);
    this.sizeBytes.set(await this.cache.persistedSize());
  }

  get isRunning(): boolean {
    return this.state() === 'running';
  }

  /** Descarga el catálogo. Devuelve false si no se pudo. */
  async download(): Promise<boolean> {
    if (this.isRunning) {
      return false;
    }

    if (this.connectivity.isOffline()) {
      this.lastError.set('settings.dlNeedsNetwork');

      return false;
    }

    this.state.set('running');
    this.lastError.set(null);
    this.progress.set(0);

    const total = this.tasks.length;
    let done = 0;
    let failed = 0;

    for (const task of this.tasks) {
      this.currentLabel.set(task.labelKey);

      try {
        // Invalida antes de pedir: si hay una copia vieja servida desde
        // caché, el servicio la devolvería sin tocar la red y la
        // "descarga" no descargaría nada.
        this.cache.invalidate(task.key);
        const value = await task.load();

        if (value !== null && value !== undefined) {
          // TTL largo: la copia descargada a mano vive, no expira en 5 min.
          this.cache.set(task.key, value, OFFLINE_TTL_MS);
        }
      } catch {
        // Un endpoint caído no debe tumbar la descarga entera: el resto
        // del catálogo sigue siendo útil sin conexión.
        failed++;
      }

      done++;
      this.progress.set(Math.round((done / total) * 100));
    }

    this.currentLabel.set(null);

    if (done - failed === 0) {
      this.state.set('error');

      return false;
    }

    try {
      this.cache.keepForOffline();
      await this.cache.persist();
      this.downloadedAt.set(Date.now());
      this.sizeBytes.set(await this.cache.persistedSize());
      this.state.set('done');

      return true;
    } catch {
      this.state.set('error');

      return false;
    }
  }

  /** Borra la copia offline. */
  async remove(): Promise<void> {
    await this.cache.removePersisted();
    this.state.set('idle');
    this.progress.set(0);
    this.downloadedAt.set(null);
    this.sizeBytes.set(0);
  }
}
