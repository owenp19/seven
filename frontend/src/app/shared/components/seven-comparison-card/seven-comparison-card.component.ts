import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';

import { IonIcon } from '@ionic/angular';

import { AssistantRecommendation } from '../../../models/assistant.model';
import { CurrencyPipe } from '../../pipes/currency/currency.pipe';
import { SafeImgDirective } from '../../directives/safe-img/safe-img.directive';
import { TranslatePipe } from '../../pipes/translate/translate.pipe';
import { assistantPriceLabelKey } from '../../utils/assistant';
import type { TranslationKey } from '../../../core/services/translation.service';

/**
 * SevenComparisonCard
 *
 * Comparación de 2–3 opciones del MISMO tipo (todo real, viene del backend).
 * No es una tabla de escritorio: dos o tres columnas deslizables en móvil, con
 * la opción numerada para poder hablarla ("me quedo con la 2").
 */
@Component({
  selector: 'app-seven-comparison-card',
  templateUrl: './seven-comparison-card.component.html',
  styleUrls: ['./seven-comparison-card.component.scss'],
  standalone: true,
  imports: [IonIcon, CurrencyPipe, SafeImgDirective, TranslatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SevenComparisonCardComponent {
  @Input({ required: true }) recommendations!: AssistantRecommendation[];

  @Output() open = new EventEmitter<AssistantRecommendation>();

  priceKey(rec: AssistantRecommendation): TranslationKey {
    return assistantPriceLabelKey(rec.priceUnit);
  }

  ratingLabel(rec: AssistantRecommendation): string {
    return rec.rating.toLocaleString('es-CO', {
      minimumFractionDigits: 1,
      maximumFractionDigits: 1,
    });
  }

  onOpen(rec: AssistantRecommendation): void {
    if (rec.route) {
      this.open.emit(rec);
    }
  }
}
