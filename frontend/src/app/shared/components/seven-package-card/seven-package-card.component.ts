import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';

import { IonIcon } from '@ionic/angular';

import { AssistantItemType, AssistantPackage, AssistantRecommendation } from '../../../models/assistant.model';
import type { TranslationKey } from '../../../core/services/translation.service';
import { SafeImgDirective } from '../../directives/safe-img/safe-img.directive';
import { TranslatePipe } from '../../pipes/translate/translate.pipe';
import { assistantTypeKey } from '../../utils/assistant';
import { SevenBudgetMeterComponent } from '../seven-budget-meter/seven-budget-meter.component';

/**
 * SevenPackageCard
 *
 * Plan (combinación de servicios) que cabe en el presupuesto, presentado como
 * ITINERARIO paso a paso, no como una factura. El total y el restante los
 * calcula el servidor: aquí solo se pintan. El presupuesto (`total + restante`)
 * alimenta el medidor; los precios por ítem no se muestran porque son unitarios
 * y su suma no tiene por qué coincidir con el total.
 */
@Component({
  selector: 'app-seven-package-card',
  templateUrl: './seven-package-card.component.html',
  styleUrls: ['./seven-package-card.component.scss'],
  standalone: true,
  imports: [IonIcon, SafeImgDirective, TranslatePipe, SevenBudgetMeterComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SevenPackageCardComponent {
  @Input({ required: true }) package!: AssistantPackage;

  @Output() openItem = new EventEmitter<AssistantRecommendation>();

  typeKey(type: AssistantItemType): TranslationKey {
    return assistantTypeKey(type);
  }

  /** Presupuesto implícito del plan (el backend garantiza total ≤ presupuesto). */
  get budget(): number {
    return this.package.total + this.package.remaining;
  }

  onOpenItem(item: AssistantRecommendation): void {
    if (item.route) {
      this.openItem.emit(item);
    }
  }
}
