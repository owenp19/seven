/**
 * Runtime configuration contract resolved through the APP_CONFIG token.
 *
 * The `environment` object is the concrete implementation; having it
 * behind an InjectionToken keeps services framework-agnostic and easy
 * to fake in tests.
 */
export interface AppConfig {
  production: boolean;
  appName: string;
  appVersion: string;
  apiUrl: string;
  apiVersion: string;
  defaultCurrency: string;

  /**
   * Public contact channels. `privacyEmail` is the monitored inbox behind
   * the Privacy Policy: access/deletion requests sent there are the
   * fallback when the traveler cannot use the in-app self-service erase.
   */
  contact: {
    privacyEmail: string;
  };

  /**
   * Largest page size the API accepts (mirrors `max_per_page` in the
   * backend `config/api.php`). Used when a service needs the whole
   * collection rather than a single page.
   */
  maxPerPage: number;
  features: {
    explore: boolean;
    bookings: boolean;
    payments: boolean;
    reviews: boolean;
    accounts: boolean;
  };
}