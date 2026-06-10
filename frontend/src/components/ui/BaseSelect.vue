<script setup lang="ts">
interface Option { value: string | number; label: string }
interface Props { modelValue: string | number; label?: string; options: Option[]; placeholder?: string; error?: string | null; disabled?: boolean }
const props = withDefaults(defineProps<Props>(), { placeholder: 'Select...', error: null, disabled: false })
const emit = defineEmits<{ 'update:modelValue': [value: string | number] }>()
</script>

<template>
  <div>
    <label v-if="label" class="mb-1 block text-sm font-medium text-gray-700">{{ label }}</label>
    <select
      :value="modelValue" :disabled="disabled"
      class="block w-full rounded-md border px-3 py-2 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1"
      :class="error
        ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
        : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'"
      @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
    >
      <option value="" disabled>{{ placeholder }}</option>
      <option v-for="opt in options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
    </select>
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
  </div>
</template>
