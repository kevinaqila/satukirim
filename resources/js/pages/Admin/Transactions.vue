<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    ChevronDown,
    Download,
    Loader2,
    Search,
    SlidersHorizontal,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AdminTransactionsTable, { type AdminTransactionSortField } from '@/components/admin/AdminTransactionsTable.vue';
import AppTopBar from '@/components/AppTopBar.vue';
import DatePeriodFilter from '@/components/DatePeriodFilter.vue';
import ShipmentDeleteDialogs from '@/components/shipments/ShipmentDeleteDialogs.vue';
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
import type { AdminTransactionItem } from '@/types/admin';

const props = defineProps<{
    transactions: {
        data: AdminTransactionItem[];
        current_page: number;
        last_page: number;
        total: number;
        from?: number | null;
        to?: number | null;
        links?: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: {
        status: string;
        search: string;
        date_preset?: string;
        date_from?: string;
        date_to?: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Super Admin',
                href: '/admin/users',
            },
            {
                title: 'Semua Pengiriman',
                href: '/admin/transactions',
            },
        ],
    },
});

const searchQuery = ref(props.filters.search || '');
const activeStatus = ref(props.filters.status || 'all');
const datePreset = ref(props.filters.date_preset || 'all');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const statusTabs = [
    { id: 'all', label: 'Semua Status' },
    { id: 'pending_payment', label: 'Perlu Dibayar' },
    { id: 'paid', label: 'Menunggu Pickup' },
    { id: 'in_transit', label: 'Diantar Kurir' },
    { id: 'delivered', label: 'Tiba di Tujuan' },
];

const applyFilters = () => {
    router.get(
        '/admin/transactions',
        {
            status: activeStatus.value,
            search: searchQuery.value || undefined,
            date_preset: datePreset.value !== 'all' ? datePreset.value : undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(searchQuery, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyFilters();
    }, 400);
});

const handleDateChange = (payload: { preset: string; dateFrom: string; dateTo: string }) => {
    datePreset.value = payload.preset;
    dateFrom.value = payload.dateFrom;
    dateTo.value = payload.dateTo;
    applyFilters();
};

const selectTab = (tabId: string) => {
    activeStatus.value = tabId;
    applyFilters();
};

const sortField = ref<AdminTransactionSortField | null>(null);
const sortDirection = ref<'asc' | 'desc'>('asc');

const toggleSort = (field: AdminTransactionSortField) => {
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
    merchant: true,
    recipient: true,
    courier: true,
    waybill: true,
    status: true,
    amount: true,
});

const displayedTransactions = computed(() => {
    const list = [...(props.transactions.data || [])];

    if (sortField.value) {
        const field = sortField.value;
        const dir = sortDirection.value === 'asc' ? 1 : -1;
        list.sort((a, b) => {
            if (field === 'id') return (a.id - b.id) * dir;
            if (field === 'order_number') return a.order_number.localeCompare(b.order_number) * dir;
            if (field === 'merchant') return (a.store?.name || '').localeCompare(b.store?.name || '') * dir;
            if (field === 'recipient') return a.recipient_name.localeCompare(b.recipient_name) * dir;
            if (field === 'courier') return (a.courier_service_name || '').localeCompare(b.courier_service_name || '') * dir;
            if (field === 'total_amount') return (a.total_amount - b.total_amount) * dir;
            if (field === 'status') return a.status.localeCompare(b.status) * dir;
            return 0;
        });
    }

    return list;
});

const selectedIds = ref<number[]>([]);

const isAllSelected = computed(() => {
    if (displayedTransactions.value.length === 0) return false;
    return displayedTransactions.value.every((item) => selectedIds.value.includes(item.id));
});

const isSomeSelected = computed(() => {
    return displayedTransactions.value.some((item) => selectedIds.value.includes(item.id)) && !isAllSelected.value;
});

const toggleSelectAll = (checked: boolean | 'indeterminate') => {
    selectedIds.value = checked === true ? displayedTransactions.value.map((item) => item.id) : [];
};

const toggleSelectRow = (id: number) => {
    const idx = selectedIds.value.indexOf(id);
    if (idx >= 0) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
};

