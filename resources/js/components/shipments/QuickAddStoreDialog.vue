<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Loader2, Phone, Search } from '@lucide/vue';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface AreaSuggestion {
    id: string;
    name: string;
    postal_code?: string | number | null;
    administrative_division_level_1_name?: string;
    administrative_division_level_2_name?: string;
    administrative_division_level_3_name?: string;
}

defineProps<{
    open: boolean;
    hasExistingStores: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const storeForm = useForm({
    name: '',
    phone: '',
    address: '',
    postal_code: '',
    biteship_area_id: '',
    area_name: '',
    is_default: true,
    from_shipment: true,
});

const storeAreaQuery = ref('');
const storeAreaResults = ref<AreaSuggestion[]>([]);
const isSearchingStoreArea = ref(false);
const showStoreAreaDropdown = ref(false);
let storeAreaTimer: ReturnType<typeof setTimeout> | null = null;

watch(storeAreaQuery, (val) => {
    if (!val || val.length < 2) {
        storeAreaResults.value = [];
        showStoreAreaDropdown.value = false;
        if (!val) {
            storeForm.biteship_area_id = '';
            storeForm.area_name = '';
        }
        return;
    }

    if (storeForm.area_name && val === storeForm.area_name) {
        return;
    }

    if (storeAreaTimer) clearTimeout(storeAreaTimer);
    storeAreaTimer = setTimeout(async () => {
        isSearchingStoreArea.value = true;
        try {
            const res = await fetch(`/api/public/areas?q=${encodeURIComponent(val)}`);
            const data = await res.json();
            storeAreaResults.value = data.areas || [];
            showStoreAreaDropdown.value = storeAreaResults.value.length > 0;
        } catch {
            storeAreaResults.value = [];
            showStoreAreaDropdown.value = false;
        } finally {
            isSearchingStoreArea.value = false;
        }
    }, 350);
});

const selectStoreArea = (item: AreaSuggestion) => {
    storeForm.biteship_area_id = item.id;
    storeForm.area_name = item.name;
    storeAreaQuery.value = item.name;
    if (item.postal_code) {
        storeForm.postal_code = String(item.postal_code);
    }
    showStoreAreaDropdown.value = false;
};

const submitNewStore = () => {
    storeForm.post('/stores', {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false);
            storeForm.reset();
            storeAreaQuery.value = '';
        },
    });
};

const handleOutsideClick = (e: MouseEvent) => {
    const target = e.target as HTMLElement;
    if (!target.closest('.store-area-dropdown-container')) {
        showStoreAreaDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleOutsideClick);
});

