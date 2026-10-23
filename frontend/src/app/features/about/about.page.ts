import { Component, ChangeDetectionStrategy, inject } from '@angular/core';
import {
  IonContent,
  IonIcon,
} from '@ionic/angular';
import { RouterLink } from '@angular/router';

import { APP_CONFIG } from '../../core/tokens';
import type { AppConfig } from '../../core/config/app-config.interface';
import { HapticsService } from '../../core/services/haptics.service';
import { TranslatePipe } from '../../shared/pipes/translate/translate.pipe';
import { PageHeaderComponent } from '../../shared/components/page-header/page-header.component';
import { DEFAULT_OPERATOR_PHONE } from '../../shared/constants/island';
import { whatsappLink } from '../../utils/formatters';

interface Link {
  icon: string;
  label: string;
  value: string;
  href: string;
}

interface Stat {
  value: string;
  label: string;
}

interface ValueCard {
  icon: string;
  title: string;
  description: string;
}

/**
 * Acerca de
 *
 * Nota: los datos de contacto son marcadores de posición y hay que
 * reemplazarlos por los reales de SEVEN antes de publicar.
 */
@Component({
  selector: 'app-about',
  templateUrl: './about.page.html',
  styleUrls: ['./about.page.scss'],
  standalone: true,
  imports: [
    IonContent,
    IonIcon,
    PageHeaderComponent,
    TranslatePipe,
    RouterLink,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AboutPage {
  private readonly haptics = inject(HapticsService);
  private readonly config = inject<AppConfig>(APP_CONFIG);

  readonly appName = this.config.appName;
  readonly appVersion = this.config.appVersion;

readonly contact: Link[] = [
    {
      icon: 'logo-whatsapp',
      label: 'WhatsApp',
      value: '+57 300 000 0000',
      href: 'https://wa.me/573000000000',
    },
    {
      icon: 'logo-instagram',
      label: 'Instagram',
      value: '@seven.isla',
      href: 'https://instagram.com/seven.isla',
    },
  ];

  /** SEVEN en cifras — trust bar (copie editorial, migrada de la Home). */
  readonly stats: Stat[] = [
    { value: '100%', label: 'Negocios verificados' },
    { value: '50+', label: 'Experiencias locales' },
    { value: '6', label: 'Categorías de la isla' },
    { value: '1.200+', label: 'Viajeros conectados' },
  ];

  /** Razones para elegir SEVEN (copie editorial, migrada de la Home). */
  readonly values: ValueCard[] = [
    {
      icon: 'shield-checkmark-outline',
      title: 'Verificado por SEVEN',
      description: 'Revisamos cada negocio para que tu experiencia sea segura.',
    },
    {
      icon: 'people-outline',
      title: '100% local',
      description: 'Conectamos directo con la comunidad raizal de la isla.',
    },
    {
      icon: 'chatbubbles-outline',
      title: 'Soporte sin fricción',
      description: 'Reservas fáciles y acompañamiento en todo tu viaje.',
    },
    {
      icon: 'leaf-outline',
      title: 'Turismo sostenible',
      description: 'Promovemos experiencias que cuidan nuestro Caribe.',
    },
  ];

  /** Enlace del llamado a negocios, con el contacto real del operador. */
  readonly businessHref = whatsappLink(
    DEFAULT_OPERATOR_PHONE,
    'Hola SEVEN, quiero publicar mi negocio en la isla',
  );

  onOpenLink(): void {
    void this.haptics.selection();
  }
}
