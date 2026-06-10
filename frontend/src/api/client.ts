import axios from 'axios'
import type {
  AxiosInstance,
  AxiosError,
  InternalAxiosRequestConfig,
} from 'axios'
import type { ValidationError } from '@/types/api'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL

const client: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

let logoutCallback: (() => void) | null = null
let errorToastCallback: ((message: string) => void) | null = null

export function setLogoutCallback(cb: () => void): void {
  logoutCallback = cb
}

export function setErrorToastCallback(cb: (message: string) => void): void {
  errorToastCallback = cb
}

client.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = localStorage.getItem('access_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error: AxiosError) => Promise.reject(error),
)

client.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (!error.response) {
      errorToastCallback?.('Connection error. Check your network.')
      return Promise.reject(error)
    }

    const { status } = error.response

    if (status === 401) {
      localStorage.removeItem('access_token')
      logoutCallback?.()
      return Promise.reject(error)
    }

    if (status === 422) {
      const data = error.response.data as ValidationError
      return Promise.reject({
        ...error,
        validationErrors: data.errors ?? {},
        message: data.message ?? 'Validation failed.',
      })
    }

    if (status === 409) {
      errorToastCallback?.('Conflict detected. Refreshing data.')
    }

    if (status && status >= 500) {
      errorToastCallback?.('Server error. Please try again later.')
    }

    return Promise.reject(error)
  },
)

export default client
