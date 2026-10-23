import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';

import { firstValueFrom } from 'rxjs';

import { CommentService } from './comment.service';
import { APP_CONFIG } from '../core/tokens';
import type { AppConfig } from '../core/config/app-config.interface';

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

const base = `${testConfig.apiUrl}/${testConfig.apiVersion}`;

const published = {
  id: 12,
  author_name: 'Camila',
  body: 'Salimos temprano y el mar estaba en calma.',
  rating: 5,
  pending: false,
  created_at: '2026-09-28T14:03:00+00:00',
};

describe('CommentService', () => {
  let service: CommentService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: APP_CONFIG, useValue: testConfig },
      ],
    });

    service = TestBed.inject(CommentService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => httpMock.verify());

  it('reads comments from the slug of a tour', async () => {
    const result = firstValueFrom(service.list('tour', 'isla-burbuja'));

    const request = httpMock.expectOne(`${base}/tours/isla-burbuja/comments`);
    request.flush({ success: true, message: 'ok', data: [published] });

    const comments = await result;
    expect(comments).toHaveLength(1);
    expect(comments[0].authorName).toBe('Camila');
    expect(comments[0].initials).toBe('C');
    expect(comments[0].pending).toBe(false);
  });

  it('uses one endpoint per vertical instead of a generic route', () => {
    service.list('restaurant', 'casa-marea').subscribe();
    httpMock.expectOne(`${base}/restaurants/casa-marea/comments`).flush({ success: true, message: 'ok', data: [] });

    service.list('inn', 'villa-del-viento').subscribe();
    httpMock.expectOne(`${base}/inns/villa-del-viento/comments`).flush({ success: true, message: 'ok', data: [] });
  });

  it('treats a null payload as no comments instead of throwing', async () => {
    const result = firstValueFrom(service.list('inn', 'villa-del-viento'));

    httpMock.expectOne(`${base}/inns/villa-del-viento/comments`).flush({ success: true, message: 'ok', data: null });

    await expect(result).resolves.toEqual([]);
  });

  it('trims the text and sends the honeypot untouched', async () => {
    const result = firstValueFrom(
      service.create('tour', 'isla-burbuja', {
        authorName: '  Camila  ',
        body: '  Salimos temprano.  ',
        rating: 5,
        website: 'http://spam.example',
      }),
    );

    const request = httpMock.expectOne(`${base}/tours/isla-burbuja/comments`);
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual({
      author_name: 'Camila',
      body: 'Salimos temprano.',
      rating: 5,
      website: 'http://spam.example',
    });

    request.flush({ success: true, message: 'ok', data: published });

    const comment = await result;
    expect(comment.id).toBe(12);
  });

  it('sends an empty honeypot when the caller has no hidden field', async () => {
    const result = firstValueFrom(
      service.create('restaurant', 'casa-marea', {
        authorName: 'Camila',
        body: 'El pescado estaba muy fresco.',
        rating: null,
      }),
    );

    const request = httpMock.expectOne(`${base}/restaurants/casa-marea/comments`);
    expect(request.request.body.website).toBe('');

    request.flush({ success: true, message: 'ok', data: published });

    await result;
  });

  it('surfaces a moderated comment as pending rather than as a failure', async () => {
    const result = firstValueFrom(
      service.create('inn', 'villa-del-viento', {
        authorName: 'Camila',
        body: 'Todo bien, volvería sin dudarlo.',
        rating: 4,
      }),
    );

    httpMock
      .expectOne(`${base}/inns/villa-del-viento/comments`)
      .flush({ success: true, message: 'ok', data: { ...published, pending: true } });

    const comment = await result;
    expect(comment.pending).toBe(true);
  });

  it('survives a payload with null text instead of throwing on initials', async () => {
    const result = firstValueFrom(service.list('tour', 'isla-burbuja'));

    httpMock.expectOne(`${base}/tours/isla-burbuja/comments`).flush({
      success: true,
      message: 'ok',
      data: [{ ...published, author_name: null, body: null, created_at: null }],
    });

    const [comment] = await result;
    expect(comment.authorName).toBe('');
    expect(comment.initials).toBe('?');
    expect(comment.relativeDate).toBe('');
  });
});