export interface Doctor {
  id: number
  user?: { id: number; name: string; email: string }
  specialty: string | null
  license_number: string | null
  prescriptions_count?: number
}
