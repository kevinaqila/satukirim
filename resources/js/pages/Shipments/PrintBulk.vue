<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Printer } from '@lucide/vue';
import { onMounted } from 'vue';
import { Button } from '@/components/ui/button';

export interface ShipmentItem {
    id: number;
    order_number: string;
    status: string;
    recipient_name: string;
    recipient_phone: string;
    destination_address: string;
    destination_area_name?: string | null;
    destination_postal_code?: string | null;
    weight: number;
    item_description?: string | null;
    courier_code: string;
    courier_service_name: string;
    shipping_fee: number;
    insurance_fee?: number | null;
    total_amount: number;
    payment_method?: string | null;
    item_value?: number | null;
    waybill_number: string | null;
    created_at: string;
    store?: {
        id: number;
        name: string;
        phone?: string | null;
        address?: string | null;
        area_name?: string | null;
        postal_code?: string | null;
    } | null;
    user?: {
        id: number;
        name: string;
        email?: string | null;
        phone?: string | null;
    } | null;
}

const props = withDefaults(
    defineProps<{
        shipments?: ShipmentItem[];
    }>(),
    {
        shipments: () => [],
    },
);

defineOptions({
    layout: (h: any, page: any) => page,
});

const handlePrint = () => {
    window.print();
};

const handleClose = () => {
    window.close();
};

const isCod = (shipment: ShipmentItem): boolean => {
    return (shipment.payment_method || '').toLowerCase() === 'cod';
};

const getCodTotal = (shipment: ShipmentItem): number => {
    return Number(shipment.total_amount || 0) + Number(shipment.item_value || 0);
};

onMounted(() => {
    setTimeout(() => {
        window.print();
    }, 400);
});
</script>

<template>
    <div class="min-h-screen bg-stone-100 print:bg-white text-stone-900 print:p-0">
        <Head title="Cetak Resi Pengiriman Massal" />

        <header class="print:hidden sticky top-0 z-50 bg-white border-b border-stone-200 px-4 py-3 sm:px-6 shadow-xs">
            <div class="max-w-4xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-[#853953] flex items-center justify-center text-white font-black text-sm">
                            SK
                        </div>
                        <div>
                            <h1 class="text-base font-bold text-stone-900 leading-tight">Cetak Resi Pengiriman Massal</h1>
                            <p class="text-xs text-stone-500">Format label thermal 100 × 150 mm</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-stone-100 text-stone-700 border border-stone-200">
                        {{ shipments.length }} Label Siap Cetak
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="handleClose"
                        class="h-9 px-3.5 text-xs font-semibold border-stone-200 text-stone-700 hover:bg-stone-50 cursor-pointer shadow-2xs"
                    >
                        Tutup
                    </Button>
                    <Button
                        type="button"
                        @click="handlePrint"
                        class="h-9 px-4 text-xs font-semibold bg-[#853953] hover:bg-[#6c2e43] text-white shadow-xs cursor-pointer inline-flex items-center gap-1.5"
                    >
                        <Printer class="size-4" />
                        <span>Cetak Sekarang</span>
                    </Button>
                </div>
            </div>
        </header>

        <main class="py-6 print:py-0">
            <div v-if="shipments.length === 0" class="max-w-md mx-auto my-12 p-8 text-center bg-white rounded-lg border border-stone-200 shadow-xs">
                <p class="text-sm font-semibold text-stone-700">Tidak ada resi pengiriman untuk dicetak.</p>
                <p class="text-xs text-stone-500 mt-1">Pastikan Anda telah memilih pengiriman yang valid dan sudah dibayar.</p>
                <Button
                    type="button"
                    variant="outline"
                    @click="handleClose"
                    class="mt-4 text-xs font-semibold border-stone-200 text-stone-700 hover:bg-stone-50 cursor-pointer"
                >
                    Tutup Jendela
                </Button>
            </div>

            <div v-else class="print:m-0">
                <div
                    v-for="(shipment, index) in shipments"
                    :key="shipment.id || index"
                    class="thermal-label-page break-after-page w-[100mm] min-h-[150mm] p-4 mx-auto border-2 border-black bg-white text-black font-sans text-xs mb-6 print:mb-0 shadow-sm print:shadow-none"
                >
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
                            <div class="font-bold text-xs mt-0.5">{{ shipment.store?.name || 'Toko Pengirim' }}</div>
                            <div>{{ shipment.store?.phone || '-' }}</div>
                            <div class="line-clamp-3 mt-0.5">{{ shipment.store?.address || '-' }}</div>
                            <div class="font-bold mt-1">{{ shipment.store?.area_name || '' }}</div>
                        </div>

                        <div class="pl-1">
                            <div class="font-bold underline">PENERIMA (KEPADA):</div>
                            <div class="font-bold text-xs mt-0.5">{{ shipment.recipient_name }}</div>
                            <div>{{ shipment.recipient_phone }}</div>
                            <div class="line-clamp-3 mt-0.5">{{ shipment.destination_address }}</div>
                            <div class="font-bold mt-1">
                                {{ shipment.destination_area_name || '' }} ({{ shipment.destination_postal_code || '-' }})
                            </div>
                        </div>
                    </div>

                    <div class="py-2 border-b border-black flex justify-between text-[10px]">
                        <div>
                            <div><strong>Berat:</strong> {{ shipment.weight }} gram</div>
                            <div><strong>Isi:</strong> {{ shipment.item_description || 'Retail Item' }}</div>
                            <div v-if="shipment.insurance_fee && shipment.insurance_fee > 0" class="text-[9px] text-stone-700 italic mt-0.5">
                                *Termasuk Asuransi Pengiriman
                            </div>
                        </div>
                        <div class="text-right">
                            <div><strong>Kode Transaksi:</strong> {{ shipment.order_number }}</div>
                            <div v-if="isCod(shipment)" class="font-black text-xs mt-1 text-black">
                                TAGIHAN COD: Rp {{ getCodTotal(shipment).toLocaleString('id-ID') }}
                            </div>
                            <div v-else class="font-bold text-xs mt-1">NON-COD / LUNAS</div>
                        </div>
                    </div>

                    <div class="pt-2 text-center text-[8px] text-stone-600">
                        Dicetak otomatis via SatuKirim. Kurir wajib melakukan scan barcode saat serah terima barang.
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<style>
@media print {
    @page {
        size: 100mm 150mm;
        margin: 0;
    }
    body {
        background-color: white !important;
        color: black !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .thermal-label-page {
        page-break-after: always;
        break-after: page;
        border: none !important;
        width: 100mm !important;
        min-height: 150mm !important;
        padding: 8mm !important;
        box-sizing: border-box !important;
    }
    .thermal-label-page:last-child {
        page-break-after: avoid;
        break-after: auto;
    }
}
</style>
