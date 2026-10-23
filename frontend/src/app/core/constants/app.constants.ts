/**
 * Business-safe domain constants shared across the app.
 */
export const APP_CONSTANTS = {
  /** LocalStorage keys used by the app. */
  storage: {
    locale: 'seven.locale',
    theme: 'seven.theme',
    customerId: 'seven.customer.id',
    guestToken: 'seven.guest.token',
  },

  /** Booking lifecycle states (mirrors the backend Booking::STATUSES). */
  bookingStatuses: ['pending', 'confirmed', 'cancelled', 'completed'] as const,

  /** Booking verticals supported in v1. */
  bookingTypes: ['gastronomic', 'accommodation', 'experience'] as const,

  /** Default listing page size when the server does not send one. */
  defaultPageSize: 15,
} as const;