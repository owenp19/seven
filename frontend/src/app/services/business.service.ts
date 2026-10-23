import { Injectable, inject } from '@angular/core';

import { Observable, map } from 'rxjs';

import { Business } from '../models';
import { BusinessPayload } from '../models/business.model';
import { Paginated } from '../interfaces';
import { ApiClientService } from '../core/services/api-client.service';
import { DataCacheService } from '../core/services/data-cache.service';

/**
 * Read operations for businesses (restaurants, fast food, bakeries,
 * raizal cuisine, experiences).
 */
@Injectable({ providedIn: 'root' })
export class BusinessService {
  private readonly api = inject(ApiClientService);
  private readonly cache = inject(DataCacheService);

  /** How long (ms) cached read-only business data stays valid. */
  private readonly cacheTtlMs = 5 * 60 * 1000;

  /** Paginated, filterable listing. */
  list(params: { category?: string; q?: string; featured?: boolean; page?: number } = {}): Observable<Paginated<Business>> {
    return this.api
      .getPaginated<BusinessPayload>('businesses', {
        category: params.category ?? '',
        q: params.q ?? '',
        featured: params.featured ?? false,
        page: params.page ?? 1,
      })
      .pipe(map((paginated) => new Paginated(paginated.items.map((b) => Business.fromApi(b)), paginated.meta)));
  }

  /** Popular/featured collection shown on the Home (cached for the session). */
  getFeatured(): Observable<Business[]> {
    const key = 'businesses:featured';
    const cached = this.cache.get<Business[]>(key);
    if (cached) {
      return new Observable((subscriber) => {
        subscriber.next(cached);
        subscriber.complete();
      });
    }

    return this.list({ featured: true }).pipe(
      map((paginated) => paginated.items),
      map((items) => {
        this.cache.set(key, items, this.cacheTtlMs);
        return items;
      }),
    );
  }

  /** Single business by id (cached). */
  getById(id: number): Observable<Business> {
    const key = `business:${id}`;
    const cached = this.cache.get<Business>(key);
    if (cached) {
      return new Observable((subscriber) => {
        subscriber.next(cached);
        subscriber.complete();
      });
    }

    return this.api
      .get<BusinessPayload>(`businesses/${id}`)
      .pipe(map((b) => Business.fromApi(b)))
      .pipe(
        map((business) => {
          this.cache.set(key, business, this.cacheTtlMs);
          return business;
        }),
      );
  }

  /** Lightweight availability for a date (opening hours, taken slots). */
  checkAvailability(id: number, date: string): Observable<unknown> {
    return this.api.get(`businesses/${id}/availability`, { date });
  }
}