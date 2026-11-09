<script setup lang="ts">
import type { CheckboxRootEmits, CheckboxRootProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { Check, Minus } from "@lucide/vue"
import { computed, ref } from "vue"
import { CheckboxIndicator, CheckboxRoot } from "reka-ui"
import { cn } from "@/lib/utils"

const props = withDefaults(
  defineProps<
    CheckboxRootProps & {
      checked?: boolean | "indeterminate"
      class?: HTMLAttributes["class"]
    }
  >(),
  {
    modelValue: undefined,
    checked: undefined,
  },
)

const emits = defineEmits<
  CheckboxRootEmits & {
    "update:checked": [value: boolean | "indeterminate"]
  }
>()

const internalValue = ref<boolean | "indeterminate">(props.defaultValue ?? false)

const resolvedValue = computed(() => {
  if (props.modelValue !== undefined) return props.modelValue
  if (props.checked !== undefined) return props.checked
  return internalValue.value
})

const handleUpdate = (val: boolean | "indeterminate") => {
  internalValue.value = val
  emits("update:modelValue", val)
  emits("update:checked", val)
}
</script>

<template>
  <CheckboxRoot
    v-slot="slotProps"
    data-slot="checkbox"
    :model-value="resolvedValue"
    :default-value="props.defaultValue"
    :disabled="props.disabled"
    :required="props.required"
    :name="props.name"
    :value="props.value"
    :id="props.id"
    @update:model-value="handleUpdate"
    :class="
      cn(
        'peer size-4 shrink-0 rounded-[4px] border border-primary bg-white text-primary-foreground transition-all outline-none cursor-pointer hover:bg-primary/10 focus-visible:ring-2 focus-visible:ring-primary/30 data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground data-[state=checked]:border-primary data-[state=indeterminate]:bg-primary data-[state=indeterminate]:text-primary-foreground data-[state=indeterminate]:border-primary disabled:cursor-not-allowed disabled:opacity-50',
        props.class,
      )
    "
  >
    <CheckboxIndicator
      data-slot="checkbox-indicator"
      class="grid place-content-center text-current transition-none"
    >
      <slot v-bind="slotProps">
        <Minus v-if="slotProps.state === 'indeterminate'" class="size-3 stroke-[2.5]" />
        <Check v-else class="size-3 stroke-[2.5]" />
      </slot>
    </CheckboxIndicator>
  </CheckboxRoot>
</template>
