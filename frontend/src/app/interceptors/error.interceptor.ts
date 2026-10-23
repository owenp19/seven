import { HttpErrorResponse, HttpEvent, HttpHandlerFn, HttpInterceptorFn, HttpRequest } from '@angular/common/http';

import { Observable, catchError, throwError } from 'rxjs';

import { ApiError } from '../interfaces';

/**
 * errorInterceptor
 *
 * Converts raw HttpErrorResponse into the normalized ApiError class so
 * feature code can rely on `{ status, message, fields }` regardless of
 * whether the failure came from the Laravel API, CORS or the network.
 */
export const errorInterceptor: HttpInterceptorFn = (
  req: HttpRequest<unknown>,
  next: HttpHandlerFn,
): Observable<HttpEvent<unknown>> => {
  return next(req).pipe(
    catchError((err: HttpErrorResponse) => {
      let status = 0;
      let message = 'Network error. Please check your connection.';
      let fields: Record<string, string[]> | undefined;

      if (err instanceof HttpErrorResponse) {
        status = err.status;

        // Laravel API envelope: { message, errors }.
        if (err.error && typeof err.error === 'object') {
          message = err.error.message ?? message;
          fields = err.error.errors ?? undefined;
        } else if (err.status) {
          message = err.statusText || 'Request failed.';
        }
      }

      return throwError(() => new ApiError(status, message, fields));
    }),
  );
};