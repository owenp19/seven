import { Injectable, computed, inject, signal } from '@angular/core';

import { NativeService } from './native.service';

const MODE_KEY = 'seven_offline_mode';
const AUTO_KEY = 'seven_offline_auto';

/**
 * ConnectivityService
 *
 * Estado de red de la app, en un signal.
 *
 * `NativeService` ya expone `networkStatus$`, pero es un `Observable` de
 * `@capacitor/network` pensado para nativo. Esta capa lo convierte a signal
 * y le añade lo que el usuario controla desde Configuración:
 *
 *  - `manualOffline`: el usuario.APAGÓ la red a propósito (modo avión
 *    simulado, ahorro de datos). Aparece junto al estado real.
 *  - `autoOffline`: bajar el catálogo a wifi y no gastar datos móviles.
 *
 * `isOnline` es la combinación que de verdad importa: si el usuario pidió
 * modo offline, la app se comporta como si no hubiera red aunque la haya.
 */
@Injectable({ providedIn: 'root' })
export class ConnectivityService {
  private readonly native = inject(NativeService);

  /** Estado real de la interfaz de red del dispositivo. */
  private readonly realOnline = signal(this.native.isOnlineSync());

  /** El usuario forzó "sin conexión" desde Configuración. */
  readonly manualOffline = signal(localStorage.getItem(MODE_KEY) === '1');

  /** Descargar catálogo automáticamente solo con wifi. */
  readonly autoOffline = signal(localStorage.getItem(AUTO_KEY) === '1');

  /** Conectividad real, sin Preferences de por medio. */
  readonly isReallyOnline = this.realOnline.asReadonly();

  /**
   * Conectividad efectiva: lo que la app debe creer.
   *
   * El modo manual gana sobre la red real porque es una orden explícita del
   * usuario; al revés sería imposible "volver a estar online" desde la app.
   */
  readonly isOnline = computed(() => !this.manualOffline() && this.realOnline());

  /** `true` cuando no se puede tocar la red, por el motivo que sea. */
  readonly isOffline = computed(() => !this.isOnline());

  constructor() {
    this.native.networkStatus$.subscribe((online) => this.realOnline.set(online));
  }

  setManualOffline(enabled: boolean): void {
    this.manualOffline.set(enabled);

    if (enabled) {
      localStorage.setItem(MODE_KEY, '1');
    } else {
      localStorage.removeItem(MODE_KEY);
    }
  }

  setAutoOffline(enabled: boolean): void {
    this.autoOffline.set(enabled);

    if (enabled) {
      localStorage.setItem(AUTO_KEY, '1');
    } else {
      localStorage.removeItem(AUTO_KEY);
    }
  }
}
