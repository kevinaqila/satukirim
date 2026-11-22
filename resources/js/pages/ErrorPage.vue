<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import { Button } from '@/components/ui/button';
import { dashboard, home } from '@/routes';
import { index as shipmentsIndex } from '@/routes/shipments';
import { index as storesIndex } from '@/routes/stores';
import { index as walletIndex } from '@/routes/wallet';

const props = defineProps<{
    status: number;
}>();

const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));
const currentPath = computed(() => (page.url ?? '/').split('?')[0]);

const sectionContext = computed(() => {
    if (!isAuthenticated.value) {
        return null;
    }

    const path = currentPath.value;
    const sections = [
        {
            prefix: '/shipments/',
            label: 'Lihat Daftar Pengiriman',
            href: shipmentsIndex(),
            notFoundDescription: 'Data pengiriman yang Anda cari tidak ditemukan atau telah dihapus.',
            forbiddenDescription: 'Akun Anda tidak memiliki izin untuk membuka data pengiriman ini.',
        },
        {
            prefix: '/stores/',
            label: 'Kelola Daftar Toko',
            href: storesIndex(),
            notFoundDescription: 'Data toko yang Anda cari tidak ditemukan atau telah dihapus.',
            forbiddenDescription: 'Akun Anda tidak memiliki izin untuk mengakses data toko ini.',
        },
        {
            prefix: '/wallet/',
            label: 'Buka Dompet Saya',
            href: walletIndex(),
            notFoundDescription: 'Halaman dompet yang Anda tuju tidak tersedia.',
            forbiddenDescription: 'Akun Anda tidak memiliki izin untuk mengakses halaman ini.',
        },
    ];

    return sections.find((section) => path.startsWith(section.prefix)) ?? null;
});

const content = computed(() => {
    const context = sectionContext.value;

    if (props.status === 403) {
        return {
            code: '403',
            title: 'Akses tidak diizinkan',
            description:
                context?.forbiddenDescription ??
                'Akun Anda tidak memiliki izin untuk membuka halaman ini.',
            background: '/images/errors/bg-403.jpg',
        };
    }

    return {
        code: '404',
        title: 'Halaman tidak ditemukan',
        description:
            context?.notFoundDescription ??
            'Halaman yang Anda buka tidak tersedia, alamat URL keliru, atau sudah dipindahkan.',
        background: '/images/errors/bg-404.jpg',
    };
});

function goBack(): void {
    if (window.history.length > 1) {
        window.history.back();

        return;
    }

    window.location.href = isAuthenticated.value ? dashboard.url() : home.url();
}
</script>

<template>
    <div class="relative flex min-h-svh items-center overflow-hidden bg-background px-6 py-12 text-foreground sm:px-10 lg:px-16">
        <Head :title="`${content.code} - ${content.title}`" />

        <img
            :src="content.background"
            alt=""
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 h-full w-full object-cover object-[75%_center] opacity-30 sm:object-right sm:opacity-90"
        />
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 bg-linear-to-t from-background via-background/85 to-background/45 sm:bg-linear-to-r sm:from-background/85 sm:from-15% sm:via-background/40 sm:via-45% sm:to-transparent"
        />

        <main class="relative z-10 mx-auto w-full max-w-6xl">
            <div class="max-w-lg sm:max-w-xl">
                <Link :href="isAuthenticated ? dashboard() : home()" class="inline-flex items-center">
                    <AppLogo layout="horizontal" size="lg" />
                </Link>

                <p class="mt-8 text-lg font-bold tracking-tight text-primary sm:text-xl">
                    {{ content.code }}
                </p>

                <h1 class="mt-2 text-3xl leading-[1.15] font-extrabold tracking-tight text-foreground sm:text-4xl lg:text-[2.75rem]">
                    {{ content.title }}
                </h1>

                <p class="mt-3.5 text-base leading-relaxed text-foreground/75 sm:text-lg">
                    {{ content.description }}
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <Button as-child size="lg" class="w-full sm:w-auto">
                        <Link :href="isAuthenticated ? dashboard() : home()">
                            {{ isAuthenticated ? 'Kembali ke Dashboard' : 'Ke Beranda' }}
                        </Link>
                    </Button>

                    <Button
                        type="button"
                        size="lg"
                        variant="outline"
                        class="w-full cursor-pointer sm:w-auto"
                        @click="goBack"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span>Kembali</span>
                    </Button>
                </div>

                <div v-if="sectionContext" class="mt-6">
                    <Link
                        :href="sectionContext.href"
                        class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary transition-colors hover:text-[#612D53] sm:text-base"
                    >
                        <span>{{ sectionContext.label }}</span>
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </main>
    </div>
</template>
