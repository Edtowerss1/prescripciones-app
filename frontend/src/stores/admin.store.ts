import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as adminApi from '@/api/admin.api'
import type { AdminMetrics, Prescription } from '@/types/prescription'
import type { PaginatedResponse } from '@/types/api'

export const useAdminStore = defineStore('admin', () => {
  const metrics = ref<AdminMetrics | null>(null)
  const prescriptions = ref<Prescription[]>([])
  const pagination = ref<PaginatedResponse<Prescription>['meta'] | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  async function fetchMetrics(from?: string, to?: string) {
    isLoading.value = true
    error.value = null
    try {
      metrics.value = await adminApi.metrics(from, to)
    } catch (err: any) {
      const msg =
        err?.response?.data?.message ||
        err?.message ||
        'Failed to load metrics.'
      error.value = msg
      metrics.value = null
    } finally {
      isLoading.value = false
    }
  }

  async function fetchPrescriptions(
    filters: adminApi.AdminPrescriptionFilters = {},
  ) {
    isLoading.value = true
    error.value = null
    try {
      const res = await adminApi.prescriptions(filters)
      prescriptions.value = res.data
      pagination.value = res.meta
    } catch (err: any) {
      const msg =
        err?.response?.data?.message ||
        err?.message ||
        'Failed to load prescriptions.'
      error.value = msg
      prescriptions.value = []
      pagination.value = null
    } finally {
      isLoading.value = false
    }
  }

  return {
    metrics,
    prescriptions,
    pagination,
    isLoading,
    error,
    fetchMetrics,
    fetchPrescriptions,
  }
})
