<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Check,
    Loader2,
    Phone,
    Search,
} from '@lucide/vue';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import AppTopBar from '@/components/AppTopBar.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Toko Saya',
                href: '/stores',
            },
            {
                title: 'Tambah Toko',
                href: '/stores/create',
            },
        ],
    },
});

const form = useForm({
    name: '',
    phone: '',
    address: '',
    postal_code: '',
    biteship_area_id: '',
    area_name: '',
    is_default: false,
});

const areaSearchQuery = ref('');
const areaSearchResults = ref<Array<{
    id: string;
    name: string;
    country_name: string;
    administrative_division_level_1_name: string;
    administrative_division_level_2_name: string;
    administrative_division_level_3_name: string;
    postal_code?: number;
}>>([]);
const isSearchingArea = ref(false);
const showAreaDropdown = ref(false);
let searchTimeout: any = null;

watch(areaSearchQuery, (val) => {
    if (!val || val.length < 2) {
        areaSearchResults.value = [];
        showAreaDropdown.value = false;
        return;
    }

    if (form.area_name && val === form.area_name) {
        return;
    }

    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        isSearchingArea.value = true;
        try {
            const res = await fetch(`/api/public/areas?q=${encodeURIComponent(val)}`);
            const data = await res.json();
            areaSearchResults.value = data.areas || [];
            showAreaDropdown.value = areaSearchResults.value.length > 0;
        } catch {
            areaSearchResults.value = [];
            showAreaDropdown.value = false;
        } finally {
            isSearchingArea.value = false;
        }
    }, 350);
});

const selectArea = (item: any) => {
    form.biteship_area_id = item.id;
    form.area_name = item.name;
    areaSearchQuery.value = item.name;
    if (item.postal_code) {
        form.postal_code = String(item.postal_code);
    }
    showAreaDropdown.value = false;
};

const handleOutsideClick = (e: MouseEvent) => {
    const target = e.target as HTMLElement;
    if (!target.closest('.area-dropdown-container')) {
        showAreaDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleOutsideClick);
});

onUnmounted(() => {
    document.removeEventListener('click', handleOutsideClick);
});

const submit = () => {
    form.post('/stores');
};
</script>

