<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowDownToLine,
    Banknote,
    Building2,
    Check,
    Clock,
    Copy,
    Eye,
    Loader2,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import AppTopBar from '@/components/AppTopBar.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
        notes?: string | null;
        created_at: string;
    }>;
    supportedBanks?: Record<string, string>;
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

const page = usePage();
const authUser = computed(() => (page.props as any).auth?.user);

const withdrawForm = useForm({
    bank_code: 'BCA',
    account_number: '',
    account_holder_name: '',
    amount: null as number | null,
});

const errorMessage = ref<string | null>(null);

const showWithdrawModal = ref(false);

const openWithdrawModal = () => {
    errorMessage.value = null;
    withdrawForm.clearErrors();
    if (!withdrawForm.account_holder_name && authUser.value?.name) {
        withdrawForm.account_holder_name = authUser.value.name;
    }
    showWithdrawModal.value = true;
};

const submitWithdrawal = () => {
    errorMessage.value = null;
    withdrawForm.post('/wallet/withdraw', {
        preserveScroll: true,
        onSuccess: () => {
            showWithdrawModal.value = false;
            withdrawForm.reset('amount');
        },
        onError: (errors) => {
            if (errors.general) {
                errorMessage.value = errors.general;
            }
        },
    });
};

const getCodFundStatus = (status: string) => {
    const s = (status || '').toLowerCase().trim();
    if (s === 'delivered') {
        return {
            label: 'Masuk Saldo',
        };
    }
    if (['cancelled', 'rejected', 'courier_not_found'].includes(s)) {
        return {
            label: 'Dibatalkan',
        };
    }
    if (s === 'returned') {
        return {
            label: 'Retur (Batal)',
        };
    }
    return {
        label: 'Tertahan',
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
                            <div class="text-xl sm:text-2xl font-bold text-[#2C2C2C] tabular-nums truncate">
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
                            <div class="text-xl sm:text-2xl font-bold text-[#2C2C2C] tabular-nums truncate">
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

                <div v-if="recentWithdrawals && recentWithdrawals.length > 0" class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs space-y-2.5">
                    <div class="flex items-center justify-between pb-2 border-b border-stone-100">
                        <span class="text-xs sm:text-sm font-bold text-stone-900">Riwayat Penarikan</span>
                        <span class="text-xs text-stone-500 tabular-nums">{{ recentWithdrawals.length }} Terakhir</span>
                    </div>
                    <div class="space-y-2">
                        <div
                            v-for="wd in recentWithdrawals"
                            :key="wd.id"
                            class="p-2.5 rounded-lg bg-stone-50/80 border border-stone-200/60 flex items-center justify-between text-xs"
                        >
                            <div class="min-w-0 pr-2">
                                <span class="font-bold text-stone-900 block truncate tabular-nums text-xs sm:text-sm">
                                    Rp {{ Number(wd.amount).toLocaleString('id-ID') }}
                                </span>
                                <span class="text-xs text-stone-500 font-mono block truncate">
                                    {{ wd.reference_number }}
                                </span>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-xs font-bold text-primary block">
                                    {{ wd.status === 'completed' ? 'Selesai' : wd.status === 'failed' ? 'Gagal' : 'Diproses' }}
                                </span>
                                <span class="text-xs text-stone-400 block mt-0.5">
                                    {{ formatDateTime(wd.created_at) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
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

                                    <span class="text-xs font-bold text-primary shrink-0 whitespace-nowrap">
                                        {{ getCodFundStatus(item.status).label }}
                                    </span>
                                </div>

                                <div class="flex items-baseline justify-between gap-2 mt-1.5">
                                    <div class="text-sm font-semibold text-stone-900 truncate">
                                        {{ item.recipient_name }}
                                    </div>
                                    <div class="text-sm font-semibold text-stone-900 tabular-nums shrink-0 text-right">
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
                                        <span class="text-sm font-bold text-primary">
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
            <DialogContent
                @pointer-down-outside="(e) => e.preventDefault()"
                @interact-outside="(e) => e.preventDefault()"
                class="sm:max-w-2xl md:max-w-3xl lg:max-w-4xl bg-white p-5 sm:p-6 rounded-xl max-h-[90vh] overflow-y-auto"
            >
                <DialogHeader class="text-left">
                    <DialogTitle class="text-base sm:text-lg font-bold text-stone-900">
                        Tarik Saldo Rekening
                    </DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitWithdrawal" class="space-y-4">
                    <div
                        v-if="withdrawForm.errors.general || errorMessage"
                        class="p-3 rounded-lg bg-rose-50 text-xs text-rose-700 flex items-start gap-2"
                    >
                        <AlertCircle class="size-4 shrink-0 mt-0.5" />
                        <div>{{ withdrawForm.errors.general || errorMessage }}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-start">
                        <div class="md:col-span-7 space-y-3.5">
                            <div class="p-3.5 rounded-xl bg-stone-50 flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-medium text-stone-500 block">Saldo Siap Tarik</span>
                                    <span class="text-base sm:text-lg font-bold text-[#853953] tabular-nums">
                                        Rp {{ Number(availableBalance).toLocaleString('id-ID') }}
                                    </span>
                                </div>
                                <span class="text-xs font-medium text-stone-400">
                                    Min. Rp 10.000
                                </span>
                            </div>

                            <div class="space-y-1">
                                <Label for="withdraw-bank" class="text-xs sm:text-sm font-semibold text-stone-700">
                                    Bank Tujuan<span class="text-red-500 ml-1">*</span>
                                </Label>
                                <select
                                    id="withdraw-bank"
                                    v-model="withdrawForm.bank_code"
                                    class="h-10 text-xs sm:text-sm bg-white border border-stone-200 rounded-lg px-3 w-full text-stone-900 focus:outline-none focus:ring-2 focus:ring-[#853953]/20 focus:border-[#853953]"
                                    required
                                >
                                    <option
                                        v-for="(bankName, code) in (supportedBanks || {
                                            BCA: 'Bank Central Asia (BCA)',
                                            MANDIRI: 'Bank Mandiri',
                                            BRI: 'Bank Rakyat Indonesia (BRI)',
                                            BNI: 'Bank Negara Indonesia (BNI)',
                                            BSI: 'Bank Syariah Indonesia (BSI)',
                                            CIMB: 'CIMB Niaga',
                                            PERMATA: 'Bank Permata',
                                            JAGO: 'Bank Jago',
                                            SEABANK: 'SeaBank Indonesia',
                                            BNC: 'Bank Neo Commerce (BNC)',
                                        })"
                                        :key="code"
                                        :value="code"
                                    >
                                        {{ bankName }}
                                    </option>
                                </select>
                                <InputError :message="withdrawForm.errors.bank_code" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <Label for="withdraw-account-number" class="text-xs sm:text-sm font-semibold text-stone-700">
                                        Nomor Rekening<span class="text-red-500 ml-1">*</span>
                                    </Label>
                                    <Input
                                        id="withdraw-account-number"
                                        v-model="withdrawForm.account_number"
                                        type="text"
                                        inputmode="numeric"
                                        placeholder="1234567890"
                                        class="h-10 text-xs sm:text-sm bg-white border-stone-200"
                                        required
                                    />
                                    <InputError :message="withdrawForm.errors.account_number" />
                                </div>

                                <div class="space-y-1">
                                    <Label for="withdraw-account-name" class="text-xs sm:text-sm font-semibold text-stone-700">
                                        Nama Pemilik Rekening<span class="text-red-500 ml-1">*</span>
                                    </Label>
                                    <Input
                                        id="withdraw-account-name"
                                        v-model="withdrawForm.account_holder_name"
                                        type="text"
                                        placeholder="Sesuai buku tabungan"
                                        class="h-10 text-xs sm:text-sm bg-white border-stone-200"
                                        required
                                    />
                                    <InputError :message="withdrawForm.errors.account_holder_name" />
                                </div>
                            </div>

                            <div class="space-y-1">
                                <Label for="withdraw-amount" class="text-xs sm:text-sm font-semibold text-stone-700">
                                    Nominal Penarikan (Rp)<span class="text-red-500 ml-1">*</span>
                                </Label>
                                <Input
                                    id="withdraw-amount"
                                    v-model.number="withdrawForm.amount"
                                    type="number"
                                    min="10000"
                                    :max="availableBalance"
                                    placeholder="Minimal Rp 10.000"
                                    class="h-10 text-xs sm:text-sm bg-white border-stone-200 font-medium"
                                    required
                                />
                                <InputError :message="withdrawForm.errors.amount" />

                                <div class="flex flex-wrap gap-1.5 pt-0.5">
                                    <button
                                        type="button"
                                        v-for="nominal in [25000, 50000, 100000, 250000]"
                                        :key="nominal"
                                        @click="withdrawForm.amount = nominal"
                                        :disabled="nominal > availableBalance"
                                        :class="[
                                            'text-xs px-2.5 py-1 rounded-lg border font-medium transition-colors cursor-pointer',
                                            withdrawForm.amount === nominal
                                                ? 'bg-[#853953] text-white border-[#853953]'
                                                : 'bg-stone-50 border-stone-200 text-stone-600 hover:bg-stone-100 disabled:opacity-40 disabled:cursor-not-allowed'
                                        ]"
                                    >
                                        Rp {{ Number(nominal).toLocaleString('id-ID') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-5 flex flex-col justify-between h-full gap-4">
                            <div class="p-4 rounded-xl bg-stone-50 space-y-3">
                                <div class="text-xs font-bold text-stone-700 uppercase tracking-wider">
                                    Rincian Penarikan
                                </div>

                                <div class="space-y-2 text-xs sm:text-sm">
                                    <div class="flex justify-between items-center text-stone-600">
                                        <span>Nominal Ditarik</span>
                                        <span class="font-bold text-stone-900 tabular-nums">
                                            Rp {{ Number(withdrawForm.amount || 0).toLocaleString('id-ID') }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center text-stone-600">
                                        <span>Biaya Admin</span>
                                        <span class="font-semibold text-stone-900">Bebas Biaya (Rp 0)</span>
                                    </div>
                                    <div class="flex justify-between items-center text-stone-600">
                                        <span>Waktu Pemrosesan</span>
                                        <span class="font-semibold text-stone-900">Realtime / Instan</span>
                                    </div>
                                </div>

                                <div class="p-3 rounded-lg bg-white flex items-center justify-between">
                                    <span class="text-xs sm:text-sm font-semibold text-stone-900">Sisa Saldo</span>
                                    <span class="text-base sm:text-lg font-bold text-primary tabular-nums">
                                        Rp {{ Math.max(0, availableBalance - (Number(withdrawForm.amount) || 0)).toLocaleString('id-ID') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="showWithdrawModal = false"
                            :disabled="withdrawForm.processing"
                            class="text-xs sm:text-sm font-semibold h-9 sm:h-10 px-4 cursor-pointer border-stone-200 text-stone-700 hover:bg-stone-50 shadow-2xs"
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            :disabled="withdrawForm.processing || !withdrawForm.amount || withdrawForm.amount > availableBalance || withdrawForm.amount < 10000"
                            class="bg-[#853953] hover:bg-[#6c2e43] text-white text-xs sm:text-sm font-semibold h-9 sm:h-10 px-5 cursor-pointer inline-flex items-center gap-1.5 shadow-xs"
                        >
                            <Loader2 v-if="withdrawForm.processing" class="mr-2 size-4 animate-spin" />
                            <span>Konfirmasi Tarik Saldo</span>
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
