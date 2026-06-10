import { ref } from 'vue'

export interface FilterState {
  status: string
  from: string
  to: string
  search: string
}

export function useFilters() {
  const status = ref('')
  const from = ref('')
  const to = ref('')
  const search = ref('')

  function setFilter(key: keyof FilterState, value: string) {
    switch (key) {
      case 'status':
        status.value = value
        break
      case 'from':
        from.value = value
        break
      case 'to':
        to.value = value
        break
      case 'search':
        search.value = value
        break
    }
  }

  function setFilters(filters: Partial<FilterState>) {
    if (filters.status !== undefined) status.value = filters.status
    if (filters.from !== undefined) from.value = filters.from
    if (filters.to !== undefined) to.value = filters.to
    if (filters.search !== undefined) search.value = filters.search
  }

  function clearFilters() {
    status.value = ''
    from.value = ''
    to.value = ''
    search.value = ''
  }

  function buildQueryParams(): Record<string, string | number> {
    const params: Record<string, string | number> = {}
    if (status.value) params.status = status.value
    if (from.value) params.from = from.value
    if (to.value) params.to = to.value
    if (search.value) params.query = search.value
    return params
  }

  return {
    status,
    from,
    to,
    search,
    setFilter,
    setFilters,
    clearFilters,
    buildQueryParams,
  }
}
