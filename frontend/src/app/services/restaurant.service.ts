import { Injectable, inject } from '@angular/core';

import { Observable, map } from 'rxjs';

import { Restaurant } from '../models';
import { RestaurantPayload } from '../models/restaurant.model';
import { ApiClientService } from '../core/services/api-client.service';
import { DataCacheService } from '../core/services/data-cache.service';

/**
 * Read operations for restaurants (gastronomía de la isla).
 */
@Injectable({ providedIn: 'root' })
export class RestaurantService {
  private readonly api = inject(ApiClientService);
  private readonly cache = inject(DataCacheService);

  /** How long (ms) cached read-only restaurant data stays valid. */
  private readonly cacheTtlMs = 5 * 60 * 1000;

  /** All active restaurants. */
  getAll(): Observable<Restaurant[]> {
    const key = 'restaurants';
    const cached = this.cache.get<Restaurant[]>(key);
    if (cached) {
      return new Observable((subscriber) => {
        subscriber.next(cached);
        subscriber.complete();
      });
    }

    return this.api.getAllPages<RestaurantPayload>('restaurants').pipe(
      map((items) => items.map((item) => Restaurant.fromApi(item))),
      map((items) => {
        this.cache.set(key, items, this.cacheTtlMs);
        return items;
      }),
    );
  }

  /** Single restaurant by slug (null when it does not exist). */
  getBySlug(slug: string): Observable<Restaurant | null> {
    const key = `restaurant:${slug}`;
    const cached = this.cache.get<Restaurant>(key);
    if (cached) {
      return new Observable((subscriber) => {
        subscriber.next(cached);
        subscriber.complete();
      });
    }

    return this.api
      .get<RestaurantPayload>(`restaurants/${slug}`)
      .pipe(map((item) => Restaurant.fromApi(item)))
      .pipe(
        map((item) => {
          this.cache.set(key, item, this.cacheTtlMs);
          return item;
        }),
      );
  }

  /** Featured restaurants shown on the Home (top rated). */
  getFeatured(): Observable<Restaurant[]> {
    const key = 'restaurants:featured';
    const cached = this.cache.get<Restaurant[]>(key);
    if (cached) {
      return new Observable((subscriber) => {
        subscriber.next(cached);
        subscriber.complete();
      });
    }

    return this.api.get<RestaurantPayload[]>('restaurants', { per_page: 3 }).pipe(
      map((items) => items.map((item) => Restaurant.fromApi(item))),
      map((items) => {
        this.cache.set(key, items, this.cacheTtlMs);
        return items;
      }),
    );
  }
}