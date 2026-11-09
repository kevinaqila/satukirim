<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { dashboard } from '@/routes';

withDefaults(
    defineProps<{
        title?: string;
        showMobileLogo?: boolean;
        showMobileBrand?: boolean;
        backHref?: string;
        backMobileOnly?: boolean;
    }>(),
    {
        title: '',
        showMobileLogo: false,
        showMobileBrand: false,
        backHref: '',
        backMobileOnly: false,
    },
);
</script>

<template>
    <header class="w-full bg-[#853953] text-white shadow-xs sticky top-0 z-20">
        <div class="flex h-14 items-center justify-between px-5 sm:px-6 lg:px-8 w-full">
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                <Link
                    v-if="backHref"
                    :href="backHref"
                    :class="[
                        backMobileOnly ? 'md:hidden' : '',
                        'inline-flex size-8 items-center justify-center rounded-lg text-white hover:bg-white/10 active:scale-95 transition-colors cursor-pointer shrink-0 -ml-1.5',
                    ]"
                    aria-label="Kembali"
                >
                    <ArrowLeft class="size-5 text-white" />
                </Link>

                <Link
                    v-if="showMobileBrand"
                    :href="dashboard()"
                    class="md:hidden flex items-center shrink-0"
                    aria-label="SatuKirim"
                >
                    <AppLogo variant="white" size="sm" :badge-icon="true" />
                </Link>

                <Link
                    v-else-if="showMobileLogo && (!backHref || backMobileOnly)"
                    :href="dashboard()"
                    :class="[
                        backHref && backMobileOnly ? 'hidden' : 'md:hidden',
                        'size-8 rounded-lg bg-white p-1 flex items-center justify-center shrink-0 shadow-2xs transition-transform active:scale-95',
                    ]"
                    aria-label="Kembali ke Dashboard"
                >
                    <AppLogoIcon variant="color" class="h-4.5 w-auto" />
                </Link>

                <slot name="title">
                    <h1 class="text-base sm:text-lg lg:text-xl font-bold tracking-tight text-white truncate">
                        {{ title }}
                    </h1>
                </slot>
            </div>

            <div v-if="$slots.actions" class="flex items-center gap-2 shrink-0">
                <slot name="actions" />
            </div>
        </div>
    </header>
</template>
