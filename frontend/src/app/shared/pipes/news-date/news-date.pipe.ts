import { Pipe, PipeTransform } from '@angular/core';

import { formatNewsDate } from '../../../utils/formatters';
import type { Language } from '../../../core/services/translation.service';

/**
 * Fecha editorial del feed de Noticias: "hace 2 h", "Ayer", "3 oct".
 *
 * Recibe el idioma como argumento en vez de inyectar el servicio para seguir
 * siendo un pipe puro: cuando la señal de idioma cambia, el argumento cambia y
 * Angular recalcula; el resto del tiempo se memoiza.
 */
@Pipe({ name: 'newsDate', standalone: true })
export class NewsDatePipe implements PipeTransform {
  transform(value: string | null | undefined, language: Language = 'es'): string {
    return formatNewsDate(value, language);
  }
}
