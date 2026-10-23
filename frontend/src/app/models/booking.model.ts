/**
 * Booking lifecycle states and verticals (mirror of the backend
 * `Booking::STATUSES` and `Booking::TYPES`).
 */
export type BookingType = 'gastronomic' | 'accommodation' | 'experience';

export type BookingStatus = 'pending' | 'confirmed' | 'cancelled' | 'completed';

export interface BookingPayload {
  id: number;
  booking_code: string;
  type: BookingType;
  status: BookingStatus;
  bookable?: Record<string, unknown> | null;
  start_at: string;
  end_at: string | null;
  guests: number;
  total_amount: number;
  currency: string;
  special_requests: string | null;
  customer?: { id: number; name: string; phone: string | null } | null;
  created_at: string | null;
  /**
   * Secreto único por reserva que el backend entrega UNA sola vez (en la
   * respuesta de creación). Permite leer/cancelar la reserva más adelante
   * sin login; no debe mostrarse ni almacenarse en logs.
   */
  booking_access_token?: string | null;
}

/**
 * A reservation made by a guest (gastronomic, accommodation or
 * experience). v1 is anonymous: the booking is tracked via a booking
 * code instead of a mandatory account.
 */
export class Booking {
  constructor(
    public readonly id: number,
    public readonly bookingCode: string,
    public readonly type: BookingType,
    public readonly status: BookingStatus,
    public readonly startAt: string,
    public readonly endAt: string | null,
    public readonly guests: number,
    public readonly totalAmount: number,
    public readonly currency: string,
    public readonly specialRequests: string | null,
    public readonly createdAt: string | null,
    public readonly bookable?: Record<string, unknown> | null,
    public readonly accessToken?: string | null,
  ) {}

  static fromApi(raw: BookingPayload): Booking {
    return new Booking(
      raw.id,
      raw.booking_code,
      raw.type,
      raw.status,
      raw.start_at,
      raw.end_at,
      raw.guests,
      raw.total_amount,
      raw.currency,
      raw.special_requests,
      raw.created_at,
      raw.bookable,
      raw.booking_access_token ?? null,
    );
  }
}