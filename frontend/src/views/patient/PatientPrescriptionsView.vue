<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseTable from '@/components/ui/BaseTable.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import PrescriptionStatusBadge from '@/components/prescriptions/PrescriptionStatusBadge.vue'
import { usePrescriptionsStore } from '@/stores/prescriptions.store'
import { usePagination } from '@/composables/usePagination'
import { useFilters } from '@/composables/useFilters'
import { useToast } from '@/composables/useToast'
import type { Prescription } from '@/types/prescription'

const router = useRouter()
const store = usePrescriptionsStore()
const { addToast } = useToast()
const pagination = usePagination()
const { status: filterStatus, buildQueryParams } = useFilters()

const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'pending', label: 'Pending' },
  { value: 'consumed', label: 'Consumed' },
]

const initialLoadDone = ref(false)

const tableColumns = [
  { key: 'code', label: 'Code' },
  { key: 'doctor_name', label: 'Doctor' },
  { key: 'status', label: 'Status' },
  { key: 'date', label: 'Date' },
  { key: 'actions', label: 'Actions' },
]

async function loadPrescriptions() {
  const params: Record<string, string | number> = {
    ...buildQueryParams(),
    page: pagination.page.value,
    limit: pagination.limit.value,
  }
  await store.fetchMyPrescriptions(params)
  if (store.pagination) {
    pagination.parseMeta(store.pagination)
  }
  initialLoadDone.value = true
}

// Reload when status or page changes
watch([filterStatus, pagination.page], () => {
  loadPrescriptions()
})

loadPrescriptions()

function viewDetail(id: number) {
  router.push(`/patient/prescriptions/${id}`)
}

async function handleConsume(id: number) {
  if (!window.confirm('Are you sure you want to mark this prescription as consumed?')) {
    return
  }
  try {
    await store.consumePrescription(id)
    addToast('Prescription marked as consumed.', 'success')
    loadPrescriptions()
  } catch (err: any) {
    if (err?.response?.status === 409) {
      addToast('This prescription has already been consumed.', 'error')
    } else if (err?.response?.status === 404) {
      addToast('Prescription not found.', 'error')
    } else {
      addToast('Failed to consume prescription.', 'error')
    }
    loadPrescriptions()
  }
}

async function handleDownloadPdf(id: number) {
  try {
    await store.downloadPdf(id)
    addToast('PDF downloaded.', 'success')
  } catch {
    addToast('Failed to download PDF.', 'error')
  }
}

function handleRetry() {
  store.error = null
  loadPrescriptions()
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-semibold text-gray-900">
        My Prescriptions
      </h1>
    </div>

    <!-- Filters — Patient list only supports status -->
    <div class="mb-6 max-w-xs">
      <BaseSelect v-model="filterStatus" label="Status" :options="statusOptions" />
    </div>

    <!-- Loading State -->
    <div v-if="store.isLoading && !initialLoadDone" class="py-12">
      <LoadingSpinner size="lg" label="Loading prescriptions..." />
    </div>

    <!-- Error State -->
    <div
      v-else-if="store.error && store.list.length === 0"
      class="rounded-lg border border-red-200 bg-red-50 p-6 text-center"
    >
      <p class="mb-4 text-red-700">{{ store.error }}</p>
      <BaseButton variant="secondary" @click="handleRetry">
        Retry
      </BaseButton>
    </div>

    <!-- Empty State -->
    <EmptyState
      v-else-if="!store.isLoading && store.list.length === 0"
      message="No prescriptions yet."
    />

    <!-- Table -->
    <div v-else>
      <BaseTable
        :columns="tableColumns"
        :rows="store.list as unknown as Record<string, any>[]"
        :loading="store.isLoading"
        empty-message="No prescriptions found."
      >
        <template #column-doctor_name="{ row }">
          {{ (row as Prescription).doctor?.name ?? '—' }}
        </template>

        <template #column-status="{ row }">
          <PrescriptionStatusBadge :status="(row as Prescription).status" />
        </template>

        <template #column-date="{ row }">
          {{ new Date((row as Prescription).created_at).toLocaleDateString() }}
        </template>

        <template #column-actions="{ row }">
          <div class="flex items-center gap-2">
            <BaseButton variant="ghost" @click="viewDetail((row as Prescription).id)">
              View
            </BaseButton>
            <BaseButton
              v-if="(row as Prescription).status === 'pending'"
              variant="secondary"
              @click="handleConsume((row as Prescription).id)"
            >
              Mark as Consumed
            </BaseButton>
            <BaseButton
              variant="ghost"
              @click="handleDownloadPdf((row as Prescription).id)"
            >
              PDF
            </BaseButton>
          </div>
        </template>
      </BaseTable>

      <!-- Pagination -->
      <div
        v-if="pagination.lastPage.value > 1"
        class="mt-4 flex items-center justify-between text-sm text-gray-600"
      >
        <span>
          Showing {{ pagination.from.value }}–{{ pagination.to.value }} of
          {{ pagination.total.value }}
        </span>
        <div class="flex items-center gap-2">
          <BaseButton
            variant="secondary"
            :disabled="pagination.page.value <= 1"
            @click="pagination.prevPage()"
          >
            Previous
          </BaseButton>
          <span class="px-2">
            Page {{ pagination.page.value }} of {{ pagination.lastPage.value }}
          </span>
          <BaseButton
            variant="secondary"
            :disabled="pagination.page.value >= pagination.lastPage.value"
            @click="pagination.nextPage()"
          >
            Next
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>
