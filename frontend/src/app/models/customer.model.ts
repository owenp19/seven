/**
 * Minimal guest data used during the anonymous v1 booking flow.
 */
export interface CustomerPayload {
  id: number;
  name: string;
  phone: string | null;
}

/**
 * Guest that makes a booking. Linked on the backend by phone/email;
 * a full account is planned for v2.
 */
export interface Customer extends CustomerPayload {}

/**
 * Vertical reservable. Cada valor es un `bookable_type` aceptado por
 * `POST /bookings` y devuelto en `bookable.type`; el backend los mapea a
 * `App\Models\{Business,Accommodation,Experience,Tour,Restaurant,Inn}`.
 */
export type BookableType = 'business' | 'accommodation' | 'experience' | 'tour' | 'restaurant' | 'inn';

/** Payload sent when creating a booking. */
export interface CreateBookingPayload {
  bookable_type: BookableType;
  bookable_id: number;
  type: 'gastronomic' | 'accommodation' | 'experience';
  start_at: string;
  end_at?: string | null;
  guests: number;
  special_requests?: string | null;
  currency?: string;
  /** Dispositivo anónimo (sin login) para dirigir notificaciones del booking. */
  install_id?: string | null;
  customer: {
    name: string;
    phone: string;
    email?: string | null;
  };
}