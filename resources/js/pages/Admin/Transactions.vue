<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    CalendarDays,
    Check,
    ChevronDown,
    Copy,
    Download,
    Eye,
    Loader2,
    Search,
    SlidersHorizontal,
    Trash2,
    Truck,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AppTopBar from '@/components/AppTopBar.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TablePagination,
    TableRow,
} from '@/components/ui/table';
import {
    excerptText,
    formatDateTime,
    formatShortDestination,
    formatWeight,
    getCourierLogo,
    getShipmentStatus,
} from '@/lib/status';
import { exportToExcel } from '@/lib/exportExcel';

export interface AdminTransactionItem {
    id: number;
    order_number: string;
    status: string;
    recipient_name: string;
    recipient_phone?: string;
    destination_address?: string;
    destination_area_name?: string | null;
    weight?: number;
    courier_code?: string;
    courier_service_name: string;
    shipping_fee: number;
    total_amount: number;
    waybill_number: string | null;
    created_at: string;
    user: {
        id: number;
        name: string;
        email: string;
    };
    store: {
        id: number;
        name: string;
    };
}

const props = defineProps<{
    transactions: {
        data: AdminTransactionItem[];
        current_page: number;
        last_page: number;
        total: number;
        from?: number;
        to?: number;
        links?: any[];
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

const datePresets = [
    { id: 'all', label: 'Periode' },
    { id: 'today', label: 'Hari Ini' },
    { id: '7d', label: '7 Hari Terakhir' },
    { id: '30d', label: '30 Hari Terakhir' },
    { id: 'this_month', label: 'Bulan Ini' },
    { id: 'last_month', label: 'Bulan Lalu' },
];

const toLocalIso = (date: Date): string => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

const datePreset = ref(props.filters.date_preset || 'all');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');
const customFrom = ref(dateFrom.value);
const customTo = ref(dateTo.value);

const selectDatePreset = (id: string) => {
    const today = new Date();
    let from = '';
    let to = '';

    if (id === 'today') {
        from = to = toLocalIso(today);
    } else if (id === '7d' || id === '30d') {
        const start = new Date(today);
        start.setDate(today.getDate() - (id === '7d' ? 6 : 29));
        from = toLocalIso(start);
        to = toLocalIso(today);
    } else if (id === 'this_month') {
        from = toLocalIso(new Date(today.getFullYear(), today.getMonth(), 1));
        to = toLocalIso(today);
    } else if (id === 'last_month') {
        from = toLocalIso(new Date(today.getFullYear(), today.getMonth() - 1, 1));
        to = toLocalIso(new Date(today.getFullYear(), today.getMonth(), 0));
    }

    datePreset.value = id;
    dateFrom.value = from;
    dateTo.value = to;
    customFrom.value = from;
    customTo.value = to;
    applyFilters();
};

const applyCustomDate = () => {
    if (!customFrom.value && !customTo.value) return;
    datePreset.value = 'custom';
    dateFrom.value = customFrom.value;
    dateTo.value = customTo.value;
    applyFilters();
};

const formatShortDate = (iso: string): string => {
    if (!iso) return '…';
    const d = new Date(`${iso}T00:00:00`);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    return `${day}/${month}`;
};

const activeDateLabel = computed(() => {
    if (datePreset.value === 'custom') {
        return `${formatShortDate(dateFrom.value)} – ${formatShortDate(dateTo.value)}`;
    }
    return datePresets.find((preset) => preset.id === datePreset.value)?.label ?? 'Periode';
});

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

let searchTimer: any = null;
watch(searchQuery, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyFilters();
    }, 400);
});

const selectTab = (tabId: string) => {
    activeStatus.value = tabId;
    applyFilters();
};

type SortField = 'id' | 'order_number' | 'merchant' | 'recipient' | 'courier' | 'total_amount' | 'status';
const sortField = ref<SortField | null>(null);
const sortDirection = ref<'asc' | 'desc'>('asc');

