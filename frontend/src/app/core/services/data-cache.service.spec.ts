import { TestBed } from '@angular/core/testing';

import { ConnectivityService } from './connectivity.service';
import { DataCacheService } from './data-cache.service';
import { STORAGE } from '../tokens';

class FakeStorage {
  readonly store = new Map<string, unknown>();

  async create(): Promise<FakeStorage> {
    return this;
  }

  async get(key: string): Promise<unknown> {
    return this.store.get(key) ?? null;
  }

  async set(key: string, value: unknown): Promise<unknown> {
    this.store.set(key, value);

    return value;
  }

  async remove(key: string): Promise<void> {
    this.store.delete(key);
  }
}

describe('DataCacheService (modo sin conexión)', () => {
  let cache: DataCacheService;
  let connectivity: ConnectivityService;
  let storage: FakeStorage;

  beforeEach(() => {
    storage = new FakeStorage();
    boot();
    cache = TestBed.inject(DataCacheService);
    connectivity = TestBed.inject(ConnectivityService);
  });

  /** `resetTestingModule` también borra los providers: hay que rehacerlos. */
  function boot(): void {
    TestBed.configureTestingModule({ providers: [{ provide: STORAGE, useValue: storage }] });
  }

  afterEach(() => {
    connectivity.setManualOffline(false);
  });

  it('sirve una entrada vigente normalmente', () => {
    cache.set('categories', [{ id: 1 }], 60_000);

    expect(cache.get('categories')).toEqual([{ id: 1 }]);
  });

  it('devuelve undefined si la entrada venció y hay red', () => {
    cache.set('categories', [{ id: 1 }], -1);

    expect(cache.get('categories')).toBeUndefined();
  });

  it('sirve una entrada vencida cuando la app está sin conexión', () => {
    connectivity.setManualOffline(true);
    cache.set('categories', [{ id: 1 }], -1);

    expect(cache.get('categories')).toEqual([{ id: 1 }]);
  });

  it('sigue sirviendo la copia vieja aunque venza, si se está offline', () => {
    cache.set('categories', [{ id: 1 }], 60_000);
    connectivity.setManualOffline(true);

    expect(cache.get('categories')).toEqual([{ id: 1 }]);
  });

  it('getStale lee la entrada aunque el TTL no haya vencido', () => {
    cache.set('banners', [{ id: 9 }], 60_000);

    expect(cache.getStale('banners')).toEqual([{ id: 9 }]);
  });

  it('getStale devuelve undefined si la clave no existe', () => {
    expect(cache.getStale('nada')).toBeUndefined();
  });

  it('keepForOffline extiende la caducidad de lo ya descargado', () => {
    // Sin un `get()` previo: leer una entrada vencida con red la borra, y
    // entonces ya no habría nada que extender.
    cache.set('categories', [{ id: 1 }], -1);

    cache.keepForOffline();

    expect(cache.get('categories')).toEqual([{ id: 1 }]);
  });

  it('una entrada vencida se borra al leerla con red, antes de activar offline', () => {
    cache.set('categories', [{ id: 1 }], -1);

    expect(cache.get('categories')).toBeUndefined();

    // Con la entrada ya evictada, activar el modo no puede resucitarla.
    cache.keepForOffline();
    expect(cache.get('categories')).toBeUndefined();
  });

  it('keepForOffline no resucita entradas ya borradas', () => {
    cache.set('categories', [{ id: 1 }], -1);
    cache.clear();
    cache.keepForOffline();

    expect(cache.get('categories')).toBeUndefined();
  });

  it('invalidate saca la entrada sin tocar el resto', () => {
    cache.set('a', [1], 60_000);
    cache.set('b', [2], 60_000);

    cache.invalidate('a');

    expect(cache.get('a')).toBeUndefined();
    expect(cache.get('b')).toEqual([2]);
  });

  it('clear vacía la caché y size refleja el estado', () => {
    cache.set('a', [1], 60_000);
    cache.set('b', [2], 60_000);
    expect(cache.size).toBe(2);

    cache.clear();

    expect(cache.size).toBe(0);
    expect(cache.get('a')).toBeUndefined();
  });

  // ---------------------------------------------------------------------------
  // Persistencia: la descarga offline tiene que sobrevivir al reinicio.
  // ---------------------------------------------------------------------------

  describe('persistencia', () => {
    it('persist vuelca la caché a disco', async () => {
      cache.set('categories', [1], 60_000);

      await cache.persist();

      expect(storage.store.has('seven:offline-catalog')).toBe(true);
      expect(cache.lastDownloadedAt).not.toBeNull();
    });

    it('restore devuelve 0 si no hay copia guardada', async () => {
      expect(await cache.restore()).toBe(0);
    });

    it('restore recupera las entradas tras un arranque nuevo', async () => {
      cache.set('categories', [1], 60_000);
      cache.set('banners', [2], 60_000);
      await cache.persist();

      TestBed.resetTestingModule();
      boot();
      const fresh = TestBed.inject(DataCacheService);

      expect(await fresh.restore()).toBe(2);
      expect(fresh.get('categories')).toEqual([1]);
      expect(fresh.get('banners')).toEqual([2]);
    });

    it('las entradas restauradas no caducan a los 5 minutos', async () => {
      // TTL vencido antes de guardar: es el caso de "lo descargué hace días".
      cache.set('categories', [1], -1);
      await cache.persist();

      TestBed.resetTestingModule();
      boot();
      const fresh = TestBed.inject(DataCacheService);
      await fresh.restore();

      expect(fresh.get('categories')).toEqual([1]);
    });

    it('hasDownload distingue con y sin copia', async () => {
      expect(await cache.hasDownload()).toBe(false);

      cache.set('a', [1], 60_000);
      await cache.persist();

      expect(await cache.hasDownload()).toBe(true);
    });

    it('persistedSize informa del tamaño en disco', async () => {
      expect(await cache.persistedSize()).toBe(0);

      cache.set('categories', [1, 2, 3], 60_000);
      await cache.persist();

      expect(await cache.persistedSize()).toBeGreaterThan(0);
    });

    it('removePersisted borra disco y memoria', async () => {
      cache.set('a', [1], 60_000);
      await cache.persist();

      await cache.removePersisted();

      expect(storage.store.has('seven:offline-catalog')).toBe(false);
      expect(cache.size).toBe(0);
      expect(cache.lastDownloadedAt).toBeNull();
    });

    it('clear() en memoria no borra la copia de disco', async () => {
      // Son cosas distintas: limpiar caché fuerza recargar; borrar la
      // descarga elimina el paquete offline.
      cache.set('a', [1], 60_000);
      await cache.persist();

      cache.clear();

      expect(await cache.hasDownload()).toBe(true);
    });
  });
});
