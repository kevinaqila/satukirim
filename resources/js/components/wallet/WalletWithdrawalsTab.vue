<script setup lang="ts">
import {
    ArrowDownToLine,
    Check,
    ChevronDown,
    RotateCcw,
    Search,
    X,
} from '@lucide/vue';
import { computed } from 'vue';
import DatePeriodFilter from '@/components/DatePeriodFilter.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
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
import type { PaginatedResult, WithdrawalItem } from '@/types/wallet';

const props = defineProps<{
    withdrawals: PaginatedResult<WithdrawalItem>;
    status: string;
    search: string;
    datePreset: string;
    dateFrom: string;
    dateTo: string;
}>();

const emit = defineEmits<{
    'update:status': [value: string];
    'update:search': [value: string];
    dateChange: [payload: { preset: string; dateFrom: string; dateTo: string }];
    reset: [];
}>();

const wdStatusOptions = [
    { id: 'all', label: 'Semua Status' },
    { id: 'processing', label: 'Diproses' },
    { id: 'completed', label: 'Selesai' },
    { id: 'failed', label: 'Gagal' },
];

const activeWdStatusLabel = computed(() => {
    return wdStatusOptions.find((opt) => opt.id === props.status)?.label || 'Semua Status';
});

const hasActiveFilters = computed(() => {
    return props.status !== 'all' || props.search !== '' || props.datePreset !== 'all';
});

const getWithdrawalStatusLabel = (status: string): string => {
    if (status === 'completed') return 'Selesai';
    if (status === 'failed') return 'Gagal';
    return 'Diproses';
};

const formatWithdrawalDestination = (notes?: string | null): string => {
    if (!notes) return '-';
    return notes
        .replace(/\[Xendit:\s*[^\]]+\]\s*/g, '')
        .replace(/\[Gagal:\s*[^\]]+\]/g, '')
        .replace(/^Transfer ke\s+/i, '')
        .trim() || '-';
};
</script>

