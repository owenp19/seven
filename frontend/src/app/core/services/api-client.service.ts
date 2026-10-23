import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';

import { Observable, EMPTY, expand, map, reduce } from 'rxjs';

import type { ApiResponse } from '../../interfaces';
import { Paginated } from '../../interfaces';
import { APP_CONFIG } from '../tokens';
import type { AppConfig } from '../config/app-config.interface';
import { NativeService } from './native.service';

/**
 * ApiClientService
 *
 * Single entry point for HTTP calls to the SEVEN Laravel API.
 *
 * Responsibilities:
 *  - Compose the base URL (`apiUrl` + version from the app config).
 *  - Unwrap the standardized response envelope `{ success, message, data, ... }`,
 *    so feature services receive clean domain data.
 *  - Preserve pagination metadata through `getPaginated`.
 *  - Attach the device identity (`X-Install-Id`) on every request.
 *
 * Interceptors keep concerns separated: they add headers and normalize
 * HTTP errors before they reach application code.
 */
@Injectable({ providedIn: 'root' })
export class ApiClientService {
  private readonly http = inject(HttpClient);
  private readonly config = inject<AppConfig>(APP_CONFIG);
  private readonly native = inject(NativeService);

  private readonly baseUrl: string;

  constructor() {
    const config = this.config;

    this.baseUrl = `${config.apiUrl}/${config.apiVersion}`;
  }

  /** GET an item/collection and unwrap the `data` field. */
  get<T>(path: string, params?: Record<string, string | number | boolean>, token?: string): Observable<T> {
    return this.http
      .get<ApiResponse<T>>(`${this.baseUrl}/${path}`, {
        headers: this.headers(token),
        params: this.toHttpParams(params),
      })
      .pipe(map((response) => response.data as T));
  }

  /** GET a paginated collection and keep both items and pagination meta. */
  getPaginated<T>(path: string, params?: Record<string, string | number | boolean>): Observable<Paginated<T>> {
    return this.http
      .get<ApiResponse<T[]>>(`${this.baseUrl}/${path}`, {
        headers: this.headers(),
        params: this.toHttpParams(params),
      })
      .pipe(map((response) => new Paginated(response.data ?? [], response.meta)));
  }

  /**
   * GET every page of a paginated collection and concatenate the items.
   *
   * The catalog endpoints cap the page size at 50 (`max_per_page`), so a
   * plain `get()` against them quietly returned only the first page and the
   * caller cached that truncated list as if it were complete. Services that
   * genuinely need the whole collection use this instead.
   */
  getAllPages<T>(path: string, params?: Record<string, string | number | boolean>): Observable<T[]> {
    const perPage = this.config.maxPerPage;
    const page$ = (page: number) => this.getPaginated<T>(path, { ...params, page, per_page: perPage });

    return page$(1).pipe(
      expand((paginated) => (paginated.hasMore ? page$(paginated.currentPage + 1) : EMPTY)),
      reduce((items: T[], paginated) => [...items, ...paginated.items], [] as T[]),
    );
  }

  /** POST and unwrap the created entity. */
  post<T>(path: string, body: unknown, params?: Record<string, string | number | boolean>): Observable<T> {
    return this.http
      .post<ApiResponse<T>>(`${this.baseUrl}/${path}`, body, {
        headers: this.headers(),
        params: this.toHttpParams(params),
      })
      .pipe(map((response) => response.data as T));
  }

  /** PUT/PATCH an existing entity. */
  put<T>(path: string, body: unknown, token?: string): Observable<T> {
    return this.http
      .put<ApiResponse<T>>(`${this.baseUrl}/${path}`, body, { headers: this.headers(token) })
      .pipe(map((response) => response.data as T));
  }

  delete<T = void>(path: string, token?: string): Observable<T> {
    return this.http
      .delete<ApiResponse<T>>(`${this.baseUrl}/${path}`, { headers: this.headers(token) })
      .pipe(map((response) => response.data as T));
  }

  /** Build the full URL for a resource (used to share/WhatsApp links). */
  url(path: string): string {
    return `${this.baseUrl}/${path}`;
  }

  /**
   * Cabeceras base de la API anónima.
   *
   * `X-Install-Id` identifica al dispositivo y scopea las reservas (sin él el
   * backend responde 403 en los endpoints de reserva). Para leer, actualizar o
   * cancelar una reserva concreta se adjunta además `X-Booking-Token`, el
   * secreto que el backend entregó al crearla: es lo que demuestra que esta
   * instalación es dueña de esa reserva.
   */
  private headers(token?: string): HttpHeaders {
    let headers = new HttpHeaders({ 'X-Install-Id': this.native.installId() });

    if (token) {
      headers = headers.set('X-Booking-Token', token);
    }

    return headers;
  }

  private toHttpParams(params?: Record<string, string | number | boolean>): HttpParams {
    let httpParams = new HttpParams();
    for (const [key, value] of Object.entries(params ?? {})) {
      if (value !== undefined && value !== null && value !== '') {
        httpParams = httpParams.set(key, String(value));
      }
    }
    return httpParams;
  }
}