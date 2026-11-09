<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    PackagePlus,
    PanelLeftOpen,
    ReceiptText,
    ShieldCheck,
    Store,
    Truck,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarTrigger,
    useSidebar,
} from '@/components/ui/sidebar';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const { state, toggleSidebar } = useSidebar();
const page = usePage();
const user = computed(() => page.props.auth?.user);
const isSuperAdmin = computed(() => user.value?.role === 'super_admin');

const shippingNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Pengiriman',
        href: '/shipments',
        icon: Truck,
    },
    {
        title: 'Kirim Paket',
        href: '/shipments/create',
        icon: PackagePlus,
    },
];

const businessNavItems: NavItem[] = [
    {
        title: 'Dompet Saya',
        href: '/wallet',
        icon: Wallet,
    },
    {
        title: 'Toko Saya',
        href: '/stores',
        icon: Store,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Dashboard Admin',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Kelola Pengguna',
        href: '/admin/users',
        icon: Users,
    },
    {
        title: 'Semua Pengiriman',
        href: '/admin/transactions',
        icon: ReceiptText,
    },
    {
        title: 'Audit Log',
        href: '/admin/audit-logs',
        icon: ShieldCheck,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">
        <SidebarHeader class="h-14 justify-center bg-[#853953] text-white">
            <SidebarMenu>
                <SidebarMenuItem v-if="state === 'collapsed'" class="flex items-center justify-center">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <button
                                type="button"
                                class="flex size-8 items-center justify-center rounded-lg bg-white text-stone-700 shadow-2xs transition-all hover:scale-105 active:scale-95 cursor-pointer"
                                aria-label="Buka Sidebar"
                                @click="toggleSidebar"
                            >
                                <AppLogoIcon variant="color" class="h-4.5 w-auto shrink-0" />
                            </button>
                        </TooltipTrigger>
                        <TooltipContent side="right" align="center">
                            Perluas Sidebar
                        </TooltipContent>
                    </Tooltip>
                </SidebarMenuItem>
                <SidebarMenuItem v-else class="flex items-center justify-between">
                    <SidebarMenuButton size="lg" as-child class="flex-1 text-white hover:text-white hover:bg-white/10 active:bg-white/15">
                        <Link :href="dashboard()" class="flex items-center gap-2">
                            <AppLogo variant="white" size="sm" :badge-icon="true" />
                        </Link>
                    </SidebarMenuButton>
                    <SidebarTrigger
                        class="hidden md:inline-flex text-white/80 hover:text-white hover:bg-white/10"
                    />
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <template v-if="!isSuperAdmin">
                <NavMain :items="shippingNavItems" label="Pengiriman" />
                <NavMain :items="businessNavItems" label="Bisnis & Toko" />
            </template>
            <NavMain v-if="isSuperAdmin" :items="adminNavItems" label="Panel Super Admin" />
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu v-if="state === 'collapsed'">
                <SidebarMenuItem>
                    <SidebarMenuButton
                        as-child
                        tooltip="Perluas Sidebar"
                        class="cursor-pointer text-stone-500 hover:text-stone-900 hover:bg-sidebar-accent"
                        @click="toggleSidebar"
                    >
                        <button type="button" class="flex w-full items-center gap-2">
                            <PanelLeftOpen class="size-4 shrink-0" />
                        </button>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
