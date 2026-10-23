import { TestBed } from '@angular/core/testing';

import { of, throwError } from 'rxjs';

import { DataPrivacyService } from './data-privacy.service';
import type { LocalBooking } from './bookings-store.service';
import { APP_CONFIG, STORAGE } from '../tokens';
import type { AppConfig } from '../config/app-config.interface';
import { BookingService } from '../../services/booking.service';
import { BookingsStoreService } from './bookings-store.service';
import { DataCacheService } from './data-cache.service';
import { FavoritesService } from './favorites.service';
import { NativeService } from './native.service';

const testConfig: AppConfig = {
  production: false,
  appName: 'SEVEN',
  appVersion: '0.1.0',
  apiUrl: 'http://127.0.0.1:8000/api',
  apiVersion: 'v1',
  defaultCurrency: 'COP',
  contact: { privacyEmail: 'privacidad@seven.app' },
  maxPerPage: 50,
  features: { explore: true, bookings: true, payments: false, reviews: false, accounts: false },
};

/** Doubles de @ionic/storage en memoria: lo justo que usa el servicio. */
class FakeStorage {
  readonly store = new Map<string, unknown>();
  cleared = false;
  createFailures = 0;

  async create(): Promise<FakeStorage> {
    if (this.createFailures > 0) {
      this.createFailures -= 1;
      throw new Error('driver no disponible');
    }
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

  async keys(): Promise<string[]> {
    return [...this.store.keys()];
  }

  async clear(): Promise<void> {
    this.store.clear();
    this.cleared = true;
  }
}

function localBooking(code: string): LocalBooking {
  return {
    code,
    entity: 'Hotel Casa del Mar',
    bookableType: 'accommodation',
    bookableId: 1,
    startAt: '2026-10-01T15:00:00Z',
    guests: 2,
    totalAmount: 480000,
    currency: 'COP',
    status: 'pending',
    updatedAt: 0,
  };
}

describe('DataPrivacyService', () => {
  let service: DataPrivacyService;
  let storage: FakeStorage;
  let bookings: { list: LocalBooking[]; eraseLocal: ReturnType<typeof vi.fn> };
  let favorites: {
    favoritesSnapshot: ReturnType<typeof vi.fn>;
    recentSnapshot: ReturnType<typeof vi.fn>;
    eraseLocal: ReturnType<typeof vi.fn>;
  };
  let bookingApi: { cancel: ReturnType<typeof vi.fn> };
  let cache: { clear: ReturnType<typeof vi.fn> };
  let native: {
    installId: ReturnType<typeof vi.fn>;
    cancelNotifications: ReturnType<typeof vi.fn>;
    clearBookingTokens: ReturnType<typeof vi.fn>;
  };

  beforeEach(() => {
    storage = new FakeStorage();
    bookings = { list: [], eraseLocal: vi.fn().mockResolvedValue(undefined) };
    favorites = {
      favoritesSnapshot: vi.fn().mockReturnValue([]),
      recentSnapshot: vi.fn().mockReturnValue([]),
      eraseLocal: vi.fn().mockResolvedValue(undefined),
    };
    bookingApi = { cancel: vi.fn().mockReturnValue(of({})) };
    cache = { clear: vi.fn() };
    native = {
      installId: vi.fn().mockReturnValue('install-uuid'),
      cancelNotifications: vi.fn().mockResolvedValue(undefined),
      clearBookingTokens: vi.fn(),
    };

    localStorage.clear();

    TestBed.configureTestingModule({
      providers: [
        { provide: APP_CONFIG, useValue: testConfig },
        { provide: STORAGE, useValue: storage },
        { provide: BookingsStoreService, useValue: bookings },
        { provide: BookingService, useValue: bookingApi },
        { provide: FavoritesService, useValue: favorites },
        { provide: DataCacheService, useValue: cache },
        { provide: NativeService, useValue: native },
      ],
    });

    service = TestBed.inject(DataPrivacyService);
  });

  afterEach(() => {
    localStorage.clear();
    vi.restoreAllMocks();
  });

  describe('inventory', () => {
    it('refleja las claves y los conteos reales', async () => {
      localStorage.setItem('seven_language', 'en');
      storage.store.set('seven:favorites', [{ type: 'tour', id: 1, slug: 'a' }]);
      storage.store.set('seven:guest', { name: 'Ana', phone: '300123' });
      bookings.list = [localBooking('AAA111')];
      favorites.favoritesSnapshot.mockReturnValue([{ type: 'tour', id: 1, slug: 'a' }]);
      favorites.recentSnapshot.mockReturnValue([{ type: 'tour', id: 1, slug: 'a', name: 'A', at: 1 }]);

      const data = await service.inventory();

      expect(data.installId).toBe('install-uuid');
      expect(data.deviceKeys).toEqual(['seven_language']);
      expect(data.storageKeys).toEqual(['seven:favorites', 'seven:guest']);
      expect(data.favoriteCount).toBe(1);
      expect(data.recentCount).toBe(1);
      expect(data.bookingCount).toBe(1);
      expect(data.bookingCodes).toEqual(['AAA111']);
      expect(data.guestName).toBe('Ana');
      expect(data.guestPhone).toBe('300123');
      expect(data.language).toBe('en');
    });

    it('omite claves ajenas a la app', async () => {
      localStorage.setItem('otro_sitio', 'x');
      localStorage.setItem('seven_language', 'pt');

      const data = await service.inventory();

      expect(data.deviceKeys).toEqual(['seven_language']);
    });

    it('no lanza si el driver de almacenamiento falla', async () => {
      storage.createFailures = 5;

      const data = await service.inventory();

      expect(data.storageKeys).toEqual([]);
      expect(data.guestName).toBe('');
      expect(data.guestPhone).toBe('');
    });

    it('devuelve un inventario vacío sin datos', async () => {
      const data = await service.inventory();

      expect(data.deviceKeys).toEqual([]);
      expect(data.storageKeys).toEqual([]);
      expect(data.bookingCount).toBe(0);
      expect(data.bookingCodes).toEqual([]);
    });
  });

  describe('eraseDevice', () => {
    it('borra las claves locales, el almacenamiento y las notificaciones', async () => {
      localStorage.setItem('seven_language', 'pt');
      localStorage.setItem('seven_reduce_motion', '1');
      localStorage.setItem('seven_install_id', 'abc');
      localStorage.setItem('seven_welcome_completed', '1');
      localStorage.setItem('seven_booking_tokens', '{"SEV-1":"tok"}');
      storage.store.set('seven:favorites', []);

      const erased = await service.eraseDevice();

      expect(erased).toBe(true);
      expect(localStorage.getItem('seven_language')).toBeNull();
      expect(localStorage.getItem('seven_reduce_motion')).toBeNull();
      expect(localStorage.getItem('seven_install_id')).toBeNull();
      expect(localStorage.getItem('seven_welcome_completed')).toBeNull();
      expect(localStorage.getItem('seven_booking_tokens')).toBeNull();
      expect(bookings.eraseLocal).toHaveBeenCalledOnce();
      expect(favorites.eraseLocal).toHaveBeenCalledOnce();
      expect(cache.clear).toHaveBeenCalledOnce();
      expect(native.cancelNotifications).toHaveBeenCalledOnce();
      expect(native.clearBookingTokens).toHaveBeenCalledOnce();
    });

    it('reporta que no quedó rastro y no toca el servidor', async () => {
      const erased = await service.eraseDevice();

      expect(erased).toBe(true);
      expect(bookingApi.cancel).not.toHaveBeenCalled();
    });

    it('termina igual si el driver de almacenamiento falla', async () => {
      storage.createFailures = 10;

      await expect(service.eraseDevice()).resolves.toBe(true);
      expect(bookings.eraseLocal).toHaveBeenCalledOnce();
    });
  });

  describe('eraseEverything', () => {
    it('cancela cada reserva en el servidor antes de limpiar el dispositivo', async () => {
      bookings.list = [localBooking('AAA111'), localBooking('BBB222')];

      const result = await service.eraseEverything();

      expect(bookingApi.cancel).toHaveBeenCalledTimes(2);
      expect(bookingApi.cancel).toHaveBeenNthCalledWith(1, 'AAA111');
      expect(bookingApi.cancel).toHaveBeenNthCalledWith(2, 'BBB222');
      expect(result).toEqual({ cancelledBookings: 2, failedBookings: 0, deviceErased: true });
    });

    it('cuenta las reservas que el servidor no dejó cancelar, sin abortar el resto', async () => {
      bookings.list = [localBooking('AAA111'), localBooking('BBB222'), localBooking('CCC333')];
      bookingApi.cancel
        .mockReturnValueOnce(of({}))
        .mockReturnValueOnce(throwError(() => new Error('sin red')))
        .mockReturnValueOnce(of({}));

      const result = await service.eraseEverything();

      expect(result.cancelledBookings).toBe(2);
      expect(result.failedBookings).toBe(1);
      expect(result.deviceErased).toBe(true);
      expect(bookings.eraseLocal).toHaveBeenCalledOnce();
    });

    it('limpia el dispositivo aunque todas las cancelaciones fallen', async () => {
      bookings.list = [localBooking('AAA111')];
      bookingApi.cancel.mockReturnValue(throwError(() => new Error('offline')));

      const result = await service.eraseEverything();

      expect(result.failedBookings).toBe(1);
      expect(result.deviceErased).toBe(true);
    });

    it('no llama al servidor cuando no hay reservas guardadas', async () => {
      const result = await service.eraseEverything();

      expect(bookingApi.cancel).not.toHaveBeenCalled();
      expect(result).toEqual({ cancelledBookings: 0, failedBookings: 0, deviceErased: true });
    });
  });

  describe('deletionRequestLink', () => {
    it('apunta al inbox de privacidad con el install_id en el cuerpo', () => {
      const link = service.deletionRequestLink('install-uuid');
      const [target, query] = link.split('?');

      expect(target).toBe('mailto:privacidad@seven.app');
      expect(decodeURIComponent(query)).toContain('install-uuid');
      expect(decodeURIComponent(query)).toContain('install_id');
    });

    it('ofrece marcar los cuatro derechos, para no tener que escribir la solicitud', () => {
      const body = decodeURIComponent(service.deletionRequestLink('x').split('body=')[1]);

      expect(body).toContain('Acceder a mis datos');
      expect(body).toContain('Eliminar mis datos');
    });
  });
});
