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
import ShipmentThermalLabel from '@/components/shipments/ShipmentThermalLabel.vue';
import ShipmentTrackingTimeline from '@/components/shipments/ShipmentTrackingTimeline.vue';
import { Button } from '@/components/ui/button';
import { useCopyClipboard } from '@/composables/useCopyClipboard';
import {
    formatDateTime,
    formatWeight,
    getShipmentStatus,
} from '@/lib/status';
import type { ShipmentDetail } from '@/types/shipments';

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

const { copy, isCopied } = useCopyClipboard();

const isPaying = ref(false);
const isCheckingStatus = ref(false);

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

const printLabel = () => {
    window.print();
};

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
                        @click="copy('order', shipment.order_number)"
                        class="text-stone-400 hover:text-stone-700 cursor-pointer p-0.5 rounded transition-colors"
                        title="Salin Kode Transaksi"
                    >
                        <Check v-if="isCopied('order')" class="size-4 text-emerald-600" />
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
                    class="bg-amber-50 border border-stone-200/80 rounded-xl p-4 md:p-5 shadow-xs"
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

                <div class="bg-white border border-stone-200/80 rounded-xl p-3.5 sm:p-4 shadow-xs">
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

                <div class="bg-white border border-stone-200/80 rounded-xl p-4 md:p-5 shadow-xs">
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
                <ShipmentTrackingTimeline :shipment="shipment" />
            </div>
        </div>

        <ShipmentThermalLabel :shipment="shipment" />
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
