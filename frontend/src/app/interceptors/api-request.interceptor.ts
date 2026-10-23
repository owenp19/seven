import { HttpEvent, HttpHandlerFn, HttpInterceptorFn, HttpRequest } from '@angular/common/http';

import { Observable, timeout } from 'rxjs';

/**
 * apiRequestInterceptor
 *
 * Adds the JSON headers every SEVEN API call needs. Since the
 * backend accepts CORS requests without cookies (v1 is anonymous),
 * no authorization header is attached here yet — it will be added
 * here later when authenticated users land in v2.
 *
 * Content-Type is only set on requests with a body: a JSON header on GET
 * forces an unnecessary CORS preflight (OPTIONS) that piles work onto the
 * single-threaded dev server. A 30s timeout guards against requests that
 * stall (queued by the dev server), so the UI falls back cleanly instead
 * of hanging forever.
 */
export const apiRequestInterceptor: HttpInterceptorFn = (
  req: HttpRequest<unknown>,
  next: HttpHandlerFn,
): Observable<HttpEvent<unknown>> => {
  const { body } = req;
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'X-App-Version': 'seven-app/0.1.0',
  };

  const hasBody = body !== null && body !== undefined && !(typeof FormData !== 'undefined' && body instanceof FormData);
  if (hasBody) {
    headers['Content-Type'] = 'application/json';
  }

  return next(req.clone({ setHeaders: headers })).pipe(timeout(30 * 1000));
};