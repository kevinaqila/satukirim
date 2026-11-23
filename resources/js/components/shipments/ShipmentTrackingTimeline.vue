<script setup lang="ts">
import { Check } from '@lucide/vue';
import { computed } from 'vue';
import { useCopyClipboard } from '@/composables/useCopyClipboard';
import { formatTrackingNote, getCourierLogo, getShipmentStatus } from '@/lib/status';
import type { ShipmentDetail } from '@/types/shipments';

const props = defineProps<{
    shipment: ShipmentDetail;
}>();

const { copy, isCopied } = useCopyClipboard();

const isCod = computed(() => (props.shipment.payment_method || '').toLowerCase() === 'cod');

const formatTrackingTime = (dateStr?: string | null): string => {
    if (!dateStr) return '';
    try {
        let d = new Date(dateStr);
        if (isNaN(d.getTime()) && dateStr.includes(' ')) {
            d = new Date(dateStr.replace(' ', 'T'));
        }
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateStr;
    }
};

const trackingSteps = computed(() => {
    const history = props.shipment.tracking_history;

    if (history && Array.isArray(history) && history.length > 0) {
        const steps: Array<{
            title: string;
            note: string;
            time: string | null;
            active: boolean;
            done: boolean;
            variant?: string;
        }> = [];

        steps.push({
            title: 'Pesanan Dibuat',
            note: 'Pesanan berhasil dibuat di sistem SatuKirim',
            time: props.shipment.created_at,
            active: true,
            done: true,
            variant: 'neutral',
        });

        if (isCod.value) {
            steps.push({
                title: 'Konfirmasi Sistem COD',
                note: 'Metode Bayar di Tempat (COD) telah terverifikasi sistem',
                time: props.shipment.paid_at || props.shipment.created_at,
                active: true,
                done: true,
                variant: 'info',
            });
        } else {
            const isPaid = Boolean(props.shipment.paid_at || props.shipment.status !== 'pending_payment');
            steps.push({
                title: 'Pembayaran Ongkir',
                note: isPaid
                    ? 'Pembayaran ongkos kirim telah terverifikasi'
                    : 'Menunggu konfirmasi pembayaran dari pengirim',
                time: props.shipment.paid_at || null,
                active: isPaid,
                done: isPaid,
                variant: isPaid ? 'info' : 'warning',
            });
        }

        history.forEach((item, idx) => {
            const statusConfig = getShipmentStatus(item.status);
            const isLast = idx === history.length - 1;
            const isDelivered = props.shipment.status === 'delivered';
            const isDone = isDelivered || !isLast;
            const stepTitle = item.status === 'paid' ? 'Penjemputan Terkonfirmasi' : statusConfig.label;

            steps.push({
                title: stepTitle,
                note: formatTrackingNote(item.note) || `Status kurir: ${stepTitle}`,
                time: item.timestamp || null,
                active: true,
                done: isDone,
                variant: statusConfig.variant,
            });
        });

        const hasDeliveredInHistory = history.some((h) => h.status === 'delivered');
        if (props.shipment.status === 'delivered' && !hasDeliveredInHistory) {
            steps.push({
                title: 'Paket Diterima',
                note: 'Paket telah berhasil diserahkan ke penerima',
                time: null,
                active: true,
                done: true,
                variant: 'success',
            });
        }

        const hasTerminalInHistory = history.some((h) => ['cancelled', 'rejected', 'courier_not_found'].includes(h.status));
        if (['cancelled', 'rejected', 'courier_not_found'].includes(props.shipment.status) && !hasTerminalInHistory) {
            const statusConfig = getShipmentStatus(props.shipment.status);
            steps.push({
                title: statusConfig.label,
                note: 'Pengiriman dihentikan atau dibatalkan oleh pihak ekspedisi/sistem.',
                time: props.shipment.updated_at || null,
                active: true,
                done: true,
                variant: 'danger',
            });
        }

        return steps;
    }

    if (['cancelled', 'rejected', 'courier_not_found'].includes(props.shipment.status)) {
        const statusConfig = getShipmentStatus(props.shipment.status);
        return [
            {
                title: 'Pesanan Dibuat',
                note: 'Pesanan tercatat di sistem SatuKirim',
                time: props.shipment.created_at,
                active: true,
                done: true,
                variant: 'neutral',
            },
            {
                title: statusConfig.label,
                note: 'Pengiriman gagal diproses ke kurir atau dibatalkan.',
                time: props.shipment.updated_at || null,
                active: true,
                done: true,
                variant: 'danger',
            },
        ];
    }

    return [
        {
            title: 'Pesanan Dibuat',
            note: 'Pesanan tercatat di sistem SatuKirim',
            time: props.shipment.created_at,
            active: true,
            done: true,
            variant: 'neutral',
        },
        {
            title: isCod.value ? 'Konfirmasi Sistem COD' : 'Pembayaran Ongkir',
            note: isCod.value
                ? 'Metode Bayar di Tempat (COD) telah terverifikasi'
                : (props.shipment.paid_at
                    ? 'Pembayaran telah terverifikasi'
                    : 'Menunggu konfirmasi pembayaran'),
            time: props.shipment.paid_at || null,
            active: Boolean(props.shipment.paid_at || isCod.value),
            done: Boolean(props.shipment.paid_at || isCod.value),
            variant: props.shipment.paid_at || isCod.value ? 'info' : 'warning',
        },
        {
            title: props.shipment.status === 'picking_up'
                ? 'Dalam Penjemputan Kurir'
                : (props.shipment.status === 'allocated' ? 'Kurir Ditugaskan' : 'Penjemputan Kurir'),
            note: props.shipment.status === 'picking_up'
                ? `Kurir sedang menuju ${props.shipment.store?.name || 'toko'} untuk mengambil paket.`
                : (props.shipment.waybill_number
                    ? `Kurir dijadwalkan jemput ke ${props.shipment.store?.name || 'toko'}`
                    : 'Menunggu konfirmasi penjemputan'),
            time: null,
            active: ['paid', 'waiting_pickup', 'allocated', 'picking_up', 'in_transit', 'delivered'].includes(props.shipment.status),
            done: ['in_transit', 'delivered'].includes(props.shipment.status),
            variant: 'info',
        },
        {
            title: 'Dalam Perjalanan',
            note: props.shipment.waybill_number
                ? 'Paket bergerak menuju kota tujuan.'
                : 'Paket belum diberangkatkan',
            time: null,
            active: ['in_transit', 'delivered'].includes(props.shipment.status),
            done: props.shipment.status === 'delivered',
            variant: 'primary',
        },
        {
            title: 'Paket Diterima',
            note: props.shipment.status === 'delivered'
                ? 'Paket telah diterima dengan sukses'
                : `Estimasi tiba ${props.shipment.estimated_delivery || '1-3 hari'}`,
            time: null,
            active: props.shipment.status === 'delivered',
            done: props.shipment.status === 'delivered',
            variant: 'success',
        },
    ];
});
</script>

