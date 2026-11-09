<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowRight,
    Building2,
    Calendar,
    Check,
    CheckCircle2,
    ChevronRight,
    Clock,
    Copy,
    CreditCard,
    Eye,
    ExternalLink,
    History,
    Loader2,
    MapPin,
    Package,
    PackageCheck,
    PackagePlus,
    ReceiptText,
    Search,
    ShieldCheck,
    Store,
    TrendingDown,
    TrendingUp,
    Truck,
    Users,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import AppTopBar from '@/components/AppTopBar.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    excerptText,
    formatDateTime,
    formatShortDestination,
    formatWeight,
    getShipmentStatus,
} from '@/lib/status';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

interface ShipmentItem {
    id: number;
    order_number: string;
    recipient_name: string;
    recipient_phone?: string;
    destination_area_name?: string | null;
    destination_address?: string;
    weight?: number;
    courier_code: string;
    courier_service_code: string;
    courier_service_name: string;
    total_amount: number;
    status: string;
    waybill_number?: string | null;
    created_at: string;
    store?: {
        id: number;
        name: string;
        phone: string;
    } | null;
    user?: {
        id: number;
        name: string;
        email: string;
    } | null;
}

const props = withDefaults(
    defineProps<{
        isSuperAdmin?: boolean;
        metrics?: {
            total?: number;
            unpaid: number;
            waiting_pickup: number;
            in_transit: number;
            delivered: number;
            total_volume?: number;
        };
        sevenDayTrend?: Array<{
            date: string;
            day: string;
            count: number;
        }>;
        weeklyGrowth?: number;
        topCourier?: {
            code: string;
            name: string;
            count: number;
            percentage: number;
        } | null;
        topCouriers?: Array<{
            code: string;
            name: string;
            count: number;
            percentage: number;
        }>;
        topDestinations?: Array<{
            city: string;
            full: string;
            count: number;
        }>;
        courierPerformance?: Array<{
            code: string;
            name: string;
            count: number;
            delivered_count: number;
            active_count: number;
            avg_fee: number;
            total_fee: number;
            delivery_rate: number;
            share_percentage: number;
        }>;
        serviceBreakdown?: Array<{
            key: string;
            label: string;
            desc: string;
            count: number;
            total_spend: number;
            avg_spend: number;
            percentage: number;
        }>;
        categoryBreakdown?: Array<{
            category: string;
            count: number;
            total_spend: number;
            percentage: number;
        }>;
        storePerformance?: Array<{
            id: number;
            name: string;
            phone: string;
            address: string;
            area_name: string | null;
            postal_code: string | null;
            is_default: boolean;
            shipments_count: number;
            delivered_count: number;
            in_transit_count: number;
            total_spend: number;
            avg_weight: number;
            share_percentage: number;
        }>;
        liveTrackingFeed?: Array<{
            id: number;
            order_number: string;
            waybill_number: string | null;
            status: string;
            recipient_name: string;
            destination_area_name: string | null;
            courier_code: string;
            courier_service_name: string;
            note: string;
            time: string;
        }>;
        recentShipments?: ShipmentItem[];
        defaultStore?: {
            id: number;
            name: string;
            area_name: string | null;
            postal_code?: string | null;
            biteship_area_id: string;
        } | null;
        storesCount?: number;
        platformStats?: {
            total_users: number;
            total_umkm?: number;
            active_umkm: number;
            total_stores?: number;
            total_shipments?: number;
            paid_shipments?: number;
            delivered_rate?: number;
            platform_revenue?: number;
            total_volume: number;
            total_audit_logs?: number;
            audit_created?: number;
            audit_updated?: number;
            audit_deleted?: number;
            audit_shipment_count?: number;
            audit_store_count?: number;
            audit_user_count?: number;
            non_cod_count?: number;
            non_cod_volume?: number;
            cod_count?: number;
            cod_volume?: number;
        } | null;
        recentAuditLogs?: Array<{
            id: number;
            user: { name: string; role: string } | null;
            event: 'created' | 'updated' | 'deleted';
            auditable_type: string;
            auditable_id: number;
            auditable_label: string;
            formatted_changes?: string[];
            old_values: Record<string, any> | null;
            new_values: Record<string, any> | null;
            ip_address: string | null;
            created_at: string;
        }>;
        umkmList?: Array<{
            id: number;
            name: string;
            email: string;
            is_active: boolean;
            stores_count: number;
            shipments_count: number;
            total_spent: number;
            created_at: string;
        }>;
    }>(),
    {
        isSuperAdmin: false,
        metrics: () => ({ total: 0, unpaid: 0, waiting_pickup: 0, in_transit: 0, delivered: 0, total_volume: 0 }),
        sevenDayTrend: () => [],
        weeklyGrowth: 0,
        topCourier: null,
        topCouriers: () => [],
        topDestinations: () => [],
        courierPerformance: () => [],
        serviceBreakdown: () => [],
        categoryBreakdown: () => [],
        storePerformance: () => [],
        liveTrackingFeed: () => [],
        recentShipments: () => [],
        defaultStore: null,
        storesCount: 0,
        platformStats: null,
        recentAuditLogs: () => [],
        umkmList: () => [],
    },
);

const page = usePage();
const user = computed(() => page.props.auth?.user);

const formatRupiah = (val: number | undefined) => {
    return Number(val || 0).toLocaleString('id-ID');
};

const getCleanCity = (rawArea: string | null | undefined, fallback: string | undefined = '') => {
    if (!rawArea) return fallback || '-';
    const parts = rawArea.split(',').map((p) => p.trim()).filter(Boolean);
    if (parts.length === 0) return fallback || '-';
    const last = parts[parts.length - 1];
    const provinces = [
        'jawa barat', 'jawa timur', 'jawa tengah', 'dki jakarta', 'banten',
        'd.i. yogyakarta', 'sumatera utara', 'sumatera selatan', 'bali', 'sulawesi selatan',
    ];
    if (parts.length >= 2 && provinces.includes(last.toLowerCase())) {
        return parts[parts.length - 2];
    }
    return last;
};

