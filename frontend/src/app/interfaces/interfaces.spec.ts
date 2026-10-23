import { describe, expect, it } from 'vitest';

import { ApiError } from './error.interface';
import { Paginated } from './pagination.interface';
import type { ApiPagination } from './api-response.interface';
import { describeApiError, hasFailed, isReady } from './async-state.interface';

describe('ApiError', () => {
  it('carries status, message and field errors', () => {
    const error = new ApiError(422, 'Validation failed', { email: ['El correo no es válido'] });

    expect(error).toBeInstanceOf(Error);
    expect(error.name).toBe('ApiError');
    expect(error.status).toBe(422);
    expect(error.message).toBe('Validation failed');
    expect(error.fields?.['email']).toEqual(['El correo no es válido']);
  });
});

describe('Paginated', () => {
  const meta: ApiPagination = {
    pagination: { total: 40, per_page: 15, current_page: 2, last_page: 3 },
  };

  it('exposes items and pagination metadata', () => {
    const page = new Paginated([1, 2, 3], meta);

    expect(page.items).toEqual([1, 2, 3]);
    expect(page.total).toBe(40);
    expect(page.currentPage).toBe(2);
  });

  it('reports hasMore while not on the last page', () => {
    expect(new Paginated([1], meta).hasMore).toBe(true);
    expect(new Paginated([1], { pagination: { ...meta.pagination, current_page: 3 } }).hasMore).toBe(false);
  });

  it('falls back to item count when no meta is provided', () => {
    const page = new Paginated(['a', 'b']);
    expect(page.total).toBe(2);
    expect(page.currentPage).toBe(1);
    expect(page.lastPage).toBe(1);
    expect(page.hasMore).toBe(false);
  });
});

describe('AsyncState helpers', () => {
  it('narrows the ready and error variants', () => {
    const ready = { status: 'ready', data: [1] } as const;
    const failed = { status: 'error', message: 'boom' } as const;
    const loading = { status: 'loading' } as const;

    expect(isReady(ready)).toBe(true);
    expect(isReady(failed)).toBe(false);
    expect(isReady(loading)).toBe(false);

    expect(hasFailed(failed)).toBe(true);
    expect(hasFailed(ready)).toBe(false);
    expect(hasFailed(loading)).toBe(false);
  });
});

describe('describeApiError', () => {
  it('explains a transport failure without leaking the status code', () => {
    const message = describeApiError(new ApiError(0, 'Network error. Please check your connection.'));

    expect(message).toContain('Sin conexión');
    expect(message).not.toContain('0');
  });

  it('keeps the server message for a validation failure', () => {
    expect(describeApiError(new ApiError(422, 'Revisa los datos'))).toBe('Revisa los datos');
  });

  it('maps server-side faults to a retryable wording', () => {
    expect(describeApiError(new ApiError(503, 'Service Unavailable'))).toContain('Inténtalo en unos minutos');
  });

  it('falls back for anything that is not an ApiError', () => {
    expect(describeApiError(new Error('boom'))).toBe('No pudimos cargar la información.');
  });
});