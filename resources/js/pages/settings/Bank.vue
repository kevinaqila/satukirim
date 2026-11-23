<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Check, Loader2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { edit } from '@/routes/bank';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Rekening Pencairan',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const supportedBanks = computed(() => (page.props.supportedBanks || {}) as Record<string, string>);

const bankForm = useForm({
    bank_code: user.value?.bank_code || '',
    bank_account_number: user.value?.bank_account_number || '',
    bank_account_name: user.value?.bank_account_name || user.value?.name || '',
});

watch(
    () => user.value,
    (newUser) => {
        if (newUser) {
            bankForm.bank_code = newUser.bank_code || '';
            bankForm.bank_account_number = newUser.bank_account_number || '';
            bankForm.bank_account_name = newUser.bank_account_name || newUser.name || '';
        }
    },
    { immediate: true },
);

const isSaved = ref(false);
const submitBank = () => {
    if (!bankForm.bank_code) {
        bankForm.setError('bank_code', 'Pilih bank tujuan terlebih dahulu.');
        return;
    }

    bankForm.patch('/settings/bank', {
        preserveScroll: true,
        onSuccess: () => {
            isSaved.value = true;
            setTimeout(() => {
                isSaved.value = false;
            }, 2500);
        },
    });
};
</script>

<template>
    <div class="space-y-4 sm:space-y-6">
        <Head title="Rekening Pencairan - SatuKirim" />

        <div class="rounded-xl bg-white border border-stone-200/80 p-4 sm:p-5 shadow-xs">
            <form @submit.prevent="submitBank" class="space-y-4">
                <div class="grid gap-1.5">
                    <Label for="bank_code" class="text-xs sm:text-sm font-semibold text-stone-800">
                        Bank Tujuan<span class="text-red-500 ml-1">*</span>
                    </Label>
                    <Select v-model="bankForm.bank_code" required>
                        <SelectTrigger
                            id="bank_code"
                            class="w-full !h-9 sm:!h-10 bg-white border-stone-200 rounded-lg text-xs sm:text-sm text-stone-900 focus-visible:border-[#853953] focus-visible:ring-[#853953]/20 cursor-pointer"
                        >
                            <SelectValue placeholder="Pilih bank tujuan" />
                        </SelectTrigger>
                        <SelectContent class="z-[60] max-h-60 bg-white border-stone-200 rounded-lg shadow-lg">
                            <SelectItem
                                v-for="(bankName, code) in supportedBanks"
                                :key="code"
                                :value="String(code)"
                                class="text-xs sm:text-sm cursor-pointer data-[state=checked]:font-semibold data-[state=checked]:text-[#853953]"
                            >
                                {{ bankName }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="bankForm.errors.bank_code" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="bank_account_number" class="text-xs sm:text-sm font-semibold text-stone-800">
                        Nomor Rekening
                    </Label>
                    <Input
                        id="bank_account_number"
                        v-model="bankForm.bank_account_number"
                        type="text"
                        inputmode="numeric"
                        placeholder="Masukkan angka nomor rekening"
                        class="h-9 sm:h-10 text-xs sm:text-sm bg-white border-stone-200 tabular-nums"
                        required
                    />
                    <InputError :message="bankForm.errors.bank_account_number" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="bank_account_name" class="text-xs sm:text-sm font-semibold text-stone-800">
                        Nama Pemilik Rekening
                    </Label>
                    <Input
                        id="bank_account_name"
                        v-model="bankForm.bank_account_name"
                        type="text"
                        placeholder="Sesuai buku tabungan"
                        class="h-9 sm:h-10 text-xs sm:text-sm bg-white border-stone-200"
                        required
                    />
                    <InputError :message="bankForm.errors.bank_account_name" />
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <Button
                        type="submit"
                        :disabled="bankForm.processing || !bankForm.bank_code"
                        class="bg-[#853953] hover:bg-[#6c2e43] text-white font-semibold text-xs sm:text-sm h-9 sm:h-10 px-5 rounded-md shadow-xs cursor-pointer"
                    >
                        <Loader2 v-if="bankForm.processing" class="mr-2 size-4 animate-spin" />
                        Simpan Rekening
                    </Button>

                    <span v-if="isSaved" class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                        <Check class="size-3.5 text-emerald-600" />
                        Tersimpan!
                    </span>
                </div>
            </form>
        </div>
    </div>
</template>

