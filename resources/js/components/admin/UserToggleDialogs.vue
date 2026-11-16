<script setup lang="ts">
import { Loader2, Power } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { AdminUserItem } from '@/types/admin';

defineProps<{
    singleOpen: boolean;
    userToToggle: AdminUserItem | null;
    isSingleLoading: boolean;
    bulkOpen: boolean;
    bulkToggleStatus: boolean | null;
    selectedCount: number;
    isBulkLoading: boolean;
}>();

const emit = defineEmits<{
    'update:singleOpen': [value: boolean];
    confirmSingle: [];
    'update:bulkOpen': [value: boolean];
    confirmBulk: [];
}>();
</script>

<template>
    <div>
        <Dialog :open="singleOpen" @update:open="emit('update:singleOpen', $event)">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <div class="flex items-center gap-3">
                        <div
                            class="size-10 rounded-full flex items-center justify-center shrink-0"
                            :class="userToToggle?.is_active ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'"
                        >
                            <Power class="size-5" />
                        </div>
                        <DialogTitle class="text-base sm:text-lg font-bold text-stone-900">
                            {{ userToToggle?.is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}
                        </DialogTitle>
                    </div>
                    <DialogDescription class="text-xs sm:text-sm text-stone-600 mt-2">
                        <template v-if="userToToggle?.is_active">
                            Apakah Anda yakin ingin menonaktifkan akun <strong class="text-stone-900 font-semibold">{{ userToToggle?.name }}</strong>? Pengguna tidak akan dapat login atau melakukan transaksi hingga diaktifkan kembali.
                        </template>
                        <template v-else>
                            Apakah Anda yakin ingin mengaktifkan akun <strong class="text-stone-900 font-semibold">{{ userToToggle?.name }}</strong>? Pengguna akan dapat login dan mengakses layanannya kembali.
                        </template>
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="pt-3 flex items-center justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="isSingleLoading"
                        @click="emit('update:singleOpen', false)"
                        class="h-9 px-4 text-xs sm:text-sm font-semibold bg-white border-stone-200 text-stone-700 hover:bg-stone-50 cursor-pointer shadow-2xs"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        :disabled="isSingleLoading"
                        @click="emit('confirmSingle')"
                        class="h-9 px-4 rounded-md text-xs sm:text-sm font-semibold shadow-xs cursor-pointer transition-colors"
                        :class="
                            userToToggle?.is_active
                                ? 'bg-rose-700 hover:bg-rose-800 text-white'
                                : 'bg-emerald-700 hover:bg-emerald-800 text-white'
                        "
                    >
                        <Loader2 v-if="isSingleLoading" class="mr-2 size-4 animate-spin" />
                        {{ userToToggle?.is_active ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog :open="bulkOpen" @update:open="emit('update:bulkOpen', $event)">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <div class="flex items-center gap-3">
                        <div
                            class="size-10 rounded-full flex items-center justify-center shrink-0"
                            :class="bulkToggleStatus ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                        >
                            <Power class="size-5" />
                        </div>
                        <DialogTitle class="text-base sm:text-lg font-bold text-stone-900">
                            {{ bulkToggleStatus ? 'Aktifkan Akun Terpilih' : 'Nonaktifkan Akun Terpilih' }}
                        </DialogTitle>
                    </div>
                    <DialogDescription class="text-xs sm:text-sm text-stone-600 mt-2">
                        Apakah Anda yakin ingin {{ bulkToggleStatus ? 'mengaktifkan' : 'menonaktifkan' }}
                        <strong class="text-stone-900 font-semibold">{{ selectedCount }} akun</strong> pengguna terpilih?
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="pt-3 flex items-center justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="isBulkLoading"
                        @click="emit('update:bulkOpen', false)"
                        class="h-9 px-4 text-xs sm:text-sm font-semibold bg-white border-stone-200 text-stone-700 hover:bg-stone-50 cursor-pointer shadow-2xs"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        :disabled="isBulkLoading"
                        @click="emit('confirmBulk')"
                        class="h-9 px-4 rounded-md text-xs sm:text-sm font-semibold shadow-xs cursor-pointer transition-colors"
                        :class="
                            bulkToggleStatus
                                ? 'bg-emerald-700 hover:bg-emerald-800 text-white'
                                : 'bg-rose-700 hover:bg-rose-800 text-white'
                        "
                    >
                        <Loader2 v-if="isBulkLoading" class="mr-2 size-4 animate-spin" />
                        {{ bulkToggleStatus ? 'Ya, Aktifkan Semua' : 'Ya, Nonaktifkan Semua' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

