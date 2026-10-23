export interface TouristNews {
  id: number;
  title: string;
  description: string;
  image: string;
  actionText?: string;
  /** Destino opcional del CTA del banner. */
  url?: string;
  active: boolean;
}
