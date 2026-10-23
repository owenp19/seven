export const environment = {
  production: true,
  appName: 'SEVEN',
  appVersion: '0.1.0',

  /**
   * Production API host.
   *
   * STILL A PLACEHOLDER: the production domain has not been provisioned yet.
   * `assertApiConfigured()` (wired in app.config.ts) throws on boot for this
   * exact host, so the release cannot ship until a real value lands here.
   * Keep the `/api` suffix — `ApiClientService` appends `apiVersion`, and the
   * backend registers the routes under `/api/v1`.
   */
  apiUrl: 'https://api.seven.app/api',
  apiVersion: 'v1',

  /** Keep in sync with `max_per_page` in backend/config/api.php. */
  maxPerPage: 50,

  defaultCurrency: 'COP',

  contact: {
    privacyEmail: 'privacidad@seven.app',
  },

  features: {
    explore: true,
    bookings: true,
    payments: false,
    reviews: false,
    accounts: false,
  },
};