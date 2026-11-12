<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    Check,
    CheckCircle2,
    Copy,
    CreditCard,
    ExternalLink,
    Loader2,
    Printer,
    RefreshCw,
    Users,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import AppTopBar from '@/components/AppTopBar.vue';
import { Button } from '@/components/ui/button';
import {
    formatDateTime,
    formatTrackingNote,
    formatWeight,
    getCourierLogo,
    getShipmentStatus,
} from '@/lib/status';

export interface ShipmentDetail {
    id: number;
    order_number: string;
    status: string;
    recipient_name: string;
    recipient_phone: string;
    destination_address: string;
    destination_postal_code: string | null;
    destination_area_id: string;
    destination_area_name: string | null;
    weight: number;
    item_description: string | null;
    item_category: string | null;
    item_value: number;
    items?: Array<{ name: string; category?: string; qty?: number; weight?: number; value?: number }> | null;
    courier_code: string;
    courier_service_code: string;
    courier_service_name: string;
    shipping_fee: number;
    total_amount: number;
    estimated_delivery: string | null;
    waybill_number: string | null;
    tracking_history: Array<{
        status: string;
        note: string;
        timestamp: string;
    }> | null;
    payment_method: string | null;
    paid_at: string | null;
    created_at: string;
    store: {
        id: number;
        name: string;
        phone: string;
        address: string;
        area_name: string | null;
        postal_code: string | null;
    };
    user?: {
        id: number;
        name: string;
        email: string;
        phone?: string | null;
    } | null;
}

const props = defineProps<{
    shipment: ShipmentDetail;
    isSuperAdmin?: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Data Pengiriman',
                href: '/shipments',
            },
            {
                title: 'Detail Pengiriman',
                href: '#',
            },
        ],
    },
});

const isPaying = ref(false);
const isCopied = ref(false);
const isOrderCopied = ref(false);

const isCod = computed(() => (props.shipment.payment_method || '').toLowerCase() === 'cod');
const codRecipientTotal = computed(
    () => Number(props.shipment.total_amount || 0) + Number(props.shipment.item_value || 0),
);

const formatPaymentMethod = (method: string | null): string => {
    if (!method) return 'Xendit';
    const map: Record<string, string> = {
        cod: 'COD (Bayar di Tempat)',
        bca_va: 'BCA Virtual Account',
        bni_va: 'BNI Virtual Account',
        bri_va: 'BRI Virtual Account',
        mandiri_va: 'Mandiri Virtual Account',
        permata_va: 'Permata Virtual Account',
        cimb_va: 'CIMB Niaga VA',
        echannel: 'Mandiri Bill',
        gopay: 'GoPay / QRIS',
        qris: 'QRIS',
        shopeepay: 'ShopeePay',
        dana: 'DANA',
        credit_card: 'Kartu Kredit',
        bank_transfer: 'Transfer Bank',
        cstore: 'Indomaret / Alfamart',
    };
    return map[method.toLowerCase()] || method.replace(/_/g, ' ').toUpperCase();
};

const formatDestinationArea = (area?: string | null, postal?: string | null): string => {
    if (!area) return postal ? `Kode Pos ${postal}` : '';
    const cleanArea = area.trim();
    if (postal && !cleanArea.includes(postal)) {
        return `${cleanArea} (${postal})`;
    }
    return cleanArea;
};

const copyText = async (text: string): Promise<boolean> => {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return true;
        }
    } catch {
    }
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    let ok = false;
    try {
        ok = document.execCommand('copy');
    } catch {
        ok = false;
    }
    document.body.removeChild(textarea);
    return ok;
};

const copyOrderNumber = async () => {
    if (!props.shipment.order_number) return;
    if (await copyText(props.shipment.order_number)) {
        isOrderCopied.value = true;
        setTimeout(() => {
            isOrderCopied.value = false;
        }, 1800);
    }
};

const copyTracking = async () => {
    if (!props.shipment.waybill_number) return;
    if (await copyText(props.shipment.waybill_number)) {
        isCopied.value = true;
        setTimeout(() => {
            isCopied.value = false;
        }, 1800);
    }
};

