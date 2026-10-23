import { Pipe, PipeTransform } from '@angular/core';

import { formatCurrency } from '../../../utils/formatters';

/**
 * Renders a number as local currency (COP by default).
 *   {{ price_per_night | currencyCOP }}
 */
@Pipe({ name: 'currencyCOP', standalone: true })
export class CurrencyPipe implements PipeTransform {
  transform(value: number | null | undefined, currency = 'COP'): string {
    return formatCurrency(value ?? 0, currency);
  }
}