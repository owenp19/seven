/**
 * Búsqueda en cliente para los listados del catálogo.
 *
 * La API no expone búsqueda por texto, así que el filtro se aplica sobre el
 * feed ya cargado. La normalización quita acentos y mayúsculas para que
 * "Providencia" encuentre "providencia" y "José" encuentre "jose".
 */

/** Minúsculas sin acentos, para comparar de forma tolerante. */
export function normalizeText(value: string): string {
  return value
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim();
}

/** ¿Alguno de los campos contiene el texto buscado? */
export function matchesQuery(
  query: string,
  ...fields: (string | null | undefined)[]
): boolean {
  const needle = normalizeText(query);

  if (!needle) {
    return true;
  }

  return fields.some((field) => field && normalizeText(field).includes(needle));
}

/** Lee el valor de un input de búsqueda desde el evento nativo. */
export function inputValue(event: Event): string {
  return (event.target as HTMLInputElement | null)?.value ?? '';
}
