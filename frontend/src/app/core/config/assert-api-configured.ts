import type { AppConfig } from './app-config.interface';

/**
 * Hosts that are guesses, not deployments.
 *
 * `environment.prod.ts` shipped `api.seven.app` as an illustrative value
 * while the real production domain was still undecided. Left in place it
 * is worse than a crash: the app builds, installs, and every screen comes
 * up empty because no request ever resolves. Listing the host here turns
 * that silent failure into a boot-time error the team has to clear.
 */
const UNCONFIRMED_HOSTS = ['api.seven.app'];

/**
 * Fails the production boot when `apiUrl` still points at a placeholder.
 *
 * Fail-closed on purpose: a wrong host is indistinguishable from an
 * outage in the field (empty lists, "sin datos" on Home, bookings that
 * never post), and it is reported as a content bug rather than a config
 * one. Throwing at bootstrap keeps the mistake in CI instead.
 *
 * No-op outside production so the dev host and the test doubles are
 * unaffected, and a deliberate `localhost` can still be shipped for
 * internal QA builds by pointing at a real loopback address.
 */
export function assertApiConfigured(config: AppConfig): AppConfig {
  if (!config.production) {
    return config;
  }

  let host: string;

  try {
    host = new URL(config.apiUrl).host;
  } catch {
    throw new Error(
      `SEVEN: environment.apiUrl is not a valid URL in production ("${config.apiUrl}"). ` +
        'Set the deployed API base URL, e.g. "https://api.<your-domain>/api".',
    );
  }

  if (UNCONFIRMED_HOSTS.includes(host)) {
    throw new Error(
      `SEVEN: environment.apiUrl still points at the unconfirmed host "${host}". ` +
        'Replace it with the deployed API base URL before shipping, or every ' +
        'screen in the release will render empty.',
    );
  }

  if (!/^https:/i.test(config.apiUrl)) {
    throw new Error(
      `SEVEN: environment.apiUrl must use HTTPS in production ("${config.apiUrl}"). ` +
        'Cleartext API traffic is rejected by both the app stores and the OS.',
    );
  }

  return config;
}
