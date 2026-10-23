import type { ProviderPayload } from './provider.model';

/**
 * SEVEN News — nota editorial de la isla (noticia, evento, consejo o
 * promoción). Es contenido redactado por el equipo, no una fila del catálogo:
 * por eso tiene `published_at` y no un `status` de aprobación.
 */

export type NewsKind = 'news' | 'event' | 'tip' | 'promotion';

export interface NewsPayload {
  id: number;
  title: string;
  slug: string;
  excerpt: string | null;
  body: string | null;
  image: string | null;
  kind: string;
  link_url: string | null;
  status: string;
  is_featured: boolean;
  published_at: string | null;
  provider?: ProviderPayload | null;
}

export class News {
  constructor(
    public readonly id: number,
    public readonly title: string,
    public readonly slug: string,
    public readonly excerpt: string,
    public readonly body: string,
    public readonly image: string,
    /** Etiqueta editorial; cualquier valor inesperado cae en `news`. */
    public readonly kind: NewsKind,
    public readonly linkUrl: string,
    public readonly isFeatured: boolean,
    public readonly publishedAt: string,
    /** Operador u autor; cadena vacía cuando la nota no lo tiene. */
    public readonly providerName: string,
    public readonly providerPhone: string,
  ) {}

  static fromApi(raw: NewsPayload): News {
    return new News(
      raw.id,
      raw.title,
      raw.slug,
      raw.excerpt ?? '',
      raw.body ?? '',
      raw.image ?? '',
      toNewsKind(raw.kind),
      raw.link_url ?? '',
      Boolean(raw.is_featured),
      raw.published_at ?? '',
      raw.provider?.business_name ?? '',
      raw.provider?.phone ?? '',
    );
  }
}

const KINDS: readonly NewsKind[] = ['news', 'event', 'tip', 'promotion'];

/**
 * Normaliza el `kind` que llega del backend.
 *
 * La API solo devuelve valores del enum, pero un news viejo o un seed
 * editado a mano pueden traer otra cosa. Sin esta guarda, ese valor se
 * colaría en la clase del badge de la tarjeta y el estilo no aplicaría,
 * dejando la nota sin etiqueta en vez de con la de por defecto.
 */
function toNewsKind(raw: string): NewsKind {
  return KINDS.includes(raw as NewsKind) ? (raw as NewsKind) : 'news';
}