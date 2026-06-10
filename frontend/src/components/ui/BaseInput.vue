<script setup lang="ts">
interface Props { modelValue: string; label?: string; type?: string; placeholder?: string; error?: string | null; disabled?: boolean }
const props = withDefaults(defineProps<Props>(), { type: 'text', placeholder: '', error: null, disabled: false })
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()
</script>

<template>
  <div>
    <label v-if="label" class="mb-1 block text-sm font-medium text-gray-700">{{ label }}</label>
    <input
      :type="type" :value="modelValue" :placeholder="placeholder" :disabled="disabled"
      class="block w-full rounded-md border px-3 py-2 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1"
      :class="error
        ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
        : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'"
      @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
  </div>
</template>
