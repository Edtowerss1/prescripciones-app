import { ref } from 'vue'

interface Toast { id: number; message: string; type: 'success' | 'error' | 'info' }

const toasts = ref<Toast[]>([])
let nextId = 1

export function useToast() {
  function addToast(message: string, type: Toast['type'] = 'info') {
    const id = nextId++
    toasts.value.push({ id, message, type })
    return id
  }
  function removeToast(id: number) {
    const idx = toasts.value.findIndex(t => t.id === id)
    if (idx !== -1) toasts.value.splice(idx, 1)
  }
  function clearAll() { toasts.value = [] }
  return { toasts, addToast, removeToast, clearAll }
}
