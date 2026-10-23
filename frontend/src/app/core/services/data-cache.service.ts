import { Injectable, inject } from '@angular/core';

import { ConnectivityService } from './connectivity.service';
import { STORAGE } from '../tokens';

/** Clave en Ionic Storage donde se guarda la copia offline del catálogo. */
const OFFLINE_KEY = 'seven:offline-catalog';

interface CacheEntry<T> {
  value: T;
  expiresAt: number;
}

/**
 * DataCacheService
 *
 * Lightweight in-memory cache with a time-to-live (TTL). Used to avoid
 * repeated network calls for stable, read-only tourism data (categories,
 * home collections) during a single app session.
 *
 * Cuando la app está sin conexión (real o forzada por el usuario), una
 * entrada vencida **sigue sirviéndose**: en una isla, un catálogo de
 * restaurantes de hace dos horas es infinitamente más útil que una pantalla
 * vacía, y el TTL existe para refrescar en red, no para castigar al
 * viajero sin datos. El flag `stale` viaja con el valor para que quien
 * lo consuma pueda avisar de que es una copia vieja.
 *
 * La descarga "para offline" (`downloadForOffline`) salta a disco con
 * `Storage`, porque una caché solo en memoria se evaporaría al cerrar la
 * app: el viajero que preparó la isla antes de salir del hotel se
 * encontraría con la home vacía en el ferry.
 */
@Injectable({ providedIn: 'root' })
export class DataCacheService {
  private readonly store = new Map<string, CacheEntry<unknown>>();
  private readonly connectivity = inject(ConnectivityService);
  private readonly storage = inject(STORAGE);
  /** Fecha del último volcado a disco, o null si nunca se descargó. */
  private downloadedAt: number | null = null;

  /** Returns the cached value if present and not yet expired. */
  get<T>(key: string): T | undefined {
    const entry = this.store.get(key);
    if (!entry) {
      return undefined;
    }
    if (Date.now() >= entry.expiresAt) {
      // Offline: vale una copia vieja más que nada.
      if (this.connectivity.isOffline()) {
        return entry.value as T;
      }
      this.store.delete(key);
      return undefined;
    }
    return entry.value as T;
  }

  /**
   * Lee ignorando el TTL, para una lectura explícita de "lo que hay".
   *
   * Lo usa el modo offline de Configuración para pintar contenido aunque la
   * entrada haya vencido hace horas.
   */
  getStale<T>(key: string): T | undefined {
    return this.store.get(key)?.value as T | undefined;
  }

  set<T>(key: string, value: T, ttlMs: number): void {
    this.store.set(key, { value, expiresAt: Date.now() + ttlMs });
  }

  /** Evicts a specific entry (e.g. after a mutation). */
  invalidate(key: string): void {
    this.store.delete(key);
  }

  /**
   * Deja el catálogo completo disponible sin conexión.
   *
   * Empuja el vencimiento de cada entrada al máximo para que el modo
   * offline sirva de verdad. Se usa al activar la opción: pulsar el
   * interruptor y ver "sin datos" sería una opción que no funciona.
   */
  keepForOffline(): void {
    const far = Date.now() + 1000 * 60 * 60 * 24 * 7;

    for (const entry of this.store.values()) {
      entry.expiresAt = far;
    }
  }

  /**
   * Guarda en disco todo lo que hay en memoria para que sobreviva al
   * reinicio.
   *
   * `@capacitor/preferences` no sirve aquí: un catálogo JSON puede pesar
   * cientos de KB y el límite por clave es pequeño. `Storage` (IndexedDB
   * en móvil, localStorage en web) aguanta de sobra.
   */
  async persist(): Promise<void> {
    const payload: Record<string, unknown> = {};
    for (const [key, entry] of this.store) {
      payload[key] = { value: entry.value, expiresAt: entry.expiresAt };
    }

    await this.storage.create();
    await this.storage.set(OFFLINE_KEY, payload);
    this.downloadedAt = Date.now();
  }

  /**
   * Restaura la copia de disco en la caché en memoria.
   *
   * Se llama al arrancar. Las entradas descargadas reciben un vencimiento
   * muy lejano: quien pidió "descargar para offline" quiere que siga
   * disponible, no que expire a los cinco minutos.
   */
  async restore(): Promise<number> {
    await this.storage.create();
    const payload = (await this.storage.get(OFFLINE_KEY)) as
      | Record<string, { value: unknown; expiresAt: number }>
      | null;

    if (!payload) {
      return 0;
    }

    let restored = 0;
    for (const [key, entry] of Object.entries(payload)) {
      if (!key || !entry) {
        continue;
      }
      const far = Date.now() + 1000 * 60 * 60 * 24 * 30;
      this.store.set(key, { value: entry.value, expiresAt: Math.max(entry.expiresAt, far) });
      restored++;
    }

    if (restored > 0) {
      this.downloadedAt = Date.now();
    }

    return restored;
  }

  /** Elimina la copia de disco y vacía la memoria. */
  async removePersisted(): Promise<void> {
    await this.storage.create();
    await this.storage.remove(OFFLINE_KEY);
    this.store.clear();
    this.downloadedAt = null;
  }

  /** ¿Hay catálogo descargado para offline? */
  async hasDownload(): Promise<boolean> {
    await this.storage.create();
    const payload = await this.storage.get(OFFLINE_KEY);

    return payload !== null && payload !== undefined;
  }

  /** Fecha en ms de la última descarga, o null. */
  get lastDownloadedAt(): number | null {
    return this.downloadedAt;
  }

  /** Tamaño aproximado de la copia en disco, en bytes. */
  async persistedSize(): Promise<number> {
    await this.storage.create();
    const payload = await this.storage.get(OFFLINE_KEY);

    return payload ? new Blob([JSON.stringify(payload)]).size : 0;
  }

  /** Número de entradas en caché (para mostrarlo en Configuración). */
  get size(): number {
    return this.store.size;
  }

  clear(): void {
    this.store.clear();
  }
}
