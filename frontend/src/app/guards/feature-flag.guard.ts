import { CanActivateFn, Router } from '@angular/router';
import { inject } from '@angular/core';

import { APP_CONFIG } from '../core/tokens';
import type { AppConfig } from '../core/config/app-config.interface';

/**
 * featureFlagGuard
 *
 * Blocks/redirects routes while a vertical is disabled in the
 * environment config (`environment.features`). Lets the team ship
 * placeholder routes safely before a feature is production-ready.
 *
 * Usage in routes:
 *   { path: 'payments', ..., canActivate: [featureFlagGuard('payments')] }
 */
export function featureFlagGuard(feature: keyof AppConfig['features']): CanActivateFn {
  return () => {
    const config = inject(APP_CONFIG);
    const router = inject(Router);

    if (config.features[feature]) {
      return true;
    }

    return router.createUrlTree(['/']);
  };
}