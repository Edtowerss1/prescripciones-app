import client from './client'
import type { PaginatedResponse } from '@/types/api'
import type { Doctor } from '@/types/doctor'

export async function list(
  query?: string,
  page: number = 1,
  limit: number = 15,
): Promise<PaginatedResponse<Doctor>> {
  const { data } = await client.get<PaginatedResponse<Doctor>>('/doctors', {
    params: { query, page, limit },
  })
  return data
}
