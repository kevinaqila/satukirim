<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ChevronRight,
    CreditCard,
    PackageCheck,
    ReceiptText,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import SevenDayTrendCard from '@/components/dashboard/SevenDayTrendCard.vue';
import { formatShortDestination, getShipmentStatus } from '@/lib/status';
import {
    formatRelativeTime,
    formatRupiah,
    type AuditLogItem,
    type CourierPerformanceItem,
    type DashboardMetrics,
    type DashboardShipmentItem,
    type PlatformStats,
    type SevenDayTrendItem,
    type TopDestinationItem,
    type UmkmListItem,
} from '@/types/dashboard';

const props = withDefaults(
    defineProps<{
        metrics: DashboardMetrics;
        platformStats?: PlatformStats | null;
        storesCount?: number;
        sevenDayTrend?: SevenDayTrendItem[];
        weeklyGrowth?: number;
        courierPerformance?: CourierPerformanceItem[];
        topDestinations?: TopDestinationItem[];
        recentShipments?: DashboardShipmentItem[];
        umkmList?: UmkmListItem[];
        recentAuditLogs?: AuditLogItem[];
    }>(),
    {
        platformStats: null,
        storesCount: 0,
        sevenDayTrend: () => [],
        weeklyGrowth: 0,
        courierPerformance: () => [],
        topDestinations: () => [],
        recentShipments: () => [],
        umkmList: () => [],
        recentAuditLogs: () => [],
    },
);

const paymentTotalCount = computed(() => {
    return (props.platformStats?.non_cod_count || 0) + (props.platformStats?.cod_count || 0);
});

const nonCodPercentage = computed(() => {
    if (paymentTotalCount.value === 0) return 0;
    return Math.round(((props.platformStats?.non_cod_count || 0) / paymentTotalCount.value) * 100);
});

const codPercentage = computed(() => {
    if (paymentTotalCount.value === 0) return 0;
    return Math.round(((props.platformStats?.cod_count || 0) / paymentTotalCount.value) * 100);
});
</script>

