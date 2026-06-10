<script setup lang="ts">
interface Props { status: 'pending' | 'consumed' }
defineProps<Props>()
const cfg: Record<string, { label: string; cls: string }> = {
  pending: { label: 'Pending', cls: 'bg-yellow-100 text-yellow-800' },
  consumed: { label: 'Consumed', cls: 'bg-green-100 text-green-800' },
}
</script>

<template>
  <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="cfg[status]?.cls ?? 'bg-gray-100 text-gray-800'">
    {{ cfg[status]?.label ?? status }}
  </span>
</template>
