<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ArrowDownToLine, Clock, Landmark, Wallet } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AppTopBar from '@/components/AppTopBar.vue';
import { Button } from '@/components/ui/button';
import WalletCodTab from '@/components/wallet/WalletCodTab.vue';
import WalletWithdrawalsTab from '@/components/wallet/WalletWithdrawalsTab.vue';
import WithdrawModal from '@/components/wallet/WithdrawModal.vue';
import { useSettingsModal } from '@/composables/useSettingsModal';
import type {
    CodShipmentItem,
    PaginatedResult,
    WalletData,
    WalletFilters,
    WithdrawalItem,
} from '@/types/wallet';

const props = withDefaults(
    defineProps<{
        wallet?: WalletData;
        availableBalance: number;
        pendingBalance: number;
        totalWithdrawn?: number;
        completedCount: number;
        pendingCount: number;
        withdrawalsCount?: number;
        codShipments: PaginatedResult<CodShipmentItem>;
        withdrawals: PaginatedResult<WithdrawalItem>;
        supportedBanks?: Record<string, string>;
        filters?: WalletFilters;
    }>(),
    {
        totalWithdrawn: 0,
        withdrawalsCount: 0,
        supportedBanks: () => ({}),
        filters: () => ({}),
    },
);

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

const { openSettingsModal } = useSettingsModal();
const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const banksMap = computed<Record<string, string>>(() => {
    const fromProp = props.supportedBanks && Object.keys(props.supportedBanks).length > 0
        ? props.supportedBanks
        : page.props.supportedBanks;
    return (fromProp || {}) as Record<string, string>;
});

const hasSavedBank = computed(() => {
    return Boolean(authUser.value?.bank_code && authUser.value?.bank_account_number);
});

const savedBankLabel = computed(() => {
    const code = authUser.value?.bank_code;
    if (!code) return '';
    return banksMap.value[code] || code;
});

const goToBankSettings = () => {
    if (typeof window !== 'undefined' && window.innerWidth >= 768) {
        openSettingsModal('bank');
    } else {
        router.visit('/settings/bank');
    }
};

const activeTab = ref<'cod' | 'withdrawals'>(props.filters?.tab === 'withdrawals' ? 'withdrawals' : 'cod');

const codStatus = ref(props.filters?.cod_status || 'all');
const codSearch = ref(props.filters?.cod_search || '');
const codDatePreset = ref(props.filters?.cod_date_preset || 'all');
const codDateFrom = ref(props.filters?.cod_date_from || '');
const codDateTo = ref(props.filters?.cod_date_to || '');

const wdStatus = ref(props.filters?.wd_status || 'all');
const wdSearch = ref(props.filters?.wd_search || '');
const wdDatePreset = ref(props.filters?.wd_date_preset || 'all');
const wdDateFrom = ref(props.filters?.wd_date_from || '');
const wdDateTo = ref(props.filters?.wd_date_to || '');

