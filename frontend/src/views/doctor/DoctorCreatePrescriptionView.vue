<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '@/components/ui/BaseButton.vue'
import PrescriptionForm from '@/components/prescriptions/PrescriptionForm.vue'
import { usePrescriptionsStore } from '@/stores/prescriptions.store'
import { useToast } from '@/composables/useToast'

const router = useRouter()
const store = usePrescriptionsStore()
const { addToast } = useToast()

const submitting = ref(false)
const fieldErrors = ref<Record<string, string>>({})

interface PrescriptionItemForm {
  name: string
  dosage: string
  quantity: number | string
  instructions: string
}

async function handleSubmit(data: {
  patient_id: number
  notes: string | null
  items: PrescriptionItemForm[]
}) {
  submitting.value = true
  fieldErrors.value = {}

  // Client-side validation
  if (!data.patient_id) {
    fieldErrors.value.patient_id = 'Please select a patient.'
    submitting.value = false
    return
  }

  if (data.items.length === 0) {
    fieldErrors.value.items = 'At least one item is required.'
    submitting.value = false
    return
  }

  const invalidItem = data.items.some(
    (item) => !item.name || !item.dosage || !item.quantity || !item.instructions,
  )
  if (invalidItem) {
    fieldErrors.value.items = 'All item fields are required.'
    submitting.value = false
    return
  }

  try {
    await store.createPrescription({
      patient_id: data.patient_id,
      notes: data.notes,
      items: data.items.map((item) => ({
        name: item.name,
        dosage: item.dosage,
        quantity: Number(item.quantity),
        instructions: item.instructions,
      })),
    })

    addToast('Prescription created successfully.', 'success')
    router.push('/doctor/prescriptions')
  } catch (err: any) {
    // Map server-side validation errors to fields
    if (err?.validationErrors) {
      const mapped: Record<string, string> = {}
      for (const [key, msgs] of Object.entries(err.validationErrors)) {
        mapped[key] = (msgs as string[])[0]
      }
      fieldErrors.value = mapped
    } else {
      addToast(
        err?.response?.data?.message ||
          err?.message ||
          'Failed to create prescription.',
        'error',
      )
    }
  } finally {
    submitting.value = false
  }
}

function goBack() {
  router.back()
}
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-semibold text-gray-900">
        New Prescription
      </h1>
      <BaseButton variant="secondary" @click="goBack">
        Cancel
      </BaseButton>
    </div>

    <div class="max-w-2xl rounded-lg border border-gray-200 bg-white p-6">
      <PrescriptionForm
        :loading="submitting"
        :errors="fieldErrors"
        @submit="handleSubmit"
      />
    </div>
  </div>
</template>
