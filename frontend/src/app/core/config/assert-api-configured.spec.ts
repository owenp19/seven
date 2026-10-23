import { describe, expect, it } from 'vitest';

import { assertApiConfigured } from './assert-api-configured';
import type { AppConfig } from './app-config.interface';

const base: AppConfig = {
  production: true,
  appName: 'SEVEN',
  appVersion: '0.1.0',
  apiUrl: 'https://api.seven.app/api',
  apiVersion: 'v1',
  defaultCurrency: 'COP',
  contact: { privacyEmail: 'privacidad@seven.app' },
  maxPerPage: 50,
  features: { explore: true, bookings: true, payments: false, reviews: false, accounts: false },
};

describe('assertApiConfigured', () => {
  it('rejects the unconfirmed placeholder host in production', () => {
    expect(() => assertApiConfigured(base)).toThrowError(/unconfirmed host/i);
  });

  it('accepts a real production host and returns the config', () => {
    const config = { ...base, apiUrl: 'https://api.seven.co/api' };

    expect(assertApiConfigured(config)).toBe(config);
  });

  it('ignores the placeholder outside production so dev and tests still boot', () => {
    const config = { ...base, production: false };

    expect(assertApiConfigured(config)).toBe(config);
  });

  it('rejects a malformed apiUrl in production', () => {
    expect(() => assertApiConfigured({ ...base, apiUrl: 'api.seven.app/api' })).toThrowError(/not a valid URL/i);
  });

  it('rejects cleartext http in production', () => {
    expect(() => assertApiConfigured({ ...base, apiUrl: 'http://api.seven.co/api' })).toThrowError(/HTTPS/i);
  });
});
