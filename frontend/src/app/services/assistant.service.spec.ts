import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';

import { firstValueFrom } from 'rxjs';

import { AssistantService } from './assistant.service';
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

const reply = {
  conversation_id: '01JABCDEF0123456789ABCDEFG',
  message: 'Con $500.000 te propongo Johnny Cay.',
  recommendations: [
    {
      type: 'tour',
      id: '12',
      name: 'Johnny Cay',
      summary: null,
      price: 170000,
      price_unit: 'per_person',
      rating: 4.8,
      review_count: 132,
      location: 'San Andrés',
      image: null,
      route: ['/tour', 'johnny-cay'],
      provider: 'Sea Keys Tours',
      availability: 'available',
    },
  ],
  packages: [],
  meta: { provider: 'gemini', fallback: false, iterations: 2 },
};

describe('AssistantService', () => {
  let service: AssistantService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: APP_CONFIG, useValue: testConfig },
      ],
    });

    service = TestBed.inject(AssistantService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => httpMock.verify());

  it('posts the message to the assistant endpoint and maps the reply', async () => {
    const result = firstValueFrom(service.send('¿Qué hago con $500.000?'));

    const request = httpMock.expectOne(`${base}/assistant/messages`);
    expect(request.request.method).toBe('POST');
    expect(request.request.body).toEqual({
      message: '¿Qué hago con $500.000?',
      conversation_id: null,
    });

    request.flush({ success: true, message: 'ok', data: reply });

    const mapped = await result;
    expect(mapped.conversationId).toBe('01JABCDEF0123456789ABCDEFG');
    expect(mapped.recommendations[0].key).toBe('tour:12');
    expect(mapped.meta.provider).toBe('gemini');
  });

  it('continues an existing conversation when a conversation id is given', async () => {
    const result = firstValueFrom(service.send('y para comer?', '01JABCDEF0123456789ABCDEFG'));

    const request = httpMock.expectOne(`${base}/assistant/messages`);
    expect(request.request.body).toEqual({
      message: 'y para comer?',
      conversation_id: '01JABCDEF0123456789ABCDEFG',
    });

    request.flush({ success: true, message: 'ok', data: reply });
    await result;
  });
});
