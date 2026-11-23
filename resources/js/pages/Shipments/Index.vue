<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Check,
    ChevronDown,
    Download,
    Loader2,
    Printer,
    Search,
    SlidersHorizontal,
    Trash2,
    Truck,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import AppTopBar from '@/components/AppTopBar.vue';
import DatePeriodFilter from '@/components/DatePeriodFilter.vue';
import ShipmentDeleteDialogs from '@/components/shipments/ShipmentDeleteDialogs.vue';
import ShipmentsDesktopTable, { type ShipmentSortField } from '@/components/shipments/ShipmentsDesktopTable.vue';
import ShipmentsMobileList from '@/components/shipments/ShipmentsMobileList.vue';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { exportToExcel } from '@/lib/exportExcel';
import { formatDateTime, getShipmentStatus } from '@/lib/status';
import type { ShipmentListItem } from '@/types/shipments';

const props = defineProps<{
    shipments: {
        data: ShipmentListItem[];
        current_page: number;
        last_page: number;
        total: number;
        from?: number | null;
        to?: number | null;
        links?: Array<{ url: string | null; label: string; active: boolean }>;
    };
    couriers: string[];
    filters: {
        status: string;
        search: string;
        courier?: string;
        date_preset?: string;
        date_from?: string;
        date_to?: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Data Pengiriman',
                href: '/shipments',
            },
        ],
    },
});

const searchQuery = ref(props.filters.search || '');
const activeStatus = ref(props.filters.status || 'all');
const activeCourier = ref(props.filters.courier || 'all');
const datePreset = ref(props.filters.date_preset || 'all');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const statusTabs = [
    { id: 'all', label: 'Semua' },
    { id: 'pending_payment', label: 'Perlu Dibayar' },
    { id: 'paid', label: 'Menunggu Pickup' },
    { id: 'in_transit', label: 'Sedang Diantar' },
    { id: 'delivered', label: 'Tiba di Tujuan' },
];

const applyFilters = () => {
    router.get(
        '/shipments',
        {
            status: activeStatus.value,
            search: searchQuery.value,
            courier: activeCourier.value,
            date_preset: datePreset.value,
            date_from: dateFrom.value,
            date_to: dateTo.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(searchQuery, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyFilters();
    }, 400);
});

const selectCourier = (value: string) => {
    activeCourier.value = value;
    applyFilters();
};

const handleDateChange = (payload: { preset: string; dateFrom: string; dateTo: string }) => {
    datePreset.value = payload.preset;
    dateFrom.value = payload.dateFrom;
    dateTo.value = payload.dateTo;
    applyFilters();
};

const activeStatusLabel = computed(
    () => statusTabs.find((tab) => tab.id === activeStatus.value)?.label ?? 'Semua',
);

const activeCourierLabel = computed(() =>
    activeCourier.value === 'all' ? 'Semua Ekspedisi' : activeCourier.value.toUpperCase(),
);

const selectTab = (tabId: string) => {
    activeStatus.value = tabId;
    applyFilters();
};

const sortField = ref<ShipmentSortField | null>(null);
const sortDirection = ref<'asc' | 'desc'>('asc');

