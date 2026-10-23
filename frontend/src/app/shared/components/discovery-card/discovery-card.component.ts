import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';

import { IonIcon } from '@ionic/angular';

import { CurrencyPipe } from '../../pipes/currency/currency.pipe';
import { SafeImgDirective } from '../../directives/safe-img/safe-img.directive';
import { TranslatePipe } from '../../pipes/translate/translate.pipe';

export type DiscoveryCardType = 'business' | 'tour' | 'restaurant' | 'inn';

/**
 * Datos de presentación de una tarjeta de descubrimiento. La Home los
 * arma mezclando las verticales (tours, restaurantes, posadas y negocios)
 * y este componente solo se encarga de pintarlos.
 */
export interface DiscoveryCardItem {
  id: number;
  slug: string;
  type: DiscoveryCardType;
  image: string;
  name: string;
  category: string;
  rating: number;
  location: string;
  price: number;
  priceLabel: string;
  providerName: string;
  route: string[];
}

/**
 * DiscoveryCard
 *
 * Tarjeta vertical compacta para los carruseles de la Home: imagen,
 * categoría, nombre, valoración·ubicación y precio. Nada de descripciones
 * largas; todo lo que pasa aquí debe venir de la API (no se inventan
 * ratings, precios ni ubicaciones).
 */
@Component({
  selector: 'app-discovery-card',
  templateUrl: './discovery-card.component.html',
  styleUrls: ['./discovery-card.component.scss'],
  standalone: true,
  imports: [IonIcon, CurrencyPipe, SafeImgDirective, TranslatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DiscoveryCardComponent {
  @Input({ required: true }) item!: DiscoveryCardItem;
  @Input() saved = false;

  @Output() open = new EventEmitter<DiscoveryCardItem>();
  @Output() toggleSave = new EventEmitter<DiscoveryCardItem>();

  get ratingLabel(): string {
    return this.item.rating.toLocaleString('es-CO', {
      minimumFractionDigits: 1,
      maximumFractionDigits: 1,
    });
  }

  onToggleSave(event: Event): void {
    event.stopPropagation();
    this.toggleSave.emit(this.item);
  }
}