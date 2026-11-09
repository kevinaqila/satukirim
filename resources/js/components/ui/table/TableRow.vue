<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <tr
    data-slot="table-row"
    :class="cn('border-b border-stone-200/70 transition-colors hover:bg-stone-50/80 data-[state=selected]:bg-[#853953]/[0.05]', props.class)"
  >
    <slot />
  </tr>
</template>
