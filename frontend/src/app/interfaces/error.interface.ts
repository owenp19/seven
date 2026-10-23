/**
 * Normalized application error produced by the error interceptor.
 * Consumed by pages and by the global error handler to show friendly
 * messages instead of raw network exceptions.
 */
export class ApiError extends Error {
  constructor(
    public readonly status: number,
    message: string,
    public readonly fields?: Record<string, string[]>,
  ) {
    super(message);
    this.name = 'ApiError';
  }
}