const printLabel = () => {
    window.print();
};

const isCheckingStatus = ref(false);

const checkPaymentStatus = () => {
    isCheckingStatus.value = true;
    router.post(`/shipments/${props.shipment.id}/check-payment`, {}, {
        onFinish: () => {
            isCheckingStatus.value = false;
        },
    });
};

const handleXenditPayment = async () => {
    isPaying.value = true;

    try {
        const res = await fetch(`/shipments/${props.shipment.id}/pay`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
        });
        const data = await res.json();

        if (data.invoice_url) {
            window.location.href = data.invoice_url;
            return;
        }

        alert(data.message || 'Gagal memuat sesi pembayaran Xendit.');
        checkPaymentStatus();
    } catch {
        checkPaymentStatus();
    } finally {
        isPaying.value = false;
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === '1' && props.shipment.status !== 'pending_payment') {
            setTimeout(() => {
                window.print();
            }, 500);
        }

        if (urlParams.get('pay') === '1' && props.shipment.status === 'pending_payment') {
            window.history.replaceState({}, '', window.location.pathname);
            setTimeout(() => {
                handleXenditPayment();
            }, 200);
        }
    }
});

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

    // Jika memiliki tracking_history riil dari kurir/Biteship
    if (history && Array.isArray(history) && history.length > 0) {
        const steps: Array<{
            title: string;
            note: string;
            time: string | null;
            active: boolean;
            done: boolean;
            variant?: string;
        }> = [];

        // 1. Checkpoint awal: Pesanan Dibuat di SatuKirim
        steps.push({
            title: 'Pesanan Dibuat',
            note: 'Pesanan berhasil dibuat di sistem SatuKirim',
            time: props.shipment.created_at,
            active: true,
            done: true,
            variant: 'neutral',
        });

        // 2. Checkpoint Pembayaran / COD (selalu tampil sebagai milestone internal)
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

        // 3. Masukkan setiap checkpoint riil dari ekspedisi
        history.forEach((item, idx) => {
            const statusConfig = getShipmentStatus(item.status);
            const isLast = idx === history.length - 1;
            const isDelivered = props.shipment.status === 'delivered';
            const isDone = isDelivered || !isLast;

            // Dalam konteks riwayat kurir, status 'paid' berarti order terkonfirmasi di ekspedisi untuk pickup
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

        // 4. Jika status shipment delivered tapi belum ada record delivered di history
        const hasDeliveredInHistory = history.some((h) => h.status === 'delivered');
        if (props.shipment.status === 'delivered' && !hasDeliveredInHistory) {
            steps.push({
                title: 'Terkirim ke Penerima',
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

    // Jika pesanan berstatus batal/gagal tanpa history kurir
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
            title: 'Terkirim ke Penerima',
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
    <Head :title="`Transaksi ${shipment.order_number} - SatuKirim`" />

    <AppTopBar title="Detail Pengiriman" class="print:hidden" />

    <div class="flex h-full flex-1 flex-col gap-3.5 sm:gap-4 p-3 sm:p-4 lg:p-5 w-full print:p-0">
        <div class="flex items-start justify-between gap-3 print:hidden">
            <div class="space-y-1 min-w-0">
                <nav class="hidden md:flex items-center gap-1.5 text-xs md:text-sm text-stone-500">
                    <Link
                        :href="isSuperAdmin ? '/admin/transactions' : '/shipments'"
                        class="hover:text-stone-800 transition-colors"
                    >
                        {{ isSuperAdmin ? 'Data Transaksi (Admin)' : 'Pengiriman' }}
                    </Link>
                    <span>/</span>
                    <span class="text-stone-700 font-medium">{{ shipment.order_number }}</span>
                </nav>

                <div class="flex items-center gap-2">
                    <Link
                        :href="isSuperAdmin ? '/admin/transactions' : '/shipments'"
                        class="md:hidden -ml-1 p-1 text-stone-700 hover:text-stone-900 transition-colors inline-flex items-center justify-center cursor-pointer"
                        :title="isSuperAdmin ? 'Kembali ke Transaksi' : 'Kembali ke Pengiriman'"
                    >
                        <ArrowLeft class="size-5" />
                    </Link>

                    <h1 class="text-lg md:text-2xl font-bold text-stone-900 tracking-tight">
                        Transaksi {{ shipment.order_number }}
                    </h1>
                    <button
                        type="button"
                        @click="copyOrderNumber"
                        class="text-stone-400 hover:text-stone-700 cursor-pointer p-0.5 rounded transition-colors"
                        title="Salin Kode Transaksi"
                    >
                        <Check v-if="isOrderCopied" class="size-4 text-emerald-600" />
                        <Copy v-else class="size-4" />
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs md:text-sm text-stone-500">
                        Dibuat pada {{ formatDateTime(shipment.created_at) }}
                    </p>

                    <div
                        v-if="isSuperAdmin && shipment.user"
                        class="inline-flex items-center gap-1.5 text-xs text-stone-700 bg-stone-100 border border-stone-200/80 px-2 py-0.5 rounded-md"
                    >
                        <Users class="size-3.5 text-stone-500" />
                        <span>Pemilik Paket: <strong class="font-semibold text-stone-900">{{ shipment.user.name }}</strong> ({{ shipment.user.email }})</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col items-end gap-1 shrink-0">
                <Button
                    v-if="shipment.status !== 'pending_payment'"
                    @click="printLabel"
                    variant="outline"
                    size="sm"
                    class="h-8 md:h-9 gap-1.5 text-xs md:text-sm font-semibold bg-white border-stone-200 text-stone-700 hover:bg-stone-50 cursor-pointer shadow-2xs px-2.5 md:px-3"
                >
                    <Printer class="size-3.5 md:size-4 text-stone-500" />
                    <span>Cetak Label Resi</span>
                </Button>

                <div class="flex items-center gap-1.5 mt-0 md:mt-2">
                    <span class="text-sm md:text-base font-bold text-primary tracking-tight">
                        {{ getShipmentStatus(shipment.status).label }}
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start print:hidden">
            <div class="lg:col-span-7 space-y-4">
                <div
                    v-if="shipment.status === 'pending_payment'"
                    class="bg-amber-50 border border-stone-200 rounded-lg p-4 md:p-5 shadow-2xs"
                >
                    <div class="flex items-center justify-between gap-2.5 sm:gap-3">
                        <div class="flex items-center gap-2 sm:gap-2.5 min-w-0">
                            <AlertCircle class="size-5 sm:size-6 md:size-7 text-amber-500 shrink-0" />
                            <div class="min-w-0">
                                <div class="text-[11px] sm:text-xs md:text-sm text-stone-600 font-medium leading-tight truncate">Total Tagihan Ongkir</div>
                                <div class="text-base sm:text-lg md:text-xl font-bold text-stone-900 leading-tight mt-0.5 whitespace-nowrap">
                                    Rp {{ Number(shipment.total_amount).toLocaleString('id-ID') }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                            <Button
                                @click="handleXenditPayment"
                                :disabled="isPaying"
                                class="bg-[#853953] hover:bg-[#6c2e43] text-white text-xs md:text-sm font-semibold h-8.5 sm:h-9 md:h-10 px-2.5 sm:px-4 rounded-md shadow-xs cursor-pointer"
                            >
                                <Loader2 v-if="isPaying" class="mr-1 sm:mr-1.5 size-3.5 sm:size-4 animate-spin" />
                                <CreditCard v-else class="mr-1 sm:mr-1.5 size-3.5 sm:size-4" />
                                Bayar Sekarang
                            </Button>
                            <Button
                                @click="checkPaymentStatus"
                                variant="outline"
                                size="sm"
                                :disabled="isCheckingStatus"
                                class="h-8.5 sm:h-9 md:h-10 px-2 sm:px-3 md:px-4 bg-white border-stone-200 text-stone-700 hover:bg-stone-50 text-xs md:text-sm font-semibold rounded-md cursor-pointer"
                            >
                                <Loader2 v-if="isCheckingStatus" class="mr-1 size-3.5 animate-spin" />
                                <RefreshCw v-else class="mr-1 size-3.5" />
                                Cek Status
                            </Button>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-stone-200/80 rounded-xl p-3.5 sm:p-4 shadow-2xs">
                    <h2 class="text-xs font-semibold text-stone-600 uppercase tracking-wider mb-2">Rute Pengiriman</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-6">
                        <div>
                            <div class="text-xs font-medium text-stone-500 uppercase tracking-wider mb-1">
                                Pengirim (Toko Asal)
                            </div>
                            <div class="text-sm font-semibold text-stone-900 leading-snug">
                                {{ shipment.store?.name || 'Toko Pengirim' }}
                            </div>
                            <div v-if="shipment.store?.phone" class="text-xs text-stone-500 font-medium">
                                {{ shipment.store.phone }}
                            </div>
                            <div class="text-xs text-stone-600 leading-snug mt-1">
                                <div>{{ shipment.store?.address }}</div>
                                <div v-if="shipment.store?.area_name" class="text-stone-400">
                                    {{ shipment.store.area_name }}
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <div class="text-xs font-medium text-stone-500 uppercase tracking-wider">
                                    Penerima (Tujuan)
                                </div>
                                <a
                                    :href="`https://wa.me/${shipment.recipient_phone.replace(/\D/g, '')}`"
                                    target="_blank"
                                    class="text-xs text-[#853953] hover:underline font-semibold inline-flex items-center gap-1 transition-colors"
                                >
                                    <span>Chat WhatsApp</span>
                                    <ExternalLink class="size-3" />
                                </a>
                            </div>
                            <div class="text-sm font-semibold text-stone-900 leading-snug">
                                {{ shipment.recipient_name }}
                            </div>
                            <div class="text-xs text-stone-500 font-medium">
                                {{ shipment.recipient_phone }}
                            </div>
                            <div class="text-xs text-stone-600 leading-snug mt-1">
                                <div>{{ shipment.destination_address }}</div>
                                <div class="text-stone-400">
                                    {{ formatDestinationArea(shipment.destination_area_name, shipment.destination_postal_code) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-stone-200 rounded-lg p-4 md:p-5 shadow-2xs">
                    <h2 class="text-xs md:text-sm font-semibold text-stone-600 uppercase tracking-wider mb-3">Rincian Paket & Ongkir</h2>

                    <div v-if="shipment.items && shipment.items.length > 0" class="rounded bg-stone-50 p-3 mb-3 space-y-2">
                        <div class="flex items-center justify-between text-xs text-stone-500 font-semibold border-b border-stone-200/60 pb-1.5">
                            <span>Rincian Barang ({{ shipment.items.length }})</span>
                            <span>{{ formatWeight(shipment.weight) }} (Rp {{ Number(shipment.item_value || 0).toLocaleString('id-ID') }})</span>
                        </div>
                        <div class="space-y-1.5 text-xs">
                            <div v-for="(it, i) in shipment.items" :key="i" class="flex items-center justify-between">
                                <span class="font-medium text-stone-800">
                                    {{ it.name }} <span class="text-stone-500">({{ it.qty || 1 }}x)</span>
                                </span>
                                <span class="text-stone-600 tabular-nums">
                                    {{ it.weight ? `${it.weight}g, ` : '' }}Rp {{ Number((it.qty || 1) * (it.value || 0)).toLocaleString('id-ID') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="grid grid-cols-3 gap-2 py-2.5 px-3.5 rounded bg-stone-50 text-xs md:text-sm mb-3">
                        <div>
                            <span class="text-stone-400 block text-xs">Berat</span>
                            <span class="font-semibold text-stone-800">{{ formatWeight(shipment.weight) }}</span>
                        </div>
                        <div>
                            <span class="text-stone-400 block text-xs">Nama Barang</span>
                            <span class="font-semibold text-stone-800 truncate block">{{ shipment.item_description || shipment.item_category || 'Barang Umum' }}</span>
                        </div>
                        <div>
                            <span class="text-stone-400 block text-xs">Nilai Barang</span>
                            <span class="font-semibold text-stone-800">Rp {{ Number(shipment.item_value || 0).toLocaleString('id-ID') }}</span>
                        </div>
                    </div>

                    <div class="space-y-2 text-xs md:text-sm pt-2">
                        <div class="flex justify-between text-stone-600">
                            <span>Biaya Pengiriman</span>
                            <span class="font-medium text-stone-900 tabular-nums">
                                Rp {{ Number(shipment.shipping_fee).toLocaleString('id-ID') }}
                            </span>
                        </div>
                        <div v-if="!isCod" class="flex justify-between text-stone-600">
                            <span>Biaya Layanan Aplikasi</span>
                            <span class="font-medium text-stone-900 tabular-nums">
                                Rp {{ Number(shipment.platform_fee || 1000).toLocaleString('id-ID') }}
                            </span>
                        </div>
                        <div class="pt-2 border-t border-stone-200">
                            <template v-if="!isCod">
                                <div class="flex justify-between items-baseline text-sm md:text-base font-bold text-stone-900">
                                    <span>Total Pembayaran</span>
                                    <span class="text-[#853953] tabular-nums">
                                        Rp {{ Number(shipment.total_amount).toLocaleString('id-ID') }}
                                    </span>
                                </div>
                                <div v-if="shipment.paid_at" class="flex flex-wrap items-center justify-between gap-1 text-xs text-stone-500 mt-1.5">
                                    <span class="inline-flex items-center gap-1 text-[#853953] font-medium">
                                        <CheckCircle2 class="size-3.5 text-[#853953]" />
                                        Lunas via {{ formatPaymentMethod(shipment.payment_method) }}
                                    </span>
                                    <span class="text-stone-400">{{ formatDateTime(shipment.paid_at) }}</span>
                                </div>
                            </template>

                            <template v-else>
                                <div class="flex justify-between items-baseline text-sm md:text-base font-bold text-stone-900">
                                    <span>Tagihan ke Penerima (COD)</span>
                                    <span class="text-[#853953] tabular-nums">
                                        Rp {{ codRecipientTotal.toLocaleString('id-ID') }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center text-xs text-stone-600 mt-1">
                                    <span>Nilai Barang (Kotor):</span>
                                    <span class="font-medium text-stone-800 tabular-nums">
                                        Rp {{ Number(shipment.item_value || 0).toLocaleString('id-ID') }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center text-xs text-stone-600 mt-0.5">
                                    <span>Potongan Biaya Layanan:</span>
                                    <span class="font-medium text-rose-600 tabular-nums">
                                        -Rp {{ Number(shipment.platform_fee || 1000).toLocaleString('id-ID') }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center text-xs font-semibold text-stone-800 mt-1 pt-1 border-t border-dashed border-stone-200">
                                    <span>Hak Bersih Penjual (Saldo COD):</span>
                                    <span class="font-semibold text-stone-900 tabular-nums">
                                        Rp {{ Math.max(0, Number(shipment.item_value || 0) - (shipment.platform_fee || 1000)).toLocaleString('id-ID') }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-1 text-xs text-stone-500 mt-1.5">
                                    <span class="inline-flex items-center gap-1 text-[#853953] font-medium">
                                        <CheckCircle2 class="size-3.5 text-[#853953]" />
                                        Metode COD (Ditagih Tunai oleh Kurir)
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5 lg:sticky lg:top-4 space-y-4">
                <div class="bg-white border border-stone-200 rounded-lg p-4 md:p-5 shadow-2xs">
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
                                @click="copyTracking"
                                class="text-xs md:text-sm text-[#853953] hover:underline font-semibold cursor-pointer inline-flex items-center gap-1"
                            >
                                <Check v-if="isCopied" class="size-3.5 text-emerald-600" />
                                <span>{{ isCopied ? 'Tersalin' : 'Salin' }}</span>
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
            </div>
        </div>

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
    </div>
</template>

<style>
@media print {
    body {
        background-color: white !important;
        color: black !important;
    }
    nav, header, footer, aside, .sidebar {
        display: none !important;
    }
}
</style>
