<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowDownToLine,
    Banknote,
    Building2,
    Check,
    Clock,
    Copy,
    Eye,
    Wallet,
} from '@lucide/vue';
import { ref } from 'vue';
import AppTopBar from '@/components/AppTopBar.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TablePagination,
    TableRow,
} from '@/components/ui/table';
import {
    excerptText,
    formatDateTime,
    formatShortDestination,
    getCourierLogo,
} from '@/lib/status';

export interface CodShipmentItem {
    id: number;
    order_number: string;
    waybill_number: string | null;
    status: string;
    recipient_name: string;
    destination_address?: string | null;
    destination_area_name: string | null;
    courier_code?: string;
    courier_service_name: string;
    item_description: string | null;
    item_value: number;
    shipping_fee: number;
    total_amount: number;
    created_at: string;
    store?: {
        id: number;
        name: string;
    };
}

export interface WalletData {
    id: number;
    user_id: number;
    available_balance: number;
    pending_balance: number;
    total_withdrawn: number;
}

const props = defineProps<{
    wallet?: WalletData;
    availableBalance: number;
    pendingBalance: number;
    totalWithdrawn?: number;
    completedCount: number;
    pendingCount: number;
    codShipments: {
        data: CodShipmentItem[];
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    recentWithdrawals?: Array<{
        id: number;
        reference_number: string;
        amount: number;
        status: string;
        created_at: string;
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dompet Saya',
                href: '/wallet',
            },
        ],
    },
});

const copiedOrderId = ref<number | null>(null);
const copiedWaybillId = ref<number | null>(null);

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
    } finally {
        document.body.removeChild(textarea);
    }
    return ok;
};

const copyOrderNumber = async (id: number, orderNumber: string) => {
    if (await copyText(orderNumber)) {
        copiedOrderId.value = id;
        setTimeout(() => {
            if (copiedOrderId.value === id) {
                copiedOrderId.value = null;
            }
        }, 1800);
    }
};

const copyWaybill = async (id: number, waybill: string) => {
    if (await copyText(waybill)) {
        copiedWaybillId.value = id;
        setTimeout(() => {
            if (copiedWaybillId.value === id) {
                copiedWaybillId.value = null;
            }
        }, 1800);
    }
};

const showWithdrawModal = ref(false);

const openWithdrawModal = () => {
    showWithdrawModal.value = true;
};

const getCodFundStatus = (status: string) => {
    const s = (status || '').toLowerCase().trim();
    if (s === 'delivered') {
        return {
            label: 'Masuk Saldo',
            textClass: 'text-emerald-600',
        };
    }
    if (['cancelled', 'rejected', 'courier_not_found'].includes(s)) {
        return {
            label: 'Dibatalkan',
            textClass: 'text-rose-600',
        };
    }
    if (s === 'returned') {
        return {
            label: 'Retur (Batal)',
            textClass: 'text-amber-700',
        };
    }
    return {
        label: 'Tertahan',
        textClass: 'text-stone-600',
    };
};
</script>

