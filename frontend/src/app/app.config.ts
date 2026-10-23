import { ApplicationConfig, provideZoneChangeDetection } from '@angular/core';
import { RouteReuseStrategy, provideRouter, withComponentInputBinding, withPreloading } from '@angular/router';
import { provideHttpClient, withFetch, withInterceptors } from '@angular/common/http';
import { provideIonicAngular, IonicRouteStrategy } from '@ionic/angular';
import { Storage } from '@ionic/storage';

import { routes } from './routes';
import { SelectivePreloadStrategy } from './core/services/selective-preload.strategy';
import { assertApiConfigured } from './core/config/assert-api-configured';
import { APP_CONFIG, STORAGE } from './core/tokens';
import { environment } from '../environments/environment';
import { apiRequestInterceptor } from './interceptors/api-request.interceptor';
import { errorInterceptor } from './interceptors/error.interceptor';
import { loadingInterceptor } from './interceptors/loading.interceptor';

/**
 * Root application configuration (standalone bootstrap).
 *
 * Wires the router with lazy standalone pages, the Ionic runtime and
 * the HTTP client with the SEVEN interceptors
 * (headers + error normalization).
 *
 * Routing uses a selective preload strategy: only the tab shell and the
 * top collections are prefetched after the app starts; detail pages and
 * heavy screens stay as on-demand lazy chunks.
 *
 * Ionic native affordances are enabled here: swipe-back gesture for
 * stack navigation (works on iOS; Android uses the system back button).
 */
export const appConfig: ApplicationConfig = {
  providers: [
    provideZoneChangeDetection({ eventCoalescing: true }),

    provideRouter(
      routes,
      withComponentInputBinding(), // route params -> component @Input()
      withPreloading(SelectivePreloadStrategy),
    ),

    provideIonicAngular({ swipeBackEnabled: true, backButtonText: '' }),

    provideHttpClient(withFetch(), withInterceptors([apiRequestInterceptor, loadingInterceptor, errorInterceptor])),

    // Runs before the first request: a production build still pointing at
    // the placeholder API host throws here instead of shipping empty screens.
    { provide: APP_CONFIG, useFactory: () => assertApiConfigured(environment) },
    {
      provide: STORAGE,
      useFactory: () => new Storage({ name: 'seven-db', storeName: 'seven_kv' }),
    },
    { provide: RouteReuseStrategy, useClass: IonicRouteStrategy },
  ],
};