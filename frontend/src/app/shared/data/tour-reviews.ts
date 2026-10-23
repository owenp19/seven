/**
 * Opiniones de viajeros (UI).
 *
 * Temporal: conjunto editorial de reseñas de muestra mientras el backend
 * no expone `GET /tours/{slug}/reviews`. Cuando la API llegue, este
 * archivo se reemplaza por el servicio correspondiente sin tocar la UI.
 */

export interface TourReview {
  author: string;
  initials: string;
  rating: number;
  date: string;
  text: string;
  helpful: number;
}

const REVIEW_POOL: Omit<TourReview, 'initials'>[] = [
  {
    author: 'Mariana G.',
    rating: 5,
    date: 'Hace 2 semanas',
    text: 'Increíble de principio a fin. El guía local conocía cada rincón y nos contó la historia raizal mientras cruzábamos el mar de siete colores. Foto a la altura de la expectativa.',
    helpful: 14,
  },
  {
    author: 'Juan P.',
    rating: 5,
    date: 'Hace 3 semanas',
    text: 'Salida puntual, chalecos en buen estado y agua cristalina. Superó el precio, vale cada peso. Recomiendo llevar bloqueador biodegradable.',
    helpful: 9,
  },
  {
    author: 'Camila R.',
    rating: 4,
    date: 'Hace 1 mes',
    text: 'Muy lindo recorrido y transporte cómodo. El único detalle es que el punto de salida cambió de muelle unos días antes; por WhatsApp nos avisaron a tiempo.',
    helpful: 6,
  },
  {
    author: 'David T.',
    rating: 5,
    date: 'Hace 1 mes',
    text: 'Vecinos de la isla nos recomendaron a este operador y no fallaron. Ambiente familiar, buen almuerzo y música en la chiva. Volvería.',
    helpful: 11,
  },
  {
    author: 'Valentina M.',
    rating: 5,
    date: 'Hace 2 meses',
    text: 'El mejor momento del viaje. Los niños se la pasaron genial en el cayo y el guía fue súper paciente con el grupo.',
    helpful: 7,
  },
];

/** Reseñas pseudo-deterministas para una página (estables por tour). */
export function sampleReviews(tourId: number): TourReview[] {
  const base = tourId % REVIEW_POOL.length;
  return [0, 1, 2].map((offset) => {
    const review = REVIEW_POOL[(base + offset) % REVIEW_POOL.length];
    return {
      ...review,
      initials: review.author
        .replace(/\.$/, '')
        .split(' ')
        .map((part) => part[0])
        .join('')
        .toUpperCase(),
    };
  });
}