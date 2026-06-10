<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseTable from '@/components/ui/BaseTable.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import PrescriptionStatusBadge from '@/components/prescriptions/PrescriptionStatusBadge.vue'
import { useAdminStore } from '@/stores/admin.store'
import { usePagination } from '@/composables/usePagination'
import * as doctorsApi from '@/api/doctors.api'
import * as patientsApi from '@/api/patients.api'
import type { Prescription } from '@/types/prescription'
import type { Doctor } from '@/types/doctor'
import type { Patient } from '@/types/patient'

const router = useRouter()
const store = useAdminStore()
const pagination = usePagination()

const initialLoadDone = ref(false)

// Status filter
const statusFilter = ref('')
const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'pending', label: 'Pending' },
  { value: 'consumed', label: 'Consumed' },
]

// Date filters
const fromFilter = ref('')
const toFilter = ref('')

// Doctor search
const doctorSearch = ref('')
const doctorSearchResults = ref<Doctor[]>([])
const selectedDoctor = ref<Doctor | null>(null)
const showDoctorDropdown = ref(false)
let doctorSearchTimeout: ReturnType<typeof setTimeout> | null = null

// Patient search
const patientSearch = ref('')
const patientSearchResults = ref<Patient[]>([])
const selectedPatient = ref<Patient | null>(null)
const showPatientDropdown = ref(false)
let patientSearchTimeout: ReturnType<typeof setTimeout> | null = null

const tableColumns = [
  { key: 'code', label: 'Code' },
  { key: 'patient_name', label: 'Patient' },
  { key: 'doctor_name', label: 'Doctor' },
  { key: 'status', label: 'Status' },
  { key: 'date', label: 'Date' },
  { key: 'actions', label: 'Actions' },
]

// Search doctors with debounce
function searchDoctors(query: string) {
  if (doctorSearchTimeout) clearTimeout(doctorSearchTimeout)
  doctorSearchTimeout = setTimeout(async () => {
    if (!query) {
      doctorSearchResults.value = []
      showDoctorDropdown.value = false
      return
    }
    try {
      const res = await doctorsApi.list(query, 1, 10)
      doctorSearchResults.value = res.data
      showDoctorDropdown.value = res.data.length > 0
    } catch {
      doctorSearchResults.value = []
      showDoctorDropdown.value = false
    }
  }, 300)
}

function selectDoctor(doctor: Doctor) {
  selectedDoctor.value = doctor
  doctorSearch.value = doctor.user?.name ?? ''
  showDoctorDropdown.value = false
}

function clearDoctor() {
  selectedDoctor.value = null
  doctorSearch.value = ''
  doctorSearchResults.value = []
}

// Search patients with debounce
function searchPatients(query: string) {
  if (patientSearchTimeout) clearTimeout(patientSearchTimeout)
  patientSearchTimeout = setTimeout(async () => {
    if (!query) {
      patientSearchResults.value = []
      showPatientDropdown.value = false
      return
    }
    try {
      const res = await patientsApi.list(query, 1, 10)
      patientSearchResults.value = res.data
      showPatientDropdown.value = res.data.length > 0
    } catch {
      patientSearchResults.value = []
      showPatientDropdown.value = false
    }
  }, 300)
}

function selectPatient(patient: Patient) {
  selectedPatient.value = patient
  patientSearch.value = patient.user?.name ?? ''
  showPatientDropdown.value = false
}

function clearPatient() {
  selectedPatient.value = null
  patientSearch.value = ''
  patientSearchResults.value = []
}

// Load prescriptions
async function loadPrescriptions() {
  const params: Record<string, string | number> = {
    page: pagination.page.value,
    limit: pagination.limit.value,
  }
  if (statusFilter.value) params.status = statusFilter.value
  if (selectedDoctor.value) params.doctor_id = selectedDoctor.value.id
  if (selectedPatient.value) params.patient_id = selectedPatient.value.id
  if (fromFilter.value) params.from = fromFilter.value
  if (toFilter.value) params.to = toFilter.value

  await store.fetchPrescriptions(params)
  if (store.pagination) {
    pagination.parseMeta(store.pagination)
  }
  initialLoadDone.value = true
}

