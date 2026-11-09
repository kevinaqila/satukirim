<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { useVModel } from "@vueuse/core"
import { cn } from "@/lib/utils"

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  class?: HTMLAttributes["class"]
}>()

const emits = defineEmits<{
  (e: "update:modelValue", payload: string | number): void
}>()

const modelValue = useVModel(props, "modelValue", emits, {
  passive: true,
  defaultValue: props.defaultValue,
})
</script>

<template>
  <input
    v-model="modelValue"
    data-slot="input"
    :class="cn(
      'file:text-foreground placeholder:text-stone-400 selection:bg-[#853953] selection:text-white border-stone-200 h-10 w-full min-w-0 rounded-xl border bg-white px-3.5 py-2 text-base text-[#2C2C2C] shadow-xs transition-colors outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
      'focus-visible:border-[#853953] focus-visible:ring-[#853953]/20 focus-visible:ring-[3px]',
'aria-invalid:ring-destructive/20 aria-invalid:border-destructive',
      props.class,
    )"
  >
</template>
