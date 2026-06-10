export interface Patient {
  id: number
  user?: { id: number; name: string; email: string }
  birth_date: string | null
}
