import { describe, expect, it } from 'vitest';

import {
  formatCurrency,
  formatDate,
  formatDateTime,
  formatNewsDate,
  formatTime,
  formatTimeOfDay,
  initials,
  relativeDay,
  whatsappLink,
} from './formatters';

describe('formatCurrency', () => {
  it('formats amounts using es-CO locale without decimals', () => {
    const result = formatCurrency(180000);
    expect(result).toMatch(/\d[.\d]*/);
    expect(result).not.toMatch(/,\d{2}$/);
    expect(result).toContain('$');
  });

  it('keeps the currency code shape intact', () => {
    expect(formatCurrency(250000, 'USD')).toContain('$');
  });
});

describe('formatDate', () => {
  it('returns empty string for nullish values', () => {
    expect(formatDate(null)).toBe('');
    expect(formatDate(undefined)).toBe('');
  });

  it('formats a valid ISO date', () => {
    const result = formatDate('2026-09-15T19:00:00Z');
    expect(result).toMatch(/sep/i);
  });
});

describe('formatTime', () => {
  it('returns empty for nullish values', () => {
    expect(formatTime(null)).toBe('');
    expect(formatTime('')).toBe('');
  });

  it('formats time of day', () => {
    const result = formatTime('14:30');
    expect(result).toContain('2');
    expect(result).toContain('30');
  });
});

describe('formatDateTime', () => {
  it('returns empty for nullish or invalid input', () => {
    expect(formatDateTime(null)).toBe('');
    expect(formatDateTime(undefined)).toBe('');
    expect(formatDateTime('not-a-date')).toBe('');
  });

  it('combines a localized day and time', () => {
    const result = formatDateTime('2026-09-15T19:00:00Z');
    expect(result).toContain('·');
    expect(result).toMatch(/sep/i);
  });

  it('switches locale with the language', () => {
    const result = formatDateTime('2026-09-15T19:00:00Z', 'en');
    expect(result).toMatch(/sep/i);
  });
});

describe('formatTimeOfDay', () => {
  it('returns empty for nullish input', () => {
    expect(formatTimeOfDay(null)).toBe('');
  });

  it('formats the time portion of an ISO date', () => {
    const result = formatTimeOfDay('2026-09-15T19:00:00Z');
    expect(result).not.toBe('');
  });
});

describe('relativeDay', () => {
  const now = new Date('2026-09-15T12:00:00Z');

  it('detects today and tomorrow', () => {
    expect(relativeDay('2026-09-15T18:00:00', now)).toBe('today');
    expect(relativeDay('2026-09-16T09:00:00', now)).toBe('tomorrow');
  });

  it('returns null for other days and invalid input', () => {
    expect(relativeDay('2026-09-20T09:00:00', now)).toBeNull();
    expect(relativeDay(null, now)).toBeNull();
    expect(relativeDay('bad', now)).toBeNull();
  });
});

describe('formatNewsDate', () => {
  const now = new Date('2026-10-12T15:00:00');

  it('returns empty for nullish or invalid input', () => {
    expect(formatNewsDate(null, 'es', now)).toBe('');
    expect(formatNewsDate('', 'es', now)).toBe('');
    expect(formatNewsDate('not-a-date', 'es', now)).toBe('');
  });

  it('uses minutes for the last hour', () => {
    expect(formatNewsDate('2026-10-12T14:35:00', 'es', now)).toMatch(/25 min/i);
  });

  it('uses hours within the same day', () => {
    expect(formatNewsDate('2026-10-12T12:00:00', 'es', now)).toMatch(/3 h/i);
  });

  it('labels the previous day as yesterday', () => {
    expect(formatNewsDate('2026-10-11T09:00:00', 'es', now)).toMatch(/ayer/i);
  });

  it('falls back to a short absolute date after a week', () => {
    const result = formatNewsDate('2026-10-01T09:00:00', 'es', now);
    expect(result).toMatch(/1/i);
    expect(result).toMatch(/oct/i);
    expect(result).not.toMatch(/d[íi]a/i);
  });

  it('adds the year when the story is from another year', () => {
    expect(formatNewsDate('2024-10-01T09:00:00', 'es', now)).toMatch(/2024/);
  });

  it('localizes the relative label', () => {
    expect(formatNewsDate('2026-10-11T09:00:00', 'en', now)).toMatch(/yesterday/i);
  });
});

describe('whatsappLink', () => {
  it('strips non-digit characters from the phone', () => {
    expect(whatsappLink('+57 301 234 5678')).toBe('https://wa.me/573012345678');
  });

  it('adds an encoded message when provided', () => {
    const link = whatsappLink('573012345678', 'Hola, quiero reservar');
    expect(link).toContain('https://wa.me/573012345678?text=');
    expect(link).toContain(encodeURIComponent('Hola, quiero reservar'));
  });

  it('handles nullish phone values', () => {
    expect(whatsappLink(null)).toBe('https://wa.me/');
    expect(whatsappLink(undefined)).toBe('https://wa.me/');
  });
});

describe('initials', () => {
  it('returns the first and last name initials', () => {
    expect(initials('Laura Gómez')).toBe('LG');
  });

  it('handles a single word name', () => {
    expect(initials('Mariana')).toBe('M');
  });

  it('returns a placeholder for nullish values', () => {
    expect(initials(null)).toBe('?');
    expect(initials(undefined)).toBe('?');
  });
});