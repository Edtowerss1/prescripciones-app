<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { Chart, ArcElement, Tooltip, Legend } from 'chart.js'

Chart.register(ArcElement, Tooltip, Legend)

interface Props {
  pending: number
  consumed: number
}

const props = withDefaults(defineProps<Props>(), {
  pending: 0,
  consumed: 0,
})

const canvasRef = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart<'doughnut', number[], string> | null = null

function renderChart() {
  if (!canvasRef.value) return

  if (chartInstance) {
    chartInstance.destroy()
  }

  const hasData = props.pending > 0 || props.consumed > 0

  chartInstance = new Chart(canvasRef.value, {
    type: 'doughnut',
    data: {
      labels: hasData ? ['Pending', 'Consumed'] : ['No Data'],
      datasets: [
        {
          data: hasData ? [props.pending, props.consumed] : [1],
          backgroundColor: hasData
            ? ['#F59E0B', '#10B981']
            : ['#E5E7EB'],
          borderWidth: 0,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          position: 'bottom',
        },
        tooltip: {
          enabled: hasData,
        },
      },
    },
  })
}

onMounted(renderChart)
watch([() => props.pending, () => props.consumed], renderChart)
</script>

<template>
  <div class="flex items-center justify-center">
    <canvas ref="canvasRef" />
  </div>
</template>
