export interface PaginatedResponse<T> {
  data: T[]
  links: {
    first: string | null
    last: string | null
    prev: string | null
    next: string | null
  }
  meta: {
    current_page: number
    from: number
    last_page: number
    per_page: number
    to: number
    total: number
  }
}

export interface ApiError {
  message: string
  code?: string
  details?: Record<string, never>
}

export interface ValidationError extends ApiError {
  errors: Record<string, string[]>
}
