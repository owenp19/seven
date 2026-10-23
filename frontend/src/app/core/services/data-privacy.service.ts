import { Injectable, inject } from '@angular/core';

import { firstValueFrom } from 'rxjs';

import { APP_CONFIG, STORAGE } from '../tokens';
import type { AppConfig } from '../config/app-config.interface';
import { BookingService } from '../../services/booking.service';
import { BookingsStoreService } from './bookings-store.service';
import { DataCacheService } from './data-cache.service';
import { FavoritesService } from './favorites.service';
import { NativeService } from './native.service';

/**
 * Claves que la app escribe en `localStorage`. El prefijo `seven_` es el
 * contrato: borrarlas todas equivale a desinstalar la app sin perder nada
 * del usuario.
 */
export const SEVEN_LOCAL_KEYS = [
  'seven_welcome_completed',
  'seven_language',
  'seven_reduce_motion',
  'seven_install_id',
  'seven_theme',
  'seven_text_size',
  'seven_offline_mode',
  'seven_offline_auto',
  // Mapa reserva -> token de acceso; borrarlo impide consultar las reservas
  // desde este dispositivo (sin login no hay otra vía).
  'seven_booking_tokens',
] as const;

/** Claves que la app escribe en @ionic/storage (IndexedDB / nativo). */
export const SEVEN_STORAGE_KEYS = [
  'seven:bookings',
  'seven:guest',
  'seven:favorites',
  'seven:recent',
  'seven:preferences',
  // Copia del catálogo descargada para usar sin conexión.
  'seven:offline-catalog',
] as const;

/** Una categoría de dato con su valor real leído del dispositivo. */
export interface StoredDataEntry {
  readonly key: string;
  readonly where: 'device' | 'server';
  readonly value: string;
}

/** Instantánea de todo lo que la app sabe del usuario en este momento. */
export interface DataInventory {
  /** Identificador de instalación (UUID) que scopea las reservas. */
  readonly installId: string;
  /** Claves presentes en `localStorage`. */
  readonly deviceKeys: readonly string[];
  /** Claves presentes en @ionic/storage. */
  readonly storageKeys: readonly string[];
  /** Favoritos guardados. */
  readonly favoriteCount: number;
  /** Servicios vistos recientemente (máx. 20). */
  readonly recentCount: number;
  /** Reservas guardadas en el dispositivo. */
  readonly bookingCount: number;
  /** Códigos de reserva: son los que también existen en el servidor. */
  readonly bookingCodes: readonly string[];
  /** Datos de contacto reutilizados para prellenar el formulario. */
  readonly guestName: string;
  readonly guestPhone: string;
  /** Idioma activo de la interfaz. */
  readonly language: string;
}

/** Resultado de una acción de borrado. */
export interface ErasureResult {
  /** Reservas que quedaron canceladas en el servidor. */
  readonly cancelledBookings: number;
  /** Reservas que el servidor no dejó cancelar (o no había red). */
  readonly failedBookings: number;
  /** `true` cuando ya no queda dato de usuario en el dispositivo. */
  readonly deviceErased: boolean;
}

/**
 * DataPrivacyService
 *
 * Convierte el derecho de supresión en algo que el usuario pueda ejecutar
 * con un botón: la Política de Privacidad no solo explica qué datos existen,
 * sino que los enumera **leídos del dispositivo real** y ofrece tres
 * salidas — borrar aquí, borrar también en el servidor, o escribirnos.
 *
 * La app es anónima por diseño (sin cuenta, sin cookies, sin publicidad),
 * así que el inventario es pequeño y vive en dos sitios: `localStorage` +
 * @ionic/storage en el dispositivo, y las reservas bajo el `install_id`
 * en el backend.
 */
@Injectable({ providedIn: 'root' })
export class DataPrivacyService {
  private readonly storage = inject(STORAGE);
  private readonly bookings = inject(BookingsStoreService);
  private readonly bookingService = inject(BookingService);
  private readonly cache = inject(DataCacheService);
  private readonly favorites = inject(FavoritesService);
  private readonly native = inject(NativeService);
  private readonly config = inject<AppConfig>(APP_CONFIG);

