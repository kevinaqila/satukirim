<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Clock, Loader2, Search } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { getCourierLogo } from '@/lib/status';
import { formatRupiah, type DefaultStoreInfo } from '@/types/dashboard';

interface RateResultItem {
    courier_code: string;
    courier_name?: string;
    courier_service_code?: string;
    courier_service_name?: string;
    service_name?: string;
    duration?: string;
    estimated_days?: string;
    price: number;
}

const props = withDefaults(
    defineProps<{
        defaultStore?: DefaultStoreInfo | null;
    }>(),
    {
        defaultStore: null,
    },
);

const defaultStoreFormattedAddress = computed(() => {
    if (!props.defaultStore) return '';
    const base = props.defaultStore.area_name || props.defaultStore.name;
    if (props.defaultStore.postal_code && !base.includes(props.defaultStore.postal_code)) {
        return `${base}. ${props.defaultStore.postal_code}`;
    }
    return base;
});

const originQuery = ref(defaultStoreFormattedAddress.value);
const originAreaId = ref(props.defaultStore?.biteship_area_id || '');
const isSearchingOrigin = ref(false);
const originSuggestions = ref<Array<{ id: string; name: string }>>([]);
const showOriginDropdown = ref(false);

const destinationQuery = ref('');
const destinationAreaId = ref('');
const isSearchingDest = ref(false);
const destSuggestions = ref<Array<{ id: string; name: string }>>([]);
const showDestDropdown = ref(false);

const packageWeight = ref('1');
const isCalculatingRates = ref(false);
const rateResults = ref<RateResultItem[]>([]);
const rateError = ref('');
const showRateResultsModal = ref(false);

let originSearchTimer: ReturnType<typeof setTimeout> | null = null;
const onOriginInputChange = () => {
    originAreaId.value = '';
    rateError.value = '';
    if (originSearchTimer) clearTimeout(originSearchTimer);
    const q = originQuery.value.trim();
    if (q.length < 2) {
        originSuggestions.value = [];
        showOriginDropdown.value = false;
        return;
    }

    isSearchingOrigin.value = true;
    originSearchTimer = setTimeout(async () => {
        try {
            const res = await fetch(`/api/public/areas?q=${encodeURIComponent(q)}`);
            const data = await res.json();
            if (data.success && Array.isArray(data.areas)) {
                originSuggestions.value = data.areas.slice(0, 5);
                showOriginDropdown.value = true;
            }
        } catch {
            originSuggestions.value = [];
        } finally {
            isSearchingOrigin.value = false;
        }
    }, 350);
};

const selectOrigin = (area: { id: string; name: string }) => {
    originAreaId.value = area.id;
    originQuery.value = area.name;
    showOriginDropdown.value = false;
};

const resetOriginToDefaultStore = () => {
    originQuery.value = defaultStoreFormattedAddress.value;
    originAreaId.value = props.defaultStore?.biteship_area_id || '';
    showOriginDropdown.value = false;
    rateError.value = '';
};

let destSearchTimer: ReturnType<typeof setTimeout> | null = null;
const onDestInputChange = () => {
    destinationAreaId.value = '';
    rateError.value = '';
    if (destSearchTimer) clearTimeout(destSearchTimer);
    const q = destinationQuery.value.trim();
    if (q.length < 2) {
        destSuggestions.value = [];
        showDestDropdown.value = false;
        return;
    }

    isSearchingDest.value = true;
    destSearchTimer = setTimeout(async () => {
        try {
            const res = await fetch(`/api/public/areas?q=${encodeURIComponent(q)}`);
            const data = await res.json();
            if (data.success && Array.isArray(data.areas)) {
                destSuggestions.value = data.areas.slice(0, 5);
                showDestDropdown.value = true;
            }
        } catch {
            destSuggestions.value = [];
        } finally {
            isSearchingDest.value = false;
        }
    }, 350);
};

const selectDestination = (area: { id: string; name: string }) => {
    destinationAreaId.value = area.id;
    destinationQuery.value = area.name;
    showDestDropdown.value = false;
};