<template>
    <div class="bg-white border border-stone-200/80 rounded-xl p-4 md:p-5 shadow-xs">
        <div class="flex items-center justify-between pb-3 border-b border-stone-100">
            <div class="flex items-center gap-2.5">
                <div class="h-7 w-12 bg-white rounded border border-stone-200/80 p-0.5 flex items-center justify-center shrink-0">
                    <img
                        v-if="getCourierLogo(shipment.courier_code || shipment.courier_service_name)"
                        :src="getCourierLogo(shipment.courier_code || shipment.courier_service_name)!"
                        :alt="shipment.courier_code"
                        class="max-h-5 max-w-full object-contain"
                    />
                    <span v-else class="text-xs font-semibold text-stone-600 uppercase">
                        {{ shipment.courier_code }}
                    </span>
                </div>
                <div>
                    <div class="text-xs text-stone-400">Ekspedisi</div>
                    <div class="text-sm md:text-base font-semibold text-stone-900">{{ shipment.courier_service_name }}</div>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded text-xs md:text-sm font-semibold bg-stone-100 text-stone-700">
                Estimasi {{ shipment.estimated_delivery || '1-3 Hari' }}
            </span>
        </div>

        <div class="py-2.5 px-3.5 rounded bg-stone-50 text-xs md:text-sm my-3">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-stone-400 block text-xs">Nomor Resi (AWB)</span>
                    <span class="font-semibold text-stone-800">
                        {{ shipment.waybill_number || 'Menunggu Pickup' }}
                    </span>
                </div>
                <button
                    v-if="shipment.waybill_number"
                    type="button"
                    @click="copy('waybill', shipment.waybill_number)"
                    class="text-xs md:text-sm text-[#853953] hover:underline font-semibold cursor-pointer inline-flex items-center gap-1"
                >
                    <Check v-if="isCopied('waybill')" class="size-3.5 text-emerald-600" />
                    <span>{{ isCopied('waybill') ? 'Tersalin' : 'Salin' }}</span>
                </button>
            </div>
        </div>

        <div class="mt-4">
            <div class="text-xs md:text-sm font-semibold text-stone-600 uppercase tracking-wider mb-3">Riwayat Pengiriman</div>

            <div class="space-y-4 relative pl-5 border-l border-stone-200 ml-2">
                <div
                    v-for="(step, idx) in trackingSteps"
                    :key="idx"
                    class="relative"
                    :class="!step.active ? 'opacity-40' : ''"
                >
                    <span
                        class="absolute -left-[25px] top-1 size-3 rounded-full ring-4 ring-white"
                        :class="
                            step.variant === 'danger'
                                ? 'bg-red-500'
                                : (step.variant === 'warning'
                                    ? 'bg-amber-500'
                                    : (step.done ? 'bg-emerald-600' : (step.active ? 'bg-[#853953]' : 'bg-stone-300')))
                        "
                    />
                    <div class="flex items-center justify-between text-xs md:text-sm gap-2">
                        <span class="font-semibold text-stone-900">{{ step.title }}</span>
                        <span v-if="step.time" class="text-stone-400 text-xs shrink-0 font-medium">
                            {{ formatTrackingTime(step.time) }}
                        </span>
                    </div>
                    <p class="text-xs md:text-sm text-stone-500 mt-0.5 leading-relaxed">{{ step.note }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