  /**
   * Lee qué datos hay guardados ahora mismo.
   *
   * Se usa en dos sitios: el inventario visible de la pantalla y la
   * confirmación previa a un borrado, para que el usuario sepa qué va a
   * perder antes de aceptarlo.
   */
  async inventory(): Promise<DataInventory> {
    const deviceKeys = this.presentLocalKeys();
    const storageKeys = await this.presentStorageKeys();
    const guest = await this.readGuest();

    return {
      installId: this.native.installId(),
      deviceKeys,
      storageKeys,
      favoriteCount: this.favorites.favoritesSnapshot().length,
      recentCount: this.favorites.recentSnapshot().length,
      bookingCount: this.bookings.list.length,
      bookingCodes: this.bookings.list.map((item) => item.code),
      guestName: guest.name,
      guestPhone: guest.phone,
      language: localStorage.getItem('seven_language') ?? 'es',
    };
  }

  /**
   * Borra todo lo del dispositivo y cancela las reservas en el servidor.
   *
   * Es la acción de "borrar mis datos" completa. Se cancela primero cada
   * reserva (el `DELETE /bookings/{code}` público solo permite pasar a
   * `cancelled`) y después se limpia el dispositivo, para que un fallo de
   * red no deje el teléfono en un estado a medias sin avisar.
   */
  async eraseEverything(): Promise<ErasureResult> {
    const codes = [...this.bookings.list.map((item) => item.code)];

    let cancelled = 0;
    let failed = 0;

    for (const code of codes) {
      try {
        await firstValueFrom(this.bookingService.cancel(code));
        cancelled += 1;
      } catch {
        failed += 1;
      }
    }

    const deviceErased = await this.eraseDevice();

    return { cancelledBookings: cancelled, failedBookings: failed, deviceErased };
  }

  /**
   * Borra únicamente lo que vive en este teléfono.
   *
   * No toca el servidor: la reserva sigue existiendo para el proveedor y
   * para nosotros, y se puede volver a consultar con el mismo código. Es la
   * opción para quien solo quiere limpiar el dispositivo (prestado, usado,
   * de segunda mano) sin cancelar lo que ya reservó.
   */
  async eraseDevice(): Promise<boolean> {
    for (const key of SEVEN_LOCAL_KEYS) {
      localStorage.removeItem(key);
    }

    try {
      await this.storage.clear();
    } catch {
      // Un driver que no responde no debe abortar el resto del borrado.
    }

    await this.bookings.eraseLocal();
    await this.favorites.eraseLocal();
    this.cache.clear();
    await this.native.cancelNotifications();
    this.native.clearBookingTokens();

    return this.presentLocalKeys().length === 0 && (await this.presentStorageKeys()).length === 0;
  }

  /**
   * Enlace `mailto:` con la solicitud ya redactada.
   *
   * Es la vía para cuando el usuario no puede usar el borrado automático
   * (reservas ya confirmadas por un proveedor, por ejemplo): el cuerpo
   * incluye el `install_id`, que es justo lo que necesita el equipo para
   * localizar sus registros.
   */
  deletionRequestLink(installId: string): string {
    const subject = 'Solicitud de acceso y eliminación de datos — SEVEN';
    const body = [
      'Hola, equipo de SEVEN:',
      '',
      'Solicito acceso a mis datos y/o su eliminación conforme a la Política de Privacidad de la app SEVEN.',
      '',
      `Identificador del dispositivo (install_id): ${installId}`,
      '',
      'Datos que solicito (marca los que apliquen):',
      '  [ ] Acceder a mis datos',
      '  [ ] Corregir mis datos',
      '  [ ] Portabilidad de mis datos',
      '  [ ] Eliminar mis datos',
      '',
      'Códigos de reserva (si los conoces):',
      '',
      'Gracias.',
    ].join('\n');

    return `mailto:${this.config.contact.privacyEmail}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
  }

  private presentLocalKeys(): string[] {
    return Object.keys(localStorage).filter((key) => key.startsWith('seven_'));
  }

  private async presentStorageKeys(): Promise<string[]> {
    try {
      await this.storage.create();

      const keys = await this.storage.keys();
      return keys.filter((key) => key.startsWith('seven:'));
    } catch {
      return [];
    }
  }

  private async readGuest(): Promise<{ name: string; phone: string }> {
    try {
      const guest = (await this.storage.get('seven:guest')) as { name?: string; phone?: string } | null;

      return { name: guest?.name ?? '', phone: guest?.phone ?? '' };
    } catch {
      return { name: '', phone: '' };
    }
  }
}