onUnmounted(() => {
    document.removeEventListener('click', handleOutsideClick);
});
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            @pointer-down-outside="(e) => e.preventDefault()"
            @interact-outside="(e) => e.preventDefault()"
            class="sm:max-w-lg bg-white p-5 sm:p-6 rounded-xl border border-stone-200 shadow-xl max-h-[90vh] overflow-y-auto"
        >
            <DialogHeader class="text-left space-y-1">
                <DialogTitle class="text-base sm:text-lg font-semibold text-stone-900">
                    {{ !hasExistingStores ? 'Atur Titik Penjemputan Pertama' : 'Tambah Titik Penjemputan' }}
                </DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submitNewStore" class="space-y-3.5 mt-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div class="space-y-1">
                        <Label for="modal-store-name" class="text-sm font-medium text-stone-700">
                            Nama Toko / Gudang<span class="text-red-500 ml-1">*</span>
                        </Label>
                        <Input
                            id="modal-store-name"
                            v-model="storeForm.name"
                            placeholder="Gudang Utama Surabaya"
                            class="h-10 text-sm bg-white border-stone-200"
                            required
                        />
                        <InputError :message="storeForm.errors.name" />
                    </div>

                    <div class="space-y-1">
                        <Label for="modal-store-phone" class="text-sm font-medium text-stone-700">
                            Nomor WhatsApp Pengirim<span class="text-red-500 ml-1">*</span>
                        </Label>
                        <div class="relative">
                            <Phone class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-stone-400" />
                            <Input
                                id="modal-store-phone"
                                v-model="storeForm.phone"
                                placeholder="081234567890"
                                class="h-10 pl-9 text-sm bg-white border-stone-200"
                                required
                            />
                        </div>
                        <InputError :message="storeForm.errors.phone" />
                    </div>
                </div>

                <div class="space-y-1 relative store-area-dropdown-container">
                    <Label for="modal-store-area" class="text-sm font-medium text-stone-700 flex items-center justify-between">
                        <span>Kecamatan & Kota Penjemputan<span class="text-red-500 ml-1">*</span></span>
                        <span class="text-xs text-[#853953] font-medium">Wajib pilih dari daftar</span>
                    </Label>

                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-stone-400" />
                        <Input
                            id="modal-store-area"
                            v-model="storeAreaQuery"
                            placeholder="Ketik kelurahan atau kecamatan penjemputan..."
                            class="h-10 pl-9 pr-9 text-sm bg-white border-stone-200"
                            autocomplete="off"
                            @focus="storeAreaResults.length > 0 ? (showStoreAreaDropdown = true) : null"
                        />
                        <div v-if="isSearchingStoreArea" class="absolute right-3 top-1/2 -translate-y-1/2">
                            <Loader2 class="size-4 animate-spin text-[#853953]" />
                        </div>
                    </div>

                    <div
                        v-if="showStoreAreaDropdown && storeAreaResults.length > 0"
                        class="absolute z-50 left-0 right-0 mt-1 max-h-48 overflow-y-auto rounded-lg border border-stone-200 bg-white shadow-lg"
                    >
                        <div
                            v-for="area in storeAreaResults"
                            :key="area.id"
                            @click="selectStoreArea(area)"
                            class="p-2.5 hover:bg-stone-50 cursor-pointer border-b border-stone-100 last:border-none transition-colors"
                        >
                            <div class="text-sm font-semibold text-stone-900">
                                {{ area.name }}
                            </div>
                            <div class="text-xs text-stone-500">
                                {{ area.administrative_division_level_3_name }}, {{ area.administrative_division_level_2_name }}, {{ area.administrative_division_level_1_name }}
                                <span v-if="area.postal_code">({{ area.postal_code }})</span>
                            </div>
                        </div>
                    </div>

                    <span v-if="storeForm.errors.biteship_area_id" class="text-xs text-red-600 font-medium block">
                        Pilih area penjemputan dari daftar pencarian.
                    </span>
                </div>

                <div class="space-y-1">
                    <Label for="modal-store-address" class="text-sm font-medium text-stone-700">
                        Alamat Lengkap & Patokan<span class="text-red-500 ml-1">*</span>
                    </Label>
                    <textarea
                        id="modal-store-address"
                        v-model="storeForm.address"
                        rows="2"
                        placeholder="Jl. Raya Sukolilo No. 45, Ruko B3 (sebelah Bank BCA)"
                        class="w-full rounded-md border border-stone-200 bg-white px-3 py-2 text-sm text-stone-900 placeholder:text-stone-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#853953] focus-visible:ring-offset-1"
                        required
                    />
                    <InputError :message="storeForm.errors.address" />
                </div>

                <DialogFooter class="pt-2 gap-2 sm:justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                        class="text-xs sm:text-sm h-9 sm:h-10 cursor-pointer border-stone-200"
                    >
                        Nanti Saja
                    </Button>
                    <Button
                        type="submit"
                        :disabled="storeForm.processing || !storeForm.biteship_area_id"
                        class="bg-[#853953] hover:bg-[#6c2e43] text-white text-xs sm:text-sm font-semibold h-9 sm:h-10 px-5 cursor-pointer"
                    >
                        <Loader2 v-if="storeForm.processing" class="mr-2 size-4 animate-spin" />
                        <span>Simpan & Gunakan Toko</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