const toggleSort = (field: ShipmentSortField) => {
    if (sortField.value === field) {
        if (sortDirection.value === 'asc') {
            sortDirection.value = 'desc';
        } else {
            sortField.value = null;
            sortDirection.value = 'asc';
        }
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

const columnVisibility = ref({
    id: false,
    store: true,
    recipient: true,
    courier: true,
    waybill: true,
    weight: true,
    amount: true,
    status: true,
});

const mobileShipments = ref<ShipmentListItem[]>([]);
const isLoadingMore = ref(false);

watch(
    () => props.shipments,
    (value) => {
        const incoming = value.data ?? [];
        if (value.current_page <= 1) {
            mobileShipments.value = [...incoming];
            return;
        }
        const knownIds = new Set(mobileShipments.value.map((item) => item.id));
        mobileShipments.value = [
            ...mobileShipments.value,
            ...incoming.filter((item) => !knownIds.has(item.id)),
        ];
    },
    { immediate: true },
);

const hasMorePages = computed(() => props.shipments.current_page < props.shipments.last_page);

const loadMore = () => {
    if (isLoadingMore.value || !hasMorePages.value) return;
    isLoadingMore.value = true;
    router.get(
        '/shipments',
        {
            status: activeStatus.value,
            search: searchQuery.value,
            courier: activeCourier.value,
            date_preset: datePreset.value,
            date_from: dateFrom.value,
            date_to: dateTo.value,
            page: props.shipments.current_page + 1,
        },
        {
            only: ['shipments'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                isLoadingMore.value = false;
            },
        },
    );
};

const displayedShipments = computed(() => {
    if (!props.shipments.data) return [];
    const list = [...props.shipments.data];

    if (sortField.value) {
        const field = sortField.value;
        const dir = sortDirection.value === 'asc' ? 1 : -1;
        list.sort((a, b) => {
            if (field === 'id') return (a.id - b.id) * dir;
            if (field === 'order_number') return a.order_number.localeCompare(b.order_number) * dir;
            if (field === 'store') return (a.store?.name || '').localeCompare(b.store?.name || '') * dir;
            if (field === 'recipient') return a.recipient_name.localeCompare(b.recipient_name) * dir;
            if (field === 'courier') return (a.courier_service_name || '').localeCompare(b.courier_service_name || '') * dir;
            if (field === 'weight') return ((a.weight || 0) - (b.weight || 0)) * dir;
            if (field === 'total_amount') return (a.total_amount - b.total_amount) * dir;
            if (field === 'status') return a.status.localeCompare(b.status) * dir;
            return 0;
        });
    }

    return list;
});

const selectedIds = ref<number[]>([]);

const isAllSelected = computed(() => {
    if (displayedShipments.value.length === 0) return false;
    return displayedShipments.value.every((item) => selectedIds.value.includes(item.id));
});

const isSomeSelected = computed(() => {
    return displayedShipments.value.some((item) => selectedIds.value.includes(item.id)) && !isAllSelected.value;
});

const toggleSelectAll = (checked: boolean | 'indeterminate') => {
    selectedIds.value = checked === true ? displayedShipments.value.map((item) => item.id) : [];
};

const toggleSelectRow = (id: number) => {
    const idx = selectedIds.value.indexOf(id);
    if (idx >= 0) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
};

const handleBatchPrint = () => {
    if (selectedIds.value.length === 0) return;

    const allKnownShipments = [...(props.shipments.data || []), ...mobileShipments.value];
    const shipmentMap = new Map<number, ShipmentListItem>();
    allKnownShipments.forEach((s) => shipmentMap.set(s.id, s));

    const validIds = selectedIds.value.filter((id) => {
        const item = shipmentMap.get(id);
        return item ? item.status !== 'pending_payment' : true;
    });

    if (validIds.length === 0) {
        toast.warning('Hanya pengiriman yang sudah dibayar yang memiliki resi untuk dicetak.');
        return;
    }

    if (validIds.length < selectedIds.value.length) {
        toast.info(`${selectedIds.value.length - validIds.length} pengiriman belum dibayar dilewati dari cetak resi.`);
    }

    window.open(`/shipments/print-bulk?ids=${validIds.join(',')}`, '_blank');
};

const exportShipmentsToExcel = (targetData: ShipmentListItem[], subtitleSuffix = '') => {
    if (targetData.length === 0) return;

    exportToExcel<ShipmentListItem>({
        filename: `Data_Pengiriman_${new Date().toISOString().slice(0, 10)}`,
        title: 'SatuKirim - Laporan Data Pengiriman',
        subtitle: `Dibuat pada ${new Date().toLocaleString('id-ID')} | Total: ${targetData.length} data${subtitleSuffix ? ' (' + subtitleSuffix + ')' : ''}`,
        columns: [
            { header: 'No', align: 'center', type: 'number', width: 6, formatter: (_, idx) => idx + 1 },
            { header: 'ID', align: 'center', type: 'number', width: 8, formatter: (item) => item.id },
            { header: 'Kode Transaksi', align: 'left', type: 'text', key: 'order_number' },
            { header: 'Tanggal Transaksi', align: 'center', type: 'text', formatter: (item) => formatDateTime(item.created_at) },
            { header: 'Toko Asal', align: 'left', type: 'text', formatter: (item) => item.store?.name || '-' },
            { header: 'Nama Penerima', align: 'left', type: 'text', key: 'recipient_name' },
            { header: 'No. HP Penerima', align: 'left', type: 'text', formatter: (item) => item.recipient_phone ? String(item.recipient_phone).trim() : '-' },
            { header: 'Tujuan', align: 'left', type: 'text', formatter: (item) => item.destination_area_name || item.destination_address || '-' },
            { header: 'Ekspedisi', align: 'left', type: 'text', formatter: (item) => `${item.courier_code?.toUpperCase()} (${item.courier_service_name})` },
            { header: 'No. Resi (AWB)', align: 'left', type: 'text', formatter: (item) => item.waybill_number || '-' },
            { header: 'Berat (gr)', align: 'right', type: 'number', formatter: (item) => item.weight ?? 0 },
            { header: 'Metode Bayar', align: 'center', type: 'text', formatter: (item) => item.payment_method === 'cod' ? 'COD (Bayar di Tempat)' : 'Non-COD (Online)' },
            { header: 'Nilai Barang (Rp)', align: 'right', type: 'currency', formatter: (item) => Number(item.item_value || 0).toLocaleString('id-ID') },
            { header: 'Ongkir (Rp)', align: 'right', type: 'currency', formatter: (item) => Number(item.shipping_fee || 0).toLocaleString('id-ID') },
            { header: 'Total Biaya (Rp)', align: 'right', type: 'currency', formatter: (item) => Number(item.total_amount || 0).toLocaleString('id-ID') },
            { header: 'Status', align: 'center', type: 'text', formatter: (item) => getShipmentStatus(item.status).label },
        ],
        data: targetData,
    });
};

const exportCurrentPage = () => {
    exportShipmentsToExcel(displayedShipments.value, 'Halaman Ini');
};

const exportSelected = () => {
    const selectedRows = displayedShipments.value.filter((item) => selectedIds.value.includes(item.id));
    if (selectedRows.length === 0) return;
    exportShipmentsToExcel(selectedRows, `${selectedRows.length} Data Terpilih`);
};

const isExportingAll = ref(false);

const handleExportAll = async () => {
    if (isExportingAll.value) return;
    isExportingAll.value = true;
    try {
        const res = await fetch('/shipments/export-all?no_filter=1', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!res.ok) {
            throw new Error('Gagal mengunduh seluruh data pengiriman.');
        }

        const result = await res.json();
        const items: ShipmentListItem[] = Array.isArray(result) ? result : (result.shipments || result.data || []);

        if (items.length === 0) {
            toast.warning('Tidak ada data pengiriman untuk diekspor.');
            return;
        }

        exportShipmentsToExcel(items);
    } catch (err: unknown) {
        const message = err instanceof Error ? err.message : 'Terjadi kesalahan saat mengunduh seluruh data.';
        toast.error(message);
    } finally {
        isExportingAll.value = false;
    }
};

const isDeleteModalOpen = ref(false);
const shipmentToDelete = ref<ShipmentListItem | null>(null);
const isDeleting = ref(false);

const openDeleteModal = (item: ShipmentListItem) => {
    shipmentToDelete.value = item;
    isDeleteModalOpen.value = true;
};

const confirmDeleteShipment = () => {
    if (!shipmentToDelete.value || isDeleting.value) return;

    const targetId = shipmentToDelete.value.id;
    isDeleting.value = true;

    router.delete(`/shipments/${targetId}`, {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = selectedIds.value.filter((id) => id !== targetId);
            mobileShipments.value = mobileShipments.value.filter((s) => s.id !== targetId);
            isDeleteModalOpen.value = false;
            shipmentToDelete.value = null;
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const isBulkDeleteModalOpen = ref(false);
const isBulkDeleting = ref(false);

const confirmBulkDelete = () => {
    if (selectedIds.value.length === 0 || isBulkDeleting.value) return;
    isBulkDeleting.value = true;
    router.post(
        '/shipments/bulk-delete',
        { ids: selectedIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                const deletedSet = new Set(selectedIds.value);
                mobileShipments.value = mobileShipments.value.filter((s) => !deletedSet.has(s.id));
                selectedIds.value = [];
                isBulkDeleteModalOpen.value = false;
            },
            onFinish: () => {
                isBulkDeleting.value = false;
            },
        },
    );
};
</script>

<template>
    <div class="flex min-h-full flex-1 flex-col w-full">
        <Head title="Data Pengiriman - SatuKirim" />

        <AppTopBar title="Data Pengiriman" />

        <div class="flex h-full flex-1 flex-col gap-3.5 p-3 sm:p-4 lg:p-5 w-full">
            <div class="rounded-none border border-stone-200 bg-white overflow-hidden shadow-2xs">
                <div class="flex flex-wrap items-center justify-between gap-2 px-3.5 sm:px-4 border-b border-stone-200 bg-stone-50/60">
                    <div class="md:hidden w-full py-2.5 grid grid-cols-2 gap-2">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button
                                    type="button"
                                    class="flex h-10 w-full items-center justify-between gap-2 rounded-md border border-stone-200 bg-white px-3 text-sm font-semibold text-[#853953] cursor-pointer"
                                    aria-label="Filter status pengiriman"
                                >
                                    <span class="truncate">{{ activeStatusLabel }}</span>
                                    <ChevronDown class="size-4 shrink-0 text-stone-400" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start" class="w-(--reka-dropdown-menu-trigger-width) min-w-44">
                                <DropdownMenuItem
                                    v-for="tab in statusTabs"
                                    :key="tab.id"
                                    class="justify-between text-sm"
                                    :class="activeStatus === tab.id ? 'text-[#853953] font-bold' : ''"
                                    @select="selectTab(tab.id)"
                                >
                                    {{ tab.label }}
                                    <Check v-if="activeStatus === tab.id" class="size-4" />
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button
                                    type="button"
                                    class="flex h-10 w-full items-center justify-between gap-2 rounded-md border border-stone-200 bg-white px-3 text-sm font-semibold text-stone-700 cursor-pointer"
                                    aria-label="Filter ekspedisi"
                                >
                                    <span class="truncate">{{ activeCourierLabel }}</span>
                                    <ChevronDown class="size-4 shrink-0 text-stone-400" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-(--reka-dropdown-menu-trigger-width) min-w-44">
                                <DropdownMenuItem
                                    v-for="option in ['all', ...couriers]"
                                    :key="option"
                                    class="justify-between text-sm"
                                    :class="activeCourier === option ? 'text-[#853953] font-bold' : ''"
                                    @select="selectCourier(option)"
                                >
                                    {{ option === 'all' ? 'Semua Ekspedisi' : option.toUpperCase() }}
                                    <Check v-if="activeCourier === option" class="size-4" />
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <div class="hidden md:flex flex-nowrap items-center gap-1 overflow-x-auto -mb-px">
                        <button
                            v-for="tab in statusTabs"
                            :key="tab.id"
                            type="button"
                            @click="selectTab(tab.id)"
                            class="px-3.5 py-2.5 text-sm font-semibold border-b-2 transition-colors cursor-pointer whitespace-nowrap"
                            :class="
                                activeStatus === tab.id
                                    ? 'border-[#853953] text-[#853953] bg-white/80'
                                    : 'border-transparent text-stone-600 hover:text-stone-900 hover:border-stone-300'
                            "
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 px-3.5 sm:px-4 py-2.5 border-b border-stone-200 bg-white">
                    <div class="relative flex-1 min-w-0 sm:max-w-md">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-stone-400 pointer-events-none" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Cari order, resi, penerima, toko, ekspedisi, alamat..."
                            class="pl-9 pr-8 text-sm h-9 rounded-md border-stone-200 w-full"
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            @click="searchQuery = ''"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-700 cursor-pointer"
                            aria-label="Bersihkan pencarian"
                        >
                            <X class="size-4" />
                        </button>
                    </div>

                    <div class="flex items-center gap-2 min-w-0 justify-between sm:justify-end">
                        <DatePeriodFilter
                            :preset="datePreset"
                            :date-from="dateFrom"
                            :date-to="dateTo"
                            align="start"
                            button-class="flex-1 sm:flex-none sm:min-w-44"
                            aria-label="Filter tanggal pengiriman"
                            @change="handleDateChange"
                        />

                        <template v-if="selectedIds.length > 0">
                            <div class="flex items-center gap-2 px-3 py-1 rounded-md bg-[#853953]/10 border border-[#853953]/25 shrink-0">
                                <span class="text-sm font-bold text-[#853953] tabular-nums">
                                    {{ selectedIds.length }} dipilih
                                </span>
                                <span class="text-[#853953]/30">|</span>
                                <button
                                    type="button"
                                    @click="handleBatchPrint"
                                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#853953] hover:underline cursor-pointer"
                                >
                                    <Printer class="size-3.5" />
                                    <span>Cetak Resi</span>
                                </button>
                                <span class="text-[#853953]/30">|</span>
                                <button
                                    type="button"
                                    @click="isBulkDeleteModalOpen = true"
                                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-red-600 hover:text-red-700 cursor-pointer"
                                >
                                    <Trash2 class="size-3.5 text-red-600" />
                                    <span>Hapus</span>
                                </button>
                                <button
                                    type="button"
                                    @click="selectedIds = []"
                                    class="ml-1 text-stone-500 hover:text-stone-800 cursor-pointer"
                                    title="Batal pilih"
                                >
                                    <X class="size-4" />
                                </button>
                            </div>
                        </template>

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button
                                    type="button"
                                    class="hidden md:inline-flex h-9 items-center gap-1.5 px-3 text-sm font-semibold text-stone-700 rounded-md border border-stone-200 bg-white hover:bg-stone-50 hover:text-stone-900 transition-colors cursor-pointer shrink-0"
                                    aria-label="Atur tampilan kolom"
                                >
                                    <SlidersHorizontal class="size-4 text-stone-500" />
                                    <span>Kolom</span>
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-48 text-sm">
                                <DropdownMenuLabel class="text-sm font-semibold text-stone-700">Tampilkan Kolom</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuCheckboxItem :checked="columnVisibility.id" @update:checked="(val) => (columnVisibility.id = val)">
                                    ID
                                </DropdownMenuCheckboxItem>
                                <DropdownMenuCheckboxItem :checked="columnVisibility.store" @update:checked="(val) => (columnVisibility.store = val)">
                                    Toko
                                </DropdownMenuCheckboxItem>
                                <DropdownMenuCheckboxItem :checked="columnVisibility.recipient" @update:checked="(val) => (columnVisibility.recipient = val)">
                                    Penerima / Tujuan
                                </DropdownMenuCheckboxItem>
                                <DropdownMenuCheckboxItem :checked="columnVisibility.courier" @update:checked="(val) => (columnVisibility.courier = val)">
                                    Ekspedisi & Metode
                                </DropdownMenuCheckboxItem>
                                <DropdownMenuCheckboxItem :checked="columnVisibility.waybill" @update:checked="(val) => (columnVisibility.waybill = val)">
                                    Nomor Resi (AWB)
                                </DropdownMenuCheckboxItem>
                                <DropdownMenuCheckboxItem :checked="columnVisibility.weight" @update:checked="(val) => (columnVisibility.weight = val)">
                                    Berat Barang
                                </DropdownMenuCheckboxItem>
                                <DropdownMenuCheckboxItem :checked="columnVisibility.status" @update:checked="(val) => (columnVisibility.status = val)">
                                    Status
                                </DropdownMenuCheckboxItem>
                                <DropdownMenuCheckboxItem :checked="columnVisibility.amount" @update:checked="(val) => (columnVisibility.amount = val)">
                                    Total Biaya
                                </DropdownMenuCheckboxItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button
                                    type="button"
                                    class="inline-flex h-9 items-center gap-1.5 px-3 text-sm font-semibold text-stone-700 rounded-md border border-stone-200 bg-white hover:bg-stone-50 hover:text-stone-900 transition-colors cursor-pointer shrink-0"
                                    :disabled="isExportingAll"
                                >
                                    <Loader2 v-if="isExportingAll" class="size-4 text-[#853953] animate-spin" />
                                    <Download v-else class="size-4 text-stone-500" />
                                    <span>Unduh</span>
                                    <span v-if="selectedIds.length > 0" class="text-xs bg-[#853953]/10 text-[#853953] font-bold px-1.5 py-0.2 rounded-full">
                                        {{ selectedIds.length }}
                                    </span>
                                    <ChevronDown class="size-3.5 text-stone-400" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-48 text-sm">
                                <DropdownMenuItem @select="exportCurrentPage" class="cursor-pointer">
                                    Halaman Ini
                                </DropdownMenuItem>
                                <DropdownMenuItem @select="handleExportAll" class="cursor-pointer" :disabled="isExportingAll">
                                    <span v-if="isExportingAll" class="inline-flex items-center gap-2">
                                        <Loader2 class="size-3.5 animate-spin" />
                                        Mengunduh...
                                    </span>
                                    <span v-else>Semua</span>
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    v-if="selectedIds.length > 0"
                                    @select="exportSelected"
                                    class="cursor-pointer font-medium text-[#853953]"
                                >
                                    {{ selectedIds.length }} Terpilih
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                <div v-if="displayedShipments.length > 0">
                    <ShipmentsMobileList
                        :shipments="mobileShipments"
                        :total="shipments.total"
                        :has-more-pages="hasMorePages"
                        :is-loading-more="isLoadingMore"
                        @open-delete="openDeleteModal"
                        @load-more="loadMore"
                    />

                    <ShipmentsDesktopTable
                        :displayed-shipments="displayedShipments"
                        :selected-ids="selectedIds"
                        :is-all-selected="isAllSelected"
                        :is-some-selected="isSomeSelected"
                        :sort-field="sortField"
                        :sort-direction="sortDirection"
                        :column-visibility="columnVisibility"
                        :pagination="{
                            links: shipments.links,
                            from: shipments.from,
                            to: shipments.to,
                            total: shipments.total,
                        }"
                        @toggle-select-all="toggleSelectAll"
                        @toggle-select-row="toggleSelectRow"
                        @toggle-sort="toggleSort"
                        @open-delete="openDeleteModal"
                    />
                </div>

                <div v-else class="py-12 px-4 text-center">
                    <div class="mx-auto size-12 rounded-lg bg-[#853953]/10 text-[#853953] flex items-center justify-center mb-3">
                        <Truck class="size-6" />
                    </div>
                    <h3 class="text-base font-bold text-stone-900">
                        Belum Ada Data Pengiriman
                    </h3>
                    <p class="text-xs sm:text-sm text-stone-600 max-w-sm mx-auto mt-1 mb-4">
                        {{ searchQuery ? 'Tidak ada pesanan yang cocok dengan kata kunci pencarian Anda.' : 'Anda belum memiliki pesanan pengiriman paket.' }}
                    </p>
                </div>
            </div>

            <ShipmentDeleteDialogs
                v-model:single-open="isDeleteModalOpen"
                :single-order-number="shipmentToDelete?.order_number"
                :single-recipient-name="shipmentToDelete?.recipient_name"
                :is-deleting="isDeleting"
                v-model:bulk-open="isBulkDeleteModalOpen"
                :bulk-count="selectedIds.length"
                :is-bulk-deleting="isBulkDeleting"
                @confirm-single="confirmDeleteShipment"
                @confirm-bulk="confirmBulkDelete"
            />
        </div>
    </div>
</template>
