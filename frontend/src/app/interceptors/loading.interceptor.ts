import { HttpEvent, HttpHandlerFn, HttpInterceptorFn, HttpRequest } from '@angular/common/http';
import { inject } from '@angular/core';

import { Observable, finalize } from 'rxjs';

import { LoadingService } from '../core/services/loading.service';

/**
 * loadingInterceptor
 *
 * Tracks in-flight HTTP requests through the global LoadingService so
 * pages can reflect a global "loading" state while critical API calls
 * are pending without blocking lazy module loads.
 */
export const loadingInterceptor: HttpInterceptorFn = (
  req: HttpRequest<unknown>,
  next: HttpHandlerFn,
): Observable<HttpEvent<unknown>> => {
  const loading = inject(LoadingService);
  loading.increment();

  return next(req).pipe(finalize(() => loading.decrement()));
};
