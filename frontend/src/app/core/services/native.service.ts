import { Injectable } from '@angular/core';
import { Capacitor } from '@capacitor/core';
import { Share } from '@capacitor/share';
import { Network } from '@capacitor/network';
import { Geolocation } from '@capacitor/geolocation';
import { LocalNotifications } from '@capacitor/local-notifications';
import { Preferences } from '@capacitor/preferences';

import { Observable, BehaviorSubject, distinctUntilChanged } from 'rxjs';

export interface SharePayload {
  title?: string;
  text?: string;
  url?: string;
}

export interface GeoPosition {
  latitude: number;
  longitude: number;
}

/** Clave del identificador de instalación, en `localStorage` y en Preferences. */
const INSTALL_ID_KEY = 'seven_install_id';

/**
 * Clave del mapa reserva -> token de acceso, en `localStorage` y Preferences.
 *
 * El backend entrega un `booking_access_token` secreto por reserva (en claro,
 * solo una vez al crear) y luego exige ese token en `X-Booking-Token` para
 * leer, cancelar o actualizar. Aquí se guarda por código de reserva para que
 * el polling de "Mis reservas" pueda volver a consultarla sin login.
 */
const BOOKING_TOKENS_KEY = 'seven_booking_tokens';

/**
 * Genera el identificador de instalación.
 *
 * `crypto.randomUUID` solo existe en contextos seguros, y una app servida por
 * Capacitor lo es; el respaldo cubre navegadores viejos en lugar de fallar.
 */
function mintInstallId(): string {
  return (
    crypto.randomUUID?.() ?? `SEVEN-${Date.now()}-${Math.random().toString(36).slice(2)}`
  ).toUpperCase();
}

/**
 * NativeService
 *
 * Single gateway to the Capacitor native plugins so feature code always
 * stays platform-agnostic: every capability is guarded with
 * `Capacitor.isNativePlatform()` and falls back to web APIs (Share API,
 * `navigator.geolocation`, `navigator.onLine`) so everything keeps
 * working in the browser during development.
 *
 * Se usa sin login: el "dispositivo" se identifica por un Install ID
 * único (UUID) persistido localmente, ver `installId()`.
 */
@Injectable({ providedIn: 'root' })
export class NativeService {
  readonly isNative = Capacitor.isNativePlatform();

  /**
   * Espejo en memoria del identificador de instalación.
   *
   * El interceptor de HTTP puede pedirlo muchas veces seguidas (una por
   * petición) y en un token de arranque que aún no ha resuelto Preferences;
   * leer la variable es lo único síncrono posible ahí.
   */
  private installIdCache: string | null = null;

  /**
   * Espejo en memoria del mapa reserva -> token de acceso.
   *
   * Igual que el install id, se lee de forma síncrona porque el servicio de
   * API arma las cabeceras antes de poder esperar un `await`.
   */
  private bookingTokensCache: Record<string, string> = {};

  private readonly online$ = new BehaviorSubject<boolean>(typeof navigator !== 'undefined' ? navigator.onLine : true);

  /** Stream de conectividad (plugins + fallback web). */
  readonly networkStatus$: Observable<boolean> = this.online$.pipe(distinctUntilChanged());

  constructor() {
    if (this.isNative) {
      void Network.addListener('networkStatusChange', (status) => this.online$.next(status.connected));
    }

    if (typeof window !== 'undefined') {
      window.addEventListener('online', () => this.online$.next(true));
      window.addEventListener('offline', () => this.online$.next(false));
    }
  }

  isOnlineSync(): boolean {
    return this.online$.value;
  }

  /** Estado de red actual (promesa, útil para una sola comprobación). */
  networkStatus(): Promise<boolean> {
    if (this.isNative) {
      return Network.getStatus().then((status) => status.connected);
    }
    return Promise.resolve(this.online$.value);
  }

  /** Posición actual del dispositivo. */
  getPosition(): Promise<GeoPosition | null> {
    if (this.isNative) {
      return Geolocation.getCurrentPosition()
        .then((pos) => ({ latitude: pos.coords.latitude, longitude: pos.coords.longitude }))
        .catch(() => null);
    }

    return new Promise((resolve) => {
      if (!('geolocation' in navigator)) {
        resolve(null);
        return;
      }
      navigator.geolocation.getCurrentPosition(
        (pos) => resolve({ latitude: pos.coords.latitude, longitude: pos.coords.longitude }),
        () => resolve(null),
        { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 },
      );
    });
  }

