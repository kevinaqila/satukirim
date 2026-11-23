<script setup lang="ts">
import {
    Banknote,
    Check,
    Clock,
    CreditCard,
    Loader2,
    MapPin,
    RefreshCw,
    Truck,
} from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { getCourierLogo } from '@/lib/status';
import type { CourierRate } from '@/types/shipments';

const props = defineProps<{
    destinationAreaId: string;
    isLoadingRates: boolean;
    ratesFetchError: string | null;
    ratesList: CourierRate[];
    courierCode: string;
    courierServiceCode: string;
    courierServiceName: string;
    shippingFee: number;
    paymentType: string;
    weight: number;
    totalWeightKg: string;
    itemValue: number;
    canSubmit: boolean;
    processing: boolean;
    courierError?: string;
}>();

const emit = defineEmits<{
    refresh: [];
    selectCourier: [rate: CourierRate];
    'update:paymentType': [value: string];
}>();

const isCourierDisabledForCod = (rate: CourierRate): boolean => {
    return props.paymentType === 'cod' && rate.available_for_cash_on_delivery === false;
};
</script>

<template>
    <div class="rounded-xl border border-stone-200/80 bg-white shadow-xs p-4 sm:p-5 space-y-3.5">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-[#2C2C2C] flex items-center gap-2">
                <Truck class="size-4.5 text-[#853953]" />
                <span>Pilih Layanan Ekspedisi</span>
            </h2>
            <Button
                v-if="destinationAreaId && weight > 0"
                type="button"
                variant="ghost"
                size="sm"
                @click="emit('refresh')"
                :disabled="isLoadingRates"
                class="h-8 px-2.5 text-xs font-semibold text-primary hover:bg-[#853953]/10 cursor-pointer"
            >
                <RefreshCw class="size-3.5 mr-1.5" :class="isLoadingRates ? 'animate-spin' : ''" />
                Refresh
            </Button>
        </div>

        <div
            v-if="!destinationAreaId"
            class="py-8 px-4 rounded-xl bg-stone-50/80 text-center space-y-2"
        >
            <MapPin class="size-7 mx-auto text-stone-400" />
            <p class="text-sm font-semibold text-stone-700">
                Tujuan Pengiriman Belum Dipilih
            </p>
            <p class="text-xs sm:text-sm text-stone-500 max-w-sm mx-auto leading-relaxed">
                Pilih kecamatan atau kota tujuan terlebih dahulu untuk melihat tarif pengiriman.
            </p>
        </div>

        <div
            v-else-if="!weight || weight <= 0"
            class="py-8 px-4 rounded-xl bg-stone-50/80 text-center space-y-2"
        >
            <Truck class="size-7 mx-auto text-stone-400" />
            <p class="text-sm font-semibold text-stone-700">
                Qty & Berat Barang Belum Diisi
            </p>
            <p class="text-xs sm:text-sm text-stone-500 max-w-sm mx-auto leading-relaxed">
                Lengkapi Qty dan Berat (gr) barang terlebih dahulu untuk melihat tarif pengiriman.
            </p>
        </div>

        <div v-else-if="isLoadingRates" class="py-10 text-center space-y-2.5">
            <Loader2 class="size-7 animate-spin mx-auto text-[#853953]" />
            <p class="text-sm font-semibold text-stone-700">
                Mengecek Tarif Ekspedisi...
            </p>
            <p class="text-xs text-stone-500">
                Memeriksa tarif layanan Reguler & Next Day
            </p>
        </div>

        <div v-else-if="ratesFetchError" class="py-8 px-4 rounded-xl bg-stone-50 text-center space-y-3">
            <p class="text-sm font-semibold text-stone-700">
                {{ ratesFetchError }}
            </p>
            <Button type="button" size="sm" @click="emit('refresh')" class="bg-[#853953] text-white cursor-pointer">
                Coba Lagi
            </Button>
        </div>

        <div v-else class="space-y-2 max-h-80 overflow-y-auto pr-1">
            <div
                v-if="paymentType === 'cod' && ratesList.length > 0 && !ratesList.some(r => r.available_for_cash_on_delivery !== false)"
                class="p-3 rounded-lg bg-stone-50 border border-stone-200 text-xs text-stone-600"
            >
                Tidak ada ekspedisi yang mendukung COD untuk rute ini. Silakan gunakan metode Non-COD.
            </div>

            <div
                v-for="rate in ratesList"
                :key="`${rate.courier_code}-${rate.courier_service_code}`"
                @click="isCourierDisabledForCod(rate) ? null : emit('selectCourier', rate)"
                class="p-3 rounded-xl transition-all flex items-center justify-between gap-3"
                :class="[
                    isCourierDisabledForCod(rate)
                        ? 'opacity-55 cursor-not-allowed bg-stone-100/70 border border-stone-200/60 select-none'
                        : (courierCode === rate.courier_code &&
                           courierServiceCode === rate.courier_service_code
                            ? 'bg-[#853953]/8 ring-1.5 ring-[#853953] cursor-pointer'
                            : 'bg-stone-50/70 hover:bg-stone-100/80 cursor-pointer')
                ]"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <div class="h-8 w-12 bg-white rounded-md border border-stone-200/70 p-1 flex items-center justify-center shrink-0">
                        <img
                            v-if="getCourierLogo(rate.courier_code)"
                            :src="getCourierLogo(rate.courier_code)!"
                            :alt="rate.courier_name"
                            class="max-h-5 max-w-full object-contain"
                            :class="isCourierDisabledForCod(rate) ? 'grayscale' : ''"
                        />
                        <span v-else class="text-xs font-semibold text-stone-600 uppercase">
                            {{ rate.courier_code }}
                        </span>
                    </div>

                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-stone-900 truncate">
                            {{ rate.courier_name }} {{ rate.courier_service_name }}
                        </div>
                        <div class="flex items-center gap-2 text-xs text-stone-500 mt-0.5">
                            <span class="flex items-center gap-1">
                                <Clock class="size-3" /> {{ rate.duration }}
                            </span>
                            <span
                                v-if="rate.available_for_cash_on_delivery === false && paymentType === 'cod'"
                                class="text-xs font-medium text-rose-600"
                            >
                                Tidak Menerima COD
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 shrink-0">
                    <div class="text-right">
                        <div
                            class="text-sm font-semibold tabular-nums"
                            :class="isCourierDisabledForCod(rate) ? 'text-stone-400' : 'text-primary'"
                        >
                            Rp {{ Number(rate.price).toLocaleString('id-ID') }}
                        </div>
                    </div>
                    <div
                        class="size-4.5 rounded-full border flex items-center justify-center shrink-0"
                        :class="[
                            isCourierDisabledForCod(rate)
                                ? 'border-stone-200 bg-stone-100 text-stone-300'
                                : (courierCode === rate.courier_code &&
                                   courierServiceCode === rate.courier_service_code
                                    ? 'border-[#853953] bg-[#853953] text-white'
                                    : 'border-stone-300 bg-white')
                        ]"
                    >
                        <Check
                            v-if="
                                !isCourierDisabledForCod(rate) &&
                                courierCode === rate.courier_code &&
                                courierServiceCode === rate.courier_service_code
                            "
                            class="size-3"
                        />
                    </div>
                </div>
            </div>

            <InputError :message="courierError" />
        </div>

        <div class="pt-3.5 border-t border-stone-100 space-y-2">
            <div class="text-xs font-semibold text-stone-600 uppercase tracking-wider">
                Metode Pembayaran
            </div>
            <div class="grid grid-cols-2 gap-2">
                <button
                    type="button"
                    @click="emit('update:paymentType', 'non_cod')"
                    class="p-2.5 rounded-lg border text-left transition-all cursor-pointer flex flex-col justify-between"
                    :class="
                        paymentType === 'non_cod'
                            ? 'border-[#853953] bg-[#853953]/5 ring-1.5 ring-[#853953]'
                            : 'border-stone-200 bg-stone-50/50 hover:bg-stone-100/70'
                    "
                >
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-semibold text-stone-900">Non-COD</span>
                        <CreditCard class="size-3.5 text-[#853953]" />
                    </div>
                    <span class="text-xs text-stone-500 leading-normal">
                        Bayar online sekarang via QRIS / VA
                    </span>
                </button>

                <button
                    type="button"
                    @click="emit('update:paymentType', 'cod')"
                    class="p-2.5 rounded-lg border text-left transition-all cursor-pointer flex flex-col justify-between"
                    :class="
                        paymentType === 'cod'
                            ? 'border-[#853953] bg-[#853953]/5 ring-1.5 ring-[#853953]'
                            : 'border-stone-200 bg-stone-50/50 hover:bg-stone-100/70'
                    "
                >
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-semibold text-stone-900">COD</span>
                        <Banknote class="size-3.5 text-[#853953]" />
                    </div>
                    <span class="text-xs text-stone-500 leading-normal">
                        Bayar di tempat saat paket sampai
                    </span>
                </button>
            </div>
        </div>

        <div class="pt-3.5 border-t border-stone-100 space-y-3">
            <div class="space-y-1.5 text-sm">
                <div class="flex items-center justify-between text-stone-600">
                    <span>Berat Paket</span>
                    <span class="font-semibold text-stone-800 tabular-nums">
                        {{ totalWeightKg }} kg ({{ weight.toLocaleString('id-ID') }} gr)
                    </span>
                </div>
                <div class="flex items-center justify-between text-stone-600">
                    <span class="truncate pr-2">
                        Ongkos Kirim {{ courierServiceName ? `(${courierServiceName})` : '' }}
                    </span>
                    <span class="font-semibold text-stone-800 tabular-nums shrink-0">
                        Rp {{ Number(shippingFee).toLocaleString('id-ID') }}
                    </span>
                </div>
                <div v-if="paymentType === 'cod'" class="flex items-center justify-between text-stone-600">
                    <span>Nilai Barang (COD)</span>
                    <span class="font-semibold text-stone-800 tabular-nums shrink-0">
                        Rp {{ Number(itemValue || 0).toLocaleString('id-ID') }}
                    </span>
                </div>
                <div class="flex items-center justify-between text-stone-600">
                    <span>Biaya Layanan</span>
                    <span class="font-semibold text-stone-800 tabular-nums shrink-0">
                        Rp 1.000
                    </span>
                </div>
            </div>

            <div v-if="paymentType === 'cod'" class="space-y-2">
                <div class="p-3 rounded-lg bg-stone-50/90 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-stone-700">Tagihan ke Penerima (COD)</span>
                        <span class="text-base font-bold text-[#853953] tabular-nums">
                            Rp {{ (Number(shippingFee) + Number(itemValue || 0)).toLocaleString('id-ID') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between pt-1.5 border-t border-stone-200/60 text-sm">
                        <span class="text-stone-500">Hak Bersih Penjual (Saldo COD):</span>
                        <span class="font-semibold text-stone-900 tabular-nums">
                            Rp {{ Math.max(0, Number(itemValue || 0) - 1000).toLocaleString('id-ID') }}
                        </span>
                    </div>
                </div>
                <p class="text-xs text-stone-500 leading-relaxed">
                    Saldo COD bersih otomatis masuk ke Saldo Dompet Anda setelah paket berhasil terkirim (biaya layanan Rp 1.000).
                </p>
                <p v-if="!itemValue || Number(itemValue) < 1000" class="text-xs text-rose-600 font-medium">
                    * Nilai barang untuk pengiriman COD minimal Rp 1.000 agar dapat diproses.
                </p>
            </div>

            <div v-else class="p-3 rounded-lg bg-stone-50/90 flex items-center justify-between">
                <span class="text-sm font-medium text-stone-800">Total Tagihan</span>
                <span class="text-lg font-bold text-primary tabular-nums">
                    Rp {{ (Number(shippingFee) + 1000).toLocaleString('id-ID') }}
                </span>
            </div>

            <Button
                type="submit"
                :disabled="!canSubmit"
                class="w-full bg-[#853953] hover:bg-[#6c2e43] text-white font-semibold h-11 text-sm rounded-lg cursor-pointer shadow-xs"
            >
                <Loader2 v-if="processing" class="mr-2 size-4 animate-spin" />
                <span>{{ paymentType === 'cod' ? 'Buat Pengiriman COD' : 'Buat Pengiriman & Bayar' }}</span>
            </Button>
        </div>
    </div>
</template>

