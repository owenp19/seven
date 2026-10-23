import { ChangeDetectionStrategy, Component, WritableSignal, computed, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { IonContent, IonIcon, IonRefresher, IonRefresherContent, IonSkeletonText } from '@ionic/angular';
import { Router } from '@angular/router';

import { BookingsStoreService, LocalBooking } from '../../core/services/bookings-store.service';
import { HapticsService } from '../../core/services/haptics.service';
import { TranslationService } from '../../core/services/translation.service';
import { BookingCardAction, BookingCardComponent } from '../../shared/components/booking-card/booking-card.component';
import { DEFAULT_OPERATOR_PHONE } from '../../shared/constants/island';
import { isLiveBooking } from '../../shared/constants/booking-status';
import { TranslatePipe } from '../../shared/pipes/translate/translate.pipe';
import { whatsappLink } from '../../utils/formatters';

type BookingSegment = 'active' | 'history';

interface BookingsView {
  active: LocalBooking[];
  history: LocalBooking[];
  /** Próximo plan: la reserva activa más cercana con fecha futura. */
  hero: LocalBooking | null;
  /** Activas que no son el hero. */
  rest: LocalBooking[];
  showSegment: boolean;
}

/** Marca de tiempo segura; las fechas inválidas se van al final. */
function timeOf(iso: string): number {
  const value = new Date(iso).getTime();
  return Number.isNaN(value) ? Number.POSITIVE_INFINITY : value;
}

/**
 * BookingsPage
 *
 * "Mis reservas" — reservas anónimas guardadas en el dispositivo. El estado
 * se sigue desde el backend y cada cambio dispara una notificación local
 * dirigida (solo este teléfono), sin cuenta ni push global.
 */
@Component({
  selector: 'app-bookings',
  templateUrl: './bookings.page.html',
  styleUrls: ['./bookings.page.scss'],
  standalone: true,
  imports: [
    IonContent,
    IonIcon,
    IonRefresher,
    IonRefresherContent,
    IonSkeletonText,
    BookingCardComponent,
    TranslatePipe,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class BookingsPage {
  private readonly store = inject(BookingsStoreService);
  private readonly haptics = inject(HapticsService);
  private readonly router = inject(Router);
  private readonly i18n = inject(TranslationService);

  readonly ready = this.store.ready;
  readonly loadFailed = this.store.loadFailed;

  private readonly bookings = toSignal(this.store.list$, { initialValue: [] as LocalBooking[] });

  readonly segment = signal<BookingSegment>('active');
  readonly refreshing = signal<ReadonlySet<string>>(new Set());
  readonly refreshFailed = signal<ReadonlySet<string>>(new Set());

  readonly ghosts = [0, 1, 2];

  readonly view = computed<BookingsView>(() => {
    const list = this.bookings();
    const now = Date.now();

    const active = list
      .filter((item) => isLiveBooking(item.status))
      .sort((a, b) => timeOf(a.startAt) - timeOf(b.startAt));

    const history = list
      .filter((item) => !isLiveBooking(item.status))
      .sort((a, b) => b.updatedAt - a.updatedAt);

    const hero = active.find((item) => timeOf(item.startAt) > now) ?? null;
    const rest = hero ? active.filter((item) => item.code !== hero.code) : active;

    return { active, history, hero, rest, showSegment: active.length > 0 && history.length > 0 };
  });

  readonly isEmpty = computed(() => this.view().active.length === 0 && this.view().history.length === 0);

  /** Grupo realmente visible: si no hay segmento, el único grupo con datos. */
  readonly visibleGroup = computed<BookingSegment>(() =>
    this.view().showSegment
      ? this.segment()
      : this.view().active.length > 0
        ? 'active'
        : 'history',
  );

  get summaryLabel(): string {
    const count = this.view().active.length;
    return count === 1 ? this.i18n.t('bookings.summaryOne') : this.i18n.t('bookings.summaryMany', { count });
  }

  selectSegment(segment: BookingSegment): void {
    if (segment === this.segment()) {
      return;
    }
    void this.haptics.selection();
    this.segment.set(segment);
  }

  isRefreshing(code: string): boolean {
    return this.refreshing().has(code);
  }

  hasRefreshFailed(code: string): boolean {
    return this.refreshFailed().has(code);
  }

  /** Maneja las acciones que la tarjeta delega a la página. */
  onCardAction(action: BookingCardAction, booking: LocalBooking): void {
    if (action === 'refresh') {
      void this.refreshOne(booking.code);
    } else {
      this.contact(booking);
    }
  }

  /** Recarga el estado de una reserva concreta. */
  async refreshOne(code: string): Promise<void> {
    this.mark(this.refreshing, code, true);
    const fresh = await this.store.refresh(code);
    this.mark(this.refreshing, code, false);
    this.mark(this.refreshFailed, code, fresh === null);
  }

  /** Recarga todas las reservas activas (pull-to-refresh). */
  async refreshAll(event: Event): Promise<void> {
    const active = this.view().active;
    await Promise.all(active.map((item) => this.store.refresh(item.code)));
    const target = event.target as HTMLIonRefresherElement;
    await target.complete();
  }

  /** WhatsApp al operador con la entidad y el código (sin login). */
  contact(booking: LocalBooking): void {
    void this.haptics.selection();
    const message = this.i18n.t('bookings.contactMessage', {
      entity: booking.entity,
      code: booking.code,
    });
    window.open(whatsappLink(DEFAULT_OPERATOR_PHONE, message), '_blank', 'noopener');
  }

  retryLoad(): void {
    void this.haptics.selection();
    this.store.retryLoad();
  }

  goToExplore(): void {
    void this.haptics.selection();
    void this.router.navigate(['/explore']);
  }

  private mark(target: WritableSignal<ReadonlySet<string>>, code: string, on: boolean): void {
    target.update((set) => {
      const next = new Set(set);
      if (on) {
        next.add(code);
      } else {
        next.delete(code);
      }
      return next;
    });
  }
}
