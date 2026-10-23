/**
 * SEVEN Banner — contenido destacado del Home (hero carousel).
 *
 * Los banners son 100% administrables desde la API y alimentan el
 * TouristNewsCarousel de la home.
 */

export interface BannerPayload {
  id: number;
  title: string;
  subtitle: string | null;
  description: string | null;
  image: string | null;
  button_text: string | null;
  button_url: string | null;
  status: string;
  order: number;
  start_date: string | null;
  end_date: string | null;
}

export class Banner {
  constructor(
    public readonly id: number,
    public readonly title: string,
    public readonly subtitle: string,
    public readonly description: string,
    public readonly image: string,
    public readonly buttonText: string,
    public readonly buttonUrl: string,
    public readonly order: number,
  ) {}

  static fromApi(raw: BannerPayload): Banner {
    return new Banner(
      raw.id,
      raw.title,
      raw.subtitle ?? '',
      raw.description ?? '',
      raw.image ?? '',
      raw.button_text ?? '',
      raw.button_url ?? '',
      raw.order ?? 0,
    );
  }
}