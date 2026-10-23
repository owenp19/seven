import type { ProviderPayload } from './provider.model';

/**
 * SEVEN Promotion — oferta de temporada de un proveedor (descuento o
 * precio especial). Consumida por la Home y la página de promociones.
 */

export interface PromotionPayload {
  id: number;
  provider_id: number | null;
  title: string;
  description: string | null;
  image: string | null;
  discount: number;
  price: number | null;
  start_date: string | null;
  end_date: string | null;
  status: string;
  provider?: ProviderPayload | null;
}

export class Promotion {
  constructor(
    public readonly id: number,
    public readonly providerId: number,
    public readonly title: string,
    public readonly description: string,
    public readonly image: string,
    public readonly discount: number,
    public readonly price: number | null,
    public readonly startDate: string,
    public readonly endDate: string,
    public readonly providerName: string,
  ) {}

  static fromApi(raw: PromotionPayload): Promotion {
    return new Promotion(
      raw.id,
      raw.provider_id ?? 0,
      raw.title,
      raw.description ?? '',
      raw.image ?? '',
      raw.discount ?? 0,
      raw.price ?? null,
      raw.start_date ?? '',
      raw.end_date ?? '',
      raw.provider?.business_name ?? '',
    );
  }
}