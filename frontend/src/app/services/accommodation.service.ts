import { Injectable, inject } from '@angular/core';

import { Observable, map } from 'rxjs';

import { Accommodation } from '../models';
import { AccommodationPayload } from '../models/accommodation.model';
import { Paginated } from '../interfaces';
import { ApiClientService } from '../core/services/api-client.service';

/**
 * Read operations for accommodations (posadas nativas, lodging).
 */
@Injectable({ providedIn: 'root' })
export class AccommodationService {
  private readonly api = inject(ApiClientService);


  /** Paginated listing, optionally filtered by capacity. */
  list(params: { guests?: number; q?: string; page?: number } = {}): Observable<Paginated<Accommodation>> {
    return this.api
      .getPaginated<AccommodationPayload>('accommodations', {
        guests: params.guests ?? '',
        q: params.q ?? '',
        page: params.page ?? 1,
      })
      .pipe(map((paginated) => new Paginated(paginated.items.map((a) => Accommodation.fromApi(a)), paginated.meta)));
  }

  /** Single accommodation by id. */
  getById(id: number): Observable<Accommodation> {
    return this.api.get<AccommodationPayload>(`accommodations/${id}`).pipe(map((a) => Accommodation.fromApi(a)));
  }

  checkAvailability(id: number, date: string): Observable<unknown> {
    return this.api.get(`accommodations/${id}/availability`, { date });
  }
}