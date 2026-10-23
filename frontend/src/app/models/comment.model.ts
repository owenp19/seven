/** Payload of a public comment as returned by the API. */
export interface CommentPayload {
  id: number;
  /**
   * Free-form name: there is no login, so this is all the authorship there is.
   *
   * Typed as nullable because the honeypot response is built from whatever the
   * request contained: it is not worth widening every consumer's types to
   * defend against a payload the API never sends on a real comment.
   */
  author_name: string | null;
  body: string | null;
  /** 1–5 stars, optional. */
  rating: number | null;
  /**
   * True while the comment sits in moderation. The API never says *why*, so
   * the UI can only tell the traveller it is pending review.
   */
  pending: boolean;
  created_at: string | null;
}

const MINUTE = 60_000;
const HOUR = 60 * MINUTE;
const DAY = 24 * HOUR;

/**
 * A traveller's opinion about a tour, restaurant or inn.
 *
 * `relativeDate` is computed on the client instead of coming from the API
 * because a comment list is read once and then left on screen: "hace 3 días"
 * has to stay true while the traveller reads the rest of the detail page.
 */
export class Comment {
  constructor(
    public readonly id: number,
    public readonly authorName: string,
    public readonly body: string,
    public readonly rating: number | null,
    public readonly pending: boolean,
    private readonly createdAt: Date | null,
  ) {}

  /**
   * Coarse "time since publication", on purpose.
   *
   * Formatting an exact timestamp would need the clock and a locale here, and
   * `DatePipe` would drag in a dependency for one label. The unit stops at
   * days because nobody judges a review by the hour it was written.
   */
  get relativeDate(): string {
    if (!this.createdAt) {
      return '';
    }

    const elapsed = Math.max(0, Date.now() - this.createdAt.getTime());

    if (elapsed < HOUR) {
      return `${Math.max(1, Math.round(elapsed / MINUTE))}m`;
    }
    if (elapsed < DAY) {
      return `${Math.round(elapsed / HOUR)}h`;
    }
    if (elapsed < 30 * DAY) {
      return `${Math.round(elapsed / DAY)}d`;
    }

    return `${Math.round(elapsed / (30 * DAY))}mo`;
  }

  /** Initials for the avatar bubble, capped at two letters. */
  get initials(): string {
    const words = this.authorName.trim().split(/\s+/).filter(Boolean);
    const letters = words.slice(0, 2).map((word) => word.charAt(0).toUpperCase());

    return letters.length ? letters.join('') : '?';
  }

  static fromApi(raw: CommentPayload): Comment {
    const parsed = raw.created_at ? new Date(raw.created_at) : null;

    // The text is coerced at this boundary and nowhere else: it is the only
    // place where untyped JSON enters, and `initials` walks the author's name
    // assuming it is a string.
    return new Comment(
      raw.id,
      raw.author_name ?? '',
      raw.body ?? '',
      raw.rating ?? null,
      raw.pending === true,
      parsed && !Number.isNaN(parsed.getTime()) ? parsed : null,
    );
  }
}

/** Destinations that accept public comments. */
export type CommentTarget = 'tour' | 'restaurant' | 'inn';

/** Body of a new comment, as typed by the traveller. */
export interface NewComment {
  authorName: string;
  body: string;
  rating: number | null;
  /**
   * Honeypot: a field humans never see. It is only sent so the value the
   * component captured is the one the backend judges; callers that have no
   * hidden field can leave it out.
   */
  website?: string;
}