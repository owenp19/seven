import { Component, Input, OnChanges, OnInit, SimpleChanges, inject, signal } from '@angular/core';
import { AsyncPipe, Location } from '@angular/common';
import { Router } from '@angular/router';
import { IonContent, IonIcon } from '@ionic/angular';

import { Observable, catchError, combineLatest, map, of, shareReplay, switchMap, tap } from 'rxjs';

import { Inn, InnRoom } from '../../models';
import { InnService } from '../../services/inn.service';
import { HapticsService } from '../../core/services/haptics.service';
import { NativeService } from '../../core/services/native.service';
import { FavoritesService } from '../../core/services/favorites.service';
import { SafeImgDirective } from '../../shared/directives/safe-img/safe-img.directive';
import { MediaCarouselComponent } from '../../shared/components/media-carousel/media-carousel.component';
import { SevenContextLoaderComponent } from '../../shared/components/seven-context-loader/seven-context-loader.component';
import { DiscoveryCardComponent, DiscoveryCardItem } from '../../shared/components/discovery-card/discovery-card.component';
import { CommentsSectionComponent } from '../../shared/components/comments-section/comments-section.component';
import { CurrencyPipe } from '../../shared/pipes/currency/currency.pipe';
import { TranslatePipe } from '../../shared/pipes/translate/translate.pipe';
import { mediaSources } from '../../shared/utils/media';
import { RouteParam } from '../../shared/utils/route-param';
import { mapsUrl } from '../../shared/utils/geo';
import { whatsappLink } from '../../utils/formatters';

/** Hecho de la franja "Lo esencial": icono, valor y etiqueta. */
interface EssentialFact {
  icon: string;
  value: string;
  label: string;
}

/** Cuántos alojamientos relacionados se muestran al final. */
const MAX_RELATED = 6;

/** Cuántos servicios se ven antes de "Ver todos". */
const VISIBLE_SERVICES = 8;

/**
 * InnDetailPage — Detalle de posada nativa.
 *
 * Comparte el lenguaje de Detalle de Tour y Restaurante (hero con acciones
 * sobre la foto, bloques limpios separados por una línea y barra fija de
 * reserva) pero con personalidad propia de alojamiento: galería más baja,
 * la información vive en el cuerpo y no sobre la imagen, una franja
 * "Lo esencial", las opciones de estadía y una banda editorial con la
 * identidad local.
 *
 * Regla de oro: solo se pinta lo que la API entrega de verdad. La posada
 * expone nombre, descripción, portada, sector, precio por noche, servicios,
 * disponibilidad, rating, habitaciones (con capacidad y precio) y el
 * proveedor (nombre, logo y verificación). Lo que no existe (camas, baños,
 * check-in/out, políticas, coordenadas, nº de opiniones) NO se inventa: el
 * botón de reserva usa el flujo anónimo ya existente.
 */
@Component({
  selector: 'app-inn-detail',
  templateUrl: './inn-detail.page.html',
  styleUrls: ['./inn-detail.page.scss'],
  standalone: true,
  imports: [
    AsyncPipe,
    IonContent,
    IonIcon,
    SafeImgDirective,
    MediaCarouselComponent,
    SevenContextLoaderComponent,
    DiscoveryCardComponent,
    CommentsSectionComponent,
    CurrencyPipe,
    TranslatePipe,
  ],
})
export class InnDetailPage implements OnInit, OnChanges {
  private readonly inns = inject(InnService);
  private readonly haptics = inject(HapticsService);
  private readonly favorites = inject(FavoritesService);
  private readonly native = inject(NativeService);
  private readonly router = inject(Router);
  private readonly location = inject(Location);

  @Input() slug!: string;

  loading = true;
  favorite = false;
  descriptionExpanded = false;
  servicesExpanded = false;

  inn$!: Observable<Inn | null>;
  related$!: Observable<DiscoveryCardItem[]>;

  /**
   * URLs del hero. Se memoizan en una señal porque la portada se reconstruye
   * si el arreglo cambia de identidad (la instancia se reutiliza entre slugs).
   */
  readonly galleryImages = signal<string[]>([]);

  /** Flota solo el panel/elemento de reserva fijo en pantallas grandes. */
  readonly isWide = signal(false);

