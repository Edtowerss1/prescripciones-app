<script setup lang="ts">
import { ref, onMounted } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import PrescriptionsByStatusChart from '@/components/charts/PrescriptionsByStatusChart.vue'
import PrescriptionsByDayChart from '@/components/charts/PrescriptionsByDayChart.vue'
import TopDoctorsChart from '@/components/charts/TopDoctorsChart.vue'
import { useAdminStore } from '@/stores/admin.store'

const store = useAdminStore()

const fromFilter = ref('')
const toFilter = ref('')
const initialLoadDone = ref(false)

async function loadMetrics() {
  await store.fetchMetrics(
    fromFilter.value || undefined,
    toFilter.value || undefined,
  )
  initialLoadDone.value = true
}

function handleFilter() {
  loadMetrics()
}

function handleRetry() {
  store.error = null
  loadMetrics()
}

function totalsEmpty(): boolean {
  if (!store.metrics?.totals) return true
  const t = store.metrics.totals
  return t.doctors === 0 && t.patients === 0 && t.prescriptions === 0
}

onMounted(loadMetrics)
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-semibold text-gray-900">
        Admin Dashboard
      </h1>
    </div>

    <!-- Date range filters -->
    <div class="mb-6 flex items-end gap-3">
      <BaseInput
        v-model="fromFilter"
        label="From"
        type="date"
      />
      <BaseInput
        v-model="toFilter"
        label="To"
        type="date"
      />
      <BaseButton @click="handleFilter">
        Apply
      </BaseButton>
    </div>

    <!-- Loading State -->
    <div v-if="store.isLoading && !initialLoadDone" class="py-12">
      <LoadingSpinner size="lg" label="Loading metrics..." />
    </div>

    <!-- Error State -->
    <div
      v-else-if="store.error && !store.metrics"
      class="rounded-lg border border-red-200 bg-red-50 p-6 text-center"
    >
      <p class="mb-4 text-red-700">{{ store.error }}</p>
      <BaseButton variant="secondary" @click="handleRetry">
        Retry
      </BaseButton>
    </div>

    <!-- Empty State -->
    <EmptyState
      v-else-if="!store.isLoading && store.metrics && totalsEmpty()"
      message="No data available."
    />

    <!-- Dashboard Content -->
    <div v-else-if="store.metrics" class="space-y-6">
      <!-- Summary cards -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div
          class="rounded-lg border border-gray-200 bg-white p-5"
          :class="{ 'opacity-60': store.isLoading }"
        >
          <p class="text-xs font-medium uppercase tracking-wider text-gray-500">
            Total Doctors
          </p>
          <p class="mt-2 text-3xl font-bold text-indigo-600">
            {{ store.metrics?.totals?.doctors ?? 0 }}
          </p>
        </div>
        <div
          class="rounded-lg border border-gray-200 bg-white p-5"
          :class="{ 'opacity-60': store.isLoading }"
        >
          <p class="text-xs font-medium uppercase tracking-wider text-gray-500">
            Total Patients
          </p>
          <p class="mt-2 text-3xl font-bold text-emerald-600">
            {{ store.metrics?.totals?.patients ?? 0 }}
          </p>
        </div>
        <div
          class="rounded-lg border border-gray-200 bg-white p-5"
          :class="{ 'opacity-60': store.isLoading }"
        >
          <p class="text-xs font-medium uppercase tracking-wider text-gray-500">
            Total Prescriptions
          </p>
          <p class="mt-2 text-3xl font-bold text-amber-600">
            {{ store.metrics?.totals?.prescriptions ?? 0 }}
          </p>
        </div>
      </div>

      <!-- Charts row -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- By Status -->
        <div class="rounded-lg border border-gray-200 bg-white p-5">
          <h3 class="mb-4 text-sm font-medium text-gray-700">
            Prescriptions by Status
          </h3>
          <PrescriptionsByStatusChart
            :pending="store.metrics.by_status.pending"
            :consumed="store.metrics.by_status.consumed"
          />
        </div>

        <!-- By Day -->
        <div class="rounded-lg border border-gray-200 bg-white p-5">
          <h3 class="mb-4 text-sm font-medium text-gray-700">
            Prescriptions by Day
          </h3>
          <PrescriptionsByDayChart :data="store.metrics.by_day" />
        </div>

        <!-- Top Doctors -->
        <div class="rounded-lg border border-gray-200 bg-white p-5">
          <h3 class="mb-4 text-sm font-medium text-gray-700">
            Top Doctors
          </h3>
          <TopDoctorsChart :data="store.metrics.top_doctors" />
        </div>
      </div>
    </div>
  </div>
</template>