const toggleSort = (field: SortField) => {
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
    amount: true,
    status: true,
});

const displayedTransactions = computed(() => {
    if (!props.transactions.data) return [];
    const list = [...props.transactions.data];

    if (sortField.value) {
        const field = sortField.value;
        const dir = sortDirection.value === 'asc' ? 1 : -1;
        list.sort((a, b) => {
            if (field === 'id') return (a.id - b.id) * dir;
            if (field === 'order_number') {
                return a.order_number.localeCompare(b.order_number) * dir;
            }
            if (field === 'merchant') {
                const nameA = a.user?.name || '';
                const nameB = b.user?.name || '';
                return nameA.localeCompare(nameB) * dir;
            }
            if (field === 'recipient') {
                return a.recipient_name.localeCompare(b.recipient_name) * dir;
            }
            if (field === 'courier') {
                return (a.courier_service_name || '').localeCompare(b.courier_service_name || '') * dir;
            }
            if (field === 'total_amount') {
                return (a.total_amount - b.total_amount) * dir;
            }
            if (field === 'status') {
                return a.status.localeCompare(b.status) * dir;
            }
            return 0;
        });
    }

    return list;
});

const selectedIds = ref<number[]>([]);
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

const isAllSelected = computed(() => {
    if (displayedTransactions.value.length === 0) return false;
    return displayedTransactions.value.every((item) => selectedIds.value.includes(item.id));
});

const isSomeSelected = computed(() => {
    return displayedTransactions.value.some((item) => selectedIds.value.includes(item.id)) && !isAllSelected.value;
});

const toggleSelectAll = (checked: boolean | 'indeterminate') => {
    if (checked === true) {
        selectedIds.value = displayedTransactions.value.map((item) => item.id);
    } else {
        selectedIds.value = [];
    }
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
    title: string,
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
            { header: 'No. Resi (AWB)', align: 'left', type: 'text', formatter: (item) => item.waybill_number || '-' },
            { header: 'Berat (gr)', align: 'right', type: 'number', formatter: (item) => item.weight || 1000 },
            { header: 'Ongkir (Rp)', align: 'right', type: 'currency', formatter: (item) => Number(item.shipping_fee || 0).toLocaleString('id-ID') },
            { header: 'Total Biaya (Rp)', align: 'right', type: 'currency', formatter: (item) => Number(item.total_amount || 0).toLocaleString('id-ID') },
            { header: 'Status', align: 'center', type: 'text', formatter: (item) => getShipmentStatus(item.status).label },
        ],
        data: targetData,
    });
};

const handleExportExcel = (selectedOnly = false) => {
    const isSelectedOnly = selectedOnly || selectedIds.value.length > 0;
    const targetData = isSelectedOnly
        ? displayedTransactions.value.filter((item) => selectedIds.value.includes(item.id))
        : displayedTransactions.value;

    if (targetData.length === 0) return;

    triggerExcelExport(
        targetData,
        'SatuKirim - Laporan Semua Pengiriman (Admin)',
        isSelectedOnly ? `${targetData.length} Baris Terpilih` : `Halaman ${props.transactions.current_page}`,
    );
};

