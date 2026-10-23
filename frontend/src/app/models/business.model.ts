import type { Category } from './category.model';
import type { GeoPoint, OpeningHours } from '../interfaces';

/**
 * Payload returned by the business endpoints (mirror of the backend
 * BusinessResource).
 */
export interface BusinessPayload {
  id: number;
  slug: string;
  name: string;
  description: string | null;
  category?: Category | null;
  address: string | null;
  location: GeoPoint;
  phone: string | null;
  whatsapp: string | null;
  email: string | null;
  images: string[];
  opening_hours: OpeningHours | null;
  tags: string[];
  features: string[];
  is_verified: boolean;
  rating_avg: number;
  rating_count: number;
}

/**
 * A local business/venue listed on SEVEN (restaurant, bakery, fast
 * food, raizal cuisine...). Used by the service and accommodation-adjacent
 * cards in the app UI.
 */
export class Business {
  constructor(
    public readonly id: number,
    public readonly slug: string,
    public readonly name: string,
    public readonly description: string | null,
    public readonly category: Category | null,
    public readonly address: string | null,
    public readonly location: GeoPoint,
    public readonly phone: string | null,
    public readonly whatsapp: string | null,
    public readonly email: string | null,
    public readonly images: string[],
    public readonly openingHours: OpeningHours | null,
    public readonly tags: string[],
    public readonly features: string[],
    public readonly isVerified: boolean,
    public readonly ratingAvg: number,
    public readonly ratingCount: number,
  ) {}

  get coverImage(): string | null {
    return this.images.length > 0 ? this.images[0] : null;
  }

  static fromApi(raw: BusinessPayload): Business {
    return new Business(
      raw.id,
      raw.slug,
      raw.name,
      raw.description,
      raw.category ?? null,
      raw.address,
      raw.location,
      raw.phone,
      raw.whatsapp,
      raw.email,
      raw.images ?? [],
      raw.opening_hours,
      raw.tags ?? [],
      raw.features ?? [],
      raw.is_verified,
      raw.rating_avg ?? 0,
      raw.rating_count ?? 0,
    );
  }
}