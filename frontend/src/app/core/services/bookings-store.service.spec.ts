import { TestBed } from '@angular/core/testing';

import { of, throwError } from 'rxjs';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { BookingsStoreService, LocalBooking } from './bookings-store.service';
import { BookingService } from '../../services/booking.service';
import { ConnectivityService } from './connectivity.service';
import { NativeService } from './native.service';
import { STORAGE } from '../tokens';
import { Booking } from '../../models';
import type { BookingStatus } from '../../models/booking.model';

const booking = (code: string, status: BookingStatus): Booking =>
  ({ bookingCode: code, status }) as unknown as Booking;

const local = (code: string, status: BookingStatus): LocalBooking =>
  ({ code, status, updatedAt: 0, bookableId: 1, entity: 'Tour' }) as unknown as LocalBooking;

describe('BookingsStoreService polling', () => {
  let getByCode: ReturnType<typeof vi.fn>;
  let notify: ReturnType<typeof vi.fn>;
  let storageSet: ReturnType<typeof vi.fn>;
  let storageRemove: ReturnType<typeof vi.fn>;
  let isOffline: ReturnType<typeof vi.fn>;
  let store: BookingsStoreService;

  beforeEach(async () => {
    getByCode = vi.fn();
    notify = vi.fn(() => Promise.resolve(true));
    storageSet = vi.fn(() => Promise.resolve());
    storageRemove = vi.fn(() => Promise.resolve());
    isOffline = vi.fn(() => false);

    TestBed.configureTestingModule({
      providers: [
        { provide: BookingService, useValue: { getByCode } },
        { provide: NativeService, useValue: { notify } },
        { provide: ConnectivityService, useValue: { isOffline } },
        {
          provide: STORAGE,
          useValue: {
            create: () => Promise.resolve(),
            get: () => Promise.resolve([]),
            set: storageSet,
            remove: storageRemove,
          },
        },
      ],
    });

    store = TestBed.inject(BookingsStoreService);
    await new Promise((resolve) => setTimeout(resolve, 0));
    store.stopMonitoring();
    storageSet.mockClear();
    notify.mockClear();
  });

  /** Dispara el tick privado sin esperar 20s. */
  async function tick(): Promise<void> {
    await (store as unknown as { tick(): Promise<void> }).tick();
  }

  /** Pone una lista en el subject sin pasar por el almacenamiento. */
  function setList(items: LocalBooking[]): void {
    (store as unknown as { listSubject: { next: (v: LocalBooking[]) => void } }).listSubject.next(items);
  }

  it('no escribe en disco ni re-renderiza si el estado no cambia', async () => {
    setList([local('SEVEN-1', 'pending')]);
    getByCode.mockReturnValue(of(booking('SEVEN-1', 'pending')));

    let emissions = 0;
    store.list$.subscribe(() => emissions++);
    const before = emissions;

    await tick();

    expect(getByCode).toHaveBeenCalledTimes(1);
    expect(storageSet).not.toHaveBeenCalled();
    expect(emissions - before).toBe(0);
  });

  it('escribe una sola vez y emite una sola vez cuando cambian varios estados', async () => {
    setList([local('SEVEN-1', 'pending'), local('SEVEN-2', 'pending'), local('SEVEN-3', 'pending')]);

    getByCode.mockImplementation((code: string) => of(booking(code, 'confirmed')));

    let emissions = 0;
    store.list$.subscribe(() => emissions++);
    const before = emissions;

    await tick();

    expect(storageSet).toHaveBeenCalledTimes(1);
    expect(emissions - before).toBe(1);
    expect(store.list.every((item) => item.status === 'confirmed')).toBe(true);
  });

  it('avisa una vez por cada reserva que cambia de estado', async () => {
    setList([local('SEVEN-1', 'pending'), local('SEVEN-2', 'pending')]);

    getByCode.mockImplementation((code: string) => of(booking(code, 'confirmed')));

    await tick();

    expect(notify).toHaveBeenCalledTimes(2);
  });

  it('no toca nada sin conexión', async () => {
    setList([local('SEVEN-1', 'pending')]);
    isOffline.mockReturnValue(true);

    await tick();

    expect(getByCode).not.toHaveBeenCalled();
    expect(storageSet).not.toHaveBeenCalled();
  });

  it('no toca nada con la app en segundo plano', async () => {
    setList([local('SEVEN-1', 'pending')]);

    const spy = vi.spyOn(document, 'visibilityState', 'get').mockReturnValue('hidden');
    await tick();
    spy.mockRestore();

    expect(getByCode).not.toHaveBeenCalled();
  });

  it('consulta varias reservas en paralelo, no en serie', async () => {
    setList([local('A', 'pending'), local('B', 'pending'), local('C', 'pending')]);

    let inFlight = 0;
    let peak = 0;
    getByCode.mockImplementation((code: string) => {
      inFlight++;
      peak = Math.max(peak, inFlight);
      return of(booking(code, 'confirmed'));
    });

    const before = Date.now();
    await tick();
    const elapsed = Date.now() - before;

    expect(peak).toBe(3);
    // En serie con 3 reservas de 40ms seriam >120ms; en paralelo se queda cerca
    // de una sola.
    expect(elapsed).toBeLessThan(120);
  });

  it('retrocede el intervalo tras fallar y lo restablece al recuperarse', async () => {
    const service = store as unknown as {
      consecutiveFailures: number;
      pollDelayMs: number;
    };

    expect(service.pollDelayMs).toBe(20_000);

    getByCode.mockReturnValue(throwError(() => new Error('offline')));
    setList([local('A', 'pending')]);

    await tick();
    expect(service.pollDelayMs).toBe(40_000);

    await tick();
    expect(service.pollDelayMs).toBe(80_000);

    getByCode.mockReturnValue(of(booking('A', 'confirmed')));
    await tick();
    expect(service.pollDelayMs).toBe(20_000);
  });

  it('acota el backoff para no dejar de comprobar nunca', async () => {
    const service = store as unknown as { consecutiveFailures: number; pollDelayMs: number };
    getByCode.mockReturnValue(throwError(() => new Error('offline')));
    setList([local('A', 'pending')]);

    for (let i = 0; i < 20; i++) {
      await tick();
    }

    expect(service.pollDelayMs).toBe(5 * 60_000);
  });

  it('serializa escrituras simultaneas sin perder actualizaciones', async () => {
    setList([local('A', 'pending'), local('B', 'pending')]);

    getByCode.mockImplementation((code: string) => of(booking(code, 'confirmed')));

    // Dos refresh a la vez: el read-modify-write se pisaba y una se perdia.
    await Promise.all([store.refresh('A'), store.refresh('B')]);

    expect(store.statusOf('A')).toBe('confirmed');
    expect(store.statusOf('B')).toBe('confirmed');
  });

  it('devuelve la reserva fresca aunque el estado no haya cambiado', async () => {
    setList([local('A', 'pending')]);
    getByCode.mockReturnValue(of(booking('A', 'pending')));

    const result = await store.refresh('A');

    expect(result?.bookingCode).toBe('A');
    expect(storageSet).not.toHaveBeenCalled();
  });

  it('propaga el fallo de red como null en vez de lanzar', async () => {
    setList([local('A', 'pending')]);
    getByCode.mockReturnValue(throwError(() => new Error('offline')));

    const result = await store.refresh('A');

    expect(result).toBeNull();
    expect(storageSet).not.toHaveBeenCalled();
  });

  it('ignora una reserva que ya no esta guardada', async () => {
    const result = await store.refresh('NO-EXISTE');

    expect(result).toBeNull();
    expect(getByCode).not.toHaveBeenCalled();
  });
});
