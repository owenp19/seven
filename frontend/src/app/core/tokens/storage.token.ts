import { InjectionToken } from '@angular/core';

import type { Storage } from '@ionic/storage';

/**
 * Provides the persistent local storage instance backed by @ionic/storage
 * (IndexedDB on web / Capacitor native storage on device).
 *
 * The concrete value is wired in `app.config.ts`:
 *   { provide: STORAGE, useFactory: () => new Storage({ name: 'seven-db' }) }
 */
export const STORAGE = new InjectionToken<Storage>('app.storage');