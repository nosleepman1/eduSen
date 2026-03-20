/**
 * Shape of every response produced by App\Support\ApiResponse on the server.
 */
export interface ApiEnvelope<T> {
  success: boolean;
  message: string;
  data: T;
  meta?: PaginationMeta;
}

/**
 * Emitted by ApiResponse only when the payload is paginated. `total` and
 * `last_page` come from a length-aware paginator; `has_more_pages` replaces them
 * when the server used simplePaginate() and cannot know the total.
 */
export interface PaginationMeta {
  current_page: number;
  per_page: number;
  from: number | null;
  to: number | null;
  total?: number;
  last_page?: number;
  has_more_pages?: boolean;
}

export interface PaginatedResult<T> {
  items: T[];
  meta: PaginationMeta | null;
}

/**
 * Total page count, or null when the server cannot tell us.
 * Never assume a last page exists — simplePaginate() endpoints have none.
 */
export function lastPageOf(meta: PaginationMeta | null): number | null {
  return meta?.last_page ?? null;
}

export function hasNextPage(meta: PaginationMeta | null): boolean {
  if (!meta) {
    return false;
  }
  if (meta.last_page !== undefined) {
    return meta.current_page < meta.last_page;
  }
  return meta.has_more_pages === true;
}

export function hasPreviousPage(meta: PaginationMeta | null): boolean {
  return (meta?.current_page ?? 1) > 1;
}
