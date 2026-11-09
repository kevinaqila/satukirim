<script setup lang="ts">
type Props = {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
};

withDefaults(defineProps<Props>(), {
    variant: 'default',
});
</script>

<template>
    <header :class="variant === 'small' ? 'space-y-0.5' : 'mb-6 sm:mb-8 space-y-1'">
        <h2
            :class="
                variant === 'small'
                    ? 'text-sm sm:text-base font-semibold tracking-tight text-stone-900'
                    : 'text-base sm:text-xl font-bold tracking-tight text-stone-900'
            "
        >
            {{ title }}
        </h2>
        <p v-if="description" class="text-xs sm:text-sm text-stone-500 leading-relaxed">
            {{ description }}
        </p>
    </header>
</template>
