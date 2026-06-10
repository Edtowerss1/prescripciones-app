<script setup lang="ts">
import { ref, watch } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import PrescriptionItemsForm from '@/components/prescriptions/PrescriptionItemsForm.vue'
import * as patientsApi from '@/api/patients.api'
import type { Patient } from '@/types/patient'

interface PrescriptionItemForm {
  name: string
  dosage: string
  quantity: number | string
  instructions: string
}

interface FormData {
  patient_id: number | ''
  notes: string
  items: PrescriptionItemForm[]
}

interface Props {
  loading?: boolean
  errors?: Record<string, string>
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  errors: () => ({}),
})

const emit = defineEmits<{
  submit: [data: {
    patient_id: number
    notes: string | null
    items: PrescriptionItemForm[]
  }]
}>()

const form = ref<FormData>({
  patient_id: '',
  notes: '',
  items: [],
})

const patients = ref<Patient[]>([])
const patientSearch = ref('')
const patientOptions = ref<Array<{ value: number; label: string }>>([])
const searchTimeout = ref<ReturnType<typeof setTimeout> | null>(null)

async function searchPatients(query: string) {
  if (searchTimeout.value) clearTimeout(searchTimeout.value)
  searchTimeout.value = setTimeout(async () => {
    try {
      const res = await patientsApi.list(query, 1, 20)
      patients.value = res.data
      patientOptions.value = res.data.map((p) => ({
        value: p.id,
        label: p.user?.name ?? `Patient #${p.id}`,
      }))
    } catch {
      // Silently fail; patients list will be empty
    }
  }, 300)
}

// Load initial patients
searchPatients('')

watch(patientSearch, (val) => {
  searchPatients(val)
})

function handleSubmit() {
  if (!form.value.patient_id) return
  if (form.value.items.length === 0) return

  emit('submit', {
    patient_id: form.value.patient_id,
    notes: form.value.notes || null,
    items: form.value.items.map((item) => ({
      name: item.name,
      dosage: item.dosage,
      quantity: Number(item.quantity),
      instructions: item.instructions,
    })),
  })
}
</script>

<template>
  <form class="space-y-6" @submit.prevent="handleSubmit">
    <BaseInput
      v-model="patientSearch"
      label="Search Patient"
      placeholder="Type to search patients..."
      :error="errors?.patient_id"
    />

    <BaseSelect
      :model-value="form.patient_id"
      label="Select Patient"
      :options="patientOptions"
      placeholder="Select a patient..."
      :error="errors?.patient_id"
      @update:model-value="form.patient_id = Number($event)"
    />

    <BaseInput
      v-model="form.notes"
      label="Notes"
      placeholder="Optional notes or instructions"
      :error="errors?.notes"
    />

    <PrescriptionItemsForm
      v-model="form.items"
    />

    <div
      v-if="errors?.items"
      class="rounded-md bg-red-50 p-3 text-sm text-red-700"
    >
      {{ errors.items }}
    </div>

    <div class="flex items-center gap-3">
      <BaseButton
        type="submit"
        :loading="loading"
        :disabled="!form.patient_id || form.items.length === 0"
      >
        Create Prescription
      </BaseButton>
    </div>
  </form>
</template>
