/**
 * Read formatting helpers shared across the UI. Kept as pure functions
 * so they can be unit-tested without Angular dependency injection.
 */

import type { Language } from '../core/services/translation.service';

/** Locale BCP-47 para fechas/horas según el idioma activo de la app. */
export const DATE_LOCALE: Record<Language, string> = {
  es: 'es-CO',
  en: 'en-US',
  pt: 'pt-BR',
};

/** Format an integer amount as COP using Colombian locale conventions. */
export function formatCurrency(value: number, currency = 'COP'): string {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency,
    maximumFractionDigits: 0,
  }).format(value);
}

/** Format an ISO date as a friendly local date. */
export function formatDate(iso: string | null | undefined): string {
  if (!iso) {
    return '';
  }
  return new Intl.DateTimeFormat('es-CO', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
  }).format(new Date(iso));
}

/** Format a time of day (e.g. "14:30" -> "2:30 p. m."). */
export function formatTime(time: string | null | undefined): string {
  if (!time) {
    return '';
  }
  return new Intl.DateTimeFormat('es-CO', {
    hour: 'numeric',
    minute: '2-digit',
  }).format(new Date(`1970-01-01T${time}:00`));
}

/** Format just the time of an ISO date (e.g. "2:30 p. m."), lo-calized. */
export function formatTimeOfDay(iso: string | null | undefined, language: Language = 'es'): string {
  if (!iso) {
    return '';
  }
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) {
    return '';
  }
  return new Intl.DateTimeFormat(DATE_LOCALE[language], {
    hour: 'numeric',
    minute: '2-digit',
  }).format(date);
}

/**
 * Format an ISO date as a localized date + time (e.g. "sáb, 15 oct · 8:30 a. m.").
 * Returns an empty string for missing or invalid input.
 */
export function formatDateTime(iso: string | null | undefined, language: Language = 'es'): string {
  if (!iso) {
    return '';
  }
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) {
    return '';
  }
  const locale = DATE_LOCALE[language];
  const day = new Intl.DateTimeFormat(locale, {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
  }).format(date);
  const time = new Intl.DateTimeFormat(locale, {
    hour: 'numeric',
    minute: '2-digit',
  }).format(date);
  return `${day} · ${time}`;
}

/** Classify a date as today/tomorrow relative to `now` (local calendar days). */
export function relativeDay(
  iso: string | null | undefined,
  now: Date = new Date(),
): 'today' | 'tomorrow' | null {
  if (!iso) {
    return null;
  }
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) {
    return null;
  }
  const startOf = (value: Date): number =>
    new Date(value.getFullYear(), value.getMonth(), value.getDate()).getTime();
  const dayMs = 24 * 60 * 60 * 1000;
  const diff = Math.round((startOf(date) - startOf(now)) / dayMs);
  if (diff === 0) {
    return 'today';
  }
  if (diff === 1) {
    return 'tomorrow';
  }
  return null;
}

/** Build a wa.me deep link for one-tap WhatsApp contact. */
export function whatsappLink(phone: string | null | undefined, message?: string): string {
  const digits = (phone ?? '').replace(/[^\d]/g, '');
  const base = `https://wa.me/${digits}`;
  return message ? `${base}?text=${encodeURIComponent(message)}` : base;
}

/** First letters of a name, max two, for avatar placeholders. */
export function initials(name: string | null | undefined): string {
  if (!name) {
    return '?';
  }
  const parts = name.trim().split(/\s+/);
  const first = parts[0]?.[0] ?? '';
  const last = parts.length > 1 ? (parts[parts.length - 1][0] ?? '') : '';
  return (first + last).toUpperCase();
}

const MINUTE_MS = 60 * 1000;
const HOUR_MS = 60 * MINUTE_MS;
const DAY_MS = 24 * HOUR_MS;

/**
 * Fecha editorial para el feed de Noticias.
 *
 * Usa tiempo relativo para lo reciente ("hace 20 min", "hace 3 h", "ayer")
 * y salta a fecha absoluta localizada en cuanto la historia pasa la semana,
 * de modo que un titular no arrastre un "hace 11 días" que nadie lee.
 * El sufijo de año solo aparece cuando la nota es de otro año.
 */
export function formatNewsDate(
  iso: string | null | undefined,
  language: Language = 'es',
  now: Date = new Date(),
): string {
  if (!iso) {
    return '';
  }
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) {
    return '';
  }

  const relative = new Intl.RelativeTimeFormat(DATE_LOCALE[language], { numeric: 'auto' });
  const diff = now.getTime() - date.getTime();

  if (diff < 0) {
    return absoluteNewsDate(date, language, now);
  }

  const minutes = Math.floor(diff / MINUTE_MS);
  if (minutes < 1) {
    return relative.format(0, 'minute');
  }
  if (minutes < 60) {
    return relative.format(-minutes, 'minute');
  }

  if (isSameCalendarDay(date, now)) {
    return relative.format(-Math.floor(diff / HOUR_MS), 'hour');
  }

  const yesterday = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1);
  if (isSameCalendarDay(date, yesterday)) {
    return relative.format(-1, 'day');
  }

  const days = Math.floor(diff / DAY_MS);
  if (days < 7) {
    return relative.format(-days, 'day');
  }

  return absoluteNewsDate(date, language, now);
}

/** Fecha corta localizada ("12 oct" o "12 oct 2024" si es de otro año). */
function absoluteNewsDate(date: Date, language: Language, now: Date): string {
  const sameYear = date.getFullYear() === now.getFullYear();
  return new Intl.DateTimeFormat(DATE_LOCALE[language], {
    day: 'numeric',
    month: 'short',
    ...(sameYear ? {} : { year: 'numeric' }),
  }).format(date);
}

function isSameCalendarDay(a: Date, b: Date): boolean {
  return (
    a.getFullYear() === b.getFullYear() &&
    a.getMonth() === b.getMonth() &&
    a.getDate() === b.getDate()
  );
}