const handleQuickRateCheck = async () => {
    rateError.value = '';
    rateResults.value = [];

    if (!originAreaId.value) {
        rateError.value = 'Silakan pilih kota / kecamatan asal pengiriman dari daftar pencarian.';
        return;
    }

    if (!destinationAreaId.value) {
        rateError.value = 'Silakan pilih kota / kecamatan tujuan dari daftar pencarian.';
        return;
    }

    isCalculatingRates.value = true;
    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        const res = await fetch('/api/public/rates', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                origin_area_id: originAreaId.value,
                destination_area_id: destinationAreaId.value,
                weight: parseFloat(packageWeight.value) || 1,
            }),
        });

        const data = await res.json();
        if (data.success && Array.isArray(data.pricing) && data.pricing.length > 0) {
            rateResults.value = [...data.pricing]
                .sort((a, b) => Number(a.price || 0) - Number(b.price || 0))
                .slice(0, 6);
            showRateResultsModal.value = true;
        } else {
            rateError.value = data.message || 'Tidak ada ekspedisi yang melayani rute ini.';
        }
    } catch {
        rateError.value = 'Gagal menghubungi server ekspedisi. Silakan coba sesaat lagi.';
    } finally {
        isCalculatingRates.value = false;
    }
};

const buildCreateShipmentUrl = (rate?: RateResultItem) => {
    const params = new URLSearchParams();
    if (originAreaId.value) params.set('origin_area_id', originAreaId.value);
    if (destinationAreaId.value) params.set('destination_area_id', destinationAreaId.value);
    if (destinationQuery.value) params.set('destination_area_name', destinationQuery.value);
    const grams = Math.max(100, Math.round((parseFloat(packageWeight.value) || 1) * 1000));
    params.set('weight', String(grams));
    if (rate?.courier_code) params.set('courier_code', String(rate.courier_code));
    if (rate?.courier_service_code) params.set('courier_service_code', String(rate.courier_service_code));
    return `/shipments/create?${params.toString()}`;
};

const handleDocumentClick = (e: MouseEvent) => {
    const target = e.target as HTMLElement;
    if (!target.closest('.dest-search-container')) {
        showDestDropdown.value = false;
    }
    if (!target.closest('.origin-search-container')) {
        showOriginDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleDocumentClick);
});

onUnmounted(() => {
    document.removeEventListener('click', handleDocumentClick);
});
</script>

