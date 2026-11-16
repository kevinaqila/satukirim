<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Building2,
    Loader2,
    MapPin,
    Pencil,
    Phone,
    Plus,
    Star,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import AppTopBar from '@/components/AppTopBar.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

export interface StoreItem {
    id: number;
    name: string;
    phone: string;
    address: string;
    postal_code: string | null;
    biteship_area_id: string;
    area_name: string | null;
    is_default: boolean;
    created_at: string;
}

const props = defineProps<{
    stores: StoreItem[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Toko Saya',
                href: '/stores',
            },
        ],
    },
});

const makeDefault = (store: StoreItem) => {
    useForm({}).patch(`/stores/${store.id}/default`, {
        preserveScroll: true,
    });
};

const isDeleteModalOpen = ref(false);
const storeToDelete = ref<StoreItem | null>(null);
const deleteForm = useForm({});

const deleteStore = (store: StoreItem) => {
    storeToDelete.value = store;
    isDeleteModalOpen.value = true;
};

const confirmDeleteStore = () => {
    if (!storeToDelete.value) return;
    deleteForm.delete(`/stores/${storeToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            storeToDelete.value = null;
        },
    });
};

const formatStoreArea = (areaName: string | null, postalCode: string | null): string => {
    const cleanArea = (areaName || '').trim();
    const cleanPostal = (postalCode || '').trim();
    if (!cleanArea) {
        return cleanPostal ? `Kode Pos ${cleanPostal}` : '';
    }
    if (cleanPostal && !cleanArea.includes(cleanPostal)) {
        return `${cleanArea} - ${cleanPostal}`;
    }
    return cleanArea;
};
</script>

<template>
    <div class="flex min-h-full flex-1 flex-col w-full relative">
        <Head title="Toko Saya - SatuKirim" />

        <AppTopBar title="Toko Saya" back-href="/settings" back-mobile-only />

        <div class="flex flex-1 flex-col gap-3.5 sm:gap-4 p-3 sm:p-4 lg:p-5 pb-28 md:pb-20 w-full">
            <div v-if="stores.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5 sm:gap-4">
                <div
                    v-for="store in stores"
                    :key="store.id"
                    class="group relative rounded-xl border bg-white p-3.5 sm:p-5 shadow-2xs transition-all duration-200 hover:shadow-xs flex flex-col justify-between"
                    :class="store.is_default ? 'border-[#853953]/40 ring-1 ring-[#853953]/20' : 'border-stone-200'"
                >
                    <div class="space-y-3 sm:space-y-3.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <h3 class="text-sm sm:text-base font-bold text-stone-900 truncate" :title="store.name">
                                    {{ store.name }}
                                </h3>
                            </div>
                            <span
                                v-if="store.is_default"
                                class="inline-flex items-center gap-1 text-[10px] sm:text-[11px] font-semibold text-[#853953] bg-[#853953]/10 px-2 py-0.5 rounded-full shrink-0"
                            >
                                <Star class="size-3 fill-[#853953]" />
                                <span>Toko Utama</span>
                            </span>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-xs sm:text-sm text-stone-600">
                                <Phone class="size-3.5 sm:size-4 text-stone-400 shrink-0" />
                                <span class="font-medium text-stone-700 tabular-nums">{{ store.phone }}</span>
                            </div>

                            <div class="flex items-start gap-2 text-xs sm:text-sm text-stone-600">
                                <MapPin class="size-3.5 sm:size-4 text-stone-400 shrink-0 mt-0.5" />
                                <div class="min-w-0 space-y-0.5">
                                    <p class="line-clamp-2 leading-relaxed text-stone-700 font-medium">
                                        {{ store.address }}
                                    </p>
                                    <p
                                        v-if="formatStoreArea(store.area_name, store.postal_code)"
                                        class="text-xs text-stone-500 leading-snug"
                                    >
                                        {{ formatStoreArea(store.area_name, store.postal_code) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between gap-2 text-xs sm:text-sm">
                        <div class="flex items-center min-w-0">
                            <Button
                                v-if="!store.is_default"
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="makeDefault(store)"
                                class="h-8 px-2.5 sm:px-3 text-xs font-semibold bg-white border-stone-200 text-stone-700 hover:text-[#853953] hover:border-[#853953]/40 hover:bg-stone-50 cursor-pointer shadow-2xs transition-colors shrink-0"
                            >
                                <Star class="size-3 text-stone-400 mr-1" />
                                <span>Jadikan Utama</span>
                            </Button>
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            <Link
                                :href="`/stores/${store.id}/edit`"
                                class="inline-flex items-center h-8 px-2.5 sm:px-3 gap-1.5 text-xs font-semibold rounded-md border border-stone-200 bg-white text-stone-700 hover:text-stone-900 hover:border-stone-300 hover:bg-stone-50 cursor-pointer shadow-2xs transition-colors"
                            >
                                <Pencil class="size-3.5 text-stone-500" />
                                <span>Ubah</span>
                            </Link>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="deleteStore(store)"
                                class="h-8 px-2 sm:px-2.5 gap-1.5 text-xs font-semibold bg-white border-stone-200 text-stone-600 hover:text-red-700 hover:border-red-300 hover:bg-red-50/70 cursor-pointer shadow-2xs transition-colors"
                                title="Hapus Toko"
                            >
                                <Trash2 class="size-3.5 text-stone-400" />
                                <span class="hidden sm:inline">Hapus</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="rounded-xl border border-dashed border-stone-300 bg-white p-8 sm:p-12 text-center shadow-2xs"
            >
                <div class="mx-auto size-14 rounded-xl bg-[#853953]/10 text-primary flex items-center justify-center mb-3.5">
                    <Building2 class="size-7" />
                </div>
                <h3 class="text-base sm:text-lg font-bold text-stone-900">
                    Belum Ada Toko Terdaftar
                </h3>
                <p class="text-xs sm:text-sm text-stone-500 max-w-md mx-auto mt-1 mb-5">
                    Tambahkan lokasi gudang atau toko Anda agar kurir dapat langsung menjemput barang saat Anda membuat pengiriman paket baru.
                </p>
                <Link
                    href="/stores/create"
                    class="inline-flex items-center justify-center bg-[#853953] hover:bg-[#6c2e43] text-white text-xs sm:text-sm font-semibold h-10 px-4 rounded-md shadow-xs cursor-pointer transition-colors"
                >
                    <Plus class="mr-1.5 size-4" />
                    Tambah Toko Pertama
                </Link>
            </div>

            <Dialog :open="isDeleteModalOpen" @update:open="(val) => isDeleteModalOpen = val">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle class="text-base sm:text-lg font-bold text-stone-900">
                            Hapus Toko
                        </DialogTitle>
                        <DialogDescription class="text-xs sm:text-sm text-stone-500">
                            Apakah Anda yakin ingin menghapus toko "{{ storeToDelete?.name }}"? Tindakan ini tidak dapat dibatalkan.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter class="pt-3 flex items-center justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isDeleteModalOpen = false"
                            class="h-9 sm:h-10 px-4 text-xs sm:text-sm font-semibold bg-white border-stone-200 text-stone-700 hover:bg-stone-50 cursor-pointer shadow-2xs"
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            :disabled="deleteForm.processing"
                            @click="confirmDeleteStore"
                            class="bg-red-700 hover:bg-red-800 text-white text-xs sm:text-sm font-semibold h-9 sm:h-10 px-4 rounded-md shadow-xs cursor-pointer transition-colors"
                        >
                            <Loader2 v-if="deleteForm.processing" class="mr-2 size-4 animate-spin" />
                            Ya, Hapus Toko
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>

        <Link
            v-if="stores.length > 0"
            href="/stores/create"
            class="fixed bottom-20 md:bottom-8 right-4 md:right-8 z-30 size-[52px] rounded-full bg-gradient-to-tr from-[#853953] to-[#A23F5C] text-white flex items-center justify-center shadow-lg shadow-[#853953]/35 transition-all duration-200 hover:scale-105 active:scale-95 cursor-pointer"
            aria-label="Tambah Toko Baru"
            title="Tambah Toko Baru"
        >
            <svg
                class="size-6 text-white shrink-0"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M17.774 10.31a1.12 1.12 0 0 0-1.549 0 2.5 2.5 0 0 1-3.451 0 1.12 1.12 0 0 0-1.548 0 2.5 2.5 0 0 1-3.452 0 1.12 1.12 0 0 0-1.549 0 2.5 2.5 0 0 1-3.77-3.248l2.889-4.184A2 2 0 0 1 7 2h10a2 2 0 0 1 1.653.873l2.895 4.192a2.5 2.5 0 0 1-3.774 3.244" />
                <path d="M4 10.95V19a2 2 0 0 0 2 2h6" />
                <path d="M20 10.95V13" />
                <path d="M12 21v-4a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v4" />
                <path d="M16 18h6" />
                <path d="M19 15v6" />
            </svg>
        </Link>
    </div>
</template>
