<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Check,
    ChevronDown,
    ChevronRight,
    Loader2,
    MapPin,
    Phone,
    Plus,
    Search,
    Store,
    User,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import AppTopBar from '@/components/AppTopBar.vue';
import InputError from '@/components/InputError.vue';
import { getCsrfHeaders } from '@/lib/csrf';
import CourierRatesPanel from '@/components/shipments/CourierRatesPanel.vue';
import QuickAddStoreDialog from '@/components/shipments/QuickAddStoreDialog.vue';
import ShipmentItemsCard from '@/components/shipments/ShipmentItemsCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type {
    CourierRate,
    CreateShipmentItem,
    StoreOption,
} from '@/types/shipments';

interface DestAreaSuggestion {
    id: string;
    name: string;
    postal_code?: string | number | null;
    administrative_division_level_1_name?: string;
    administrative_division_level_2_name?: string;
    administrative_division_level_3_name?: string;
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

const showStoreDropdown = ref(false);
const showAddStoreModal = ref(props.stores.length === 0);

const form = useForm({
    store_id: props.defaultStore?.id ?? (props.stores[0]?.id ?? 0),
    recipient_name: '',
    recipient_phone: '',
    destination_address: '',
    destination_postal_code: '',
    destination_area_id: '',
    destination_area_name: '',
    items: [
        {
            name: '',
            description: '',
            qty: 1,
            weight: 500,
            value: 50000,
            length: null,
            width: null,
            height: null,
        },
    ] as CreateShipmentItem[],
    weight: 500,
    item_description: '',
    item_value: 50000,
    courier_code: '',
    courier_service_code: '',
    courier_service_name: '',
    shipping_fee: 0,
    estimated_delivery: '',
    payment_type: 'non_cod',
    is_cod: false,
});

const addItem = () => {
    if (form.items.length >= 10) return;
    form.items.push({
        name: '',
        description: '',
        qty: 1,
        weight: 250,
        value: 50000,
        length: null,
        width: null,
        height: null,
    });
};

const removeItem = (index: number) => {
    if (form.items.length <= 1) return;
    form.items.splice(index, 1);
};

const totalWeightGrams = computed(() => {
    const sum = form.items.reduce((acc, it) => {
        const q = Math.max(1, Number(it.qty) || 1);
        const w = Math.max(10, Number(it.weight) || 100);
        return acc + q * w;
    }, 0);
    return Math.min(10000, Math.max(100, Math.round(sum)));
});

const totalWeightKg = computed(() => {
    const kg = totalWeightGrams.value / 1000;
    return kg.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
});

const totalItemValue = computed(() => {
    return form.items.reduce((acc, it) => {
        const q = Math.max(1, Number(it.qty) || 1);
        const v = Math.max(0, Number(it.value) || 0);
        return acc + q * v;
    }, 0);
});

watch(
    [totalWeightGrams, totalItemValue],
    ([newWeight, newValue]) => {
        form.weight = newWeight;
        form.item_value = newValue;
    },
    { immediate: true },
);

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
};

const preselectedCourier = ref<{ code: string; service: string } | null>(null);

onMounted(() => {
    document.addEventListener('click', handleOutsideClick);

    const search = new URLSearchParams(window.location.search);
    const originAreaParam = search.get('origin_area_id');
    const destAreaIdParam = search.get('destination_area_id');
    const destAreaNameParam = search.get('destination_area_name');
    const weightParam = search.get('weight');
    const lengthParam = search.get('length');
    const widthParam = search.get('width');
    const heightParam = search.get('height');
    const courierCodeParam = search.get('courier_code');
    const courierServiceParam = search.get('courier_service_code');

    if (originAreaParam) {
        const matchedStore = props.stores.find((s) => s.biteship_area_id === originAreaParam);
        if (matchedStore) {
            form.store_id = matchedStore.id;
        }
    }

    if (weightParam) {
        const parsedWeight = Math.round(Number(weightParam));
        if (!Number.isNaN(parsedWeight) && parsedWeight >= 100 && form.items.length > 0) {
            form.items[0].qty = 1;
            form.items[0].weight = Math.min(10000, parsedWeight);
        }
    }

    if (lengthParam && widthParam && heightParam && form.items.length > 0) {
        const l = Math.round(Number(lengthParam));
        const w = Math.round(Number(widthParam));
        const h = Math.round(Number(heightParam));
        if (l >= 1 && w >= 1 && h >= 1) {
            form.items[0].length = Math.min(500, l);
            form.items[0].width = Math.min(500, w);
            form.items[0].height = Math.min(500, h);
        }
    }

    if (courierCodeParam && courierServiceParam) {
        preselectedCourier.value = {
            code: courierCodeParam.toLowerCase(),
            service: courierServiceParam.toLowerCase(),
        };
    }

    if (destAreaIdParam && destAreaNameParam) {
        form.destination_area_id = destAreaIdParam;
        form.destination_area_name = destAreaNameParam;
        destAreaQuery.value = destAreaNameParam;
    }
});

