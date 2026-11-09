<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import { computed, onMounted } from 'vue';
import AppTopBar from '@/components/AppTopBar.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import { edit as editSecurity } from '@/routes/security';

const isDesktop = useMediaQuery('(min-width: 768px)');
const { isCurrentOrParentUrl } = useCurrentUrl();

const currentTitle = computed(() => {
    if (isCurrentOrParentUrl(editSecurity())) {
        return 'Keamanan & Kata Sandi';
    }
    return 'Profil Akun';
});

onMounted(() => {
    if (typeof window !== 'undefined' && window.innerWidth >= 768) {
        const tab = isCurrentOrParentUrl(editSecurity()) ? 'security' : '1';
        router.visit(dashboard({ query: { settings: tab } }), { replace: true });
    }
});
</script>

<template>
    <div class="flex min-h-full flex-1 flex-col w-full min-w-0">
        <AppTopBar :title="currentTitle" back-href="/settings" />

        <div class="px-4 py-4 sm:py-6 max-w-xl mx-auto w-full">
            <div class="space-y-6">
                <slot />
            </div>
        </div>
    </div>
</template>
