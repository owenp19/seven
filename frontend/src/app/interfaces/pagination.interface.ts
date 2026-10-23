import type { ApiPagination } from './api-response.interface';

/**
 * Wraps a paginated server response: the items plus the pagination meta
 * needed to build infinite scroll / "load more" lists.
 */
export class Paginated<T> {
  constructor(
    public readonly items: T[],
    public readonly meta: ApiPagination | null = null,
  ) {}

  get total(): number {
    return this.meta?.pagination.total ?? this.items.length;
  }

  get currentPage(): number {
    return this.meta?.pagination.current_page ?? 1;
  }

  get lastPage(): number {
    return this.meta?.pagination.last_page ?? 1;
  }

  get hasMore(): boolean {
    return this.currentPage < this.lastPage;
  }
}