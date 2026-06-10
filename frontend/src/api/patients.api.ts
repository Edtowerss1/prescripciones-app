import client from './client'
import type { PaginatedResponse } from '@/types/api'
import type { Patient } from '@/types/patient'

export async function list(
  query?: string,
  page: number = 1,
  limit: number = 15,
): Promise<PaginatedResponse<Patient>> {
  const { data } = await client.get<PaginatedResponse<Patient>>('/patients', {
    params: { query, page, limit },
  })
  return data
}
