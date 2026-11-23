<script setup lang="ts">
import { Loader2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

defineProps<{
    singleOpen: boolean;
    singleOrderNumber?: string;
    singleRecipientName?: string;
    isDeleting: boolean;
    bulkOpen: boolean;
    bulkCount: number;
    isBulkDeleting: boolean;
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
        <Dialog :open="singleOpen" @update:open="(val) => { if (!isDeleting) emit('update:singleOpen', val); }">
            <DialogContent class="sm:max-w-md bg-white">
                <DialogHeader class="text-left">
                    <DialogTitle class="text-base sm:text-lg font-bold text-stone-900">
                        Hapus Data Pengiriman?
                    </DialogTitle>
                    <DialogDescription class="text-xs sm:text-sm text-stone-600 leading-relaxed pt-1">
                        Apakah Anda yakin ingin menghapus data pengiriman
                        <span v-if="singleOrderNumber" class="font-bold text-stone-900">{{ singleOrderNumber }}</span>
                        untuk penerima
                        <span v-if="singleRecipientName" class="font-semibold text-stone-800">{{ singleRecipientName }}</span>?
                        Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="pt-3 flex-row items-center justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="isDeleting"
                        @click="emit('update:singleOpen', false)"
                        class="h-9 sm:h-10 px-4 text-xs sm:text-sm font-semibold bg-white border-stone-200 text-stone-700 hover:bg-stone-50 cursor-pointer shadow-2xs"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        :disabled="isDeleting"
                        @click="emit('confirmSingle')"
                        class="h-9 sm:h-10 px-4 text-xs sm:text-sm font-semibold bg-red-700 hover:bg-red-800 text-white rounded-md shadow-xs cursor-pointer transition-colors"
                    >
                        <Loader2 v-if="isDeleting" class="mr-2 size-4 animate-spin" />
                        Ya, Hapus Pengiriman
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog :open="bulkOpen" @update:open="(val) => { if (!isBulkDeleting) emit('update:bulkOpen', val); }">
            <DialogContent class="sm:max-w-md bg-white">
                <DialogHeader class="text-left">
                    <DialogTitle class="text-base sm:text-lg font-bold text-stone-900">
                        Hapus {{ bulkCount }} Data Pengiriman Terpilih?
                    </DialogTitle>
                    <DialogDescription class="text-xs sm:text-sm text-stone-600 leading-relaxed pt-1">
                        Apakah Anda yakin ingin menghapus {{ bulkCount }} data pengiriman yang dipilih? Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="pt-3 flex-row items-center justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="isBulkDeleting"
                        @click="emit('update:bulkOpen', false)"
                        class="h-9 sm:h-10 px-4 text-xs sm:text-sm font-semibold bg-white border-stone-200 text-stone-700 hover:bg-stone-50 cursor-pointer shadow-2xs"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        :disabled="isBulkDeleting"
                        @click="emit('confirmBulk')"
                        class="h-9 sm:h-10 px-4 text-xs sm:text-sm font-semibold bg-red-700 hover:bg-red-800 text-white rounded-md shadow-xs cursor-pointer transition-colors"
                    >
                        <Loader2 v-if="isBulkDeleting" class="mr-2 size-4 animate-spin" />
                        Ya, Hapus {{ bulkCount }} Pengiriman
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