<template>
    <Head title="Dompet Saya - SatuKirim" />

    <AppTopBar title="Dompet Saya" />

    <div class="flex flex-1 flex-col gap-3.5 sm:gap-4 p-3 sm:p-4 lg:p-5 w-full">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 sm:gap-4 items-start">
            <div class="lg:col-span-4 lg:sticky lg:top-4 space-y-2.5 sm:space-y-3">
                <div class="grid grid-cols-2 lg:grid-cols-1 gap-2.5 sm:gap-3">
                    <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-stone-600">
                            <span class="text-xs sm:text-sm font-semibold text-stone-700">Saldo Siap Tarik</span>
                            <div class="size-8 rounded-lg bg-rose-50 flex items-center justify-center text-[#853953] shrink-0">
                                <Wallet class="size-4.5" />
                            </div>
                        </div>
                        <div class="mt-2 sm:mt-2.5">
                            <div class="text-xl sm:text-2xl font-extrabold text-[#2C2C2C] tabular-nums truncate">
                                Rp {{ Number(availableBalance).toLocaleString('id-ID') }}
                            </div>
                            <p class="text-xs text-stone-500 font-medium mt-0.5 truncate">
                                {{ completedCount }} paket telah sampai
                            </p>
                        </div>
                    </div>

                    <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between text-stone-600">
                            <span class="text-xs sm:text-sm font-semibold text-stone-700">Dana Tertahan</span>
                            <div class="size-8 rounded-lg bg-rose-50 flex items-center justify-center text-[#853953] shrink-0">
                                <Clock class="size-4.5" />
                            </div>
                        </div>
                        <div class="mt-2 sm:mt-2.5">
                            <div class="text-xl sm:text-2xl font-extrabold text-[#2C2C2C] tabular-nums truncate">
                                Rp {{ Number(pendingBalance).toLocaleString('id-ID') }}
                            </div>
                            <p class="text-xs text-stone-500 font-medium mt-0.5 truncate">
                                {{ pendingCount }} dalam pengiriman
                            </p>
                        </div>
                    </div>
                </div>

                <Button
                    type="button"
                    @click="openWithdrawModal"
                    class="w-full bg-[#853953] hover:bg-[#6c2e43] text-white font-semibold text-xs sm:text-sm h-10 rounded-xl cursor-pointer shadow-xs inline-flex items-center justify-center gap-2"
                >
                    <ArrowDownToLine class="size-4" />
                    <span>Tarik Saldo</span>
                </Button>
            </div>

            <div class="lg:col-span-8 w-full min-w-0">
                <div class="rounded-xl border border-stone-200/80 bg-white shadow-xs overflow-hidden">
                    <div class="px-3.5 sm:px-4 py-3 border-b border-stone-200 bg-stone-50/60 flex items-center justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-sm sm:text-base font-bold text-stone-900 truncate">Riwayat Pengiriman COD</h2>
                        </div>
                        <span class="text-sm font-semibold text-stone-600 shrink-0 tabular-nums">
                            Total {{ codShipments.total }} Transaksi
                        </span>
                    </div>

                    <div v-if="!codShipments.data || codShipments.data.length === 0" class="flex flex-col items-center justify-center py-12 px-4 text-center space-y-3">
                        <div class="size-12 mx-auto rounded-full bg-rose-50 flex items-center justify-center text-[#853953]">
                            <Banknote class="size-6" />
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-sm font-bold text-stone-900">Belum Ada Transaksi COD</h3>
                            <p class="text-xs text-stone-500 max-w-sm mx-auto">
                                Saat membuat pengiriman baru, pilih metode pembayaran "COD (Bayar di Tempat)" agar pembeli membayar saat paket tiba.
                            </p>
                        </div>
                        <Link href="/shipments/create">
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
                                        <span class="text-sm font-bold text-primary truncate">
                                            {{ item.order_number }}
                                        </span>
                                        <button
                                            type="button"
                                            @click.stop.prevent="copyOrderNumber(item.id, item.order_number)"
                                            class="text-stone-400 hover:text-primary transition-colors cursor-pointer shrink-0"
                                            title="Salin Kode Transaksi"
                                        >
                                            <Check v-if="copiedOrderId === item.id" class="size-3.5 text-emerald-600" />
                                            <Copy v-else class="size-3.5" />
                                        </button>
                                        <span class="text-stone-300 shrink-0">•</span>
                                        <span class="text-xs text-stone-500 truncate">
                                            {{ formatDateTime(item.created_at) }}
                                        </span>
                                    </div>

                                    <span
                                        class="text-xs font-bold shrink-0 whitespace-nowrap"
                                        :class="getCodFundStatus(item.status).textClass"
                                    >
                                        {{ getCodFundStatus(item.status).label }}
                                    </span>
                                </div>

                                <div class="flex items-baseline justify-between gap-2 mt-1.5">
                                    <div class="text-sm font-semibold text-stone-900 truncate">
                                        {{ item.recipient_name }}
                                    </div>
                                    <div class="text-sm font-extrabold text-stone-900 tabular-nums shrink-0 text-right">
                                        Rp {{ Math.max(0, Number(item.item_value) - 1000).toLocaleString('id-ID') }}
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-2 text-xs text-stone-500 mt-0.5">
                                    <span class="truncate">
                                        {{ formatShortDestination(item.destination_area_name, item.destination_address || null) }} · {{ item.courier_service_name }}
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
                                                class="text-sm font-bold text-primary hover:underline transition-colors"
                                            >
                                                {{ item.order_number }}
                                            </Link>
                                            <button
                                                type="button"
                                                @click="copyOrderNumber(item.id, item.order_number)"
                                                class="text-stone-400 hover:text-primary transition-colors cursor-pointer"
                                                title="Salin Kode Transaksi"
                                            >
                                                <Check v-if="copiedOrderId === item.id" class="size-3.5 text-emerald-600" />
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
                                            <span class="text-sm font-bold text-primary">
                                                {{ item.waybill_number }}
                                            </span>
                                            <button
                                                type="button"
                                                @click="copyWaybill(item.id, item.waybill_number)"
                                                class="text-stone-400 hover:text-primary transition-colors cursor-pointer"
                                                title="Salin nomor resi"
                                            >
                                                <Check v-if="copiedWaybillId === item.id" class="size-3.5 text-emerald-600" />
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
                                        <span
                                            class="text-sm font-bold"
                                            :class="getCodFundStatus(item.status).textClass"
                                        >
                                            {{ getCodFundStatus(item.status).label }}
                                        </span>
                                    </TableCell>

                                    <TableCell class="pr-4 text-center">
                                        <div class="inline-flex items-center justify-center">
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
            </div>
        </div>

        <Dialog :open="showWithdrawModal" @update:open="showWithdrawModal = $event">
            <DialogContent class="sm:max-w-md bg-white p-5 sm:p-6 rounded-xl border border-stone-200 shadow-xl">
                <DialogHeader class="text-left space-y-2">
                    <div class="size-11 rounded-full bg-rose-50 text-[#853953] flex items-center justify-center mb-1">
                        <Building2 class="size-6" />
                    </div>
                    <DialogTitle class="text-base sm:text-lg font-bold text-stone-900">
                        Fitur Tarik Saldo Belum Tersedia
                    </DialogTitle>
                    <DialogDescription class="text-xs sm:text-sm text-stone-600 leading-relaxed">
                        Modul pemilihan rekening bank dan pencairan dana otomatis (<strong class="font-semibold text-stone-800">Disbursement API / BI-FAST</strong>) saat ini sedang dalam tahap pengujian kepatuhan integrasi mitra perbankan.
                    </DialogDescription>
                </DialogHeader>

                <div class="my-3 p-3.5 rounded-lg bg-stone-50 border border-stone-200/80 space-y-2 text-xs text-stone-700">
                    <div class="flex justify-between items-center pb-2 border-b border-stone-200/60">
                        <span class="text-stone-500 font-medium">Saldo Siap Tarik Anda:</span>
                        <span class="font-extrabold text-sm text-primary tabular-nums">
                            Rp {{ Number(availableBalance).toLocaleString('id-ID') }}
                        </span>
                    </div>
                    <p class="text-xs text-stone-500 leading-normal">
                        Seluruh saldo COD Anda tetap tersimpan dengan aman di sistem SatuKirim dan dapat langsung dicairkan begitu integrasi resmi diaktifkan.
                    </p>
                </div>

                <DialogFooter class="sm:justify-end gap-2">
                    <Button
                        type="button"
                        @click="showWithdrawModal = false"
                        class="w-full sm:w-auto bg-[#853953] hover:bg-[#6c2e43] text-white text-xs sm:text-sm font-semibold cursor-pointer"
                    >
                        Saya Mengerti
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
