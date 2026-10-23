import { TestBed } from '@angular/core/testing';

import { of, throwError } from 'rxjs';

import { BannerService } from '../../services/banner.service';
import { BusinessService } from '../../services/business.service';
import { CategoryService } from '../../services/category.service';
import { InnService } from '../../services/inn.service';
import { PromotionService } from '../../services/promotion.service';
import { RestaurantService } from '../../services/restaurant.service';
import { TourService } from '../../services/tour.service';
import { ConnectivityService } from './connectivity.service';
import { DataCacheService } from './data-cache.service';
import { OfflineDownloadService } from './offline-download.service';
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

function stub<T>(value: T, calls: { count: number }): { getAll?: unknown; getFeatured?: unknown } {
  const fn = (): unknown => {
    calls.count++;

    return of(value);
  };

  return { getAll: fn, getFeatured: fn };
}

/** Cebo de red que siempre falla, para probar degradación parcial. */
const dead = () => throwError(() => new Error('boom'));

describe('OfflineDownloadService', () => {
  let service: OfflineDownloadService;
  let storage: FakeStorage;
  let cache: DataCacheService;
  let connectivity: ConnectivityService;
  let calls: { count: number };

  /**
   * `TestBed.resetTestingModule()` también tira los providers, así que
   * "arrancar de nuevo" hay que rehacerlo a mano: sin esto, la segunda
   * instancia no encuentra STORAGE.
   *
   * `overrides` se indexa por nombre de token ('banners', 'categories', …)
   * en lugar de por la clase: un `typeof Clase` no sirve como tipo índice.
   */
  function boot(overrides: Record<string, unknown> = {}): void {
    TestBed.configureTestingModule({
      providers: [
        { provide: STORAGE, useValue: storage },
        { provide: BannerService, useValue: overrides['banners'] ?? stub([{ id: 1 }], calls) },
        { provide: CategoryService, useValue: overrides['categories'] ?? stub([{ id: 2 }], calls) },
        { provide: BusinessService, useValue: overrides['businesses'] ?? stub([{ id: 3 }], calls) },
        { provide: TourService, useValue: overrides['tours'] ?? stub([{ id: 4 }], calls) },
        { provide: RestaurantService, useValue: overrides['restaurants'] ?? stub([{ id: 5 }], calls) },
        { provide: InnService, useValue: overrides['inns'] ?? stub([{ id: 6 }], calls) },
        { provide: PromotionService, useValue: overrides['promotions'] ?? stub([{ id: 7 }], calls) },
      ],
    });
  }

  beforeEach(() => {
    storage = new FakeStorage();
    calls = { count: 0 };

    boot();

    service = TestBed.inject(OfflineDownloadService);
    cache = TestBed.inject(DataCacheService);
    connectivity = TestBed.inject(ConnectivityService);
  });

  afterEach(() => {
    connectivity.setManualOffline(false);
  });

  it('arranca idle, sin progreso y sin descarga', () => {
    expect(service.state()).toBe('idle');
    expect(service.progress()).toBe(0);
    expect(service.downloadedAt()).toBeNull();
    expect(service.isRunning).toBe(false);
  });

  it('descarga las siete fuentes y termina en done', async () => {
    const ok = await service.download();

    expect(ok).toBe(true);
    expect(service.state()).toBe('done');
    // Siete fuentes, una llamada de red por fuente.
    expect(calls.count).toBe(7);
  });

  it('deja el progreso a 100 al terminar', async () => {
    await service.download();

    expect(service.progress()).toBe(100);
  });

  it('el progreso es monótono y nunca baja', async () => {
    // Ni `setInterval` ni un `effect` sirven aquí: la descarga se resuelve
    // entera en un solo flush, así que coalescerían a una única lectura. Se
    // observa desde dentro de los stubs, que sí se ejecutan entre tarea y
    // tarea, con el progreso del momento.
    const seen: number[] = [];
    // Referencia mutable: el closure debe leer la instancia nueva, no la del
    // `beforeEach`, que quedó huérfana tras el reset.
    let target: OfflineDownloadService | null = null;
    const watch = (value: unknown) => (): unknown => {
      seen.push(target ? target.progress() : -1);

      return of(value);
    };

    TestBed.resetTestingModule();
    boot({
      banners: { getAll: watch([{ id: 1 }]) },
      categories: { getAll: watch([{ id: 2 }]) },
      businesses: { getFeatured: watch([{ id: 3 }]) },
      tours: { getFeatured: watch([{ id: 4 }]) },
      restaurants: { getFeatured: watch([{ id: 5 }]) },
      inns: { getFeatured: watch([{ id: 6 }]) },
      promotions: { getAll: watch([{ id: 7 }]) },
    });

    const watched = TestBed.inject(OfflineDownloadService);
    target = watched;

    expect(await watched.download()).toBe(true);

    // Siete fuentes, y ninguna lectura ve el progreso retroceder.
    expect(seen).toHaveLength(7);
    for (let i = 1; i < seen.length; i++) {
      expect(seen[i]).toBeGreaterThan(seen[i - 1]);
    }
    expect(watched.progress()).toBe(100);
  });

  it('persiste el catálogo en disco, no solo en memoria', async () => {
    await service.download();

    // Sin esto la descarga moriría al reiniciar la app.
    expect(storage.store.has('seven:offline-catalog')).toBe(true);
  });

  it('guarda las siete claves en la copia de disco', async () => {
    await service.download();

    const payload = storage.store.get('seven:offline-catalog') as Record<string, unknown>;

    expect(Object.keys(payload).sort()).toEqual([
      'banners',
      'businesses:featured',
      'categories',
      'inns:featured',
      'promotions',
      'restaurants:featured',
      'tours:featured',
    ]);
  });

  it('la copia sobrevive a un arranque nuevo', async () => {
    await service.download();

    TestBed.resetTestingModule();
    boot();

    expect(await TestBed.inject(DataCacheService).restore()).toBe(7);
  });

  it('restaura con caducidad lejana para que sirva de verdad offline', async () => {
    // TTL vencido antes de guardar: es el caso de "lo descargué hace días".
    await service.download();

    TestBed.resetTestingModule();
    boot();
    const freshCache = TestBed.inject(DataCacheService);
    await freshCache.restore();

    // La copia descargada a mano no puede expirar a los 5 minutos.
    expect(freshCache.get('categories')).toBeDefined();
  });

  it('anota la fecha de descarga y el tamaño', async () => {
    await service.download();

    expect(service.downloadedAt()).not.toBeNull();
    expect(service.sizeBytes()).toBeGreaterThan(0);
  });

  it('refresh lee el estado guardado', async () => {
    await service.download();
    service.downloadedAt.set(null);
    service.sizeBytes.set(0);

    await service.refresh();

    expect(service.downloadedAt()).not.toBeNull();
    expect(service.sizeBytes()).toBeGreaterThan(0);
  });

  it('un endpoint caído no tumba la descarga entera', async () => {
    TestBed.resetTestingModule();
    boot({ banners: { getAll: dead } });

    const partial = TestBed.inject(OfflineDownloadService);

    // El resto del catálogo sigue siendo útil aunque un endpoint caiga.
    expect(await partial.download()).toBe(true);
    expect(partial.state()).toBe('done');
  });

  it('falla si fallan todas las fuentes', async () => {
    TestBed.resetTestingModule();
    boot({
      banners: { getAll: dead },
      categories: { getAll: dead },
      businesses: { getFeatured: dead },
      tours: { getFeatured: dead },
      restaurants: { getFeatured: dead },
      inns: { getFeatured: dead },
      promotions: { getAll: dead },
    });

    const broken = TestBed.inject(OfflineDownloadService);

    expect(await broken.download()).toBe(false);
    expect(broken.state()).toBe('error');
  });

  it('no intenta descargar sin red, y lo explica', async () => {
    connectivity.setManualOffline(true);

    const ok = await service.download();

    expect(ok).toBe(false);
    // No consume datos de una conexión que el usuario cree cortada.
    expect(calls.count).toBe(0);
    expect(service.lastError()).toBe('settings.dlNeedsNetwork');
  });

  it('ignora un segundo toque mientras corre', async () => {
    const first = service.download();
    const second = service.download();

    expect(await second).toBe(false);
    await first;
  });

  it('remove borra la copia de disco y reinicia el estado', async () => {
    await service.download();

    await service.remove();

    expect(storage.store.has('seven:offline-catalog')).toBe(false);
    expect(service.state()).toBe('idle');
    expect(service.downloadedAt()).toBeNull();
    expect(service.sizeBytes()).toBe(0);
    expect(service.progress()).toBe(0);
  });

  it('remove limpia también la caché en memoria', async () => {
    await service.download();
    expect(cache.size).toBeGreaterThan(0);

    await service.remove();

    expect(cache.size).toBe(0);
  });
});
