import { Injectable, inject } from '@angular/core';

import { Observable, map } from 'rxjs';

import { Booking } from '../models';
import { BookingPayload } from '../models/booking.model';
import type { CreateBookingPayload } from '../models/customer.model';
import { Paginated } from '../interfaces';
import { ApiClientService } from '../core/services/api-client.service';
import { NativeService } from '../core/services/native.service';

/**
 * Booking operations: create, look up by code, cancel and list. v1 is
 * anonymous, so lookups use the generated booking code plus the secret
 * `booking_access_token` the backend handed out when the booking was
 * created: the API client sends it as `X-Booking-Token` (no login). The
 * device identity (`X-Install-Id`) is attached by the API client on every
 * call.
 */
@Injectable({ providedIn: 'root' })
export class BookingService {
  private readonly api = inject(ApiClientService);
  private readonly native = inject(NativeService);

  /** Create a tour/gastronomic/accommodation/experience reservation. */
  create(payload: CreateBookingPayload): Observable<Booking> {
    return this.api
      .post<BookingPayload>('bookings', { ...payload })
      .pipe(map((b) => Booking.fromApi(b)));
  }

  /**
   * Find a booking by its human-friendly code (SEV-XXXXXX).
   *
   * Requiere el token de la reserva: el código solo ya no es una credencial,
   * y el backend responde 403 si no se adjunta el `X-Booking-Token`.
   */
  getByCode(code: string): Observable<Booking> {
    return this.api
      .get<BookingPayload>(`bookings/${code}`, undefined, this.native.bookingToken(code) ?? undefined)
      .pipe(map((b) => Booking.fromApi(b)));
  }

  /** Update status. The public API only accepts `cancelled`. */
  updateStatus(code: string, status: string): Observable<Booking> {
    return this.api
      .put<BookingPayload>(`bookings/${code}`, { status }, this.native.bookingToken(code) ?? undefined)
      .pipe(map((b) => Booking.fromApi(b)));
  }

  /** Cancel a reservation. */
  cancel(code: string): Observable<Booking> {
    return this.api
      .delete<BookingPayload>(`bookings/${code}`, this.native.bookingToken(code) ?? undefined)
      .pipe(map((b) => Booking.fromApi(b)));
  }

  /**
   * Reservations created from this device.
   *
   * The API scopes the list to the caller's `X-Install-Id` and answers 403
   * without it, so there is no customer id or phone to pass: that query was
   * an enumeration oracle for booking codes belonging to other travellers.
   */
  listMine(page = 1): Observable<Paginated<Booking>> {
    return this.api
      .getPaginated<BookingPayload>('bookings', { page })
      .pipe(map((paginated) => new Paginated(paginated.items.map((b) => Booking.fromApi(b)), paginated.meta)));
  }
}