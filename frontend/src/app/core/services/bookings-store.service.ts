import { Injectable, inject, signal } from '@angular/core';

import { BehaviorSubject, firstValueFrom } from 'rxjs';

import { BookingStatus } from '../../models/booking.model';
import { Booking } from '../../models';
import type { BookableType } from '../../models/customer.model';
import { BookingService } from '../../services/booking.service';
import { ConnectivityService } from './connectivity.service';
import { NativeService } from './native.service';
import { STORAGE } from '../tokens';

/** Reserva del usuario guardada en el dispositivo (v1 anónima). */
export interface LocalBooking {
  code: string;
  entity: string;
  bookableType: BookableType;
  bookableId: number;
  startAt: string;
  guests: number;
  totalAmount: number;
  currency: string;
  status: BookingStatus;
  updatedAt: number;
}

const STORAGE_KEY = 'seven:bookings';

/** Estados que todavía pueden cambiar y por lo tanto merecen seguir vivos. */
const ACTIVE_STATUSES: readonly BookingStatus[] = ['pending', 'confirmed'];

const BASE_POLL_MS = 20_000;
const MAX_POLL_MS = 5 * 60_000;

/**
 * BookingsStoreService
 *
 * Guarda localmente las reservas del dispositivo (no hay cuenta) y las
 * sigue desde el backend: consulta `GET /bookings/{code}` y cuando el estado
 * cambia dispara una *notificación local dirigida* (solo este teléfono), sin
 * necesitar login ni push global.
 *
 * El seguimiento se mantiene barato a propósito:
 *
 *  - Pausa cuando la app está en segundo plano o sin red. Un `setInterval`
 *    fijo seguía golpeando la API con la pantalla apagada y agotaba la
 *    batería del viajero.
 *  - Retrocede exponencialmente cuando las peticiones fallan, en vez de
 *    reintentar cada 20s para siempre contra un servidor caído.
   *  - Solo escribe en disco y emite cuando un estado cambia de verdad. Antes
   *    `updatedAt` se ponía a `Date.now()` en cada tick, así que la lista nunca
   *    era "igual" y cada pase re-renderizaba la vista y el almacenamiento local
   *    aunque no hubiera cambiado nada.
 *  - Las lecturas van en paralelo, pero las escrituras se serializan: las
 *    peticiones solapaban un read-modify-write sobre la misma lista y la
 *    última que terminaba se comía las actualizaciones anteriores.
 */
@Injectable({ providedIn: 'root' })
export class BookingsStoreService {
  private readonly storage = inject(STORAGE);
  private readonly bookings = inject(BookingService);
  private readonly native = inject(NativeService);
  private readonly connectivity = inject(ConnectivityService);

  private readonly listSubject = new BehaviorSubject<LocalBooking[]>([]);
  readonly list$ = this.listSubject.asObservable();

  /** `true` cuando el almacenamiento local ya se leyó (hay o no reservas). */
  readonly ready = signal(false);

  /** `true` si la lectura inicial del almacenamiento falló. */
  readonly loadFailed = signal(false);

  private initPromise: Promise<void>;
  private timer: ReturnType<typeof setInterval> | null = null;

  /** Fallos consecutivos; define el intervalo actual del backoff. */
  private consecutiveFailures = 0;

  /** Encadena las escrituras para que dos refresh simultáneos no se pisen. */
  private writeQueue: Promise<void> = Promise.resolve();

  private visibilityHooked = false;

  constructor() {
    this.initPromise = this.load();
    void this.initPromise.then(() => this.startMonitoring());
  }

  /** Reintenta la lectura inicial del almacenamiento tras un fallo. */
  retryLoad(): void {
    this.loadFailed.set(false);
    this.ready.set(false);
    this.initPromise = this.load();
    void this.initPromise.then(() => this.startMonitoring());
  }

  get list(): LocalBooking[] {
    return this.listSubject.value;
  }

  /** Persiste una reserva recién creada y arranca su seguimiento. */
  async add(booking: Booking, entity: string, bookableType: LocalBooking['bookableType'], bookableId: number): Promise<void> {
    await this.initPromise;

    // Guarda el secreto que da acceso a esta reserva. El backend solo lo
    // entrega una vez (en la creación), así que este es el único momento en
    // que el dispositivo puede quedárselo para las consultas posteriores.
    if (booking.accessToken) {
      this.native.saveBookingToken(booking.bookingCode, booking.accessToken);
    }

    const existing = this.list.find((item) => item.code === booking.bookingCode);
    const local: LocalBooking = {
      code: booking.bookingCode,
      entity,
      bookableType,
      bookableId,
      startAt: booking.startAt,
      guests: booking.guests,
      totalAmount: booking.totalAmount,
      currency: booking.currency,
      status: booking.status,
      updatedAt: Date.now(),
    };

    const next = existing
      ? this.list.map((item) => (item.code === booking.bookingCode ? local : item))
      : [local, ...this.list];

    await this.persist(next);
    this.listSubject.next(next);
    this.startMonitoring();
  }

  /**
   * Consulta el estado actual de una reserva y notifica si cambió.
   *
   * Devuelve la reserva del servidor (o `null` si falló o no existe) para que
   * quien llama pueda mostrar el dato fresco aunque el estado sea el mismo.
   */
  async refresh(code: string): Promise<Booking | null> {
    const stored = this.list.find((item) => item.code === code);
    if (!stored) {
      return null;
    }

    const fresh = await this.fetch(code);
    if (!fresh) {
      return null;
    }

    const results = new Map([[code, fresh]]);
    await this.applyResults(results, { notify: true });
    return fresh;
  }

  /** Estado del seguimiento de una reserva concreta. */
  statusOf(code: string): BookingStatus | undefined {
    return this.list.find((item) => item.code === code)?.status;
  }

