import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output, inject, signal } from '@angular/core';
import { IonIcon } from '@ionic/angular';

import { LocalBooking } from '../../../core/services/bookings-store.service';
import { TranslationKey, TranslationService } from '../../../core/services/translation.service';
import { HapticsService } from '../../../core/services/haptics.service';
import { CurrencyPipe } from '../../pipes/currency/currency.pipe';
import { TranslatePipe } from '../../pipes/translate/translate.pipe';
import {
  BOOKING_STATUS_UI,
  BookingStatusUi,
  bookableTypeIcon,
  bookableTypeLabelKey,
  isLiveBooking,
} from '../../constants/booking-status';
import { formatDateTime, formatTimeOfDay, relativeDay } from '../../../utils/formatters';

/** Acciones que la tarjeta delega a la página (fetch y contacto). */
export type BookingCardAction = 'refresh' | 'contact';

/**
 * BookingCard
 *
 * Tarjeta de una reserva del dispositivo. Presenta de un vistazo qué se
 * reservó, cuándo, para cuántos y en qué estado va; y expone una acción
 * principal (actualizar) más una secundaria (contactar) para las reservas
 * vivas. El código se puede copiar en cualquier estado.
 *
 * El contrato local (`LocalBooking`) no trae foto ni ubicación: el vertical
 * se representa con un tile de icono, sin inventar datos que no existen.
 */
@Component({
  selector: 'app-booking-card',
  templateUrl: './booking-card.component.html',
  styleUrls: ['./booking-card.component.scss'],
  standalone: true,
  imports: [IonIcon, CurrencyPipe, TranslatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class BookingCardComponent {
  @Input({ required: true }) booking!: LocalBooking;
  /** Reserva destacada como "próximo plan". */
  @Input() hero = false;
  /** La consulta de esta reserva está en curso. */
  @Input() refreshing = false;
  /** La última consulta de esta reserva falló. */
  @Input() refreshFailed = false;

  @Output() action = new EventEmitter<BookingCardAction>();

  private readonly i18n = inject(TranslationService);
  private readonly haptics = inject(HapticsService);

  /** Feedback temporal tras copiar el código. */
  readonly copied = signal(false);

  get status(): BookingStatusUi {
    return BOOKING_STATUS_UI[this.booking.status];
  }

  get live(): boolean {
    return isLiveBooking(this.booking.status);
  }

  get typeIcon(): string {
    return bookableTypeIcon(this.booking.bookableType);
  }

  get typeLabel(): TranslationKey {
    return bookableTypeLabelKey(this.booking.bookableType);
  }

  /** Fecha legible, con "Hoy/Mañana" cuando aplica, siempre localizada. */
  get whenLabel(): string {
    const day = relativeDay(this.booking.startAt);
    const time = formatTimeOfDay(this.booking.startAt, this.i18n.language());
    if (day === 'today') {
      return `${this.i18n.t('bookings.today')} · ${time}`;
    }
    if (day === 'tomorrow') {
      return `${this.i18n.t('bookings.tomorrow')} · ${time}`;
    }
    return formatDateTime(this.booking.startAt, this.i18n.language());
  }

  get guestsLabel(): string {
    return this.booking.guests === 1
      ? this.i18n.t('bookings.guestsOne')
      : this.i18n.t('bookings.guestsMany', { count: this.booking.guests });
  }

  get hasAmount(): boolean {
    return this.booking.totalAmount > 0;
  }

  onRefresh(): void {
    if (this.refreshing) {
      return;
    }
    void this.haptics.selection();
    this.action.emit('refresh');
  }

  onContact(): void {
    void this.haptics.selection();
    this.action.emit('contact');
  }

  async copyCode(): Promise<void> {
    void this.haptics.selection();
    if (await this.writeCode()) {
      this.copied.set(true);
      setTimeout(() => this.copied.set(false), 2000);
    }
  }

  private async writeCode(): Promise<boolean> {
    const clipboard = navigator.clipboard;
    if (!clipboard) {
      return false;
    }
    try {
      await clipboard.writeText(this.booking.code);
      return true;
    } catch {
      return false;
    }
  }
}
