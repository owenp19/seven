import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';

import { firstValueFrom, of } from 'rxjs';

import { ApiClientService } from './api-client.service';
import { APP_CONFIG } from '../tokens';
import type { AppConfig } from '../config/app-config.interface';

const testConfig: AppConfig = {
  production: false,
  appName: 'SEVEN',
  appVersion: '0.1.0',
  apiUrl: 'http://127.0.0.1:8000/api',
  apiVersion: 'v1',
  defaultCurrency: 'COP',
  contact: { privacyEmail: 'privacidad@seven.app' },
  maxPerPage: 50,
  features: { explore: true, bookings: true, payments: false, reviews: false, accounts: false },
};

/**
 * Derived from the config on purpose. These assertions used to hardcode
 * `localhost`, which contradicted the apiUrl above and failed 5 tests: the
 * real dev host is 127.0.0.1 because `php artisan serve` only binds IPv4.
 */
const base = `${testConfig.apiUrl}/${testConfig.apiVersion}`;

describe('ApiClientService', () => {
  let service: ApiClientService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: APP_CONFIG, useValue: testConfig },
      ],
    });

    service = TestBed.inject(ApiClientService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => httpMock.verify());

  it('unwraps the data field from the response envelope', async () => {
    const result = firstValueFrom(
      service.get<{ id: number }>('businesses/1'),
    );

    const request = httpMock.expectOne(`${base}/businesses/1`);
    request.flush({ success: true, message: 'ok', data: { id: 1 } });

    await expect(result).resolves.toEqual({ id: 1 });
  });

  it('sends query params skipping empty values', async () => {
    const result = firstValueFrom(
      service.get<{ items: number[] }>('businesses', { category: 'restaurantes', q: '', page: 1 }),
    );

    const request = httpMock.expectOne(r => r.url === `${base}/businesses`);
    expect(request.request.params.has('category')).toBe(true);
    expect(request.request.params.get('category')).toBe('restaurantes');
    expect(request.request.params.has('q')).toBe(false);
    expect(request.request.params.get('page')).toBe('1');

    request.flush({ success: true, message: 'ok', data: { items: [] } });
    await result;
  });

  it('builds a Paginated wrapper from paginated endpoints', async () => {
    const result = firstValueFrom(service.getPaginated<{ id: number }>('businesses'));

    const request = httpMock.expectOne(`${base}/businesses`);
    request.flush({
      success: true,
      message: 'ok',
      data: [{ id: 1 }, { id: 2 }],
      meta: { pagination: { total: 20, per_page: 15, current_page: 1, last_page: 2 } },
    });

    const page = await result;
    expect(page.items).toHaveLength(2);
    expect(page.total).toBe(20);
    expect(page.hasMore).toBe(true);
  });

  it('walks every page of a paginated collection', async () => {
    const result = firstValueFrom(service.getAllPages<{ id: number }>('tours'));

    const first = httpMock.expectOne(
      r => r.url === `${base}/tours` && r.params.get('page') === '1',
    );
    expect(first.request.params.get('per_page')).toBe('50');
    first.flush({
      success: true,
      message: 'ok',
      data: [{ id: 1 }, { id: 2 }],
      meta: { pagination: { total: 3, per_page: 50, current_page: 1, last_page: 2 } },
    });

    const second = httpMock.expectOne(
      r => r.url === `${base}/tours` && r.params.get('page') === '2',
    );
    second.flush({
      success: true,
      message: 'ok',
      data: [{ id: 3 }],
      meta: { pagination: { total: 3, per_page: 50, current_page: 2, last_page: 2 } },
    });

    await expect(result).resolves.toEqual([{ id: 1 }, { id: 2 }, { id: 3 }]);
  });

  it('stops requesting pages on a single-page response', async () => {
    const result = firstValueFrom(service.getAllPages<{ id: number }>('tours'));

    httpMock.expectOne(r => r.url === `${base}/tours` && r.params.get('page') === '1').flush({
      success: true,
      message: 'ok',
      data: [{ id: 1 }],
      meta: { pagination: { total: 1, per_page: 50, current_page: 1, last_page: 1 } },
    });

    await expect(result).resolves.toEqual([{ id: 1 }]);
  });

  it('posts and unwraps the created resource', async () => {
    const result = firstValueFrom(
      service.post<{ booking_code: string }>('bookings', { type: 'gastronomic' }),
    );

    const request = httpMock.expectOne(`${base}/bookings`);
    expect(request.request.method).toBe('POST');
    request.flush({ success: true, message: 'created', data: { booking_code: 'SEV-ABC123' } });

    await expect(result).resolves.toEqual({ booking_code: 'SEV-ABC123' });
  });

  it('builds full urls for sharing links', () => {
    expect(service.url('bookings/SEV-ABC123')).toBe(`${base}/bookings/SEV-ABC123`);
  });
});