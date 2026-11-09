<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    CalendarDays,
    Check,
    ChevronDown,
    Copy,
    Search,
    SlidersHorizontal,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AppTopBar from '@/components/AppTopBar.vue';
import { Button } from '@/components/ui/button';
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
import { formatDateTime } from '@/lib/status';

export interface AuditLogItem {
    id: number;
    user: {
        id: number;
        name: string;
        email: string;
        role: string;
    } | null;
    event: 'created' | 'updated' | 'deleted';
    auditable_type: string;
    auditable_id: number;
    auditable_label: string;
    old_values: Record<string, any> | null;
    new_values: Record<string, any> | null;
    old_formatted?: string[];
    new_formatted?: string[];
    ip_address: string | null;
    created_at: string;
}

const props = defineProps<{
    logs: {
        data: AuditLogItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    stats: {
        total: number;
        created_count: number;
        updated_count: number;
        deleted_count: number;
    };
    filters: {
        event: string;
        model: string;
        search: string;
        date_preset: string;
        date_from: string;
        date_to: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Super Admin',
                href: '/admin/audit-logs',
            },
            {
                title: 'Audit Log',
                href: '/admin/audit-logs',
            },
        ],
    },
});

const searchQuery = ref(props.filters.search || '');
const activeEvent = ref(props.filters.event || 'all');
const selectedModel = ref(props.filters.model || 'all');
const datePreset = ref(props.filters.date_preset || 'all');
const customFrom = ref(props.filters.date_from || '');
const customTo = ref(props.filters.date_to || '');

// Modul filter options (Pengiriman, Toko, Pengguna)
const modelOptions = [
    { id: 'all', label: 'Semua Modul' },
    { id: 'Shipment', label: 'Pengiriman' },
    { id: 'Store', label: 'Toko' },
    { id: 'User', label: 'Pengguna' },
];

const activeModelLabel = computed(() => {
    if (selectedModel.value === 'all') return 'Modul';
    return modelOptions.find((m) => m.id === selectedModel.value)?.label ?? 'Modul';
});

const selectModel = (id: string) => {
    selectedModel.value = id;
    applyFilters();
};

// Column Visibility (id: false by default)
const columnVisibility = ref({
    id: false,
    tanggal: true,
    pengguna: true,
    aksi: true,
    target: true,
    old_values: true,
    new_values: true,
    ip_address: true,
});

// Tabs: Status Filter Tabs (matching Users & Transactions styling)
const eventTabs = computed(() => [
    { id: 'all', label: `Semua Log (${props.stats.total})` },
    { id: 'created', label: `Data Dibuat (${props.stats.created_count})` },
    { id: 'updated', label: `Diperbarui (${props.stats.updated_count})` },
    { id: 'deleted', label: `Dihapus (${props.stats.deleted_count})` },
]);

const selectTab = (tabId: string) => {
    activeEvent.value = tabId;
    applyFilters();
};

const datePresets = [
    { id: 'all', label: 'Periode' },
    { id: 'today', label: 'Hari Ini' },
    { id: '7days', label: '7 Hari Terakhir' },
    { id: '30days', label: '30 Hari Terakhir' },
];

const formatShortDate = (iso: string): string => {
    if (!iso) return '…';
    const d = new Date(`${iso}T00:00:00`);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    return `${day}/${month}`;
};

const activeDateLabel = computed(() => {
    if (datePreset.value === 'custom') {
        if (customFrom.value && customTo.value) {
            return `${formatShortDate(customFrom.value)} – ${formatShortDate(customTo.value)}`;
        }
        if (customFrom.value) return `Sejak ${formatShortDate(customFrom.value)}`;
        if (customTo.value) return `Hingga ${formatShortDate(customTo.value)}`;
        return 'Rentang Kustom';
    }
    if (datePreset.value === 'all') {
        return 'Periode';
    }
    const match = datePresets.find((p) => p.id === datePreset.value);
    return match ? match.label : 'Periode';
});

const selectDatePreset = (id: string) => {
    datePreset.value = id;
    if (id !== 'custom') {
        customFrom.value = '';
        customTo.value = '';
    }
    applyFilters();
};

const applyCustomDate = () => {
    datePreset.value = 'custom';
    applyFilters();
};