  /**
   * Vacía la lista local de reservas y detiene el seguimiento.
   *
   * La usan dos caminos: el borrado de datos del usuario (que antes cancela
   * cada reserva en el servidor) y el withdrawal del consentimiento. Borra
   * también el estado en memoria para que "Mis reservas" no siga mostrando
   * filas que ya no existen.
   */
  async eraseLocal(): Promise<void> {
    await this.initPromise;

    this.stopMonitoring();
    await this.storage.remove(STORAGE_KEY);
    this.listSubject.next([]);
  }

  /** Seguimiento en vivo de todas las reservas activas (pending/confirmed). */
  startMonitoring(): void {
    this.hookVisibility();

    const active = this.activeCount();
    if (active === 0) {
      this.stopMonitoring();
      return;
    }

    if (this.timer === null) {
      this.scheduleTick();
    }
  }

  stopMonitoring(): void {
    if (this.timer !== null) {
      clearTimeout(this.timer);
      this.timer = null;
    }
  }

  /** Última llamada a la API: 20s en calma, hasta 5min tras fallos seguidos. */
  private get pollDelayMs(): number {
    return Math.min(BASE_POLL_MS * 2 ** this.consecutiveFailures, MAX_POLL_MS);
  }

  private scheduleTick(): void {
    this.timer = setTimeout(() => {
      this.timer = null;
      void this.tick().finally(() => this.startMonitoring());
    }, this.pollDelayMs);
  }

  private activeCount(): number {
    return this.list.filter((item) => ACTIVE_STATUSES.includes(item.status)).length;
  }

  /** Una app en segundo plano no necesita saber el estado cada 20 segundos. */
  private get isBackgrounded(): boolean {
    return typeof document !== 'undefined' && document.visibilityState === 'hidden';
  }

  private hookVisibility(): void {
    if (this.visibilityHooked || typeof document === 'undefined') {
      return;
    }
    this.visibilityHooked = true;
    document.addEventListener('visibilitychange', () => {
      if (!this.isBackgrounded) {
        // Al volver a primer plano se consulta de inmediato en vez de esperar
        // al resto del intervalo.
        this.stopMonitoring();
        this.startMonitoring();
      }
    });
  }

  private async tick(): Promise<void> {
    if (this.isBackgrounded || this.connectivity.isOffline()) {
      return;
    }

    const codes = this.list
      .filter((item) => ACTIVE_STATUSES.includes(item.status))
      .map((item) => item.code);

    if (codes.length === 0) {
      return;
    }

    // En paralelo: varias reservas ya no cuestan la suma de sus latencias.
    const fetched = await Promise.all(codes.map((code) => this.fetch(code)));
    const results = new Map<string, Booking>();
    for (const booking of fetched) {
      if (booking) {
        results.set(booking.bookingCode, booking);
      }
    }

    if (results.size > 0) {
      this.consecutiveFailures = 0;
    } else {
      this.consecutiveFailures += 1;
    }

    await this.applyResults(results, { notify: true });
  }

  /** Red de un solo codigo; `null` cuando falla o el backend no responde. */
  private async fetch(code: string): Promise<Booking | null> {
    try {
      return await firstValueFrom(this.bookings.getByCode(code));
    } catch {
      return null;
    }
  }

  /**
   * Aplica los estados nuevos en una sola escritura.
   *
   * Si ningún estado cambio no se toca el disco ni se emite, que es lo que
   * hacia que el polling re-renderizara la vista cada 20s sin motivo.
   */
  private applyResults(results: Map<string, Booking>, options: { notify: boolean }): Promise<void> {
    this.writeQueue = this.writeQueue.then(() => this.applyResultsNow(results, options));
    return this.writeQueue;
  }

  private async applyResultsNow(results: Map<string, Booking>, options: { notify: boolean }): Promise<void> {
    const now = Date.now();
    const changed: string[] = [];

    for (const [code, fresh] of results) {
      const stored = this.list.find((item) => item.code === code);
      if (stored && stored.status !== fresh.status) {
        changed.push(code);
      }
    }

    if (options.notify) {
      for (const code of changed) {
        const stored = this.list.find((item) => item.code === code);
        const fresh = results.get(code);
        if (!stored || !fresh) {
          continue;
        }
        await this.native.notify(
          stored.entity,
          this.messageFor(stored.entity, fresh.status, code),
          2000 + stored.bookableId,
        );
      }
    }

    if (changed.length === 0) {
      return;
    }

    const next = this.list.map((item) => {
      const fresh = results.get(item.code);
      return fresh && fresh.status !== item.status
        ? { ...item, status: fresh.status, updatedAt: now }
        : item;
    });

    await this.persist(next);
    this.listSubject.next(next);
  }

  private messageFor(entity: string, status: BookingStatus, code: string): string {
    switch (status) {
      case 'confirmed':
        return `${entity} fue confirmada. Código ${code}. ¡Buen viaje!`;
      case 'cancelled':
        return `Lo sentimos, ${entity.toLowerCase()} fue cancelada. Puedes reservar fechas nuevas.`;
      case 'completed':
        return `${entity} finalizó con éxito. Cuéntanos cómo la pasaste.`;
      default:
        return `${entity} sigue en espera de confirmación.`;
    }
  }

  private async persist(list: LocalBooking[]): Promise<void> {
    await this.storage.set(STORAGE_KEY, list);
  }

  private async load(): Promise<void> {
    try {
      await this.storage.create();
      const stored = ((await this.storage.get(STORAGE_KEY)) as LocalBooking[] | null) ?? [];
      this.listSubject.next(stored);
      this.ready.set(true);
    } catch {
      this.loadFailed.set(true);
    }
  }
}