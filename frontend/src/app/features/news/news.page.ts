import { Component, OnInit, inject, signal } from '@angular/core';
import { AsyncPipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import {
  IonContent,
  IonIcon,
  IonRefresher,
  IonRefresherContent,
  IonSkeletonText,
} from '@ionic/angular';

import {
  Observable,
  Subject,
  catchError,
  combineLatest,
  map,
  of,
  startWith,
  switchMap,
} from 'rxjs';
import { toObservable } from '@angular/core/rxjs-interop';

import { News } from '../../models';
import type { NewsKind } from '../../models/news.model';
import { NewsService } from '../../services/news.service';
import { PageHeaderComponent } from '../../shared/components/page-header/page-header.component';
import { SafeImgDirective } from '../../shared/directives/safe-img/safe-img.directive';
import { NewsDatePipe } from '../../shared/pipes/news-date/news-date.pipe';
import { HapticsService } from '../../core/services/haptics.service';
import { DataCacheService } from '../../core/services/data-cache.service';
import { TranslationService } from '../../core/services/translation.service';
import { TranslatePipe } from '../../shared/pipes/translate/translate.pipe';

/** Categoría seleccionable en la fila de chips. */
type NewsFilter = NewsKind | 'all';

/**
 * NewsFeed
 *
 * Vista ya resuelta del feed: el hero, los destacados secundarios, las últimas
 * historias y las categorías realmente presentes. La plantilla no decide la
 * jerarquía, solo la pinta; así el orden editorial (destacado primero, resto
 * por fecha) se calcula en un único sitio.
 */
interface NewsFeed {
  featured: News | null;
  highlights: News[];
  latest: News[];
  categories: NewsKind[];
}

/** Orden fijo de las categorías para que los chips no bailen entre cargas. */
const CATEGORY_ORDER: readonly NewsKind[] = ['news', 'event', 'tip', 'promotion'];

/** Filas fantasma del estado de carga (hero + historias). */
const GHOST_ROWS = [1, 2, 3, 4];

/**
 * NewsPage — portada de la revista de la isla.
 *
 * No es un catálogo: es una portada editorial. El destacado manda, los
 * destacados secundarios van en una lista compacta y las últimas historias
 * se leen como un sumario de periódico. Cada pieza enlaza a su detalle
 * (`/noticias/:slug`); no hay botón "ver más" repetido porque la pieza entera
 * es el enlace.
 */
@Component({
  selector: 'app-news',
  templateUrl: './news.page.html',
  styleUrls: ['./news.page.scss'],
  standalone: true,
  imports: [
    AsyncPipe,
    IonContent,
    IonIcon,
    IonRefresher,
    IonRefresherContent,
    IonSkeletonText,
    NewsDatePipe,
    PageHeaderComponent,
    RouterLink,
    SafeImgDirective,
    TranslatePipe,
  ],
})
export class NewsPage implements OnInit {
  private readonly news = inject(NewsService);
  private readonly haptics = inject(HapticsService);
  private readonly dataCache = inject(DataCacheService);
  private readonly translation = inject(TranslationService);

  /** Idioma actual para fechar las historias; `signal` para reaccionar al cambio. */
  readonly language = this.translation.language;

  readonly ghosts = GHOST_ROWS;

  /** Filtro activo de categoría; `all` no filtra. */
  readonly filter = signal<NewsFilter>('all');

  private readonly reload$ = new Subject<void>();
  private readonly filter$ = toObservable(this.filter);

  /**
   * El feed se combina con el filtro activo. Mientras no haya llegado la
   * primera respuesta, la plantilla ve `null` y pinta los esqueletos: sin esa
   * emisión inicial la lista nunca se vería mientras la petición está en vuelo.
   */
  vm$!: Observable<NewsFeed>;

  ngOnInit(): void {
    this.vm$ = combineLatest([
      this.reload$.pipe(
        startWith(undefined),
        switchMap(() =>
          this.news.getAll().pipe(
            // Un fallo de red deja el feed vacío con su mensaje, no la pantalla
            // en blanco: el usuario ve el estado vacío y puede tirar para recargar.
            catchError(() => of<News[]>([])),
          ),
        ),
      ),
      toObservable(this.filter),
    ]).pipe(map(([items, filter]) => this.buildFeed(items, filter)));
  }

  onRefresh(event: Event): void {
    void this.haptics.selection();
    this.dataCache.invalidate('news');
    this.reload$.next();
    const target = event.target as HTMLIonRefresherElement;
    Promise.resolve().then(() => setTimeout(() => target.complete(), 400));
  }

  setFilter(filter: NewsFilter): void {
    this.haptics.selection();
    this.filter.set(filter);
  }

  isActive(filter: NewsFilter): boolean {
    return this.filter() === filter;
  }

  /**
   * Traducción de la etiqueta según el `kind` editorial de la nota.
   *
   * El tipo de retorno es la unión de las cuatro claves y no `string`: el pipe
   * `| t` exige una `TranslationKey`, y un `string` genérico no se puede
   * comprobar contra el diccionario en tiempo de compilación.
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

  /** Icono de la etiqueta: cada clase editorial tiene su propio símbolo. */
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

  /** El destacado es la primera pieza marcada; si no hay, la más reciente. */
  private buildFeed(items: News[], filter: NewsFilter): NewsFeed {
    const scoped = filter === 'all' ? items : items.filter((item) => item.kind === filter);
    const featured = scoped.find((item) => item.isFeatured) ?? scoped[0] ?? null;
    const rest = scoped.filter((item) => item !== featured);

    return {
      featured,
      highlights: rest.filter((item) => item.isFeatured),
      latest: rest.filter((item) => !item.isFeatured),
      categories: this.categories(items),
    };
  }

  /** Solo se ofrecen las categorías que existen de verdad en el feed. */
  private categories(items: News[]): NewsKind[] {
    const present = new Set(items.map((item) => item.kind));
    return CATEGORY_ORDER.filter((kind) => present.has(kind));
  }
}
