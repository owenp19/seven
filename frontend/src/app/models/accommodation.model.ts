import type { GeoPoint } from '../interfaces';

/**
 * Payload returned by the accommodations endpoints.
 */
export interface AccommodationPayload {
  id: number;
  slug: string;
  name: string;
  description: string | null;
  capacity: number;
  price_per_night: number;
  currency: string;
  address: string | null;
  location: GeoPoint;
  images: string[];
  included_services: string[];
  rating_avg: number;
  rating_count: number;
}

/**
 * Native inns (posadas nativas) and lodging offers.
 */
export class Accommodation {
  constructor(
    public readonly id: number,
    public readonly slug: string,
    public readonly name: string,
    public readonly description: string | null,
    public readonly capacity: number,
    public readonly pricePerNight: number,
    public readonly currency: string,
    public readonly address: string | null,
    public readonly location: GeoPoint,
    public readonly images: string[],
    public readonly includedServices: string[],
    public readonly ratingAvg: number,
    public readonly ratingCount: number,
  ) {}

  get coverImage(): string | null {
    return this.images.length > 0 ? this.images[0] : null;
  }

  static fromApi(raw: AccommodationPayload): Accommodation {
    return new Accommodation(
      raw.id,
      raw.slug,
      raw.name,
      raw.description,
      raw.capacity,
      raw.price_per_night,
      raw.currency,
      raw.address,
      raw.location,
      raw.images ?? [],
      raw.included_services ?? [],
      raw.rating_avg ?? 0,
      raw.rating_count ?? 0,
    );
  }
}