<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
interface Props { open: boolean; title: string }
defineProps<Props>()
const emit = defineEmits<{ close: [] }>()
function onEscape(e: KeyboardEvent) { if (e.key === 'Escape') emit('close') }
onMounted(() => window.addEventListener('keydown', onEscape))
onUnmounted(() => window.removeEventListener('keydown', onEscape))
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center" @click.self="emit('close')">
        <div class="fixed inset-0 bg-black/50" />
        <div class="relative z-10 mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">{{ title }}</h2>
            <button class="text-gray-400 hover:text-gray-600" @click="emit('close')">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>
          <div class="mb-6"><slot /></div>
          <div v-if="$slots.actions" class="flex justify-end gap-3"><slot name="actions" /></div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
