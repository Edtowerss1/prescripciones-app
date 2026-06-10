<script setup lang="ts">
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'

interface PrescriptionItemForm {
  name: string
  dosage: string
  quantity: number | string
  instructions: string
}

interface Props {
  modelValue: PrescriptionItemForm[]
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [items: PrescriptionItemForm[]]
}>()

function addItem() {
  const items = [
    ...props.modelValue,
    { name: '', dosage: '', quantity: '', instructions: '' },
  ]
  emit('update:modelValue', items)
}

function removeItem(index: number) {
  const items = props.modelValue.filter((_, i) => i !== index)
  emit('update:modelValue', items)
}

function updateItem(
  index: number,
  field: keyof PrescriptionItemForm,
  value: string,
) {
  const items = props.modelValue.map((item, i) => {
    if (i === index) {
      return { ...item, [field]: value }
    }
    return item
  })
  emit('update:modelValue', items)
}
</script>

<template>
  <div>
    <div class="mb-2 flex items-center justify-between">
      <label class="block text-sm font-medium text-gray-700">
        Prescription Items
      </label>
      <BaseButton variant="secondary" type="button" @click="addItem">
        + Add Item
      </BaseButton>
    </div>

    <div
      v-for="(item, index) in modelValue"
      :key="index"
      class="mb-4 rounded-lg border border-gray-200 p-4"
    >
      <div class="mb-2 flex items-center justify-between">
        <span class="text-sm font-medium text-gray-600">
          Item {{ index + 1 }}
        </span>
        <button
          type="button"
          class="text-sm text-red-600 hover:text-red-500"
          @click="removeItem(index)"
        >
          Remove
        </button>
      </div>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <BaseInput
          :model-value="item.name"
          label="Name"
          placeholder="Medication name"
          @update:model-value="updateItem(index, 'name', $event)"
        />
        <BaseInput
          :model-value="item.dosage"
          label="Dosage"
          placeholder="e.g. 500mg"
          @update:model-value="updateItem(index, 'dosage', $event)"
        />
        <BaseInput
          :model-value="String(item.quantity)"
          label="Quantity"
          type="number"
          placeholder="e.g. 30"
          @update:model-value="updateItem(index, 'quantity', $event)"
        />
        <BaseInput
          :model-value="item.instructions"
          label="Instructions"
          placeholder="e.g. Take twice daily"
          @update:model-value="
            updateItem(index, 'instructions', $event)
          "
        />
      </div>
    </div>

    <p
      v-if="modelValue.length === 0"
      class="py-4 text-center text-sm text-gray-400"
    >
      No items added yet. Click "Add Item" to add a prescription item.
    </p>
  </div>
</template>
