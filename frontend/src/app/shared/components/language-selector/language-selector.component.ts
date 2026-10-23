import { ChangeDetectionStrategy, Component, inject } from '@angular/core';

import { HapticsService } from '../../../core/services/haptics.service';
import { TranslationService } from '../../../core/services/translation.service';
import type { Language } from '../../../core/services/translation.service';
import { TranslatePipe } from '../../pipes/translate/translate.pipe';

/**
 * Selector de idioma ES/EN/PT reutilizable.
 *
 * Welcome y Configuración montan el mismo control: la preferencia se guarda
 * en `localStorage` a través de `TranslationService`, así que cambiarlo en
 * cualquiera de los dos sitios deja la app entera en ese idioma.
 */
@Component({
  selector: 'app-language-selector',
  templateUrl: './language-selector.component.html',
  styleUrls: ['./language-selector.component.scss'],
  standalone: true,
  imports: [TranslatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LanguageSelectorComponent {
  private readonly i18n = inject(TranslationService);
  private readonly haptics = inject(HapticsService);

  readonly languages = this.i18n.languages;
  readonly current = this.i18n.language;

  select(code: Language): void {
    if (this.i18n.isActive(code)) {
      return;
    }

    this.i18n.setLanguage(code);
    void this.haptics.selection();
  }
}
