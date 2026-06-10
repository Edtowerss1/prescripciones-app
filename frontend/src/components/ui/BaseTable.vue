<script setup lang="ts">
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
interface Column { key: string; label: string; sortable?: boolean }
interface Props { columns: Column[]; rows: Record<string, any>[]; loading?: boolean; emptyMessage?: string }
withDefaults(defineProps<Props>(), { loading: false, emptyMessage: 'No data available.' })
const emit = defineEmits<{ sort: [key: string] }>()
</script>

<template>
  <div class="overflow-x-auto rounded-lg border border-gray-200">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th v-for="col in columns" :key="col.key" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
            <button v-if="col.sortable" class="flex items-center gap-1 hover:text-gray-700" @click="emit('sort', col.key)">
              {{ col.label }}
              <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>
            </button>
            <span v-else>{{ col.label }}</span>
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 bg-white">
        <tr v-if="loading">
          <td :colspan="columns.length" class="px-4 py-8"><LoadingSpinner size="md" /></td>
        </tr>
        <tr v-else-if="rows.length === 0">
          <td :colspan="columns.length" class="px-4 py-8 text-center text-sm text-gray-500">{{ emptyMessage }}</td>
        </tr>
        <tr v-for="(row, ri) in rows" v-else :key="ri" class="hover:bg-gray-50">
          <td v-for="col in columns" :key="col.key" class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
            <slot :name="'column-' + col.key" :row="row" :value="row[col.key]">{{ row[col.key] }}</slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
