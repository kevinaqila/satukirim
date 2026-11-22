<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Printer } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ShipmentThermalLabel, { type PrintableShipment } from '@/components/shipments/ShipmentThermalLabel.vue';
import { Button } from '@/components/ui/button';

export type ShipmentItem = PrintableShipment;

withDefaults(
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
</script>

<template>
    <div class="min-h-screen bg-stone-100 print:min-h-0 print:bg-white text-stone-900 print:p-0">
        <Head title="Cetak Resi Thermal" />

        <div class="print:hidden sticky top-0 z-50 bg-white border-b border-stone-200 px-4 py-3 sm:px-6 shadow-xs">
            <div class="max-w-4xl mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3 flex-wrap">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-lg bg-stone-50 border border-stone-200 flex items-center justify-center shrink-0">
                            <AppLogoIcon variant="color" class="w-6 h-[18px]" />
                        </div>
                        <div>
                            <h1 class="text-base font-bold text-stone-900 leading-tight">Cetak Resi Thermal</h1>
                            <p class="text-xs text-stone-500">Format stiker thermal 100 × 150 mm (1 Resi / Halaman)</p>
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
                        :disabled="shipments.length === 0"
                        class="h-9 px-4 text-xs font-semibold bg-[#853953] hover:bg-[#6c2e43] text-white shadow-xs cursor-pointer inline-flex items-center gap-1.5"
                    >
                        <Printer class="size-4" />
                        <span>Cetak Sekarang</span>
                    </Button>
                </div>
            </div>
        </div>

        <main class="py-6 px-3 print:p-0">
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

            <div v-else class="space-y-6 print:space-y-0">
                <div
                    v-for="(shipment, index) in shipments"
                    :key="shipment.id || index"
                    class="print-thermal-sheet w-[94mm] h-[144mm] mx-auto bg-white shadow-sm print:w-full print:h-[96vh] print:p-[1px] print:m-0 print:shadow-none"
                >
                    <ShipmentThermalLabel :shipment="shipment" />
                </div>
            </div>
        </main>
    </div>
</template>

<style>
@media print {
    @page {
        size: 100mm 150mm;
        margin: 3mm;
    }

    html,
    body {
        margin: 0 !important;
        padding: 0 !important;
        background-color: white !important;
        color: black !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .print-thermal-sheet {
        page-break-after: always;
        break-after: page;
        break-inside: avoid;
    }

    .print-thermal-sheet:last-child {
        page-break-after: avoid;
        break-after: auto;
    }
}
</style>
