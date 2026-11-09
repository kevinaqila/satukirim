import { ref } from 'vue';

const isSettingsModalOpen = ref(false);
const activeSettingsTab = ref<'profile' | 'security'>('profile');

export function useSettingsModal() {
    const openSettingsModal = (tab: 'profile' | 'security' = 'profile') => {
        activeSettingsTab.value = tab;
        isSettingsModalOpen.value = true;
    };

    const closeSettingsModal = () => {
        isSettingsModalOpen.value = false;
    };

    return {
        isSettingsModalOpen,
        activeSettingsTab,
        openSettingsModal,
        closeSettingsModal,
    };
}

