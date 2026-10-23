import type { ProviderPayload } from './provider.model';

/**
 * SEVEN Inn — posada nativa (alojamiento). Una posada tiene habitaciones
 * con precio, capacidad e imagen propias.
 */

export interface InnRoomPayload {
  id: number;
  name: string;
  description: string | null;
  price: number;
  image: string | null;
  capacity: number;
}

export interface InnRoom {
  id: number;
  name: string;
  description: string;
  price: number;
  image: string;
  capacity: number;
}

export interface InnPayload {
  id: number;
  slug: string;
  name: string;
  description: string | null;
  cover_image: string | null;
  location: string | null;
  price: number;
  services: string[];
  availability: boolean;
  rating: number;
  status: string;
  rooms?: InnRoomPayload[];
  provider?: ProviderPayload | null;
}

export class Inn {
  constructor(
    public readonly id: number,
    public readonly slug: string,
    public readonly name: string,
    public readonly description: string,
    public readonly coverImage: string,
    public readonly location: string,
    public readonly price: number,
    public readonly services: string[],
    public readonly availability: boolean,
    public readonly rating: number,
    public readonly rooms: InnRoom[],
    /** Nombre del operador que lleva la posada; vacío si no hay proveedor. */
    public readonly providerName = '',
    /** Logo del proveedor, si lo tiene. */
    public readonly providerLogo = '',
    /** `true` solo si el proveedor está verificado por el backend. */
    public readonly providerVerified = false,
    /** Teléfono público del proveedor para contacto; vacío si no existe. */
    public readonly providerPhone = '',
  ) {}

  static fromApi(raw: InnPayload): Inn {
    return new Inn(
      raw.id,
      raw.slug,
      raw.name,
      raw.description ?? '',
      raw.cover_image ?? '',
      raw.location ?? '',
      raw.price ?? 0,
      raw.services ?? [],
      raw.availability ?? false,
      raw.rating ?? 0,
      (raw.rooms ?? []).map((room) => ({
        id: room.id,
        name: room.name,
        description: room.description ?? '',
        price: room.price ?? 0,
        image: room.image ?? '',
        capacity: room.capacity ?? 0,
      })),
      raw.provider?.business_name?.trim() || raw.provider?.owner_name?.trim() || '',
      raw.provider?.logo?.trim() || '',
      raw.provider?.verification_status === 'verified',
      raw.provider?.phone?.trim() || '',
    );
  }
}