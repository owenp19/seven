/**
 * Standard SEVEN API response envelope.
 * Every endpoint returns this shape (see backend `app/Helpers/helpers.php`).
 */
export interface ApiResponse<T = unknown> {
  success: boolean;
  message: string;
  data: T | null;
  errors?: Record<string, string[]> | null;
  meta?: ApiPagination | null;
}

/** Pagination block sent by Laravel paginators. */
export interface ApiPagination {
  pagination: PaginationMeta;
}

export interface PaginationMeta {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
}

/** Geo coordinates attached to businesses and accommodations. */
export interface GeoPoint {
  latitude: string | null;
  longitude: string | null;
}

/** Shape of the `opening_hours` JSON column: monday..sunday. */
export interface OpeningHours {
  [day: string]: {
    open: string;
    close: string;
  } | null;
}

/**
 * Error payload thrown by the error interceptor after a failed request.
 */
export interface ApiErrorPayload {
  status: number;
  message: string;
  fields?: Record<string, string[]>;
}