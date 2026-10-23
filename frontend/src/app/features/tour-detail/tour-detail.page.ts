import { Component, Input, OnChanges, OnInit, SimpleChanges, inject } from '@angular/core';
import { AsyncPipe, Location } from '@angular/common';
import { Router } from '@angular/router';
import { IonContent, IonIcon, IonSkeletonText } from '@ionic/angular';

import { Observable, catchError, combineLatest, map, of, shareReplay, switchMap, tap } from 'rxjs';

import { Tour } from '../../models/tour.model';
import { Restaurant } from '../../models/restaurant.model';
import { Inn } from '../../models/inn.model';
import { TourService } from '../../services/tour.service';
import { RestaurantService } from '../../services/restaurant.service';
import { InnService } from '../../services/inn.service';
import { HapticsService } from '../../core/services/haptics.service';
import { FavoritesService } from '../../core/services/favorites.service';
import { NativeService } from '../../core/services/native.service';
import { SevenContextLoaderComponent } from '../../shared/components/seven-context-loader/seven-context-loader.component';
import { MediaCarouselComponent } from '../../shared/components/media-carousel/media-carousel.component';
import { SevenResultCardComponent } from '../../shared/components/seven-result-card/seven-result-card.component';
import { CommentsSectionComponent } from '../../shared/components/comments-section/comments-section.component';
import { CurrencyPipe } from '../../shared/pipes/currency/currency.pipe';
import { TranslatePipe } from '../../shared/pipes/translate/translate.pipe';
import { mediaSources } from '../../shared/utils/media';
import { RouteParam } from '../../shared/utils/route-param';
import {
  ResolvedSpot,
  estimateNearby,
  mapsUrl,
  resolveSpot,
} from '../../shared/utils/geo';
import type { NearbyEstimate } from '../../shared/utils/geo';
import { TourReview, sampleReviews } from '../../shared/data/tour-reviews';
import { whatsappLink } from '../../utils/formatters';
import { DEFAULT_OPERATOR_PHONE } from '../../shared/constants/island';

type Difficulty = 'Fácil' | 'Media' | 'Exigente';

/** Tarjeta de recomendación mixta (tours / restaurantes / posadas). */
interface SuggestionCard {
  image: string;
  name: string;
  category: string;
  description: string;
  rating: number;
  ratingCount: number;
  price: number;
  priceLabel: string;
  link: string;
  alt: string;
}

/** How many suggestions are rendered below the tour profile. */
const MAX_SUGGESTIONS = 6;

/**
 * TourDetailPage
 *
 * Pushed page (outside the tab stack) that shows the full profile of
 * a tour in a minimal "international travel app" layout: immersive
 * hero, quick facts, group planner, description, gallery, includes,
 * recommendations, traveller reviews and distance-to-spot, plus a
 * sticky price bar. Receives `slug` from the route thanks to
 * `withComponentInputBinding()`.
 */
@Component({
  selector: 'app-tour-detail',
  templateUrl: './tour-detail.page.html',
  styleUrls: ['./tour-detail.page.scss'],
  standalone: true,
  imports: [AsyncPipe, IonContent, IonIcon, IonSkeletonText, SevenContextLoaderComponent, MediaCarouselComponent, SevenResultCardComponent, CommentsSectionComponent, CurrencyPipe, TranslatePipe],
})
export class TourDetailPage implements OnInit, OnChanges {
  private readonly tours = inject(TourService);
  private readonly restaurants = inject(RestaurantService);
  private readonly inns = inject(InnService);
  private readonly haptics = inject(HapticsService);
  private readonly favorites = inject(FavoritesService);
  private readonly native = inject(NativeService);
  private readonly router = inject(Router);
  private readonly location = inject(Location);

  @Input() slug!: string;

  favorite = false;
  descriptionExpanded = false;
  loading = true;
  suggestions$!: Observable<SuggestionCard[]>;

  /** Estado del widget "Cerca de mí". */
  spot: ResolvedSpot | null = null;
  near: NearbyEstimate | null = null;
  locating = false;
  locError = false;

  /** Planificador de grupo (mini herramienta). */
  persons = 1;
  readonly minPersons = 1;
  readonly maxPersons = 20;

