import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';

import { IonIcon } from '@ionic/angular';

import { TranslatePipe } from '../../pipes/translate/translate.pipe';

/**
 * SevenAssistantHeader
 *
 * Cabecera propia del concierge: identidad (marca Seven AI + rol) y, cuando
 * hay conversación en curso, el botón de nueva conversación. Compacta en móvil
 * y con safe-area propia, porque sustituye a la cabecera compartida.
 */
@Component({
  selector: 'app-seven-assistant-header',
  templateUrl: './seven-assistant-header.component.html',
  styleUrls: ['./seven-assistant-header.component.scss'],
  standalone: true,
  imports: [IonIcon, TranslatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SevenAssistantHeaderComponent {
  /** Muestra el botón de reinicio solo si ya hay algo que reiniciar. */
  @Input() canReset = false;

  @Output() restart = new EventEmitter<void>();
}