// Filter actions
const applyFilters = () => {
    router.get(
        '/admin/audit-logs',
        {
            search: searchQuery.value || undefined,
            event: activeEvent.value !== 'all' ? activeEvent.value : undefined,
            model: selectedModel.value !== 'all' ? selectedModel.value : undefined,
            date_preset: datePreset.value !== 'all' ? datePreset.value : undefined,
            date_from: datePreset.value === 'custom' ? customFrom.value || undefined : undefined,
            date_to: datePreset.value === 'custom' ? customTo.value || undefined : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

let searchTimer: any = null;
watch(searchQuery, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyFilters();
    }, 400);
});

watch(selectedModel, () => {
    applyFilters();
});

const resetAllFilters = () => {
    searchQuery.value = '';
    activeEvent.value = 'all';
    selectedModel.value = 'all';
    datePreset.value = 'all';
    customFrom.value = '';
    customTo.value = '';
    applyFilters();
};

const hasActiveFilters = computed(() => {
    return (
        searchQuery.value !== '' ||
        activeEvent.value !== 'all' ||
        selectedModel.value !== 'all' ||
        datePreset.value !== 'all'
    );
});

const copiedId = ref<number | null>(null);
const copyIp = (id: number, ip: string) => {
    if (navigator?.clipboard) {
        navigator.clipboard.writeText(ip);
        copiedId.value = id;
        setTimeout(() => {
            if (copiedId.value === id) copiedId.value = null;
        }, 1500);
    }
};
</script>

