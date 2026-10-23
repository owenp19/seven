import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';

import { IonIcon } from '@ionic/angular';

import { TranslatePipe } from '../../pipes/translate/translate.pipe';
import type { TranslationKey } from '../../../core/services/translation.service';

/** Una intención rápida de descubrimiento (icono + etiqueta + mensaje real). */
export interface QuickIntent {
  icon: string;
  labelKey: TranslationKey;
  /** Clave del mensaje real que se envía al asistente al pulsarla. */
  promptKey: TranslationKey;
}

/**
 * SevenQuickIntent
 *
 * Tarjeta pulsable del bloque de descubrimiento. No ejecuta nada por sí sola:
 * emite la clave del mensaje para que la página la resuelva y la envíe al
 * asistente REAL (nada de respuestas simuladas).
 */
@Component({
  selector: 'app-seven-quick-intent',
  templateUrl: './seven-quick-intent.component.html',
  styleUrls: ['./seven-quick-intent.component.scss'],
  standalone: true,
  imports: [IonIcon, TranslatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SevenQuickIntentComponent {
  @Input({ required: true }) intent!: QuickIntent;

  @Output() picked = new EventEmitter<TranslationKey>();

  onPick(): void {
    this.picked.emit(this.intent.promptKey);
  }
}
