<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import {
  Chart,
  BarController,
  CategoryScale,
  LinearScale,
  BarElement,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js'

Chart.register(
  BarController,
  CategoryScale,
  LinearScale,
  BarElement,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
)

interface DayData {
  date: string
  count: number
}

interface Props {
  data?: DayData[]
}

const props = withDefaults(defineProps<Props>(), {
  data: () => [],
})

const canvasRef = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart<'bar', number[], string> | null = null

function formatDate(dateStr: string): string {
  const d = new Date(dateStr + 'T00:00:00')
  return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}

function renderChart() {
  if (!canvasRef.value) return

  if (chartInstance) {
    chartInstance.destroy()
  }

  const hasData = props.data.length > 0
  const labels = hasData
    ? props.data.map((d) => formatDate(d.date))
    : ['No Data']
  const values = hasData
    ? props.data.map((d) => d.count)
    : [0]

  chartInstance = new Chart(canvasRef.value, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: 'Prescriptions',
          data: values,
          backgroundColor: hasData ? '#6366F1' : '#E5E7EB',
          borderRadius: 4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { enabled: hasData },
      },
      scales: {
        y: {
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
  <div class="flex min-h-64 items-center justify-center">
    <canvas ref="canvasRef" />
  </div>
</template>