const formatRelativeTime = (dateStr: string) => {
    if (!dateStr) return '';
    try {
        const date = new Date(dateStr);
        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);
    } catch {
        return '';
    }
};

const copiedOrderId = ref<number | null>(null);
const copiedWaybillId = ref<number | null>(null);

const copyOrderNumber = (id: number, orderNumber: string) => {
    if (navigator?.clipboard) {
        navigator.clipboard.writeText(orderNumber);
        copiedOrderId.value = id;
        setTimeout(() => {
            if (copiedOrderId.value === id) copiedOrderId.value = null;
        }, 1800);
    }
};

const copyWaybill = (id: number, waybill: string) => {
    if (navigator?.clipboard) {
        navigator.clipboard.writeText(waybill);
        copiedWaybillId.value = id;
        setTimeout(() => {
            if (copiedWaybillId.value === id) copiedWaybillId.value = null;
        }, 1800);
    }
};

const currentDateFormatted = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date());
});

const currentDateShort = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date());
});

const urgentActionCount = computed(() => {
    return (props.metrics?.unpaid || 0) + (props.metrics?.waiting_pickup || 0);
});

const urgentActionDescription = computed(() => {
    const unpaid = props.metrics?.unpaid || 0;
    const pickup = props.metrics?.waiting_pickup || 0;
    if (unpaid > 0 && pickup > 0) {
        return `${unpaid} paket belum dibayar dan ${pickup} paket siap dijemput oleh kurir.`;
    }
    if (unpaid > 0) {
        return `${unpaid} paket menunggu pembayaran ongkir agar dapat segera diproses kurir.`;
    }
    if (pickup > 0) {
        return `${pickup} paket siap diserahkan kepada kurir penjemput.`;
    }
    return '';
});

const urgentActionHref = computed(() => {
    const unpaid = props.metrics?.unpaid || 0;
    const pickup = props.metrics?.waiting_pickup || 0;
    if (unpaid > 0 && pickup === 0) {
        return '/shipments?status=pending_payment';
    }
    if (pickup > 0 && unpaid === 0) {
        return '/shipments?status=waiting_pickup';
    }
    return '/shipments';
});

const maxTrendCount = computed(() => {
    if (!props.sevenDayTrend || props.sevenDayTrend.length === 0) return 1;
    const max = Math.max(...props.sevenDayTrend.map((t) => t.count));
    return max > 0 ? max : 1;
});

const total7DayVolume = computed(() => {
    if (!props.sevenDayTrend) return 0;
    return props.sevenDayTrend.reduce((acc, curr) => acc + curr.count, 0);
});

const peakDayTrend = computed(() => {
    if (!props.sevenDayTrend || props.sevenDayTrend.length === 0) {
        return { day: '-', count: 0 };
    }
    return props.sevenDayTrend.reduce((best, curr) => (curr.count > best.count ? curr : best), props.sevenDayTrend[0]);
});

const overallDeliveryRate = computed(() => {
    const delivered = props.metrics?.delivered || 0;
    const totalActiveOrDone = (props.metrics?.waiting_pickup || 0) + (props.metrics?.in_transit || 0) + delivered;
    if (totalActiveOrDone === 0) return 0;
    return Math.round((delivered / totalActiveOrDone) * 100);
});

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
const rateResults = ref<Array<any>>([]);
const rateError = ref('');
const showRateResultsModal = ref(false);

