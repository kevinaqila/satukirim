<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CreditCard, MoreVertical, Printer, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    formatDateTime,
    formatShortDestination,
    formatWeight,
    getShipmentStatus,
} from '@/lib/status';
import type { ShipmentListItem } from '@/types/shipments';

defineProps<{
    shipments: ShipmentListItem[];
    total: number;
    hasMorePages: boolean;
    isLoadingMore: boolean;
}>();

const emit = defineEmits<{
    openDelete: [item: ShipmentListItem];
    loadMore: [];
}>();
</script>

<template>
    <div class="md:hidden">
        <div class="divide-y divide-stone-200">
            <div
                v-for="item in shipments"
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
                                    @click="emit('openDelete', item)"
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
                                {{ item.courier_service_name }} - {{ formatWeight(item.weight) }}
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

        <div class="border-t border-stone-200 bg-stone-50/60 px-3.5 py-3 text-center">
            <p class="text-xs text-stone-500">
                Menampilkan {{ shipments.length }} dari {{ total }} data
            </p>
            <Button
                v-if="hasMorePages"
                type="button"
                variant="outline"
                class="mt-2.5 w-full h-10 text-sm font-semibold text-[#853953] border-[#853953]/30 hover:bg-[#853953]/5 cursor-pointer"
                :disabled="isLoadingMore"
                @click="emit('loadMore')"
            >
                {{ isLoadingMore ? 'Memuat...' : 'Muat lebih banyak' }}
            </Button>
        </div>
    </div>
</template>

