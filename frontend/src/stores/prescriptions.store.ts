import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as prescriptionsApi from '@/api/prescriptions.api'
import type { Prescription, CreatePrescriptionPayload } from '@/types/prescription'
import type { PaginatedResponse } from '@/types/api'

export const usePrescriptionsStore = defineStore('prescriptions', () => {
  const list = ref<Prescription[]>([])
  const current = ref<Prescription | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref<PaginatedResponse<Prescription>['meta'] | null>(null)

  async function fetchPrescriptions(
    filters: prescriptionsApi.PrescriptionFilters = {},
  ) {
    isLoading.value = true
    error.value = null
    try {
      const res = await prescriptionsApi.list(filters)
      list.value = res.data
      pagination.value = res.meta
    } catch (err: any) {
      const msg =
        err?.response?.data?.message ||
        err?.message ||
        'Failed to load prescriptions.'
      error.value = msg
      list.value = []
      pagination.value = null
    } finally {
      isLoading.value = false
    }
  }

  async function fetchMyPrescriptions(
    filters: prescriptionsApi.PrescriptionFilters = {},
  ) {
    isLoading.value = true
    error.value = null
    try {
      const res = await prescriptionsApi.myList(filters)
      list.value = res.data
      pagination.value = res.meta
    } catch (err: any) {
      const msg =
        err?.response?.data?.message ||
        err?.message ||
        'Failed to load prescriptions.'
      error.value = msg
      list.value = []
      pagination.value = null
    } finally {
      isLoading.value = false
    }
  }

  async function createPrescription(
    payload: CreatePrescriptionPayload,
  ): Promise<Prescription> {
    isLoading.value = true
    error.value = null
    try {
      const prescription = await prescriptionsApi.create(payload)
      return prescription
    } catch (err: any) {
      const msg =
        err?.response?.data?.message ||
        err?.message ||
        'Failed to create prescription.'
      error.value = msg
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function fetchPrescription(id: number) {
    isLoading.value = true
    error.value = null
    try {
      current.value = await prescriptionsApi.show(id)
    } catch (err: any) {
      if (err?.response?.status === 404) {
        error.value = 'Prescription not found.'
      } else if (err?.response?.status === 403) {
        error.value = 'Access denied.'
      } else {
        error.value =
          err?.response?.data?.message ||
          err?.message ||
          'Failed to load prescription.'
      }
      current.value = null
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function consumePrescription(id: number): Promise<Prescription> {
    error.value = null
    try {
      const prescription = await prescriptionsApi.consume(id)
      // Update current detail if it matches
      if (current.value?.id === id) {
        current.value = prescription
      }
      return prescription
    } catch (err: any) {
      const msg =
        err?.response?.data?.message ||
        err?.message ||
        'Failed to consume prescription.'
      error.value = msg
      throw err
    }
  }

  async function downloadPdf(id: number): Promise<void> {
    try {
      const blob = await prescriptionsApi.pdf(id)
      const url = window.URL.createObjectURL(blob)
      const anchor = document.createElement('a')
      anchor.href = url
      anchor.download = `prescription-${id}.pdf`
      document.body.appendChild(anchor)
      anchor.click()
      document.body.removeChild(anchor)
      window.URL.revokeObjectURL(url)
    } catch (err: any) {
      const msg =
        err?.response?.data?.message ||
        err?.message ||
        'Failed to download PDF.'
      error.value = msg
      throw err
    }
  }

  return {
    list,
    current,
    isLoading,
    error,
    pagination,
    fetchPrescriptions,
    fetchMyPrescriptions,
    createPrescription,
    fetchPrescription,
    consumePrescription,
    downloadPdf,
  }
})
