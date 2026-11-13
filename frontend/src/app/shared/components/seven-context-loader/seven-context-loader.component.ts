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

interface LoaderProfile {
  readonly icon: string;
  readonly label: TranslationKey;
  readonly title: TranslationKey;
  readonly hint: TranslationKey;
}

/** Perfil visual y copy de cada vertical. */
const PROFILES: Record<SevenContentType, LoaderProfile> = {
  tour: {
    icon: 'compass-outline',
    label: 'loading.tour',
    title: 'loading.title.tour',
    hint: 'loading.hint.tour',
  },
  restaurant: {
    icon: 'restaurant-outline',
    label: 'loading.restaurant',
    title: 'loading.title.restaurant',
    hint: 'loading.hint.restaurant',
  },
  inn: {
    icon: 'bed-outline',
    label: 'loading.inn',
    title: 'loading.title.inn',
    hint: 'loading.hint.inn',
  },
  experience: {
    icon: 'sparkles-outline',
    label: 'loading.experience',
    title: 'loading.title.experience',
    hint: 'loading.hint.experience',
  },
  promotion: {
    icon: 'pricetag-outline',
    label: 'loading.promotion',
    title: 'loading.title.promotion',
    hint: 'loading.hint.promotion',
  },
  business: {
    icon: 'storefront-outline',
    label: 'loading.business',
    title: 'loading.title.business',
    hint: 'loading.hint.business',
  },
  news: {
    icon: 'newspaper-outline',
    label: 'loading.news',
    title: 'loading.title.news',
    hint: 'loading.hint.news',
  },
  category: {
    icon: 'grid-outline',
    label: 'loading.category',
    title: 'loading.title.category',
    hint: 'loading.hint.category',
  },
  generic: {
    icon: 'sparkles-outline',
    label: 'common.loading',
    title: 'loading.title.generic',
    hint: 'loading.hint.generic',
  },
};

/**
 * SevenContextLoader
 *
 * Precarga contextual de SEVEN. Cada vertical tiene su propio esqueleto:
 * tour (ficha + disponibilidad), restaurante (carta), posada (habitaciones),
 * negocio (servicios y horarios), noticia (artículo), promoción (oferta) y
 * catálogo (tarjetas). El esqueleto imita el layout final para que la entrada
 * del contenido no produzca saltos, y el copy sale del i18n existente.
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
  /** Entidad que se está cargando; decide el esqueleto y el copy contextual. */
  @Input() type: SevenContentType = 'generic';

  /** `detail` imita una página de detalle; `list` imita un catálogo. */
  @Input() variant: 'detail' | 'list' = 'detail';

  /** Alto del hero: `tall` (tour/restaurante), `short` (posada/negocio) o `none` (artículo). */
  @Input() hero: 'tall' | 'short' | 'none' = 'tall';

  /** Reserva el alto de la barra fija inferior (Detail con CTA sticky). */
  @Input() stickyBar = true;

  /** Número de tarjetas fantasma en la variante `list`. */
  @Input() count = 4;

  get profile(): LoaderProfile {
    return PROFILES[this.type] ?? PROFILES.generic;
  }

  get icon(): string {
    return this.profile.icon;
  }

  get labelKey(): TranslationKey {
    return this.profile.label;
  }

  get titleKey(): TranslationKey {
    return this.profile.title;
  }

  get hintKey(): TranslationKey {
    return this.profile.hint;
  }

  get ghosts(): number[] {
    return Array.from({ length: this.count }, (_, index) => index);
  }
}