  private readonly savedKeys = signal<Set<string>>(new Set());
  private innRef: Inn | null = null;

  /**
   * El router reutiliza la instancia cuando cambia solo el slug, así que la
   * carga se reacciona a `slug` en vez de leerse una vez en `ngOnInit`.
   */
  private readonly slugParam = new RouteParam<string>();

  constructor() {
    if (typeof window !== 'undefined' && typeof window.matchMedia === 'function') {
      const query = window.matchMedia('(min-width: 1024px)');
      this.isWide.set(query.matches);
      query.addEventListener('change', (event) => this.isWide.set(event.matches));
    }
  }

  ngOnChanges(changes: SimpleChanges): void {
    this.slugParam.bind(changes, 'slug', this.slug);
  }

  ngOnInit(): void {
    this.favorites.favorites$.subscribe((items) => {
      const next = new Set<string>();
      for (const item of items) {
        next.add(`${item.type}:${item.id}`);
      }
      this.savedKeys.set(next);
    });

    const source = this.slugParam.stream$.pipe(
      switchMap((slug) => this.inns.getBySlug(slug)),
      catchError(() => of(null)),
    );
    const shared = source.pipe(
      shareReplay({ bufferSize: 1, refCount: true }),
      tap(() => (this.loading = false)),
    );

    shared.subscribe((inn) => {
      this.innRef = inn;
      this.favorite = false;
      this.descriptionExpanded = false;
      this.servicesExpanded = false;
      this.galleryImages.set(inn ? this.gallery(inn) : []);

      if (inn) {
        void this.favorites.track({ type: 'inn', id: inn.id, slug: inn.slug, name: inn.name });
        void this.favorites.isFavorite('inn', inn.id).then((value) => (this.favorite = value));
      }
    });

    this.inn$ = shared;

    this.related$ = combineLatest([
      shared,
      this.inns.getAll().pipe(catchError(() => of([] as Inn[]))),
    ]).pipe(map(([current, all]) => this.buildRelated(current?.slug ?? '', all)));
  }

  /**
   * El backend solo expone `cover_image` para la posada, así que el carrusel
   * se completa con las fotos de las habitaciones (que sí traen imagen).
   */
  gallery(inn: Inn): string[] {
    return mediaSources(inn.coverImage, ...(inn.rooms ?? []).map((room) => room.image));
  }

  /** Franja "Lo esencial" — solo hechos respaldados por la API. */
  essentials(inn: Inn): EssentialFact[] {
    const facts: EssentialFact[] = [];

    if (inn.rooms && inn.rooms.length > 0) {
      facts.push({ icon: 'bed-outline', value: String(inn.rooms.length), label: 'Habitaciones' });
    }

    const capacity = this.maxCapacity(inn);
    if (capacity > 0) {
      facts.push({ icon: 'people-outline', value: `Hasta ${capacity}`, label: 'Por habitación' });
    }

    if (inn.location) {
      facts.push({ icon: 'location-outline', value: inn.location.split(',')[0].trim(), label: 'Sector' });
    }

    facts.push({
      icon: inn.availability ? 'checkmark-circle-outline' : 'close-circle-outline',
      value: inn.availability ? 'Disponible' : 'Completa',
      label: 'Estado',
    });

    return facts;
  }

  /** Servicios visibles según "Ver todos". */
  visibleServices(inn: Inn): string[] {
    return this.servicesExpanded ? inn.services : inn.services.slice(0, VISIBLE_SERVICES);
  }

  get hasMoreServices(): boolean {
    return (this.innRef?.services?.length ?? 0) > VISIBLE_SERVICES;
  }

