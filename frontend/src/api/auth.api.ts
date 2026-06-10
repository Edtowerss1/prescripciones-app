import client from './client'
import type { LoginRequest, LoginResponse } from '@/types/auth'
import type { User } from '@/types/user'

export async function login(credentials: LoginRequest): Promise<LoginResponse> {
  const { data } = await client.post<LoginResponse>('/auth/login', credentials)
  return data
}

export async function profile(): Promise<User> {
  const { data } = await client.get<User>('/auth/profile')
  return data
}

export async function logout(): Promise<void> {
  await client.post('/auth/logout')
}
