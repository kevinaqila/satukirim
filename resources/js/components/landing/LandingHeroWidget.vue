<script setup lang="ts">
import {
    AlertCircle,
    Box,
    Clock,
    Loader2,
    MapPin,
    Package,
    Search,
    Truck,
    Wallet,
} from '@lucide/vue';
import { onMounted, onUnmounted, ref } from 'vue';
import { quickRouteMap, quickRoutes } from '@/data/landing';

type WidgetTab = 'ongkir' | 'resi';

interface AreaItem {
    id: string;
    name: string;
    administrative_division_level_1_name: string;
    administrative_division_level_2_name: string;
    administrative_division_level_3_name: string;
    postal_code: number | string;
}

interface CourierRateItem {
    name: string;
    service: string;
    eta: string;
    price: string;
    rawPrice: number;
    tag: string;
    best: boolean;
}

const tab = ref<WidgetTab>('ongkir');
const showDemo = ref(false);

const originInput = ref('Gambir, Jakarta Pusat');
const originAreaId = ref('IDnp10110');
const destinationInput = ref('Gubeng, Surabaya');
const destinationAreaId = ref('IDnp60281');
const weightInput = ref('1');
const resiInput = ref('');
const courierInput = ref('sicepat');

const originSuggestions = ref<AreaItem[]>([]);
const destSuggestions = ref<AreaItem[]>([]);
const showOriginDropdown = ref(false);
const showDestDropdown = ref(false);
const isOriginSearching = ref(false);
const isDestSearching = ref(false);
const originHasSearched = ref(false);
const destHasSearched = ref(false);

const isSearchingRates = ref(false);
const rateErrorMessage = ref('');
const liveRates = ref<CourierRateItem[]>([]);

const isTrackingResi = ref(false);
const trackResult = ref<any>(null);
const trackErrorMessage = ref('');

function closeAllDropdowns(): void {
    showOriginDropdown.value = false;
    showDestDropdown.value = false;
}

function resetTab(next: WidgetTab = 'ongkir'): void {
    tab.value = next;
    showDemo.value = false;
    closeAllDropdowns();
}

defineExpose({ resetTab });

let originDebounceTimer: ReturnType<typeof setTimeout> | null = null;
function onOriginInput(): void {
    showDestDropdown.value = false;
    originAreaId.value = '';
    originHasSearched.value = false;
    if (originDebounceTimer) clearTimeout(originDebounceTimer);

    const query = originInput.value.trim();
    if (query.length < 2) {
        originSuggestions.value = [];
        showOriginDropdown.value = false;
        isOriginSearching.value = false;
        return;
    }

    isOriginSearching.value = true;
    showOriginDropdown.value = true;

    originDebounceTimer = setTimeout(async () => {
        try {
            const res = await fetch(`/api/public/areas?q=${encodeURIComponent(query)}`);
            const data = await res.json();
            originSuggestions.value = data.success && Array.isArray(data.areas) ? data.areas : [];
        } catch {
            originSuggestions.value = [];
        } finally {
            isOriginSearching.value = false;
            originHasSearched.value = true;
            showOriginDropdown.value = true;
        }
    }, 300);
}

function onOriginFocus(): void {
    showDestDropdown.value = false;
    if (originSuggestions.value.length > 0 || (originHasSearched.value && originInput.value.trim().length >= 2)) {
        showOriginDropdown.value = true;
    } else if (originInput.value.trim().length >= 2) {
        onOriginInput();
    }
}

function selectOrigin(area: AreaItem): void {
    const district = area.administrative_division_level_3_name || area.name;
    const city = area.administrative_division_level_2_name;
    const postal = area.postal_code ? ` (${area.postal_code})` : '';
    originInput.value = city ? `${district}, ${city}${postal}` : `${area.name}`;
    originAreaId.value = area.id;
    originHasSearched.value = false;
    closeAllDropdowns();
}

