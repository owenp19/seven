import { Component, OnInit, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { IonContent, IonIcon, IonRefresher, IonRefresherContent, IonSkeletonText } from '@ionic/angular';

import { Subject, catchError, combineLatest, interval, of, startWith, switchMap, take } from 'rxjs';
import { toSignal } from '@angular/core/rxjs-interop';

import { Banner, Business, Category, Inn, Promotion, Restaurant, Tour, TouristNews } from '../../models';
import { CategoryService } from '../../services/category.service';
import { BusinessService } from '../../services/business.service';
import { TourService } from '../../services/tour.service';
import { RestaurantService } from '../../services/restaurant.service';
import { InnService } from '../../services/inn.service';
import { BannerService } from '../../services/banner.service';
import { PromotionService } from '../../services/promotion.service';
import { DataCacheService } from '../../core/services/data-cache.service';
import { FavoritesService } from '../../core/services/favorites.service';
import { HapticsService } from '../../core/services/haptics.service';
import { SectionHeaderComponent } from '../../shared/components/section-header/section-header.component';
import { TouristNewsCarouselComponent } from '../../shared/components/tourist-news-carousel/tourist-news-carousel.component';
import { DiscoveryCardComponent, DiscoveryCardItem } from '../../shared/components/discovery-card/discovery-card.component';
import { PromoCardComponent } from '../../shared/components/promo-card/promo-card.component';
import { TranslatePipe } from '../../shared/pipes/translate/translate.pipe';

type CategoryColor = 'blue' | 'aqua' | 'sand' | 'green';

interface CategoryCard {
  id: number;
  slug: string;
  icon: string;
  name: string;
  color: CategoryColor;
}

/**
 * HomePage
 *
 * Pantalla principal estilo app: saludo + ubicación, banner, categorías y
 * carruseles de descubrimiento que se leen de la API SEVEN. Las secciones de
 * marca (cifras, valores y llamado a negocios) viven en Acerca de. Cada
 * sección con datos es un skeleton propio y se oculta completa si llega
 * vacía (nada de estados ni datos inventados).
 */
@Component({
  selector: 'app-home',
  templateUrl: './home.page.html',
  styleUrls: ['./home.page.scss'],
  standalone: true,
  imports: [
    IonContent,
    IonIcon,
    IonRefresher,
    IonRefresherContent,
    IonSkeletonText,
    SectionHeaderComponent,
    TouristNewsCarouselComponent,
    DiscoveryCardComponent,
    PromoCardComponent,
    TranslatePipe,
  ],
})
export class HomePage implements OnInit {
  private readonly categories = inject(CategoryService);
  private readonly businesses = inject(BusinessService);
  private readonly tours = inject(TourService);
  private readonly restaurants = inject(RestaurantService);
  private readonly inns = inject(InnService);
  private readonly banners = inject(BannerService);
  private readonly promotions = inject(PromotionService);
  private readonly haptics = inject(HapticsService);
  private readonly dataCache = inject(DataCacheService);
  private readonly favorites = inject(FavoritesService);
  private readonly router = inject(Router);

  private readonly reload$ = new Subject<void>();

  /** True hasta la primera entrada a Home; evita pedir datos dos veces al crear la vista. */
  private firstEnter = true;

  private readonly savedKeys = signal<Set<string>>(new Set());

  /** Only these categories are shown on the home, in this order. */
  private readonly allowedSlugs = ['tours', 'restaurantes', 'posadas-nativas', 'panaderias'];

  readonly bannerSlides = toSignal<TouristNews[]>(this.reload$.pipe(
    startWith(null),
    switchMap(() => {
      const localBanners: Banner[] = ['assets/img/backgounds/sai-bg.jpg', 'assets/img/backgounds/sai-bg-2.jpeg', 'assets/img/backgounds/sai-bg-3.jpeg', 'assets/img/backgounds/sai-bg-4.jpeg', 'assets/img/backgounds/sai-bg-5.jpeg'].map(
        (img, idx) => ({
          id: -1000 - idx,
          title: idx === 0 ? 'San Andrés, Colombia' : `San Andrés · Isla ${idx + 1}`,
          subtitle: '',
          description: 'Descubre el encanto de San Andrés. Agua cristalina, cultura raizal, playas paradisíacas.',
          image: img,
          buttonText: '',
          buttonUrl: '',
          order: idx,
        }),
      );
      return this.banners.getAll().pipe(
        catchError(() => of(localBanners)),
        switchMap((items) => {
          const slides = (items.length > 0 ? items : localBanners).map((banner) => this.toNewsSlide(banner));
          if (slides.length === 0) {
            return of(slides);
          }
          return interval(4000).pipe(
            startWith(0),
            take(Number.MAX_SAFE_INTEGER),
            switchMap((i) => {
              const idx = i % slides.length;
              const list = slides.map((s, j) => ({ ...s, active: j === idx }));
              return of(list);
            }),
          );
        }),
      );
    }),
  ));

  readonly categoriesList = toSignal<Category[]>(this.reload$.pipe(
    startWith(null),
    switchMap(() => this.categories.getAll().pipe(catchError(() => of([] as Category[])))),
  ));

  readonly featured = toSignal<DiscoveryCardItem[]>(this.reload$.pipe(
    startWith(null),
    switchMap(() =>
      combineLatest([
        this.businesses.getFeatured().pipe(catchError(() => of([] as Business[]))),
        this.tours.getFeatured().pipe(catchError(() => of([] as Tour[]))),
        this.restaurants.getFeatured().pipe(catchError(() => of([] as Restaurant[]))),
        this.inns.getFeatured().pipe(catchError(() => of([] as Inn[]))),
      ]),
    ),
    switchMap(([businessItems, tourItems, restaurantItems, innItems]) =>
      of(this.mergeFeatured(businessItems, tourItems, restaurantItems, innItems)),
    ),
  ));

  readonly promotionList = toSignal<Promotion[]>(this.reload$.pipe(
    startWith(null),
    switchMap(() => this.promotions.getAll().pipe(catchError(() => of([] as Promotion[])))),
  ));

  /** Palette mapped to the theme tokens (.home__category--{color}). */
  private readonly colors: CategoryColor[] = ['blue', 'aqua', 'sand', 'green'];

  readonly ghostCategories = [1, 2, 3, 4];
  readonly ghostCards = [1, 2];

  ngOnInit(): void {
    this.favorites.favorites$.subscribe((items) => {
      const next = new Set<string>();
      for (const item of items) {
        next.add(`${item.type}:${item.id}`);
      }
      this.savedKeys.set(next);
    });
  }

  /**
   * Ionic lifecycle: se ejecuta en cada entrada a la pestaña Home.
   *
   * La primera vez lo maneja el arranque de cada señal; en las siguientes
   * entradas — incluida una view cacheada por Ionic que pudiera quedarse
   * con datos vacíos — se re-solicita y re-renderiza todo de inmediato,
   * sin depender de recargar el navegador.
   */
  ionViewWillEnter(): void {
    if (this.firstEnter) {
      this.firstEnter = false;
      return;
    }
    this.reload$.next();
  }

  /** Keeps only the allowed categories, preserving the home order. */
  private pickAllowed(items: Category[]): Category[] {
    const bySlug = new Map(items.map((category) => [category.slug, category]));
    return this.allowedSlugs.map((slug) => bySlug.get(slug)).filter((c): c is Category => !!c);
  }

  /** Banners del CMS -> slides del carousel. */
  private toNewsSlide(banner: Banner): TouristNews {
    return {
      id: banner.id,
      title: banner.title,
      description: banner.description || banner.subtitle || banner.title,
      image: banner.image,
      actionText: banner.buttonText || undefined,
      url: banner.buttonUrl || undefined,
      active: true,
    };
  }

  /** Combina destacados de las cuatro verticales en tarjetas de descubrimiento. */
  private mergeFeatured(businesses: Business[], tours: Tour[], restaurants: Restaurant[], inns: Inn[]): DiscoveryCardItem[] {
    const cards: DiscoveryCardItem[] = [];

    for (const item of tours) {
      cards.push({
        id: item.id,
        slug: item.slug,
        type: 'tour',
        image: item.cover,
        name: item.name,
        category: 'Tours',
        rating: item.rating,
        location: item.location,
        route: ['/tour', item.slug],
        price: item.price,
        priceLabel: 'Desde',
        providerName: item.providerName,
      });
    }
    for (const item of restaurants) {
      cards.push({
        id: item.id,
        slug: item.slug,
        type: 'restaurant',
        image: item.cover,
        name: item.name,
        category: 'Restaurantes',
        rating: item.rating,
        location: item.address,
        route: ['/restaurant', item.slug],
        price: 0,
        priceLabel: '',
        providerName: item.providerName,
      });
    }
    for (const item of inns) {
      cards.push({
        id: item.id,
        slug: item.slug,
        type: 'inn',
        image: item.coverImage,
        name: item.name,
        category: 'Posadas nativas',
        rating: item.rating,
        location: item.location,
        route: ['/inn', item.slug],
        price: item.price,
        priceLabel: 'Desde',
        providerName: item.providerName,
      });
    }
    for (const item of businesses) {
      cards.push({
        id: item.id,
        slug: String(item.id),
        type: 'business',
        image: item.coverImage ?? '',
        name: item.name,
        category: item.category?.name ?? 'Lugar local',
        rating: item.ratingAvg,
        location: item.address ?? '',
        route: ['/business', String(item.id)],
        price: 0,
        priceLabel: '',
        providerName: '',
      });
    }

    return cards;
  }

  private toCategory(category: Category, index: number): CategoryCard {
    return {
      id: category.id,
      slug: category.slug,
      icon: category.icon ?? 'sparkles-outline',
      name: category.name,
      color: this.colors[index % this.colors.length],
    };
  }

  /** Categorías permitidas, mapeadas a tarjetas con paleta por posición. */
  categoryCards(list: Category[]): CategoryCard[] {
    return this.pickAllowed(list).map((category, index) => this.toCategory(category, index));
  }

  onBannerAction(slide: TouristNews): void {
    void this.haptics.selection();
    if (slide.url) {
      if (slide.url.startsWith('/')) {
        void this.router.navigateByUrl(slide.url);
      } else {
        window.open(slide.url, '_blank', 'noopener');
      }
    }
  }

  onRefresh(event: Event): void {
    void this.haptics.selection();
    this.invalidateCaches();
    this.reload$.next();
    const target = event.target as HTMLIonRefresherElement;
    Promise.resolve().then(() => {
      setTimeout(() => target.complete(), 400);
    });
  }

  private invalidateCaches(): void {
    for (const key of [
      'categories',
      'businesses:featured',
      'tours:featured',
      'restaurants:featured',
      'inns:featured',
      'banners',
      'promotions',
    ]) {
      this.dataCache.invalidate(key);
    }
  }

  openCategory(category: CategoryCard): void {
    void this.haptics.selection();
    const routes: Record<string, string> = {
      tours: '/tours',
      restaurantes: '/restaurants',
      'cocina-raizal': '/restaurants',
      'posadas-nativas': '/inns',
    };
    const target = routes[category.slug] ?? '/explore';
    void this.router.navigate([target]);
  }

  openPromotions(): void {
    void this.haptics.selection();
    void this.router.navigate(['/promotions']);
  }

  openFeatured(item: DiscoveryCardItem): void {
    void this.haptics.selection();
    void this.router.navigate(item.route);
  }

  isSaved(item: DiscoveryCardItem): boolean {
    return this.savedKeys().has(`${item.type}:${item.id}`);
  }

  toggleSave(item: DiscoveryCardItem): void {
    void this.haptics.selection();
    void this.favorites.toggle(item.type, item.id, item.slug);
  }
}