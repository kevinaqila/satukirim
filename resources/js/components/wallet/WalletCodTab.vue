<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Banknote,
    Check,
    ChevronDown,
    Copy,
    Eye,
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
import { useCopyClipboard } from '@/composables/useCopyClipboard';
import {
    excerptText,
    formatDateTime,
    formatShortDestination,
    getCourierLogo,
} from '@/lib/status';
import type { CodShipmentItem, PaginatedResult } from '@/types/wallet';

const props = defineProps<{
    codShipments: PaginatedResult<CodShipmentItem>;
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

const { copy, isCopied } = useCopyClipboard();

const codStatusOptions = [
    { id: 'all', label: 'Semua Status' },
    { id: 'settled', label: 'Masuk Saldo' },
    { id: 'pending', label: 'Tertahan' },
    { id: 'cancelled', label: 'Batal / Retur' },
];

const activeCodStatusLabel = computed(() => {
    return codStatusOptions.find((opt) => opt.id === props.status)?.label || 'Semua Status';
});

const hasActiveFilters = computed(() => {
    return props.status !== 'all' || props.search !== '' || props.datePreset !== 'all';
});

const getCodFundStatus = (status: string) => {
    const s = (status || '').toLowerCase().trim();
    if (s === 'delivered') return 'Masuk Saldo';
    if (['cancelled', 'rejected', 'courier_not_found'].includes(s)) return 'Dibatalkan';
    if (s === 'returned') return 'Retur (Batal)';
    return 'Tertahan';
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
                    placeholder="Cari kode transaksi, resi, atau penerima..."
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
                            <span>{{ activeCodStatusLabel }}</span>
                            <ChevronDown class="size-4 text-stone-400" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start" class="w-44 text-sm">
                        <DropdownMenuItem
                            v-for="opt in codStatusOptions"
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

        <div v-if="!codShipments.data || codShipments.data.length === 0" class="flex flex-col items-center justify-center py-12 px-4 text-center space-y-3">
            <div class="size-12 mx-auto rounded-full bg-rose-50 flex items-center justify-center text-[#853953]">
                <Banknote class="size-6" />
            </div>
            <div class="space-y-1">
                <h3 class="text-sm font-bold text-stone-900">
                    {{ hasActiveFilters ? 'Tidak Ada Transaksi COD yang Sesuai' : 'Belum Ada Transaksi COD' }}
                </h3>
                <p class="text-xs text-stone-500 max-w-sm mx-auto">
                    {{
                        hasActiveFilters
                            ? 'Coba ubah kata kunci pencarian, status dana, atau rentang periode filter.'
                            : 'Saat membuat pengiriman baru, pilih metode pembayaran "COD (Bayar di Tempat)" agar pembeli membayar saat paket tiba.'
                    }}
                </p>
            </div>
            <Button v-if="hasActiveFilters" size="sm" variant="outline" @click="emit('reset')" class="cursor-pointer mt-1">
                Reset Filter
            </Button>
            <Link v-else href="/shipments/create">
                <Button size="sm" class="bg-[#853953] hover:bg-[#6c2e43] text-white cursor-pointer mt-1">
                    Buat Pengiriman COD
                </Button>
            </Link>
        </div>

        <div v-else>
            <div class="md:hidden divide-y divide-stone-200">
                <Link
                    v-for="item in codShipments.data"
                    :key="item.id"
                    :href="`/shipments/${item.id}`"
                    class="block p-3 hover:bg-stone-50/80 active:bg-stone-100/70 transition-colors cursor-pointer"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div class="inline-flex items-center gap-1.5 min-w-0">
                            <span class="text-sm font-bold text-[#853953] truncate">
                                {{ item.order_number }}
                            </span>
                            <button
                                type="button"
                                @click.stop.prevent="copy(`order:${item.id}`, item.order_number)"
                                class="text-stone-400 hover:text-[#853953] transition-colors cursor-pointer shrink-0"
                                title="Salin Kode Transaksi"
                            >
                                <Check v-if="isCopied(`order:${item.id}`)" class="size-3.5 text-emerald-600" />
                                <Copy v-else class="size-3.5" />
                            </button>
                        </div>
                        <span class="text-xs font-bold text-[#853953] shrink-0 whitespace-nowrap">
                            {{ getCodFundStatus(item.status) }}
                        </span>
                    </div>

                    <div class="flex items-baseline justify-between gap-2 mt-1.5">
                        <div class="text-sm font-semibold text-stone-900 truncate">
                            {{ item.recipient_name }}
                        </div>
                        <div class="text-sm font-semibold text-stone-900 tabular-nums shrink-0 text-right">
                            Rp {{ Math.max(0, Number(item.item_value) - 1000).toLocaleString('id-ID') }}
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 text-xs text-stone-500 mt-0.5">
                        <span class="truncate">
                            {{ formatShortDestination(item.destination_area_name, item.destination_address || null) }} - {{ item.courier_service_name }}
                        </span>
                        <span class="shrink-0 text-stone-400">
                            Potong Rp 1.000
                        </span>
                    </div>
                </Link>
            </div>

            <Table class="hidden md:table">
                <TableHeader>
                    <TableRow class="hover:bg-transparent border-b border-stone-200">
                        <TableHead class="pl-4">
                            <div class="flex flex-col leading-tight">
                                <span class="text-sm font-semibold text-stone-800">Kode Transaksi</span>
                                <span class="text-sm font-semibold text-stone-800 mt-0.5">Tanggal Transaksi</span>
                            </div>
                        </TableHead>

                        <TableHead>
                            <div class="flex flex-col leading-tight">
                                <span class="text-sm font-semibold text-stone-800">Penerima</span>
                                <span class="text-sm font-semibold text-stone-800 mt-0.5">Tujuan</span>
                            </div>
                        </TableHead>

                        <TableHead>
                            <span class="text-sm font-semibold text-stone-800">Ekspedisi</span>
                        </TableHead>

                        <TableHead>
                            <span class="text-sm font-semibold text-stone-800">No. Resi (AWB)</span>
                        </TableHead>

                        <TableHead class="text-right">
                            <div class="flex flex-col leading-tight items-end">
                                <span class="text-sm font-semibold text-stone-800">Hak Bersih</span>
                                <span class="text-sm font-semibold text-stone-800 mt-0.5">Tagihan COD</span>
                            </div>
                        </TableHead>

                        <TableHead class="text-center">
                            <span class="text-sm font-semibold text-stone-800">Status Dana</span>
                        </TableHead>

                        <TableHead class="w-16 pr-4 text-center">
                            <span class="text-sm font-semibold text-stone-800">Aksi</span>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="item in codShipments.data" :key="item.id" class="group">
                        <TableCell class="pl-4">
                            <div class="inline-flex items-center gap-1.5 leading-tight">
                                <Link
                                    :href="`/shipments/${item.id}`"
                                    class="text-sm font-bold text-[#853953] hover:underline transition-colors"
                                >
                                    {{ item.order_number }}
                                </Link>
                                <button
                                    type="button"
                                    @click="copy(`order:${item.id}`, item.order_number)"
                                    class="text-stone-400 hover:text-[#853953] transition-colors cursor-pointer"
                                    title="Salin Kode Transaksi"
                                >
                                    <Check v-if="isCopied(`order:${item.id}`)" class="size-3.5 text-emerald-600" />
                                    <Copy v-else class="size-3.5" />
                                </button>
                            </div>
                            <div class="text-xs text-stone-500 mt-0.5 whitespace-nowrap">
                                {{ formatDateTime(item.created_at) }}
                            </div>
                        </TableCell>

                        <TableCell>
                            <div
                                class="text-sm font-semibold text-stone-900 truncate max-w-[140px] leading-tight"
                                :title="item.recipient_name"
                            >
                                {{ excerptText(item.recipient_name, 16) }}
                            </div>
                            <div
                                class="text-xs text-stone-500 truncate max-w-[140px] mt-0.5"
                                :title="item.destination_area_name || item.destination_address || '-'"
                            >
                                {{ excerptText(formatShortDestination(item.destination_area_name, item.destination_address || null), 18) }}
                            </div>
                        </TableCell>

                        <TableCell>
                            <div class="flex items-center gap-2">
                                <div class="h-6 w-11 bg-white rounded border border-stone-200/80 p-0.5 flex items-center justify-center shrink-0">
                                    <img
                                        v-if="getCourierLogo(item.courier_code || item.courier_service_name)"
                                        :src="getCourierLogo(item.courier_code || item.courier_service_name)!"
                                        :alt="item.courier_code || item.courier_service_name"
                                        class="max-h-4 max-w-full object-contain"
                                    />
                                    <span v-else class="text-xs font-bold text-stone-600 uppercase">
                                        {{ item.courier_code || 'KURIR' }}
                                    </span>
                                </div>
                                <span
                                    class="text-sm font-semibold text-stone-800 truncate max-w-[105px]"
                                    :title="item.courier_service_name"
                                >
                                    {{ excerptText(item.courier_service_name, 14) }}
                                </span>
                            </div>
                        </TableCell>

                        <TableCell>
                            <div v-if="item.waybill_number" class="inline-flex items-center gap-1.5">
                                <span class="text-sm font-bold text-[#853953]">
                                    {{ item.waybill_number }}
                                </span>
                                <button
                                    type="button"
                                    @click="copy(`waybill:${item.id}`, item.waybill_number)"
                                    class="text-stone-400 hover:text-[#853953] transition-colors cursor-pointer"
                                    title="Salin nomor resi"
                                >
                                    <Check v-if="isCopied(`waybill:${item.id}`)" class="size-3.5 text-emerald-600" />
                                    <Copy v-else class="size-3.5" />
                                </button>
                            </div>
                            <span v-else class="text-sm text-stone-400">—</span>
                        </TableCell>

                        <TableCell class="text-right whitespace-nowrap">
                            <div class="text-sm font-bold text-stone-900 tabular-nums leading-tight">
                                Rp {{ Math.max(0, Number(item.item_value) - 1000).toLocaleString('id-ID') }}
                            </div>
                            <div class="text-xs text-stone-500 tabular-nums mt-0.5">
                                Tagihan: Rp {{ (Number(item.item_value) + Number(item.total_amount)).toLocaleString('id-ID') }}
                            </div>
                        </TableCell>

                        <TableCell class="text-center whitespace-nowrap">
                            <span class="text-sm font-bold text-[#853953]">
                                {{ getCodFundStatus(item.status) }}
                            </span>
                        </TableCell>

                        <TableCell class="pr-4 text-center">
                            <div class="inline-flex items-center justify-center">
                                <Button
                                    as-child
                                    variant="outline"
                                    size="icon-sm"
                                    class="size-8 rounded-md border border-stone-200 bg-white text-stone-600 hover:text-[#853953] hover:border-[#853953]/40 hover:bg-stone-50 cursor-pointer shadow-2xs transition-colors"
                                    title="Lihat Detail Pengiriman"
                                >
                                    <Link :href="`/shipments/${item.id}`">
                                        <Eye class="size-4" />
                                    </Link>
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <TablePagination
                v-if="codShipments.links && codShipments.links.length > 0"
                :links="codShipments.links"
                :from="codShipments.from"
                :to="codShipments.to"
                :total="codShipments.total"
            />
        </div>
    </div>
</template>

