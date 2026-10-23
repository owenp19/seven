import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { IonContent, IonIcon } from '@ionic/angular';

import { APP_CONFIG } from '../../core/tokens';
import type { AppConfig } from '../../core/config/app-config.interface';
import { HapticsService } from '../../core/services/haptics.service';
import { TranslationService, type TranslationKey } from '../../core/services/translation.service';
import { TranslatePipe } from '../../shared/pipes/translate/translate.pipe';

interface HubLink {
  icon: string;
  labelKey: TranslationKey;
  route: string;
}

interface InfoBlock {
  icon: string;
  titleKey: TranslationKey;
  textKey: TranslationKey;
}

/**
 * Contact hub
 *
 * Perfil de SEVEN y centro del viajero, no un formulario corporativo. Reúne en
 * un solo lugar al asistente (el canal protagonista, con respuestas reales del
 * catálogo), el contacto directo por correo (único canal real disponible hoy) y
 * los accesos que antes vivían en el menú lateral: Noticias, Configuración,
 * Acerca de y Privacidad. No hay formulario porque el backend todavía no expone
 * un endpoint de mensajes: no se finge un envío.
 */
@Component({
  selector: 'app-contact',
  templateUrl: './contact.page.html',
  styleUrls: ['./contact.page.scss'],
  standalone: true,
  imports: [IonContent, IonIcon, RouterLink, TranslatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ContactPage {
  private readonly haptics = inject(HapticsService);
  private readonly i18n = inject(TranslationService);
  private readonly router = inject(Router);
  private readonly config = inject<AppConfig>(APP_CONFIG);

  /** Único canal real disponible hoy; sale de la configuración existente. */
  readonly email = this.config.contact.privacyEmail;
  readonly emailHref = `mailto:${this.email}`;

  readonly examples: TranslationKey[] = [
    'contact.example1',
    'contact.example2',
    'contact.example3',
    'contact.example4',
  ];

  /** Accesos que absorbieron el menú lateral eliminado. */
  readonly hubLinks: HubLink[] = [
    { icon: 'newspaper-outline', labelKey: 'menu.news', route: '/noticias' },
    { icon: 'settings-outline', labelKey: 'menu.settings', route: '/configuracion' },
    { icon: 'information-circle-outline', labelKey: 'menu.about', route: '/acerca-de' },
    { icon: 'shield-checkmark-outline', labelKey: 'menu.privacy', route: '/privacidad' },
    { icon: 'calendar-outline', labelKey: 'contact.helpBookings', route: '/bookings' },
    { icon: 'sparkles-outline', labelKey: 'contact.helpAssistant', route: '/assistant' },
  ];

  readonly infoBlocks: InfoBlock[] = [
    { icon: 'compass-outline', titleKey: 'contact.info1Title', textKey: 'contact.info1Text' },
    { icon: 'chatbubble-ellipses-outline', titleKey: 'contact.info2Title', textKey: 'contact.info2Text' },
    { icon: 'lock-closed-outline', titleKey: 'contact.info3Title', textKey: 'contact.info3Text' },
  ];

  onTap(): void {
    void this.haptics.selection();
  }

  openAssistant(): void {
    void this.haptics.selection();
    void this.router.navigate(['/assistant']);
  }

  /** Abre el asistente enviando la pregunta del chip como mensaje inicial. */
  openExample(key: TranslationKey): void {
    void this.haptics.selection();
    void this.router.navigate(['/assistant'], { queryParams: { q: this.i18n.t(key) } });
  }
}
