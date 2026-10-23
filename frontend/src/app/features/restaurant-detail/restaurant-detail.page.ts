import {
  Component,
  Input,
  OnChanges,
  OnInit,
  SimpleChanges,
  computed,
  inject,
  signal,
} from '@angular/core';
import { AsyncPipe, Location } from '@angular/common';
import { Router } from '@angular/router';
import { IonContent, IonIcon } from '@ionic/angular';

import { Observable, catchError, of, shareReplay, switchMap } from 'rxjs';

import { Restaurant, RestaurantProduct } from '../../models';
import { RestaurantService } from '../../services/restaurant.service';
import { HapticsService } from '../../core/services/haptics.service';
import { NativeService } from '../../core/services/native.service';
import { SafeImgDirective } from '../../shared/directives/safe-img/safe-img.directive';
import { MediaCarouselComponent } from '../../shared/components/media-carousel/media-carousel.component';
import { SevenContextLoaderComponent } from '../../shared/components/seven-context-loader/seven-context-loader.component';
import { CommentsSectionComponent } from '../../shared/components/comments-section/comments-section.component';
import { CurrencyPipe } from '../../shared/pipes/currency/currency.pipe';
import { TranslatePipe } from '../../shared/pipes/translate/translate.pipe';
import { mediaSources } from '../../shared/utils/media';
import { RouteParam } from '../../shared/utils/route-param';
import { mapsUrl } from '../../shared/utils/geo';
import { whatsappLink } from '../../utils/formatters';

/** Orden aplicable a la carta. */
type MenuSort = 'default' | 'price-asc' | 'price-desc';

/** Secuencia del botón de orden, para no escribir el array en cada clic. */
const SORT_ORDER: MenuSort[] = ['default', 'price-asc', 'price-desc'];

/**
 * RestaurantDetailPage
 *
 * Perfil de un restaurante con el mismo lenguaje visual "premium" que el
 * detalle de tour: hero inmersivo, datos rápidos y secciones limpias. La
 * pieza propia de esta vertical es el **explorador de carta**: una
 * herramienta funcional (buscar, filtrar por categoría y ordenar) que
 * trabaja sobre los platos reales que expone la API, sin inventar datos.
 */
@Component({
  selector: 'app-restaurant-detail',
  templateUrl: './restaurant-detail.page.html',
  styleUrls: ['./restaurant-detail.page.scss'],
  standalone: true,
  imports: [
    AsyncPipe,
    IonContent,
    IonIcon,
    SafeImgDirective,
    MediaCarouselComponent,
    SevenContextLoaderComponent,
    CommentsSectionComponent,
    CurrencyPipe,
    TranslatePipe,
  ],
})
export class RestaurantDetailPage implements OnInit, OnChanges {
  private readonly restaurants = inject(RestaurantService);
  private readonly haptics = inject(HapticsService);
  private readonly native = inject(NativeService);
  private readonly router = inject(Router);
  private readonly location = inject(Location);

  @Input() slug!: string;

  loading = true;
  descriptionExpanded = false;

  restaurant$!: Observable<Restaurant | null>;

  /**
   * URLs del carrusel del hero. Se memoizan en una señal porque la portada
   * se reconstruye si el arreglo cambia de identidad: un método que devolviera
   * un arreglo nuevo en cada detección de cambios haría que el Swiper se
   * destruyera y recreara con cada clic del explorador de carta.
   */
  readonly galleryImages = signal<string[]>([]);

  // ─── Explorador de carta: estado de la herramienta ────────────────────
  readonly menuProducts = signal<RestaurantProduct[]>([]);
  readonly menuQuery = signal('');
  readonly activeCategory = signal('all');
  readonly sortMode = signal<MenuSort>('default');
  readonly selectedDish = signal<RestaurantProduct | null>(null);

  /** Categorías reales presentes en la carta, sin repetir y en orden. */
  readonly categories = computed(() => {
    const seen = new Set<string>();
    for (const product of this.menuProducts()) {
      const category = product.category?.trim();
      if (category) {
        seen.add(category);
      }
    }
    return [...seen].sort((a, b) => a.localeCompare(b, 'es'));
  });

  /** Platos visibles según búsqueda, categoría y orden elegidos. */
  readonly filteredDishes = computed(() => {
    const query = this.menuQuery().trim().toLowerCase();
    const category = this.activeCategory();

    const list = this.menuProducts().filter((product) => {
      const matchesCategory = category === 'all' || product.category === category;
      const matchesQuery =
        !query ||
        product.name.toLowerCase().includes(query) ||
        product.description.toLowerCase().includes(query);
      return matchesCategory && matchesQuery;
    });

    const mode = this.sortMode();
    if (mode === 'price-asc') {
      return [...list].sort((a, b) => a.price - b.price);
    }
    if (mode === 'price-desc') {
      return [...list].sort((a, b) => b.price - a.price);
    }
    return list;
  });

