import { Component, Input, OnChanges, OnInit, SimpleChanges, inject, signal } from '@angular/core';
import { AsyncPipe, Location } from '@angular/common';
import { Router } from '@angular/router';
import { IonContent, IonIcon } from '@ionic/angular';

import { Observable, catchError, combineLatest, map, of, shareReplay, switchMap, tap } from 'rxjs';

import { Business } from '../../models';
import { BusinessService } from '../../services/business.service';
import { HapticsService } from '../../core/services/haptics.service';
import { NativeService } from '../../core/services/native.service';
import { FavoritesService } from '../../core/services/favorites.service';
import { SafeImgDirective } from '../../shared/directives/safe-img/safe-img.directive';
import { MediaCarouselComponent } from '../../shared/components/media-carousel/media-carousel.component';
import { SevenContextLoaderComponent } from '../../shared/components/seven-context-loader/seven-context-loader.component';
import { RatingStarsComponent } from '../../shared/components/rating-stars/rating-stars.component';
import { DiscoveryCardComponent, DiscoveryCardItem } from '../../shared/components/discovery-card/discovery-card.component';
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
  warn?: boolean;
}

/** Fila de horario ya lista para pintar. */
interface OpeningRow {
  label: string;
  hours: string;
  today: boolean;
}

const DAY_ORDER = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as const;

const DAY_LABELS: Record<string, string> = {
  monday: 'Lunes',
  tuesday: 'Martes',
  wednesday: 'Miércoles',
  thursday: 'Jueves',
  friday: 'Viernes',
  saturday: 'Sábado',
  sunday: 'Domingo',
};

/** Cuántos alojamientos/lugares relacionados se muestran al final. */
const MAX_RELATED = 6;

/** Cuántas características se ven antes de "Ver todas". */
const VISIBLE_FEATURES = 8;

/**
 * BusinessDetailPage
 *
 * Detalle de un lugar local (negocio / restaurante de barrio). Se alinea con
 * el lenguaje de Tour, Restaurante y Posada Detail: hero con acciones sobre
 * la foto, bloques limpios y barra fija. Recibe `id` de la ruta gracias a
 * `withComponentInputBinding()`.
 *
 * Solo se pinta lo que el API entrega: galería, categoría, descripción,
 * dirección, coordenadas, teléfono/WhatsApp, horario, etiquetas,
 * características verificadas y rating real. El negocio no admite opiniones
 * en el contrato actual, así que no se muestra la sección de reseñas.
 */
@Component({
  selector: 'app-business-detail',
  templateUrl: './business-detail.page.html',
  styleUrls: ['./business-detail.page.scss'],
  standalone: true,
  imports: [
    AsyncPipe,
    IonContent,
    IonIcon,
    SafeImgDirective,
    MediaCarouselComponent,
    SevenContextLoaderComponent,
    RatingStarsComponent,
    DiscoveryCardComponent,
    TranslatePipe,
  ],
})
export class BusinessDetailPage implements OnInit, OnChanges {
  private readonly businesses = inject(BusinessService);
  private readonly haptics = inject(HapticsService);
  private readonly favorites = inject(FavoritesService);
  private readonly native = inject(NativeService);
  private readonly router = inject(Router);
  private readonly location = inject(Location);

  @Input() id!: string;

  loading = true;
  favorite = false;
  descriptionExpanded = false;
  featuresExpanded = false;

  business$!: Observable<Business | null>;
  related$!: Observable<DiscoveryCardItem[]>;

  /** URLs del hero, memoizadas para la reutilización de instancia entre ids. */
  readonly galleryImages = signal<string[]>([]);

  readonly isWide = signal(false);

  private readonly savedKeys = signal<Set<string>>(new Set());
  private businessRef: Business | null = null;

  /**
   * El router reutiliza la instancia cuando cambia solo el id, así que la
   * carga se reacciona a `id` en vez de leerse una vez en `ngOnInit`.
   */
  private readonly idParam = new RouteParam<string>();

  constructor() {
    if (typeof window !== 'undefined' && typeof window.matchMedia === 'function') {
      const query = window.matchMedia('(min-width: 1024px)');
      this.isWide.set(query.matches);
      query.addEventListener('change', (event) => this.isWide.set(event.matches));
    }
  }

  ngOnChanges(changes: SimpleChanges): void {
    this.idParam.bind(changes, 'id', this.id);
  }