<template>
    <div class="p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between pb-1">
                <h2 class="text-base font-bold text-[#2C2C2C] tracking-tight">
                    Cek Ongkir Cepat
                </h2>
            </div>

            <div class="mt-3.5 space-y-3">
                <div class="origin-search-container relative">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs sm:text-sm font-semibold text-stone-700">
                            Kota / Kecamatan Asal
                        </label>
                        <button
                            v-if="defaultStore && originQuery !== defaultStoreFormattedAddress"
                            type="button"
                            @click="resetOriginToDefaultStore"
                            class="text-xs font-semibold text-[#853953] hover:underline cursor-pointer"
                        >
                            Reset ke Toko
                        </button>
                    </div>
                    <div class="relative">
                        <Input
                            v-model="originQuery"
                            @input="onOriginInputChange"
                            placeholder="Ketik kota asal (Surabaya)..."
                            class="text-sm h-10 pr-8 rounded-lg"
                        />
                        <div class="absolute right-3 top-1/2 -translate-y-1/2">
                            <Loader2 v-if="isSearchingOrigin" class="size-4 animate-spin text-stone-400" />
                            <Search v-else class="size-4 text-stone-400" />
                        </div>
                    </div>

                    <div
                        v-if="showOriginDropdown && originSuggestions.length > 0"
                        class="absolute left-0 right-0 top-full mt-1 bg-white border border-stone-200 rounded-lg shadow-lg z-30 max-h-48 overflow-y-auto divide-y divide-stone-100"
                    >
                        <button
                            v-for="area in originSuggestions"
                            :key="area.id"
                            type="button"
                            @click="selectOrigin(area)"
                            class="w-full text-left px-3.5 py-2.5 text-sm hover:bg-stone-50 transition-colors cursor-pointer"
                        >
                            {{ area.name }}
                        </button>
                    </div>
                </div>

                <div class="dest-search-container relative">
                    <label class="block text-xs sm:text-sm font-semibold text-stone-700 mb-1.5">
                        Kota / Kecamatan Tujuan
                    </label>
                    <div class="relative">
                        <Input
                            v-model="destinationQuery"
                            @input="onDestInputChange"
                            placeholder="Ketik kota tujuan (Jakarta)..."
                            class="text-sm h-10 pr-8 rounded-lg"
                        />
                        <div class="absolute right-3 top-1/2 -translate-y-1/2">
                            <Loader2 v-if="isSearchingDest" class="size-4 animate-spin text-stone-400" />
                            <Search v-else class="size-4 text-stone-400" />
                        </div>
                    </div>

                    <div
                        v-if="showDestDropdown && destSuggestions.length > 0"
                        class="absolute left-0 right-0 top-full mt-1 bg-white border border-stone-200 rounded-lg shadow-lg z-30 max-h-48 overflow-y-auto divide-y divide-stone-100"
                    >
                        <button
                            v-for="area in destSuggestions"
                            :key="area.id"
                            type="button"
                            @click="selectDestination(area)"
                            class="w-full text-left px-3.5 py-2.5 text-sm hover:bg-stone-50 transition-colors cursor-pointer"
                        >
                            {{ area.name }}
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-stone-700 mb-1.5">
                        Estimasi Berat Paket (kg)
                    </label>
                    <Input
                        v-model="packageWeight"
                        type="number"
                        step="0.5"
                        min="0.1"
                        max="10"
                        placeholder="1"
                        class="text-sm h-10 rounded-lg"
                    />
                </div>

                <p v-if="rateError" class="text-xs text-red-600 font-medium">
                    {{ rateError }}
                </p>
            </div>
        </div>

        <div class="mt-4">
            <Button
                type="button"
                @click="handleQuickRateCheck"
                :disabled="isCalculatingRates"
                class="w-full bg-[#853953] hover:bg-[#6c2e43] text-white text-sm font-semibold h-10 rounded-lg cursor-pointer"
            >
                <Loader2 v-if="isCalculatingRates" class="mr-2 size-4 animate-spin" />
                <span v-else>Cek Tarif Ekspedisi</span>
            </Button>
        </div>

        <Teleport to="body">
            <div
                v-if="showRateResultsModal"
                class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
            >
                <div class="bg-white rounded-xl max-w-md w-full border border-stone-200 shadow-2xl overflow-hidden p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                        <div>
                            <h3 class="text-base font-bold text-[#2C2C2C] tracking-tight">
                                Perbandingan Tarif Ekspedisi
                            </h3>
                            <p class="text-xs text-stone-500 mt-0.5">
                                {{ originQuery || defaultStoreFormattedAddress || 'Asal' }} → {{ destinationQuery || 'Tujuan' }} ({{ packageWeight }} kg)
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="showRateResultsModal = false"
                            class="text-stone-400 hover:text-stone-600 text-lg leading-none cursor-pointer"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="space-y-2.5 max-h-72 overflow-y-auto pr-1">
                        <div
                            v-for="(rate, idx) in rateResults"
                            :key="idx"
                            class="p-3 rounded-lg border border-stone-200/80 hover:border-[#853953] transition-colors flex items-center justify-between gap-3"
                        >
                            <div class="min-w-0">
                                <div class="flex items-center gap-2.5">
                                    <div class="h-6 w-12 bg-white rounded border border-stone-200/80 p-0.5 flex items-center justify-center shrink-0 shadow-2xs">
                                        <img
                                            v-if="getCourierLogo(rate.courier_code)"
                                            :src="getCourierLogo(rate.courier_code)!"
                                            :alt="rate.courier_code"
                                            class="max-h-4 max-w-full object-contain"
                                        />
                                        <span v-else class="text-xs font-bold font-mono text-stone-600 uppercase">
                                            {{ rate.courier_code }}
                                        </span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-sm text-[#2C2C2C] truncate">
                                            {{ rate.courier_name ? `${rate.courier_name} - ` : '' }}{{ rate.courier_service_name || rate.service_name || 'Reguler' }}
                                        </div>
                                        <div class="text-xs text-stone-500 mt-0.5 flex items-center gap-1 font-medium">
                                            <Clock class="size-3.5 shrink-0" />
                                            <span>Estimasi {{ rate.duration || rate.estimated_days || '1-2 Hari' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <div class="font-bold text-sm sm:text-base text-[#853953] tabular-nums">
                                    Rp {{ formatRupiah(rate.price) }}
                                </div>
                                <Link
                                    :href="buildCreateShipmentUrl(rate)"
                                    class="inline-block mt-1 text-xs font-semibold text-[#853953] hover:underline cursor-pointer"
                                >
                                    Kirim Paket Ini →
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="showRateResultsModal = false"
                            class="text-xs sm:text-sm font-medium px-4 h-9"
                        >
                            Tutup
                        </Button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