  /** Share sheet nativo; en web cae a navigator.share y luego al portapapeles. */
  async share(data: SharePayload): Promise<void> {
    try {
      if (this.isNative) {
        await Share.share({ title: data.title, text: data.text, url: data.url });
        return;
      }
    } catch {
      // Nativos sin soporte fallan -> probar la vía web
    }

    try {
      if (typeof navigator.share === 'function') {
        await navigator.share({ title: data.title, text: data.text, url: data.url });
        return;
      }
    } catch {
      // El usuario canceló el share sheet
    }

    if (data.url && typeof navigator.clipboard?.writeText === 'function') {
      await navigator.clipboard.writeText(data.url);
    }
  }

  /**
   * Identificador de instalación único y persistente (sin login).
   *
   * Sigue siendo síncrono porque lo consume el interceptor de HTTP, que corre
   * antes de que exista un `await` que esperar. La persistencia en dos sitios
   * no es complejidad gratuita sino una reparación, y el motivo es concreto:
   *
   * En iOS, el `localStorage` de la WebView vive en el almacén de datos del
   * webview, que el sistema puede purgar bajo presión de almacenamiento o al
   * actualizar el dispositivo. Si eso pasa, `localStorage` llega vacío, se
   * acuña un UUID nuevo y el usuario tiene derecho a un **segundo comentario**
   * del mismo teléfono, además de reiniciar su cuota del rate limit. Ese
   * identificador es lo único que sostiene la regla de "una opinión por
   * dispositivo", así que perderlo no es un detalle menor.
   *
   * `Preferences` escribe en UserDefaults / SharedPreferences, que el sistema
   * no evacua. Por eso `localStorage` sigue siendo el camino síncrono —es lo
   * que hay disponible dentro de un interceptor— pero `restoreInstallId()` se
   * ejecuta al arrancar y vuelve a sellar `localStorage` desde Preferences si
   * encuentra que se ha perdido.
   */
  installId(): string {
    const cached = this.installIdCache;
    if (cached) {
      return cached;
    }

    let stored: string | null = null;
    try {
      stored = localStorage.getItem(INSTALL_ID_KEY);
    } catch {
      // Modo privado o almacenamiento bloqueado: el ID vive solo en memoria
      // durante esta sesión, que es mejor que bloquear el arranque.
    }

    if (stored) {
      this.installIdCache = stored;
      return stored;
    }

    const id = mintInstallId();
    this.installIdCache = id;

    try {
      localStorage.setItem(INSTALL_ID_KEY, id);
    } catch {
      // Ver arriba.
    }

    // Se escribe en paralelo y sin esperar: Preferences es la copia durable,
    // pero una petición saliente no puede bloquearse por un `await`.
    if (this.isNative) {
      void Preferences.set({ key: INSTALL_ID_KEY, value: id });
    }

    return id;
  }

  /**
   * Reconcilia `localStorage` con la copia durable de Preferences.
   *
   * Se llama una vez al arrancar, antes de que salga la primera petición. En
   * nativo, Preferences manda: si tiene un valor, ese es el bueno y se vuelve a
   * escribir en `localStorage` aunque allí falte. Si no lo tiene, se siembra con
   * lo que haya generado `installId()`.
   *
   * Nunca lanza: un fallo aquí solo significa que el ID irá en `localStorage`
   * como siempre, que es el comportamiento de antes.
   */
  async restoreInstallId(): Promise<void> {
    if (!this.isNative) {
      return;
    }

    try {
      const { value } = await Preferences.get({ key: INSTALL_ID_KEY });

      if (!value) {
        await Preferences.set({ key: INSTALL_ID_KEY, value: this.installId() });
        return;
      }

      this.installIdCache = value;

      try {
        if (localStorage.getItem(INSTALL_ID_KEY) !== value) {
          localStorage.setItem(INSTALL_ID_KEY, value);
        }
      } catch {
        // Sin `localStorage` no hay nada que reparar; Preferences ya basta.
      }
    } catch {
      // Preferences no disponible en esta plataforma: se sigue con localStorage.
    }
  }

  /**
   * Token de acceso de una reserva guardada en este dispositivo.
   *
   * Síncrono por la misma razón que `installId()`: el servicio de API arma
   * las cabeceras de cada petición y no puede esperar una promesa. Devuelve
   * `null` cuando la reserva se creó antes de existir este token (legacy) o
   * nunca se guardó aquí.
   */
  bookingToken(code: string): string | null {
    const cached = this.bookingTokensCache[code];
    if (cached) {
      return cached;
    }

    const token = this.readBookingTokens()[code] ?? null;
    if (token) {
      this.bookingTokensCache[code] = token;
    }
    return token;
  }

