<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import {
    Check,
    Loader2,
    Shield,
    User as UserIcon,
    X,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useSettingsModal } from '@/composables/useSettingsModal';

const isDesktop = useMediaQuery('(min-width: 768px)');
const page = usePage();
const user = computed(() => page.props.auth?.user);
const { isSettingsModalOpen, activeSettingsTab, closeSettingsModal } = useSettingsModal();

const navItems = [
    {
        id: 'profile' as const,
        title: 'Profil Akun',
        icon: UserIcon,
    },
    {
        id: 'security' as const,
        title: 'Keamanan & Sandi',
        icon: Shield,
    },
];

const profileForm = useForm({
    name: user.value?.name || '',
    email: user.value?.email || '',
});

watch(
    () => user.value,
    (newUser) => {
        if (newUser) {
            profileForm.name = newUser.name;
            profileForm.email = newUser.email;
        }
    },
    { immediate: true },
);

const isProfileSaved = ref(false);
const submitProfile = () => {
    profileForm.patch('/settings/profile', {
        preserveScroll: true,
        onSuccess: () => {
            isProfileSaved.value = true;
            setTimeout(() => {
                isProfileSaved.value = false;
            }, 2500);
        },
    });
};

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const isPasswordSaved = ref(false);
const submitPassword = () => {
    passwordForm.put('/settings/password', {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            isPasswordSaved.value = true;
            setTimeout(() => {
                isPasswordSaved.value = false;
            }, 2500);
        },
        onError: () => {
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
            }
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password');
            }
        },
    });
};

const onKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && isSettingsModalOpen.value) {
        closeSettingsModal();
    }
};

