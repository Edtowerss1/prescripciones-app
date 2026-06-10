<script setup lang="ts">
interface Props { size?: 'sm' | 'md' | 'lg'; label?: string }
withDefaults(defineProps<Props>(), { size: 'md', label: 'Loading' })
const sizes: Record<string, string> = { sm: 'h-4 w-4 border-2', md: 'h-8 w-8 border-[3px]', lg: 'h-12 w-12 border-4' }
</script>

<template>
  <div class="flex items-center justify-center" role="status">
    <span class="inline-block animate-spin rounded-full border-indigo-600 border-t-transparent" :class="sizes[size]" :aria-label="label" />
    <span v-if="label" class="ml-2 text-sm text-gray-500">{{ label }}</span>
  </div>
</template>
