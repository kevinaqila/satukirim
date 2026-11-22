<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Camera, Loader2, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';

withDefaults(
    defineProps<{
        avatar?: string | null;
        userName: string;
    }>(),
    {
        avatar: null,
    },
);

const fileInputRef = ref<HTMLInputElement | null>(null);
const isUploading = ref(false);
const isRemoving = ref(false);
const errorMessage = ref<string | undefined>(undefined);

const triggerSelect = () => {
    fileInputRef.value?.click();
};

const onFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';

    if (!file) {
        return;
    }

    errorMessage.value = undefined;
    isUploading.value = true;

    router.post(
        '/settings/profile/avatar',
        { avatar: file },
        {
            forceFormData: true,
            preserveScroll: true,
            onError: (errors) => {
                errorMessage.value = errors.avatar ?? 'Gagal mengunggah foto profil.';
            },
            onFinish: () => {
                isUploading.value = false;
            },
        },
    );
};

const removeAvatar = () => {
    errorMessage.value = undefined;
    isRemoving.value = true;

    router.delete('/settings/profile/avatar', {
        preserveScroll: true,
        onError: () => {
            errorMessage.value = 'Gagal menghapus foto profil.';
        },
        onFinish: () => {
            isRemoving.value = false;
        },
    });
};
</script>

<template>
    <div class="space-y-2">
        <span class="block text-xs sm:text-sm font-semibold text-stone-800">
            Foto Profil
        </span>

        <div class="flex items-center gap-4">
            <Avatar
                :key="avatar || 'fallback'"
                class="size-16 rounded-full border border-stone-200 bg-stone-100 shrink-0"
            >
                <AvatarImage
                    v-if="avatar"
                    :src="avatar"
                    :alt="userName"
                    class="object-cover"
                />
                <AvatarFallback class="font-bold text-base bg-[#853953] text-white">
                    {{ getInitials(userName || '') }}
                </AvatarFallback>
            </Avatar>

            <div class="space-y-1.5 min-w-0">
                <input
                    ref="fileInputRef"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="sr-only"
                    @change="onFileChange"
                />

                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="isUploading || isRemoving"
                        @click="triggerSelect"
                        class="h-8 sm:h-9 px-3 text-xs font-semibold border-stone-200 bg-white text-stone-700 hover:bg-stone-100 cursor-pointer gap-1.5"
                    >
                        <Loader2 v-if="isUploading" class="size-3.5 animate-spin" />
                        <Camera v-else class="size-3.5" />
                        <span>{{ isUploading ? 'Mengunggah...' : avatar ? 'Ganti Foto' : 'Unggah Foto' }}</span>
                    </Button>

                    <Button
                        v-if="avatar"
                        type="button"
                        variant="ghost"
                        size="sm"
                        :disabled="isUploading || isRemoving"
                        @click="removeAvatar"
                        class="h-8 sm:h-9 px-2.5 text-xs font-medium text-rose-600 hover:text-rose-700 hover:bg-rose-50 cursor-pointer gap-1"
                    >
                        <Loader2 v-if="isRemoving" class="size-3.5 animate-spin" />
                        <Trash2 v-else class="size-3.5" />
                        <span>Hapus</span>
                    </Button>
                </div>

                <p class="text-[11px] sm:text-xs text-stone-500">
                    Format JPG, PNG, atau WebP (maks. 2 MB)
                </p>
            </div>
        </div>

        <InputError :message="errorMessage" />
    </div>
</template>
