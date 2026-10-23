import { SimpleChanges } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import { distinctUntilChanged, filter } from 'rxjs/operators';

/**
 * RouteParam
 *
 * Re-emits a route param every time the router rebinds it on a component that
 * is already alive.
 *
 * `withComponentInputBinding()` means a navigation that keeps the same route
 * config but changes the param (`/tour/a` -> `/tour/b`) does NOT recreate the
 * page: Angular reuses the instance and just reassigns the `@Input()`. A page
 * that reads `this.slug` once in `ngOnInit` therefore keeps rendering the
 * previous record, which is how a traveller ends up reading tour A while the
 * address bar says tour B.
 *
 * Detail pages pipe `stream$` through `switchMap` instead of reading the input
 * once. That covers the reuse case and also drops the in-flight request for the
 * previous param, so a slow response can no longer land after a newer one.
 *
 * Angular calls `ngOnChanges` before `ngOnInit`, so the first value is already
 * buffered by the time the page subscribes.
 */
export class RouteParam<T extends string> {
  private readonly subject = new BehaviorSubject<T | null>(null);

  /**
   * Feed this from `ngOnChanges`. Ignores repeats and empty values, so an
   * unrelated input change (or a param the router left blank) cannot trigger a
   * second load.
   */
  bind(changes: SimpleChanges, key: string, value: T | undefined): void {
    if (!changes[key] || !value || this.subject.value === value) {
      return;
    }
    this.subject.next(value);
  }

  /** The last value the router bound, if any. */
  get value(): T | null {
    return this.subject.value;
  }

  /** Every distinct non-empty value, starting with the current one. */
  get stream$(): Observable<T> {
    return this.subject.pipe(
      filter((value): value is T => value !== null),
      distinctUntilChanged(),
    );
  }
}
