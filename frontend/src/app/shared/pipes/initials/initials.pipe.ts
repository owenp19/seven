import { Pipe, PipeTransform } from '@angular/core';

import { initials } from '../../../utils/formatters';

/**
 * Derives avatar initials from a full name.
 *   {{ customer.name | initials }}
 */
@Pipe({ name: 'initials', standalone: true })
export class InitialsPipe implements PipeTransform {
  transform(name: string | null | undefined): string {
    return initials(name);
  }
}