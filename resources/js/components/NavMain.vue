<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

withDefaults(
    defineProps<{
        items: NavItem[];
        label?: string;
    }>(),
    {
        label: 'Menu Utama',
    }
);

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

const isActive = (href: string) => {
    if (href === '/shipments/create') {
        return isCurrentUrl('/shipments/create');
    }
    if (href === '/shipments') {
        return isCurrentUrl('/shipments') || (isCurrentOrParentUrl('/shipments') && !isCurrentUrl('/shipments/create'));
    }
    if (href === dashboard()) {
        return isCurrentUrl(dashboard());
    }
    return isCurrentOrParentUrl(href);
};
</script>

<template>
    <SidebarGroup class="px-2 py-2">
        <SidebarGroupLabel class="text-stone-500 font-semibold text-[11px] tracking-wider uppercase px-2 mb-1">
            {{ label }}
        </SidebarGroupLabel>
        <SidebarMenu class="space-y-1">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isActive(item.href)"
                    :tooltip="item.title"
                    class="rounded-lg text-stone-600 hover:text-stone-900 hover:bg-stone-100 active:bg-stone-200/80 data-[active=true]:bg-[#853953] data-[active=true]:text-white data-[active=true]:font-semibold data-[active=true]:shadow-xs hover:data-[active=true]:bg-[#702b42] hover:data-[active=true]:text-white transition-all duration-150"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
