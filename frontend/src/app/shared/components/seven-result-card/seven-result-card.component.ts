import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { RouterLink } from '@angular/router';

import { IonIcon } from '@ionic/angular';

import { CurrencyPipe } from '../../pipes/currency/currency.pipe';
import { TranslatePipe } from '../../pipes/translate/translate.pipe';
import { SafeImgDirective } from '../../directives/safe-img/safe-img.directive';
import { RatingStarsComponent } from '../rating-stars/rating-stars.component';

export type SevenResultCardVariant = 'list' | 'grid';

/**
 * SevenResultCard — tarjeta de resultado única del catálogo.
 *
 * Reemplaza a las variantes solapadas (`catalog-card`, la antigua
 * `seven-result-card` de Explore, `experience-card`, `service-card` y
 * `accommodation-card`). Dos layouts sobre los mismos datos de presentación:
 *
 *  - `variant="list"`: horizontal compacta, para listas de resultados
 *    (Explore). Imagen a la izquierda + metadatos densos a la derecha.
 *  - `variant="grid"`: vertical con portada, para rejillas de categoría
 *    (tours, restaurantes, posadas, promociones y "te puede interesar").
 *
 * Toda la información viene de la API; el componente no inventa ratings,
 * precios ni ubicaciones.
 */
@Component({
  selector: 'app-seven-result-card',
  templateUrl: './seven-result-card.component.html',
  styleUrls: ['./seven-result-card.component.scss'],
  standalone: true,
  imports: [IonIcon, RouterLink, SafeImgDirective, CurrencyPipe, TranslatePipe, RatingStarsComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SevenResultCardComponent {
  @Input() variant: SevenResultCardVariant = 'list';
  @Input() image = '';
  @Input() name = '';
  @Input() category = '';
  @Input() description = '';
  @Input() rating = 0;
  @Input() ratingCount = 0;
  @Input() price: number | null = null;
  @Input() priceLabel = '';
  @Input() location = '';
  @Input() verified = false;
  @Input() link: string | readonly (string | number)[] = '';
  @Input() alt = '';

  get ratingLabel(): string {
    return this.rating.toLocaleString('es-CO', {
      minimumFractionDigits: 1,
      maximumFractionDigits: 1,
    });
  }
}