let destDebounceTimer: ReturnType<typeof setTimeout> | null = null;
function onDestInput(): void {
    showOriginDropdown.value = false;
    destinationAreaId.value = '';
    destHasSearched.value = false;
    if (destDebounceTimer) clearTimeout(destDebounceTimer);

    const query = destinationInput.value.trim();
    if (query.length < 2) {
        destSuggestions.value = [];
        showDestDropdown.value = false;
        isDestSearching.value = false;
        return;
    }

    isDestSearching.value = true;
    showDestDropdown.value = true;

    destDebounceTimer = setTimeout(async () => {
        try {
            const res = await fetch(`/api/public/areas?q=${encodeURIComponent(query)}`);
            const data = await res.json();
            destSuggestions.value = data.success && Array.isArray(data.areas) ? data.areas : [];
        } catch {
            destSuggestions.value = [];
        } finally {
            isDestSearching.value = false;
            destHasSearched.value = true;
            showDestDropdown.value = true;
        }
    }, 300);
}

function onDestFocus(): void {
    showOriginDropdown.value = false;
    if (destSuggestions.value.length > 0 || (destHasSearched.value && destinationInput.value.trim().length >= 2)) {
        showDestDropdown.value = true;
    } else if (destinationInput.value.trim().length >= 2) {
        onDestInput();
    }
}

function selectDest(area: AreaItem): void {
    const district = area.administrative_division_level_3_name || area.name;
    const city = area.administrative_division_level_2_name;
    const postal = area.postal_code ? ` (${area.postal_code})` : '';
    destinationInput.value = city ? `${district}, ${city}${postal}` : `${area.name}`;
    destinationAreaId.value = area.id;
    destHasSearched.value = false;
    closeAllDropdowns();
}

let touchStartY = 0;
let touchStartX = 0;

function onItemPointerDown(e: PointerEvent): void {
    touchStartY = e.clientY;
    touchStartX = e.clientX;
}

function onItemPointerUp(e: PointerEvent, callback: () => void): void {
    const deltaY = Math.abs(e.clientY - touchStartY);
    const deltaX = Math.abs(e.clientX - touchStartX);
    if (deltaY < 10 && deltaX < 10) {
        callback();
    }
}

function handleDocumentClick(event: MouseEvent): void {
    const target = event.target as HTMLElement | null;
    if (!target) return;
    if (!target.closest('.field-origin') && !target.closest('.field-destination')) {
        closeAllDropdowns();
    }
}

onMounted(() => {
    document.addEventListener('click', handleDocumentClick);
});

onUnmounted(() => {
    document.removeEventListener('click', handleDocumentClick);
});

function selectQuickRoute(from: string, to: string): void {
    closeAllDropdowns();
    const key = `${from}-${to}`;
    const mapped = quickRouteMap[key];
    if (mapped) {
        originInput.value = mapped.fromName;
        originAreaId.value = mapped.fromId;
        destinationInput.value = mapped.toName;
        destinationAreaId.value = mapped.toId;
    } else {
        originInput.value = from;
        destinationInput.value = to;
    }
    submitWidget();
}

