<script setup lang="ts">
import BaseToast from '@/components/ui/BaseToast.vue'
import { useToast } from '@/composables/useToast'
const { toasts, removeToast } = useToast()
</script>

<template>
  <div class="pointer-events-none fixed right-4 top-4 z-[100] flex flex-col gap-2">
    <TransitionGroup name="toast">
      <BaseToast v-for="t in toasts" :key="t.id" :message="t.message" :type="t.type" :id="t.id" @dismiss="removeToast" />
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active { transition: all 0.3s ease-out; }
.toast-leave-active { transition: all 0.2s ease-in; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(30px); }
</style>
