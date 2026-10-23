import { ChangeDetectionStrategy, Component } from '@angular/core';

import { IonIcon } from '@ionic/angular';

import { TranslatePipe } from '../../pipes/translate/translate.pipe';

/**
 * SevenThinking
 *
 * Estado de carga natural del asistente: en lugar de tres puntos genéricos,
 * un mensaje ("Seven está buscando opciones…") con esqueletos de tarjeta que
 * anticipan la forma del resultado. Accesible vía `role="status"`.
 */
@Component({
  selector: 'app-seven-thinking',
  templateUrl: './seven-thinking.component.html',
  styleUrls: ['./seven-thinking.component.scss'],
  standalone: true,
  imports: [IonIcon, TranslatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SevenThinkingComponent {}
