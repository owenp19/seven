import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output, inject } from '@angular/core';

import { IonIcon } from '@ionic/angular';

import { Promotion } from '../../../models';
import { TranslationService } from '../../../core/services/translation.service';
import { DATE_LOCALE } from '../../../utils/formatters';
import { CurrencyPipe } from '../../pipes/currency/currency.pipe';
import { SafeImgDirective } from '../../directives/safe-img/safe-img.directive';

/**
 * PromoCard
 *
 * Tarjeta horizontal para el carrusel de promociones: difiere de la de
 * descubrimiento en que el porcentaje de descuento es el protagonista
 * (badge Soft Coral) y no hay rating ni ubicación. La etiqueta "hasta el
 * {fecha}" se localiza aquí con la locale activa de la app.
 */
@Component({
  selector: 'app-promo-card',
  templateUrl: './promo-card.component.html',
  styleUrls: ['./promo-card.component.scss'],
  standalone: true,
  imports: [IonIcon, CurrencyPipe, SafeImgDirective],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PromoCardComponent {
  private readonly i18n = inject(TranslationService);

  @Input({ required: true }) promo!: Promotion;

  @Output() open = new EventEmitter<Promotion>();

  get title(): string {
    return this.promo.title;
  }

  get providerName(): string {
    return this.promo.providerName;
  }

  get image(): string {
    return this.promo.image;
  }

  get discount(): number {
    return this.promo.discount;
  }

  get price(): number {
    return this.promo.price ?? 0;
  }

  /** Plazo localizado del descuento, p. ej. "Hasta el 15 nov". */
  get untilLabel(): string {
    const { endDate } = this.promo;
    if (!endDate) {
      return '';
    }

    const parsed = new Date(`${endDate}T00:00:00`);
    if (Number.isNaN(parsed.getTime())) {
      return '';
    }

    const locale = DATE_LOCALE[this.i18n.language()] ?? DATE_LOCALE.es;
    const date = parsed.toLocaleDateString(locale, { day: 'numeric', month: 'short' });
    return this.i18n.t('home.promoUntil', { date });
  }
}