<template>
    <Head title="Audit Log - Super Admin SatuKirim" />

    <!-- Top Bar with Primary Background (#853953) -->
    <AppTopBar title="Audit Log Sistem" />

    <div class="flex h-full flex-1 flex-col gap-3.5 p-3 sm:p-4 lg:p-5 w-full">
        <!-- Unified Audit Logs Data Table Container -->
        <div class="rounded-none border border-stone-200 bg-white overflow-hidden shadow-2xs">
            <!-- Top Row: Status Filter Tabs (Identik dengan Users & Transactions) -->
            <div class="flex flex-wrap items-center justify-between gap-2 px-3.5 sm:px-4 border-b border-stone-200 bg-stone-50/60">
                <div class="flex items-center gap-1 overflow-x-auto -mb-px">
                    <button
                        v-for="tab in eventTabs"
                        :key="tab.id"
                        type="button"
                        @click="selectTab(tab.id)"
                        class="px-3.5 py-2.5 text-sm font-semibold border-b-2 transition-colors cursor-pointer whitespace-nowrap"
                        :class="
                            activeEvent === tab.id
                                ? 'border-[#853953] text-[#853953] bg-white/80'
                                : 'border-transparent text-stone-600 hover:text-stone-900 hover:border-stone-300'
                        "
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <!-- Second Row: Search Input, Model Filter, Period Filter, Reset & Column Visibility -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 px-3.5 sm:px-4 py-2.5 border-b border-stone-200 bg-white">
                <div class="flex flex-1 flex-col sm:flex-row items-stretch sm:items-center gap-2.5 min-w-0">
                    <!-- Search Input -->
                    <div class="relative flex-1 max-w-md">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-stone-400 pointer-events-none" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Cari nama pengguna, IP address, atau ID target..."
                            class="pl-9 pr-8 text-sm h-9 rounded-md border-stone-200"
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

                    <!-- Filter Modul (Pengiriman, Toko, Pengguna) -->
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="flex h-9 items-center justify-between gap-2 rounded-md border bg-white px-3 text-sm font-semibold cursor-pointer w-full sm:w-auto shrink-0 transition-colors"
                                :class="selectedModel === 'all' ? 'border-stone-200 text-stone-700 hover:bg-stone-50' : 'border-[#853953]/40 text-[#853953] bg-white'"
                                aria-label="Filter modul"
                            >
                                <span class="truncate">{{ activeModelLabel }}</span>
                                <ChevronDown class="size-4 shrink-0 text-stone-400" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start" class="w-48 text-sm">
                            <DropdownMenuItem
                                v-for="opt in modelOptions"
                                :key="opt.id"
                                class="justify-between text-sm cursor-pointer"
                                :class="selectedModel === opt.id ? 'text-[#853953] font-bold' : ''"
                                @select="selectModel(opt.id)"
                            >
                                {{ opt.label }}
                                <Check v-if="selectedModel === opt.id" class="size-4" />
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <!-- Filter Periode (Desktop: di samping pencarian, identik dengan Transactions.vue) -->
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="flex h-9 items-center justify-between gap-2 rounded-md border bg-white px-3 text-sm font-semibold cursor-pointer w-full sm:w-auto sm:min-w-36 shrink-0 transition-colors"
                                :class="datePreset === 'all' ? 'border-stone-200 text-stone-700 hover:bg-stone-50' : 'border-[#853953]/40 text-[#853953] bg-white'"
                                aria-label="Filter periode log"
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
                </div>

                <div class="flex items-center justify-between sm:justify-end gap-2.5 shrink-0">
                    <!-- Reset Filter Button -->
                    <button
                        v-if="hasActiveFilters"
                        type="button"
                        @click="resetAllFilters"
                        class="text-xs text-rose-600 hover:underline font-semibold cursor-pointer"
                    >
                        Reset Filter
                    </button>

                    <!-- Column Visibility Dropdown -->
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="inline-flex h-9 items-center gap-1.5 px-3 text-sm font-semibold text-stone-700 rounded-md border border-stone-200 bg-white hover:bg-stone-50 hover:text-stone-900 transition-colors cursor-pointer shrink-0"
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
                                @update:checked="(val: boolean) => (columnVisibility.id = val)"
                            >
                                ID
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.tanggal"
                                @update:checked="(val: boolean) => (columnVisibility.tanggal = val)"
                            >
                                Tanggal
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.pengguna"
                                @update:checked="(val: boolean) => (columnVisibility.pengguna = val)"
                            >
                                Pengguna
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.aksi"
                                @update:checked="(val: boolean) => (columnVisibility.aksi = val)"
                            >
                                Aksi
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.target"
                                @update:checked="(val: boolean) => (columnVisibility.target = val)"
                            >
                                Target Data
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.old_values"
                                @update:checked="(val: boolean) => (columnVisibility.old_values = val)"
                            >
                                Nilai Lama
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.new_values"
                                @update:checked="(val: boolean) => (columnVisibility.new_values = val)"
                            >
                                Nilai Baru
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.ip_address"
                                @update:checked="(val: boolean) => (columnVisibility.ip_address = val)"
                            >
                                IP Address
                            </DropdownMenuCheckboxItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <!-- Table -->
            <div v-if="logs.data.length > 0" class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-stone-50/80 hover:bg-stone-50/80">
                            <TableHead v-if="columnVisibility.id" class="w-16 pl-4 text-sm font-semibold text-stone-800">ID</TableHead>
                            <TableHead v-if="columnVisibility.tanggal" class="w-44 text-sm font-semibold text-stone-800">Tanggal</TableHead>
                            <TableHead v-if="columnVisibility.pengguna" class="w-48 text-sm font-semibold text-stone-800">Pengguna</TableHead>
                            <TableHead v-if="columnVisibility.aksi" class="w-24 text-sm font-semibold text-stone-800">Aksi</TableHead>
                            <TableHead v-if="columnVisibility.target" class="w-48 text-sm font-semibold text-stone-800">Target Data</TableHead>
                            <TableHead v-if="columnVisibility.old_values" class="w-48 max-w-[200px] text-sm font-semibold text-stone-800">Nilai Lama</TableHead>
                            <TableHead v-if="columnVisibility.new_values" class="w-48 max-w-[200px] text-sm font-semibold text-stone-800">Nilai Baru</TableHead>
                            <TableHead v-if="columnVisibility.ip_address" class="w-36 pr-4 text-sm font-semibold text-stone-800">IP Address</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="log in logs.data"
                            :key="log.id"
                            class="hover:bg-stone-50/60 transition-colors"
                        >
                            <!-- ID (Angka polos tanpa #, default hidden) -->
                            <TableCell v-if="columnVisibility.id" class="pl-4 font-mono text-sm font-medium text-stone-500 tabular-nums">
                                {{ log.id }}
                            </TableCell>

                            <!-- Tanggal (Satu baris rapi) -->
                            <TableCell v-if="columnVisibility.tanggal" class="text-sm font-medium text-stone-600 tabular-nums whitespace-nowrap">
                                {{ formatDateTime(log.created_at) }}
                            </TableCell>

                            <!-- Pengguna -->
                            <TableCell v-if="columnVisibility.pengguna">
                                <div v-if="log.user">
                                    <div class="text-sm font-semibold text-stone-900 truncate" :title="log.user.name">
                                        {{ log.user.name }}
                                    </div>
                                    <div class="text-xs text-stone-500 mt-0.5">
                                        {{ log.user.role === 'super_admin' ? 'Super Admin' : 'Merchant UMKM' }}
                                    </div>
                                </div>
                                <div v-else>
                                    <div class="text-sm font-semibold text-stone-900">
                                        Sistem
                                    </div>
                                    <div class="text-xs text-stone-500 mt-0.5">
                                        Otomatis
                                    </div>
                                </div>
                            </TableCell>

                            <!-- Aksi (Teks murni tanpa background, tanpa border, tanpa dot) -->
                            <TableCell v-if="columnVisibility.aksi" class="text-sm font-medium text-stone-700">
                                {{ log.event === 'created' ? 'Created' : (log.event === 'updated' ? 'Updated' : 'Deleted') }}
                            </TableCell>

                            <!-- Target Data (Label polos tanpa Order #, tipe data bahasa Indonesia yang bersih) -->
                            <TableCell v-if="columnVisibility.target">
                                <div class="text-sm font-bold text-stone-900 truncate">
                                    {{ log.auditable_label }}
                                </div>
                                <div class="text-xs text-stone-500">
                                    {{ log.auditable_type === 'Shipment' ? 'Pengiriman' : (log.auditable_type === 'Store' ? 'Toko' : (log.auditable_type === 'User' ? 'Pengguna' : log.auditable_type)) }}
                                </div>
                            </TableCell>

                            <!-- Nilai Lama (Readable Bullet Points, dibatasi lebarnya dan membungkus ke bawah) -->
                            <TableCell v-if="columnVisibility.old_values" class="align-top py-2.5 whitespace-normal w-48 max-w-[200px]">
                                <div v-if="log.old_formatted && log.old_formatted.length > 0" class="space-y-1 max-w-[185px] whitespace-normal">
                                    <div
                                        v-for="(item, idx) in log.old_formatted"
                                        :key="idx"
                                        class="flex items-start gap-1 text-xs text-stone-600 leading-snug whitespace-normal"
                                    >
                                        <span class="shrink-0 text-stone-400 select-none">-</span>
                                        <span class="break-words min-w-0 whitespace-normal">{{ item }}</span>
                                    </div>
                                </div>
                                <span v-else class="text-xs text-stone-400">-</span>
                            </TableCell>

                            <!-- Nilai Baru (Readable Bullet Points, dibatasi lebarnya dan membungkus ke bawah) -->
                            <TableCell v-if="columnVisibility.new_values" class="align-top py-2.5 whitespace-normal w-48 max-w-[200px]">
                                <div v-if="log.new_formatted && log.new_formatted.length > 0" class="space-y-1 max-w-[185px] whitespace-normal">
                                    <div
                                        v-for="(item, idx) in log.new_formatted"
                                        :key="idx"
                                        class="flex items-start gap-1 text-xs text-stone-800 font-medium leading-snug whitespace-normal"
                                    >
                                        <span class="shrink-0 text-stone-400 select-none">-</span>
                                        <span class="break-words min-w-0 whitespace-normal">{{ item }}</span>
                                    </div>
                                </div>
                                <span v-else class="text-xs text-stone-400">-</span>
                            </TableCell>

                            <!-- IP Address -->
                            <TableCell v-if="columnVisibility.ip_address" class="pr-4 text-sm font-mono text-stone-600 tabular-nums whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <span>{{ log.ip_address || '127.0.0.1' }}</span>
                                    <button
                                        v-if="log.ip_address"
                                        type="button"
                                        @click="copyIp(log.id, log.ip_address)"
                                        class="text-stone-400 hover:text-stone-700 cursor-pointer"
                                        title="Salin IP"
                                    >
                                        <Check v-if="copiedId === log.id" class="size-3 text-stone-700" />
                                        <Copy v-else class="size-3" />
                                    </button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination footer -->
                <TablePagination
                    :links="logs.links"
                    :from="logs.from"
                    :to="logs.to"
                    :total="logs.total"
                />
            </div>

            <div v-else class="py-12 text-center text-stone-500 text-sm">
                Tidak ada data log yang sesuai filter.
            </div>
        </div>
    </div>
</template>
