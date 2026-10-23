import { Injectable, inject } from '@angular/core';

import { Observable, map } from 'rxjs';

import { AssistantReply, AssistantReplyPayload } from '../models/assistant.model';
import { ApiClientService } from '../core/services/api-client.service';

/**
 * AssistantService
 *
 * Envía un mensaje al asistente turístico y devuelve su respuesta.
 *
 * El `X-Install-Id` lo adjunta `ApiClientService` en todas las peticiones, así
 * que el backend resuelve la identidad del dispositivo por cabecera (el
 * `install_id` del cuerpo es solo un respaldo). `conversation_id` continúa una
 * conversación existente; sin él, el backend abre una nueva y la devuelve.
 */
@Injectable({ providedIn: 'root' })
export class AssistantService {
  private readonly api = inject(ApiClientService);

  send(message: string, conversationId?: string | null): Observable<AssistantReply> {
    return this.api
      .post<AssistantReplyPayload>('assistant/messages', {
        message,
        conversation_id: conversationId ?? null,
      })
      .pipe(map((payload) => AssistantReply.fromApi(payload)));
  }
}
