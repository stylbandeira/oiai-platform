export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export type QueryValue = string | number | boolean;

export type QueryParams = Record<string, QueryValue | undefined>;

export interface PaginatedResponse<T> {
  data: T[];
  meta: PaginationMeta;
}

export interface ApiErrorResponse {
  message?: string;
  errors?: Record<string, string[]>;
}
