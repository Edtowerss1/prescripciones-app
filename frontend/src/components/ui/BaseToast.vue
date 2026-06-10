<script setup lang="ts">
import { onMounted } from 'vue'
interface Props { message: string; type: 'success' | 'error' | 'info'; id: string | number; duration?: number }
const props = withDefaults(defineProps<Props>(), { duration: 5000 })
const emit = defineEmits<{ dismiss: [id: string | number] }>()
const styles: Record<string, string> = {
  success: 'bg-green-50 text-green-800 border-green-200',
  error: 'bg-red-50 text-red-800 border-red-200',
  info: 'bg-blue-50 text-blue-800 border-blue-200',
}
onMounted(() => { if (props.duration > 0) setTimeout(() => emit('dismiss', props.id), props.duration) })
</script>

<template>
  <div class="pointer-events-auto flex items-center gap-2 rounded-lg border px-4 py-3 shadow-md" :class="styles[type]">
    <span class="text-sm">{{ message }}</span>
    <button class="ml-auto text-current opacity-60 hover:opacity-100" @click="emit('dismiss', id)">
      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
    </button>
  </div>
</template>
