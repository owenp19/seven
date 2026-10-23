import { Pipe, PipeTransform, inject } from '@angular/core';

import { TranslationService } from '../../../core/services/translation.service';
import type { TranslationKey } from '../../../core/services/translation.service';

/**
 * Pipe `| t`
 *
 * Atajo de plantilla para `i18n.t('clave')`. Es impuro a propósito: así se
 * recalcula en cada ciclo de detección y las vistas que lo usan se actualizan
 * aunque el cambio de idioma lo dispare un botón en otra página.
 *
 * Uso: `{{ 'settings.title' | t }}` o `{{ 'settings.version' | t: { version } }}`.
 */
@Pipe({ name: 't', standalone: true, pure: false })
export class TranslatePipe implements PipeTransform {
  private readonly i18n = inject(TranslationService);

  transform(key: TranslationKey, params?: Record<string, string | number>): string {
    return this.i18n.t(key, params);
  }
}
