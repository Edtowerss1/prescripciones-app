<script setup lang="ts">
import BaseTable from '@/components/ui/BaseTable.vue'
import PrescriptionStatusBadge from '@/components/prescriptions/PrescriptionStatusBadge.vue'
import type { Prescription } from '@/types/prescription'

interface Props {
  prescriptions: Prescription[]
  loading?: boolean
}

withDefaults(defineProps<Props>(), {
  loading: false,
})

const columns = [
  { key: 'code', label: 'Code' },
  { key: 'patient_name', label: 'Patient' },
  { key: 'status', label: 'Status' },
  { key: 'date', label: 'Date' },
  { key: 'actions', label: 'Actions' },
]
</script>

<template>
  <BaseTable
    :columns="columns"
    :rows="prescriptions"
    :loading="loading"
    empty-message="No prescriptions found."
  >
    <template #column-patient_name="{ row }">
      {{ (row as Prescription).patient?.name ?? '—' }}
    </template>

    <template #column-status="{ row }">
      <PrescriptionStatusBadge :status="(row as Prescription).status" />
    </template>

    <template #column-date="{ row }">
      {{ new Date((row as Prescription).created_at).toLocaleDateString() }}
    </template>

    <template #column-actions="{ row }">
      <slot
        name="actions"
        :prescription="(row as Prescription)"
      />
    </template>
  </BaseTable>
</template>
