<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Check,
    ChevronDown,
    Search,
    SlidersHorizontal,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AuditLogsTable from '@/components/admin/AuditLogsTable.vue';
import AppTopBar from '@/components/AppTopBar.vue';
import DatePeriodFilter from '@/components/DatePeriodFilter.vue';
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
import type { AdminAuditLogItem } from '@/types/admin';

const props = defineProps<{
    logs: {
        data: AdminAuditLogItem[];
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

const modelOptions = [
    { id: 'all', label: 'Semua Modul' },
    { id: 'Shipment', label: 'Pengiriman' },
    { id: 'Store', label: 'Toko' },
    { id: 'User', label: 'Pengguna' },
];

const auditDatePresets = [
    { id: 'all', label: 'Semua Periode' },
    { id: 'today', label: 'Hari Ini' },
    { id: '7days', label: '7 Hari Terakhir' },
    { id: '30days', label: '30 Hari Terakhir' },
];

const activeModelLabel = computed(() => {
    if (selectedModel.value === 'all') return 'Modul';
    return modelOptions.find((m) => m.id === selectedModel.value)?.label ?? 'Modul';
});

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

const eventTabs = computed(() => [
    { id: 'all', label: `Semua Log (${props.stats.total})` },
    { id: 'created', label: `Data Dibuat (${props.stats.created_count})` },
    { id: 'updated', label: `Diperbarui (${props.stats.updated_count})` },
    { id: 'deleted', label: `Dihapus (${props.stats.deleted_count})` },
]);

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
        },
    );
};

const selectTab = (tabId: string) => {
    activeEvent.value = tabId;
    applyFilters();
};

const selectModel = (id: string) => {
    selectedModel.value = id;
    applyFilters();
};

const handleDateChange = (payload: { preset: string; dateFrom: string; dateTo: string }) => {
    datePreset.value = payload.preset;
    customFrom.value = payload.dateFrom;
    customTo.value = payload.dateTo;
    applyFilters();
};

let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(searchQuery, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyFilters();
    }, 400);
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
</script>

<template>
    <Head title="Audit Log - Super Admin SatuKirim" />

    <AppTopBar title="Audit Log Sistem" />

    <div class="flex h-full flex-1 flex-col gap-3.5 p-3 sm:p-4 lg:p-5 w-full">
        <div class="rounded-none border border-stone-200 bg-white overflow-hidden shadow-2xs">
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

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 px-3.5 sm:px-4 py-2.5 border-b border-stone-200 bg-white">
                <div class="flex flex-1 flex-col sm:flex-row items-stretch sm:items-center gap-2.5 min-w-0">
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

                    <DatePeriodFilter
                        :preset="datePreset"
                        :date-from="customFrom"
                        :date-to="customTo"
                        :presets="auditDatePresets"
                        :manual-ranges="true"
                        align="start"
                        button-class="w-full sm:w-auto sm:min-w-36"
                        aria-label="Filter periode log"
                        @change="handleDateChange"
                    />
                </div>

                <div class="flex items-center justify-between sm:justify-end gap-2.5 shrink-0">
                    <button
                        v-if="hasActiveFilters"
                        type="button"
                        @click="resetAllFilters"
                        class="text-xs text-rose-600 hover:underline font-semibold cursor-pointer"
                    >
                        Reset Filter
                    </button>

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
                            <DropdownMenuCheckboxItem :checked="columnVisibility.id" @update:checked="(val: boolean) => (columnVisibility.id = val)">
                                ID
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem :checked="columnVisibility.tanggal" @update:checked="(val: boolean) => (columnVisibility.tanggal = val)">
                                Tanggal
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem :checked="columnVisibility.pengguna" @update:checked="(val: boolean) => (columnVisibility.pengguna = val)">
                                Pengguna
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem :checked="columnVisibility.aksi" @update:checked="(val: boolean) => (columnVisibility.aksi = val)">
                                Aksi
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem :checked="columnVisibility.target" @update:checked="(val: boolean) => (columnVisibility.target = val)">
                                Target Data
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem :checked="columnVisibility.old_values" @update:checked="(val: boolean) => (columnVisibility.old_values = val)">
                                Nilai Lama
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem :checked="columnVisibility.new_values" @update:checked="(val: boolean) => (columnVisibility.new_values = val)">
                                Nilai Baru
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem :checked="columnVisibility.ip_address" @update:checked="(val: boolean) => (columnVisibility.ip_address = val)">
                                IP Address
                            </DropdownMenuCheckboxItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <AuditLogsTable
                v-if="logs.data.length > 0"
                :logs="logs"
                :column-visibility="columnVisibility"
            />

            <div v-else class="py-12 text-center text-stone-500 text-sm">
                Tidak ada data log yang sesuai filter.
            </div>
        </div>
    </div>
</template>
