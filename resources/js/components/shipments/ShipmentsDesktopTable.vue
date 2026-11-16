<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Check,
    Copy,
    CreditCard,
    Eye,
    Printer,
    Trash2,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
    formatWeight,
    getCourierLogo,
    getShipmentStatus,
} from '@/lib/status';
import type { ShipmentListItem } from '@/types/shipments';

export type ShipmentSortField = 'id' | 'order_number' | 'store' | 'recipient' | 'courier' | 'weight' | 'total_amount' | 'status';

defineProps<{
    displayedShipments: ShipmentListItem[];
    selectedIds: number[];
    isAllSelected: boolean;
    isSomeSelected: boolean;
    sortField: ShipmentSortField | null;
    sortDirection: 'asc' | 'desc';
    columnVisibility: {
        id: boolean;
        store: boolean;
        recipient: boolean;
        courier: boolean;
        waybill: boolean;
        weight: boolean;
        amount: boolean;
        status: boolean;
    };
    pagination: {
        links?: Array<{ url: string | null; label: string; active: boolean }>;
        from?: number | null;
        to?: number | null;
        total: number;
    };
}>();

const emit = defineEmits<{
    toggleSelectAll: [checked: boolean | 'indeterminate'];
    toggleSelectRow: [id: number];
    toggleSort: [field: ShipmentSortField];
    openDelete: [item: ShipmentListItem];
}>();

const { copy, isCopied } = useCopyClipboard();
</script>

<template>
    <div class="hidden md:block">
        <Table>
            <TableHeader>
                <TableRow class="hover:bg-transparent border-b border-stone-200">
                    <TableHead class="w-10 pl-4 pr-2">
                        <Checkbox
                            :checked="isAllSelected ? true : isSomeSelected ? 'indeterminate' : false"
                            @update:checked="emit('toggleSelectAll', $event)"
                            aria-label="Pilih semua pengiriman"
                        />
                    </TableHead>

                    <TableHead v-if="columnVisibility.id" class="w-16">
                        <button
                            type="button"
                            @click="emit('toggleSort', 'id')"
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
                            @click="emit('toggleSort', 'order_number')"
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
                            @click="emit('toggleSort', 'store')"
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
                            @click="emit('toggleSort', 'recipient')"
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
                            @click="emit('toggleSort', 'courier')"
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
                            @click="emit('toggleSort', 'weight')"
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
                            @click="emit('toggleSort', 'status')"
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
                            @click="emit('toggleSort', 'total_amount')"
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
                            @update:checked="() => emit('toggleSelectRow', item.id)"
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
                                @click="copy(`order:${item.id}`, item.order_number)"
                                class="text-stone-400 hover:text-primary transition-colors cursor-pointer"
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
                            :title="item.destination_area_name || item.destination_address || ''"
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
                                @click="copy(`waybill:${item.id}`, item.waybill_number)"
                                class="text-stone-400 hover:text-primary transition-colors cursor-pointer"
                                title="Salin nomor resi"
                            >
                                <Check v-if="isCopied(`waybill:${item.id}`)" class="size-3.5 text-emerald-600" />
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
                                @click="emit('openDelete', item)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>

        <TablePagination
            :links="pagination.links"
            :from="pagination.from"
            :to="pagination.to"
            :total="pagination.total"
            :selected-count="selectedIds.length"
        />
    </div>
</template>

