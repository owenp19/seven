import { Injectable } from '@angular/core';

import { BehaviorSubject, Observable, map } from 'rxjs';

/**
 * LoadingService
 *
 * Tracks the number of in-flight HTTP requests so UI can react to
 * global "loading" states (e.g. showing a progress indicator while
 * critical calls finish). Implemented as an RxJS stream for consistency
 * with the rest of the app.
 */
@Injectable({ providedIn: 'root' })
export class LoadingService {
  private readonly pending$ = new BehaviorSubject<number>(0);

  readonly active$: Observable<boolean> = this.pending$.pipe(map((count) => count > 0));

  increment(): void {
    this.pending$.next(this.pending$.value + 1);
  }

  decrement(): void {
    this.pending$.next(Math.max(0, this.pending$.value - 1));
  }
}