  ngOnInit(): void {
    this.favorites.favorites$.subscribe((items) => {
      const next = new Set<string>();
      for (const item of items) {
        next.add(`${item.type}:${item.id}`);
      }
      this.savedKeys.set(next);
    });

    const source = this.idParam.stream$.pipe(
      switchMap((id) => this.businesses.getById(Number(id))),
      catchError(() => of(null)),
    );
    const shared = source.pipe(
      shareReplay({ bufferSize: 1, refCount: true }),
      tap(() => (this.loading = false)),
    );

    shared.subscribe((business) => {
      this.businessRef = business;
      this.favorite = false;
      this.descriptionExpanded = false;
      this.featuresExpanded = false;
      this.galleryImages.set(business ? this.gallery(business) : []);

      if (business) {
        void this.favorites.track({ type: 'business', id: business.id, slug: business.slug, name: business.name });
        void this.favorites.isFavorite('business', business.id).then((value) => (this.favorite = value));
      }
    });

    this.business$ = shared;

    this.related$ = combineLatest([
      shared,
      this.businesses.list().pipe(
        map((paginated) => paginated.items),
        catchError(() => of([] as Business[])),
      ),
    ]).pipe(map(([current, items]) => this.buildRelated(current?.id ?? 0, items)));
  }

  /** Galería del hero a partir de las imágenes reales del negocio. */
  gallery(business: Business): string[] {
    return mediaSources(...(business.images ?? []));
  }

  /** Franja "Lo esencial": estado, categoría y dirección (solo si existen). */
  essentials(business: Business): EssentialFact[] {
    const facts: EssentialFact[] = [];

    const open = this.openNow(business);
    if (open !== null) {
      facts.push({
        icon: open ? 'time-outline' : 'moon-outline',
        value: open ? 'Abierto ahora' : 'Cerrado',
        label: 'Ahora',
        warn: !open,
      });
    }

    if (business.category?.name) {
      facts.push({ icon: 'pricetag-outline', value: business.category.name, label: 'Categoría' });
    }

    if (business.address) {
      facts.push({ icon: 'location-outline', value: business.address, label: 'Dirección' });
    }

    return facts;
  }

  /** Horario semanal, con el día de hoy resaltado. */
  openingRows(business: Business): OpeningRow[] {
    const hours = business.openingHours;
    if (!hours) {
      return [];
    }

    const todayKey = this.todayKey();

    return DAY_ORDER.filter((day) => day in hours).map((day) => {
      const entry = hours[day];
      return {
        label: DAY_LABELS[day],
        hours: entry ? `${entry.open} – ${entry.close}` : 'Cerrado',
        today: day === todayKey,
      };
    });
  }

  /** Características visibles según "Ver todas". */
  visibleFeatures(business: Business): string[] {
    return this.featuresExpanded ? business.features : business.features.slice(0, VISIBLE_FEATURES);
  }

  get hasMoreFeatures(): boolean {
    return (this.businessRef?.features?.length ?? 0) > VISIBLE_FEATURES;
  }

  /** Icono para una característica/etiqueta, mapeado por palabra clave. */
  featureIcon(name: string): string {
    const value = name.toLowerCase();
    if (/wi-?fi|internet/.test(value)) return 'wifi-outline';
    if (/aire|a\/?c\b|climat/.test(value)) return 'snow-outline';
    if (/parquea|estacion|parking|garaje/.test(value)) return 'car-outline';
    if (/domicilio|deliver|env[íi]o/.test(value)) return 'bicycle-outline';
    if (/tarjeta|pago|efectivo|nequi|daviplata/.test(value)) return 'card-outline';
    if (/reserva/.test(value)) return 'calendar-outline';
    if (/terraza|balc|jard|patio/.test(value)) return 'sunny-outline';
    if (/bar|licor|coctel|cóctel|cerveza/.test(value)) return 'wine-outline';
    if (/m[úu]sica|dj|vivo/.test(value)) return 'musical-notes-outline';
    if (/mascota|pet/.test(value)) return 'paw-outline';
    if (/marisc|pescado/.test(value)) return 'fish-outline';
    if (/vegetarian|vegan/.test(value)) return 'leaf-outline';
    if (/caf[ée]|desayun/.test(value)) return 'cafe-outline';
    if (/acces|silla de ruedas/.test(value)) return 'accessibility-outline';
    if (/familia|ni[ñn]os/.test(value)) return 'people-outline';
    return 'checkmark-circle-outline';
  }

