/**
 * Utilidades de imagen compartidas por los carruseles y las portadas.
 */

/**
 * Normaliza una lista de URLs para usarla como slides.
 *
 * Descarta `null`/`undefined`, recorta espacios, quita las entradas que
 * quedan en blanco y elimina duplicados **conservando el orden original**:
 * la portada siempre debe ser la primera foto.
 *
 *   mediaSources('a.jpg', '', 'b.jpg', 'a.jpg') // ['a.jpg', 'b.jpg']
 */
export function mediaSources(...sources: (string | null | undefined)[]): string[] {
  const cleaned = sources
    .map((source) => (typeof source === 'string' ? source.trim() : ''))
    .filter((source) => source.length > 0);

  return [...new Set(cleaned)];
}
