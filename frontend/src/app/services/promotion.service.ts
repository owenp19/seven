import { Injectable, inject } from '@angular/core';

import { Observable, map } from 'rxjs';

import { Promotion } from '../models';
import { PromotionPayload } from '../models/promotion.model';
import { ApiClientService } from '../core/services/api-client.service';
import { DataCacheService } from '../core/services/data-cache.service';

/**
 * Read operations for promotions (ofertas de temporada).
 */
@Injectable({ providedIn: 'root' })
export class PromotionService {
  private readonly api = inject(ApiClientService);
  private readonly cache = inject(DataCacheService);

  /** How long (ms) cached promotion data stays valid. */
  private readonly cacheTtlMs = 5 * 60 * 1000;

  /** Active promotions ordered for display. */
  getAll(): Observable<Promotion[]> {
    const key = 'promotions';
    const cached = this.cache.get<Promotion[]>(key);
    if (cached) {
      return new Observable((subscriber) => {
        subscriber.next(cached);
        subscriber.complete();
      });
    }

    return this.api.get<PromotionPayload[]>('promotions').pipe(
      map((items) => items.map((item) => Promotion.fromApi(item))),
      map((items) => {
        this.cache.set(key, items, this.cacheTtlMs);
        return items;
      }),
    );
  }
}