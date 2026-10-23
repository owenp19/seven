import { Injectable, inject } from '@angular/core';

import { Observable, map } from 'rxjs';

import { News } from '../models';
import type { NewsPayload } from '../models/news.model';
import { ApiClientService } from '../core/services/api-client.service';
import { DataCacheService } from '../core/services/data-cache.service';

/**
 * Lectura del feed de noticias.
 *
 * Sustituye al montaje que hacía la página de Noticias mezclando banners y
 * promociones: ahora hay una entidad editorial propia y este servicio habla
 * solo con ella.
 */
@Injectable({ providedIn: 'root' })
export class NewsService {
  private readonly api = inject(ApiClientService);
  private readonly cache = inject(DataCacheService);

  /** Cuánto vive en caché el feed (ms). */
  private readonly cacheTtlMs = 5 * 60 * 1000;

  /** Noticias publicadas, en el orden en que las devuelve la API. */
  getAll(): Observable<News[]> {
    const key = 'news';
    const cached = this.cache.get<News[]>(key);
    if (cached) {
      return new Observable((subscriber) => {
        subscriber.next(cached);
        subscriber.complete();
      });
    }

    return this.api.get<NewsPayload[]>('news').pipe(
      map((items) => items.map((item) => News.fromApi(item))),
      map((items) => {
        this.cache.set(key, items, this.cacheTtlMs);
        return items;
      }),
    );
  }

  /** Una nota concreta por su slug, para poder abrirla desde un enlace. */
  getBySlug(slug: string): Observable<News> {
    return this.api
      .get<NewsPayload>(`news/${slug}`)
      .pipe(map((item) => News.fromApi(item)));
  }
}