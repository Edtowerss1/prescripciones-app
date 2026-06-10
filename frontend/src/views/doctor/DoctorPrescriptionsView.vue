<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import PrescriptionTable from '@/components/prescriptions/PrescriptionTable.vue'
import { usePrescriptionsStore } from '@/stores/prescriptions.store'
import { usePagination } from '@/composables/usePagination'
import { useFilters } from '@/composables/useFilters'

const router = useRouter()
const store = usePrescriptionsStore()
const pagination = usePagination()
const { status: filterStatus, from: filterFrom, to: filterTo, search: filterSearch, buildQueryParams } = useFilters()

const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'pending', label: 'Pending' },
  { value: 'consumed', label: 'Consumed' },
]

const initialLoadDone = ref(false)

async function loadPrescriptions() {
  const params = {
    ...buildQueryParams(),
    page: pagination.page.value,
    limit: pagination.limit.value,
  }
  await store.fetchPrescriptions(params)
  if (store.pagination) {
    pagination.parseMeta(store.pagination)
  }
  initialLoadDone.value = true
}

// Reload when status, from, to, or page changes
watch([filterStatus, filterFrom, filterTo, pagination.page], () => {
  loadPrescriptions()
})

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout> | null = null
watch(filterSearch, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    pagination.goToPage(1)
    loadPrescriptions()
  }, 300)
})

loadPrescriptions()

function viewDetail(id: number) {
  router.push(`/doctor/prescriptions/${id}`)
}

function goToCreate() {
  router.push('/doctor/prescriptions/new')
}

function handleRetry() {
  store.error = null
  loadPrescriptions()
}
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-semibold text-gray-900">
        My Prescriptions
      </h1>
      <BaseButton @click="goToCreate">
        + New Prescription
      </BaseButton>
    </div>

    <!-- Filters -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
      <BaseInput v-model="filterSearch" label="Search" placeholder="Search patients..." />
      <BaseSelect v-model="filterStatus" label="Status" :options="statusOptions" />
      <BaseInput v-model="filterFrom" label="From" type="date" />
      <BaseInput v-model="filterTo" label="To" type="date" />
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
      action-label="Create Prescription"
      @action="goToCreate"
    />

    <!-- Table -->
    <div v-else>
      <PrescriptionTable
        :prescriptions="store.list"
        :loading="store.isLoading"
      >
        <template #actions="{ prescription }">
          <div class="flex items-center gap-2">
            <BaseButton variant="ghost" @click="viewDetail(prescription.id)">
              View
            </BaseButton>
          </div>
        </template>
      </PrescriptionTable>

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