<template>
    <div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2 px-3.5 sm:px-4 py-2.5 border-b border-stone-200 bg-white">
            <div class="relative flex-1 min-w-0">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-stone-400 pointer-events-none" />
                <Input
                    :model-value="search"
                    @update:model-value="emit('update:search', String($event))"
                    placeholder="Cari referensi WD-... atau bank tujuan..."
                    class="pl-9 pr-8 text-sm h-9 rounded-md border-stone-200"
                />
                <button
                    v-if="search"
                    type="button"
                    @click="emit('update:search', '')"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-700 cursor-pointer"
                    aria-label="Bersihkan pencarian"
                >
                    <X class="size-4" />
                </button>
            </div>

            <div class="flex items-center gap-2 shrink-0 flex-wrap">
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="flex h-9 items-center justify-between gap-2 rounded-md border bg-white px-3 text-xs sm:text-sm font-semibold cursor-pointer transition-colors"
                            :class="status === 'all' ? 'border-stone-200 text-stone-700 hover:bg-stone-50' : 'border-[#853953]/40 text-[#853953]'"
                        >
                            <span>{{ activeWdStatusLabel }}</span>
                            <ChevronDown class="size-4 text-stone-400" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start" class="w-44 text-sm">
                        <DropdownMenuItem
                            v-for="opt in wdStatusOptions"
                            :key="opt.id"
                            class="justify-between cursor-pointer"
                            :class="status === opt.id ? 'text-[#853953] font-bold' : ''"
                            @select="emit('update:status', opt.id)"
                        >
                            {{ opt.label }}
                            <Check v-if="status === opt.id" class="size-4" />
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <DatePeriodFilter
                    :preset="datePreset"
                    :date-from="dateFrom"
                    :date-to="dateTo"
                    @change="emit('dateChange', $event)"
                />

                <Button
                    v-if="hasActiveFilters"
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="emit('reset')"
                    class="h-9 px-2.5 text-xs font-semibold text-stone-600 hover:text-[#853953] cursor-pointer"
                    title="Reset Filter"
                >
                    <RotateCcw class="size-3.5" />
                </Button>
            </div>
        </div>

        <div v-if="!withdrawals.data || withdrawals.data.length === 0" class="flex flex-col items-center justify-center py-12 px-4 text-center space-y-3">
            <div class="size-12 mx-auto rounded-full bg-rose-50 flex items-center justify-center text-[#853953]">
                <ArrowDownToLine class="size-6" />
            </div>
            <div class="space-y-1">
                <h3 class="text-sm font-bold text-stone-900">
                    {{ hasActiveFilters ? 'Tidak Ada Riwayat Penarikan yang Sesuai' : 'Belum Ada Riwayat Penarikan Dana' }}
                </h3>
                <p class="text-xs text-stone-500 max-w-sm mx-auto">
                    {{
                        hasActiveFilters
                            ? 'Coba ubah kata kunci pencarian, status pencairan, atau rentang periode filter.'
                            : 'Semua pengajuan penarikan saldo COD Anda akan tercatat lengkap beserta nomor referensi di sini.'
                    }}
                </p>
            </div>
            <Button v-if="hasActiveFilters" size="sm" variant="outline" @click="emit('reset')" class="cursor-pointer mt-1">
                Reset Filter
            </Button>
        </div>

        <div v-else>
            <div class="md:hidden divide-y divide-stone-200">
                <div
                    v-for="wd in withdrawals.data"
                    :key="wd.id"
                    class="p-3.5 space-y-1.5"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-bold text-[#853953] tabular-nums">
                            {{ wd.reference_number }}
                        </span>
                        <span class="text-xs font-bold text-[#853953]">
                            {{ getWithdrawalStatusLabel(wd.status) }}
                        </span>
                    </div>
                    <div class="flex items-baseline justify-between gap-2">
                        <span class="text-xs text-stone-600 truncate">
                            {{ formatWithdrawalDestination(wd.notes) }}
                        </span>
                        <span class="text-sm font-bold text-[#2C2C2C] tabular-nums shrink-0">
                            Rp {{ Number(wd.amount).toLocaleString('id-ID') }}
                        </span>
                    </div>
                    <div class="text-xs text-stone-400">
                        {{ formatDateTime(wd.created_at) }}
                    </div>
                </div>
            </div>

            <Table class="hidden md:table">
                <TableHeader>
                    <TableRow class="hover:bg-transparent border-b border-stone-200">
                        <TableHead class="pl-4">
                            <span class="text-sm font-semibold text-stone-800">Referensi</span>
                        </TableHead>
                        <TableHead>
                            <span class="text-sm font-semibold text-stone-800">Tanggal Pengajuan</span>
                        </TableHead>
                        <TableHead>
                            <span class="text-sm font-semibold text-stone-800">Rekening Tujuan</span>
                        </TableHead>
                        <TableHead class="text-right">
                            <span class="text-sm font-semibold text-stone-800">Nominal</span>
                        </TableHead>
                        <TableHead class="pr-4 text-center">
                            <span class="text-sm font-semibold text-stone-800">Status</span>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="wd in withdrawals.data" :key="wd.id">
                        <TableCell class="pl-4 text-sm font-bold text-[#853953] tabular-nums whitespace-nowrap">
                            {{ wd.reference_number }}
                        </TableCell>
                        <TableCell class="text-xs text-stone-500 whitespace-nowrap">
                            {{ formatDateTime(wd.created_at) }}
                        </TableCell>
                        <TableCell class="text-sm text-stone-800 max-w-[260px] truncate" :title="formatWithdrawalDestination(wd.notes)">
                            {{ formatWithdrawalDestination(wd.notes) }}
                        </TableCell>
                        <TableCell class="text-right text-sm font-bold text-[#2C2C2C] tabular-nums whitespace-nowrap">
                            Rp {{ Number(wd.amount).toLocaleString('id-ID') }}
                        </TableCell>
                        <TableCell class="pr-4 text-center whitespace-nowrap">
                            <span class="text-sm font-bold text-[#853953]">
                                {{ getWithdrawalStatusLabel(wd.status) }}
                            </span>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <TablePagination
                v-if="withdrawals.links && withdrawals.links.length > 0"
                :links="withdrawals.links"
                :from="withdrawals.from"
                :to="withdrawals.to"
                :total="withdrawals.total"
            />
        </div>
    </div>
</template>