let originSearchTimer: any = null;
const onOriginInputChange = () => {
    originAreaId.value = '';
    rateError.value = '';
    clearTimeout(originSearchTimer);
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

let destSearchTimer: any = null;
const onDestInputChange = () => {
    destinationAreaId.value = '';
    rateError.value = '';
    clearTimeout(destSearchTimer);
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
            rateResults.value = data.pricing.slice(0, 5);
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

const courierLogos: Record<string, string> = {
    jne: '/images/couriers/jne.svg',
    jnt: '/images/couriers/jnt.svg',
    sicepat: '/images/couriers/sicepat.svg',
    anteraja: '/images/couriers/anteraja.svg',
    ninja: '/images/couriers/ninjaxpress.svg',
    ninjaxpress: '/images/couriers/ninjaxpress.svg',
    lion: '/images/couriers/lionparcel.svg',
    lionparcel: '/images/couriers/lionparcel.svg',
    pos: '/images/couriers/posindonesia.svg',
    posindonesia: '/images/couriers/posindonesia.svg',
    idexpress: '/images/couriers/idexpress.svg',
    tiki: '/images/couriers/tiki.svg',
    wahana: '/images/couriers/wahana.svg',
    sapexpress: '/images/couriers/sapexpress.svg',
};

const getCourierLogo = (code: string | undefined): string | null => {
    if (!code) return null;
    const clean = code.toLowerCase().replace(/[^a-z0-9]/g, '');
    return courierLogos[clean] || courierLogos[code.toLowerCase()] || null;
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
    <Head title="Dashboard - SatuKirim" />

    <AppTopBar show-mobile-brand>
        <template #title>
            <h1 class="hidden md:block text-base sm:text-lg lg:text-xl font-bold tracking-tight text-white truncate">
                Halo, {{ user?.name || 'Partner UMKM' }}
            </h1>
        </template>
        <template #actions>
 <div class="flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg bg-white/15 hover:bg-white/20 border border-white/20 text-xs sm:text-sm font-medium text-white shadow-2xs backdrop-blur-xs transition-colors shrink-0">
                <Calendar class="size-3.5 sm:size-4 text-white/90" />
                <span class="hidden sm:inline">{{ currentDateFormatted }}</span>
                <span class="sm:hidden">{{ currentDateShort }}</span>
            </div>
        </template>
    </AppTopBar>

    <div class="flex h-full flex-1 flex-col gap-3 sm:gap-4 p-3 sm:p-4 lg:p-5 w-full">

        <div
            v-if="!isSuperAdmin && urgentActionCount > 0"
            class="rounded-xl bg-white border p-3 sm:p-3.5 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3"
        >
            <div class="flex items-center gap-3.5 min-w-0">
                <div class="size-10 sm:size-11 rounded-lg bg-rose-50 text-[#853953] flex items-center justify-center shrink-0 shadow-2xs">
                    <AlertCircle class="size-5 sm:size-5.5" />
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-bold text-stone-900 flex items-center gap-2">
                        <span>{{ urgentActionCount }} Paket Memerlukan Tindakan Segera</span>
                    </h3>
                    <p class="text-xs sm:text-sm text-stone-600 mt-0.5">
                        {{ urgentActionDescription }}
                    </p>
                </div>
            </div>

            <div class="shrink-0 self-end sm:self-center w-full sm:w-auto flex justify-end">
                <Link
                    :href="urgentActionHref"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold text-white bg-[#853953] hover:bg-[#6e2e44] active:scale-[0.99] shadow-xs hover:shadow transition-all"
                >
                    <span>Cek Pengiriman</span>
                    <ArrowRight class="size-4" />
                </Link>
            </div>
        </div>

        <div v-if="isSuperAdmin" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-3.5">
            <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-stone-600">
                    <span class="text-sm font-semibold">Pendapatan Platform</span>
                    <div class="size-8 rounded-lg bg-rose-50 flex items-center justify-center text-[#853953]">
                        <CreditCard class="size-4.5" />
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-2xl sm:text-3xl font-extrabold text-[#2C2C2C] tabular-nums">
                        Rp {{ formatRupiah(platformStats?.platform_revenue || 0) }}
                    </div>
                    <p class="text-xs text-stone-500 font-medium mt-0.5">Biaya layanan Rp 1.000 / transaksi</p>
                </div>
            </div>

            <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-stone-600">
                    <span class="text-sm font-semibold">Perputaran Transaksi</span>
                    <div class="size-8 rounded-lg bg-rose-50 flex items-center justify-center text-[#853953]">
                        <ReceiptText class="size-4.5" />
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-2xl sm:text-3xl font-extrabold text-[#2C2C2C] tabular-nums">
                        Rp {{ formatRupiah(platformStats?.total_volume || metrics.total_volume || 0) }}
                    </div>
                    <p class="text-xs text-stone-500 font-medium mt-0.5">Akumulasi logistik terbayar</p>
                </div>
            </div>

            <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-stone-600">
                    <span class="text-sm font-semibold">Mitra UMKM</span>
                    <div class="size-8 rounded-lg bg-rose-50 flex items-center justify-center text-[#853953]">
                        <Users class="size-4.5" />
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-2xl sm:text-3xl font-extrabold text-[#2C2C2C] tabular-nums">
                        {{ platformStats?.total_umkm || 0 }}
                    </div>
                    <p class="text-xs text-stone-500 font-medium mt-0.5">
                        {{ platformStats?.active_umkm || 0 }} akun aktif - {{ platformStats?.total_stores || storesCount }} toko
                    </p>
                </div>
            </div>

            <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-stone-600">
                    <span class="text-sm font-semibold">Total Pengiriman</span>
                    <div class="size-8 rounded-lg bg-rose-50 flex items-center justify-center text-[#853953]">
                        <PackageCheck class="size-4.5" />
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-2xl sm:text-3xl font-extrabold text-[#2C2C2C] tabular-nums">
                        {{ platformStats?.total_shipments || metrics.total || 0 }}
                    </div>
                    <p class="text-xs text-stone-500 font-medium mt-0.5">
                        {{ platformStats?.delivered_rate || 0 }}% tingkat keberhasilan antar
                    </p>
                </div>
            </div>
        </div>

        <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-3.5">
            <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-stone-600">
                    <span class="text-sm font-semibold">Total Paket</span>
                    <div class="size-8 rounded-lg bg-rose-50 flex items-center justify-center text-[#853953]">
                        <Package class="size-4.5" />
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-2xl sm:text-3xl font-extrabold text-[#2C2C2C]">
                        {{ metrics.total ?? ((metrics.unpaid || 0) + (metrics.waiting_pickup || 0) + (metrics.in_transit || 0) + (metrics.delivered || 0)) }}
                    </div>
                    <p class="text-xs text-stone-500 font-medium mt-0.5">Semua pengiriman toko</p>
                </div>
            </div>

            <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-stone-600">
                    <span class="text-sm font-semibold">Sedang Dikirim</span>
                    <div class="size-8 rounded-lg bg-rose-50 flex items-center justify-center text-[#853953]">
                        <Truck class="size-4.5" />
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-2xl sm:text-3xl font-extrabold text-[#2C2C2C]">
                        {{ metrics.in_transit }}
                    </div>
                    <p class="text-xs text-stone-500 font-medium mt-0.5">Dalam perjalanan kurir</p>
                </div>
            </div>

            <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-stone-600">
                    <span class="text-sm font-semibold">Paket Sampai</span>
                    <div class="size-8 rounded-lg bg-rose-50 flex items-center justify-center text-[#853953]">
                        <CheckCircle2 class="size-4.5" />
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-2xl sm:text-3xl font-extrabold text-[#2C2C2C]">
                        {{ metrics.delivered }}
                    </div>
                    <p class="text-xs text-stone-500 font-medium mt-0.5">Telah diterima pembeli</p>
                </div>
            </div>

            <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-stone-600">
                    <span class="text-sm font-semibold">Total Ongkir</span>
                    <div class="size-8 rounded-lg bg-rose-50 flex items-center justify-center text-[#853953]">
                        <ReceiptText class="size-4.5" />
                    </div>
                </div>
                <div class="mt-2.5">
                    <div class="text-2xl sm:text-3xl font-extrabold text-[#2C2C2C] truncate tabular-nums">
                        Rp {{ formatRupiah(metrics.total_volume) }}
                    </div>
                    <p class="text-xs text-stone-500 font-medium mt-0.5">Logistik terbayar</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-3.5">
            <div v-if="isSuperAdmin" class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-1">
                        <h2 class="text-base font-bold text-[#2C2C2C]">
                            Ringkasan Pengguna & Transaksi
                        </h2>
                        <Link
                            href="/admin/users"
                            class="text-sm font-semibold text-primary hover:underline flex items-center gap-1 shrink-0"
                        >
                            <span>Pengguna ({{ platformStats?.total_users || 0 }})</span>
                            <ChevronRight class="size-4" />
                        </Link>
                    </div>

                    <div class="mt-3 space-y-2">
                        <div class="p-2.5 rounded-lg bg-stone-50/80">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="text-xs sm:text-sm font-bold text-stone-800 truncate">
                                        Mitra UMKM Aktif
                                    </span>
                                    <span class="text-xs text-stone-400 tabular-nums shrink-0">
                                        ({{ platformStats?.active_umkm || 0 }} dari {{ platformStats?.total_umkm || 0 }})
                                    </span>
                                </div>
                                <div class="text-xs sm:text-sm font-bold text-primary tabular-nums shrink-0">
                                    {{ platformStats?.total_umkm ? Math.round(((platformStats.active_umkm || 0) / platformStats.total_umkm) * 100) : 100 }}%
                                </div>
                            </div>
                            <div class="mt-1.5 w-full h-1.5 bg-stone-200/70 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-[#853953] rounded-full transition-all duration-500"
                                    :style="{ width: `${platformStats?.total_umkm ? Math.max(4, Math.round(((platformStats.active_umkm || 0) / platformStats.total_umkm) * 100)) : 100}%` }"
                                />
                            </div>
                        </div>

                        <div class="p-2.5 rounded-lg bg-stone-50/80">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="text-xs sm:text-sm font-bold text-stone-800 truncate">
                                        Transaksi Terbayar
                                    </span>
                                    <span class="text-xs text-stone-400 tabular-nums shrink-0">
                                        ({{ platformStats?.paid_shipments || 0 }} dari {{ platformStats?.total_shipments || 0 }})
                                    </span>
                                </div>
                                <div class="text-xs sm:text-sm font-bold text-primary tabular-nums shrink-0">
                                    {{ platformStats?.total_shipments ? Math.round(((platformStats.paid_shipments || 0) / platformStats.total_shipments) * 100) : 0 }}%
                                </div>
                            </div>
                            <div class="mt-1.5 w-full h-1.5 bg-stone-200/70 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-[#853953] rounded-full transition-all duration-500"
                                    :style="{ width: `${platformStats?.total_shipments ? Math.max(4, Math.round(((platformStats.paid_shipments || 0) / platformStats.total_shipments) * 100)) : 4}%` }"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 pt-2.5 border-t border-stone-100">
                        <div class="text-xs sm:text-sm font-semibold text-stone-500 mb-1.5">
                            Status Transaksi Pengiriman
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs sm:text-sm py-0.5">
                                <span class="font-semibold text-stone-800">Menunggu Pembayaran</span>
                                <span class="text-xs sm:text-sm text-stone-500 font-medium tabular-nums">
                                    <strong class="font-bold text-stone-800">{{ metrics.unpaid || 0 }}</strong> paket
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs sm:text-sm py-0.5">
                                <span class="font-semibold text-stone-800">Sedang Diproses & Dikirim</span>
                                <span class="text-xs sm:text-sm text-stone-500 font-medium tabular-nums">
                                    <strong class="font-bold text-stone-800">{{ (metrics.waiting_pickup || 0) + (metrics.in_transit || 0) }}</strong> paket
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs sm:text-sm py-0.5">
                                <span class="font-semibold text-stone-800">Selesai Diterima</span>
                                <span class="text-xs sm:text-sm text-stone-500 font-medium tabular-nums">
                                    <strong class="font-bold text-stone-800">{{ metrics.delivered || 0 }}</strong> paket
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 pt-2.5 border-t border-stone-100">
                    <div class="text-xs sm:text-sm font-semibold text-stone-500 mb-1.5">
                        Cakupan Toko & Pengiriman
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="p-2.5 rounded-lg bg-stone-50/80">
                            <div class="text-xs sm:text-sm font-bold text-stone-800 truncate">
                                Toko Terdaftar
                            </div>
                            <div class="text-xs text-stone-500 tabular-nums mt-0.5 flex items-center justify-between gap-2">
                                <span>{{ platformStats?.total_umkm || 0 }} mitra</span>
                                <span class="font-bold text-stone-800">{{ platformStats?.total_stores || storesCount }} toko</span>
                            </div>
                        </div>
                        <div class="p-2.5 rounded-lg bg-stone-50/80">
                            <div class="text-xs sm:text-sm font-bold text-stone-800 truncate">
                                Rasio Terkirim
                            </div>
                            <div class="text-xs text-stone-500 tabular-nums mt-0.5 flex items-center justify-between gap-2">
                                <span>{{ metrics.delivered || 0 }} selesai</span>
                                <span class="font-bold text-primary">{{ platformStats?.delivered_rate || 0 }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-1">
                        <h2 class="text-base font-bold text-[#2C2C2C] flex items-center gap-2">
                            <span>Cek Ongkir Cepat</span>
                        </h2>
                    </div>

                    <div class="mt-4 space-y-3.5">
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
                                    placeholder="Ketik kota asal (cth: Surabaya)..."
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
                                    placeholder="Ketik kota tujuan (cth: Jakarta)..."
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

                <div class="mt-5">
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
            </div>

            <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-1">
                        <h2 class="text-base font-bold text-[#2C2C2C] flex items-center gap-2">
                            <span>{{ isSuperAdmin ? 'Aktivitas Sistem 7 Hari' : 'Volume 7 Hari Terakhir' }}</span>
                        </h2>
                        <span class="text-sm font-bold text-stone-700">
                            {{ total7DayVolume }} {{ isSuperAdmin ? 'Log' : 'Paket' }}
                        </span>
                    </div>

                    <div class="mt-4 flex items-end justify-between gap-2 h-44 sm:h-48 px-1">
                        <div
                            v-for="item in sevenDayTrend"
                            :key="item.date"
                            class="flex flex-col items-center flex-1 h-full justify-end group cursor-default"
                        >
                            <span class="text-xs sm:text-sm font-bold text-stone-600 mb-1 opacity-80 group-hover:opacity-100 transition-opacity">
                                {{ item.count }}
                            </span>

                            <div class="w-full max-w-[28px] bg-stone-100 rounded-t-md relative flex items-end overflow-hidden h-32 sm:h-36">
                                <div
                                    class="w-full rounded-t-md transition-all duration-500 ease-out bg-[#853953] group-hover:bg-[#612D53]"
                                    :style="{
                                        height: item.count > 0 ? `${Math.max(14, Math.round((item.count / maxTrendCount) * 100))}%` : '4px',
                                        opacity: item.count > 0 ? '1' : '0.2',
                                    }"
                                />
                            </div>

                            <span class="text-xs sm:text-sm font-semibold text-stone-600 mt-2">
                                {{ item.day }}
                            </span>
                        </div>
                    </div>
                </div>

                <div v-if="isSuperAdmin" class="mt-3.5 grid grid-cols-2 gap-2">
                    <div class="p-2.5 rounded-lg bg-stone-50/80">
                        <span class="text-xs font-semibold text-stone-500 block">
                            Tren vs Pekan Lalu
                        </span>
                        <div class="mt-0.5 text-sm sm:text-base font-bold text-stone-900 flex items-center gap-1.5 truncate">
                            <span class="flex items-center gap-0.5 font-extrabold text-primary">
                                <TrendingUp v-if="(weeklyGrowth ?? 0) >= 0" class="size-3.5 sm:size-4 inline shrink-0" />
                                <TrendingDown v-else class="size-3.5 sm:size-4 inline shrink-0" />
                                <span>{{ (weeklyGrowth ?? 0) > 0 ? `+${weeklyGrowth}%` : `${weeklyGrowth ?? 0}%` }}</span>
                            </span>
                            <span class="text-xs font-medium text-stone-500">
                                {{ (weeklyGrowth ?? 0) >= 0 ? 'Meningkat' : 'Menurun' }}
                            </span>
                        </div>
                    </div>

                    <div class="p-2.5 rounded-lg bg-stone-50/80">
                        <span class="text-xs font-semibold text-stone-500 block">
                            Rata-rata Harian
                        </span>
                        <div class="mt-0.5 text-sm sm:text-base font-bold text-stone-900 truncate">
                            ~{{ Math.round(total7DayVolume / 7) }} <span class="text-xs font-medium text-stone-500">log / hari</span>
                        </div>
                    </div>

                    <div class="p-2.5 rounded-lg bg-stone-50/80">
                        <span class="text-xs font-semibold text-stone-500 block">
                            Puncak Harian
                        </span>
                        <div class="mt-0.5 text-sm sm:text-base font-bold text-stone-900 truncate">
                            {{ peakDayTrend.count }} <span class="text-xs font-medium text-stone-500">log ({{ peakDayTrend.day }})</span>
                        </div>
                    </div>

                    <div class="p-2.5 rounded-lg bg-stone-50/80">
                        <span class="text-xs font-semibold text-stone-500 block">
                            Total Rekaman Log
                        </span>
                        <div class="mt-0.5 text-sm sm:text-base font-bold text-primary truncate">
                            {{ platformStats?.total_audit_logs || 0 }} <span class="text-xs font-medium text-stone-500">log sistem</span>
                        </div>
                    </div>
                </div>

                <div v-else class="mt-3.5 grid grid-cols-2 gap-2">
                    <div class="p-2.5 rounded-lg bg-stone-50/80">
                        <span class="text-xs font-semibold text-stone-500 block">
                            Tren vs Pekan Lalu
                        </span>
                        <div class="mt-0.5 text-sm sm:text-base font-bold text-stone-900 flex items-center gap-1.5 truncate">
                            <span class="flex items-center gap-0.5 font-extrabold text-primary">
                                <TrendingUp v-if="(weeklyGrowth ?? 0) >= 0" class="size-3.5 sm:size-4 inline shrink-0" />
                                <TrendingDown v-else class="size-3.5 sm:size-4 inline shrink-0" />
                                <span>{{ (weeklyGrowth ?? 0) > 0 ? `+${weeklyGrowth}%` : `${weeklyGrowth ?? 0}%` }}</span>
                            </span>
                            <span class="text-xs font-medium text-stone-500">
                                {{ (weeklyGrowth ?? 0) >= 0 ? 'Meningkat' : 'Menurun' }}
                            </span>
                        </div>
                    </div>

                    <div class="p-2.5 rounded-lg bg-stone-50/80">
                        <span class="text-xs font-semibold text-stone-500 block">
                            Rata-rata Harian
                        </span>
                        <div class="mt-0.5 text-sm sm:text-base font-bold text-stone-900 truncate">
                            ~{{ Math.round(total7DayVolume / 7) }} <span class="text-xs font-medium text-stone-500">paket / hari</span>
                        </div>
                    </div>

                    <div class="p-2.5 rounded-lg bg-stone-50/80">
                        <span class="text-xs font-semibold text-stone-500 block">
                            Puncak Harian
                        </span>
                        <div class="mt-0.5 text-sm sm:text-base font-bold text-stone-900 truncate">
                            {{ peakDayTrend.count }} <span class="text-xs font-medium text-stone-500">paket ({{ peakDayTrend.day }})</span>
                        </div>
                    </div>

                    <div class="p-2.5 rounded-lg bg-stone-50/80">
                        <span class="text-xs font-semibold text-stone-500 block">
                            Rasio Terkirim
                        </span>
                        <div class="mt-0.5 text-sm sm:text-base font-bold text-primary truncate">
                            {{ overallDeliveryRate }}% <span class="text-xs font-medium text-stone-500">({{ metrics.delivered || 0 }} selesai)</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="isSuperAdmin" class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-1">
                        <h2 class="text-base font-bold text-[#2C2C2C]">
                            Metode Bayar & Modul Audit
                        </h2>
                        <Link
                            href="/admin/audit-logs"
                            class="text-sm font-semibold text-primary hover:underline flex items-center gap-1 shrink-0"
                        >
                            <span>Audit Log</span>
                            <ChevronRight class="size-4" />
                        </Link>
                    </div>

                    <div class="mt-3 space-y-2">
                        <div class="p-2.5 rounded-lg bg-stone-50/80">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="text-xs sm:text-sm font-bold text-stone-800 truncate">
                                        Non-COD (Midtrans)
                                    </span>
                                    <span class="text-xs text-stone-400 tabular-nums shrink-0">
                                        ({{ platformStats?.non_cod_count || 0 }} paket)
                                    </span>
                                </div>
                                <div class="text-xs sm:text-sm flex items-center gap-1 tabular-nums shrink-0">
                                    <span class="font-bold text-primary">Rp {{ formatRupiah(platformStats?.non_cod_volume || 0) }}</span>
                                    <span class="text-xs font-bold text-stone-500">({{ platformStats?.paid_shipments ? Math.round(((platformStats?.non_cod_count || 0) / platformStats.paid_shipments) * 100) : 100 }}%)</span>
                                </div>
                            </div>
                            <div class="mt-1.5 w-full h-1.5 bg-stone-200/70 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-[#853953] rounded-full transition-all duration-500"
                                    :style="{ width: `${platformStats?.paid_shipments ? Math.max(4, Math.round(((platformStats?.non_cod_count || 0) / platformStats.paid_shipments) * 100)) : 100}%` }"
                                />
                            </div>
                        </div>

                        <div class="p-2.5 rounded-lg bg-stone-50/80">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="text-xs sm:text-sm font-bold text-stone-800 truncate">
                                        COD (Potong Saldo)
                                    </span>
                                    <span class="text-xs text-stone-400 tabular-nums shrink-0">
                                        ({{ platformStats?.cod_count || 0 }} paket)
                                    </span>
                                </div>
                                <div class="text-xs sm:text-sm flex items-center gap-1 tabular-nums shrink-0">
                                    <span class="font-bold text-primary">Rp {{ formatRupiah(platformStats?.cod_volume || 0) }}</span>
                                    <span class="text-xs font-bold text-stone-500">({{ platformStats?.paid_shipments ? Math.round(((platformStats?.cod_count || 0) / platformStats.paid_shipments) * 100) : 0 }}%)</span>
                                </div>
                            </div>
                            <div class="mt-1.5 w-full h-1.5 bg-stone-200/70 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-[#853953] rounded-full transition-all duration-500"
                                    :style="{ width: `${platformStats?.paid_shipments ? Math.max(4, Math.round(((platformStats?.cod_count || 0) / platformStats.paid_shipments) * 100)) : 4}%` }"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 pt-2.5 border-t border-stone-100">
                        <div class="text-xs sm:text-sm font-semibold text-stone-500 mb-1.5">
                            Distribusi Log per Modul
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs sm:text-sm py-0.5">
                                <span class="font-semibold text-stone-800">Pengiriman</span>
                                <span class="text-xs sm:text-sm text-stone-500 font-medium tabular-nums">
                                    <strong class="font-bold text-stone-800">{{ platformStats?.audit_shipment_count || 0 }}</strong> log
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs sm:text-sm py-0.5">
                                <span class="font-semibold text-stone-800">Toko</span>
                                <span class="text-xs sm:text-sm text-stone-500 font-medium tabular-nums">
                                    <strong class="font-bold text-stone-800">{{ platformStats?.audit_store_count || 0 }}</strong> log
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs sm:text-sm py-0.5">
                                <span class="font-semibold text-stone-800">Pengguna</span>
                                <span class="text-xs sm:text-sm text-stone-500 font-medium tabular-nums">
                                    <strong class="font-bold text-stone-800">{{ platformStats?.audit_user_count || 0 }}</strong> log
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 pt-2.5 border-t border-stone-100">
                    <div class="text-xs sm:text-sm font-semibold text-stone-500 mb-1.5">
                        Rekaman Aksi Audit
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="p-2.5 rounded-lg bg-stone-50/80">
                            <div class="text-xs sm:text-sm font-bold text-stone-800 truncate">
                                Data Dibuat
                            </div>
                            <div class="text-xs text-stone-500 tabular-nums mt-0.5 flex items-center justify-between gap-2">
                                <span>Created</span>
                                <span class="font-bold text-stone-800">{{ platformStats?.audit_created || 0 }} log</span>
                            </div>
                        </div>
                        <div class="p-2.5 rounded-lg bg-stone-50/80">
                            <div class="text-xs sm:text-sm font-bold text-stone-800 truncate">
                                Data Diperbarui
                            </div>
                            <div class="text-xs text-stone-500 tabular-nums mt-0.5 flex items-center justify-between gap-2">
                                <span>Updated</span>
                                <span class="font-bold text-stone-800">{{ platformStats?.audit_updated || 0 }} log</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-1">
                        <h2 class="text-base font-bold text-[#2C2C2C]">
                            Layanan, Rute & Toko Asal
                        </h2>
                        <Link
                            href="/stores"
                            class="text-sm font-semibold text-primary hover:underline flex items-center gap-1 shrink-0"
                        >
                            <span>Toko ({{ storesCount || storePerformance.length }})</span>
                            <ChevronRight class="size-4" />
                        </Link>
                    </div>

                    <div class="mt-3 space-y-2">
                        <div
                            v-for="tier in serviceBreakdown"
                            :key="tier.key"
                            class="p-2.5 rounded-lg bg-stone-50/80"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="text-xs sm:text-sm font-bold text-stone-800 truncate">
                                        {{ tier.label }}
                                    </span>
                                    <span class="text-xs text-stone-400 tabular-nums shrink-0">
                                        ({{ tier.count }} paket)
                                    </span>
                                </div>
                                <div class="text-xs sm:text-sm flex items-center gap-1 tabular-nums shrink-0">
                                    <span class="font-bold text-primary">Rp {{ formatRupiah(tier.total_spend) }}</span>
                                    <span class="text-xs font-bold text-stone-500">({{ tier.percentage }}%)</span>
                                </div>
                            </div>
                            <div class="mt-1.5 w-full h-1.5 bg-stone-200/70 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-[#853953] rounded-full transition-all duration-500"
                                    :style="{ width: `${Math.max(4, tier.percentage)}%` }"
                                />
                            </div>
                        </div>
                    </div>

                    <div v-if="topDestinations && topDestinations.length > 0" class="mt-3 pt-2.5 border-t border-stone-100">
                        <div class="text-xs sm:text-sm font-semibold text-stone-500 mb-1.5">
                            Kota Tujuan Teratas
                        </div>
                        <div class="space-y-1">
                            <div
                                v-for="(dest, idx) in topDestinations"
                                :key="idx"
                                class="flex items-center justify-between text-xs sm:text-sm py-0.5"
                            >
                                <span class="font-semibold text-stone-800 truncate max-w-[200px]">
                                    {{ dest.city }}
                                </span>
                                <span class="text-xs sm:text-sm text-stone-500 font-medium tabular-nums">
                                    <strong class="font-bold text-stone-800">{{ dest.count }}</strong> paket
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="storePerformance && storePerformance.length > 0" class="mt-3 pt-2.5 border-t border-stone-100">
                    <div class="text-xs sm:text-sm font-semibold text-stone-500 mb-1.5">
                        Volume Titik Jemput Toko
                    </div>
                    <div class="grid gap-2" :class="storePerformance.length > 1 ? 'grid-cols-2' : 'grid-cols-1'">
                        <div
                            v-for="st in storePerformance.slice(0, 2)"
                            :key="st.id"
                            class="p-2.5 rounded-lg bg-stone-50/80"
                        >
                            <div class="w-full min-w-0">
                                <div class="text-xs sm:text-sm font-bold text-stone-800 truncate" :title="st.name">
                                    {{ excerptText(st.name, 26) }}
                                </div>
                                <div class="text-xs text-stone-500 tabular-nums mt-0.5 flex items-center justify-between gap-2">
                                    <span>{{ st.shipments_count }} paket</span>
                                    <span class="font-bold text-stone-800">Rp {{ formatRupiah(st.total_spend) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-3.5">
            <div v-if="isSuperAdmin" class="p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-1">
                        <h2 class="text-base font-bold text-[#2C2C2C] tracking-tight">
                            Mitra UMKM Terdaftar
                        </h2>
                        <Link
                            href="/admin/users"
                            class="text-sm font-semibold text-primary hover:underline flex items-center gap-1 shrink-0"
                        >
                            <span>Lihat Semua</span>
                            <ChevronRight class="size-4" />
                        </Link>
                    </div>

                    <div v-if="umkmList && umkmList.length > 0" class="mt-2.5 divide-y divide-stone-100">
                        <div
                            v-for="m in umkmList.slice(0, 5)"
                            :key="m.id"
                            class="py-2 first:pt-0.5 last:pb-0 flex items-center justify-between gap-3"
                        >
                            <div class="min-w-0 flex-1">
                                <Link
                                    href="/admin/users"
                                    class="text-sm font-bold text-stone-900 hover:text-primary hover:underline block truncate leading-snug"
                                >
                                    {{ m.name }}
                                </Link>
                                <p class="text-xs text-stone-500 truncate leading-snug">
                                    {{ m.email }} - {{ m.stores_count }} toko - {{ m.shipments_count }} paket
                                </p>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="text-sm font-bold text-primary tabular-nums block leading-snug">
                                    Rp {{ formatRupiah(m.total_spent) }}
                                </span>
                                <span class="text-xs text-stone-400 whitespace-nowrap block leading-snug">
                                    {{ m.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-sm text-stone-500">
                        Belum ada mitra UMKM terdaftar.
                    </div>
                </div>
            </div>

            <div v-else class="p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-1">
                        <h2 class="text-base font-bold text-[#2C2C2C] tracking-tight">
                            Matriks Performa Ekspedisi
                        </h2>
                        <Link
                            href="/shipments"
                            class="text-sm font-semibold text-primary hover:underline flex items-center gap-1 shrink-0"
                        >
                            <span>Pengiriman</span>
                            <ChevronRight class="size-4" />
                        </Link>
                    </div>

                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div
                            v-for="courier in courierPerformance"
                            :key="courier.code"
                            class="p-2.5 rounded-lg border border-stone-200/70 bg-stone-50/50 flex items-center justify-between gap-2.5"
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="h-7 w-11 bg-white rounded border border-stone-200/80 p-0.5 flex items-center justify-center shrink-0">
                                    <img
                                        v-if="getCourierLogo(courier.code)"
                                        :src="getCourierLogo(courier.code)!"
                                        :alt="courier.name"
                                        class="max-h-4 max-w-full object-contain"
                                    />
                                    <span v-else class="text-xs font-bold text-stone-600 uppercase">
                                        {{ courier.code }}
                                    </span>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-stone-900 truncate">
                                        {{ courier.name }}
                                    </div>
                                    <div class="text-xs text-stone-500 truncate">
                                        Rata-rata Rp {{ formatRupiah(courier.avg_fee) }}
                                    </div>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <div class="text-sm font-bold text-stone-900 tabular-nums">
                                    {{ courier.count }} <span class="text-xs font-semibold text-stone-500">Paket</span>
                                </div>
                                <div class="text-xs text-stone-500 tabular-nums">
                                    {{ courier.delivered_count }} terkirim
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="isSuperAdmin" class="p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-1">
                        <h2 class="text-base font-bold text-[#2C2C2C] tracking-tight">
                            Log Aktivitas Sistem Terkini
                        </h2>
                        <Link
                            href="/admin/audit-logs"
                            class="text-sm font-semibold text-primary hover:underline flex items-center gap-1 shrink-0"
                        >
                            <span>Lihat Semua</span>
                            <ChevronRight class="size-4" />
                        </Link>
                    </div>

                    <div v-if="recentAuditLogs && recentAuditLogs.length > 0" class="mt-2.5 divide-y divide-stone-100">
                        <div
                            v-for="log in recentAuditLogs.slice(0, 5)"
                            :key="log.id"
                            class="py-2 first:pt-0.5 last:pb-0 flex items-center justify-between gap-3"
                        >
                            <div class="min-w-0 flex-1">
                                <Link
                                    href="/admin/audit-logs"
                                    class="text-sm font-bold text-stone-900 hover:text-primary hover:underline block truncate leading-snug"
                                >
                                    {{ log.auditable_label }}
                                </Link>
                                <p class="text-xs text-stone-500 truncate leading-snug">
                                    {{ log.user ? log.user.name : 'Sistem' }} - {{ log.auditable_type === 'Shipment' ? 'Pengiriman' : (log.auditable_type === 'Store' ? 'Toko' : 'Pengguna') }}<template v-if="log.formatted_changes && log.formatted_changes.length > 0"> - {{ log.formatted_changes.join(', ') }}</template>
                                </p>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="text-sm font-bold text-primary block leading-snug">
                                    {{ log.event === 'created' ? 'Created' : (log.event === 'updated' ? 'Updated' : 'Deleted') }}
                                </span>
                                <span class="text-xs text-stone-400 whitespace-nowrap block leading-snug">
                                    {{ formatRelativeTime(log.created_at) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-sm text-stone-500">
                        Belum ada riwayat audit log.
                    </div>
                </div>
            </div>

            <div v-else class="p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-1">
                        <h2 class="text-base font-bold text-[#2C2C2C] tracking-tight">
                            Pergerakan Paket Terkini
                        </h2>
                        <Link
                            href="/shipments"
                            class="text-sm font-semibold text-primary hover:underline flex items-center gap-1 shrink-0"
                        >
                            <span>Lihat Semua</span>
                            <ChevronRight class="size-4" />
                        </Link>
                    </div>

                    <div v-if="liveTrackingFeed && liveTrackingFeed.length > 0" class="mt-2.5 divide-y divide-stone-100">
                        <div
                            v-for="feed in liveTrackingFeed.slice(0, 5)"
                            :key="feed.id"
                            class="py-2 first:pt-0.5 last:pb-0 flex items-center justify-between gap-3"
                        >
                            <div class="min-w-0 flex-1">
                                <Link
                                    :href="`/shipments/${feed.id}`"
                                    class="text-sm font-bold text-primary hover:underline block truncate leading-snug"
                                >
                                    {{ feed.order_number }}
                                </Link>
                                <p class="text-xs text-stone-500 truncate leading-snug">
                                    {{ formatShortDestination(feed.destination_area_name) }}
                                </p>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="text-sm font-bold text-primary block leading-snug">
                                    {{ getShipmentStatus(feed.status).label }}
                                </span>
                                <span class="text-xs text-stone-400 whitespace-nowrap block leading-snug">
                                    {{ formatRelativeTime(feed.time) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-sm text-stone-500">
                        Belum ada pergerakan paket aktif.
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="showRateResultsModal"
            class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
        >
 <div class="bg-white rounded-xl max-w-md w-full border border-stone-200 shadow-2xl overflow-hidden p-5 space-y-4">
 <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <div>
 <h3 class="text-base font-bold text-[#2C2C2C]">
                            Perbandingan Tarif Ekspedisi
                        </h3>
 <p class="text-xs text-stone-500 mt-0.5">
                            {{ originName }} → {{ destinationQuery || 'Tujuan' }} ({{ packageWeight }} kg)
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
 class="p-3 rounded-lg border border-stone-200/80 hover:border-[#853953] transition-colors flex items-center justify-between"
                    >
                        <div>
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
                                <div>
 <div class="font-bold text-sm text-stone-900">
                                        {{ rate.courier_service_name || rate.service_name || 'Reguler' }}
                                    </div>
 <div class="text-xs text-stone-500 mt-0.5 flex items-center gap-1 font-medium">
                                        <Clock class="size-3.5" />
                                        <span>Estimasi {{ rate.duration || rate.estimated_days || '1-2 Hari' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
 <div class="font-extrabold text-sm sm:text-base text-[#853953]">
                                Rp {{ formatRupiah(rate.price) }}
                            </div>
                            <Link
                                :href="`/shipments/create?origin_area_id=${originAreaId}&weight=${packageWeight}`"
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
    </div>
</template>