<template>
    <div class="flex flex-col gap-3.5 sm:gap-4">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-3.5">
            <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-stone-600">
                    <span class="text-xs sm:text-sm font-semibold text-stone-700">Pendapatan Platform</span>
                    <div class="size-8 rounded-lg bg-rose-50 flex items-center justify-center text-[#853953] shrink-0">
                        <CreditCard class="size-4.5" />
                    </div>
                </div>
                <div class="mt-2 sm:mt-2.5">
                    <div class="text-xl sm:text-2xl font-bold text-[#2C2C2C] tabular-nums truncate">
                        Rp {{ formatRupiah(platformStats?.platform_revenue || 0) }}
                    </div>
                    <p class="text-xs text-stone-500 font-medium mt-0.5 truncate">
                        Biaya layanan Rp 1.000 / transaksi
                    </p>
                </div>
            </div>

            <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-stone-600">
                    <span class="text-xs sm:text-sm font-semibold text-stone-700">Perputaran Transaksi</span>
                    <div class="size-8 rounded-lg bg-rose-50 flex items-center justify-center text-[#853953] shrink-0">
                        <ReceiptText class="size-4.5" />
                    </div>
                </div>
                <div class="mt-2 sm:mt-2.5">
                    <div class="text-xl sm:text-2xl font-bold text-[#2C2C2C] tabular-nums truncate">
                        Rp {{ formatRupiah(platformStats?.total_volume || metrics.total_volume || 0) }}
                    </div>
                    <p class="text-xs text-stone-500 font-medium mt-0.5 truncate">
                        Akumulasi ongkir terbayar
                    </p>
                </div>
            </div>

            <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-stone-600">
                    <span class="text-xs sm:text-sm font-semibold text-stone-700">Mitra UMKM</span>
                    <div class="size-8 rounded-lg bg-rose-50 flex items-center justify-center text-[#853953] shrink-0">
                        <Users class="size-4.5" />
                    </div>
                </div>
                <div class="mt-2 sm:mt-2.5">
                    <div class="text-xl sm:text-2xl font-bold text-[#2C2C2C] tabular-nums truncate">
                        {{ platformStats?.total_umkm || 0 }}
                    </div>
                    <p class="text-xs text-stone-500 font-medium mt-0.5 truncate">
                        {{ platformStats?.active_umkm || 0 }} aktif - {{ platformStats?.total_stores || storesCount }} toko
                    </p>
                </div>
            </div>

            <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-stone-600">
                    <span class="text-xs sm:text-sm font-semibold text-stone-700">Total Pengiriman</span>
                    <div class="size-8 rounded-lg bg-rose-50 flex items-center justify-center text-[#853953] shrink-0">
                        <PackageCheck class="size-4.5" />
                    </div>
                </div>
                <div class="mt-2 sm:mt-2.5">
                    <div class="text-xl sm:text-2xl font-bold text-[#2C2C2C] tabular-nums truncate">
                        {{ platformStats?.total_shipments || metrics.total || 0 }}
                    </div>
                    <p class="text-xs text-stone-500 font-medium mt-0.5 truncate">
                        {{ platformStats?.delivered_rate || 0 }}% tingkat keberhasilan antar
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-3.5">
            <div class="p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-1">
                        <h2 class="text-base font-bold text-[#2C2C2C] tracking-tight">
                            Status Operasional &amp; COD
                        </h2>
                        <Link
                            href="/admin/transactions"
                            class="text-xs sm:text-sm font-semibold text-[#853953] hover:underline flex items-center gap-1 shrink-0"
                        >
                            <span>Transaksi</span>
                            <ChevronRight class="size-4" />
                        </Link>
                    </div>

                    <div class="mt-3 space-y-2">
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
                                <div class="text-xs sm:text-sm font-bold text-[#853953] tabular-nums shrink-0">
                                    {{ platformStats?.total_shipments ? Math.round(((platformStats.paid_shipments || 0) / platformStats.total_shipments) * 100) : 0 }}%
                                </div>
                            </div>
                            <div class="mt-1.5 w-full h-1.5 bg-stone-200/70 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-[#853953] rounded-full transition-all duration-500"
                                    :style="{ width: `${platformStats?.total_shipments && platformStats.paid_shipments ? Math.max(4, Math.round((platformStats.paid_shipments / platformStats.total_shipments) * 100)) : 0}%` }"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 pt-2.5 border-t border-stone-100">
                        <div class="text-xs sm:text-sm font-semibold text-stone-500 mb-1.5">
                            Pipeline Status Pengiriman
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs sm:text-sm py-0.5">
                                <span class="font-semibold text-stone-800">Menunggu Pembayaran</span>
                                <span class="text-xs sm:text-sm text-stone-500 font-medium tabular-nums">
                                    <strong class="font-bold text-stone-800">{{ metrics.unpaid || 0 }}</strong> paket
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs sm:text-sm py-0.5">
                                <span class="font-semibold text-stone-800">Sedang Diproses &amp; Dikirim</span>
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
                            <div class="flex items-center justify-between text-xs sm:text-sm py-0.5">
                                <span class="font-semibold text-stone-800">Dibatalkan / Retur</span>
                                <span class="text-xs sm:text-sm text-stone-500 font-medium tabular-nums">
                                    <strong class="font-bold text-stone-800">{{ platformStats?.cancelled_shipments || 0 }}</strong> paket
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 pt-2.5 border-t border-stone-100">
                    <div class="text-xs sm:text-sm font-semibold text-stone-500 mb-1.5">
                        Pencairan Dana COD (Withdrawal)
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="p-2.5 rounded-lg bg-stone-50/80">
                            <div class="text-xs font-semibold text-stone-500 truncate">
                                Dalam Proses ({{ platformStats?.pending_withdrawals_count || 0 }})
                            </div>
                            <div class="text-xs sm:text-sm font-bold text-[#2C2C2C] tabular-nums mt-0.5 truncate">
                                Rp {{ formatRupiah(platformStats?.pending_withdrawals_amount || 0) }}
                            </div>
                        </div>
                        <div class="p-2.5 rounded-lg bg-stone-50/80">
                            <div class="text-xs font-semibold text-stone-500 truncate">
                                Sudah Dicairkan ({{ platformStats?.completed_withdrawals_count || 0 }})
                            </div>
                            <div class="text-xs sm:text-sm font-bold text-[#853953] tabular-nums mt-0.5 truncate">
                                Rp {{ formatRupiah(platformStats?.completed_withdrawals_amount || 0) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <SevenDayTrendCard
                title="Volume Pengiriman 7 Hari"
                :seven-day-trend="sevenDayTrend"
                :weekly-growth="weeklyGrowth"
                :delivery-rate="platformStats?.delivered_rate || 0"
                :delivered-count="metrics.delivered || 0"
            />

            <div class="p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-1">
                        <h2 class="text-base font-bold text-[#2C2C2C] tracking-tight">
                            Metode Bayar &amp; Ekspedisi
                        </h2>
                        <Link
                            href="/admin/transactions"
                            class="text-xs sm:text-sm font-semibold text-[#853953] hover:underline flex items-center gap-1 shrink-0"
                        >
                            <span>Detail</span>
                            <ChevronRight class="size-4" />
                        </Link>
                    </div>

                    <div class="mt-3 space-y-2">
                        <div class="p-2.5 rounded-lg bg-stone-50/80">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="text-xs sm:text-sm font-bold text-stone-800 truncate">
                                        Non-COD (Online / QRIS)
                                    </span>
                                    <span class="text-xs text-stone-400 tabular-nums shrink-0">
                                        ({{ platformStats?.non_cod_count || 0 }} paket)
                                    </span>
                                </div>
                                <div class="text-xs sm:text-sm flex items-center gap-1 tabular-nums shrink-0">
                                    <span class="font-bold text-[#853953]">Rp {{ formatRupiah(platformStats?.non_cod_volume || 0) }}</span>
                                    <span class="text-xs font-bold text-stone-500">({{ nonCodPercentage }}%)</span>
                                </div>
                            </div>
                            <div class="mt-1.5 w-full h-1.5 bg-stone-200/70 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-[#853953] rounded-full transition-all duration-500"
                                    :style="{ width: `${nonCodPercentage > 0 ? Math.max(4, nonCodPercentage) : 0}%` }"
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
                                    <span class="font-bold text-[#853953]">Rp {{ formatRupiah(platformStats?.cod_volume || 0) }}</span>
                                    <span class="text-xs font-bold text-stone-500">({{ codPercentage }}%)</span>
                                </div>
                            </div>
                            <div class="mt-1.5 w-full h-1.5 bg-stone-200/70 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-[#853953] rounded-full transition-all duration-500"
                                    :style="{ width: `${codPercentage > 0 ? Math.max(4, codPercentage) : 0}%` }"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 pt-2.5 border-t border-stone-100">
                        <div class="text-xs sm:text-sm font-semibold text-stone-500 mb-1.5">
                            Ekspedisi Terbanyak Dipakai
                        </div>
                        <div v-if="courierPerformance && courierPerformance.length > 0" class="space-y-1.5">
                            <div
                                v-for="courier in courierPerformance.slice(0, 4)"
                                :key="courier.code"
                                class="flex items-center justify-between text-xs sm:text-sm py-0.5 gap-2"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="font-semibold text-stone-800 truncate">{{ courier.name }}</span>
                                    <span class="text-xs text-stone-400 tabular-nums shrink-0">{{ courier.share_percentage }}%</span>
                                </div>
                                <span class="text-xs sm:text-sm text-stone-500 font-medium tabular-nums shrink-0">
                                    <strong class="font-bold text-stone-800">{{ courier.count }}</strong> paket
                                </span>
                            </div>
                        </div>
                        <p v-else class="text-xs text-stone-400 py-2">
                            Belum ada transaksi ekspedisi tercatat.
                        </p>
                    </div>
                </div>

                <div v-if="topDestinations && topDestinations.length > 0" class="mt-3 pt-2.5 border-t border-stone-100">
                    <div class="text-xs sm:text-sm font-semibold text-stone-500 mb-1.5">
                        Kota Tujuan Terpadat
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="(dest, idx) in topDestinations"
                            :key="idx"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-stone-50 border border-stone-200/70 text-xs font-semibold text-stone-700 tabular-nums"
                        >
                            <span>{{ dest.city }}</span>
                            <span class="text-[#853953] font-bold">{{ dest.count }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-3.5">
            <div class="p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-1">
                        <h2 class="text-base font-bold text-[#2C2C2C] tracking-tight">
                            Transaksi Pengiriman Terbaru
                        </h2>
                        <Link
                            href="/admin/transactions"
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
                                    href="/admin/transactions"
                                    class="text-sm font-bold text-[#853953] hover:underline block truncate leading-snug"
                                >
                                    {{ s.order_number }}
                                </Link>
                                <p class="text-xs text-stone-500 truncate leading-snug">
                                    {{ s.user?.name || 'UMKM' }} - {{ formatShortDestination(s.destination_area_name || s.destination_address) }}
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
                        Belum ada transaksi pengiriman tercatat.
                    </div>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-1">
                        <h2 class="text-base font-bold text-[#2C2C2C] tracking-tight">
                            Mitra UMKM Terdaftar
                        </h2>
                        <Link
                            href="/admin/users"
                            class="text-xs sm:text-sm font-semibold text-[#853953] hover:underline flex items-center gap-1 shrink-0"
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
                                    class="text-sm font-bold text-[#2C2C2C] hover:text-[#853953] hover:underline block truncate leading-snug"
                                >
                                    {{ m.name }}
                                </Link>
                                <p class="text-xs text-stone-500 truncate leading-snug">
                                    {{ m.email }} - {{ m.stores_count }} toko - {{ m.shipments_count }} paket
                                </p>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="text-sm font-bold text-[#853953] tabular-nums block leading-snug">
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
        </div>

        <div class="p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs">
            <div class="flex items-center justify-between pb-1">
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-bold text-[#2C2C2C] tracking-tight">
                        Log Aktivitas Sistem Terkini
                    </h2>
                    <span class="px-2 py-0.5 rounded-md bg-stone-100 text-xs font-semibold text-stone-600 tabular-nums">
                        {{ platformStats?.total_audit_logs || 0 }} log
                    </span>
                </div>
                <Link
                    href="/admin/audit-logs"
                    class="text-xs sm:text-sm font-semibold text-[#853953] hover:underline flex items-center gap-1 shrink-0"
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
                            class="text-sm font-bold text-[#2C2C2C] hover:text-[#853953] hover:underline block truncate leading-snug"
                        >
                            {{ log.auditable_label }}
                        </Link>
                        <p class="text-xs text-stone-500 truncate leading-snug">
                            {{ log.user ? log.user.name : 'Sistem' }} - {{ log.auditable_type === 'Shipment' ? 'Pengiriman' : (log.auditable_type === 'Store' ? 'Toko' : 'Pengguna') }}<template v-if="log.formatted_changes && log.formatted_changes.length > 0"> - {{ log.formatted_changes.join(', ') }}</template>
                        </p>
                    </div>

                    <div class="text-right shrink-0">
                        <span class="text-sm font-bold text-[#853953] block leading-snug">
                            {{ log.event === 'created' ? 'Created' : (log.event === 'updated' ? 'Updated' : 'Deleted') }}
                        </span>
                        <span class="text-xs text-stone-400 whitespace-nowrap block leading-snug">
                            {{ formatRelativeTime(log.created_at) }}
                        </span>
                    </div>
                </div>
            </div>
            <div v-else class="py-6 text-center text-sm text-stone-500">
                Belum ada riwayat audit log.
            </div>
        </div>
    </div>
</template>