async function submitWidget(): Promise<void> {
    closeAllDropdowns();
    showDemo.value = true;

    if (tab.value === 'ongkir') {
        isSearchingRates.value = true;
        rateErrorMessage.value = '';
        liveRates.value = [];

        try {
            let oId = originAreaId.value;
            let dId = destinationAreaId.value;

            if (!oId && originInput.value.trim().length >= 2) {
                const res = await fetch(`/api/public/areas?q=${encodeURIComponent(originInput.value.trim())}`);
                const data = await res.json();
                if (data.areas?.[0]?.id) {
                    oId = data.areas[0].id;
                    originAreaId.value = oId;
                }
            }

            if (!dId && destinationInput.value.trim().length >= 2) {
                const res = await fetch(`/api/public/areas?q=${encodeURIComponent(destinationInput.value.trim())}`);
                const data = await res.json();
                if (data.areas?.[0]?.id) {
                    dId = data.areas[0].id;
                    destinationAreaId.value = dId;
                }
            }

            const parsedWeight = parseFloat(weightInput.value);
            if (isNaN(parsedWeight) || parsedWeight < 0.1 || parsedWeight > 10) {
                rateErrorMessage.value = 'Berat paket harus antara 0.1 kg hingga maksimal 10 kg (skala parsel retail non-kargo).';
                return;
            }

            const response = await fetch('/api/public/rates', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    origin_area_id: oId || 'IDnp10110',
                    destination_area_id: dId || 'IDnp60281',
                    weight: parsedWeight,
                }),
            });

            const result = await response.json();

            if (response.status === 422) {
                rateErrorMessage.value = result.errors?.weight?.[0] || result.message || 'Validasi formulir gagal.';
                return;
            }

            if (result.success && Array.isArray(result.pricing) && result.pricing.length > 0) {
                const sorted = [...result.pricing].sort((a: any, b: any) => a.price - b.price);
                liveRates.value = sorted.map((p: any, idx: number) => ({
                    name: `${p.courier_name} ${p.courier_service_name}`,
                    service: p.courier_service_name,
                    eta: p.duration ? `${p.duration}` : '1 - 3 hari',
                    price: new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(p.price),
                    rawPrice: p.price,
                    tag: idx === 0 ? 'Termurah' : (idx === 1 ? 'Populer' : ''),
                    best: idx === 0,
                }));
            } else {
                rateErrorMessage.value = result.message || 'Tidak ada ekspedisi yang melayani rute ini. Coba pilih kelurahan/kecamatan yang lebih spesifik.';
            }
        } catch {
            rateErrorMessage.value = 'Terjadi gangguan koneksi saat menghubungi server Biteship. Silakan coba kembali.';
        } finally {
            isSearchingRates.value = false;
        }
    } else {
        if (!resiInput.value.trim()) {
            return;
        }
        isTrackingResi.value = true;
        trackErrorMessage.value = '';
        trackResult.value = null;

        try {
            const res = await fetch(`/api/public/track?courier=${encodeURIComponent(courierInput.value)}&waybill=${encodeURIComponent(resiInput.value.trim())}`);
            const data = await res.json();

            if (data.success && data.data) {
                trackResult.value = data.data;
            } else {
                trackErrorMessage.value = data.message || 'Nomor resi tidak ditemukan atau belum tercatat di ekspedisi.';
            }
        } catch {
            trackErrorMessage.value = 'Gagal memeriksa status resi saat ini. Silakan coba sesaat lagi.';
        } finally {
            isTrackingResi.value = false;
        }
    }
}
</script>

