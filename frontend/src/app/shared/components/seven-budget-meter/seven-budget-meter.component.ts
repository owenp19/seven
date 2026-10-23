import { ChangeDetectionStrategy, Component, Input } from '@angular/core';

import { IonIcon } from '@ionic/angular';

import { TranslatePipe } from '../../pipes/translate/translate.pipe';
import { formatCurrency } from '../../../utils/formatters';

/**
 * SevenBudgetMeter
 *
 * Medidor de presupuesto elegante (no un dashboard financiero). Se alimenta del
 * plan calculado por el servidor: el total gastado (`spent`) y el presupuesto
 * (`budget`) que ya viene implícito en la respuesta (`total + remaining`). No
 * inventa datos: si no hay presupuesto, no se usa el componente.
 */
@Component({
  selector: 'app-seven-budget-meter',
  templateUrl: './seven-budget-meter.component.html',
  styleUrls: ['./seven-budget-meter.component.scss'],
  standalone: true,
  imports: [IonIcon, TranslatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SevenBudgetMeterComponent {
  @Input({ required: true }) spent!: number;
  @Input({ required: true }) budget!: number;

  get spentLabel(): string {
    return formatCurrency(this.spent);
  }

  get budgetLabel(): string {
    return formatCurrency(this.budget);
  }

  get remaining(): number {
    return Math.max(this.budget - this.spent, 0);
  }

  get remainingLabel(): string {
    return formatCurrency(this.remaining);
  }

  /** Porcentaje gastado, acotado a [0, 100] para la barra. */
  get percent(): number {
    if (this.budget <= 0) {
      return 0;
    }
    return Math.min(Math.max(Math.round((this.spent / this.budget) * 100), 0), 100);
  }
}
