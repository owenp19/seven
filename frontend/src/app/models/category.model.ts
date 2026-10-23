/**
 * A discoverable category (Restaurantes, Panaderías, Posadas...).
 */
export interface CategoryPayload {
  id: number;
  slug: string;
  name: string;
  icon?: string | null;
  image?: string | null;
  description?: string | null;
  sort_order?: number;
  order?: number;
  status?: string;
  business_count?: number;
}

export class Category {
  constructor(
    public readonly id: number,
    public readonly slug: string,
    public readonly name: string,
    public readonly icon: string | null = null,
    public readonly image: string | null = null,
    public readonly description: string | null = null,
    public readonly sortOrder: number = 0,
    public readonly businessCount: number = 0,
    public readonly status: string = 'active',
  ) {}

  static fromApi(raw: CategoryPayload): Category {
    return new Category(
      raw.id,
      raw.slug,
      raw.name,
      raw.icon,
      raw.image,
      raw.description,
      raw.sort_order ?? 0,
      raw.business_count ?? 0,
      raw.status ?? 'active',
    );
  }
}