import { ApiError } from './error.interface';

/**
 * Lifecycle of an async read that the UI has to render honestly.
 *
 * A bare `catchError(() => of([]))` collapses "the request failed" into
 * "there is nothing here", so the user is told they have no results when the
 * truth is that the API is unreachable. Pages emit this instead and render a
 * retry affordance for the `error` case.
 */
export type AsyncState<T> =
  | { status: 'loading' }
  | { status: 'ready'; data: T }
  | { status: 'error'; message: string };

/** Narrowing helper for templates and `switch` statements. */
export function isReady<T>(state: AsyncState<T>): state is { status: 'ready'; data: T } {
  return state.status === 'ready';
}

/** Narrowing helper for templates and `switch` statements. */
export function hasFailed<T>(state: AsyncState<T>): state is { status: 'error'; message: string } {
  return state.status === 'error';
}

/**
 * Turn a failed request into a message worth showing a traveller.
 *
 * The interceptor already normalizes transport errors into `ApiError`; this
 * only decides which text the user reads, and avoids leaking raw status
 * codes or stack-ish text into the UI.
 */
export function describeApiError(error: unknown): string {
  if (error instanceof ApiError) {
    switch (error.status) {
      case 0:
        return 'Sin conexión con el servidor. Revisa tu internet e inténtalo de nuevo.';
      case 404:
        return 'No encontramos esa información.';
      case 422:
        return error.message || 'Revisa los datos e inténtalo de nuevo.';
      case 500:
      case 502:
      case 503:
        return 'El servidor está teniendo problemas. Inténtalo en unos minutos.';
      default:
        return error.message || 'No pudimos cargar la información.';
    }
  }

  return 'No pudimos cargar la información.';
}
