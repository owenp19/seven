export const environment = {
  production: false,
  appName: 'SEVEN',
  appVersion: '0.1.0',

  /**
   * Base URL of the Laravel REST API.
   * The API client appends the version segment automatically.
   *
   * Usar 127.0.0.1 (no "localhost"): `php artisan serve` vincula solo IPv4
   * 127.0.0.1; en navegadores "localhost" puede resolver a ::1 (IPv6) y
   * esos request fallan (ERR_NETWORK_CHANGED / "sin datos en la Home").
   */
  apiUrl: 'http://127.0.0.1:8000/api',
  apiVersion: 'v1',

  /** Keep in sync with `max_per_page` in backend/config/api.php. */
  maxPerPage: 50,

  defaultCurrency: 'COP',

  /**
   * Public contact channels. Replace `privacyEmail` with the real
   * monitored inbox (privacy@ / datos@) before publishing: the Privacy
   * Policy points travelers there for access and deletion requests.
   */
  contact: {
    privacyEmail: 'privacidad@seven.app',
  },

  /**
   * Feature flags let the team ship safe builds while migrations to
   * new verticals (payments, reviews, accounts) are still in progress.
   */
  features: {
    explore: true,
    bookings: true,
    payments: false,
    reviews: false,
    accounts: false,
  },
};