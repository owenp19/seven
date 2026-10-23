import { ChangeDetectionStrategy, ChangeDetectorRef, Component, Input, OnChanges, SimpleChanges, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { IonIcon, IonSkeletonText } from '@ionic/angular';

import { Observable, catchError, map, of } from 'rxjs';

import { CommentService } from '../../../services/comment.service';
import { ApiError } from '../../../interfaces';
import type { Comment, CommentTarget } from '../../../models';
import type { TranslationKey } from '../../../core/services/translation.service';
import { TranslatePipe } from '../../pipes/translate/translate.pipe';

/** Clave donde se recuerda el nombre firmado entre comentarios. */
const NAME_STORAGE_KEY = 'seven_comment_name';

/** Mismo mínimo que exige la API, para no gastar un envío en un 422. */
const BODY_MIN = 10;

/** Máximo de la API: evita escribir mil caracteres que el servidor va a cortar. */
const BODY_MAX = 1000;

const RATING_VALUES = [5, 4, 3, 2, 1] as const;

type SubmitPhase = 'idle' | 'sending' | 'done' | 'pending' | 'error';

/**
 * CommentsSection
 *
 * Opiniones de viajeros sobre un destino, con su formulario de alta.
 *
 * Decisiones que no son obvias:
 *
 * - El nombre se recuerda en `localStorage`: escribirlo en cada destino es la
 *   razón principal por la que la gente abandona un formulario a medio
 *   hacer. No es un perfil, solo una firma.
 *
 * - `pending` no es un error. El filtro de spam del backend manda a revisión
 *   lo sospechoso sin rechazar la petición, así que la respuesta llega 201 y
 *   el comentario se muestra con la etiqueta "en revisión" en lugar de
 *   desaparecer sin explicación.
 *
 * - Un 422 sobre el campo `body` no es un dato inválido cualquiera: quiere
 *   decir que este móvil ya escribió aquí. La reacción correcta es otra: no
 *   reintentar, sino avisar y cerrar el formulario.
 *
 * - La lista se pide sin caché y solo se recarga tras publicar: la página de
 *   detalle se reutiliza en el `IonRouterOutlet`, así que el componente
 *   cambia de slug sin recrearse y tiene que recargar por su cuenta.
 */
@Component({
  selector: 'app-comments-section',
  templateUrl: './comments-section.component.html',
  styleUrls: ['./comments-section.component.scss'],
  standalone: true,
  imports: [FormsModule, IonIcon, IonSkeletonText, TranslatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: { '[class.comments--flush]': 'flush' },
})
export class CommentsSectionComponent implements OnChanges {
  /** Vertical del catálogo: define el endpoint. */
  @Input({ required: true }) target: CommentTarget = 'tour';

  /** Slug del destino. Cambia al navegar sin recrear el componente. */
  @Input({ required: true }) slug = '';

  /** Cuando la sección cierra la página: sin padding inferior propio. */
  @Input() flush = false;

  readonly ratingValues = RATING_VALUES;

  comments: Comment[] = [];
  loading = true;
  loadFailed = false;

  authorName = '';
  body = '';
  rating: number | null = null;

  /** Trampa para robots. Visible solo para quien mire el DOM. */
  honeypot = '';

  phase: SubmitPhase = 'idle';
  /** Motivo del último fallo, como clave de traducción ya completa. */
  errorMessageKey: TranslationKey = 'comments.networkError';

  private readonly comments$ = inject(CommentService);
  private readonly changeDetector = inject(ChangeDetectorRef);

  get hasSubmitted(): boolean {
    return this.phase === 'done' || this.phase === 'pending';
  }

  get canSubmit(): boolean {
    return this.phase !== 'sending' && this.body.trim().length >= BODY_MIN;
  }

  get remainingChars(): number {
    return BODY_MAX - this.body.length;
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['slug'] && this.slug) {
      this.authorName = this.readStoredName();
      this.load();
    }
  }

  onBodyInput(): void {
    if (this.body.length > BODY_MAX) {
      this.body = this.body.slice(0, BODY_MAX);
    }
  }

  toggleRating(value: number): void {
    // Volver a tocar la misma estrella quita la valoración: es la única forma
    // de deshacerla sin un botón aparte que solo ocuparía espacio.
    this.rating = this.rating === value ? null : value;
  }

  submit(): void {
    if (this.phase === 'sending') {
      return;
    }

    const authorName = this.authorName.trim();

    if (!authorName) {
      this.fail('comments.needName');
      return;
    }

    if (this.body.trim().length < BODY_MIN) {
      this.fail('comments.tooShort');
      return;
    }

    this.phase = 'sending';
    this.errorMessageKey = 'comments.networkError';

    const honeypot = this.honeypot;
    this.honeypot = '';

    this.comments$
      .create(this.target, this.slug, {
        authorName,
        body: this.body,
        rating: this.rating,
        website: honeypot,
      })
      .subscribe({
        next: (comment) => {
          this.persistName(authorName);
          this.body = '';
          this.rating = null;

          // El recién enviado se muestra siempre, salga publicado o a revisión:
          // si desaparece sin más, el viajero no sabe si le llegó a escribir.
          this.comments = [comment, ...this.comments];
          this.phase = comment.pending ? 'pending' : 'done';
          this.changeDetector.markForCheck();
        },
        error: (error: unknown) => {
          const apiError = error as ApiError;

          if (apiError.status === 422) {
            this.fail('comments.alreadyDone');
          } else if (apiError.status === 429) {
            this.fail('comments.rateLimit');
          } else {
            this.fail('comments.networkError');
          }
        },
      });
  }

  private load(): void {
    this.loading = true;
    this.loadFailed = false;
    this.comments = [];
    this.phase = 'idle';
    this.honeypot = '';

    this.loadComments().subscribe();
  }

  private loadComments(): Observable<Comment[]> {
    return this.comments$.list(this.target, this.slug).pipe(
      map((comments) => {
        this.comments = comments;
        this.loading = false;
        this.changeDetector.markForCheck();

        return comments;
      }),
      catchError(() => {
        // La lista vacía no es un error de red: la sección se oculta y el
        // formulario sigue disponible para publicar.
        this.comments = [];
        this.loading = false;
        this.loadFailed = true;
        this.changeDetector.markForCheck();

        return of<Comment[]>([]);
      }),
    );
  }

  private fail(key: TranslationKey): void {
    this.errorMessageKey = key;
    this.phase = 'error';
    this.changeDetector.markForCheck();
  }

  private readStoredName(): string {
    try {
      return localStorage.getItem(NAME_STORAGE_KEY) ?? '';
    } catch {
      // Modo privado o almacenamiento bloqueado: se pide el nombre cada vez.
      return '';
    }
  }

  private persistName(name: string): void {
    try {
      localStorage.setItem(NAME_STORAGE_KEY, name);
    } catch {
      // No es crítico: perder la firma solo significa volver a escribirla.
    }
  }
}