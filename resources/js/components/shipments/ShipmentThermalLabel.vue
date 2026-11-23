<script setup lang="ts">
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import { encodeCode128B } from '@/lib/barcode';
import { formatDateTime, formatShortDestination, formatWeight, getCourierLogo } from '@/lib/status';
import type { ShipmentItemEntry } from '@/types/shipments';

export interface PrintableShipment {
    id: number;
    order_number: string;
    status: string;
    recipient_name: string;
    recipient_phone: string;
    destination_address: string;
    destination_area_name?: string | null;
    destination_postal_code?: string | null;
    weight: number;
    length?: number | null;
    width?: number | null;
    height?: number | null;
    item_description?: string | null;
    item_category?: string | null;
    items?: ShipmentItemEntry[] | null;
    courier_code: string;
    courier_service_name: string;
    shipping_fee: number;
    insurance_fee?: number | null;
    total_amount: number;
    payment_method?: string | null;
    item_value?: number | null;
    waybill_number: string | null;
    created_at?: string | null;
    store?: {
        id: number;
        name: string;
        phone?: string | null;
        address?: string | null;
        area_name?: string | null;
        postal_code?: string | null;
    } | null;
}

const props = defineProps<{
    shipment: PrintableShipment;
}>();

const MAX_VISIBLE_ITEMS = 4;

const displayWaybill = computed(
    () => props.shipment.waybill_number?.trim() || `STK-${props.shipment.order_number}`,
);

const barcode = computed(() => encodeCode128B(displayWaybill.value, 56, 4));

const courierLogo = computed(() =>
    getCourierLogo(props.shipment.courier_code || props.shipment.courier_service_name),
);

const isCod = computed(() => (props.shipment.payment_method || '').toLowerCase() === 'cod');

const codRecipientTotal = computed(
    () => Number(props.shipment.total_amount || 0) + Number(props.shipment.item_value || 0),
);

const hasDimensions = computed(
    () =>
        Number(props.shipment.length || 0) > 0 &&
        Number(props.shipment.width || 0) > 0 &&
        Number(props.shipment.height || 0) > 0,
);

const parsedItems = computed(() => {
    const raw = props.shipment.items;
    if (!Array.isArray(raw) || raw.length === 0) return [];

    return raw
        .map((item) => {
            const qty = Math.max(1, Number(item.qty || 1));
            const unitWeight = Number(item.weight || 0);
            const hasDim =
                Number(item.length || 0) > 0 &&
                Number(item.width || 0) > 0 &&
                Number(item.height || 0) > 0;

            return {
                name: (item.name || '').trim() || '-',
                description: (item.description || '').trim() || null,
                qty,
                lineWeight: unitWeight > 0 ? unitWeight * qty : 0,
                dimensions: hasDim ? `${item.length}×${item.width}×${item.height} cm` : null,
            };
        })
        .filter((item) => item.name.length > 0);
});

const visibleItems = computed(() => parsedItems.value.slice(0, MAX_VISIBLE_ITEMS));

const remainingItemsCount = computed(() => Math.max(0, parsedItems.value.length - MAX_VISIBLE_ITEMS));

const totalItemQty = computed(() =>
    parsedItems.value.length > 0 ? parsedItems.value.reduce((sum, item) => sum + item.qty, 0) : 1,
);

function formatAreaWithPostal(areaName?: string | null, postalCode?: string | null): string {
    const area = (areaName || '').trim();
    const postal = (postalCode || '').trim();
    if (!area && !postal) return '-';
    if (!area) return `Kode Pos ${postal}`;
    if (!postal || postal === '-') return area;
    if (area.includes(postal)) {
        return area;
    }
    return `${area} (${postal})`;
}

const routingDestination = computed(() =>
    formatShortDestination(props.shipment.destination_area_name, props.shipment.destination_address),
);

const routingPostal = computed(() => {
    const explicit = (props.shipment.destination_postal_code || '').trim();
    if (explicit && explicit !== '-') return explicit;
    const areaMatch = (props.shipment.destination_area_name || '').match(/\b\d{5}\b/);
    return areaMatch ? areaMatch[0] : '';
});

const senderArea = computed(() =>
    formatAreaWithPostal(props.shipment.store?.area_name, props.shipment.store?.postal_code),
);

const recipientArea = computed(() =>
    formatAreaWithPostal(props.shipment.destination_area_name, props.shipment.destination_postal_code),
);

const shortServiceName = computed(() => {
    const raw = (props.shipment.courier_service_name || props.shipment.courier_code || 'REGULER').trim();
    return raw;
});

