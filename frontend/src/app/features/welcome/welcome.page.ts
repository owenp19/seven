import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { Router } from '@angular/router';

import {
  IonButton,
  IonContent,
  IonIcon,
} from '@ionic/angular';

import { addIcons } from 'ionicons';
import {
  arrowForwardOutline,
} from 'ionicons/icons';

import { LanguageSelectorComponent } from '../../shared/components/language-selector/language-selector.component';
import { TranslatePipe } from '../../shared/pipes/translate/translate.pipe';
import { LoaderGateService } from '../../core/services/loader-gate.service';
import { HapticsService } from '../../core/services/haptics.service';

/**
 * WelcomePage
 *
 * Primera pantalla (sin login). El idioma se elige aquí o después en
 * Configuración: ambos mounts usan el mismo `LanguageSelectorComponent`, y
 * la preferencia queda persistida por `TranslationService`.
 */
@Component({
  selector: 'app-welcome',
  templateUrl: './welcome.page.html',
  styleUrls: ['./welcome.page.scss'],
  standalone: true,
  imports: [
    CommonModule,
    IonContent,
    IonButton,
    IonIcon,
    LanguageSelectorComponent,
    TranslatePipe,
  ],
})
export class WelcomePage {

  private readonly router = inject(Router);
  private readonly loaderGate = inject(LoaderGateService);
  private readonly haptics = inject(HapticsService);

  leaving = false;

  /**
   * Duración de la salida: 400ms se va el contenido (ver `welcome-out`) y
   * ~120ms extras se quedan solo la foto de fondo antes de entrar a Home.
   */
  private readonly exitDuration = 520;

  constructor() {
    addIcons({
      arrowForwardOutline,
    });
  }

  /**
   * Continúa hacia la aplicación.
   *
   * El loader global se salta a propósito (`skipNext`): mientras se carga
   * el chunk de Home esta pantalla sigue pintando su foto de fondo a
   * pantalla completa, así que el usuario ve la imagen → y no un rectángulo
   * azul marino con el logo por encima.
   */
  async continueToApp(): Promise<void> {

    if (this.leaving) {
      return;
    }

    this.leaving = true;

    void this.haptics.selection();

    localStorage.setItem(
      'seven_welcome_completed',
      'true',
    );

    await new Promise<void>(
      (resolve) => setTimeout(resolve, this.exitDuration),
    );

    this.loaderGate.skipNext();

    await this.router.navigateByUrl(
      '/home',
      {
        replaceUrl: true,
      },
    );
  }

}
