<script setup lang="ts">
import { Link } from "@inertiajs/vue3"

export interface PaginationLink {
  url: string | null
  label: string
  active: boolean
}

defineProps<{
  links?: PaginationLink[]
  from?: number | null
  to?: number | null
  total?: number
  selectedCount?: number
}>()
</script>

<template>
  <div
    v-if="(total && total > 0) || (links && links.length > 3)"
    class="flex flex-col sm:flex-row items-center justify-between gap-2.5 px-4 py-2.5 border-t border-stone-200 bg-stone-50/60 text-sm text-stone-600"
  >
    <div class="flex items-center gap-2">
      <span
        v-if="selectedCount && selectedCount > 0"
        class="inline-flex items-center px-2.5 py-0.5 rounded bg-[#853953]/10 text-[#853953] font-semibold text-sm"
      >
        {{ selectedCount }} baris dipilih
      </span>
      <span v-if="from !== null && from !== undefined && to && total">
        Menampilkan <span class="font-semibold text-stone-900 tabular-nums">{{ from }}–{{ to }}</span> dari <span class="font-semibold text-stone-900 tabular-nums">{{ total }}</span> data
      </span>
    </div>
    <div v-if="links && links.length > 3" class="flex items-center gap-1">
      <template v-for="(link, idx) in links" :key="idx">
        <span
          v-if="!link.url"
          class="px-2.5 py-1 rounded-md text-stone-400 bg-white border border-stone-200/60 opacity-60 cursor-not-allowed select-none text-sm"
          v-html="link.label"
        />
        <Link
          v-else
          :href="link.url"
          preserve-scroll
          preserve-state
          class="px-2.5 py-1 rounded-md transition-colors text-sm tabular-nums"
          :class="
            link.active
              ? 'bg-[#853953] text-white font-semibold border border-[#853953]'
              : 'bg-white text-stone-700 hover:bg-stone-100 border border-stone-200 font-medium'
          "
          v-html="link.label"
        />
      </template>
    </div>
  </div>
</template>