const handleExportCurrentPage = () => {
    triggerExcelExport(
        displayedTransactions.value,
        'SatuKirim - Laporan Semua Pengiriman (Admin)',
        `Halaman ${props.transactions.current_page}`,
    );
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

        triggerExcelExport(
            rows,
            'SatuKirim - Laporan Semua Pengiriman (Admin)',
            'Semua Data',
        );
    } catch (err) {
        console.error('Error saat ekspor semua data:', err);
        triggerExcelExport(
            displayedTransactions.value,
            'SatuKirim - Laporan Semua Pengiriman (Admin)',
            `Halaman ${props.transactions.current_page}`,
        );
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
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="flex h-9 items-center justify-between gap-2 rounded-md border bg-white px-3 text-sm font-semibold cursor-pointer flex-1 sm:flex-none sm:min-w-44 shrink-0"
                                :class="datePreset === 'all' ? 'border-stone-200 text-stone-700' : 'border-[#853953]/40 text-[#853953]'"
                                aria-label="Filter periode transaksi"
                            >
                                <span class="flex items-center gap-2 min-w-0">
                                    <CalendarDays class="size-4 shrink-0 text-stone-400" />
                                    <span class="truncate">{{ activeDateLabel }}</span>
                                </span>
                                <ChevronDown class="size-4 shrink-0 text-stone-400" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start" class="w-64 max-w-[calc(100vw-1.5rem)]">
                            <DropdownMenuItem
                                v-for="preset in datePresets"
                                :key="preset.id"
                                class="justify-between text-sm"
                                :class="datePreset === preset.id ? 'text-[#853953] font-bold' : ''"
                                @select="selectDatePreset(preset.id)"
                            >
                                {{ preset.label }}
                                <Check v-if="datePreset === preset.id" class="size-4" />
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <div class="space-y-2 p-2" @keydown.stop>
                                <p class="text-xs font-semibold text-stone-500">Rentang kustom</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="space-y-1 text-xs text-stone-500">
                                        Dari
                                        <Input v-model="customFrom" type="date" :max="customTo || undefined" class="h-9 text-xs px-2" />
                                    </label>
                                    <label class="space-y-1 text-xs text-stone-500">
                                        Sampai
                                        <Input v-model="customTo" type="date" :min="customFrom || undefined" class="h-9 text-xs px-2" />
                                    </label>
                                </div>
                                <Button
                                    type="button"
                                    size="sm"
                                    class="w-full h-9 bg-[#853953] hover:bg-[#6c2e43] text-white text-sm font-semibold cursor-pointer"
                                    :disabled="!customFrom && !customTo"
                                    @click="applyCustomDate"
                                >
                                    Terapkan
                                </Button>
                            </div>
                        </DropdownMenuContent>
                    </DropdownMenu>

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
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.id"
                                @update:checked="(val) => (columnVisibility.id = val)"
                            >
                                ID
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.merchant"
                                @update:checked="(val) => (columnVisibility.merchant = val)"
                            >
                                Toko / Merchant
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.recipient"
                                @update:checked="(val) => (columnVisibility.recipient = val)"
                            >
                                Penerima / Tujuan
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.courier"
                                @update:checked="(val) => (columnVisibility.courier = val)"
                            >
                                Ekspedisi
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.waybill"
                                @update:checked="(val) => (columnVisibility.waybill = val)"
                            >
                                Nomor Resi (AWB)
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.status"
                                @update:checked="(val) => (columnVisibility.status = val)"
                            >
                                Status
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.amount"
                                @update:checked="(val) => (columnVisibility.amount = val)"
                            >
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
                            <DropdownMenuItem
                                class="text-sm cursor-pointer"
                                @select="handleExportCurrentPage"
                            >
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
                                    @select="handleExportExcel(true)"
                                >
                                    {{ selectedIds.length }} Terpilih
                                </DropdownMenuItem>
                            </template>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <div v-if="displayedTransactions && displayedTransactions.length > 0">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent border-b border-stone-200">
                            <TableHead class="w-10 pl-4 pr-2">
                                <Checkbox
                                    :checked="isAllSelected ? true : (isSomeSelected ? 'indeterminate' : false)"
                                    @update:checked="toggleSelectAll"
                                    aria-label="Pilih semua pengiriman"
                                />
                            </TableHead>

                            <TableHead v-if="columnVisibility.id" class="w-16">
                                <button
                                    type="button"
                                    @click="toggleSort('id')"
                                    class="group/th inline-flex items-center gap-1.5 text-sm font-semibold text-stone-800 hover:text-[#853953] cursor-pointer select-none"
                                >
                                    <span>ID</span>
                                    <ArrowUp v-if="sortField === 'id' && sortDirection === 'asc'" class="size-3.5 text-[#853953]" />
                                    <ArrowDown v-else-if="sortField === 'id' && sortDirection === 'desc'" class="size-3.5 text-[#853953]" />
                                    <ArrowUpDown v-else class="size-3 text-stone-300 group-hover/th:text-stone-500 transition-colors" />
                                </button>
                            </TableHead>

                            <TableHead>
                                <button
                                    type="button"
                                    @click="toggleSort('order_number')"
                                    class="group/th inline-flex items-center gap-2 text-left cursor-pointer select-none"
                                >
                                    <div class="flex flex-col leading-tight">
                                        <span class="text-sm font-semibold text-stone-800 group-hover/th:text-[#853953] transition-colors">
                                            Kode Transaksi
                                        </span>
                                        <span class="text-sm font-semibold text-stone-800 group-hover/th:text-[#853953] transition-colors mt-0.5">
                                            Tanggal Transaksi
                                        </span>
                                    </div>
                                    <ArrowUp v-if="sortField === 'order_number' && sortDirection === 'asc'" class="size-3.5 text-[#853953]" />
                                    <ArrowDown v-else-if="sortField === 'order_number' && sortDirection === 'desc'" class="size-3.5 text-[#853953]" />
                                    <ArrowUpDown v-else class="size-3.5 text-stone-300 group-hover/th:text-stone-500 transition-colors" />
                                </button>
                            </TableHead>

                            <TableHead v-if="columnVisibility.merchant">
                                <button
                                    type="button"
                                    @click="toggleSort('merchant')"
                                    class="group/th inline-flex items-center gap-2 text-left cursor-pointer select-none"
                                >
                                    <div class="flex flex-col leading-tight">
                                        <span class="text-sm font-semibold text-stone-800 group-hover/th:text-[#853953] transition-colors">
                                            Toko
                                        </span>
                                        <span class="text-sm font-semibold text-stone-800 group-hover/th:text-[#853953] transition-colors mt-0.5">
                                            Merchant
                                        </span>
                                    </div>
                                    <ArrowUp v-if="sortField === 'merchant' && sortDirection === 'asc'" class="size-3.5 text-[#853953]" />
                                    <ArrowDown v-else-if="sortField === 'merchant' && sortDirection === 'desc'" class="size-3.5 text-[#853953]" />
                                    <ArrowUpDown v-else class="size-3.5 text-stone-300 group-hover/th:text-stone-500 transition-colors" />
                                </button>
                            </TableHead>

                            <TableHead v-if="columnVisibility.recipient">
                                <button
                                    type="button"
                                    @click="toggleSort('recipient')"
                                    class="group/th inline-flex items-center gap-2 text-left cursor-pointer select-none"
                                >
                                    <div class="flex flex-col leading-tight">
                                        <span class="text-sm font-semibold text-stone-800 group-hover/th:text-[#853953] transition-colors">
                                            Penerima
                                        </span>
                                        <span class="text-sm font-semibold text-stone-800 group-hover/th:text-[#853953] transition-colors mt-0.5">
                                            Tujuan
                                        </span>
                                    </div>
                                    <ArrowUp v-if="sortField === 'recipient' && sortDirection === 'asc'" class="size-3.5 text-[#853953]" />
                                    <ArrowDown v-else-if="sortField === 'recipient' && sortDirection === 'desc'" class="size-3.5 text-[#853953]" />
                                    <ArrowUpDown v-else class="size-3.5 text-stone-300 group-hover/th:text-stone-500 transition-colors" />
                                </button>
                            </TableHead>

                            <TableHead v-if="columnVisibility.courier">
                                <button
                                    type="button"
                                    @click="toggleSort('courier')"
                                    class="group/th inline-flex items-center gap-1.5 text-sm font-semibold text-stone-800 hover:text-[#853953] transition-colors cursor-pointer select-none"
                                >
                                    <span>Ekspedisi</span>
                                    <ArrowUp v-if="sortField === 'courier' && sortDirection === 'asc'" class="size-3.5 text-[#853953]" />
                                    <ArrowDown v-else-if="sortField === 'courier' && sortDirection === 'desc'" class="size-3.5 text-[#853953]" />
                                    <ArrowUpDown v-else class="size-3.5 text-stone-300 group-hover/th:text-stone-500 transition-colors" />
                                </button>
                            </TableHead>

                            <TableHead v-if="columnVisibility.waybill">
                                <span class="text-sm font-semibold text-stone-800">No. Resi (AWB)</span>
                            </TableHead>

                            <TableHead>
                                <span class="text-sm font-semibold text-stone-800">Berat</span>
                            </TableHead>

                            <TableHead v-if="columnVisibility.status" class="text-center">
                                <button
                                    type="button"
                                    @click="toggleSort('status')"
                                    class="group/th inline-flex items-center justify-center gap-1.5 text-sm font-semibold text-stone-800 hover:text-[#853953] transition-colors cursor-pointer select-none mx-auto"
                                >
                                    <span>Status</span>
                                    <ArrowUp v-if="sortField === 'status' && sortDirection === 'asc'" class="size-3.5 text-[#853953]" />
                                    <ArrowDown v-else-if="sortField === 'status' && sortDirection === 'desc'" class="size-3.5 text-[#853953]" />
                                    <ArrowUpDown v-else class="size-3.5 text-stone-300 group-hover/th:text-stone-500 transition-colors" />
                                </button>
                            </TableHead>

                            <TableHead v-if="columnVisibility.amount" class="text-right">
                                <button
                                    type="button"
                                    @click="toggleSort('total_amount')"
                                    class="group/th inline-flex items-center gap-1.5 text-sm font-semibold text-stone-800 hover:text-[#853953] transition-colors cursor-pointer select-none ml-auto"
                                >
                                    <span>Total Biaya</span>
                                    <ArrowUp v-if="sortField === 'total_amount' && sortDirection === 'asc'" class="size-3.5 text-[#853953]" />
                                    <ArrowDown v-else-if="sortField === 'total_amount' && sortDirection === 'desc'" class="size-3.5 text-[#853953]" />
                                    <ArrowUpDown v-else class="size-3.5 text-stone-300 group-hover/th:text-stone-500 transition-colors" />
                                </button>
                            </TableHead>

                            <TableHead class="w-20 pr-4 text-center">
                                <span class="text-sm font-semibold text-stone-800">Aksi</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow
                            v-for="item in displayedTransactions"
                            :key="item.id"
                            :data-state="selectedIds.includes(item.id) ? 'selected' : undefined"
                            class="group"
                        >
                            <TableCell class="pl-4 pr-2">
                                <Checkbox
                                    :checked="selectedIds.includes(item.id)"
                                    @update:checked="() => toggleSelectRow(item.id)"
                                    :aria-label="`Pilih transaksi ${item.order_number}`"
                                />
                            </TableCell>

                            <TableCell v-if="columnVisibility.id" class="text-sm font-mono font-medium text-stone-500 tabular-nums whitespace-nowrap">
                                #{{ item.id }}
                            </TableCell>

                            <TableCell>
                                <div class="inline-flex items-center gap-1.5 leading-tight">
                                    <Link
                                        :href="`/shipments/${item.id}`"
                                        class="font-bold text-sm text-primary hover:underline transition-colors"
                                    >
                                        {{ item.order_number }}
                                    </Link>
                                    <button
                                        type="button"
                                        @click="copyOrderNumber(item.id, item.order_number)"
                                        class="text-stone-400 hover:text-primary transition-colors cursor-pointer"
                                        title="Salin Kode Transaksi"
                                    >
                                        <Check v-if="copiedOrderId === item.id" class="size-3.5 text-emerald-600" />
                                        <Copy v-else class="size-3.5" />
                                    </button>
                                </div>
                                <div class="text-xs text-stone-500 mt-0.5 tabular-nums whitespace-nowrap">
                                    {{ formatDateTime(item.created_at) }}
                                </div>
                            </TableCell>

                            <TableCell v-if="columnVisibility.merchant">
                                <div
                                    class="font-semibold text-sm text-stone-900 truncate max-w-[110px] leading-tight"
                                    :title="item.store?.name || 'Toko'"
                                >
                                    {{ excerptText(item.store?.name || 'Toko', 14) }}
                                </div>
                                <div
                                    class="text-xs text-stone-500 mt-0.5 truncate max-w-[110px]"
                                    :title="item.user?.name || 'Merchant'"
                                >
                                    {{ excerptText(item.user?.name || 'Merchant', 16) }}
                                </div>
                            </TableCell>

                            <TableCell v-if="columnVisibility.recipient">
                                <div
                                    class="font-semibold text-sm text-stone-900 truncate max-w-[140px] leading-tight"
                                    :title="item.recipient_name"
                                >
                                    {{ excerptText(item.recipient_name, 16) }}
                                </div>
                                <div
                                    class="text-xs text-stone-500 mt-0.5 truncate max-w-[140px]"
                                    :title="item.destination_area_name || item.destination_address || '-'"
                                >
                                    {{ excerptText(formatShortDestination(item.destination_area_name, item.destination_address), 18) }}
                                </div>
                            </TableCell>

                            <TableCell v-if="columnVisibility.courier">
                                <div class="flex items-center gap-2.5">
                                    <div class="h-6 w-11 bg-white rounded border border-stone-200/80 p-0.5 flex items-center justify-center shrink-0">
                                        <img
                                            v-if="getCourierLogo(item.courier_code || item.courier_service_name)"
                                            :src="getCourierLogo(item.courier_code || item.courier_service_name)!"
                                            :alt="item.courier_service_name"
                                            class="max-h-4 max-w-full object-contain"
                                        />
                                        <span v-else class="text-xs font-bold text-stone-600 uppercase">
                                             {{ item.courier_service_name?.slice(0, 3) }}
                                        </span>
                                    </div>
                                    <span
                                        class="text-sm font-semibold text-stone-800 truncate max-w-[115px]"
                                        :title="item.courier_service_name"
                                    >
                                        {{ excerptText(item.courier_service_name, 14) }}
                                    </span>
                                </div>
                            </TableCell>

                            <TableCell v-if="columnVisibility.waybill">
                                <div v-if="item.waybill_number" class="inline-flex items-center gap-1.5">
                                    <span class="text-sm font-bold text-primary">
                                        {{ item.waybill_number }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="copyWaybill(item.id, item.waybill_number)"
                                        class="text-stone-400 hover:text-primary transition-colors cursor-pointer"
                                        title="Salin nomor resi"
                                    >
                                        <Check v-if="copiedWaybillId === item.id" class="size-3.5 text-emerald-600" />
                                        <Copy v-else class="size-3.5" />
                                    </button>
                                </div>
                                <span v-else class="text-sm text-stone-400 select-none">—</span>
                            </TableCell>

                            <TableCell class="text-sm font-medium text-stone-800 tabular-nums">
                                {{ formatWeight(item.weight) }}
                            </TableCell>

                            <TableCell v-if="columnVisibility.status" class="text-center">
                                <span class="text-sm font-bold text-primary">
                                    {{ getShipmentStatus(item.status).label }}
                                </span>
                            </TableCell>

                            <TableCell v-if="columnVisibility.amount" class="text-right font-bold tabular-nums text-sm text-stone-900">
                                Rp {{ Number(item.total_amount).toLocaleString('id-ID') }}
                            </TableCell>

                            <TableCell class="pr-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <Button
                                        as-child
                                        variant="outline"
                                        size="icon-sm"
                                        class="size-8 rounded-md border border-stone-200 bg-white text-stone-600 hover:text-primary hover:border-primary/40 hover:bg-stone-50 shadow-2xs cursor-pointer transition-colors"
                                        title="Lihat Detail Transaksi"
                                    >
                                        <Link :href="`/shipments/${item.id}`">
                                            <Eye class="size-4" />
                                        </Link>
                                    </Button>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon-sm"
                                        class="size-8 rounded-md border border-stone-200 bg-white text-stone-400 hover:text-red-700 hover:border-red-300 hover:bg-red-50/70 shadow-2xs cursor-pointer transition-colors"
                                        title="Hapus Pengiriman"
                                        @click="openDeleteModal(item)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <TablePagination
                    :links="transactions.links"
                    :from="transactions.from"
                    :to="transactions.to"
                    :total="transactions.total"
                    :selected-count="selectedIds.length"
                />
            </div>

            <div v-else class="py-12 text-center text-stone-500 text-xs">
                Tidak ada data pengiriman yang sesuai filter.
            </div>
        </div>

        <Dialog :open="isBulkDeleteOpen" @update:open="(val) => { if (!isBulkDeleting) isBulkDeleteOpen = val; }">
            <DialogContent class="sm:max-w-md bg-white">
                <DialogHeader>
                    <DialogTitle class="text-base sm:text-lg font-bold text-stone-900">
                        Hapus {{ selectedIds.length }} Data Pengiriman Terpilih?
                    </DialogTitle>
                    <DialogDescription class="text-xs sm:text-sm text-stone-600 leading-relaxed pt-1">
                        Apakah Anda yakin ingin menghapus {{ selectedIds.length }} data pengiriman yang dipilih? Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="pt-3 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="isBulkDeleting"
                        @click="isBulkDeleteOpen = false"
                        class="h-9 sm:h-10 px-4 text-xs sm:text-sm font-semibold bg-white border-stone-200 text-stone-700 hover:bg-stone-50 cursor-pointer shadow-2xs"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        :disabled="isBulkDeleting"
                        @click="confirmBulkDelete"
                        class="h-9 sm:h-10 px-4 text-xs sm:text-sm font-semibold bg-red-700 hover:bg-red-800 text-white rounded-md shadow-xs cursor-pointer transition-colors"
                    >
                        <Loader2 v-if="isBulkDeleting" class="mr-2 size-4 animate-spin" />
                        Ya, Hapus {{ selectedIds.length }} Pengiriman
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog :open="isDeleteModalOpen" @update:open="(val) => { if (!isDeleting) isDeleteModalOpen = val; }">
            <DialogContent class="sm:max-w-md bg-white">
                <DialogHeader>
                    <DialogTitle class="text-base sm:text-lg font-bold text-stone-900">
                        Hapus Data Pengiriman?
                    </DialogTitle>
                    <DialogDescription class="text-xs sm:text-sm text-stone-600 leading-relaxed pt-1">
                        Apakah Anda yakin ingin menghapus data pengiriman
                        <span v-if="shipmentToDelete" class="font-bold text-stone-900">{{ shipmentToDelete.order_number }}</span>
                        untuk penerima
                        <span v-if="shipmentToDelete" class="font-semibold text-stone-800">{{ shipmentToDelete.recipient_name }}</span>?
                        Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="pt-3 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="isDeleting"
                        @click="isDeleteModalOpen = false"
                        class="h-9 sm:h-10 px-4 text-xs sm:text-sm font-semibold bg-white border-stone-200 text-stone-700 hover:bg-stone-50 cursor-pointer shadow-2xs"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        :disabled="isDeleting"
                        @click="confirmDeleteShipment"
                        class="h-9 sm:h-10 px-4 text-xs sm:text-sm font-semibold bg-red-700 hover:bg-red-800 text-white rounded-md shadow-xs cursor-pointer transition-colors"
                    >
                        <Loader2 v-if="isDeleting" class="mr-2 size-4 animate-spin" />
                        Ya, Hapus Pengiriman
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
