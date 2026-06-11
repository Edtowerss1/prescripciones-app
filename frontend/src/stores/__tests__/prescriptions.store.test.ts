import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'

vi.mock('@/api/prescriptions.api', () => ({
  show: vi.fn(),
  list: vi.fn(),
  myList: vi.fn(),
  create: vi.fn(),
  pdf: vi.fn(),
  consume: vi.fn(),
}))

import * as prescriptionsApi from '@/api/prescriptions.api'
import { usePrescriptionsStore } from '@/stores/prescriptions.store'

function mockError(status: number, message: string) {
  const err = new Error(message) as any
  err.response = { status }
  return err
}

describe('prescriptions store fetchPrescription', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('sets error to "Prescription not found" on 404', async () => {
    const store = usePrescriptionsStore()
    vi.mocked(prescriptionsApi.show).mockRejectedValue(mockError(404, 'Not Found'))

    await expect(store.fetchPrescription(1)).rejects.toThrow()
    expect(store.error).toBe('Prescription not found.')
    expect(store.current).toBeNull()
  })

  it('sets error to "Access denied" on 403', async () => {
    const store = usePrescriptionsStore()
    vi.mocked(prescriptionsApi.show).mockRejectedValue(mockError(403, 'Forbidden'))

    await expect(store.fetchPrescription(1)).rejects.toThrow()
    expect(store.error).toBe('Access denied.')
    expect(store.current).toBeNull()
  })

  it('sets generic error message on unexpected error without message', async () => {
    const store = usePrescriptionsStore()
    const err = new Error() as any
    err.response = { status: 500 }
    vi.mocked(prescriptionsApi.show).mockRejectedValue(err)

    await expect(store.fetchPrescription(1)).rejects.toThrow()
    expect(store.error).toBe('Failed to load prescription.')
    expect(store.current).toBeNull()
  })

  it('sets error from response data message on 422', async () => {
    const store = usePrescriptionsStore()
    const err = mockError(422, 'Validation Error')
    err.response.data = { message: 'Validation failed.' }
    vi.mocked(prescriptionsApi.show).mockRejectedValue(err)

    await expect(store.fetchPrescription(1)).rejects.toThrow()
    expect(store.error).toBe('Validation failed.')
    expect(store.current).toBeNull()
  })

  it('uses err.message as fallback when response has no data.message', async () => {
    const store = usePrescriptionsStore()
    vi.mocked(prescriptionsApi.show).mockRejectedValue(mockError(500, 'Server Error'))

    await expect(store.fetchPrescription(1)).rejects.toThrow()
    expect(store.error).toBe('Server Error')
    expect(store.current).toBeNull()
  })
})