<template>
    <div class="flex min-h-full flex-1 flex-col w-full">
        <Head title="Tambah Toko Baru - SatuKirim" />

        <AppTopBar title="Tambah Toko Baru" back-href="/stores" />

        <div class="flex flex-1 flex-col gap-3.5 sm:gap-4 p-3 sm:p-4 lg:p-5 w-full">
            <div class="rounded-xl border border-stone-200/80 bg-white p-4 sm:p-5 lg:p-6 shadow-xs">
                <form @submit.prevent="submit" class="space-y-3.5 sm:space-y-4">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
                        <div class="space-y-2.5 sm:space-y-3">
                            <div class="space-y-1">
                                <Label for="store-name" class="text-xs sm:text-sm font-semibold text-stone-800">
                                    Nama Toko / Gudang<span class="text-red-500 ml-1">*</span>
                                </Label>
                                <Input
                                    id="store-name"
                                    v-model="form.name"
                                    placeholder="Gudang Utama Surabaya"
                                    class="h-10 text-xs sm:text-sm bg-white border-stone-200"
                                    required
                                />
                                <span v-if="form.errors.name" class="text-[11px] text-red-600 font-medium">
                                    {{ form.errors.name }}
                                </span>
                            </div>

                            <div class="space-y-1">
                                <Label for="store-phone" class="text-xs sm:text-sm font-semibold text-stone-800">
                                    Nomor WhatsApp Pengirim<span class="text-red-500 ml-1">*</span>
                                </Label>
                                <div class="relative">
                                    <Phone class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-stone-400" />
                                    <Input
                                        id="store-phone"
                                        v-model="form.phone"
                                        placeholder="081234567890"
                                        class="h-10 pl-9 text-xs sm:text-sm bg-white border-stone-200"
                                        required
                                    />
                                </div>
                                <span v-if="form.errors.phone" class="text-[11px] text-red-600 font-medium">
                                    {{ form.errors.phone }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-2.5 sm:space-y-3">
                            <div class="space-y-1 relative area-dropdown-container">
                                <Label for="store-area" class="text-xs sm:text-sm font-semibold text-stone-800 flex items-center justify-between">
                                    <span>Kecamatan & Kota (Area Penjemputan)<span class="text-red-500 ml-1">*</span></span>
                                    <span class="text-[11px] text-[#853953] font-medium">Wajib pilih dari daftar</span>
                                </Label>

                                <div class="relative">
                                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-stone-400" />
                                    <Input
                                        id="store-area"
                                        v-model="areaSearchQuery"
                                        placeholder="Ketik kelurahan atau kecamatan penjemputan..."
                                        class="h-10 pl-9 pr-9 text-xs sm:text-sm bg-white border-stone-200"
                                        autocomplete="off"
                                        @focus="areaSearchResults.length > 0 ? (showAreaDropdown = true) : null"
                                    />
                                    <div v-if="isSearchingArea" class="absolute right-3 top-1/2 -translate-y-1/2">
                                        <Loader2 class="size-4 animate-spin text-[#853953]" />
                                    </div>
                                </div>

                                <div
                                    v-if="showAreaDropdown && areaSearchResults.length > 0"
                                    class="absolute z-50 left-0 right-0 mt-1 max-h-56 overflow-y-auto rounded-lg border border-stone-200 bg-white shadow-lg"
                                >
                                    <div
                                        v-for="area in areaSearchResults"
                                        :key="area.id"
                                        @click="selectArea(area)"
                                        class="p-2.5 sm:p-3 hover:bg-stone-50 cursor-pointer border-b border-stone-100 last:border-none transition-colors"
                                    >
                                        <div class="text-xs sm:text-sm font-bold text-stone-900">
                                            {{ area.name }}
                                        </div>
                                        <div class="text-[11px] sm:text-xs text-stone-500">
                                            {{ area.administrative_division_level_3_name }}, {{ area.administrative_division_level_2_name }}, {{ area.administrative_division_level_1_name }}
                                            <span v-if="area.postal_code">({{ area.postal_code }})</span>
                                        </div>
                                    </div>
                                </div>

                                <span v-if="form.errors.biteship_area_id" class="text-[11px] text-red-600 font-medium">
                                    Pilih area penjemputan dari daftar pencarian.
                                </span>
                            </div>

                            <div class="space-y-1">
                                <Label for="store-address" class="text-xs sm:text-sm font-semibold text-stone-800">
                                    Alamat Lengkap & Patokan<span class="text-red-500 ml-1">*</span>
                                </Label>
                                <textarea
                                    id="store-address"
                                    v-model="form.address"
                                    rows="3"
                                    placeholder="Jl. Raya Sukolilo No. 45, Ruko B3 (sebelah Bank BCA)"
                                    class="w-full rounded-md border border-stone-200 bg-white px-3 py-2 text-xs sm:text-sm text-stone-900 placeholder:text-stone-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#853953] focus-visible:ring-offset-1"
                                    required
                                />
                                <span v-if="form.errors.address" class="text-[11px] text-red-600 font-medium">
                                    {{ form.errors.address }}
                                </span>
                            </div>

                            <div class="space-y-1">
                                <Label for="store-postal" class="text-xs sm:text-sm font-semibold text-stone-800">
                                    Kode Pos (Opsional)
                                </Label>
                                <Input
                                    id="store-postal"
                                    v-model="form.postal_code"
                                    placeholder="60118"
                                    class="h-10 text-xs sm:text-sm bg-white border-stone-200"
                                />
                                <span v-if="form.errors.postal_code" class="text-[11px] text-red-600 font-medium">
                                    {{ form.errors.postal_code }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-stone-200/80 bg-stone-50/70 p-3 sm:p-3.5">
                        <div class="flex items-start gap-2.5">
                            <Checkbox
                                id="is-default"
                                :checked="form.is_default"
                                @update:checked="(val) => (form.is_default = Boolean(val))"
                                class="mt-0.5"
                            />
                            <div class="space-y-0.5">
                                <Label for="is-default" class="text-xs sm:text-sm font-semibold text-stone-900 cursor-pointer">
                                    Jadikan sebagai Toko Utama
                                </Label>
                                <p class="text-[11px] sm:text-xs text-stone-500">
                                    Toko ini akan otomatis dipilih sebagai lokasi penjemputan barang saat membuat pengiriman paket baru.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-1 sm:pt-2">
                        <Link
                            href="/stores"
                            class="inline-flex items-center justify-center h-10 px-4 text-xs sm:text-sm font-semibold rounded-md border border-stone-200 bg-white text-stone-700 hover:bg-stone-50 shadow-2xs transition-colors"
                        >
                            Batal
                        </Link>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-[#853953] hover:bg-[#6c2e43] text-white text-xs sm:text-sm font-semibold h-10 px-5 rounded-md shadow-xs cursor-pointer"
                        >
                            <Loader2 v-if="form.processing" class="mr-2 size-4 animate-spin" />
                            Tambah Toko
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
