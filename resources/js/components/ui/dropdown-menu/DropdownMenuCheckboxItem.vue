<script setup lang="ts">
import type { DropdownMenuCheckboxItemEmits, DropdownMenuCheckboxItemProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { Check } from "@lucide/vue"
import { computed } from "vue"
import {
  DropdownMenuCheckboxItem,
  DropdownMenuItemIndicator,
} from "reka-ui"
import { cn } from "@/lib/utils"

const props = withDefaults(
  defineProps<
    DropdownMenuCheckboxItemProps & {
      checked?: boolean | "indeterminate"
      class?: HTMLAttributes["class"]
    }
  >(),
  { modelValue: undefined, checked: undefined },
)

const emits = defineEmits<
  DropdownMenuCheckboxItemEmits & {
    "update:checked": [value: boolean]
  }
>()

const resolvedValue = computed(() =>
  props.modelValue !== undefined ? props.modelValue : props.checked,
)

const handleUpdate = (val: boolean) => {
  emits("update:modelValue", val)
  emits("update:checked", val)
}
</script>

<template>
  <DropdownMenuCheckboxItem
    data-slot="dropdown-menu-checkbox-item"
    :model-value="resolvedValue"
    :disabled="props.disabled"
    :text-value="props.textValue"
    @update:model-value="handleUpdate"
    @select="emits('select', $event)"
    :class=" cn(
      'focus:bg-accent focus:text-accent-foreground relative flex cursor-pointer items-center gap-2 rounded-sm py-1.5 pr-2 pl-8 text-xs font-medium outline-hidden select-none data-[disabled]:pointer-events-none data-[disabled]:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=\'size-\'])]:size-3.5',
      props.class,
    )"
  >
    <span class="pointer-events-none absolute left-2 flex size-3.5 items-center justify-center text-primary">
      <DropdownMenuItemIndicator>
        <slot name="indicator-icon">
          <Check class="size-3.5 stroke-[2.5]" />
        </slot>
      </DropdownMenuItemIndicator>
    </span>
    <slot />
  </DropdownMenuCheckboxItem>
</template>
