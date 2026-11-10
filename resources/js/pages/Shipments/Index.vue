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
    CreditCard,
    Download,
    Eye,
    Loader2,
    MoreVertical,
    Printer,
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
import { toast } from 'vue-sonner';

export interface ShipmentItem {
    id: number;
    order_number: string;
    status: string;
    recipient_name: string;
    recipient_phone: string;
    destination_address: string;
    destination_area_name: string | null;
    weight: number;
    courier_code: string;
    courier_service_name: string;
    shipping_fee: number;
    total_amount: number;
    payment_method?: string | null;
    item_value?: number;
    waybill_number: string | null;
    created_at: string;
    store: {
        id: number;
        name: string;
    };
}

const props = defineProps<{
    shipments: {
        data: ShipmentItem[];
        current_page: number;
        last_page: number;
        total: number;
        from?: number | null;
        to?: number | null;
        links?: any[];
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

let searchTimer: any = null;
watch(searchQuery, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyFilters();
    }, 400);
});

const selectCourier = (value: string) => {
    activeCourier.value = value;
    applyFilters();
};

const datePresets = [
    { id: 'all', label: 'Periode' },
    { id: 'today', label: 'Hari ini' },
    { id: '7d', label: '7 hari terakhir' },
    { id: '30d', label: '30 hari terakhir' },
    { id: 'this_month', label: 'Bulan ini' },
    { id: 'last_month', label: 'Bulan lalu' },
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

const formatShortDate = (iso: string): string =>
    iso ? new Date(`${iso}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) : '…';

const activeDateLabel = computed(() => {
    if (datePreset.value === 'custom') {
        return `${formatShortDate(dateFrom.value)} – ${formatShortDate(dateTo.value)}`;
    }
    return datePresets.find((preset) => preset.id === datePreset.value)?.label ?? 'Periode';
});

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

type SortField = 'id' | 'order_number' | 'store' | 'recipient' | 'courier' | 'weight' | 'total_amount' | 'status';
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
    store: true,
    recipient: true,
    courier: true,
    waybill: true,
    weight: true,
    amount: true,
    status: true,
});

const mobileShipments = ref<ShipmentItem[]>([]);
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
            if (field === 'order_number') {
                return a.order_number.localeCompare(b.order_number) * dir;
            }
            if (field === 'store') {
                return (a.store?.name || '').localeCompare(b.store?.name || '') * dir;
            }
            if (field === 'recipient') {
                return a.recipient_name.localeCompare(b.recipient_name) * dir;
            }
            if (field === 'courier') {
                return (a.courier_service_name || '').localeCompare(b.courier_service_name || '') * dir;
            }
            if (field === 'weight') {
                return ((a.weight || 0) - (b.weight || 0)) * dir;
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

const isAllSelected = computed(() => {
    if (displayedShipments.value.length === 0) return false;
    return displayedShipments.value.every((item) => selectedIds.value.includes(item.id));
});

const isSomeSelected = computed(() => {
    return displayedShipments.value.some((item) => selectedIds.value.includes(item.id)) && !isAllSelected.value;
});

const toggleSelectAll = (checked: boolean | 'indeterminate') => {
    if (checked === true) {
        selectedIds.value = displayedShipments.value.map((item) => item.id);
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

const handleBatchPrint = () => {
    if (selectedIds.value.length === 0) return;

    const allKnownShipments = [...(props.shipments.data || []), ...mobileShipments.value];
    const shipmentMap = new Map<number, ShipmentItem>();
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

const exportShipmentsToExcel = (targetData: ShipmentItem[], subtitleSuffix = '') => {
    if (targetData.length === 0) return;

    exportToExcel<ShipmentItem>({
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
            { header: 'Berat (gr)', align: 'right', type: 'number', formatter: (item) => item.weight || 1000 },
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

const handleExportExcel = () => {
    if (selectedIds.value.length > 0) {
        exportSelected();
    } else {
        exportCurrentPage();
    }
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
        const items: ShipmentItem[] = Array.isArray(result) ? result : (result.shipments || result.data || []);

        if (items.length === 0) {
            toast.warning('Tidak ada data pengiriman untuk diekspor.');
            return;
        }

        exportShipmentsToExcel(items, `Semua Data (${items.length} data)`);
    } catch (err: any) {
        toast.error(err.message || 'Terjadi kesalahan saat mengunduh seluruh data.');
    } finally {
        isExportingAll.value = false;
    }
};

const copyText = async (text: string): Promise<boolean> => {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return true;
        }
    } catch {
    }
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    let ok = false;
    try {
        ok = document.execCommand('copy');
    } catch {
        ok = false;
    }
    document.body.removeChild(textarea);
    return ok;
};

const copyOrderNumber = async (id: number, orderNumber: string) => {
    if (!(await copyText(orderNumber))) return;
    copiedOrderId.value = id;
    setTimeout(() => {
        if (copiedOrderId.value === id) copiedOrderId.value = null;
    }, 1800);
};

const copyWaybill = async (id: number, waybill: string) => {
    if (!(await copyText(waybill))) return;
    copiedWaybillId.value = id;
    setTimeout(() => {
        if (copiedWaybillId.value === id) copiedWaybillId.value = null;
    }, 1800);
};

const isDeleteModalOpen = ref(false);
const shipmentToDelete = ref<ShipmentItem | null>(null);
const isDeleting = ref(false);

const openDeleteModal = (item: ShipmentItem) => {
    shipmentToDelete.value = item;
    isDeleteModalOpen.value = true;
};

const confirmDeleteShipment = () => {
    if (!shipmentToDelete.value || isDeleting.value) {
        return;
    }

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

const openBulkDeleteModal = () => {
    if (selectedIds.value.length === 0) return;
    isBulkDeleteModalOpen.value = true;
};

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
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="flex h-9 items-center justify-between gap-2 rounded-md border bg-white px-3 text-sm font-semibold cursor-pointer flex-1 sm:flex-none sm:min-w-44 shrink-0"
                                :class="datePreset === 'all' ? 'border-stone-200 text-stone-700' : 'border-[#853953]/40 text-[#853953]'"
                                aria-label="Filter tanggal pengiriman"
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
                                @click="handleBatchPrint"
                                class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#853953] hover:underline cursor-pointer"
                            >
                                <Printer class="size-3.5" />
                                <span>Cetak Resi</span>
                            </button>
                            <span class="text-[#853953]/30">|</span>
                            <button
                                type="button"
                                @click="openBulkDeleteModal"
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
                                :checked="columnVisibility.store"
                                @update:checked="(val) => (columnVisibility.store = val)"
                            >
                                Toko
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
                                Ekspedisi & Metode
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.waybill"
                                @update:checked="(val) => (columnVisibility.waybill = val)"
                            >
                                Nomor Resi (AWB)
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.weight"
                                @update:checked="(val) => (columnVisibility.weight = val)"
                            >
                                Berat Barang
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
                            <DropdownMenuItem
                                @select="exportCurrentPage"
                                class="cursor-pointer"
                            >
                                Halaman Ini
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                @select="handleExportAll"
                                class="cursor-pointer"
                                :disabled="isExportingAll"
                            >
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
                <div class="md:hidden divide-y divide-stone-200">
                    <div
                        v-for="item in mobileShipments"
                        :key="`m-${item.id}`"
                        class="px-3.5 py-3.5 bg-white transition-colors"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <Link :href="`/shipments/${item.id}`" class="min-w-0 flex-1 group">
                                <div class="text-sm font-bold text-primary group-hover:underline">{{ item.order_number }}</div>
                                <div class="text-xs text-stone-500 mt-0.5">{{ formatDateTime(item.created_at) }}</div>
                            </Link>

                            <div class="flex items-center gap-1.5 shrink-0">
                                <span class="text-xs font-bold text-primary text-right">
                                    {{ getShipmentStatus(item.status).label }}
                                </span>

                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <button
                                            type="button"
                                            class="size-7 -mr-1 rounded-md text-stone-400 hover:text-stone-700 hover:bg-stone-100 flex items-center justify-center cursor-pointer transition-colors"
                                            aria-label="Menu opsi pengiriman"
                                        >
                                            <MoreVertical class="size-4" />
                                        </button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-48">
                                        <DropdownMenuItem v-if="item.status === 'pending_payment'" as-child>
                                            <Link :href="`/shipments/${item.id}?pay=1`" class="cursor-pointer text-xs font-medium">
                                                <CreditCard class="mr-2 size-4 text-[#853953]" />
                                                Bayar Ongkos Kirim
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem v-else as-child>
                                            <a :href="`/shipments/${item.id}?print=1`" target="_blank" rel="noopener noreferrer" class="cursor-pointer text-xs font-medium">
                                                <Printer class="mr-2 size-4 text-stone-600" />
                                                Cetak Label Resi
                                            </a>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="openDeleteModal(item)"
                                            class="text-red-700 focus:text-red-700 focus:bg-red-50 cursor-pointer text-xs font-medium"
                                        >
                                            <Trash2 class="mr-2 size-4 text-red-600" />
                                            Hapus Pengiriman
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>

                        <Link :href="`/shipments/${item.id}`" class="block group mt-2">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-stone-900 truncate">{{ item.recipient_name }}</div>
                                <div class="text-xs text-stone-500 truncate mt-0.5">
                                    {{ formatShortDestination(item.destination_area_name, item.destination_address) }}
                                </div>
                            </div>
                            <div class="mt-2.5 flex items-center justify-between gap-3 text-xs text-stone-500">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="truncate font-medium">
                                        {{ item.courier_service_name }} · {{ formatWeight(item.weight) }}
                                    </span>
                                    <span
                                        v-if="item.payment_method === 'cod'"
                                        class="shrink-0 px-1.5 py-0.2 rounded text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200/70 uppercase"
                                    >
                                        COD
                                    </span>
                                    <span
                                        v-else
                                        class="shrink-0 px-1.5 py-0.2 rounded text-[10px] font-semibold bg-stone-100 text-stone-600 border border-stone-200/60 uppercase"
                                    >
                                        Non-COD
                                    </span>
                                </div>
                                <span class="text-sm font-bold text-stone-900 tabular-nums shrink-0">
                                    Rp {{ Number(item.total_amount).toLocaleString('id-ID') }}
                                </span>
                            </div>
                        </Link>
                    </div>
                </div>

                <Table class="hidden md:table">
                    <TableHeader>
                        <TableRow class="hover:bg-transparent border-b border-stone-200">
                            <TableHead class="w-10 pl-4 pr-2">
                                <Checkbox
                                    :checked="isAllSelected ? true : isSomeSelected ? 'indeterminate' : false"
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

                            <TableHead v-if="columnVisibility.store">
                                <button
                                    type="button"
                                    @click="toggleSort('store')"
                                    class="group/th inline-flex items-center gap-1.5 text-sm font-semibold text-stone-800 hover:text-[#853953] cursor-pointer select-none"
                                >
                                    <span>Toko</span>
                                    <ArrowUp v-if="sortField === 'store' && sortDirection === 'asc'" class="size-3.5 text-[#853953]" />
                                    <ArrowDown v-else-if="sortField === 'store' && sortDirection === 'desc'" class="size-3.5 text-[#853953]" />
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
                                    class="group/th inline-flex items-center gap-2 text-left cursor-pointer select-none"
                                >
                                    <div class="flex flex-col leading-tight">
                                        <span class="text-sm font-semibold text-stone-800 group-hover/th:text-[#853953] transition-colors">
                                            Ekspedisi
                                        </span>
                                        <span class="text-sm font-semibold text-stone-800 group-hover/th:text-[#853953] transition-colors mt-0.5">
                                            Metode
                                        </span>
                                    </div>
                                    <ArrowUp v-if="sortField === 'courier' && sortDirection === 'asc'" class="size-3.5 text-[#853953]" />
                                    <ArrowDown v-else-if="sortField === 'courier' && sortDirection === 'desc'" class="size-3.5 text-[#853953]" />
                                    <ArrowUpDown v-else class="size-3.5 text-stone-300 group-hover/th:text-stone-500 transition-colors" />
                                </button>
                            </TableHead>

                            <TableHead v-if="columnVisibility.waybill">
                                <span class="text-sm font-semibold text-stone-800">No. Resi (AWB)</span>
                            </TableHead>

                            <TableHead v-if="columnVisibility.weight">
                                <button
                                    type="button"
                                    @click="toggleSort('weight')"
                                    class="group/th inline-flex items-center gap-1.5 text-sm font-semibold text-stone-800 hover:text-[#853953] cursor-pointer select-none"
                                >
                                    <span>Berat</span>
                                    <ArrowUp v-if="sortField === 'weight' && sortDirection === 'asc'" class="size-3.5 text-[#853953]" />
                                    <ArrowDown v-else-if="sortField === 'weight' && sortDirection === 'desc'" class="size-3.5 text-[#853953]" />
                                    <ArrowUpDown v-else class="size-3.5 text-stone-300 group-hover/th:text-stone-500 transition-colors" />
                                </button>
                            </TableHead>

                            <TableHead v-if="columnVisibility.status" class="text-center">
                                <button
                                    type="button"
                                    @click="toggleSort('status')"
                                    class="group/th inline-flex items-center justify-center gap-1.5 text-sm font-semibold text-stone-800 hover:text-[#853953] cursor-pointer select-none mx-auto"
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
                                    class="group/th inline-flex items-center gap-1.5 text-sm font-semibold text-stone-800 hover:text-[#853953] cursor-pointer select-none ml-auto"
                                >
                                    <span>Total Biaya</span>
                                    <ArrowUp v-if="sortField === 'total_amount' && sortDirection === 'asc'" class="size-3.5 text-[#853953]" />
                                    <ArrowDown v-else-if="sortField === 'total_amount' && sortDirection === 'desc'" class="size-3.5 text-[#853953]" />
                                    <ArrowUpDown v-else class="size-3.5 text-stone-300 group-hover/th:text-stone-500 transition-colors" />
                                </button>
                            </TableHead>

                            <TableHead class="w-32 pr-4 text-center">
                                <span class="text-sm font-semibold text-stone-800">Aksi</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow
                            v-for="item in displayedShipments"
                            :key="item.id"
                            :data-state="selectedIds.includes(item.id) ? 'selected' : undefined"
                            class="group"
                        >
                            <TableCell class="pl-4 pr-2">
                                <Checkbox
                                    :checked="selectedIds.includes(item.id)"
                                    @update:checked="() => toggleSelectRow(item.id)"
                                    :aria-label="`Pilih ${item.order_number}`"
                                />
                            </TableCell>

                            <TableCell v-if="columnVisibility.id" class="text-sm font-mono font-medium text-stone-500 tabular-nums whitespace-nowrap">
                                {{ item.id }}
                            </TableCell>

                            <TableCell>
                                <div class="inline-flex items-center gap-1.5 leading-tight">
                                    <Link
                                        :href="`/shipments/${item.id}`"
                                        class="text-sm font-bold text-primary hover:underline transition-colors"
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
                                <div class="text-xs text-stone-500 mt-0.5 whitespace-nowrap">
                                    {{ formatDateTime(item.created_at) }}
                                </div>
                            </TableCell>

                            <TableCell v-if="columnVisibility.store">
                                <span
                                    class="text-sm font-medium text-stone-800 block truncate max-w-[72px]"
                                    :title="item.store?.name || 'Toko'"
                                >
                                    {{ excerptText(item.store?.name || 'Toko', 8) }}
                                </span>
                            </TableCell>

                            <TableCell v-if="columnVisibility.recipient">
                                <div
                                    class="text-sm font-semibold text-stone-900 truncate max-w-[140px] leading-tight"
                                    :title="item.recipient_name"
                                >
                                    {{ excerptText(item.recipient_name, 16) }}
                                </div>
                                <div
                                    class="text-xs text-stone-500 truncate max-w-[140px] mt-0.5"
                                    :title="item.destination_area_name || item.destination_address"
                                >
                                    {{ excerptText(formatShortDestination(item.destination_area_name, item.destination_address), 18) }}
                                </div>
                            </TableCell>

                            <TableCell v-if="columnVisibility.courier">
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-2">
                                        <div class="h-5.5 w-10 bg-white rounded border border-stone-200/80 p-0.5 flex items-center justify-center shrink-0">
                                            <img
                                                v-if="getCourierLogo(item.courier_code || item.courier_service_name)"
                                                :src="getCourierLogo(item.courier_code || item.courier_service_name)!"
                                                :alt="item.courier_code"
                                                class="max-h-3.5 max-w-full object-contain"
                                            />
                                            <span v-else class="text-[11px] font-bold text-stone-600 uppercase">
                                                {{ item.courier_code }}
                                            </span>
                                        </div>
                                        <span
                                            class="text-sm font-semibold text-stone-800 truncate max-w-[115px]"
                                            :title="item.courier_service_name"
                                        >
                                            {{ excerptText(item.courier_service_name, 14) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center">
                                        <span
                                            v-if="item.payment_method === 'cod'"
                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200/70 tracking-wide uppercase"
                                        >
                                            COD
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-stone-100 text-stone-600 border border-stone-200/60 tracking-wide uppercase"
                                        >
                                            Non-COD
                                        </span>
                                    </div>
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

                            <TableCell v-if="columnVisibility.weight" class="text-sm font-medium text-stone-800 tabular-nums">
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
                                <div class="inline-flex items-center justify-center gap-1.5">
                                    <Button
                                        v-if="item.status === 'pending_payment'"
                                        as-child
                                        variant="outline"
                                        size="icon-sm"
                                        class="size-8 rounded-md border border-[#853953]/30 bg-[#853953]/5 text-primary hover:bg-[#853953] hover:text-white hover:border-[#853953] cursor-pointer shadow-2xs transition-colors"
                                        title="Bayar Ongkos Kirim"
                                    >
                                        <Link :href="`/shipments/${item.id}?pay=1`">
                                            <CreditCard class="size-4" />
                                        </Link>
                                    </Button>

                                    <Button
                                        v-else
                                        as-child
                                        variant="outline"
                                        size="icon-sm"
                                        class="size-8 rounded-md border border-stone-200 bg-white text-stone-600 hover:text-primary hover:border-primary/40 hover:bg-stone-50 cursor-pointer shadow-2xs transition-colors"
                                        title="Cetak Label Resi Thermal"
                                    >
                                        <a :href="`/shipments/${item.id}?print=1`" target="_blank" rel="noopener noreferrer">
                                            <Printer class="size-4" />
                                        </a>
                                    </Button>

                                    <Button
                                        as-child
                                        variant="outline"
                                        size="icon-sm"
                                        class="size-8 rounded-md border border-stone-200 bg-white text-stone-600 hover:text-primary hover:border-primary/40 hover:bg-stone-50 cursor-pointer shadow-2xs transition-colors"
                                        title="Lihat Detail Pengiriman"
                                    >
                                        <Link :href="`/shipments/${item.id}`">
                                            <Eye class="size-4" />
                                        </Link>
                                    </Button>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon-sm"
                                        class="size-8 rounded-md border border-stone-200 bg-white text-stone-500 hover:text-red-700 hover:border-red-300 hover:bg-red-50/70 cursor-pointer shadow-2xs transition-colors"
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

                <div class="hidden md:block">
                    <TablePagination
                        :links="shipments.links"
                        :from="shipments.from"
                        :to="shipments.to"
                        :total="shipments.total"
                        :selected-count="selectedIds.length"
                    />
                </div>

                <div class="md:hidden border-t border-stone-200 bg-stone-50/60 px-3.5 py-3 text-center">
                    <p class="text-xs text-stone-500">
                        Menampilkan {{ mobileShipments.length }} dari {{ shipments.total }} data
                    </p>
                    <Button
                        v-if="hasMorePages"
                        type="button"
                        variant="outline"
                        class="mt-2.5 w-full h-10 text-sm font-semibold text-[#853953] border-[#853953]/30 hover:bg-[#853953]/5 cursor-pointer"
                        :disabled="isLoadingMore"
                        @click="loadMore"
                    >
                        {{ isLoadingMore ? 'Memuat...' : 'Muat lebih banyak' }}
                    </Button>
                </div>
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

            <Dialog :open="isBulkDeleteModalOpen" @update:open="(val) => { if (!isBulkDeleting) isBulkDeleteModalOpen = val; }">
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
                            @click="isBulkDeleteModalOpen = false"
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
        </div>
    </div>
</template>
