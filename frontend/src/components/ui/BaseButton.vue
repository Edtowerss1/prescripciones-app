<script setup lang="ts">
interface Props { variant?: 'primary' | 'secondary' | 'danger' | 'ghost'; loading?: boolean; disabled?: boolean; type?: 'button' | 'submit' }
withDefaults(defineProps<Props>(), { variant: 'primary', loading: false, disabled: false, type: 'button' })
const emit = defineEmits<{ click: [event: MouseEvent] }>()
const variants: Record<string, string> = {
  primary: 'bg-indigo-600 text-white hover:bg-indigo-500 focus:ring-indigo-500',
  secondary: 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-indigo-500',
  danger: 'bg-red-600 text-white hover:bg-red-500 focus:ring-red-500',
  ghost: 'bg-transparent text-gray-600 hover:bg-gray-100 focus:ring-indigo-500',
}
</script>

<template>
  <button
    :type="type" :disabled="disabled || loading"
    class="inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 transition-colors"
    :class="variants[variant]"
    @click="!loading && !disabled && emit('click', $event)"
  >
    <svg v-if="loading" class="-ml-1 mr-2 h-4 w-4 animate-spin text-current" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
    </svg>
    <slot />
  </button>
</template>
