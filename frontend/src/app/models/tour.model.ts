import type { ProviderPayload } from './provider.model';

/**
 * SEVEN Tour — experiencia turística vendible (tours y actividades).
 *
 * El payload proviene del TourResource del backend: `cover_image` como
 * URL absoluta, galería a través de `images`, y los chips de includes /
 * recomendaciones. Los features del detalle se derivan de `duration`,
 * `category` y `location` (el backend no expone ese combo de iconos).
 */

export interface TourFeature {
  icon: string;
  /** Valor destacado, p. ej. "4 horas". */
  value: string;
  /** Etiqueta del feature, p. ej. "Duración". */
  label: string;
}

export interface TourChip {
  icon: string;
  name: string;
}

export interface TourImagePayload {
  id: number;
  image: string;
  order: number;
}

export interface TourImage {
  id: number;
  image: string;
  order: number;
}

export interface TourPayload {
  id: number;
  slug: string;
  name: string;
  subtitle: string | null;
  description: string | null;
  category: string | null;
  location: string | null;
  duration: string | null;
  price: number;
  rating: number;
  reviews: number;
  cover_image: string | null;
  status: string;
  images?: TourImagePayload[];
  includes?: TourChip[];
  recommendations?: TourChip[];
  provider?: ProviderPayload | null;
}

export class Tour {
  constructor(
    public readonly id: number,
    public readonly slug: string,
    public readonly name: string,
    public readonly subtitle: string,
    public readonly description: string,
    public readonly category: string | null,
    public readonly location: string,
    public readonly duration: string,
    public readonly price: number,
    public readonly rating: number,
    public readonly reviews: number,
    public readonly cover: string,
    public readonly images: TourImage[],
    public readonly includes: TourChip[],
    public readonly recommendations: TourChip[],
    /** Nombre del operador que vende la experiencia; vacío si no hay proveedor. */
    public readonly providerName = '',
  ) {}

  /** Galería de fotos como lista de URLs. */
  get gallery(): string[] {
    return this.images.map((image) => image.image);
  }

  /** Contador de fotos de portada. */
  get photos(): number {
    return this.images.length;
  }

  /** Lugar principal (alias de ubicación). */
  get place(): string {
    return this.location;
  }

  /** Chips del detalle derivados de los datos del tour. */
  get features(): TourFeature[] {
    const features: TourFeature[] = [];
    if (this.duration) {
      features.push({ icon: 'time-outline', value: this.duration, label: 'Duración' });
    }
    if (this.category) {
      features.push({ icon: 'compass-outline', value: this.category, label: 'Tipo' });
    }
    if (this.location) {
      features.push({
        icon: 'location-outline',
        value: this.location.split(',')[0],
        label: 'Ubicación',
      });
    }
    return features;
  }

  static fromApi(raw: Partial<TourPayload> & Pick<TourPayload, 'id' | 'name'>): Tour {
    return new Tour(
      raw.id,
      raw.slug ?? '',
      raw.name,
      raw.subtitle ?? '',
      raw.description ?? '',
      raw.category ?? null,
      raw.location ?? '',
      raw.duration ?? '',
      raw.price ?? 0,
      raw.rating ?? 0,
      raw.reviews ?? 0,
      raw.cover_image ?? '',
      (raw.images ?? []).map((image) => ({
        id: image.id,
        image: image.image,
        order: image.order,
      })),
      raw.includes ?? [],
      raw.recommendations ?? [],
      raw.provider?.business_name?.trim() || raw.provider?.owner_name?.trim() || '',
    );
  }
}