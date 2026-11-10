<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Banknote,
    Check,
    ChevronDown,
    ChevronRight,
    Clock,
    CreditCard,
    Loader2,
    MapPin,
    Package,
    Phone,
    Plus,
    RefreshCw,
    Search,
    Store,
    Truck,
    User,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import AppTopBar from '@/components/AppTopBar.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getCourierLogo } from '@/lib/status';

export interface StoreOption {
    id: number;
    name: string;
    phone: string;
    address: string;
    biteship_area_id: string;
    area_name: string | null;
    postal_code?: string | null;
    is_default: boolean;
}

export interface CourierRate {
    courier_code: string;
    courier_name: string;
    courier_service_code: string;
    courier_service_name: string;
    duration: string;
    price: number;
    type: string;
}

const props = defineProps<{
    stores: StoreOption[];
    defaultStore: StoreOption | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Kirim Paket',
                href: '/shipments/create',
            },
        ],
    },
});

const weightKg = ref<number | string>(1);
const showStoreDropdown = ref(false);

const form = useForm({
    store_id: props.defaultStore?.id ?? (props.stores[0]?.id ?? 0),
    recipient_name: '',
    recipient_phone: '',
    destination_address: '',
    destination_postal_code: '',
    destination_area_id: '',
    destination_area_name: '',
    weight: 1000,
    item_description: '',
    item_category: 'Fashion & Pakaian',
    item_value: 100000,
    courier_code: '',
    courier_service_code: '',
    courier_service_name: '',
    shipping_fee: 0,
    estimated_delivery: '',
    payment_type: 'non_cod',
    is_cod: false,
});

const selectedStore = computed(() => {
    return props.stores.find((s) => s.id === Number(form.store_id)) || props.stores[0];
});

watch(
    () => props.stores,
    (newStores) => {
        if (newStores.length > 0 && (!form.store_id || form.store_id === 0)) {
            const def = newStores.find((s) => s.is_default) || newStores[0];
            form.store_id = def.id;
        }
    },
    { immediate: true },
);

const showAddStoreModal = ref(props.stores.length === 0);

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
const storeAreaResults = ref<any[]>([]);
const isSearchingStoreArea = ref(false);
const showStoreAreaDropdown = ref(false);
let storeAreaTimer: any = null;

watch(storeAreaQuery, (val) => {
    if (!val || val.length < 2) {
        storeAreaResults.value = [];
        showStoreAreaDropdown.value = false;
        return;
    }

    if (storeForm.area_name && val === storeForm.area_name) {
        return;
    }

    clearTimeout(storeAreaTimer);
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

const selectStoreArea = (item: any) => {
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
            showAddStoreModal.value = false;
            storeForm.reset();
            storeAreaQuery.value = '';
        },
    });
};

const selectStoreOption = (store: StoreOption) => {
    form.store_id = store.id;
    showStoreDropdown.value = false;
};

