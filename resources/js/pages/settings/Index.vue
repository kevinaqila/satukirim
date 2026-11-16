<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ChevronRight,
    Landmark,
    LogOut,
    Shield,
    Store,
    User as UserIcon,
} from '@lucide/vue';
import { computed, onMounted } from 'vue';
import AppTopBar from '@/components/AppTopBar.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { dashboard, logout } from '@/routes';
import { edit as editBank } from '@/routes/bank';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

defineOptions({
    layout: AppSidebarLayout,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

const menuItems = [
    {
        title: 'Profil Akun',
        description: 'Nama lengkap, email, dan nomor telepon',
        href: editProfile(),
        icon: UserIcon,
    },
    {
        title: 'Rekening Pencairan',
        description: 'Atur bank dan nomor rekening tujuan tarik saldo',
        href: editBank(),
        icon: Landmark,
    },
    {
        title: 'Toko Saya',
        description: 'Kelola alamat penjemputan dan profil toko',
        href: '/stores',
        icon: Store,
    },
    {
        title: 'Keamanan & Kata Sandi',
        description: 'Ubah kata sandi dan proteksi akun',
        href: editSecurity(),
        icon: Shield,
    },
];

const handleLogout = () => {
    router.flushAll();
};

onMounted(() => {
    if (typeof window !== 'undefined' && window.innerWidth >= 768) {
        router.visit(dashboard({ query: { settings: 1 } }), { replace: true });
    }
});
</script>

<template>
    <div class="flex h-full flex-1 flex-col w-full min-w-0">
        <Head title="Pengaturan Akun - SatuKirim" />

        <AppTopBar title="Pengaturan Akun" />

        <div class="px-4 py-4 sm:py-6 max-w-xl mx-auto w-full space-y-4 sm:space-y-6">
            <div class="flex items-center gap-3.5 p-4 rounded-xl bg-white border border-stone-200 text-stone-900 shadow-2xs">
                <Avatar class="size-12 rounded-full border border-stone-200 bg-stone-100 shrink-0">
                    <AvatarImage v-if="user?.avatar" :src="user.avatar" :alt="user?.name" />
                    <AvatarFallback class="font-bold text-sm bg-[#853953] text-white">
                        {{ getInitials(user?.name || '') }}
                    </AvatarFallback>
                </Avatar>
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm sm:text-base font-bold text-stone-900 truncate">
                        {{ user?.name }}
                    </h2>
                    <p class="text-xs text-stone-500 truncate">
                        {{ user?.email }}
                    </p>
                </div>
            </div>

            <div class="rounded-xl bg-white border border-stone-200 divide-y divide-stone-100 overflow-hidden shadow-2xs">
                <Link
                    v-for="item in menuItems"
                    :key="item.title"
                    :href="item.href"
                    class="flex items-center justify-between p-4 hover:bg-stone-50 active:bg-stone-100 transition-colors group cursor-pointer"
                >
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="size-9 rounded-lg bg-stone-100 flex items-center justify-center text-stone-600 group-hover:bg-[#853953]/10 group-hover:text-[#853953] transition-colors shrink-0">
                            <component :is="item.icon" class="size-4.5" />
                        </div>
                        <div class="min-w-0">
                            <span class="text-xs sm:text-sm font-semibold text-stone-900 block truncate">
                                {{ item.title }}
                            </span>
                            <span class="text-[11px] text-stone-500 block truncate">
                                {{ item.description }}
                            </span>
                        </div>
                    </div>
                    <ChevronRight class="size-4.5 text-stone-400 group-hover:text-stone-600 transition-colors shrink-0" />
                </Link>
            </div>

            <div class="pt-1">
                <Link
                    :href="logout()"
                    @click="handleLogout"
                    as="button"
                    class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-white border border-stone-200/80 hover:bg-stone-50 active:bg-stone-100 text-stone-700 hover:text-red-600 font-semibold text-xs sm:text-sm transition-colors cursor-pointer shadow-2xs"
                >
                    <LogOut class="size-4 text-stone-400" />
                    Keluar dari Akun
                </Link>
            </div>
        </div>
    </div>
</template>