const isBulkDeleteOpen = ref(false);
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
                selectedIds.value = [];
                isBulkDeleteOpen.value = false;
            },
            onFinish: () => {
                isBulkDeleting.value = false;
            },
        },
    );
};

const isDeleteModalOpen = ref(false);
const shipmentToDelete = ref<AdminTransactionItem | null>(null);
const isDeleting = ref(false);

const openDeleteModal = (item: AdminTransactionItem) => {
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
            isDeleteModalOpen.value = false;
            shipmentToDelete.value = null;
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const triggerExcelExport = (
    targetData: AdminTransactionItem[],
    subtitleSuffix = '',
) => {
    if (targetData.length === 0) return;

    exportToExcel<AdminTransactionItem>({
        filename: `Data_Pengiriman_Admin_${new Date().toISOString().slice(0, 10)}`,
        title: 'SatuKirim - Laporan Semua Pengiriman (Admin)',
        subtitle: `Dibuat pada ${new Date().toLocaleString('id-ID')} | Total: ${targetData.length} data${subtitleSuffix ? ` (${subtitleSuffix})` : ''}`,
        columns: [
            { header: 'No', align: 'center', type: 'number', width: 6, formatter: (_, idx) => idx + 1 },
            { header: 'ID', align: 'center', type: 'number', width: 8, formatter: (item) => item.id },
            { header: 'Kode Transaksi', align: 'left', type: 'text', key: 'order_number' },
            { header: 'Tanggal Transaksi', align: 'center', type: 'text', formatter: (item) => formatDateTime(item.created_at) },
            { header: 'Merchant', align: 'left', type: 'text', formatter: (item) => item.user?.name || '-' },
            { header: 'Email Merchant', align: 'left', type: 'text', formatter: (item) => item.user?.email || '-' },
            { header: 'Toko', align: 'left', type: 'text', formatter: (item) => item.store?.name || '-' },
            { header: 'Nama Penerima', align: 'left', type: 'text', key: 'recipient_name' },
            { header: 'No. HP Penerima', align: 'left', type: 'text', formatter: (item) => item.recipient_phone ? String(item.recipient_phone).trim() : '-' },
            { header: 'Tujuan', align: 'left', type: 'text', formatter: (item) => item.destination_area_name || item.destination_address || '-' },
            { header: 'Ekspedisi', align: 'left', type: 'text', formatter: (item) => `${item.courier_code ? item.courier_code.toUpperCase() + ' - ' : ''}${item.courier_service_name}` },
            { header: 'Metode Bayar', align: 'center', type: 'text', formatter: (item) => item.payment_method === 'cod' ? 'COD (Bayar di Tempat)' : 'Non-COD (Online)' },
            { header: 'No. Resi (AWB)', align: 'left', type: 'text', formatter: (item) => item.waybill_number || '-' },
            { header: 'Berat (gr)', align: 'right', type: 'number', formatter: (item) => item.weight || 1000 },
            { header: 'Ongkir (Rp)', align: 'right', type: 'currency', formatter: (item) => Number(item.shipping_fee || 0).toLocaleString('id-ID') },
            { header: 'Total Biaya (Rp)', align: 'right', type: 'currency', formatter: (item) => Number(item.total_amount || 0).toLocaleString('id-ID') },
            { header: 'Status', align: 'center', type: 'text', formatter: (item) => getShipmentStatus(item.status).label },
        ],
        data: targetData,
    });
};

const handleExportSelected = () => {
    const targetData = displayedTransactions.value.filter((item) => selectedIds.value.includes(item.id));
    if (targetData.length === 0) return;
    triggerExcelExport(targetData, `${targetData.length} Baris Terpilih`);
};

const handleExportCurrentPage = () => {
    triggerExcelExport(displayedTransactions.value, `Halaman ${props.transactions.current_page}`);
};

const isExportingAll = ref(false);

const handleExportAllFiltered = async () => {
    if (isExportingAll.value) return;
    isExportingAll.value = true;
    try {
        const response = await fetch('/admin/shipments/export-all?no_filter=1', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Gagal mengunduh data: ${response.statusText}`);
        }

        const result = await response.json();
        const rows: AdminTransactionItem[] = Array.isArray(result)
            ? result
            : (result.transactions || result.data || []);

        if (rows.length === 0) {
            alert('Tidak ada data untuk diekspor.');
            return;
        }

        triggerExcelExport(rows, 'Semua Data');
    } catch (err) {
        console.error('Error saat ekspor semua data:', err);
        handleExportCurrentPage();
    } finally {
        isExportingAll.value = false;
    }
};
</script>

<template>
    <Head title="Semua Pengiriman - Super Admin SatuKirim" />

    <AppTopBar title="Semua Pengiriman" />

    <div class="flex h-full flex-1 flex-col gap-3.5 p-3 sm:p-4 lg:p-5 w-full">
        <div class="rounded-none border border-stone-200 bg-white overflow-hidden shadow-2xs">
            <div class="flex flex-wrap items-center justify-between gap-2 px-3.5 sm:px-4 border-b border-stone-200 bg-stone-50/60">
                <div class="flex items-center gap-1 overflow-x-auto -mb-px">
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
                        placeholder="Cari order, resi, nama merchant, atau penerima..."
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
                        aria-label="Filter periode transaksi"
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
                                @click="isBulkDeleteOpen = true"
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
                            <DropdownMenuCheckboxItem :checked="columnVisibility.merchant" @update:checked="(val) => (columnVisibility.merchant = val)">
                                Toko / Merchant
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
                                aria-label="Menu Ekspor Data"
                            >
                                <Loader2 v-if="isExportingAll" class="size-4 text-stone-500 animate-spin" />
                                <Download v-else class="size-4 text-stone-500" />
                                <span>Unduh</span>
                                <span
                                    v-if="selectedIds.length > 0"
                                    class="text-xs bg-[#853953]/10 text-[#853953] font-bold px-1.5 py-0.2 rounded-full tabular-nums"
                                >
                                    {{ selectedIds.length }}
                                </span>
                                <ChevronDown class="size-3.5 text-stone-400" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-48 text-sm">
                            <DropdownMenuItem class="text-sm cursor-pointer" @select="handleExportCurrentPage">
                                Halaman Ini
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                class="text-sm cursor-pointer flex items-center justify-between"
                                :disabled="isExportingAll"
                                @select="handleExportAllFiltered"
                            >
                                <span>Semua</span>
                                <Loader2 v-if="isExportingAll" class="size-3.5 animate-spin text-stone-400" />
                            </DropdownMenuItem>
                            <template v-if="selectedIds.length > 0">
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    class="text-sm font-semibold text-[#853953] cursor-pointer"
                                    @select="handleExportSelected"
                                >
                                    {{ selectedIds.length }} Terpilih
                                </DropdownMenuItem>
                            </template>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <AdminTransactionsTable
                v-if="displayedTransactions && displayedTransactions.length > 0"
                :displayed-transactions="displayedTransactions"
                :selected-ids="selectedIds"
                :is-all-selected="isAllSelected"
                :is-some-selected="isSomeSelected"
                :sort-field="sortField"
                :sort-direction="sortDirection"
                :column-visibility="columnVisibility"
                :pagination="{
                    links: transactions.links,
                    from: transactions.from,
                    to: transactions.to,
                    total: transactions.total,
                }"
                @toggle-select-all="toggleSelectAll"
                @toggle-select-row="toggleSelectRow"
                @toggle-sort="toggleSort"
                @open-delete="openDeleteModal"
            />

            <div v-else class="py-12 text-center text-stone-500 text-xs">
                Tidak ada data pengiriman yang sesuai filter.
            </div>
        </div>

        <ShipmentDeleteDialogs
            v-model:single-open="isDeleteModalOpen"
            :single-order-number="shipmentToDelete?.order_number"
            :single-recipient-name="shipmentToDelete?.recipient_name"
            :is-deleting="isDeleting"
            v-model:bulk-open="isBulkDeleteOpen"
            :bulk-count="selectedIds.length"
            :is-bulk-deleting="isBulkDeleting"
            @confirm-single="confirmDeleteShipment"
            @confirm-bulk="confirmBulkDelete"
        />
    </div>
</template>
