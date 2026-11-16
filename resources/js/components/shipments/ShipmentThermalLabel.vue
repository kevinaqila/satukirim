<script setup lang="ts">
import { computed } from 'vue';
import type { ShipmentDetail } from '@/types/shipments';

const props = defineProps<{
    shipment: ShipmentDetail;
}>();

const isCod = computed(() => (props.shipment.payment_method || '').toLowerCase() === 'cod');
const codRecipientTotal = computed(
    () => Number(props.shipment.total_amount || 0) + Number(props.shipment.item_value || 0),
);
</script>

<template>
    <div id="thermal-label" class="hidden print:block w-[100mm] min-h-[150mm] p-4 mx-auto border-2 border-black bg-white text-black font-sans text-xs">
        <div class="flex items-center justify-between border-b-2 border-black pb-2 mb-2">
            <div>
                <div class="text-base font-black tracking-tight">SATUKIRIM</div>
                <div class="text-[9px]">Platform Pengiriman Logistik UMKM</div>
            </div>
            <div class="text-right">
                <div class="text-base font-black uppercase">{{ shipment.courier_service_name }}</div>
                <div class="text-[9px]">Layanan Reguler Multi-Ekspedisi</div>
            </div>
        </div>

        <div class="text-center py-3 border-b-2 border-black space-y-1">
            <div class="font-mono text-xs font-semibold">RESI / NO. AIRWAY BILL (AWB)</div>
            <div class="text-lg font-black tracking-widest font-mono">
                {{ shipment.waybill_number || 'STK-' + shipment.order_number }}
            </div>
            <div class="h-10 flex items-center justify-center gap-0.5 px-4 overflow-hidden">
                <div v-for="n in 48" :key="n" class="h-full bg-black" :style="{ width: (n % 3 === 0 ? '3px' : '1.5px'), opacity: (n % 7 === 0 ? '0' : '1') }" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2 py-2 border-b-2 border-black text-[10px]">
            <div class="border-r border-black pr-2">
                <div class="font-bold underline">PENGIRIM (DARI):</div>
                <div class="font-bold text-xs mt-0.5">{{ shipment.store?.name }}</div>
                <div>{{ shipment.store?.phone }}</div>
                <div class="line-clamp-3 mt-0.5">{{ shipment.store?.address }}</div>
                <div class="font-bold mt-1">{{ shipment.store?.area_name }}</div>
            </div>

            <div class="pl-1">
                <div class="font-bold underline">PENERIMA (KEPADA):</div>
                <div class="font-bold text-xs mt-0.5">{{ shipment.recipient_name }}</div>
                <div>{{ shipment.recipient_phone }}</div>
                <div class="line-clamp-3 mt-0.5">{{ shipment.destination_address }}</div>
                <div class="font-bold mt-1">{{ shipment.destination_area_name }} ({{ shipment.destination_postal_code || '-' }})</div>
            </div>
        </div>

        <div class="py-2 border-b border-black flex justify-between text-[10px]">
            <div>
                <div><strong>Berat:</strong> {{ shipment.weight }} gram</div>
                <div><strong>Isi:</strong> {{ shipment.item_description || 'Retail Item' }}</div>
            </div>
            <div class="text-right">
                <div><strong>Kode Transaksi:</strong> {{ shipment.order_number }}</div>
                <div v-if="isCod" class="font-black text-xs mt-1 text-black">
                    TAGIHAN COD: Rp {{ codRecipientTotal.toLocaleString('id-ID') }}
                </div>
                <div v-else class="font-bold text-xs mt-1">NON-COD / LUNAS</div>
            </div>
        </div>

        <div class="pt-2 text-center text-[8px] text-stone-600">
            Dicetak otomatis via SatuKirim. Kurir wajib melakukan scan barcode saat serah terima barang.
        </div>
    </div>
</template>

