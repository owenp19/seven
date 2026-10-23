import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';

import { IonIcon } from '@ionic/angular';

import { AssistantRecommendation } from '../../../models/assistant.model';
import type { TranslationKey } from '../../../core/services/translation.service';
import { CurrencyPipe } from '../../pipes/currency/currency.pipe';
import { SafeImgDirective } from '../../directives/safe-img/safe-img.directive';
import { TranslatePipe } from '../../pipes/translate/translate.pipe';
import { assistantPriceLabelKey, assistantTypeKey } from '../../utils/assistant';

/**
 * SevenAssistantRecommendationCard
 *
 * Tarjeta de un servicio real recomendado por el asistente: imagen, tipo,
 * nombre, valoración, ubicación y precio unitario ("Desde …"). Si el backend
 * no devuelve una ruta es porque no existe pantalla para esa vertical todavía
 * y la tarjeta deja de ser navegable (nada de enlaces muertos).
 */
@Component({
  selector: 'app-seven-assistant-recommendation-card',
  templateUrl: './seven-assistant-recommendation-card.component.html',
  styleUrls: ['./seven-assistant-recommendation-card.component.scss'],
  standalone: true,
  imports: [IonIcon, CurrencyPipe, SafeImgDirective, TranslatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SevenAssistantRecommendationCardComponent {
  @Input({ required: true }) recommendation!: AssistantRecommendation;

  @Output() open = new EventEmitter<AssistantRecommendation>();

  get typeKey(): TranslationKey {
    return assistantTypeKey(this.recommendation.type);
  }

  get priceKey(): TranslationKey {
    return assistantPriceLabelKey(this.recommendation.priceUnit);
  }

  get ratingLabel(): string {
    return this.recommendation.rating.toLocaleString('es-CO', {
      minimumFractionDigits: 1,
      maximumFractionDigits: 1,
    });
  }

  onOpen(): void {
    if (this.recommendation.route) {
      this.open.emit(this.recommendation);
    }
  }
}
