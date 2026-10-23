import { ChangeDetectionStrategy, Component, HostBinding, Input } from '@angular/core';
import { IonSpinner } from '@ionic/angular';

/**
 * GlobalLoader
 *
 * Reusable premium load screen with SEVEN branding. Shows the white
 * logo (fade in + scale) over the solid SEVEN Ocean Blue background
 * with the brand tagline.
 *
 * Usage:
 *   <app-global-loader [visible]="true" />
 *   <app-global-loader [visible]="true" label="Cargando el tour…" [spinner]="true" />
 */
@Component({
  selector: 'app-global-loader',
  templateUrl: './global-loader.component.html',
  styleUrls: ['./global-loader.component.scss'],
  standalone: true,
  imports: [IonSpinner],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class GlobalLoaderComponent {
  @Input() visible = false;
  @Input() tagline = 'MÁS QUE UN DESTINO';
  @Input() logo = 'assets/img/brand/seven-logo-blanco.png';

  /** Status line shown under the tagline, e.g. "Cargando el tour…". */
  @Input() label?: string;

  /** Shows a spinner below the logo (data-loading states). */
  @Input() spinner = false;

  @HostBinding('class.is-visible') get isVisible(): boolean {
    return this.visible;
  }

  @HostBinding('attr.aria-hidden') get ariaHidden(): string {
    return this.visible ? 'false' : 'true';
  }
}