  private tourRef: Tour | null = null;
  reviews: TourReview[] = [];
  tour$!: Observable<Tour | null>;

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
      switchMap((slug) => this.tours.getBySlug(slug)),
      catchError(() => of(null)),
    );
    const shared = source.pipe(
      shareReplay({ bufferSize: 1, refCount: true }),
      tap(() => (this.loading = false)),
    );

    shared.subscribe((tour) => {
      if (tour) {
        this.tourRef = tour;
        this.reviews = sampleReviews(tour.id);
        this.spot = resolveSpot(tour.location || '');
        void this.favorites.track({ type: 'tour', id: tour.id, slug: tour.slug, name: tour.name });
        void this.favorites.isFavorite('tour', tour.id).then((value) => (this.favorite = value));
      }
    });

    this.tour$ = shared;

    this.suggestions$ = combineLatest([
      this.tours.getAll().pipe(catchError(() => of([] as Tour[]))),
      this.restaurants.getAll().pipe(catchError(() => of([] as Restaurant[]))),
      this.inns.getAll().pipe(catchError(() => of([] as Inn[]))),
    ]).pipe(
      map(([tours, restaurants, inns]) => this.buildSuggestions(tours, restaurants, inns)),
    );
  }

  /**
   * Recomendaciones variadas: mezcla tours, restaurantes y posadas
   * (excluye el tour actual) priorizando los mejor puntuados y
   * alternándolos por vertical para que la fila siempre luzca variada.
   */
  private buildSuggestions(tours: Tour[], restaurants: Restaurant[], inns: Inn[]): SuggestionCard[] {
    const currentSlug = this.tourRef?.slug;

    const toTourCards = (items: Tour[]): SuggestionCard[] =>
      items
        .filter((item) => item.slug !== currentSlug)
        .map((item) => ({
          image: item.cover,
          name: item.name,
          category: item.category ?? 'Tours y experiencias',
          description: item.subtitle || item.description,
          rating: item.rating,
          ratingCount: item.reviews,
          price: item.price,
          priceLabel: 'Desde',
          link: '/tour/' + item.slug,
          alt: item.name,
        }));

    const toRestaurantCards = (items: Restaurant[]): SuggestionCard[] =>
      items.map((item) => ({
        image: item.cover,
        name: item.name,
        category: item.category ?? 'Restaurantes',
        description: item.description,
        rating: item.rating,
        ratingCount: item.reviews,
        price: 0,
        priceLabel: '',
        link: '/restaurant/' + item.slug,
        alt: item.name,
      }));

    const toInnCards = (items: Inn[]): SuggestionCard[] =>
      items.map((item) => ({
        image: item.coverImage,
        name: item.name,
        category: 'Posadas nativas',
        description: item.description,
        rating: item.rating,
        ratingCount: 0,
        price: item.price,
        priceLabel: 'Noche',
        link: '/inn/' + item.slug,
        alt: item.name,
      }));

    const byRating = (items: SuggestionCard[]) => [...items].sort((a, b) => b.rating - a.rating);

    return this.roundRobin([byRating(toTourCards(tours)), byRating(toRestaurantCards(restaurants)), byRating(toInnCards(inns))])
      .slice(0, MAX_SUGGESTIONS);
  }

  /** Intercala varias listas elemento a elemento para garantizar variedad. */
  private roundRobin<T>(lists: T[][]): T[] {
    const merged: T[] = [];
    let depth = 0;
    let remaining = true;
    while (remaining) {
      remaining = false;
      for (const list of lists) {
        if (depth < list.length) {
          merged.push(list[depth]);
          remaining = true;
        }
      }
      depth += 1;
    }
    return merged;
  }

  get total(): number {
    return (this.tourRef?.price ?? 0) * this.persons;
  }

  /**
   * Imágenes del carrusel del hero: la portada primero y después la galería,
   * normalizadas para no repetir la portada si también está en la galería.
   */
  heroImages(tour: Tour): string[] {
    return mediaSources(tour.cover, ...tour.gallery);
  }

  /** Índices para pintar estrellas en reseñas. */
  stars(rating: number): number[] {
    const count = Math.max(1, Math.min(5, Math.round(rating)));
    return Array.from({ length: count }, (_, i) => i);
  }

  /** Nivel de esfuerzo derivado de la duración (sin datos extra en la API). */
  get difficulty(): Difficulty {
    const duration = this.tourRef?.duration ?? '';
    const match = duration.match(/(\d+)\s*horas?/i);
    const minutes = match ? Number(match[1]) * 60 : 0;
    if (minutes <= 300) {
      return 'Fácil';
    }
    if (minutes <= 360) {
      return 'Media';
    }
    return 'Exigente';
  }

  goBack(): void {
    this.location.back();
  }

  toggleFavorite(tour: Tour): void {
    void this.haptics.selection();
    void this.favorites.toggle('tour', tour.id, tour.slug).then((value) => (this.favorite = value));
  }

  toggleDescription(): void {
    this.descriptionExpanded = !this.descriptionExpanded;
  }

  /** Comparte el tour (share nativo con fallback web / portapapeles). */
  share(tour: Tour): void {
    void this.haptics.selection();
    void this.native.share({
      title: `${tour.name} · San Andrés`,
      text: `${tour.name}: ${tour.subtitle || 'una experiencia imperdible en la isla'}`,
      url: window.location.href,
    });
  }

  /** Posiciona al usuario y estima distancia hasta el punto del tour. */
  async locate(): Promise<void> {
    if (!this.spot) {
      return;
    }
    this.locating = true;
    this.locError = false;
    const origin = await this.native.getPosition();
    if (origin) {
      this.near = estimateNearby(origin, this.spot);
    } else {
      this.locError = true;
    }
    this.locating = false;
  }

  /** Abre la ubicación en Google Maps (con coordenadas si se conocen). */
  openMap(tour: Tour): void {
    void this.haptics.selection();
    const target = this.spot ? { lat: this.spot.lat, lng: this.spot.lng } : tour.location || tour.name;
    window.open(mapsUrl(target), '_blank', 'noopener');
  }

  /** WhatsApp directo con el operador (sin login). */
  askWhatsapp(tour: Tour): void {
    void this.haptics.selection();
    const phone = DEFAULT_OPERATOR_PHONE;
    const text = `Hola, vi el tour "${tour.name}" en SEVEN y ${this.persons} ${this.persons === 1 ? 'persona' : 'personas'} quieren hacerlo (${this.total.toLocaleString('es-CO')} COP aprox.). ¿Tienen disponibilidad para el tour?`;
    window.open(whatsappLink(phone, text), '_blank', 'noopener');
  }

  /** CTA principal: lleva al flujo de reserva anónimo (sin login). */
  reserve(tour: Tour): void {
    void this.haptics.selection();
    void this.router.navigate(['/booking/new'], {
      queryParams: {
        bookableType: 'tour',
        bookableId: tour.id,
        type: 'experience',
        title: tour.name,
        price: tour.price,
        duration: tour.duration,
        location: tour.location,
      },
    });
  }

  addPerson(): void {
    if (this.persons < this.maxPersons) {
      this.persons += 1;
    }
  }

  removePerson(): void {
    if (this.persons > this.minPersons) {
      this.persons -= 1;
    }
  }
}