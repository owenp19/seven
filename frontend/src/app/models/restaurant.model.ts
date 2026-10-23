import type { ProviderPayload } from './provider.model';

/**
 * SEVEN Restaurant — gastronomía de la isla (antigua vertical de
 * negocios, ahora marketplace independiente con su propio menú).
 */

export interface RestaurantProductPayload {
  id: number;
  name: string;
  description: string | null;
  price: number;
  image: string | null;
  category: string | null;
  status: string;
}

export interface RestaurantProduct {
  id: number;
  name: string;
  description: string;
  price: number;
  image: string;
  category: string;
}

export interface RestaurantPayload {
  id: number;
  slug: string;
  name: string;
  logo: string | null;
  cover_image: string | null;
  description: string | null;
  address: string | null;
  phone: string | null;
  category: string | null;
  rating: number;
  reviews: number;
  opening_hours: string | null;
  price_range: string | null;
  location: { latitude: number; longitude: number };
  status: string;
  products?: RestaurantProductPayload[];
  provider?: ProviderPayload | null;
}

export class Restaurant {
  constructor(
    public readonly id: number,
    public readonly slug: string,
    public readonly name: string,
    public readonly logo: string,
    public readonly coverImage: string,
    public readonly description: string,
    public readonly address: string,
    public readonly phone: string,
    public readonly category: string | null,
    public readonly rating: number,
    public readonly reviews: number,
    public readonly openingHours: string,
    public readonly priceRange: string,
    public readonly products: RestaurantProduct[],
    /** Nombre del operador del restaurante; vacío si no hay proveedor. */
    public readonly providerName = '',
  ) {}

  get cover(): string {
    return this.coverImage || this.logo || '';
  }

  static fromApi(raw: RestaurantPayload): Restaurant {
    return new Restaurant(
      raw.id,
      raw.slug,
      raw.name,
      raw.logo ?? '',
      raw.cover_image ?? '',
      raw.description ?? '',
      raw.address ?? '',
      raw.phone ?? '',
      raw.category ?? null,
      raw.rating ?? 0,
      raw.reviews ?? 0,
      raw.opening_hours ?? '',
      raw.price_range ?? '',
      (raw.products ?? []).map((product) => ({
        id: product.id,
        name: product.name,
        description: product.description ?? '',
        price: product.price ?? 0,
        image: product.image ?? '',
        category: product.category ?? '',
      })),
      raw.provider?.business_name?.trim() || raw.provider?.owner_name?.trim() || '',
    );
  }
}