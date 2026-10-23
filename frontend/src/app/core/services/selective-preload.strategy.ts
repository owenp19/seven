import { Injectable } from '@angular/core';
import { PreloadingStrategy, Route } from '@angular/router';
import { Observable, of } from 'rxjs';

/**
 * SelectivePreloadStrategy
 *
 * Preloads ONLY the routes flagged with `data: { preload: true }` (the
 * tab shell and the most visited collections) so the critical UI is
 * instant, while detail pages and heavy routes stay as lazy chunks and
 * load on demand. Replaces `PreloadAllModules` (which downloaded every
 * chunk right after bootstrap).
 */
@Injectable({ providedIn: 'root' })
export class SelectivePreloadStrategy extends PreloadingStrategy {
  override preload(route: Route, load: (route: Route) => Observable<unknown>): Observable<unknown> {
    if (route.data?.['preload'] === true) {
      return load(route);
    }

    return of(null);
  }
}