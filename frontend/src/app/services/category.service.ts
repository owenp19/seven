import { Injectable, inject } from '@angular/core';

import { Observable, map } from 'rxjs';

import { Category} from '../models';
import { CategoryPayload } from '../models/category.model';
import { ApiClientService } from '../core/services/api-client.service';
import { DataCacheService } from '../core/services/data-cache.service';

/**
 * Read operations for categories.
 */
@Injectable({ providedIn: 'root' })
export class CategoryService {
  private readonly api = inject(ApiClientService);
  private readonly cache = inject(DataCacheService);

  /** How long (ms) categories stay cached before a fresh fetch. */
  private readonly cacheTtlMs = 5 * 60 * 1000;

  /** Active categories with their business count, ordered for display. */
  getAll(): Observable<Category[]> {
    const cached = this.cache.get<Category[]>('categories');
    if (cached) {
      return new Observable((subscriber) => {
        subscriber.next(cached);
        subscriber.complete();
      });
    }

    return this.api.get<CategoryPayload[]>('categories').pipe(
      map((items) => items.map((item) => Category.fromApi(item))),
      map((items) => {
        this.cache.set('categories', items, this.cacheTtlMs);
        return items;
      }),
    );
  }

  /** Single category by slug (curl en las categorías de la home). */
  getBySlug(slug: string): Observable<Category> {
    const key = `category:${slug}`;
    const cached = this.cache.get<Category>(key);
    if (cached) {
      return new Observable((subscriber) => {
        subscriber.next(cached);
        subscriber.complete();
      });
    }

    return this.api
      .get<CategoryPayload>(`categories/${slug}`)
      .pipe(map((item) => Category.fromApi(item)))
      .pipe(
        map((item) => {
          this.cache.set(key, item, this.cacheTtlMs);
          return item;
        }),
      );
  }
}