<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { Calendar } from '@lucide/vue';
import { computed } from 'vue';
import AppTopBar from '@/components/AppTopBar.vue';
import SuperAdminDashboard from '@/components/dashboard/SuperAdminDashboard.vue';
import UmkmDashboard from '@/components/dashboard/UmkmDashboard.vue';
import { dashboard } from '@/routes';
import type {
    AuditLogItem,
    CourierPerformanceItem,
    DashboardMetrics,
    DashboardShipmentItem,
    DefaultStoreInfo,
    LiveTrackingFeedItem,
    PlatformStats,
    ServiceBreakdownItem,
    SevenDayTrendItem,
    StorePerformanceItem,
    TopDestinationItem,
    UmkmListItem,
} from '@/types/dashboard';

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

withDefaults(
    defineProps<{
        isSuperAdmin?: boolean;
        metrics?: DashboardMetrics;
        sevenDayTrend?: SevenDayTrendItem[];
        weeklyGrowth?: number;
        topDestinations?: TopDestinationItem[];
        courierPerformance?: CourierPerformanceItem[];
        serviceBreakdown?: ServiceBreakdownItem[];
        storePerformance?: StorePerformanceItem[];
        liveTrackingFeed?: LiveTrackingFeedItem[];
        recentShipments?: DashboardShipmentItem[];
        defaultStore?: DefaultStoreInfo | null;
        storesCount?: number;
        platformStats?: PlatformStats | null;
        recentAuditLogs?: AuditLogItem[];
        umkmList?: UmkmListItem[];
    }>(),
    {
        isSuperAdmin: false,
        metrics: () => ({ total: 0, unpaid: 0, waiting_pickup: 0, in_transit: 0, delivered: 0, failed: 0, cancelled: 0, returned: 0, total_volume: 0 }),
        sevenDayTrend: () => [],
        weeklyGrowth: 0,
        topDestinations: () => [],
        courierPerformance: () => [],
        serviceBreakdown: () => [],
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

    <div class="flex h-full flex-1 flex-col gap-3.5 sm:gap-4 p-3 sm:p-4 lg:p-5 w-full">
        <SuperAdminDashboard
            v-if="isSuperAdmin"
            :metrics="metrics"
            :platform-stats="platformStats"
            :stores-count="storesCount"
            :seven-day-trend="sevenDayTrend"
            :weekly-growth="weeklyGrowth"
            :courier-performance="courierPerformance"
            :top-destinations="topDestinations"
            :recent-shipments="recentShipments"
            :umkm-list="umkmList"
            :recent-audit-logs="recentAuditLogs"
        />

        <UmkmDashboard
            v-else
            :metrics="metrics"
            :default-store="defaultStore"
            :stores-count="storesCount"
            :seven-day-trend="sevenDayTrend"
            :weekly-growth="weeklyGrowth"
            :service-breakdown="serviceBreakdown"
            :top-destinations="topDestinations"
            :store-performance="storePerformance"
            :recent-shipments="recentShipments"
            :live-tracking-feed="liveTrackingFeed"
            :courier-performance="courierPerformance"
        />
    </div>
</template>
