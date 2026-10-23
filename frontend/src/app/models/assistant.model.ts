/**
 * SEVEN Assistant — modelo de respuesta del asistente turístico.
 *
 * El backend (`POST /api/v1/assistant/messages`) devuelve un contrato estable
 * con el texto del asistente y, además, recomendaciones y paquetes ya
 * resueltos contra el catálogo REAL. El cliente solo pinta lo que llega: nunca
 * inventa precios, valoraciones ni disponibilidad.
 *
 * `route` es la ruta de Angular lista para `router.navigate`; llega `null`
 * cuando aún no existe una pantalla para esa vertical (mejora futura).
 */

/** Verticales del catálogo que el asistente puede recomendar. */
export type AssistantItemType =
  | 'tour'
  | 'experience'
  | 'inn'
  | 'accommodation'
  | 'restaurant'
  | 'bakery'
  | 'business';

/** Unidad del precio unitario devuelto por el backend. */
export type AssistantPriceUnit = 'per_person' | 'per_night' | 'per_item';

/** Disponibilidad publicada por el catálogo; `unknown` cuando no consta. */
export type AssistantAvailability = 'available' | 'unavailable' | 'unknown';

const ITEM_TYPES: readonly AssistantItemType[] = [
  'tour',
  'experience',
  'inn',
  'accommodation',
  'restaurant',
  'bakery',
  'business',
];

const PRICE_UNITS: readonly AssistantPriceUnit[] = ['per_person', 'per_night', 'per_item'];

const AVAILABILITIES: readonly AssistantAvailability[] = ['available', 'unavailable', 'unknown'];

export interface AssistantRecommendationPayload {
  type: string;
  id: string;
  name: string;
  summary: string | null;
  price: number | null;
  price_unit: string | null;
  rating: number | null;
  review_count: number | null;
  location: string | null;
  image: string | null;
  route: string[] | null;
  provider: string | null;
  availability: string;
  extra?: Record<string, unknown>;
}

export interface AssistantPackagePayload {
  total: number;
  remaining: number;
  items: AssistantRecommendationPayload[];
}

export interface AssistantReplyMeta {
  provider: string;
  fallback: boolean;
  iterations: number;
}

export interface AssistantReplyPayload {
  conversation_id: string;
  message: string;
  recommendations: AssistantRecommendationPayload[];
  packages: AssistantPackagePayload[];
  meta: AssistantReplyMeta;
}

function toItemType(value: string): AssistantItemType {
  return (ITEM_TYPES as readonly string[]).includes(value) ? (value as AssistantItemType) : 'business';
}

function toPriceUnit(value: string | null): AssistantPriceUnit | null {
  return value !== null && (PRICE_UNITS as readonly string[]).includes(value)
    ? (value as AssistantPriceUnit)
    : null;
}

function toAvailability(value: string): AssistantAvailability {
  return (AVAILABILITIES as readonly string[]).includes(value)
    ? (value as AssistantAvailability)
    : 'unknown';
}

/** Un servicio real recomendado por el asistente. */
export class AssistantRecommendation {
  constructor(
    public readonly type: AssistantItemType,
    public readonly id: string,
    public readonly name: string,
    public readonly summary: string,
    public readonly price: number,
    public readonly priceUnit: AssistantPriceUnit | null,
    public readonly rating: number,
    public readonly reviewCount: number,
    public readonly location: string,
    public readonly image: string,
    public readonly route: string[] | null,
    public readonly provider: string,
    public readonly availability: AssistantAvailability,
  ) {}

  static fromApi(raw: AssistantRecommendationPayload): AssistantRecommendation {
    return new AssistantRecommendation(
      toItemType(raw.type),
      raw.id,
      raw.name,
      raw.summary ?? '',
      raw.price ?? 0,
      toPriceUnit(raw.price_unit),
      raw.rating ?? 0,
      raw.review_count ?? 0,
      raw.location ?? '',
      raw.image ?? '',
      raw.route && raw.route.length > 0 ? raw.route : null,
      raw.provider ?? '',
      toAvailability(raw.availability),
    );
  }

  /** Clave estable para `@for` y para detectar repetidos. */
  get key(): string {
    return `${this.type}:${this.id}`;
  }

  get hasPrice(): boolean {
    return this.price > 0;
  }

  get isAvailable(): boolean {
    return this.availability === 'available';
  }

  get isUnavailable(): boolean {
    return this.availability === 'unavailable';
  }
}

/** Un plan (combinación de servicios) que cabe en el presupuesto. */
export class AssistantPackage {
  constructor(
    public readonly total: number,
    public readonly remaining: number,
    public readonly items: AssistantRecommendation[],
  ) {}

  static fromApi(raw: AssistantPackagePayload): AssistantPackage {
    return new AssistantPackage(
      raw.total ?? 0,
      raw.remaining ?? 0,
      (raw.items ?? []).map((item) => AssistantRecommendation.fromApi(item)),
    );
  }
}

/** Respuesta completa del asistente para un turno de conversación. */
export class AssistantReply {
  constructor(
    public readonly conversationId: string,
    public readonly message: string,
    public readonly recommendations: AssistantRecommendation[],
    public readonly packages: AssistantPackage[],
    public readonly meta: AssistantReplyMeta,
  ) {}

  static fromApi(raw: AssistantReplyPayload): AssistantReply {
    return new AssistantReply(
      raw.conversation_id,
      raw.message,
      (raw.recommendations ?? []).map((item) => AssistantRecommendation.fromApi(item)),
      (raw.packages ?? []).map((item) => AssistantPackage.fromApi(item)),
      {
        provider: raw.meta?.provider ?? '',
        fallback: raw.meta?.fallback ?? false,
        iterations: raw.meta?.iterations ?? 0,
      },
    );
  }
}