<template>
    <div class="hero-widget">
        <div class="widget">
            <div class="widget-tabs">
                <button type="button" class="tab" :class="{ active: tab === 'ongkir' }" @click="resetTab('ongkir')">
                    <Wallet class="icon" style="width: 1rem; height: 1rem" /> Cek Ongkir
                </button>
                <button type="button" class="tab" :class="{ active: tab === 'resi' }" @click="resetTab('resi')">
                    <Package class="icon" style="width: 1rem; height: 1rem" /> Lacak Status
                </button>
            </div>
            <form class="widget-body" @submit.prevent="submitWidget">
                <p class="widget-title">{{ tab === 'ongkir' ? 'Berapa ongkir paketmu hari ini?' : 'Pantau posisi barang pesanan' }}</p>
                <p class="widget-sub">
                    {{ tab === 'ongkir' ? 'Bandingkan tarif terbaik dari 8 ekspedisi resmi (JNE, J&T, SiCepat, AnterAja, TIKI, POS, Ninja, Lion).' : 'Masukkan nomor resi resmi untuk memantau pergerakan paket.' }}
                </p>

                <div v-if="tab === 'ongkir'" class="quick-routes">
                    <button
                        v-for="route in quickRoutes"
                        :key="`${route.from}-${route.to}`"
                        type="button"
                        class="route-pill"
                        @click="selectQuickRoute(route.from, route.to)"
                    >
                        {{ route.from }} → {{ route.to }}
                    </button>
                </div>

                <template v-if="tab === 'ongkir'">
                    <div class="field field-origin" :class="{ 'is-open': showOriginDropdown }">
                        <label class="field-label" for="origin-input">Kota / Kecamatan Asal</label>
                        <span class="input-wrap">
                            <MapPin class="icon" />
                            <input
                                id="origin-input"
                                v-model="originInput"
                                required
                                placeholder="Ketik nama kecamatan atau 5 digit kode pos"
                                autocomplete="off"
                                @input="onOriginInput"
                                @focus="onOriginFocus"
                            />
                            <Loader2 v-if="isOriginSearching" class="icon-spin animate-spin shrink-0 text-[#853953]" style="width: 1rem; height: 1rem; margin-right: 0.5rem;" />
                        </span>
                        <ul v-if="showOriginDropdown" class="area-autocomplete-list">
                            <li v-if="isOriginSearching" class="area-autocomplete-loading">
                                <Loader2 class="icon-spin animate-spin shrink-0 text-[#853953]" style="width: 1rem; height: 1rem;" />
                                <span>Mencari wilayah...</span>
                            </li>
                            <template v-else-if="originSuggestions.length > 0">
                                <li
                                    v-for="area in originSuggestions"
                                    :key="area.id"
                                    class="area-autocomplete-item"
                                    @pointerdown="onItemPointerDown"
                                    @pointerup="onItemPointerUp($event, () => selectOrigin(area))"
                                    @click="selectOrigin(area)"
                                >
                                    <div class="area-item-header">
                                        <span class="area-item-name">{{ area.administrative_division_level_3_name || area.name }}</span>
                                        <span v-if="area.postal_code" class="area-postal-badge">Kode Pos {{ area.postal_code }}</span>
                                    </div>
                                    <small>{{ area.administrative_division_level_2_name }}, {{ area.administrative_division_level_1_name }}</small>
                                </li>
                            </template>
                            <li v-else-if="originHasSearched" class="area-autocomplete-empty">
                                <div class="area-empty-icon">
                                    <AlertCircle style="width: 1rem; height: 1rem;" />
                                </div>
                                <div class="area-empty-text">
                                    <span class="area-empty-title">Wilayah tidak ditemukan</span>
                                    <span class="area-empty-desc">
                                        Pastikan ejaan benar, ketik nama kecamatan dengan lengkap, atau gunakan <strong>5 digit kode pos</strong> (misal: <em>60188</em>).
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="field field-destination" :class="{ 'is-open': showDestDropdown }">
                        <label class="field-label" for="destination-input">Kota / Kecamatan Tujuan</label>
                        <span class="input-wrap">
                            <MapPin class="icon" />
                            <input
                                id="destination-input"
                                v-model="destinationInput"
                                required
                                placeholder="Ketik nama kecamatan atau 5 digit kode pos"
                                autocomplete="off"
                                @input="onDestInput"
                                @focus="onDestFocus"
                            />
                            <Loader2 v-if="isDestSearching" class="icon-spin animate-spin shrink-0 text-[#853953]" style="width: 1rem; height: 1rem; margin-right: 0.5rem;" />
                        </span>
                        <ul v-if="showDestDropdown" class="area-autocomplete-list">
                            <li v-if="isDestSearching" class="area-autocomplete-loading">
                                <Loader2 class="icon-spin animate-spin shrink-0 text-[#853953]" style="width: 1rem; height: 1rem;" />
                                <span>Mencari wilayah...</span>
                            </li>
                            <template v-else-if="destSuggestions.length > 0">
                                <li
                                    v-for="area in destSuggestions"
                                    :key="area.id"
                                    class="area-autocomplete-item"
                                    @pointerdown="onItemPointerDown"
                                    @pointerup="onItemPointerUp($event, () => selectDest(area))"
                                    @click="selectDest(area)"
                                >
                                    <div class="area-item-header">
                                        <span class="area-item-name">{{ area.administrative_division_level_3_name || area.name }}</span>
                                        <span v-if="area.postal_code" class="area-postal-badge">Kode Pos {{ area.postal_code }}</span>
                                    </div>
                                    <small>{{ area.administrative_division_level_2_name }}, {{ area.administrative_division_level_1_name }}</small>
                                </li>
                            </template>
                            <li v-else-if="destHasSearched" class="area-autocomplete-empty">
                                <div class="area-empty-icon">
                                    <AlertCircle style="width: 1rem; height: 1rem;" />
                                </div>
                                <div class="area-empty-text">
                                    <span class="area-empty-title">Wilayah tidak ditemukan</span>
                                    <span class="area-empty-desc">
                                        Pastikan ejaan benar, ketik nama kecamatan dengan lengkap, atau gunakan <strong>5 digit kode pos</strong> (misal: <em>60188</em>).
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="field field-weight">
                        <div style="display: flex; justify-content: space-between; align-items: baseline">
                            <label class="field-label" for="weight-input">Berat Paket (kg)</label>
                            <span class="weight-limit-badge">Maks. 10 kg</span>
                        </div>
                        <span class="input-wrap">
                            <Box class="icon" />
                            <input
                                id="weight-input"
                                v-model="weightInput"
                                required
                                min="0.1"
                                max="10"
                                step="0.1"
                                type="number"
                                placeholder="1"
                            />
                        </span>
                    </div>
                </template>

                <template v-else>
                    <label class="field">
                        Pilihan Ekspedisi
                        <span class="input-wrap">
                            <Truck class="icon" />
                            <select v-model="courierInput" style="width: 100%; border: 0; outline: 0; background: transparent; padding: 0.75rem 0; font-weight: 500; color: var(--charcoal)">
                                <option value="sicepat">SiCepat Ekspres</option>
                                <option value="jne">JNE Express</option>
                                <option value="jnt">J&amp;T Express</option>
                                <option value="anteraja">AnterAja</option>
                                <option value="ninja">Ninja Xpress</option>
                                <option value="lion">Lion Parcel</option>
                                <option value="pos">Pos Indonesia</option>
                                <option value="tiki">TIKI</option>
                            </select>
                        </span>
                    </label>
                    <label class="field">
                        Nomor Resi Pengiriman
                        <span class="input-wrap"><Package class="icon" /><input v-model="resiInput" required placeholder="Contoh: 004281920194 atau JP0123456789" /></span>
                    </label>
                </template>

                <button type="submit" class="btn-pill btn-berry" :disabled="isSearchingRates || isTrackingResi">
                    <Loader2 v-if="isSearchingRates || isTrackingResi" class="icon btn-spin" style="width: 1rem; height: 1rem" />
                    <Search v-else class="icon" style="width: 1rem; height: 1rem" />
                    <span v-if="tab === 'ongkir'">{{ isSearchingRates ? 'Memeriksa Tarif Kurir...' : 'Lihat Pilihan Ongkos Kirim' }}</span>
                    <span v-else>{{ isTrackingResi ? 'Melacak Status Paket...' : 'Lacak Lokasi Paket' }}</span>
                </button>

                <div v-if="showDemo && tab === 'ongkir'" class="results">
                    <div v-if="isSearchingRates" style="padding: 1.5rem 0; text-align: center; color: var(--berry)">
                        <Loader2 class="icon btn-spin" style="width: 1.8rem; height: 1.8rem; margin: 0 auto 0.5rem" />
                        <p style="font-size: 0.9rem; font-weight: 500">Menghubungkan ke ekspedisi via Biteship...</p>
                    </div>
                    <div v-else-if="rateErrorMessage" style="padding: 0.85rem; border-radius: 0.75rem; background: rgba(239, 68, 68, 0.1); color: #dc2626; font-size: 0.88rem; display: flex; align-items: center; gap: 0.5rem">
                        <AlertCircle class="icon" style="width: 1.25rem; height: 1.25rem; flex-shrink: 0" />
                        <span>{{ rateErrorMessage }}</span>
                    </div>
                    <template v-else-if="liveRates.length > 0">
                        <div class="results-header">
                            <div class="results-heading-row">
                                <span class="results-title">Pilihan Layanan Ekspedisi</span>
                                <span class="results-badge">{{ liveRates.length }} Kurir Tersedia</span>
                            </div>
                            <div class="results-route-tag" :title="`${originInput} → ${destinationInput} (${weightInput} kg)`">
                                <MapPin class="icon-inline" />
                                <div class="results-route-content">
                                    <span class="results-route-text">{{ originInput }} → {{ destinationInput }}</span>
                                    <span class="results-route-weight">• {{ weightInput }} kg</span>
                                </div>
                            </div>
                        </div>
                        <div class="results-list">
                            <div v-for="rate in liveRates" :key="`${rate.name}-${rate.price}`" class="result-row" :class="{ best: rate.best }">
                                <div class="result-info">
                                    <div class="result-title-row">
                                        <span class="courier-name">{{ rate.name }}</span>
                                        <span v-if="rate.tag" class="rate-tag" :class="{ highlight: rate.best }">{{ rate.tag }}</span>
                                    </div>
                                    <small>
                                        <Clock class="icon-inline" />
                                        <span>Estimasi tiba: {{ rate.eta }} • Pickup resmi kurir</span>
                                    </small>
                                </div>
                                <div class="result-price">
                                    <b>{{ rate.price }}</b>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div v-if="showDemo && tab === 'resi'" class="results">
                    <div v-if="isTrackingResi" style="padding: 1.5rem 0; text-align: center; color: var(--berry)">
                        <Loader2 class="icon btn-spin" style="width: 1.8rem; height: 1.8rem; margin: 0 auto 0.5rem" />
                        <p style="font-size: 0.9rem; font-weight: 500">Mengecek nomor resi ke server ekspedisi...</p>
                    </div>
                    <div v-else-if="trackErrorMessage" style="padding: 0.85rem; border-radius: 0.75rem; background: rgba(239, 68, 68, 0.1); color: #dc2626; font-size: 0.88rem; display: flex; align-items: center; gap: 0.5rem">
                        <AlertCircle class="icon" style="width: 1.25rem; height: 1.25rem; flex-shrink: 0" />
                        <span>{{ trackErrorMessage }}</span>
                    </div>
                    <template v-else-if="trackResult">
                        <div class="results-header">
                            <div class="results-heading-row">
                                <span class="results-title">Hasil Pelacakan Resi</span>
                                <span class="results-badge">{{ trackResult.status || 'Tercatat' }}</span>
                            </div>
                            <div class="results-route-tag">
                                <Truck class="icon-inline" />
                                <div class="results-route-content">
                                    <span class="results-route-text">{{ trackResult.courier?.name || courierInput.toUpperCase() }}</span>
                                    <span class="results-route-weight">• No. Resi: {{ resiInput }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="results-list">
                            <div class="result-row best">
                                <div class="result-info">
                                    <span class="courier-name">{{ trackResult.courier?.name || courierInput.toUpperCase() }} ({{ resiInput }})</span>
                                    <small>
                                        <Truck class="icon-inline" />
                                        <span>Status: {{ trackResult.status || 'Tercatat' }} • {{ trackResult.history?.[0]?.note || 'Paket dalam pemrosesan kurir' }}</span>
                                    </small>
                                </div>
                                <div class="result-price">
                                    <b style="color: #16a34a">{{ trackResult.status || 'Aktif' }}</b>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </form>
        </div>
    </div>
</template>