const printedTimestamp = computed(() => {
    if (!props.shipment.created_at) return null;
    const formatted = formatDateTime(props.shipment.created_at);
    if (formatted === '-') return null;
    return formatted.replace(/:(\d{2})$/, '');
});
</script>

<template>
    <article class="shipment-label-card w-full h-full bg-white text-black border border-neutral-900 box-border select-text flex flex-col text-[11px]">
        <div class="flex items-center justify-between gap-2.5 px-3 py-2 border-b border-neutral-400">
            <AppLogo
                variant="dark"
                size="sm"
                icon-class="h-5 w-auto"
                class-name="gap-2 shrink-0"
            />

            <div class="flex items-center gap-2 min-w-0 text-right">
                <div class="text-[10.5px] font-bold uppercase text-black leading-tight line-clamp-2">
                    {{ shortServiceName }}
                </div>
                <img
                    v-if="courierLogo"
                    :src="courierLogo"
                    :alt="shipment.courier_code"
                    class="h-6 max-w-[64px] object-contain shrink-0"
                />
            </div>
        </div>

        <div class="text-center px-3 py-2 border-b border-neutral-400">
            <div class="mx-auto h-14 max-w-[96%]">
                <svg
                    :viewBox="`0 0 ${barcode.totalWidth} 56`"
                    preserveAspectRatio="none"
                    shape-rendering="crispEdges"
                    class="w-full h-full block"
                    role="img"
                    :aria-label="`Barcode resi ${displayWaybill}`"
                >
                    <path :d="barcode.pathD" fill="#000000" />
                </svg>
            </div>
            <div class="text-[15.5px] font-bold tracking-[0.05em] tabular-nums text-black leading-tight mt-1">
                {{ displayWaybill }}
            </div>
            <div class="text-[9px] text-neutral-600 tabular-nums mt-0.5">
                No. Order <span class="font-medium text-black">{{ shipment.order_number }}</span>
                <span v-if="printedTimestamp"> | {{ printedTimestamp }}</span>
            </div>
        </div>

        <div class="flex items-center justify-between gap-2.5 px-3 py-2 border-b border-neutral-400">
            <div class="min-w-0">
                <div class="text-[8.5px] font-medium uppercase tracking-wider text-neutral-500">
                    Tujuan Pengiriman
                </div>
                <div class="text-[11.5px] font-bold uppercase text-black tabular-nums leading-tight mt-0.5">
                    {{ routingDestination }}<template v-if="routingPostal">, {{ routingPostal }}</template>
                </div>
            </div>

            <div class="text-right shrink-0">
                <template v-if="isCod">
                    <div class="text-[8.5px] font-medium uppercase tracking-wider text-neutral-500 leading-none">
                        Tagihan COD
                    </div>
                    <div class="text-[15px] font-bold tabular-nums text-black leading-tight mt-0.5">
                        Rp {{ codRecipientTotal.toLocaleString('id-ID') }}
                    </div>
                </template>
                <template v-else>
                    <div class="text-[8.5px] font-medium uppercase tracking-wider text-neutral-500 leading-none">
                        Pembayaran
                    </div>
                    <div class="text-[11px] font-bold text-black leading-tight mt-0.5">
                        NON-COD (LUNAS)
                    </div>
                </template>
            </div>
        </div>

        <div class="grid grid-cols-12 border-b border-neutral-400">
            <div class="col-span-7 border-r border-dashed border-neutral-400 px-3 py-2.5">
                <div class="text-[8.5px] font-medium uppercase tracking-wider text-neutral-500 mb-0.5">
                    Penerima
                </div>
                <div class="text-[12.5px] font-bold text-black leading-snug">
                    {{ shipment.recipient_name }}
                </div>
                <div class="text-[10.5px] font-medium text-neutral-800 tabular-nums mt-0.5">
                    {{ shipment.recipient_phone }}
                </div>
                <p class="text-[10px] font-normal text-neutral-800 leading-snug mt-1 break-words line-clamp-4">
                    {{ shipment.destination_address }}
                </p>
                <div class="text-[9.5px] font-medium text-neutral-900 leading-snug mt-1">
                    {{ recipientArea }}
                </div>
            </div>

            <div class="col-span-5 px-3 py-2.5">
                <div class="text-[8.5px] font-medium uppercase tracking-wider text-neutral-500 mb-0.5">
                    Pengirim
                </div>
                <div class="text-[11.5px] font-bold text-black leading-snug">
                    {{ shipment.store?.name || '-' }}
                </div>
                <div class="text-[10px] font-medium text-neutral-700 tabular-nums mt-0.5">
                    {{ shipment.store?.phone || '-' }}
                </div>
                <p class="text-[9.5px] font-normal text-neutral-700 leading-snug mt-1 break-words line-clamp-4">
                    {{ shipment.store?.address || '-' }}
                </p>
                <div
                    v-if="senderArea !== '-'"
                    class="text-[9px] font-medium text-neutral-800 leading-snug mt-1"
                >
                    {{ senderArea }}
                </div>
            </div>
        </div>

        <div class="text-[9px]">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="border-b border-neutral-300 text-[8px] font-medium uppercase tracking-wider text-neutral-500">
                        <th class="py-1 pl-3 pr-1.5 w-5 font-medium">#</th>
                        <th class="py-1 px-1.5 font-medium">Produk & Deskripsi</th>
                        <th class="py-1 px-1.5 text-right w-10 font-medium">Qty</th>
                        <th class="py-1 pl-1.5 pr-3 text-right w-15 font-medium">Berat</th>
                    </tr>
                </thead>
                <tbody v-if="parsedItems.length > 0" class="divide-y divide-neutral-200">
                    <tr v-for="(item, idx) in visibleItems" :key="idx" class="align-top">
                        <td class="py-1 pl-3 pr-1.5 tabular-nums text-neutral-500">{{ idx + 1 }}</td>
                        <td class="py-1 px-1.5 text-black leading-tight">
                            <div class="font-medium">
                                {{ item.name }}
                                <span v-if="item.dimensions" class="text-[8px] font-normal text-neutral-500 tabular-nums ml-1">
                                    ({{ item.dimensions }})
                                </span>
                            </div>
                            <div v-if="item.description" class="text-[8px] text-neutral-600 leading-tight mt-0.5 line-clamp-1">
                                {{ item.description }}
                            </div>
                        </td>
                        <td class="py-1 px-1.5 text-right tabular-nums whitespace-nowrap">{{ item.qty }}x</td>
                        <td class="py-1 pl-1.5 pr-3 text-right tabular-nums whitespace-nowrap text-neutral-800">
                            {{ item.lineWeight > 0 ? formatWeight(item.lineWeight) : '-' }}
                        </td>
                    </tr>
                    <tr v-if="remainingItemsCount > 0">
                        <td colspan="4" class="py-1 px-3 text-[8px] text-neutral-600 italic">
                            +{{ remainingItemsCount }} barang lainnya terlampir di detail order
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr class="align-top">
                        <td class="py-1 pl-3 pr-1.5 tabular-nums text-neutral-500">1</td>
                        <td class="py-1 px-1.5 font-medium text-black leading-tight">
                            {{ shipment.item_description || shipment.item_category || '-' }}
                        </td>
                        <td class="py-1 px-1.5 text-right tabular-nums whitespace-nowrap">1x</td>
                        <td class="py-1 pl-1.5 pr-3 text-right tabular-nums whitespace-nowrap text-neutral-800">
                            {{ formatWeight(shipment.weight) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="border-t border-neutral-300 text-[8.5px] font-bold text-black">
                        <td colspan="2" class="py-1 pl-3 pr-1.5">
                            Total
                            <span v-if="shipment.insurance_fee && shipment.insurance_fee > 0" class="font-normal text-neutral-600 ml-1">
                                (Termasuk Asuransi)
                            </span>
                            <span v-else-if="hasDimensions" class="font-normal text-neutral-600 ml-1">
                                ({{ shipment.length }}×{{ shipment.width }}×{{ shipment.height }} cm)
                            </span>
                        </td>
                        <td class="py-1 px-1.5 text-right tabular-nums whitespace-nowrap">{{ totalItemQty }} pcs</td>
                        <td class="py-1 pl-1.5 pr-3 text-right tabular-nums whitespace-nowrap">
                            {{ formatWeight(shipment.weight) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-auto px-3 py-1.5 border-t border-neutral-400 flex items-center justify-between gap-2 text-[8px] text-neutral-500">
            <span class="font-['Mukta',sans-serif] font-bold text-neutral-800 text-[9px]">SatuKirim</span>
            <span>Kurir wajib scan barcode saat serah terima</span>
        </div>
    </article>
</template>

<style scoped>
.shipment-label-card {
    font-family: 'Roboto', Arial, Helvetica, sans-serif;
    line-height: 1.35;
}
</style>