  toggleDescription(): void {
    this.descriptionExpanded = !this.descriptionExpanded;
  }

  toggleFeatures(): void {
    this.haptics.selection();
    this.featuresExpanded = !this.featuresExpanded;
  }

  goBack(): void {
    this.location.back();
  }

  goExplore(): void {
    void this.router.navigate(['/explore']);
  }

  share(business: Business): void {
    void this.haptics.selection();
    void this.native.share({
      title: `${business.name} · San Andrés`,
      text: business.description ?? 'Un lugar local en San Andrés.',
      url: window.location.href,
    });
  }

  toggleFavorite(business: Business): void {
    void this.haptics.selection();
    void this.favorites.toggle('business', business.id, business.slug).then((value) => (this.favorite = value));
  }

  /** Abre la ubicación en Google Maps (con coordenadas reales si existen). */
  openMap(business: Business): void {
    void this.haptics.selection();
    window.open(mapsUrl(this.mapTarget(business)), '_blank', 'noopener');
  }

  /** Llamada directa, solo si el negocio publica teléfono. */
  call(business: Business): void {
    if (!business.phone) {
      return;
    }
    void this.haptics.selection();
    window.location.href = `tel:${business.phone.replace(/\s+/g, '')}`;
  }

  /** WhatsApp directo con el lugar. */
  contactWhatsapp(business: Business): void {
    const phone = business.whatsapp ?? business.phone;
    if (!phone) {
      return;
    }
    void this.haptics.selection();
    window.open(
      whatsappLink(phone, `Hola ${business.name}, quisiera más información sobre el lugar.`),
      '_blank',
      'noopener',
    );
  }

  /** Reserva anónima de mesa (sin login). */
  reserve(business: Business): void {
    void this.haptics.selection();
    void this.router.navigate(['/booking/new'], {
      queryParams: {
        bookableType: 'business',
        bookableId: business.id,
        type: 'gastronomic',
        title: business.name,
        location: business.address ?? '',
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

  /** Clave del día actual (monday..sunday) según la fecha local. */
  private todayKey(): string {
    return DAY_ORDER[(new Date().getDay() + 6) % 7];
  }

  /** ¿Está abierto ahora? `null` cuando el negocio no publica horario. */
  private openNow(business: Business): boolean | null {
    const hours = business.openingHours;
    if (!hours) {
      return null;
    }

    const entry = hours[this.todayKey()];
    if (!entry) {
      return false;
    }

    const now = new Date();
    const minutes = now.getHours() * 60 + now.getMinutes();
    const open = this.toMinutes(entry.open);
    const close = this.toMinutes(entry.close);

    if (open === null || close === null) {
      return null;
    }

    // Horarios que cruzan medianoche (p. ej. 18:00 – 02:00).
    if (close <= open) {
      return minutes >= open || minutes < close;
    }
    return minutes >= open && minutes < close;
  }

  private toMinutes(value: string): number | null {
    const match = value.match(/^(\d{1,2}):(\d{2})/);
    if (!match) {
      return null;
    }
    return Number(match[1]) * 60 + Number(match[2]);
  }

  private mapTarget(business: Business): { lat: number; lng: number } | string {
    const lat = Number(business.location?.latitude);
    const lng = Number(business.location?.longitude);
    if (Number.isFinite(lat) && Number.isFinite(lng) && lat !== 0 && lng !== 0) {
      return { lat, lng };
    }
    return business.address || business.name;
  }

  /** Otros lugares (mejor puntuados) como relacionados. */
  private buildRelated(currentId: number, items: Business[]): DiscoveryCardItem[] {
    return items
      .filter((item) => item.id !== currentId)
      .sort((a, b) => b.ratingAvg - a.ratingAvg)
      .slice(0, MAX_RELATED)
      .map((item) => ({
        id: item.id,
        slug: String(item.id),
        type: 'business',
        image: item.coverImage ?? '',
        name: item.name,
        category: item.category?.name ?? 'Lugar local',
        rating: item.ratingAvg,
        location: item.address ?? '',
        price: 0,
        priceLabel: '',
        providerName: '',
        route: ['/business', String(item.id)],
      }));
  }
}
