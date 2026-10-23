import { Injectable, inject } from '@angular/core';

import { Observable, map } from 'rxjs';

import { Tour } from '../models';
import { TourPayload } from '../models/tour.model';
import { ApiClientService } from '../core/services/api-client.service';
import { DataCacheService } from '../core/services/data-cache.service';

/**
 * Read operations for tours (experiencias turísticas).
 *
 * Consume el endpoint `GET /api/v1/tours` (+ `/tours/{slug}`) de la API,
 * con paginación y caché en sesión para los listados estables.
 */
@Injectable({ providedIn: 'root' })
export class TourService {
  private readonly api = inject(ApiClientService);
  private readonly cache = inject(DataCacheService);

  /** How long (ms) cached read-only tour data stays valid. */
  private readonly cacheTtlMs = 5 * 60 * 1000;

  /** Full catalog of available tours. */
  getAll(): Observable<Tour[]> {
    const key = 'tours';
    const cached = this.cache.get<Tour[]>(key);
    if (cached) {
      return new Observable((subscriber) => {
        subscriber.next(cached);
        subscriber.complete();
      });
    }

    return this.api.getAllPages<TourPayload>('tours').pipe(
      map((items) => items.map((item) => Tour.fromApi(item))),
      map((items) => {
        this.cache.set(key, items, this.cacheTtlMs);
        return items;
      }),
    );
  }

  /** Single tour by slug (null when it does not exist). */
  getBySlug(slug: string): Observable<Tour | null> {
    const key = `tour:${slug}`;
    const cached = this.cache.get<Tour>(key);
    if (cached) {
      return new Observable((subscriber) => {
        subscriber.next(cached);
        subscriber.complete();
      });
    }

    return this.api.get<TourPayload>(`tours/${slug}`).pipe(map((item) => Tour.fromApi(item))).pipe(
      map((tour) => {
        this.cache.set(key, tour, this.cacheTtlMs);
        return tour;
      }),
    );
  }

  /** Featured tours shown on the Home (top rated). */
  getFeatured(): Observable<Tour[]> {
    const key = 'tours:featured';
    const cached = this.cache.get<Tour[]>(key);
    if (cached) {
      return new Observable((subscriber) => {
        subscriber.next(cached);
        subscriber.complete();
      });
    }

    return this.api.get<TourPayload[]>('tours', { per_page: 3 }).pipe(
      map((items) => items.map((item) => Tour.fromApi(item))),
      map((items) => {
        this.cache.set(key, items, this.cacheTtlMs);
        return items;
      }),
    );
  }
}