import { Component, Input, OnChanges, OnInit, SimpleChanges, inject } from '@angular/core';
import { AsyncPipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { IonContent, IonIcon } from '@ionic/angular';

import { Observable, catchError, combineLatest, map, of, shareReplay, switchMap } from 'rxjs';

import { News } from '../../models';
import type { NewsKind } from '../../models/news.model';
import { NewsService } from '../../services/news.service';
import { PageHeaderComponent } from '../../shared/components/page-header/page-header.component';
import { SevenContextLoaderComponent } from '../../shared/components/seven-context-loader/seven-context-loader.component';
import { SafeImgDirective } from '../../shared/directives/safe-img/safe-img.directive';
import { NewsDatePipe } from '../../shared/pipes/news-date/news-date.pipe';
import { HapticsService } from '../../core/services/haptics.service';
import { TranslationService } from '../../core/services/translation.service';
import { RouteParam } from '../../shared/utils/route-param';
import { whatsappLink } from '../../utils/formatters';
import { TranslatePipe } from '../../shared/pipes/translate/translate.pipe';

/** Cuántas historias relacionadas se muestran al final. */
const MAX_RELATED = 3;

/**
 * NewsDetailPage — una historia de la revista.
 *
 * Se lee como un artículo y no como una ficha: sin tarjeta envolvente, con
 * medida de columna cómoda (≈720px en escritorio), el cuerpo en crudo y una
 * firma de operador solo cuando la nota la trae de verdad. Las relacionadas
 * salen del mismo feed (`getAll`) en vez de un endpoint extra.
 */
@Component({
  selector: 'app-news-detail',
  templateUrl: './news-detail.page.html',
  styleUrls: ['./news-detail.page.scss'],
  standalone: true,
  imports: [
    AsyncPipe,
    IonContent,
    IonIcon,
    PageHeaderComponent,
    SevenContextLoaderComponent,
    RouterLink,
    SafeImgDirective,
    NewsDatePipe,
    TranslatePipe,
  ],
})
export class NewsDetailPage implements OnInit, OnChanges {
  private readonly news = inject(NewsService);
  private readonly haptics = inject(HapticsService);
  private readonly translation = inject(TranslationService);

  /** Idioma del contenido; `signal` para re-fechar si cambia el idioma. */
  readonly language = this.translation.language;

  @Input() slug!: string;

  loading = true;

  news$!: Observable<News | null>;
  related$!: Observable<News[]>;

  /**
   * El router reutiliza la instancia al cambiar solo el slug, así que la carga
   * se reacciona a `slug` en vez de leerse una vez en `ngOnInit`.
   */
  private readonly slugParam = new RouteParam<string>();

  ngOnChanges(changes: SimpleChanges): void {
    this.slugParam.bind(changes, 'slug', this.slug);
  }

  ngOnInit(): void {
    const source = this.slugParam.stream$.pipe(
      switchMap((slug) => this.news.getBySlug(slug)),
      catchError(() => of(null)),
    );

    const shared = source.pipe(shareReplay({ bufferSize: 1, refCount: true }));
    // Suscripción viva: pinta el resultado aunque la plantilla espere a que
    // `loading` deje de ser cierto para evaluar el `@if`.
    shared.subscribe({ next: () => (this.loading = false), error: () => (this.loading = false) });

    this.news$ = shared;

    this.related$ = combineLatest([
      shared,
      this.news.getAll().pipe(catchError(() => of([] as News[]))),
    ]).pipe(map(([current, all]) => this.buildRelated(current?.slug ?? '', all)));
  }

  /**
   * Traducción de la etiqueta según el `kind` editorial de la nota.
   *
   * El tipo de retorno es la unión de las cuatro claves y no `string`: el pipe
   * `| t` exige una `TranslationKey`.
   */
  kindLabel(kind: NewsKind): 'news.kindNews' | 'news.kindEvent' | 'news.kindTip' | 'news.kindPromotion' {
    switch (kind) {
      case 'event':
        return 'news.kindEvent';
      case 'tip':
        return 'news.kindTip';
      case 'promotion':
        return 'news.kindPromotion';
      default:
        return 'news.kindNews';
    }
  }

  kindIcon(kind: NewsKind): string {
    switch (kind) {
      case 'event':
        return 'calendar-outline';
      case 'tip':
        return 'bulb-outline';
      case 'promotion':
        return 'pricetag-outline';
      default:
        return 'newspaper-outline';
    }
  }

  /** WhatsApp directo con el operador de la nota, solo si expone teléfono. */
  contactProvider(article: News): void {
    if (!article.providerPhone) {
      return;
    }
    void this.haptics.selection();
    window.open(
      whatsappLink(article.providerPhone, `Hola, leí "${article.title}" en SEVEN y quiero más información.`),
      '_blank',
      'noopener',
    );
  }

  private buildRelated(currentSlug: string, all: News[]): News[] {
    return all.filter((item) => item.slug !== currentSlug).slice(0, MAX_RELATED);
  }
}