  /**
   * Icono para un servicio de la API. Se mapea por palabra clave sobre texto
   * real (no se inventan comodidades): lo que no se reconoce usa un check.
   */
  serviceIcon(name: string): string {
    const value = name.toLowerCase();
    if (/wi-?fi|internet/.test(value)) return 'wifi-outline';
    if (/aire|a\/?c\b|climat/.test(value)) return 'snow-outline';
    if (/desayun|comida|aliment|restaurant/.test(value)) return 'restaurant-outline';
    if (/\btv\b|televis/.test(value)) return 'tv-outline';
    if (/cocina|kitchen/.test(value)) return 'grid-outline';
    if (/piscina|pool/.test(value)) return 'water-outline';
    if (/playa|beach/.test(value)) return 'umbrella-outline';
    if (/terraza|balc|jard|patio/.test(value)) return 'sunny-outline';
    if (/parquea|estacion|parking|garaje/.test(value)) return 'car-outline';
    if (/mascota|pet/.test(value)) return 'paw-outline';
    if (/ba(ñ|n)o|ducha|agua caliente/.test(value)) return 'water-outline';
    if (/nevera|refriger|minibar/.test(value)) return 'beer-outline';
    if (/ventilador/.test(value)) return 'leaf-outline';
    if (/recepc|segur/.test(value)) return 'shield-checkmark-outline';
    return 'checkmark-circle-outline';
  }

  toggleDescription(): void {
    this.descriptionExpanded = !this.descriptionExpanded;
  }

  toggleServices(): void {
    this.haptics.selection();
    this.servicesExpanded = !this.servicesExpanded;
  }

  goBack(): void {
    this.location.back();
  }

  goExplore(): void {
    void this.router.navigate(['/inns']);
  }

  share(inn: Inn): void {
    void this.haptics.selection();
    void this.native.share({
      title: `${inn.name} · San Andrés`,
      text: inn.description || 'Una posada nativa en San Andrés.',
      url: window.location.href,
    });
  }

  toggleFavorite(inn: Inn): void {
    void this.haptics.selection();
    void this.favorites.toggle('inn', inn.id, inn.slug).then((value) => (this.favorite = value));
  }

  /** Abre el sector en Google Maps (sin coordenadas: se busca por texto real). */
  openMap(inn: Inn): void {
    void this.haptics.selection();
    window.open(mapsUrl(inn.location || inn.name), '_blank', 'noopener');
  }

  /** WhatsApp directo con el proveedor, solo si expone un teléfono público. */
  contactProvider(inn: Inn): void {
    if (!inn.providerPhone) {
      return;
    }
    void this.haptics.selection();
    window.open(
      whatsappLink(inn.providerPhone, `Hola, quisiera información sobre "${inn.name}" en SEVEN. ¿Está disponible?`),
      '_blank',
      'noopener',
    );
  }

  /**
   * Reserva anónima (sin login). El backend trata la posada como una unidad
   * bookable (`inn`), así que la reserva viaja por la posada; si el viajero
   * eligió una habitación, su nombre y precio se adjuntan al título para que
   * el operador sepa qué se pidió.
   */
  reserve(inn: Inn, room?: InnRoom): void {
    void this.haptics.selection();
    const title = room ? `${inn.name} · ${room.name}` : inn.name;
    const price = room ? room.price : inn.price;
    void this.router.navigate(['/booking/new'], {
      queryParams: {
        bookableType: 'inn',
        bookableId: inn.id,
        type: 'accommodation',
        title,
        price,
        location: inn.location ?? '',
      },
    });
  }

  openRelated(item: DiscoveryCardItem): void {
    void this.haptics.selection();
    void this.router.navigate(item.route);
  }

  isSaved(item: DiscoveryCardItem): boolean {
    return this.savedKeys().has(`${item.type}:${item.id}`);
  }

  toggleSaveRelated(item: DiscoveryCardItem): void {
    void this.haptics.selection();
    void this.favorites.toggle(item.type, item.id, item.slug);
  }

  private maxCapacity(inn: Inn): number {
    return (inn.rooms ?? []).reduce((max, room) => Math.max(max, room.capacity || 0), 0);
  }

  /** Otras posadas (mejor puntuadas) como alojamientos relacionados. */
  private buildRelated(currentSlug: string, inns: Inn[]): DiscoveryCardItem[] {
    return inns
      .filter((inn) => inn.slug !== currentSlug)
      .sort((a, b) => b.rating - a.rating)
      .slice(0, MAX_RELATED)
      .map((inn) => ({
        id: inn.id,
        slug: inn.slug,
        type: 'inn',
        image: inn.coverImage,
        name: inn.name,
        category: 'Posada nativa',
        rating: inn.rating,
        location: inn.location,
        price: inn.price,
        priceLabel: 'Desde',
        providerName: inn.providerName,
        route: ['/inn', inn.slug],
      }));
  }
}