onUnmounted(() => {
    document.removeEventListener('click', handleOutsideClick);
});

const destAreaQuery = ref('');
const destAreaResults = ref<DestAreaSuggestion[]>([]);
const isSearchingDestArea = ref(false);
const showDestAreaDropdown = ref(false);
let destSearchTimer: ReturnType<typeof setTimeout> | null = null;
let rateReloadTimer: ReturnType<typeof setTimeout> | null = null;

watch(destAreaQuery, (val) => {
    if (!val || val.length < 2) {
        destAreaResults.value = [];
        showDestAreaDropdown.value = false;
        if (!val) {
            form.destination_area_id = '';
            form.destination_area_name = '';
            ratesList.value = [];
            form.courier_code = '';
            form.courier_service_code = '';
            form.courier_service_name = '';
            form.shipping_fee = 0;
            form.estimated_delivery = '';
        }
        return;
    }

    if (form.destination_area_name && val === form.destination_area_name) {
        return;
    }

    if (destSearchTimer) clearTimeout(destSearchTimer);
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

const selectDestArea = (item: DestAreaSuggestion) => {
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

const isCourierDisabledForCod = (rate: CourierRate): boolean => {
    return form.payment_type === 'cod' && rate.available_for_cash_on_delivery === false;
};

const selectCourier = (rate: CourierRate) => {
    form.courier_code = rate.courier_code;
    form.courier_service_code = rate.courier_service_code;
    form.courier_service_name = `${rate.courier_name} ${rate.courier_service_name}`;
    form.shipping_fee = rate.price;
    form.estimated_delivery = rate.duration;
};

const ensureValidCourierSelected = () => {
    if (!ratesList.value.length) return;

    if (preselectedCourier.value) {
        const target = preselectedCourier.value;
        preselectedCourier.value = null;
        const matchedPreselected = ratesList.value.find(
            (r) =>
                r.courier_code.toLowerCase() === target.code &&
                r.courier_service_code.toLowerCase() === target.service,
        );
        if (matchedPreselected && !isCourierDisabledForCod(matchedPreselected)) {
            selectCourier(matchedPreselected);
            return;
        }
    }

    const stillExists = ratesList.value.find(
        (r) => r.courier_code === form.courier_code && r.courier_service_code === form.courier_service_code,
    );

    if (stillExists && !isCourierDisabledForCod(stillExists)) {
        selectCourier(stillExists);
        return;
    }

    const firstValid = form.payment_type === 'cod'
        ? ratesList.value.find((r) => r.available_for_cash_on_delivery !== false)
        : ratesList.value[0];

    if (firstValid) {
        selectCourier(firstValid);
    } else {
        form.courier_code = '';
        form.courier_service_code = '';
        form.courier_service_name = '';
        form.shipping_fee = 0;
        form.estimated_delivery = '';
    }
};

watch(
    () => form.payment_type,
    () => {
        ensureValidCourierSelected();
    },
);

const activeItemsRatesSignature = computed(() =>
    form.items
        .map((it) => {
            const q = Math.max(1, Number(it.qty) || 1);
            const w = Math.max(10, Number(it.weight) || 100);
            const hasDim = Number(it.length) > 0 && Number(it.width) > 0 && Number(it.height) > 0;
            const dim = hasDim ? `${Math.round(Number(it.length))}x${Math.round(Number(it.width))}x${Math.round(Number(it.height))}` : '';
            return `${q}:${w}:${dim}`;
        })
        .join('|'),
);

const fetchRates = async () => {
    if (!form.store_id || !form.destination_area_id || !form.weight) {
        return;
    }

    isLoadingRates.value = true;
    ratesFetchError.value = null;

    try {
        const itemsPayload = form.items.map((it) => {
            const entry: Record<string, string | number> = {
                name: it.name.trim() || 'Paket Barang',
                qty: Math.max(1, Number(it.qty) || 1),
                weight: Math.min(10000, Math.max(10, Math.round(Number(it.weight) || 100))),
                value: Math.max(0, Math.round(Number(it.value) || 0)),
            };
            const desc = (it.description || '').trim();
            if (desc) {
                entry.description = desc;
            }
            if (Number(it.length) > 0 && Number(it.width) > 0 && Number(it.height) > 0) {
                entry.length = Math.min(500, Math.round(Number(it.length)));
                entry.width = Math.min(500, Math.round(Number(it.width)));
                entry.height = Math.min(500, Math.round(Number(it.height)));
            }
            return entry;
        });

        const response = await fetch('/shipments/rates', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...getCsrfHeaders(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                store_id: form.store_id,
                destination_area_id: form.destination_area_id,
                weight: form.weight,
                item_value: form.item_value || 50000,
                items: itemsPayload,
            }),
        });

        const data = await response.json();
        if (data.success && Array.isArray(data.pricing) && data.pricing.length > 0) {
            ratesList.value = data.pricing;
            ensureValidCourierSelected();
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
    () => [form.store_id, form.destination_area_id, form.weight, activeItemsRatesSignature.value],
    ([storeId, destAreaId, weight]) => {
        if (!storeId || !destAreaId || !weight) {
            return;
        }
        if (rateReloadTimer) clearTimeout(rateReloadTimer);
        rateReloadTimer = setTimeout(() => {
            fetchRates();
        }, 300);
    },
);

const canSubmit = computed(() => {
    const hasValidItems = form.items.length > 0 && form.items.every((it) => {
        const dimCount = [it.length, it.width, it.height].filter((v) => typeof v === 'number' && v > 0).length;
        return it.name.trim().length > 0 && Number(it.qty) >= 1 && Number(it.weight) >= 10 && (dimCount === 0 || dimCount === 3);
    });
    const baseValid = Boolean(
        form.store_id &&
            form.recipient_name.trim() &&
            form.recipient_phone.trim() &&
            form.destination_area_id &&
            form.destination_address.trim() &&
            hasValidItems &&
            form.courier_code &&
            form.shipping_fee > 0 &&
            !form.processing,
    );
    if (!baseValid) return false;
    if (form.payment_type === 'cod') {
        if (!form.item_value || Number(form.item_value) < 1000) {
            return false;
        }
        const selectedRate = ratesList.value.find(
            (r) => r.courier_code === form.courier_code && r.courier_service_code === form.courier_service_code,
        );
        if (!selectedRate || selectedRate.available_for_cash_on_delivery === false) {
            return false;
        }
    }
    return true;
});

const submitOrder = () => {
    if (!canSubmit.value) {
        return;
    }
    const summary = form.items
        .map((it) => `${it.name.trim() || 'Barang'}${Number(it.qty) > 1 ? ` (${it.qty}x)` : ''}`)
        .join(', ');
    form.item_description = summary || 'Barang Dagangan UMKM';
    form.weight = totalWeightGrams.value;
    form.item_value = totalItemValue.value;
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
                        <h2 class="text-base font-semibold text-[#2C2C2C] flex items-center gap-2">
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
                            <p class="text-sm font-semibold text-stone-900">
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
                                <span class="text-sm font-semibold text-stone-900 truncate">
                                    {{ selectedStore?.name }}
                                </span>
                                <span
                                    v-if="selectedStore?.is_default"
                                    class="px-2 py-0.5 rounded-md bg-[#853953]/10 text-[#853953] text-xs font-semibold shrink-0"
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
                                        <span class="text-sm font-semibold truncate">
                                            {{ store.name }}
                                        </span>
                                        <span
                                            v-if="store.is_default"
                                            class="px-1.5 py-0.5 rounded bg-[#853953]/10 text-[#853953] text-xs font-semibold shrink-0"
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
                            class="p-3 sm:p-3.5 rounded-lg bg-stone-50/80 border border-stone-200/70 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5"
                        >
                            <div class="flex items-start gap-2.5 min-w-0">
                                <MapPin class="size-4 text-[#853953] shrink-0 mt-0.5" />
                                <div class="min-w-0">
                                    <p class="text-xs sm:text-sm font-medium text-stone-800 leading-snug">
                                        {{ selectedStore.address }}
                                    </p>
                                    <p v-if="selectedStore.area_name" class="text-xs text-stone-500 mt-0.5">
                                        {{ selectedStore.area_name }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs sm:text-sm text-stone-600 font-medium tabular-nums shrink-0 pl-6.5 sm:pl-0">
                                <Phone class="size-3.5 text-stone-400 shrink-0" />
                                <span>{{ selectedStore.phone }}</span>
                            </div>
                        </div>
                        <InputError :message="form.errors.store_id" />
                    </div>
                </div>

                <div class="rounded-xl border border-stone-200/80 bg-white shadow-xs p-4 sm:p-5 space-y-3.5">
                    <h2 class="text-base font-semibold text-[#2C2C2C] flex items-center gap-2">
                        <User class="size-4.5 text-[#853953]" />
                        <span>Informasi Penerima & Tujuan</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div class="space-y-1.5">
                            <Label for="rec-name" class="text-sm font-medium text-stone-700">
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
                            <Label for="rec-phone" class="text-sm font-medium text-stone-700">
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
                            <Label for="dest-area" class="text-sm font-medium text-stone-700">
                                Kecamatan / Kota Tujuan
                            </Label>
                            <span
                                v-if="form.destination_area_id"
                                class="inline-flex items-center gap-1 text-xs font-medium text-primary"
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
                                placeholder="Ketik kecamatan atau kota tujuan (Kebon Jeruk, Jakarta)..."
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
                            <Label for="dest-address" class="text-sm font-medium text-stone-700">
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
                            <Label for="dest-zip" class="text-sm font-medium text-stone-700">
                                Kode Pos
                            </Label>
                            <Input
                                id="dest-zip"
                                v-model="form.destination_postal_code"
                                placeholder="11530"
                                class="text-sm h-10"
                            />
                            <InputError :message="form.errors.destination_postal_code" />
                        </div>
                    </div>
                </div>

                <ShipmentItemsCard
                    :items="form.items"
                    :total-weight-kg="totalWeightKg"
                    :errors="(form.errors as Record<string, string | undefined>)"
                    @add-item="addItem"
                    @remove-item="removeItem"
                />
            </div>

            <div class="lg:col-span-5 lg:sticky lg:top-4 space-y-3.5 sm:space-y-4">
                <CourierRatesPanel
                    :destination-area-id="form.destination_area_id"
                    :is-loading-rates="isLoadingRates"
                    :rates-fetch-error="ratesFetchError"
                    :rates-list="ratesList"
                    :courier-code="form.courier_code"
                    :courier-service-code="form.courier_service_code"
                    :courier-service-name="form.courier_service_name"
                    :shipping-fee="form.shipping_fee"
                    :payment-type="form.payment_type"
                    :weight="form.weight"
                    :total-weight-kg="totalWeightKg"
                    :item-value="form.item_value"
                    :can-submit="canSubmit"
                    :processing="form.processing"
                    :courier-error="form.errors.courier_code"
                    @refresh="fetchRates"
                    @select-courier="selectCourier"
                    @update:payment-type="form.payment_type = $event"
                />
            </div>
        </form>

        <QuickAddStoreDialog
            v-model:open="showAddStoreModal"
            :has-existing-stores="stores.length > 0"
        />
    </div>
</template>