// Apply filters (reset to page 1)
function applyFilters() {
  pagination.page.value = 1
  loadPrescriptions()
}

// Watch status and page changes
watch([statusFilter, pagination.page], () => {
  loadPrescriptions()
})

// Watch doctor search input
watch(doctorSearch, (val) => {
  if (!val) {
    clearDoctor()
  } else if (!selectedDoctor.value || selectedDoctor.value.user?.name !== val) {
    searchDoctors(val)
  }
})

// Watch patient search input
watch(patientSearch, (val) => {
  if (!val) {
    clearPatient()
  } else if (!selectedPatient.value || selectedPatient.value.user?.name !== val) {
    searchPatients(val)
  }
})

function viewDetail(id: number) {
  router.push(`/admin/prescriptions/${id}`)
}

function handleRetry() {
  store.error = null
  loadPrescriptions()
}

onMounted(() => {
  loadPrescriptions()
})
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-semibold text-gray-900">
        All Prescriptions
      </h1>
    </div>

    <!-- Filters -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
      <BaseSelect
        v-model="statusFilter"
        label="Status"
        :options="statusOptions"
      />

      <!-- Doctor search -->
      <div class="relative">
        <BaseInput
          v-model="doctorSearch"
          label="Doctor"
          placeholder="Search doctors..."
        />
        <button
          v-if="selectedDoctor"
          class="absolute right-2 top-[34px] text-gray-400 hover:text-gray-600"
          @click="clearDoctor"
        >
          &times;
        </button>
        <ul
          v-if="showDoctorDropdown"
          class="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg"
        >
          <li
            v-for="doc in doctorSearchResults"
            :key="doc.id"
            class="cursor-pointer px-3 py-2 text-sm hover:bg-indigo-50"
            @click="selectDoctor(doc)"
          >
            {{ doc.user?.name ?? `Doctor #${doc.id}` }}
          </li>
        </ul>
      </div>

      <!-- Patient search -->
      <div class="relative">
        <BaseInput
          v-model="patientSearch"
          label="Patient"
          placeholder="Search patients..."
        />
        <button
          v-if="selectedPatient"
          class="absolute right-2 top-[34px] text-gray-400 hover:text-gray-600"
          @click="clearPatient"
        >
          &times;
        </button>
        <ul
          v-if="showPatientDropdown"
          class="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg"
        >
          <li
            v-for="pat in patientSearchResults"
            :key="pat.id"
            class="cursor-pointer px-3 py-2 text-sm hover:bg-indigo-50"
            @click="selectPatient(pat)"
          >
            {{ pat.user?.name ?? `Patient #${pat.id}` }}
          </li>
        </ul>
      </div>

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
    </div>

    <!-- Apply filters button -->
    <div class="mb-6">
      <BaseButton @click="applyFilters">
        Apply Filters
      </BaseButton>
    </div>

    <!-- Loading State -->
    <div v-if="store.isLoading && !initialLoadDone" class="py-12">
      <LoadingSpinner size="lg" label="Loading prescriptions..." />
    </div>

    <!-- Error State -->
    <div
      v-else-if="store.error && store.prescriptions.length === 0"
      class="rounded-lg border border-red-200 bg-red-50 p-6 text-center"
    >
      <p class="mb-4 text-red-700">{{ store.error }}</p>
      <BaseButton variant="secondary" @click="handleRetry">
        Retry
      </BaseButton>
    </div>

    <!-- Empty State -->
    <EmptyState
      v-else-if="!store.isLoading && store.prescriptions.length === 0"
      message="No prescriptions found."
    />

    <!-- Table -->
    <div v-else>
      <BaseTable
        :columns="tableColumns"
        :rows="store.prescriptions as unknown as Record<string, any>[]"
        :loading="store.isLoading"
        empty-message="No prescriptions found."
      >
        <template #column-patient_name="{ row }">
          {{ (row as Prescription).patient?.name ?? '—' }}
        </template>

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
          <BaseButton variant="ghost" @click="viewDetail((row as Prescription).id)">
            View
          </BaseButton>
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
