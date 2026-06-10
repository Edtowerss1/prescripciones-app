<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import {
  Chart,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js'

Chart.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

interface DoctorData {
  doctor_name: string
  count: number
}

interface Props {
  data?: DoctorData[]
}

const props = withDefaults(defineProps<Props>(), {
  data: () => [],
})

const canvasRef = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart<'bar', number[], string> | null = null

function truncateName(name: string, maxLen = 20): string {
  return name.length > maxLen ? name.slice(0, maxLen) + '...' : name
}

function renderChart() {
  if (!canvasRef.value) return

  if (chartInstance) {
    chartInstance.destroy()
  }

  const sorted = [...props.data].sort((a, b) => b.count - a.count)
  const hasData = sorted.length > 0
  const labels = hasData
    ? sorted.map((d) => truncateName(d.doctor_name))
    : ['No Data']
  const values = hasData ? sorted.map((d) => d.count) : [0]

  chartInstance = new Chart(canvasRef.value, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: 'Prescriptions',
          data: values,
          backgroundColor: hasData ? '#F59E0B' : '#E5E7EB',
          borderRadius: 4,
        },
      ],
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { display: false },
        tooltip: { enabled: hasData },
      },
      scales: {
        x: {
          beginAtZero: true,
          ticks: {
            precision: 0,
          },
        },
      },
    },
  })
}

onMounted(renderChart)
watch(() => props.data, renderChart, { deep: true })
</script>

<template>
  <div class="flex items-center justify-center">
    <canvas ref="canvasRef" />
  </div>
</template>
