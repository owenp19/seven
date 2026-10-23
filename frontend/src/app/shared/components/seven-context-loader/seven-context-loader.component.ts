import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { IonIcon } from '@ionic/angular';

import { TranslatePipe } from '../../pipes/translate/translate.pipe';
import type { TranslationKey } from '../../../core/services/translation.service';

/**
 * Tipo de contenido que se está cargando. Determina el texto contextual
 * (`loading.tour`, `loading.inn`, …) sin que cada pantalla duplique cadenas.
 */
export type SevenContentType =
  | 'tour'
  | 'restaurant'
  | 'inn'
  | 'experience'
  | 'promotion'
  | 'business'
  | 'news'
  | 'category'
  | 'generic';

/** Clave i18n contextual por tipo de contenido. */
const LABEL_KEYS: Record<SevenContentType, TranslationKey> = {
  tour: 'loading.tour',
  restaurant: 'loading.restaurant',
  inn: 'loading.inn',
  experience: 'loading.experience',
  promotion: 'loading.promotion',
  business: 'loading.business',
  news: 'loading.news',
  category: 'loading.category',
  generic: 'common.loading',
};

/**
 * SevenContextLoader
 *
 * Estado de carga propio de SEVEN: un skeleton suave que imita el layout final
 * (hero + título + metadatos + contenido + CTA en `detail`; cabecera + tarjetas
 * en `list`) con un pequeño símbolo de marca y un texto contextual que sale del
 * sistema i18n existente — así respeta el idioma elegido en Welcome/Onboarding
 * sin crear un segundo diccionario.
 *
 *   <app-seven-context-loader type="inn" hero="short" />
 *   <app-seven-context-loader variant="list" type="tour" />
 */
@Component({
  selector: 'app-seven-context-loader',
  templateUrl: './seven-context-loader.component.html',
  styleUrls: ['./seven-context-loader.component.scss'],
  standalone: true,
  imports: [IonIcon, TranslatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SevenContextLoaderComponent {
  /** Entidad que se está cargando; decide el texto contextual traducido. */
  @Input() type: SevenContentType = 'generic';

  /** `detail` imita una página de detalle; `list` imita un catálogo. */
  @Input() variant: 'detail' | 'list' = 'detail';

  /** Alto del hero: `tall` (tour/restaurante), `short` (posada/negocio) o `none` (artículo). */
  @Input() hero: 'tall' | 'short' | 'none' = 'tall';

  /** Reserva el alto de la barra fija inferior (Detail con CTA sticky). */
  @Input() stickyBar = true;

  /** Número de tarjetas fantasma en la variante `list`. */
  @Input() count = 4;

  get labelKey(): TranslationKey {
    return LABEL_KEYS[this.type] ?? 'common.loading';
  }

  get ghosts(): number[] {
    return Array.from({ length: this.count }, (_, index) => index);
  }
}
