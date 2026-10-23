import { InjectionToken } from '@angular/core';

import type { AppConfig } from '../config/app-config.interface';

/**
 * Provides the resolved application configuration anywhere in the app.
 *
 * The concrete value is wired in `app.config.ts` from the environment
 * files (one per build target):
 *   { provide: APP_CONFIG, useValue: environment }
 */
export const APP_CONFIG = new InjectionToken<AppConfig>('app.config');