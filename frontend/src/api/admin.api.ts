import client from './client'
import type { PaginatedResponse } from '@/types/api'
import type { Prescription, AdminMetrics } from '@/types/prescription'

export interface AdminPrescriptionFilters {
  status?: string
  doctor_id?: number
  patient_id?: number
  from?: string
  to?: string
  page?: number
  limit?: number
}

export async function metrics(
  from?: string,
  to?: string,
): Promise<AdminMetrics> {
  const { data } = await client.get<AdminMetrics>('/admin/metrics', {
    params: { from, to },
  })
  return data
}

export async function prescriptions(
  filters: AdminPrescriptionFilters = {},
): Promise<PaginatedResponse<Prescription>> {
  const { data } = await client.get<PaginatedResponse<Prescription>>(
    '/admin/prescriptions',
    { params: filters },
  )
  return data
}
