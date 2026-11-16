<script setup lang="ts">
import { onMounted } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import MobileBottomNav from '@/components/MobileBottomNav.vue';
import SettingsModal from '@/components/SettingsModal.vue';
import { Toaster } from '@/components/ui/sonner';
import { useSettingsModal } from '@/composables/useSettingsModal';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const { openSettingsModal } = useSettingsModal();

onMounted(() => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        const settingsParam = urlParams.get('settings');
        if (settingsParam) {
            const tab = settingsParam === 'security' ? 'security' : settingsParam === 'bank' ? 'bank' : 'profile';
            openSettingsModal(tab);
            urlParams.delete('settings');
            const newSearch = urlParams.toString();
            const cleanUrl = window.location.pathname + (newSearch ? `?${newSearch}` : '') + window.location.hash;
            window.history.replaceState({}, '', cleanUrl);
        }
    }
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="min-w-0 overflow-x-clip pb-20 md:pb-6">
            <slot />
        </AppContent>
        <MobileBottomNav />
        <SettingsModal />
        <Toaster />
    </AppShell>
</template>
