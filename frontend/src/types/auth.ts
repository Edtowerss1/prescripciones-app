export interface LoginRequest {
  email: string
  password: string
}

export interface LoginResponse {
  access_token: string
  token_type: 'Bearer'
  user: User
}

export interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'doctor' | 'patient'
}
