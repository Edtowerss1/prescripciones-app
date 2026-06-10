<script setup lang="ts">
interface Props { message: string; actionLabel?: string; icon?: string }
withDefaults(defineProps<Props>(), { icon: '📋' })
const emit = defineEmits<{ action: [] }>()
</script>

<template>
  <div class="flex flex-col items-center justify-center py-12 text-center">
    <span class="mb-4 text-4xl">{{ icon }}</span>
    <p class="mb-4 text-sm text-gray-500">{{ message }}</p>
    <button v-if="actionLabel" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" @click="emit('action')">
      {{ actionLabel }}
    </button>
    <slot />
  </div>
</template>
