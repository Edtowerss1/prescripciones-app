import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'

// Mock the patients API module
vi.mock('@/api/patients.api', () => ({
  list: vi.fn(),
}))

import * as patientsApi from '@/api/patients.api'
import PrescriptionForm from '@/components/prescriptions/PrescriptionForm.vue'
import PrescriptionItemsForm from '@/components/prescriptions/PrescriptionItemsForm.vue'

const mockPatients = {
  data: [
    {
      id: 1,
      user: { id: 1, name: 'Alice Patient', email: 'alice@test.com' },
      birth_date: '1990-01-15',
    },
    {
      id: 2,
      user: { id: 2, name: 'Bob Patient', email: 'bob@test.com' },
      birth_date: '1985-06-20',
    },
  ],
  links: { first: null, last: null, prev: null, next: null },
  meta: { current_page: 1, from: 1, last_page: 1, per_page: 20, to: 2, total: 2 },
}

describe('PrescriptionForm', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()

    // Mock patients API to return data
    const mockedList = vi.mocked(patientsApi.list)
    mockedList.mockResolvedValue(mockPatients)
  })

  it('renders patient search input, notes field, and items form', async () => {
    const wrapper = mount(PrescriptionForm)

    // Patient search input
    expect(wrapper.html()).toContain('Search Patient')

    // Notes field
    expect(wrapper.html()).toContain('Notes')

    // Items form section
    expect(wrapper.text()).toContain('Prescription Items')
    expect(wrapper.text()).toContain('No items added yet')
  })

  it('renders patient select dropdown', async () => {
    const wrapper = mount(PrescriptionForm)
    expect(wrapper.html()).toContain('Select Patient')
  })

  it('adds a new item when clicking Add Item button', async () => {
    const wrapper = mount(PrescriptionForm)

    // Find the Add Item button (it's in PrescriptionItemsForm child)
    const itemsForm = wrapper.findComponent(PrescriptionItemsForm)
    expect(itemsForm.exists()).toBe(true)

    // Click the "Add Item" button inside the items form
    const addBtn = itemsForm.find('button')
    expect(addBtn.exists()).toBe(true)
    expect(addBtn.text()).toContain('Add Item')
    await addBtn.trigger('click')

    // Should now show Item 1
    expect(wrapper.text()).toContain('Item 1')
  })

  it('removes an item when clicking Remove', async () => {
    const wrapper = mount(PrescriptionForm)
    const itemsForm = wrapper.findComponent(PrescriptionItemsForm)
    const addBtn = itemsForm.find('button')
    await addBtn.trigger('click')

    // Item 1 should appear
    expect(wrapper.text()).toContain('Item 1')

    // Click Remove button (it's the second button in itemsForm - the "Remove" text button)
    const allButtons = itemsForm.findAll('button')
    const removeBtn = allButtons.find((b) => b.text() === 'Remove')
    expect(removeBtn).toBeTruthy()
    await removeBtn!.trigger('click')

    // Item 1 should be gone, empty message should show
    expect(wrapper.text()).toContain('No items added yet')
  })

  it('disables submit button when patient is not selected and no items', () => {
    const wrapper = mount(PrescriptionForm)
    const submitBtn = wrapper.find('button[type="submit"]')
    expect(submitBtn.exists()).toBe(true)
    expect(submitBtn.attributes('disabled')).toBeDefined()
  })

  it('does not emit submit when patient is missing', async () => {
    const wrapper = mount(PrescriptionForm)
    await wrapper.find('form').trigger('submit.prevent')
    expect(wrapper.emitted('submit')).toBeUndefined()
  })

  it('shows loading state on submit button when loading prop is true', () => {
    const wrapper = mount(PrescriptionForm, {
      props: {
        loading: true,
      },
    })

    // The BaseButton should show spinner when loading
    const submitBtn = wrapper.find('button[type="submit"]')
    expect(submitBtn.attributes('disabled')).toBeDefined()

    // Should have a loading spinner SVG
    const spinner = submitBtn.find('svg')
    expect(spinner.exists()).toBe(true)
  })

  it('passes errors prop to form fields', () => {
    const wrapper = mount(PrescriptionForm, {
      props: {
        errors: {
          patient_id: 'Please select a patient.',
          notes: 'Notes are required.',
          items: 'At least one item is required.',
        },
      },
    })

    expect(wrapper.text()).toContain('Please select a patient.')
    expect(wrapper.text()).toContain('At least one item is required.')
  })
})