const handleOutsideClick = (e: MouseEvent) => {
    const target = e.target as HTMLElement;
    if (!target.closest('.store-dropdown-container')) {
        showStoreDropdown.value = false;
    }
    if (!target.closest('.dest-area-dropdown-container')) {
        showDestAreaDropdown.value = false;
    }
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

const destAreaQuery = ref('');
const destAreaResults = ref<any[]>([]);
const isSearchingDestArea = ref(false);
const showDestAreaDropdown = ref(false);
let destSearchTimer: any = null;
let rateReloadTimer: any = null;

watch(weightKg, (val) => {
    const parsed = parseFloat(String(val));
    const clampedKg = Number.isNaN(parsed) ? 0.5 : Math.min(10, Math.max(0.1, parsed));
    form.weight = Math.round(clampedKg * 1000);
});

watch(destAreaQuery, (val) => {
    if (!val || val.length < 2) {
        destAreaResults.value = [];
        showDestAreaDropdown.value = false;
        return;
    }

    if (form.destination_area_name && val === form.destination_area_name) {
        return;
    }

    clearTimeout(destSearchTimer);
    destSearchTimer = setTimeout(async () => {
        isSearchingDestArea.value = true;
        try {
            const res = await fetch(`/api/public/areas?q=${encodeURIComponent(val)}`);
            const data = await res.json();
            destAreaResults.value = data.areas || [];
            showDestAreaDropdown.value = destAreaResults.value.length > 0;
        } catch {
            destAreaResults.value = [];
            showDestAreaDropdown.value = false;
        } finally {
            isSearchingDestArea.value = false;
        }
    }, 300);
});

const selectDestArea = (item: any) => {
    form.destination_area_id = item.id;
    form.destination_area_name = item.name;
    destAreaQuery.value = item.name;
    if (item.postal_code) {
        form.destination_postal_code = String(item.postal_code);
    }
    showDestAreaDropdown.value = false;
};

const ratesList = ref<CourierRate[]>([]);
const isLoadingRates = ref(false);
const ratesFetchError = ref<string | null>(null);

const fetchRates = async () => {
    if (!form.store_id || !form.destination_area_id || !form.weight) {
        return;
    }

    isLoadingRates.value = true;
    ratesFetchError.value = null;

    try {
        const params = new URLSearchParams({
            store_id: String(form.store_id),
            destination_area_id: String(form.destination_area_id),
            weight: String(form.weight),
        });

        const response = await fetch(`/shipments/rates?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json();
        if (data.success && Array.isArray(data.pricing) && data.pricing.length > 0) {
            ratesList.value = data.pricing;
            const stillExists = ratesList.value.find(
                (r) => r.courier_code === form.courier_code && r.courier_service_code === form.courier_service_code,
            );
            if (stillExists) {
                selectCourier(stillExists);
            } else {
                selectCourier(ratesList.value[0]);
            }
        } else {
            ratesList.value = [];
            form.courier_code = '';
            form.courier_service_code = '';
            form.courier_service_name = '';
            form.shipping_fee = 0;
            ratesFetchError.value = 'Tidak ada layanan kurir tersedia untuk rute ini.';
        }
    } catch {
        ratesList.value = [];
        ratesFetchError.value = 'Gagal memuat tarif. Silakan periksa koneksi internet Anda.';
    } finally {
        isLoadingRates.value = false;
    }
};

watch(
    () => [form.store_id, form.destination_area_id, form.weight],
    ([storeId, destAreaId, weight]) => {
        if (!storeId || !destAreaId || !weight) {
            return;
        }
        clearTimeout(rateReloadTimer);
        rateReloadTimer = setTimeout(() => {
            fetchRates();
        }, 300);
    },
);

const selectCourier = (rate: CourierRate) => {
    form.courier_code = rate.courier_code;
    form.courier_service_code = rate.courier_service_code;
    form.courier_service_name = `${rate.courier_name} ${rate.courier_service_name}`;
    form.shipping_fee = rate.price;
    form.estimated_delivery = rate.duration;
};

const canSubmit = computed(() => {
    const baseValid = Boolean(
        form.store_id &&
            form.recipient_name.trim() &&
            form.recipient_phone.trim() &&
            form.destination_area_id &&
            form.destination_address.trim() &&
            form.courier_code &&
            form.shipping_fee > 0 &&
            !form.processing,
    );
    if (!baseValid) return false;
    if (form.payment_type === 'cod' && (!form.item_value || Number(form.item_value) < 1000)) {
        return false;
    }
    return true;
});

const submitOrder = () => {
    if (!canSubmit.value) {
        return;
    }
    if (!form.item_description.trim()) {
        form.item_description = form.item_category;
    }
    form.is_cod = form.payment_type === 'cod';
    form.post('/shipments');
};
</script>

<template>
    <Head title="Kirim Paket Baru - SatuKirim" />

    <AppTopBar title="Kirim Paket" />

    <div class="flex h-full flex-1 flex-col gap-3 sm:gap-4 p-3 sm:p-4 lg:p-5 w-full">
        <form @submit.prevent="submitOrder" class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 sm:gap-4 items-start">
            <div class="lg:col-span-7 space-y-3.5 sm:space-y-4">
                <div class="rounded-xl border border-stone-200/80 bg-white shadow-xs p-4 sm:p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold text-[#2C2C2C] flex items-center gap-2">
                            <Store class="size-4.5 text-[#853953]" />
                            <span>Titik Penjemputan</span>
                        </h2>
                        <Link
                            href="/stores"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs sm:text-sm font-semibold text-primary transition-colors shrink-0"
                        >
                            <span>Kelola Toko</span>
                            <ChevronRight class="size-3.5" />
                        </Link>
                    </div>

                    <div v-if="stores.length === 0" class="rounded-lg border border-dashed border-stone-200 bg-stone-50/70 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-1">
                            <p class="text-xs sm:text-sm font-bold text-stone-900">
                                Belum Ada Titik Penjemputan
                            </p>
                            <p class="text-xs text-stone-500 leading-relaxed">
                                Tambahkan alamat toko atau gudang pertama Anda agar kurir dapat menjemput paket.
                            </p>
                        </div>
                        <Button
                            type="button"
                            @click="showAddStoreModal = true"
                            class="bg-[#853953] hover:bg-[#6c2e43] text-white font-semibold text-xs sm:text-sm h-9 px-4 rounded-lg cursor-pointer shrink-0 self-start sm:self-center inline-flex items-center gap-1.5 shadow-2xs"
                        >
                            <Plus class="size-3.5" />
                            <span>Tambah Toko</span>
                        </Button>
                    </div>

                    <div v-else class="store-dropdown-container relative space-y-2.5">
                        <button
                            type="button"
                            @click="showStoreDropdown = !showStoreDropdown"
                            class="w-full h-10 px-3.5 rounded-lg border bg-white text-left flex items-center justify-between gap-2 transition-all cursor-pointer"
                            :class="
                                showStoreDropdown
                                    ? 'border-[#853953] ring-2 ring-[#853953]/20'
                                    : 'border-stone-200 hover:border-stone-300'
                            "
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-sm font-bold text-stone-900 truncate">
                                    {{ selectedStore?.name }}
                                </span>
                                <span
                                    v-if="selectedStore?.is_default"
                                    class="px-2 py-0.5 rounded-md bg-amber-100/90 text-amber-900 text-xs font-semibold shrink-0"
                                >
                                    Toko Utama
                                </span>
                            </div>
                            <ChevronDown
                                class="size-4 text-stone-500 shrink-0 transition-transform duration-200"
                                :class="showStoreDropdown ? 'rotate-180 text-[#853953]' : ''"
                            />
                        </button>

                        <div
                            v-if="showStoreDropdown"
                            class="absolute z-40 left-0 right-0 top-11 rounded-xl border border-stone-200 bg-white shadow-xl p-1.5 space-y-1"
                        >
                            <button
                                v-for="store in stores"
                                :key="store.id"
                                type="button"
                                @click="selectStoreOption(store)"
                                class="w-full text-left px-3 py-2.5 rounded-lg flex items-center justify-between gap-3 transition-colors cursor-pointer"
                                :class="
                                    form.store_id === store.id
                                        ? 'bg-[#853953]/8 text-[#853953]'
                                        : 'hover:bg-stone-50 text-stone-800'
                                "
                            >
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold truncate">
                                            {{ store.name }}
                                        </span>
                                        <span
                                            v-if="store.is_default"
                                            class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-900 text-[11px] font-semibold shrink-0"
                                        >
                                            Toko Utama
                                        </span>
                                    </div>
                                    <div class="text-xs text-stone-500 truncate mt-0.5">
                                        {{ store.area_name || store.address }}
                                    </div>
                                </div>
                                <Check v-if="form.store_id === store.id" class="size-4 text-[#853953] shrink-0" />
                            </button>

                            <div class="pt-1 border-t border-stone-100">
                                <button
                                    type="button"
                                    @click="showStoreDropdown = false; showAddStoreModal = true"
                                    class="w-full text-left px-3 py-2 rounded-lg flex items-center gap-2 text-xs font-semibold text-[#853953] hover:bg-stone-50 transition-colors cursor-pointer"
                                >
                                    <Plus class="size-3.5" />
                                    <span>Tambah Titik Penjemputan Baru</span>
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="selectedStore"
                            class="p-3 rounded-lg bg-stone-50/80 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-stone-600"
                        >
                            <div class="flex items-start gap-2 min-w-0">
                                <MapPin class="size-3.5 text-[#853953] shrink-0 mt-0.5" />
                                <div class="min-w-0">
                                    <span class="font-semibold text-stone-800 block truncate">
                                        {{ selectedStore.area_name || 'Area Terverifikasi' }}
                                    </span>
                                    <span class="text-stone-500 block truncate">
                                        {{ selectedStore.address }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 text-stone-500 font-medium shrink-0 pl-5 sm:pl-0">
                                <Phone class="size-3 text-stone-400" />
                                <span>{{ selectedStore.phone }}</span>
                            </div>
                        </div>
                        <InputError :message="form.errors.store_id" />
                    </div>
                </div>

                <div class="rounded-xl border border-stone-200/80 bg-white shadow-xs p-4 sm:p-5 space-y-3.5">
                    <h2 class="text-base font-bold text-[#2C2C2C] flex items-center gap-2">
                        <User class="size-4.5 text-[#853953]" />
                        <span>Informasi Penerima & Tujuan</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div class="space-y-1.5">
                            <Label for="rec-name" class="text-xs sm:text-sm font-semibold text-stone-700">
                                Nama Lengkap Penerima
                            </Label>
                            <Input
                                id="rec-name"
                                v-model="form.recipient_name"
                                placeholder="Budi Santoso"
                                class="text-sm h-10"
                                required
                            />
                            <InputError :message="form.errors.recipient_name" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="rec-phone" class="text-xs sm:text-sm font-semibold text-stone-700">
                                Nomor WhatsApp / HP Penerima
                            </Label>
                            <Input
                                id="rec-phone"
                                v-model="form.recipient_phone"
                                type="tel"
                                placeholder="081234567890"
                                class="text-sm h-10"
                                required
                            />
                            <InputError :message="form.errors.recipient_phone" />
                        </div>
                    </div>

                    <div class="dest-area-dropdown-container space-y-1.5 relative">
                        <div class="flex items-center justify-between">
                            <Label for="dest-area" class="text-xs sm:text-sm font-semibold text-stone-700">
                                Kecamatan / Kota Tujuan
                            </Label>
                            <span
                                v-if="form.destination_area_id"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600"
                            >
                                <Check class="size-3.5" /> Area Terpilih
                            </span>
                            <span v-else class="text-xs text-stone-400">
                                Pilih dari hasil pencarian
                            </span>
                        </div>

                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-stone-400" />
                            <Input
                                id="dest-area"
                                v-model="destAreaQuery"
                                placeholder="Ketik kecamatan atau kota tujuan (cth: Kebon Jeruk, Jakarta)..."
                                class="pl-9 pr-9 text-sm h-10"
                                autocomplete="off"
                                @focus="destAreaResults.length > 0 ? (showDestAreaDropdown = true) : null"
                            />
                            <div v-if="isSearchingDestArea" class="absolute right-3 top-1/2 -translate-y-1/2">
                                <Loader2 class="size-4 animate-spin text-[#853953]" />
                            </div>
                        </div>

                        <div
                            v-if="showDestAreaDropdown && destAreaResults.length > 0"
                            class="absolute z-50 left-0 right-0 top-full mt-1 max-h-56 overflow-y-auto rounded-xl border border-stone-200 bg-white shadow-xl p-1.5 space-y-0.5"
                        >
                            <button
                                v-for="area in destAreaResults"
                                :key="area.id"
                                type="button"
                                @click="selectDestArea(area)"
                                class="w-full text-left px-3 py-2 rounded-lg hover:bg-stone-50 cursor-pointer transition-colors"
                            >
                                <div class="text-sm font-semibold text-stone-900">
                                    {{ area.name }}
                                </div>
                                <div
                                    v-if="area.administrative_division_level_3_name || area.postal_code"
                                    class="text-xs text-stone-500 mt-0.5"
                                >
                                    {{
                                        [
                                            area.administrative_division_level_3_name,
                                            area.administrative_division_level_2_name,
                                            area.administrative_division_level_1_name,
                                        ]
                                            .filter(Boolean)
                                            .join(', ')
                                    }}
                                    <span v-if="area.postal_code">({{ area.postal_code }})</span>
                                </div>
                            </button>
                        </div>
                        <InputError :message="form.errors.destination_area_id" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3.5">
                        <div class="sm:col-span-3 space-y-1.5">
                            <Label for="dest-address" class="text-xs sm:text-sm font-semibold text-stone-700">
                                Alamat Lengkap & Patokan
                            </Label>
                            <Input
                                id="dest-address"
                                v-model="form.destination_address"
                                placeholder="Nama jalan, nomor rumah, RT/RW, atau patokan kurir"
                                class="text-sm h-10"
                                required
                            />
                            <InputError :message="form.errors.destination_address" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="dest-zip" class="text-xs sm:text-sm font-semibold text-stone-700">
                                Kode Pos
                            </Label>
                            <Input
                                id="dest-zip"
                                v-model="form.destination_postal_code"
                                placeholder="11530"
                                class="text-sm h-10"
                            />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-stone-200/80 bg-white shadow-xs p-4 sm:p-5 space-y-3.5">
                    <h2 class="text-base font-bold text-[#2C2C2C] flex items-center gap-2">
                        <Package class="size-4.5 text-[#853953]" />
                        <span>Spesifikasi Barang & Paket</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                        <div class="space-y-1.5">
                            <Label for="pkg-desc" class="text-xs sm:text-sm font-semibold text-stone-700">
                                Nama Barang
                            </Label>
                            <Input
                                id="pkg-desc"
                                v-model="form.item_description"
                                placeholder="Kemeja Batik, Kopi"
                                class="text-sm h-10"
                                required
                            />
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <Label for="pkg-weight-kg" class="text-xs sm:text-sm font-semibold text-stone-700">
                                    Berat Paket (Kg)
                                </Label>
                                <span class="text-xs font-medium text-stone-400">
                                    Maks. 10 kg
                                </span>
                            </div>
                            <Input
                                id="pkg-weight-kg"
                                v-model="weightKg"
                                type="number"
                                step="0.1"
                                min="0.1"
                                max="10"
                                placeholder="1"
                                class="text-sm h-10"
                                required
                            />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="pkg-val" class="text-xs sm:text-sm font-semibold text-stone-700">
                                Estimasi Nilai Barang (Rp)
                            </Label>
                            <Input
                                id="pkg-val"
                                v-model="form.item_value"
                                type="number"
                                min="0"
                                step="1000"
                                placeholder="100000"
                                class="text-sm h-10"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5 lg:sticky lg:top-4 space-y-3.5 sm:space-y-4">
                <div class="rounded-xl border border-stone-200/80 bg-white shadow-xs p-4 sm:p-5 space-y-3.5">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold text-[#2C2C2C] flex items-center gap-2">
                            <Truck class="size-4.5 text-[#853953]" />
                            <span>Pilih Layanan Ekspedisi</span>
                        </h2>
                        <Button
                            v-if="form.destination_area_id"
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="fetchRates"
                            :disabled="isLoadingRates"
                            class="h-8 px-2.5 text-xs font-semibold text-primary hover:bg-[#853953]/10 cursor-pointer"
                        >
                            <RefreshCw class="size-3.5 mr-1.5" :class="isLoadingRates ? 'animate-spin' : ''" />
                            Refresh
                        </Button>
                    </div>

                    <div
                        v-if="!form.destination_area_id"
                        class="py-8 px-4 rounded-xl bg-stone-50/80 text-center space-y-2"
                    >
                        <MapPin class="size-7 mx-auto text-stone-400" />
                        <p class="text-sm font-semibold text-stone-700">
                            Tujuan Pengiriman Belum Dipilih
                        </p>
                        <p class="text-xs text-stone-500 max-w-xs mx-auto leading-relaxed">
                            Pilih <strong class="font-semibold text-stone-700">Kecamatan / Kota Tujuan</strong> di formulir sebelah kiri untuk menampilkan perbandingan ongkir dari 8 ekspedisi secara otomatis.
                        </p>
                    </div>

                    <div v-else-if="isLoadingRates" class="py-10 text-center space-y-2.5">
                        <Loader2 class="size-7 animate-spin mx-auto text-[#853953]" />
                        <p class="text-sm font-semibold text-stone-700">
                            Mengecek Tarif 8 Ekspedisi...
                        </p>
                        <p class="text-xs text-stone-500">
                            Membandingkan harga layanan Reguler & Next Day terbaik
                        </p>
                    </div>

                    <div v-else-if="ratesFetchError" class="py-8 px-4 rounded-xl bg-stone-50 text-center space-y-3">
                        <p class="text-sm font-semibold text-stone-700">
                            {{ ratesFetchError }}
                        </p>
                        <Button type="button" size="sm" @click="fetchRates" class="bg-[#853953] text-white cursor-pointer">
                            Coba Lagi
                        </Button>
                    </div>

                    <div v-else class="space-y-2 max-h-80 overflow-y-auto pr-1">
                        <div
                            v-for="rate in ratesList"
                            :key="`${rate.courier_code}-${rate.courier_service_code}`"
                            @click="selectCourier(rate)"
                            class="p-3 rounded-xl transition-all cursor-pointer flex items-center justify-between gap-3"
                            :class="
                                form.courier_code === rate.courier_code &&
                                form.courier_service_code === rate.courier_service_code
                                    ? 'bg-[#853953]/8 ring-1.5 ring-[#853953]'
                                    : 'bg-stone-50/70 hover:bg-stone-100/80'
                            "
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="h-8 w-12 bg-white rounded-md border border-stone-200/70 p-1 flex items-center justify-center shrink-0">
                                    <img
                                        v-if="getCourierLogo(rate.courier_code)"
                                        :src="getCourierLogo(rate.courier_code)!"
                                        :alt="rate.courier_name"
                                        class="max-h-5 max-w-full object-contain"
                                    />
                                    <span v-else class="text-xs font-bold text-stone-600 uppercase">
                                        {{ rate.courier_code }}
                                    </span>
                                </div>

                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-stone-900 truncate">
                                        {{ rate.courier_name }} {{ rate.courier_service_name }}
                                    </div>
                                    <div class="flex items-center gap-2 text-xs text-stone-500 mt-0.5">
                                        <span class="flex items-center gap-1">
                                            <Clock class="size-3" /> {{ rate.duration }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5 shrink-0">
                                <div class="text-right">
                                    <div class="text-sm font-bold text-primary tabular-nums">
                                        Rp {{ Number(rate.price).toLocaleString('id-ID') }}
                                    </div>
                                </div>
                                <div
                                    class="size-4.5 rounded-full border flex items-center justify-center shrink-0"
                                    :class="
                                        form.courier_code === rate.courier_code &&
                                        form.courier_service_code === rate.courier_service_code
                                            ? 'border-[#853953] bg-[#853953] text-white'
                                            : 'border-stone-300 bg-white'
                                    "
                                >
                                    <Check
                                        v-if="
                                            form.courier_code === rate.courier_code &&
                                            form.courier_service_code === rate.courier_service_code
                                        "
                                        class="size-3"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3.5 border-t border-stone-100 space-y-2">
                        <div class="text-xs font-bold text-stone-700 uppercase tracking-wider">
                            Metode Pembayaran
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="form.payment_type = 'non_cod'"
                                class="p-2.5 rounded-lg border text-left transition-all cursor-pointer flex flex-col justify-between"
                                :class="
                                    form.payment_type === 'non_cod'
                                        ? 'border-[#853953] bg-[#853953]/5 ring-1.5 ring-[#853953]'
                                        : 'border-stone-200 bg-stone-50/50 hover:bg-stone-100/70'
                                "
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold text-stone-900">Non-COD</span>
                                    <CreditCard class="size-3.5 text-[#853953]" />
                                </div>
                                <span class="text-[11px] text-stone-500 leading-tight">
                                    Bayar online sekarang via Midtrans/QRIS
                                </span>
                            </button>

                            <button
                                type="button"
                                @click="form.payment_type = 'cod'"
                                class="p-2.5 rounded-lg border text-left transition-all cursor-pointer flex flex-col justify-between"
                                :class="
                                    form.payment_type === 'cod'
                                        ? 'border-[#853953] bg-[#853953]/5 ring-1.5 ring-[#853953]'
                                        : 'border-stone-200 bg-stone-50/50 hover:bg-stone-100/70'
                                "
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold text-stone-900">COD</span>
                                    <Banknote class="size-3.5 text-emerald-600" />
                                </div>
                                <span class="text-[11px] text-stone-500 leading-tight">
                                    Bayar di tempat saat paket sampai
                                </span>
                            </button>
                        </div>
                    </div>

                    <div class="pt-3.5 border-t border-stone-100 space-y-3">
                        <div class="space-y-1.5 text-sm">
                            <div class="flex items-center justify-between text-stone-600">
                                <span>Berat Paket</span>
                                <span class="font-semibold text-stone-800 tabular-nums">
                                    {{ weightKg || 1 }} kg ({{ form.weight.toLocaleString('id-ID') }} gr)
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-stone-600">
                                <span class="truncate pr-2">
                                    Ongkos Kirim {{ form.courier_service_name ? `(${form.courier_service_name})` : '' }}
                                </span>
                                <span class="font-semibold text-stone-800 tabular-nums shrink-0">
                                    Rp {{ Number(form.shipping_fee).toLocaleString('id-ID') }}
                                </span>
                            </div>
                            <div v-if="form.payment_type === 'cod'" class="flex items-center justify-between text-stone-600">
                                <span>Nilai Barang (COD)</span>
                                <span class="font-semibold text-stone-800 tabular-nums shrink-0">
                                    Rp {{ Number(form.item_value || 0).toLocaleString('id-ID') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-stone-600">
                                <span>Biaya Layanan</span>
                                <span class="font-semibold text-stone-800 tabular-nums shrink-0">
                                    Rp 1.000
                                </span>
                            </div>
                        </div>

                        <div v-if="form.payment_type === 'cod'" class="space-y-2">
                            <div class="p-3 rounded-lg bg-stone-50/90 space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-stone-700">Tagihan ke Penerima (COD)</span>
                                    <span class="text-base font-extrabold text-[#853953] tabular-nums">
                                        Rp {{ (Number(form.shipping_fee) + Number(form.item_value || 0)).toLocaleString('id-ID') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between pt-1.5 border-t border-stone-200/60 text-xs">
                                    <span class="text-stone-500">Hak Bersih Penjual (Saldo COD):</span>
                                    <span class="font-bold text-emerald-600 tabular-nums">
                                        Rp {{ Math.max(0, Number(form.item_value || 0) - 1000).toLocaleString('id-ID') }}
                                    </span>
                                </div>
                            </div>
                            <p class="text-[11px] text-stone-500 leading-normal">
                                Kurir akan menagih total Rp {{ (Number(form.shipping_fee) + Number(form.item_value || 0)).toLocaleString('id-ID') }} ke penerima. Saldo bersih Rp {{ Math.max(0, Number(form.item_value || 0) - 1000).toLocaleString('id-ID') }} otomatis masuk ke Saldo COD Anda setelah paket sampai (potong biaya layanan Rp 1.000).
                            </p>
                        </div>

                        <div v-else class="p-3 rounded-lg bg-stone-50/90 flex items-center justify-between">
                            <span class="text-sm font-bold text-stone-900">Total Tagihan</span>
                            <span class="text-lg font-extrabold text-primary tabular-nums">
                                Rp {{ (Number(form.shipping_fee) + 1000).toLocaleString('id-ID') }}
                            </span>
                        </div>

                        <Button
                            type="submit"
                            :disabled="!canSubmit"
                            class="w-full bg-[#853953] hover:bg-[#6c2e43] text-white font-semibold h-11 text-sm rounded-lg cursor-pointer shadow-xs"
                        >
                            <Loader2 v-if="form.processing" class="mr-2 size-4 animate-spin" />
                            <span>{{ form.payment_type === 'cod' ? 'Buat Pengiriman COD' : 'Buat Pengiriman & Bayar' }}</span>
                        </Button>
                    </div>
                </div>
            </div>
        </form>

        <Dialog :open="showAddStoreModal" @update:open="showAddStoreModal = $event">
            <DialogContent
                @pointer-down-outside="(e) => e.preventDefault()"
                @interact-outside="(e) => e.preventDefault()"
                class="sm:max-w-lg bg-white p-5 sm:p-6 rounded-xl border border-stone-200 shadow-xl max-h-[90vh] overflow-y-auto"
            >
                <DialogHeader class="text-left space-y-1">
                    <div class="flex items-center gap-2.5">
                        <div>
                            <DialogTitle class="text-base sm:text-lg font-bold text-stone-900">
                                {{ stores.length === 0 ? 'Atur Titik Penjemputan Pertama' : 'Tambah Titik Penjemputan' }}
                            </DialogTitle>
                            <DialogDescription class="text-xs text-stone-500">
                                {{ stores.length === 0 ? 'Lengkapi data toko/gudang asal sebelum mulai mengirim paket.' : 'Tambahkan alamat penjemputan baru untuk pengiriman Anda.' }}
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <form @submit.prevent="submitNewStore" class="space-y-3.5 mt-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div class="space-y-1">
                            <Label for="modal-store-name" class="text-xs sm:text-sm font-semibold text-stone-800">
                                Nama Toko / Gudang<span class="text-red-500 ml-1">*</span>
                            </Label>
                            <Input
                                id="modal-store-name"
                                v-model="storeForm.name"
                                placeholder="Gudang Utama Surabaya"
                                class="h-10 text-xs sm:text-sm bg-white border-stone-200"
                                required
                            />
                            <InputError :message="storeForm.errors.name" />
                        </div>

                        <div class="space-y-1">
                            <Label for="modal-store-phone" class="text-xs sm:text-sm font-semibold text-stone-800">
                                Nomor WhatsApp Pengirim<span class="text-red-500 ml-1">*</span>
                            </Label>
                            <div class="relative">
                                <Phone class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-stone-400" />
                                <Input
                                    id="modal-store-phone"
                                    v-model="storeForm.phone"
                                    placeholder="081234567890"
                                    class="h-10 pl-9 text-xs sm:text-sm bg-white border-stone-200"
                                    required
                                />
                            </div>
                            <InputError :message="storeForm.errors.phone" />
                        </div>
                    </div>

                    <div class="space-y-1 relative store-area-dropdown-container">
                        <Label for="modal-store-area" class="text-xs sm:text-sm font-semibold text-stone-800 flex items-center justify-between">
                            <span>Kecamatan & Kota Penjemputan<span class="text-red-500 ml-1">*</span></span>
                            <span class="text-[11px] text-[#853953] font-medium">Wajib pilih dari daftar</span>
                        </Label>

                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-stone-400" />
                            <Input
                                id="modal-store-area"
                                v-model="storeAreaQuery"
                                placeholder="Ketik kelurahan atau kecamatan penjemputan..."
                                class="h-10 pl-9 pr-9 text-xs sm:text-sm bg-white border-stone-200"
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
                                <div class="text-xs sm:text-sm font-bold text-stone-900">
                                    {{ area.name }}
                                </div>
                                <div class="text-[11px] text-stone-500">
                                    {{ area.administrative_division_level_3_name }}, {{ area.administrative_division_level_2_name }}, {{ area.administrative_division_level_1_name }}
                                    <span v-if="area.postal_code">({{ area.postal_code }})</span>
                                </div>
                            </div>
                        </div>

                        <span v-if="storeForm.errors.biteship_area_id" class="text-[11px] text-red-600 font-medium block">
                            Pilih area penjemputan dari daftar pencarian.
                        </span>
                    </div>

                    <div class="space-y-1">
                        <Label for="modal-store-address" class="text-xs sm:text-sm font-semibold text-stone-800">
                            Alamat Lengkap & Patokan<span class="text-red-500 ml-1">*</span>
                        </Label>
                        <textarea
                            id="modal-store-address"
                            v-model="storeForm.address"
                            rows="2"
                            placeholder="Jl. Raya Sukolilo No. 45, Ruko B3 (sebelah Bank BCA)"
                            class="w-full rounded-md border border-stone-200 bg-white px-3 py-2 text-xs sm:text-sm text-stone-900 placeholder:text-stone-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#853953] focus-visible:ring-offset-1"
                            required
                        />
                        <InputError :message="storeForm.errors.address" />
                    </div>

                    <DialogFooter class="pt-2 gap-2 sm:justify-end">
                        <Button
                            type="button"
                            variant="outline"
                            @click="showAddStoreModal = false"
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
    </div>
</template>
