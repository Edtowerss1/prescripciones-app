export interface Prescription {
  id: number
  code: string
  status: 'pending' | 'consumed'
  notes: string | null
  consumed_at: string | null
  doctor?: { id: number; name: string }
  patient?: { id: number; name: string }
  items?: PrescriptionItem[]
  created_at: string
}

export interface PrescriptionItem {
  id: number
  name: string
  dosage: string
  quantity: number
  instructions: string
}

export interface CreatePrescriptionPayload {
  patient_id: number
  notes?: string | null
  items: Omit<PrescriptionItem, 'id'>[]
}

export interface AdminMetrics {
  totals: {
    doctors: number
    patients: number
    prescriptions: number
  }
  by_status: {
    pending: number
    consumed: number
  }
  by_day: { date: string; count: number }[]
  top_doctors: { doctor_id: number; doctor_name: string; count: number }[]
}
