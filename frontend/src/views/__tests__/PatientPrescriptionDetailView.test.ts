import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'

vi.mock('@/api/prescriptions.api', () => ({
  show: vi.fn(),
  list: vi.fn(),
  myList: vi.fn(),
  create: vi.fn(),
  pdf: vi.fn(),
  consume: vi.fn(),
}))

const push = vi.fn()
vi.mock('vue-router', () => ({
  useRoute: () => ({ params: { id: '1' } }),
  useRouter: () => ({ push }),
}))

import * as prescriptionsApi from '@/api/prescriptions.api'
import PatientPrescriptionDetailView from '@/views/patient/PatientPrescriptionDetailView.vue'

function mockError(status: number) {
  const err = new Error() as any
  err.response = { status }
  return err
}

describe('PatientPrescriptionDetailView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    push.mockClear()
    vi.clearAllMocks()
  })

  it('shows "Prescription not found" on 404 response', async () => {
    vi.mocked(prescriptionsApi.show).mockRejectedValue(mockError(404))

    const wrapper = mount(PatientPrescriptionDetailView)
    await flushPromises()

    expect(wrapper.text()).toContain('Prescription not found')
    expect(wrapper.text()).toContain('Back to Prescriptions')
  })

  it('shows "Access denied" on 403 response', async () => {
    vi.mocked(prescriptionsApi.show).mockRejectedValue(mockError(403))

    const wrapper = mount(PatientPrescriptionDetailView)
    await flushPromises()

    expect(wrapper.text()).toContain('Access denied')
    expect(wrapper.text()).toContain('Back to Prescriptions')
  })

  it('shows loading spinner while fetching', async () => {
    vi.mocked(prescriptionsApi.show).mockImplementation(
      () => new Promise(() => {}), // never resolves
    )

    const wrapper = mount(PatientPrescriptionDetailView)
    await flushPromises()

    expect(wrapper.text()).toContain('Loading prescription')
  })
})
