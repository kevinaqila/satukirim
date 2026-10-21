<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import { home } from '@/routes';

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        align?: 'start' | 'center';
    }>(),
    {
        align: 'center',
    }
);
</script>

<template>
    <div class="min-h-svh lg:h-svh w-full grid lg:grid-cols-2 bg-white lg:overflow-hidden">
        <div class="relative hidden lg:flex h-full flex-col justify-end overflow-hidden bg-stone-900 p-10 xl:p-14 2xl:p-16 text-white select-none">
            <img
                src="/images/auth/auth_parcels.jpg"
                alt="Pengiriman Paket SatuKirim"
                class="absolute inset-0 h-full w-full object-cover object-center transition-transform duration-700 hover:scale-105"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-[#2C2C2C]/95 via-[#2C2C2C]/40 to-transparent" />

            <div class="relative z-10 space-y-3 max-w-lg">
                <h2 class="text-2xl xl:text-3xl 2xl:text-4xl font-extrabold leading-tight text-white tracking-tight">
                    Kirim paket ke seluruh Indonesia lebih hemat dan mudah.
                </h2>
                <p class="text-sm xl:text-base text-stone-200 leading-relaxed font-normal">
                    Bandingkan ongkir ekspedisi ternama, cetak label pengiriman instan, dan pantau nomor resi otomatis dalam satu sistem terpadu.
                </p>
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 pt-2 text-xs xl:text-sm text-stone-200">
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <span class="text-[#f3cbd7] font-bold">✓</span> Cek Ongkir Otomatis
                    </span>
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <span class="text-[#f3cbd7] font-bold">✓</span> Gratis Jemput Paket
                    </span>
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <span class="text-[#f3cbd7] font-bold">✓</span> Multi-Ekspedisi Resmi
                    </span>
                </div>
            </div>
        </div>

        <div
            :class="[
                'relative flex min-h-svh lg:h-full flex-col items-center px-4 sm:px-8 lg:px-12 bg-white overflow-y-auto',
                props.align === 'center'
                    ? 'justify-center py-8 sm:py-12'
                    : 'justify-start pt-6 pb-8 sm:pt-8 sm:pb-10',
            ]"
        >
            <div class="w-full max-w-[520px] mx-auto">
                <div class="flex flex-col items-start text-left mb-4 sm:mb-5">
                    <Link :href="home()" class="group mb-4.5 inline-flex transition-transform hover:scale-102">
                        <AppLogo layout="horizontal" size="lg" />
                    </Link>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-[#2C2C2C] tracking-tight leading-tight">{{ title }}</h1>
                    <p v-if="description" class="text-sm sm:text-base text-stone-500 mt-1 leading-relaxed">{{ description }}</p>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
