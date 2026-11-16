import { ref } from 'vue';

export type SettingsTab = 'profile' | 'bank' | 'security';

const isSettingsModalOpen = ref(false);
const activeSettingsTab = ref<SettingsTab>('profile');

export function useSettingsModal() {
    const openSettingsModal = (tab: SettingsTab = 'profile') => {
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