  /**
   * Guarda el token de acceso de una reserva recién creada.
   *
   * Misma estrategia de doble persistencia que el install id: `localStorage`
   * es el camino síncrono y Preferences la copia durable (UserDefaults /
   * SharedPreferences) que sobrevive a una purga de la WebView en iOS.
   */
  saveBookingToken(code: string, token: string): void {
    const stored = this.readBookingTokens();
    stored[code] = token;
    this.bookingTokensCache[code] = token;

    try {
      localStorage.setItem(BOOKING_TOKENS_KEY, JSON.stringify(stored));
    } catch {
      // Sin `localStorage` el token vive solo en memoria durante la sesión.
    }

    if (this.isNative) {
      // Fire-and-forget: un borrado/persistencia no puede bloquear la reserva.
      void Preferences.set({ key: BOOKING_TOKENS_KEY, value: JSON.stringify(stored) });
    }
  }

  /**
   * Reconcilia el mapa de tokens en memoria con la copia durable de
   * Preferences, igual que `restoreInstallId()`.
   *
   * Se ejecuta al arrancar de la app antes del primer polling de reservas.
   * Hace merge con lo que ya haya en memoria para no perder un token
   * guardado durante la propia arrancada.
   */
  async restoreBookingTokens(): Promise<void> {
    if (!this.isNative) {
      return;
    }

    try {
      const { value } = await Preferences.get({ key: BOOKING_TOKENS_KEY });
      if (!value) {
        return;
      }

      let stored: Record<string, string>;
      try {
        stored = JSON.parse(value);
      } catch {
        return;
      }

      this.bookingTokensCache = { ...stored, ...this.bookingTokensCache };

      const merged = JSON.stringify(this.bookingTokensCache);
      try {
        if (localStorage.getItem(BOOKING_TOKENS_KEY) !== merged) {
          localStorage.setItem(BOOKING_TOKENS_KEY, merged);
        }
      } catch {
        // Sin `localStorage` no hay nada que reparar; la memoria ya basta.
      }
    } catch {
      // Preferences no disponible en esta plataforma: se sigue con localStorage.
    }
  }

  /**
   * Borra todos los tokens de reserva guardados.
   *
   * Parte del borrado de datos del usuario (ver `DataPrivacyService`): sin
   * estos tokens el dispositivo ya no puede consultar las reservas, aunque
   * sigan existiendo para el proveedor.
   */
  clearBookingTokens(): void {
    this.bookingTokensCache = {};

    try {
      localStorage.removeItem(BOOKING_TOKENS_KEY);
    } catch {
      // Nada que limpiar.
    }

    if (this.isNative) {
      void Preferences.remove({ key: BOOKING_TOKENS_KEY });
    }
  }

  /**
   * Lee el mapa de tokens desde `localStorage`.
   *
   * Devuelve un objeto siempre utilizable (aunque vacío) ante JSON corrupto
   * o almacenamiento bloqueado, para que los demás métodos nunca lancen.
   */
  private readBookingTokens(): Record<string, string> {
    try {
      const raw = localStorage.getItem(BOOKING_TOKENS_KEY);
      if (!raw) {
        return {};
      }
      const parsed = JSON.parse(raw);
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch {
      return {};
    }
  }

  /** Notificación local al dispositivo (solo nativo; noop en web). */
  async notify(title: string, body: string, id = 1): Promise<boolean> {
    if (!this.isNative) {
      return false;
    }

    void this.ensureNotificationPermission();
    await this.ensureChannel();

    await LocalNotifications.schedule({
      notifications: [
        {
          id,
          title,
          body,
          channelId: 'seven-trip',
        },
      ],
    });
    return true;
  }

  /**
   * Cancela las notificaciones locales ya programadas.
   *
   * Parte del borrado de datos del usuario: aunque el aviso de un estado ya
   * no lleve datos personales, es contenido que la app dejó en el
   * dispositivo y el usuario pidió quitar.
   */
  async cancelNotifications(): Promise<void> {
    if (!this.isNative) {
      return;
    }

    try {
      await LocalNotifications.cancelAll();
    } catch {
      // Plataforma sin soporte — no hay nada que cancelar.
    }
  }

  private async ensureChannel(): Promise<void> {
    if (Capacitor.getPlatform() !== 'android') {
      return;
    }
    try {
      await LocalNotifications.createChannel({
        id: 'seven-trip',
        name: 'Tours y reservas',
        importance: 5,
      });
    } catch {
      // Plataforma sin soporte — se ignora.
    }
  }

  private async ensureNotificationPermission(): Promise<void> {
    const permission = await LocalNotifications.requestPermissions();
    if (permission.display === 'denied') {
      return;
    }
  }
}