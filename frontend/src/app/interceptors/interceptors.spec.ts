import { HttpErrorResponse, HttpEvent, HttpRequest } from '@angular/common/http';

import { firstValueFrom, of, throwError } from 'rxjs';

import { describe, expect, it } from 'vitest';

import { ApiError } from '../interfaces';
import { apiRequestInterceptor } from './api-request.interceptor';
import { errorInterceptor } from './error.interceptor';

describe('apiRequestInterceptor', () => {
  it('sets JSON headers and the app version, without forcing preflight on GET', async () => {
    const req = new HttpRequest('GET', 'http://localhost:8000/api/v1/businesses');
    let captured: HttpRequest<unknown> | undefined;

    await firstValueFrom(
      apiRequestInterceptor(req, (out) => {
        captured = out;
        return of({ type: 0, url: out.urlWithParams } as HttpEvent<unknown>);
      }),
    );

    expect(captured).toBeDefined();
    expect(captured?.headers.get('Accept')).toBe('application/json');
    expect(captured?.headers.get('X-App-Version')).toBe('seven-app/0.1.0');
    expect(captured?.headers.has('Content-Type')).toBe(false);
  });

  it('sets Content-Type only on requests with a body', async () => {
    const req = new HttpRequest('POST', 'http://localhost:8000/api/v1/bookings', { date: '2026-09-18' });
    let captured: HttpRequest<unknown> | undefined;

    await firstValueFrom(
      apiRequestInterceptor(req, (out) => {
        captured = out;
        return of({ type: 0, url: out.urlWithParams } as HttpEvent<unknown>);
      }),
    );

    expect(captured?.headers.get('Content-Type')).toBe('application/json');
  });
});

describe('errorInterceptor', () => {
  it('normalizes Laravel validation errors into ApiError with fields', async () => {
    const req = new HttpRequest('GET', 'http://localhost:8000/api/v1/businesses');
    const err = new HttpErrorResponse({
      status: 422,
      statusText: 'Unprocessable Content',
      url: req.url,
      error: { message: 'The given data was invalid.', errors: { email: ['El correo no es válido'] } },
    });

    const apiError = await firstValueFrom(
      errorInterceptor(req, () => throwError(() => err)).pipe(),
    ).catch((e: unknown) => e);

    expect(apiError).toBeInstanceOf(ApiError);
    expect((apiError as ApiError).status).toBe(422);
    expect((apiError as ApiError).fields?.['email']).toEqual(['El correo no es válido']);
  });

  it('falls back to a network message for non-HTTP errors', async () => {
    const req = new HttpRequest('GET', 'http://localhost:8000/api/v1/businesses');
    const networkErr = new Error('Failed to fetch');

    const apiError = await firstValueFrom(
      errorInterceptor(req, () => throwError(() => networkErr)).pipe(),
    ).catch((e: unknown) => e);

    expect(apiError).toBeInstanceOf(ApiError);
    expect((apiError as ApiError).status).toBe(0);
    expect((apiError as ApiError).message).toContain('Network');
  });
});