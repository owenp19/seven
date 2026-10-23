import { Injectable, inject } from '@angular/core';

import { Observable, map } from 'rxjs';

import { Banner } from '../models';
import { BannerPayload } from '../models/banner.model';
import { ApiClientService } from '../core/services/api-client.service';
import { DataCacheService } from '../core/services/data-cache.service';

/**
 * Read operations for banners (hero carousel de la Home).
 */
@Injectable({ providedIn: 'root' })
export class BannerService {
  private readonly api = inject(ApiClientService);
  private readonly cache = inject(DataCacheService);

  /** How long (ms) cached banner data stays valid. */
  private readonly cacheTtlMs = 5 * 60 * 1000;

  /** Active banners ordered for display. */
  getAll(): Observable<Banner[]> {
    const key = 'banners';
    const cached = this.cache.get<Banner[]>(key);
    if (cached) {
      return new Observable((subscriber) => {
        subscriber.next(cached);
        subscriber.complete();
      });
    }

    return this.api.get<BannerPayload[]>('banners').pipe(
      map((items) => items.map((item) => Banner.fromApi(item))),
      map((items) => {
        this.cache.set(key, items, this.cacheTtlMs);
        return items;
      }),
    );
  }
}