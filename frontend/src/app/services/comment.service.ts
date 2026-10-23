import { Injectable, inject } from '@angular/core';

import { Observable, map } from 'rxjs';

import { Comment } from '../models';
import type { CommentPayload, CommentTarget, NewComment } from '../models/comment.model';
import { ApiClientService } from '../core/services/api-client.service';

/**
 * Los endpoints son por vertical y no genéricos a propósito: el backend
 * resuelve cada slug con su propio modelo, así que un tour no aprobado no
 * tiene comentarios y su URL devuelve 404 en lugar de una lista vacía.
 */
const PATH_SEGMENT: Record<CommentTarget, string> = {
  tour: 'tours',
  restaurant: 'restaurants',
  inn: 'inns',
};

/**
 * CommentService
 *
 * Opiniones de viajeros sobre un destino, una por dispositivo.
 *
 * La identidad viaja sola: `ApiClientService` añade `X-Install-Id` a cada
 * llamada, y el backend rechaza la publicación sin él. No hace falta mandar el
 * identificador en el cuerpo, igual que hace `BookingService`, y así no queda
 * en el payload de red.
 *
 * No lleva caché: los comentarios son lo que más cambia de una visita a otra,
 * y un `DataCacheService` de cinco minutos devolvería la lista ya leída
 * justo después de publicar.
 */
@Injectable({ providedIn: 'root' })
export class CommentService {
  private readonly api = inject(ApiClientService);

  /** Published comments of a destination, newest first. */
  list(target: CommentTarget, slug: string): Observable<Comment[]> {
    return this.api
      .get<CommentPayload[]>(`${PATH_SEGMENT[target]}/${slug}/comments`)
      .pipe(map((payloads) => (payloads ?? []).map((payload) => Comment.fromApi(payload))));
  }

  /**
   * Publish a comment from this device.
   *
   * The API owns the spam filter, so a comment that tripped it comes back
   * with `pending: true` rather than as an error: the traveller did nothing
   * wrong and must not be told they were rejected.
   */
  create(target: CommentTarget, slug: string, input: NewComment): Observable<Comment> {
    return this.api
      .post<CommentPayload>(`${PATH_SEGMENT[target]}/${slug}/comments`, {
        author_name: input.authorName.trim(),
        body: input.body.trim(),
        rating: input.rating,
        // Campo trampa: vacío para las personas, tentador para los robots.
        // El backend responde 201 con un comentario falso en vez de guardar.
        website: input.website ?? '',
      })
      .pipe(map((payload) => Comment.fromApi(payload)));
  }
}