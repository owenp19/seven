import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { IonBackButton, IonButtons, IonHeader, IonToolbar } from '@ionic/angular';

import { TranslatePipe } from '../../pipes/translate/translate.pipe';

/**
 * PageHeader
 *
 * Cabecera compartida de las páginas interiores (Configuración, Privacidad,
 * Acerca de, Noticias...). Sustituye al bloque repetido de `ion-header` +
 * `ion-back-button` + `ion-title` que cada página escribía por su cuenta.
 *
 * Dos decisiones que no son obvias:
 *
 * 1. El botón de regreso no es un `ion-back-button` pelado sino un disco. La
 *    flecha suelta de Ionic es correcta pero invisible en un teléfono: sin
 *    fondo ni borde se pierde contra el contenido que pasa por debajo. El
 *    disco le da superficie, tamaño táctil y un lugar fijo donde caer la vista
 *    al volver de una pantalla larga.
 *
 * 2. El toolbar lleva filete y sombra propia en vez de depender de
 *    `[translucent]`. En modo MD el comportamiento translúcido no hace nada, así
 *    que el header quedaría pegado al contenido sin separación visible.
 */
@Component({
  selector: 'app-page-header',
  templateUrl: './page-header.component.html',
  styleUrls: ['./page-header.component.scss'],
  standalone: true,
  imports: [IonHeader, IonToolbar, IonButtons, IonBackButton, TranslatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PageHeaderComponent {
  /** Texto del encabezado de la página. Es también el titular semántico (`h1`). */
  @Input() title = '';

  /** Destino de respaldo cuando no hay historial al que volver. */
  @Input() defaultHref = '/home';
}