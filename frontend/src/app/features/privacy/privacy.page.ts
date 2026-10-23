import { Component, ChangeDetectionStrategy, computed, inject, signal } from '@angular/core';
import {
  AlertController,
  IonContent,
  IonIcon,
  IonSpinner,
} from '@ionic/angular';

import { DataPrivacyService } from '../../core/services/data-privacy.service';
import type { DataInventory } from '../../core/services/data-privacy.service';
import { HapticsService } from '../../core/services/haptics.service';
import { TranslationService } from '../../core/services/translation.service';
import { PRIVACY_CONTENT } from './privacy-content';
import type { PrivacyContent } from './privacy-content';
import { TranslatePipe } from '../../shared/pipes/translate/translate.pipe';
import { PageHeaderComponent } from '../../shared/components/page-header/page-header.component';

/** Qué acción de borrado está en curso, para bloquear los botones. */
type BusyAction = 'device' | 'everything' | null;

/**
 * Rellena los `{placeholders}` del documento legal.
 *
 * Los textos de resultado son los únicos con variables y viven en
 * `privacy-content.ts` para que el documento siga siendo trilingüe por
 * completo; aquí se sustituyen con los números que devuelve el borrado real.
 */
function fill(template: string, params: Record<string, string | number>): string {
  return template.replace(/\{(\w+)\}/g, (match, name: string) =>
    name in params ? String(params[name]) : match,
  );
}

/**
 * Política de Privacidad
 *
 * El documento vive en `privacy-content.ts` (no en `TranslationService`,
 * porque son párrafos y no etiquetas de interfaz) y esta pantalla lo monta.
 *
 * Lo que hace esta página y no suele hacer una política:
 *
 * - Muestra el inventario **real** leído del dispositivo, no una descripción
 *   teórica. Si no hay datos, dice que no hay datos.
 * - Ejecuta el borrado con dos alcances distintos: solo el teléfono, o el
 *   teléfono más las reservas del servidor. Cada uno explica qué conserva.
 * - Deja la tercera vía, el correo, para lo que la app no puede tocar
 *   (una reserva ya confirmada por un proveedor, por ejemplo).
 */
@Component({
  selector: 'app-privacy',
  templateUrl: './privacy.page.html',
  styleUrls: ['./privacy.page.scss'],
  standalone: true,
  imports: [
    IonContent,
    IonIcon,
    IonSpinner,
    PageHeaderComponent,
    TranslatePipe,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PrivacyPage {
  private readonly privacy = inject(DataPrivacyService);
  private readonly haptics = inject(HapticsService);
  private readonly alerts = inject(AlertController);
  private readonly translation = inject(TranslationService);

  /** El documento completo se resuelve por idioma activo. */
  readonly content = computed<PrivacyContent>(() => PRIVACY_CONTENT[this.translation.language()]);

  /** Datos reales del dispositivo; `null` mientras se lee el almacenamiento. */
  readonly inventory = signal<DataInventory | null>(null);

  /** Acción en curso, para deshabilitar los botones y mostrar el spinner. */
  readonly busy = signal<BusyAction>(null);

  /** Mensaje de resultado tras un borrado. */
  readonly feedback = signal<string | null>(null);

  /** `true` cuando no queda nada guardado en el teléfono. */
  readonly isEmpty = computed<boolean>(() => {
    const data = this.inventory();

    if (!data) {
      return false;
    }

    return (
      data.deviceKeys.length === 0 &&
      data.storageKeys.length === 0 &&
      data.bookingCount === 0 &&
      data.favoriteCount === 0 &&
      data.recentCount === 0 &&
      !data.guestName &&
      !data.guestPhone
    );
  });

  constructor() {
    void this.loadInventory();
  }

  /** Relee el inventario; se usa al volver de un borrado o al reentrar. */
  async loadInventory(): Promise<void> {
    this.inventory.set(await this.privacy.inventory());
  }

  /**
   * Salta a una sección del documento.
   *
   * Usa `scrollIntoView` sobre el id en vez de un router outlet: el contenido
   * entero ya está en la misma pantalla, así que navegar de nuevo solo
   * costaría un parpadeo de loader y un chunk que ya está en memoria.
   */
  scrollToSection(id: string): void {
    void this.haptics.selection();

    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  onRequestByEmail(): void {
    void this.haptics.selection();

    const installId = this.inventory()?.installId ?? '';
    window.location.href = this.privacy.deletionRequestLink(installId);
  }

  /** Borra solo el dispositivo, previa confirmación. */
  async onEraseDevice(): Promise<void> {
    if (!(await this.confirm('confirmDeviceTitle', 'confirmDeviceBody', 'confirmDeviceConfirm'))) {
      return;
    }

    this.busy.set('device');
    this.feedback.set(null);

    try {
      const erased = await this.privacy.eraseDevice();
      await this.loadInventory();
      this.feedback.set(erased ? this.content().doneDevice : this.content().donePartial);
      await (erased ? this.haptics.success() : this.haptics.warning());
    } finally {
      this.busy.set(null);
    }
  }

  /**
   * Cancela las reservas en el servidor y borra el dispositivo.
   *
   * Es la acción destructiva larga: por eso necesita confirmación y avisa
   * si alguna reserva no se pudo cancelar (sin red, o porque el proveedor
   * ya la cerró), en cuyo caso el correo sigue siendo la salida.
   */
  async onEraseEverything(): Promise<void> {
    if (!(await this.confirm('confirmEverythingTitle', 'confirmEverythingBody', 'confirmEverythingConfirm'))) {
      return;
    }

    this.busy.set('everything');
    this.feedback.set(null);

    try {
      const result = await this.privacy.eraseEverything();
      await this.loadInventory();

      if (result.failedBookings > 0) {
        this.feedback.set(fill(this.content().donePartial, { failed: result.failedBookings }));
        await this.haptics.warning();
      } else {
        this.feedback.set(fill(this.content().doneEverything, { count: result.cancelledBookings }));
        await this.haptics.success();
      }
    } finally {
      this.busy.set(null);
    }
  }

  onDismissFeedback(): void {
    this.feedback.set(null);
  }

  /**
   * Confirmación destructiva nativa.
   *
   * Va en un `AlertController` y no en un `confirm()` del navegador porque
   * esta app es un contenedor Capacitor: el diálogo del sistema es lo único
   * que el usuario reconoce como "esto borra cosas de verdad".
   */
  private async confirm(
    titleKey: 'confirmDeviceTitle' | 'confirmEverythingTitle',
    bodyKey: 'confirmDeviceBody' | 'confirmEverythingBody',
    confirmKey: 'confirmDeviceConfirm' | 'confirmEverythingConfirm',
  ): Promise<boolean> {
    const copy = this.content();
    void this.haptics.selection();

    const alert = await this.alerts.create({
      header: copy[titleKey],
      message: copy[bodyKey],
      cssClass: 'seven-destructive-alert',
      buttons: [
        { text: copy.cancel, role: 'cancel' },
        { text: copy[confirmKey], role: 'confirm' },
      ],
    });

    await alert.present();
    const { role } = await alert.onDidDismiss();

    return role === 'confirm';
  }
}
