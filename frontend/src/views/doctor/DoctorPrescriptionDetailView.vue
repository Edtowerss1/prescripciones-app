<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseTable from '@/components/ui/BaseTable.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import PrescriptionStatusBadge from '@/components/prescriptions/PrescriptionStatusBadge.vue'
import { usePrescriptionsStore } from '@/stores/prescriptions.store'
import { useToast } from '@/composables/useToast'
import type { PrescriptionItem } from '@/types/prescription'

const route = useRoute()
const router = useRouter()
const store = usePrescriptionsStore()
const { addToast } = useToast()

const id = Number(route.params.id)
const notFound = ref(false)

const itemColumns = [
  { key: 'name', label: 'Name' },
  { key: 'dosage', label: 'Dosage' },
  { key: 'quantity', label: 'Qty' },
  { key: 'instructions', label: 'Instructions' },
]

async function loadPrescription() {
  try {
    await store.fetchPrescription(id)
    notFound.value = false
  } catch (err: any) {
    if (err?.response?.status === 404) {
      notFound.value = true
    }
  }
}

async function handleDownloadPdf() {
  try {
    await store.downloadPdf(id)
    addToast('PDF downloaded.', 'success')
  } catch {
    addToast('Failed to download PDF.', 'error')
  }
}

function goBack() {
  router.push('/doctor/prescriptions')
}

onMounted(() => {
  loadPrescription()
})
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-semibold text-gray-900">
        Prescription Detail
      </h1>
      <BaseButton variant="secondary" @click="goBack">
        ← Back to List
      </BaseButton>
    </div>

    <!-- Loading State -->
    <div v-if="store.isLoading && !notFound" class="py-12">
      <LoadingSpinner size="lg" label="Loading prescription..." />
    </div>

    <!-- Not Found / Unauthorized State -->
    <div
      v-else-if="notFound"
      class="rounded-lg border border-yellow-200 bg-yellow-50 p-8 text-center"
    >
      <p class="mb-2 text-lg font-medium text-yellow-800">
        Prescription not found
      </p>
      <p class="mb-4 text-sm text-yellow-600">
        This prescription may not exist or you may not have access to it.
      </p>
      <BaseButton @click="goBack">
        Back to Prescriptions
      </BaseButton>
    </div>

    <!-- Error State (non-404) -->
    <div
      v-else-if="store.error && !store.current"
      class="rounded-lg border border-red-200 bg-red-50 p-8 text-center"
    >
      <p class="mb-4 text-red-700">{{ store.error }}</p>
      <div class="flex items-center justify-center gap-3">
        <BaseButton @click="loadPrescription">
          Retry
        </BaseButton>
        <BaseButton variant="secondary" @click="goBack">
          Back to List
        </BaseButton>
      </div>
    </div>

    <!-- Detail Content -->
    <div v-else-if="store.current" class="space-y-6">
      <!-- Info Card -->
      <div class="rounded-lg border border-gray-200 bg-white p-6">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-lg font-medium text-gray-900">
            {{ store.current.code }}
          </h2>
          <PrescriptionStatusBadge :status="store.current.status" />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div>
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">
              Patient
            </p>
            <p class="mt-1 text-sm text-gray-900">
              {{ store.current.patient?.name ?? '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">
              Doctor
            </p>
            <p class="mt-1 text-sm text-gray-900">
              {{ store.current.doctor?.name ?? '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">
              Created
            </p>
            <p class="mt-1 text-sm text-gray-900">
              {{ new Date(store.current.created_at).toLocaleDateString() }}
            </p>
          </div>
        </div>

        <div v-if="store.current.notes" class="mt-4">
          <p class="text-xs font-medium uppercase tracking-wider text-gray-500">
            Notes
          </p>
          <p class="mt-1 text-sm text-gray-700">
            {{ store.current.notes }}
          </p>
        </div>

        <div v-if="store.current.consumed_at" class="mt-4">
          <p class="text-xs font-medium uppercase tracking-wider text-gray-500">
            Consumed At
          </p>
          <p class="mt-1 text-sm text-gray-700">
            {{ new Date(store.current.consumed_at).toLocaleDateString() }}
          </p>
        </div>
      </div>

      <!-- Items Table -->
      <div class="rounded-lg border border-gray-200 bg-white p-6">
        <h3 class="mb-4 text-base font-medium text-gray-900">
          Prescription Items
        </h3>

        <BaseTable
          :columns="itemColumns"
          :rows="(store.current.items as unknown as Record<string, any>[]) ?? []"
          empty-message="No items in this prescription."
        />
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-3">
        <BaseButton @click="handleDownloadPdf">
          Download PDF
        </BaseButton>
      </div>
    </div>
  </div>
</template>
