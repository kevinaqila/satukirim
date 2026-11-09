<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

type Props = {
    layout?: 'horizontal' | 'vertical';
    variant?: 'color' | 'white' | 'dark' | 'auto';
    size?: 'sm' | 'md' | 'lg' | 'xl';
    showTagline?: boolean;
    badgeIcon?: boolean;
    className?: HTMLAttributes['class'];
    iconClass?: HTMLAttributes['class'];
};

const props = withDefaults(defineProps<Props>(), {
    layout: 'horizontal',
    variant: 'color',
    size: 'md',
    showTagline: false,
    badgeIcon: false,
});

const iconClasses = computed(() => {
    if (props.iconClass) {
        return props.iconClass;
    }
    switch (props.size) {
        case 'sm':
            return props.layout === 'vertical' ? 'h-8 w-auto' : 'h-6 w-auto';
        case 'lg':
            return props.layout === 'vertical' ? 'h-18 w-auto' : 'h-12 w-auto';
        case 'xl':
            return props.layout === 'vertical' ? 'h-24 w-auto' : 'h-16 w-auto';
        case 'md':
        default:
            return props.layout === 'vertical' ? 'h-14 w-auto' : 'h-7.5 w-auto';
    }
});

const textClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'text-base sm:text-lg';
        case 'lg':
            return props.layout === 'vertical' ? 'text-2xl sm:text-3xl' : 'text-xl sm:text-2xl';
        case 'xl':
            return props.layout === 'vertical' ? 'text-3xl sm:text-4xl' : 'text-2xl sm:text-3xl';
        case 'md':
        default:
            return props.layout === 'vertical' ? 'text-xl' : 'text-lg sm:text-xl';
    }
});

const textColorClasses = computed(() => {
    if (props.variant === 'white') {
        return 'text-white';
    }
    if (props.variant === 'dark') {
        return 'text-[#2C2C2C]';
    }
 return'text-[#2C2C2C]';
});

const taglineColorClasses = computed(() => {
    if (props.variant === 'white') {
        return 'text-white/80';
    }
    return 'text-[#853953]';
});
</script>

<template>
    <div
        :class="[
            'app-brand-lockup inline-flex select-none transition-opacity duration-200',
            props.layout === 'vertical'
                ? 'flex-col items-center justify-center text-center gap-2.5'
                : 'flex-row items-center gap-3',
            props.className,
        ]"
    >
        <div
            v-if="props.badgeIcon"
            class="size-8.5 rounded-lg bg-white p-1 flex items-center justify-center shrink-0 shadow-2xs transition-transform duration-200 group-hover:scale-105"
        >
            <AppLogoIcon
                variant="color"
                class="h-5 w-auto"
            />
        </div>
        <AppLogoIcon
            v-else
            variant="color"
            :class="[iconClasses, 'shrink-0 transition-transform duration-200 group-hover:scale-105']"
        />

        <div
            :class="[
                'flex flex-col leading-none font-[\'Mukta\',sans-serif] group-data-[collapsible=icon]:hidden',
                props.layout === 'vertical' ? 'items-center text-center' : 'items-start text-left',
            ]"
        >
            <span
                :class="[
                    'font-extrabold tracking-tight',
                    textClasses,
                    textColorClasses,
                ]"
            >
                SatuKirim
            </span>
            <span
                v-if="props.showTagline"
                :class="[
                    'mt-1 text-[10px] sm:text-[11px] font-semibold tracking-wider uppercase',
                    taglineColorClasses,
                ]"
            >
                Platform Pengiriman UMKM
            </span>
        </div>
    </div>
</template>
