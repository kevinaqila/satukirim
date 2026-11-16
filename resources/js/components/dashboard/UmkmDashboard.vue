<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowRight,
    ChevronRight,
    Package,
    ReceiptText,
    Truck,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import QuickRateCheckCard from '@/components/dashboard/QuickRateCheckCard.vue';
import SevenDayTrendCard from '@/components/dashboard/SevenDayTrendCard.vue';
import {
    excerptText,
    formatShortDestination,
    getCourierLogo,
    getShipmentStatus,
} from '@/lib/status';
import {
    formatRelativeTime,
    formatRupiah,
    type CourierPerformanceItem,
    type DashboardMetrics,
    type DashboardShipmentItem,
    type DefaultStoreInfo,
    type LiveTrackingFeedItem,
    type ServiceBreakdownItem,
    type SevenDayTrendItem,
    type StorePerformanceItem,
    type TopDestinationItem,
    type WalletStats,
} from '@/types/dashboard';

const props = withDefaults(
    defineProps<{
        metrics: DashboardMetrics;
        walletStats?: WalletStats | null;
        defaultStore?: DefaultStoreInfo | null;
        storesCount?: number;
        sevenDayTrend?: SevenDayTrendItem[];
        weeklyGrowth?: number;
        serviceBreakdown?: ServiceBreakdownItem[];
        topDestinations?: TopDestinationItem[];
        storePerformance?: StorePerformanceItem[];
        recentShipments?: DashboardShipmentItem[];
        liveTrackingFeed?: LiveTrackingFeedItem[];
        courierPerformance?: CourierPerformanceItem[];
    }>(),
    {
        walletStats: null,
        defaultStore: null,
        storesCount: 0,
        sevenDayTrend: () => [],
        weeklyGrowth: 0,
        serviceBreakdown: () => [],
        topDestinations: () => [],
        storePerformance: () => [],
        recentShipments: () => [],
        liveTrackingFeed: () => [],
        courierPerformance: () => [],
    },
);