onMounted(() => {
    window.addEventListener('keydown', onKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeyDown);
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="isSettingsModalOpen && isDesktop"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-black/45 backdrop-blur-xs transition-opacity duration-200 animate-in fade-in"
            @click.self="closeSettingsModal"
        >
            <div
                class="w-full max-w-2xl h-[520px] max-h-[90vh] bg-white rounded-2xl border border-stone-200 shadow-xl flex flex-col overflow-hidden animate-in zoom-in-95 duration-150 select-text"
                role="dialog"
                aria-modal="true"
                aria-labelledby="settings-dialog-title"
            >
                <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-stone-100 bg-white shrink-0">
                    <h2 id="settings-dialog-title" class="text-base sm:text-lg font-bold text-stone-900 tracking-tight">
                        Pengaturan
                    </h2>

                    <button
                        type="button"
                        @click="closeSettingsModal"
                        class="size-8 rounded-lg text-stone-400 hover:text-stone-800 hover:bg-stone-100 flex items-center justify-center transition-colors cursor-pointer active:scale-95"
                        aria-label="Tutup Pengaturan"
                        title="Tutup (Esc)"
                    >
                        <X class="size-4.5" />
                    </button>
                </div>

                <div class="flex flex-1 min-h-0 overflow-hidden">
                    <aside class="flex w-48 md:w-52 bg-stone-50/70 border-r border-stone-100 p-3 flex-col shrink-0 select-none">
                        <div class="space-y-2">
                            <p class="text-xs font-semibold text-stone-500 uppercase tracking-wider px-2">
                                Menu
                            </p>

                            <nav class="space-y-1" aria-label="Menu Pengaturan">
                                <button
                                    v-for="item in navItems"
                                    :key="item.id"
                                    type="button"
                                    @click="activeSettingsTab = item.id"
                                    :class="[
                                        'w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all cursor-pointer text-left',
                                        activeSettingsTab === item.id
                                            ? 'bg-[#853953] text-white shadow-xs'
                                            : 'text-stone-600 hover:text-stone-900 hover:bg-stone-200/50',
                                    ]"
                                >
                                    <component
                                        :is="item.icon"
                                        :class="[
                                            'size-4 shrink-0 transition-colors',
                                            activeSettingsTab === item.id ? 'text-white' : 'text-stone-400',
                                        ]"
                                    />
                                    <span>{{ item.title }}</span>
                                </button>
                            </nav>
                        </div>
                    </aside>

                    <main class="flex-1 min-w-0 p-5 sm:p-6 md:p-7 overflow-y-auto bg-white">
                        <div v-if="activeSettingsTab === 'profile'" class="max-w-md space-y-5">
                            <form @submit.prevent="submitProfile" class="space-y-4">
                                <div class="space-y-1.5">
                                    <Label for="profile-name" class="text-xs sm:text-sm font-semibold text-stone-800">
                                        Nama Lengkap
                                    </Label>
                                    <Input
                                        id="profile-name"
                                        v-model="profileForm.name"
                                        type="text"
                                        placeholder="Nama lengkap Anda"
                                        class="h-9 sm:h-10 text-xs sm:text-sm bg-white border-stone-200 rounded-lg"
                                        required
                                    />
                                    <InputError :message="profileForm.errors.name" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label for="profile-email" class="text-xs sm:text-sm font-semibold text-stone-800">
                                        Alamat Email
                                    </Label>
                                    <Input
                                        id="profile-email"
                                        v-model="profileForm.email"
                                        type="email"
                                        placeholder="nama@contoh.com"
                                        class="h-9 sm:h-10 text-xs sm:text-sm bg-white border-stone-200 rounded-lg"
                                        required
                                    />
                                    <InputError :message="profileForm.errors.email" />
                                </div>

                                <div class="flex items-center gap-3 pt-1">
                                    <Button
                                        type="submit"
                                        :disabled="profileForm.processing"
                                        class="bg-[#853953] hover:bg-[#6c2e43] text-white text-xs sm:text-sm font-semibold h-9 sm:h-10 px-5 rounded-lg shadow-xs cursor-pointer"
                                    >
                                        <Loader2 v-if="profileForm.processing" class="mr-2 size-4 animate-spin" />
                                        Simpan Perubahan
                                    </Button>

                                    <span v-if="isProfileSaved" class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                                        <Check class="size-3.5 text-emerald-600" />
                                        Tersimpan!
                                    </span>
                                </div>
                            </form>

                            <div class="pt-6 border-t border-stone-100">
                                <DeleteUser />
                            </div>
                        </div>

                        <div v-else-if="activeSettingsTab === 'security'" class="max-w-md space-y-5">
                            <form @submit.prevent="submitPassword" class="space-y-4">
                                <div class="space-y-1.5">
                                    <Label for="current-password" class="text-xs sm:text-sm font-semibold text-stone-800">
                                        Kata Sandi Saat Ini
                                    </Label>
                                    <PasswordInput
                                        id="current-password"
                                        v-model="passwordForm.current_password"
                                        placeholder="Masukkan kata sandi saat ini"
                                        class="h-9 sm:h-10 text-xs sm:text-sm bg-white border-stone-200 rounded-lg"
                                        autocomplete="current-password"
                                        required
                                    />
                                    <InputError :message="passwordForm.errors.current_password" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label for="new-password" class="text-xs sm:text-sm font-semibold text-stone-800">
                                        Kata Sandi Baru
                                    </Label>
                                    <PasswordInput
                                        id="new-password"
                                        v-model="passwordForm.password"
                                        placeholder="Minimal 8 karakter"
                                        class="h-9 sm:h-10 text-xs sm:text-sm bg-white border-stone-200 rounded-lg"
                                        autocomplete="new-password"
                                        required
                                    />
                                    <InputError :message="passwordForm.errors.password" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label for="confirm-password" class="text-xs sm:text-sm font-semibold text-stone-800">
                                        Konfirmasi Kata Sandi Baru
                                    </Label>
                                    <PasswordInput
                                        id="confirm-password"
                                        v-model="passwordForm.password_confirmation"
                                        placeholder="Ulangi kata sandi baru"
                                        class="h-9 sm:h-10 text-xs sm:text-sm bg-white border-stone-200 rounded-lg"
                                        autocomplete="new-password"
                                        required
                                    />
                                    <InputError :message="passwordForm.errors.password_confirmation" />
                                </div>

                                <div class="flex items-center gap-3 pt-1">
                                    <Button
                                        type="submit"
                                        :disabled="passwordForm.processing"
                                        class="bg-[#853953] hover:bg-[#6c2e43] text-white text-xs sm:text-sm font-semibold h-9 sm:h-10 px-5 rounded-lg shadow-xs cursor-pointer"
                                    >
                                        <Loader2 v-if="passwordForm.processing" class="mr-2 size-4 animate-spin" />
                                        Perbarui Kata Sandi
                                    </Button>

                                    <span v-if="isPasswordSaved" class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                                        <Check class="size-3.5 text-emerald-600" />
                                        Kata sandi diperbarui!
                                    </span>
                                </div>
                            </form>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </Teleport>
</template>
