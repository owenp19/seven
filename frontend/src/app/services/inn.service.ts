import { Injectable, inject } from '@angular/core';

import { Observable, map } from 'rxjs';

import { Inn } from '../models';
import { InnPayload } from '../models/inn.model';
import { ApiClientService } from '../core/services/api-client.service';
import { DataCacheService } from '../core/services/data-cache.service';

/**
 * Read operations for inns (posadas nativas).
 */
@Injectable({ providedIn: 'root' })
export class InnService {
  private readonly api = inject(ApiClientService);
  private readonly cache = inject(DataCacheService);

  /** How long (ms) cached read-only inn data stays valid. */
  private readonly cacheTtlMs = 5 * 60 * 1000;

  /** All active inns. */
  getAll(): Observable<Inn[]> {
    const key = 'inns';
    const cached = this.cache.get<Inn[]>(key);
    if (cached) {
      return new Observable((subscriber) => {
        subscriber.next(cached);
        subscriber.complete();
      });
    }

    return this.api.getAllPages<InnPayload>('inns').pipe(
      map((items) => items.map((item) => Inn.fromApi(item))),
      map((items) => {
        this.cache.set(key, items, this.cacheTtlMs);
        return items;
      }),
    );
  }

  /** Single inn by slug (null when it does not exist). */
  getBySlug(slug: string): Observable<Inn | null> {
    const key = `inn:${slug}`;
    const cached = this.cache.get<Inn>(key);
    if (cached) {
      return new Observable((subscriber) => {
        subscriber.next(cached);
        subscriber.complete();
      });
    }

    return this.api.get<InnPayload>(`inns/${slug}`).pipe(map((item) => Inn.fromApi(item))).pipe(
      map((item) => {
        this.cache.set(key, item, this.cacheTtlMs);
        return item;
      }),
    );
  }

  /** Featured inns shown on the Home (top rated). */
  getFeatured(): Observable<Inn[]> {
    const key = 'inns:featured';
    const cached = this.cache.get<Inn[]>(key);
    if (cached) {
      return new Observable((subscriber) => {
        subscriber.next(cached);
        subscriber.complete();
      });
    }

    return this.api.get<InnPayload[]>('inns', { per_page: 3 }).pipe(
      map((items) => items.map((item) => Inn.fromApi(item))),
      map((items) => {
        this.cache.set(key, items, this.cacheTtlMs);
        return items;
      }),
    );
  }
}