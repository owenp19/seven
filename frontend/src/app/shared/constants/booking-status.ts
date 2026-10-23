import type { TranslationKey } from '../../core/services/translation.service';
import type { BookingStatus } from '../../models/booking.model';
import type { BookableType } from '../../models/customer.model';

/**
 * Presentación de los estados de reserva del backend.
 *
 * El backend solo conoce `pending | confirmed | cancelled | completed`
 * (`Booking::STATUSES`). Este módulo traduce cada uno a su etiqueta e icono
 * de UI en un único lugar: así la página de reservas, la tarjeta y el
 * formulario de reserva no repiten el mismo `if/else` cada uno por su lado.
 */
export interface BookingStatusUi {
  /** Icono de Ionicons (kebab-case, ya registrado en `register-icons`). */
  readonly icon: string;
  /** Clave de traducción de la etiqueta visible. */
  readonly labelKey: TranslationKey;
  /**
   * `true` mientras la reserva sigue viva (esperando o confirmada): son las
   * únicas que tiene sentido volver a consultar contra el backend.
   */
  readonly live: boolean;
}

export const BOOKING_STATUS_UI: Record<BookingStatus, BookingStatusUi> = {
  pending: { icon: 'time-outline', labelKey: 'bookingStatus.pending', live: true },
  confirmed: { icon: 'checkmark-circle', labelKey: 'bookingStatus.confirmed', live: true },
  cancelled: { icon: 'close-circle', labelKey: 'bookingStatus.cancelled', live: false },
  completed: { icon: 'checkmark-circle-outline', labelKey: 'bookingStatus.completed', live: false },
};

/** ¿La reserva sigue viva (puede cambiar de estado en el servidor)? */
export function isLiveBooking(status: BookingStatus): boolean {
  return BOOKING_STATUS_UI[status].live;
}

const BOOKABLE_TYPE_ICON: Record<BookableType, string> = {
  tour: 'map-outline',
  restaurant: 'restaurant-outline',
  inn: 'bed-outline',
  accommodation: 'home-outline',
  experience: 'sparkles-outline',
  business: 'business-outline',
};

const BOOKABLE_TYPE_LABEL: Record<BookableType, TranslationKey> = {
  tour: 'bookings.typeTour',
  restaurant: 'bookings.typeRestaurant',
  inn: 'bookings.typeInn',
  accommodation: 'bookings.typeAccommodation',
  experience: 'bookings.typeExperience',
  business: 'bookings.typeBusiness',
};

/** Icono que representa el vertical reservado (sin foto: el contrato local no la trae). */
export function bookableTypeIcon(type: BookableType): string {
  return BOOKABLE_TYPE_ICON[type];
}

/** Clave de traducción del nombre del vertical reservado. */
export function bookableTypeLabelKey(type: BookableType): TranslationKey {
  return BOOKABLE_TYPE_LABEL[type];
}
