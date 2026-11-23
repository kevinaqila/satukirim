<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { AlertCircle, Loader2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const props = defineProps<{
    open: boolean;
    availableBalance: number;
    banksMap: Record<string, string>;
    hasSavedBank: boolean;
    defaultBankCode?: string | null;
    defaultAccountNumber?: string | null;
    defaultAccountHolderName?: string | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const withdrawForm = useForm({
    bank_code: props.defaultBankCode || '',
    account_number: '',
    account_holder_name: '',
    amount: undefined as number | undefined,
    save_bank: true,
});

const errorMessage = ref<string | null>(null);

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;
        errorMessage.value = null;
        withdrawForm.clearErrors();
        withdrawForm.bank_code = props.defaultBankCode || '';
        withdrawForm.account_number = props.defaultAccountNumber || '';
        withdrawForm.account_holder_name = props.defaultAccountHolderName || '';
        withdrawForm.save_bank = !props.hasSavedBank;
    },
);

const submitWithdrawal = () => {
    if (!withdrawForm.bank_code) {
        withdrawForm.setError('bank_code', 'Pilih bank tujuan terlebih dahulu.');
        return;
    }

    errorMessage.value = null;
    withdrawForm.post('/wallet/withdraw', {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false);
            withdrawForm.reset('amount');
        },
        onError: (errors) => {
            const generalError = (errors as Record<string, string | undefined>).general;
            if (generalError) {
                errorMessage.value = generalError;
            }
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
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
                    v-if="(withdrawForm.errors as Record<string, string | undefined>).general || errorMessage"
                    class="p-3 rounded-lg bg-rose-50 text-xs text-rose-700 flex items-start gap-2"
                >
                    <AlertCircle class="size-4 shrink-0 mt-0.5" />
                    <div>{{ (withdrawForm.errors as Record<string, string | undefined>).general || errorMessage }}</div>
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
                            <div class="flex items-center justify-between">
                                <Label for="withdraw-bank" class="text-xs sm:text-sm font-semibold text-stone-700">
                                    Bank Tujuan<span class="text-red-500 ml-1">*</span>
                                </Label>
                                <span v-if="hasSavedBank" class="text-xs font-medium text-stone-500">
                                    Terisi dari profil akun
                                </span>
                            </div>
                            <Select v-model="withdrawForm.bank_code" required>
                                <SelectTrigger
                                    id="withdraw-bank"
                                    class="w-full !h-10 bg-white border-stone-200 rounded-lg text-xs sm:text-sm text-stone-900 focus-visible:border-[#853953] focus-visible:ring-[#853953]/20 cursor-pointer"
                                >
                                    <SelectValue placeholder="Pilih bank tujuan" />
                                </SelectTrigger>
                                <SelectContent class="z-[60] max-h-60 bg-white border-stone-200 rounded-lg shadow-lg">
                                    <SelectItem
                                        v-for="(bankName, code) in banksMap"
                                        :key="code"
                                        :value="String(code)"
                                        class="text-xs sm:text-sm cursor-pointer data-[state=checked]:font-semibold data-[state=checked]:text-[#853953]"
                                    >
                                        {{ bankName }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
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
                                    class="h-10 text-xs sm:text-sm bg-white border-stone-200 tabular-nums"
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

                        <label class="flex items-center gap-2 text-xs text-stone-600 cursor-pointer select-none">
                            <Checkbox
                                :checked="withdrawForm.save_bank"
                                @update:checked="(val) => (withdrawForm.save_bank = Boolean(val))"
                            />
                            <span>Simpan sebagai rekening pencairan utama di profil</span>
                        </label>

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
                                    <span class="font-semibold text-stone-900">&lt; 1 Menit</span>
                                </div>
                            </div>

                            <div class="p-3 rounded-lg bg-white flex items-center justify-between">
                                <span class="text-xs sm:text-sm font-semibold text-stone-900">Sisa Saldo</span>
                                <span class="text-base sm:text-lg font-bold text-[#853953] tabular-nums">
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
                        @click="emit('update:open', false)"
                        :disabled="withdrawForm.processing"
                        class="text-xs sm:text-sm font-semibold h-9 sm:h-10 px-4 cursor-pointer border-stone-200 text-stone-700 hover:bg-stone-50 shadow-2xs"
                    >
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        :disabled="withdrawForm.processing || !withdrawForm.bank_code || !withdrawForm.amount || withdrawForm.amount > availableBalance || withdrawForm.amount < 10000"
                        class="bg-[#853953] hover:bg-[#6c2e43] text-white text-xs sm:text-sm font-semibold h-9 sm:h-10 px-5 cursor-pointer inline-flex items-center gap-1.5 shadow-xs"
                    >
                        <Loader2 v-if="withdrawForm.processing" class="mr-2 size-4 animate-spin" />
                        <span>Konfirmasi Tarik Saldo</span>
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>

