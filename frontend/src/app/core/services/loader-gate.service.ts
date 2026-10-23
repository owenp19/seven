import { Injectable } from '@angular/core';

/**
 * LoaderGateService
 *
 * The global loader (see `AppComponent.watchRouteTransitions`) covers the
 * screen on every navigation so the user never sees a half-painted page.
 *
 * Some transitions are better without it: the Welcome page owns a
 * full-bleed background photo plus its own exit animation, so raising the
 * opaque loader on top of it flashes a flat navy rectangle with the logo
 * right before Home, hiding the very image the transition is built around.
 *
 * A page that is about to navigate calls `skipNext()` once; the app
 * component consumes the flag on the following `NavigationStart`, so the
 * outgoing page keeps painting (background visible) until the incoming
 * one is ready to take over.
 */
@Injectable({ providedIn: 'root' })
export class LoaderGateService {
  private pendingSkips = 0;

  /** Hide the global loader for the next navigation only. */
  skipNext(): void {
    this.pendingSkips += 1;
  }

  /**
   * Consumes a pending skip.
   *
   * @returns `true` when the caller must NOT show the global loader.
   */
  consume(): boolean {
    if (this.pendingSkips < 1) {
      return false;
    }

    this.pendingSkips -= 1;
    return true;
  }
}