const umkmActiveShipmentCount = computed(() => {
    return (props.metrics?.waiting_pickup || 0) + (props.metrics?.in_transit || 0);
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

const overallDeliveryRate = computed(() => {
    const delivered = props.metrics?.delivered || 0;
    const totalActiveOrDone = (props.metrics?.waiting_pickup || 0) + (props.metrics?.in_transit || 0) + delivered;
    if (totalActiveOrDone === 0) return 0;
    return Math.round((delivered / totalActiveOrDone) * 100);
});
</script>

<template>
    <div class="flex flex-col gap-3.5 sm:gap-4">
        <div
            v-if="urgentActionCount > 0"
            class="rounded-xl bg-white border border-stone-200/80 shadow-xs p-3.5 sm:p-4 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3"
        >
            <div class="flex items-center gap-3.5 min-w-0">
                <div class="size-10 sm:size-11 rounded-lg bg-rose-50 text-[#853953] flex items-center justify-center shrink-0">
                    <AlertCircle class="size-5 sm:size-5.5" />
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm sm:text-base font-bold text-[#2C2C2C]">
                        {{ urgentActionCount }} Paket Memerlukan Tindakan Segera
                    </h3>
                    <p class="text-xs sm:text-sm text-stone-600 mt-0.5">
                        {{ urgentActionDescription }}
                    </p>
                </div>
            </div>

            <div class="shrink-0 self-end sm:self-center w-full sm:w-auto flex justify-end">
                <Link
                    :href="urgentActionHref"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold text-white bg-[#853953] hover:bg-[#6c2e43] active:scale-[0.99] shadow-xs transition-all"
                >
                    <span>Cek Pengiriman</span>
                    <ArrowRight class="size-4" />
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-3.5">
            <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-stone-600">
                    <span class="text-xs sm:text-sm font-semibold text-stone-700">Total Paket</span>
                    <div class="size-8 rounded-lg bg-rose-50 flex items-center justify-center text-[#853953] shrink-0">
                        <Package class="size-4.5" />
                    </div>
                </div>
                <div class="mt-2 sm:mt-2.5">
                    <div class="text-xl sm:text-2xl font-bold text-[#2C2C2C] tabular-nums truncate">
                        {{ metrics.total ?? ((metrics.unpaid || 0) + (metrics.waiting_pickup || 0) + (metrics.in_transit || 0) + (metrics.delivered || 0)) }}
                    </div>
                    <p class="text-xs text-stone-500 font-medium mt-0.5 truncate">
                        {{ metrics.delivered || 0 }} sampai - {{ metrics.unpaid || 0 }} belum bayar
                    </p>
                </div>
            </div>

            <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-stone-600">
                    <span class="text-xs sm:text-sm font-semibold text-stone-700">Dalam Proses</span>
                    <div class="size-8 rounded-lg bg-rose-50 flex items-center justify-center text-[#853953] shrink-0">
                        <Truck class="size-4.5" />
                    </div>
                </div>
                <div class="mt-2 sm:mt-2.5">
                    <div class="text-xl sm:text-2xl font-bold text-[#2C2C2C] tabular-nums truncate">
                        {{ umkmActiveShipmentCount }}
                    </div>
                    <p class="text-xs text-stone-500 font-medium mt-0.5 truncate">
                        {{ metrics.in_transit || 0 }} di kurir - {{ metrics.waiting_pickup || 0 }} siap pickup
                    </p>
                </div>
            </div>

            <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-stone-600">
                    <span class="text-xs sm:text-sm font-semibold text-stone-700">Total Ongkir</span>
                    <div class="size-8 rounded-lg bg-rose-50 flex items-center justify-center text-[#853953] shrink-0">
                        <ReceiptText class="size-4.5" />
                    </div>
                </div>
                <div class="mt-2 sm:mt-2.5">
                    <div class="text-xl sm:text-2xl font-bold text-[#2C2C2C] tabular-nums truncate">
                        Rp {{ formatRupiah(metrics.total_volume) }}
                    </div>
                    <p class="text-xs text-stone-500 font-medium mt-0.5 truncate">
                        {{ overallDeliveryRate }}% selesai antar
                    </p>
                </div>
            </div>

            <Link
                href="/wallet"
                class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 hover:border-[#853953]/40 shadow-xs flex flex-col justify-between transition-colors group"
            >
                <div class="flex items-center justify-between text-stone-600">
                    <span class="text-xs sm:text-sm font-semibold text-stone-700 group-hover:text-[#853953] transition-colors">Saldo Dompet COD</span>
                    <div class="size-8 rounded-lg bg-rose-50 flex items-center justify-center text-[#853953] shrink-0">
                        <Wallet class="size-4.5" />
                    </div>
                </div>
                <div class="mt-2 sm:mt-2.5">
                    <div class="text-xl sm:text-2xl font-bold text-[#2C2C2C] tabular-nums truncate">
                        Rp {{ formatRupiah(walletStats?.available_balance || 0) }}
                    </div>
                    <p class="text-xs text-stone-500 font-medium mt-0.5 truncate">
                        Tertahan: Rp {{ formatRupiah(walletStats?.pending_balance || 0) }}
                    </p>
                </div>
            </Link>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-3.5">
            <QuickRateCheckCard :default-store="defaultStore" />

            <SevenDayTrendCard
                title="Volume 7 Hari Terakhir"
                :seven-day-trend="sevenDayTrend"
                :weekly-growth="weeklyGrowth"
                :delivery-rate="overallDeliveryRate"
                :delivered-count="metrics.delivered || 0"
            />

            <div class="p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-1">
                        <h2 class="text-base font-bold text-[#2C2C2C] tracking-tight">
                            Layanan &amp; Rute Favorit
                        </h2>
                        <Link
                            href="/stores"
                            class="text-xs sm:text-sm font-semibold text-[#853953] hover:underline flex items-center gap-1 shrink-0"
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
                                    <span class="font-bold text-[#853953]">Rp {{ formatRupiah(tier.total_spend) }}</span>
                                    <span class="text-xs font-bold text-stone-500">({{ tier.count > 0 ? tier.percentage : 0 }}%)</span>
                                </div>
                            </div>
                            <div class="mt-1.5 w-full h-1.5 bg-stone-200/70 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-[#853953] rounded-full transition-all duration-500"
                                    :style="{ width: `${tier.count > 0 ? Math.max(4, tier.percentage) : 0}%` }"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="text-xs sm:text-sm font-semibold text-stone-500 mb-1.5">
                            Kota Tujuan Teratas
                        </div>
                        <div v-if="topDestinations && topDestinations.length > 0" class="space-y-1">
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
                        <p v-else class="text-xs text-stone-400 py-1.5">
                            Belum ada data kota tujuan pengiriman.
                        </p>
                    </div>
                </div>

                <div v-if="storePerformance && storePerformance.length > 1" class="mt-3">
                    <div class="text-xs sm:text-sm font-semibold text-stone-500 mb-1.5">
                        Perbandingan Titik Jemput Toko
                    </div>
                    <div class="grid grid-cols-2 gap-2">
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
                <div v-else-if="defaultStore" class="mt-3 pt-2.5 border-t border-stone-100">
                    <div class="p-2.5 rounded-lg bg-stone-50/80 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <span class="text-xs font-semibold text-stone-500 block">Titik Jemput Utama</span>
                            <span class="text-xs sm:text-sm font-bold text-[#2C2C2C] truncate block">
                                {{ defaultStore.name }}
                            </span>
                        </div>
                        <Link
                            href="/shipments/create"
                            class="px-2.5 py-1.5 rounded-lg bg-[#853953] hover:bg-[#6c2e43] text-white text-xs font-semibold shrink-0 transition-colors"
                        >
                            + Kirim Paket
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-3.5">
            <div class="p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-1">
                        <h2 class="text-base font-bold text-[#2C2C2C] tracking-tight">
                            Pengiriman Terbaru
                        </h2>
                        <Link
                            href="/shipments"
                            class="text-xs sm:text-sm font-semibold text-[#853953] hover:underline flex items-center gap-1 shrink-0"
                        >
                            <span>Lihat Semua</span>
                            <ChevronRight class="size-4" />
                        </Link>
                    </div>

                    <div v-if="recentShipments && recentShipments.length > 0" class="mt-2.5 divide-y divide-stone-100">
                        <div
                            v-for="s in recentShipments.slice(0, 5)"
                            :key="s.id"
                            class="py-2 first:pt-0.5 last:pb-0 flex items-center justify-between gap-3"
                        >
                            <div class="min-w-0 flex-1">
                                <Link
                                    :href="`/shipments/${s.id}`"
                                    class="text-sm font-bold text-[#853953] hover:underline block truncate leading-snug"
                                >
                                    {{ s.order_number }}
                                </Link>
                                <p class="text-xs text-stone-500 truncate leading-snug">
                                    {{ s.recipient_name }} - {{ formatShortDestination(s.destination_area_name || s.destination_address) }}
                                </p>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="text-sm font-bold text-[#2C2C2C] tabular-nums block leading-snug">
                                    Rp {{ formatRupiah(s.total_amount) }}
                                </span>
                                <span class="text-xs font-medium text-stone-500 whitespace-nowrap block leading-snug">
                                    {{ getShipmentStatus(s.status).label }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-sm text-stone-500">
                        Belum ada pengiriman dibuat.
                    </div>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-1">
                        <h2 class="text-base font-bold text-[#2C2C2C] tracking-tight">
                            Pergerakan Paket Terkini
                        </h2>
                        <Link
                            href="/shipments"
                            class="text-xs sm:text-sm font-semibold text-[#853953] hover:underline flex items-center gap-1 shrink-0"
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
                                    class="text-sm font-bold text-[#853953] hover:underline block truncate leading-snug"
                                >
                                    {{ feed.order_number }}
                                </Link>
                                <p class="text-xs text-stone-500 truncate leading-snug">
                                    {{ formatShortDestination(feed.destination_area_name) }}
                                </p>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="text-sm font-bold text-[#853953] block leading-snug">
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

        <div class="p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs">
            <div class="flex items-center justify-between pb-1">
                <h2 class="text-base font-bold text-[#2C2C2C] tracking-tight">
                    Performa Ekspedisi Digunakan
                </h2>
                <Link
                    href="/shipments"
                    class="text-xs sm:text-sm font-semibold text-[#853953] hover:underline flex items-center gap-1 shrink-0"
                >
                    <span>Pengiriman</span>
                    <ChevronRight class="size-4" />
                </Link>
            </div>

            <div v-if="courierPerformance && courierPerformance.length > 0" class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
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
                            <div class="text-sm font-bold text-[#2C2C2C] truncate">
                                {{ courier.name }}
                            </div>
                            <div class="text-xs text-stone-500 truncate tabular-nums">
                                Rata-rata Rp {{ formatRupiah(courier.avg_fee) }}
                            </div>
                        </div>
                    </div>

                    <div class="text-right shrink-0">
                        <div class="text-sm font-bold text-[#2C2C2C] tabular-nums">
                            {{ courier.count }} <span class="text-xs font-semibold text-stone-500">Paket</span>
                        </div>
                        <div class="text-xs text-stone-500 tabular-nums">
                            {{ courier.delivered_count }} terkirim
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="py-6 text-center text-sm text-stone-500">
                Belum ada riwayat ekspedisi. Buat pengiriman pertama untuk melihat ringkasan performa kurir.
            </div>
        </div>
    </div>
</template>

