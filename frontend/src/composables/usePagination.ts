import { ref, computed } from 'vue'

export function usePagination() {
  const page = ref(1)
  const limit = ref(15)
  const total = ref(0)
  const lastPage = ref(1)

  const from = computed(() => (page.value - 1) * limit.value + 1)
  const to = computed(() => Math.min(page.value * limit.value, total.value))

  function parseMeta(meta: {
    current_page: number
    last_page: number
    total: number
    per_page: number
  }) {
    page.value = meta.current_page
    lastPage.value = meta.last_page
    total.value = meta.total
    limit.value = meta.per_page
  }

  function nextPage() {
    if (page.value < lastPage.value) {
      page.value++
    }
  }

  function prevPage() {
    if (page.value > 1) {
      page.value--
    }
  }

  function goToPage(p: number) {
    if (p >= 1 && p <= lastPage.value) {
      page.value = p
    }
  }

  function setLimit(newLimit: number) {
    limit.value = newLimit
    page.value = 1
  }

  return {
    page,
    limit,
    total,
    lastPage,
    from,
    to,
    parseMeta,
    nextPage,
    prevPage,
    goToPage,
    setLimit,
  }
}
