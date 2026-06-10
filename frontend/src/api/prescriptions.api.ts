import client from './client'
import type { PaginatedResponse } from '@/types/api'
import type {
  Prescription,
  CreatePrescriptionPayload,
} from '@/types/prescription'

export interface PrescriptionFilters {
  status?: string
  from?: string
  to?: string
  page?: number
  limit?: number
}

export async function list(
  filters: PrescriptionFilters,
): Promise<PaginatedResponse<Prescription>> {
  const { data } = await client.get<PaginatedResponse<Prescription>>(
    '/prescriptions',
    { params: filters },
  )
  return data
}

export async function create(
  payload: CreatePrescriptionPayload,
): Promise<Prescription> {
  const { data } = await client.post<Prescription>('/prescriptions', payload)
  return data
}

export async function show(id: number): Promise<Prescription> {
  const { data } = await client.get<Prescription>(`/prescriptions/${id}`)
  return data
}

export async function pdf(id: number): Promise<Blob> {
  const { data } = await client.get<Blob>(`/prescriptions/${id}/pdf`, {
    responseType: 'blob',
  })
  return data
}

export async function consume(id: number): Promise<Prescription> {
  const { data } = await client.put<Prescription>(
    `/prescriptions/${id}/consume`,
  )
  return data
}
