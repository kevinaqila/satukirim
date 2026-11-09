<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <thead
    data-slot="table-header"
    :class="cn('bg-stone-50/90 [&_tr]:border-b [&_tr]:border-stone-200', props.class)"
  >
    <slot />
  </thead>
</template>