  readonly sortLabel = computed(() => {
    switch (this.sortMode()) {
      case 'price-asc':
        return 'Precio ↑';
      case 'price-desc':
        return 'Precio ↓';
      default:
        return 'Recomendado';
    }
  });

  readonly sortIcon = computed(() => {
    switch (this.sortMode()) {
      case 'price-asc':
        return 'arrow-up-outline';
      case 'price-desc':
        return 'arrow-down-outline';
      default:
        return 'swap-vertical-outline';
    }
  });

  /**
   * El router reutiliza la instancia cuando cambia solo el slug, así que la
   * carga se reacciona a `slug` en vez de leerse una vez en `ngOnInit`.
   */
  private readonly slugParam = new RouteParam<string>();

  ngOnChanges(changes: SimpleChanges): void {
    this.slugParam.bind(changes, 'slug', this.slug);
  }

  ngOnInit(): void {
    const source = this.slugParam.stream$.pipe(
      switchMap((slug) => this.restaurants.getBySlug(slug)),
      catchError(() => of(null)),
    );
    const shared = source.pipe(shareReplay({ bufferSize: 1, refCount: true }));

    // Suscripción inmediata: apaga el loader y alimenta el explorador de la
    // carta. La instancia se reutiliza entre slugs, así que aquí también se
    // reinician búsqueda, filtro, orden y ficha abierta.
    shared.subscribe((restaurant) => {
      this.loading = false;
      this.galleryImages.set(restaurant ? this.gallery(restaurant) : []);
      this.menuProducts.set(restaurant?.products ?? []);
      this.menuQuery.set('');
      this.activeCategory.set('all');
      this.sortMode.set('default');
      this.selectedDish.set(null);
      this.descriptionExpanded = false;
    });

    this.restaurant$ = shared;
  }

  /**
   * El backend solo expone `logo` y `cover_image` para restaurantes, así que
   * el carrusel se arma con la portada más las fotos de los productos del
   * menú, que sí traen imagen propia.
   */
  gallery(restaurant: Restaurant): string[] {
    return mediaSources(restaurant.cover, ...restaurant.products.map((product) => product.image));
  }

  goBack(): void {
    this.location.back();
  }

  share(restaurant: Restaurant): void {
    void this.haptics.selection();
    void this.native.share({
      title: `${restaurant.name} · San Andrés`,
      text: restaurant.description,
      url: window.location.href,
    });
  }

  toggleDescription(): void {
    this.descriptionExpanded = !this.descriptionExpanded;
  }

  // ─── Herramienta · explorador de carta ────────────────────────────────
  onSearch(event: Event): void {
    this.menuQuery.set((event.target as HTMLInputElement).value);
  }

  clearSearch(): void {
    this.menuQuery.set('');
  }

  selectCategory(category: string): void {
    void this.haptics.selection();
    this.activeCategory.set(category);
  }

  cycleSort(): void {
    const index = SORT_ORDER.indexOf(this.sortMode());
    this.sortMode.set(SORT_ORDER[(index + 1) % SORT_ORDER.length]);
  }

  clearFilters(): void {
    this.menuQuery.set('');
    this.activeCategory.set('all');
  }

  openDish(dish: RestaurantProduct): void {
    void this.haptics.selection();
    this.selectedDish.set(dish);
  }

  closeDish(): void {
    this.selectedDish.set(null);
  }

  orderDish(restaurant: Restaurant, dish: RestaurantProduct): void {
    void this.haptics.selection();
    const message = `Hola ${restaurant.name}, quiero pedir "${dish.name}" (${dish.price.toLocaleString('es-CO')} COP). ¿Está disponible?`;
    window.open(whatsappLink(restaurant.phone, message), '_blank', 'noopener');
  }

  askWhatsapp(restaurant: Restaurant): void {
    void this.haptics.selection();
    window.open(whatsappLink(restaurant.phone, `Hola ${restaurant.name}, quiero más información`), '_blank', 'noopener');
  }

  openMap(restaurant: Restaurant): void {
    void this.haptics.selection();
    window.open(mapsUrl(restaurant.address || restaurant.name), '_blank', 'noopener');
  }

  call(restaurant: Restaurant): void {
    if (restaurant.phone) {
      window.open(`tel:${restaurant.phone}`);
    }
  }

  /**
   * Reserva anónima de mesa (sin login).
   *
   * `restaurant` es un `bookable_type` válido del backend y `gastronomic` es
   * el tipo de servicio que waivea el chequeo de disponibilidad por slot: el
   * restaurante es quien acepta o rechaza la mesa desde el panel.
   */
  reserve(restaurant: Restaurant): void {
    void this.haptics.selection();
    this.selectedDish.set(null);
    void this.router.navigate(['/booking/new'], {
      queryParams: {
        bookableType: 'restaurant',
        bookableId: restaurant.id,
        type: 'gastronomic',
        title: restaurant.name,
        location: restaurant.address ?? '',
      },
    });
  }
}