const applyFilters = () => {
    router.get(
        '/wallet',
        {
            tab: activeTab.value,
            cod_status: codStatus.value !== 'all' ? codStatus.value : undefined,
            cod_search: codSearch.value || undefined,
            cod_date_preset: codDatePreset.value !== 'all' ? codDatePreset.value : undefined,
            cod_date_from: codDateFrom.value || undefined,
            cod_date_to: codDateTo.value || undefined,
            wd_status: wdStatus.value !== 'all' ? wdStatus.value : undefined,
            wd_search: wdSearch.value || undefined,
            wd_date_preset: wdDatePreset.value !== 'all' ? wdDatePreset.value : undefined,
            wd_date_from: wdDateFrom.value || undefined,
            wd_date_to: wdDateTo.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const switchTab = (tab: 'cod' | 'withdrawals') => {
    if (activeTab.value === tab) return;
    activeTab.value = tab;
    applyFilters();
};

let codSearchTimer: ReturnType<typeof setTimeout> | null = null;
watch(codSearch, () => {
    if (codSearchTimer) clearTimeout(codSearchTimer);
    codSearchTimer = setTimeout(() => applyFilters(), 350);
});

let wdSearchTimer: ReturnType<typeof setTimeout> | null = null;
watch(wdSearch, () => {
    if (wdSearchTimer) clearTimeout(wdSearchTimer);
    wdSearchTimer = setTimeout(() => applyFilters(), 350);
});

const handleCodDateChange = (payload: { preset: string; dateFrom: string; dateTo: string }) => {
    codDatePreset.value = payload.preset;
    codDateFrom.value = payload.dateFrom;
    codDateTo.value = payload.dateTo;
    applyFilters();
};

const handleWdDateChange = (payload: { preset: string; dateFrom: string; dateTo: string }) => {
    wdDatePreset.value = payload.preset;
    wdDateFrom.value = payload.dateFrom;
    wdDateTo.value = payload.dateTo;
    applyFilters();
};

const resetCodFilters = () => {
    codStatus.value = 'all';
    codSearch.value = '';
    codDatePreset.value = 'all';
    codDateFrom.value = '';
    codDateTo.value = '';
    applyFilters();
};

const resetWdFilters = () => {
    wdStatus.value = 'all';
    wdSearch.value = '';
    wdDatePreset.value = 'all';
    wdDateFrom.value = '';
    wdDateTo.value = '';
    applyFilters();
};

const showWithdrawModal = ref(false);
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

                    <div class="col-span-2 lg:col-span-1 p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <span class="text-xs font-semibold text-stone-500 block">Total Sudah Dicairkan</span>
                            <div class="text-base sm:text-lg font-bold text-[#2C2C2C] tabular-nums truncate mt-0.5">
                                Rp {{ Number(totalWithdrawn || 0).toLocaleString('id-ID') }}
                            </div>
                        </div>
                        <span class="text-xs font-semibold text-stone-500 tabular-nums shrink-0">
                            {{ withdrawalsCount }} penarikan
                        </span>
                    </div>
                </div>

                <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs space-y-2.5">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <Landmark class="size-4 text-[#853953] shrink-0" />
                            <span class="text-xs sm:text-sm font-bold text-[#2C2C2C] truncate">Rekening Pencairan</span>
                        </div>
                        <button
                            type="button"
                            @click="goToBankSettings"
                            class="text-xs font-semibold text-[#853953] hover:underline cursor-pointer shrink-0"
                        >
                            {{ hasSavedBank ? 'Ubah' : 'Atur' }}
                        </button>
                    </div>

                    <div v-if="hasSavedBank" class="p-2.5 rounded-lg bg-stone-50/90 border border-stone-200/60 space-y-0.5">
                        <div class="text-xs sm:text-sm font-bold text-[#2C2C2C] truncate">
                            {{ savedBankLabel }}
                        </div>
                        <div class="text-xs font-mono font-semibold text-[#853953] tabular-nums">
                            {{ authUser?.bank_account_number }}
                        </div>
                        <div class="text-xs text-stone-500 truncate">
                            a/n {{ authUser?.bank_account_name || authUser?.name }}
                        </div>
                    </div>
                    <p v-else class="text-xs text-stone-500 leading-relaxed">
                        Belum ada rekening utama tersimpan. Atur di Pengaturan agar penarikan saldo lebih cepat dan aman.
                    </p>
                </div>

                <Button
                    type="button"
                    @click="showWithdrawModal = true"
                    class="w-full bg-[#853953] hover:bg-[#6c2e43] text-white font-semibold text-xs sm:text-sm h-10 rounded-xl cursor-pointer shadow-xs inline-flex items-center justify-center gap-2"
                >
                    <ArrowDownToLine class="size-4" />
                    <span>Tarik Saldo</span>
                </Button>
            </div>

            <div class="lg:col-span-8 w-full min-w-0">
                <div class="rounded-xl border border-stone-200/80 bg-white shadow-xs overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-2 px-3.5 sm:px-4 border-b border-stone-200 bg-stone-50/60">
                        <div class="flex items-center gap-1 overflow-x-auto -mb-px">
                            <button
                                type="button"
                                @click="switchTab('cod')"
                                class="px-3.5 py-2.5 text-sm font-semibold border-b-2 transition-colors cursor-pointer whitespace-nowrap flex items-center gap-1.5"
                                :class="
                                    activeTab === 'cod'
                                        ? 'border-[#853953] text-[#853953] bg-white/80'
                                        : 'border-transparent text-stone-600 hover:text-stone-900 hover:border-stone-300'
                                "
                            >
                                <span>Mutasi Paket COD</span>
                            </button>

                            <button
                                type="button"
                                @click="switchTab('withdrawals')"
                                class="px-3.5 py-2.5 text-sm font-semibold border-b-2 transition-colors cursor-pointer whitespace-nowrap flex items-center gap-1.5"
                                :class="
                                    activeTab === 'withdrawals'
                                        ? 'border-[#853953] text-[#853953] bg-white/80'
                                        : 'border-transparent text-stone-600 hover:text-stone-900 hover:border-stone-300'
                                "
                            >
                                <span>Riwayat Penarikan Saldo</span>
                            </button>
                        </div>
                    </div>

                    <WalletCodTab
                        v-if="activeTab === 'cod'"
                        :cod-shipments="codShipments"
                        :status="codStatus"
                        :search="codSearch"
                        :date-preset="codDatePreset"
                        :date-from="codDateFrom"
                        :date-to="codDateTo"
                        @update:status="codStatus = $event; applyFilters()"
                        @update:search="codSearch = $event"
                        @date-change="handleCodDateChange"
                        @reset="resetCodFilters"
                    />

                    <WalletWithdrawalsTab
                        v-else
                        :withdrawals="withdrawals"
                        :status="wdStatus"
                        :search="wdSearch"
                        :date-preset="wdDatePreset"
                        :date-from="wdDateFrom"
                        :date-to="wdDateTo"
                        @update:status="wdStatus = $event; applyFilters()"
                        @update:search="wdSearch = $event"
                        @date-change="handleWdDateChange"
                        @reset="resetWdFilters"
                    />
                </div>
            </div>
        </div>

        <WithdrawModal
            v-model:open="showWithdrawModal"
            :available-balance="availableBalance"
            :banks-map="banksMap"
            :has-saved-bank="hasSavedBank"
            :default-bank-code="authUser?.bank_code"
            :default-account-number="authUser?.bank_account_number"
            :default-account-holder-name="authUser?.bank_account_name || authUser?.name"
        />
    </div>
</template>
