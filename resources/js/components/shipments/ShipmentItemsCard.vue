<script setup lang="ts">
import { Package, Plus, Trash2 } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { CreateShipmentItem } from '@/types/shipments';

defineProps<{
    items: CreateShipmentItem[];
    totalWeightKg: string;
    errors: Record<string, string | undefined>;
}>();

const emit = defineEmits<{
    addItem: [];
    removeItem: [index: number];
}>();

const normalizeDimensionModel = (value: string | number): number | null => {
    if (value === '' || value === null || value === undefined) return null;
    const num = Number(value);
    return Number.isFinite(num) && num > 0 ? Math.round(num) : null;
};

const getDimensionCount = (item: CreateShipmentItem): number => {
    return [item.length, item.width, item.height].filter((v) => typeof v === 'number' && v > 0).length;
};

const getVolumetricKg = (item: CreateShipmentItem): string | null => {
    if (getDimensionCount(item) !== 3) return null;
    const qty = Math.max(1, Number(item.qty) || 1);
    const volKg = ((Number(item.length) * Number(item.width) * Number(item.height)) / 6000) * qty;
    return volKg.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 2 });
};
</script>

<template>
    <div class="rounded-xl border border-stone-200/80 bg-white shadow-xs p-4 sm:p-5 space-y-3.5">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-[#2C2C2C] flex items-center gap-2">
                <Package class="size-4.5 text-[#853953]" />
                <span>Rincian Barang</span>
            </h2>
            <span class="text-sm font-medium text-stone-500">
                Total {{ totalWeightKg }} kg ({{ items.length }} barang)
            </span>
        </div>

        <div class="space-y-3">
            <div
                v-for="(item, idx) in items"
                :key="idx"
                class="p-3.5 rounded-xl border border-stone-200/80 bg-stone-50/50 space-y-3 relative transition-all"
            >
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-stone-800">
                        Barang {{ idx + 1 }}
                    </span>
                    <button
                        v-if="items.length > 1"
                        type="button"
                        @click="emit('removeItem', idx)"
                        class="text-sm font-medium text-rose-600 hover:text-rose-700 flex items-center gap-1 cursor-pointer transition-colors"
                    >
                        <Trash2 class="size-3.5" />
                        <span>Hapus</span>
                    </button>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-12 gap-2.5">
                    <div class="col-span-2 sm:col-span-5 space-y-1">
                        <Label :for="`item-name-${idx}`" class="text-sm font-medium text-stone-700">
                            Nama Barang <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            :id="`item-name-${idx}`"
                            v-model="item.name"
                            placeholder="Kemeja Flanel, Kopi Arabika"
                            class="text-sm h-10 bg-white border border-stone-200"
                            required
                        />
                        <InputError :message="errors[`items.${idx}.name`]" />
                    </div>

                    <div class="col-span-1 sm:col-span-2 space-y-1">
                        <Label :for="`item-qty-${idx}`" class="text-sm font-medium text-stone-700">
                            Qty
                        </Label>
                        <Input
                            :id="`item-qty-${idx}`"
                            v-model.number="item.qty"
                            type="number"
                            min="1"
                            max="1000"
                            placeholder="1"
                            class="text-sm h-10 bg-white border border-stone-200"
                            required
                        />
                        <InputError :message="errors[`items.${idx}.qty`]" />
                    </div>

                    <div class="col-span-1 sm:col-span-2 space-y-1">
                        <Label :for="`item-weight-${idx}`" class="text-sm font-medium text-stone-700">
                            Berat (gr)
                        </Label>
                        <Input
                            :id="`item-weight-${idx}`"
                            v-model.number="item.weight"
                            type="number"
                            min="10"
                            max="10000"
                            step="1"
                            placeholder="250"
                            class="text-sm h-10 bg-white border border-stone-200"
                            required
                        />
                        <InputError :message="errors[`items.${idx}.weight`]" />
                    </div>

                    <div class="col-span-2 sm:col-span-3 space-y-1">
                        <Label :for="`item-val-${idx}`" class="text-sm font-medium text-stone-700">
                            Harga per item (Rp)
                        </Label>
                        <Input
                            :id="`item-val-${idx}`"
                            v-model.number="item.value"
                            type="number"
                            min="0"
                            step="1"
                            placeholder="50000"
                            class="text-sm h-10 bg-white border border-stone-200"
                            required
                        />
                        <InputError :message="errors[`items.${idx}.value`]" />
                    </div>
                </div>

                <div class="space-y-1">
                    <Label :for="`item-desc-${idx}`" class="text-xs font-medium text-stone-600">
                        Deskripsi Barang <span class="text-stone-400 font-normal">(Opsional)</span>
                    </Label>
                    <Input
                        :id="`item-desc-${idx}`"
                        :model-value="item.description ?? ''"
                        @update:model-value="item.description = String($event)"
                        placeholder="Varian, warna, ukuran, atau catatan pengemasan (mis. Hitam XL, Bubble Wrap)"
                        maxlength="255"
                        class="text-sm h-9 bg-white border border-stone-200"
                    />
                    <InputError :message="errors[`items.${idx}.description`]" />
                </div>

                <div class="space-y-1.5">
                    <div class="flex flex-wrap items-center justify-between gap-1.5">
                        <span class="text-xs font-medium text-stone-600">
                            Dimensi Paket (cm) <span class="text-stone-400 font-normal">(Opsional)</span>
                        </span>
                        <span v-if="getVolumetricKg(item)" class="text-xs font-semibold text-[#853953] tabular-nums">
                            Est. Volumetrik: {{ getVolumetricKg(item) }} kg
                        </span>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <Input
                            :id="`item-length-${idx}`"
                            :model-value="item.length ?? ''"
                            @update:model-value="item.length = normalizeDimensionModel($event)"
                            type="number"
                            min="1"
                            max="500"
                            step="1"
                            placeholder="P (cm)"
                            aria-label="Panjang (cm)"
                            class="text-sm h-9 bg-white border border-stone-200 tabular-nums"
                        />
                        <Input
                            :id="`item-width-${idx}`"
                            :model-value="item.width ?? ''"
                            @update:model-value="item.width = normalizeDimensionModel($event)"
                            type="number"
                            min="1"
                            max="500"
                            step="1"
                            placeholder="L (cm)"
                            aria-label="Lebar (cm)"
                            class="text-sm h-9 bg-white border border-stone-200 tabular-nums"
                        />
                        <Input
                            :id="`item-height-${idx}`"
                            :model-value="item.height ?? ''"
                            @update:model-value="item.height = normalizeDimensionModel($event)"
                            type="number"
                            min="1"
                            max="500"
                            step="1"
                            placeholder="T (cm)"
                            aria-label="Tinggi (cm)"
                            class="text-sm h-9 bg-white border border-stone-200 tabular-nums"
                        />
                    </div>
                    <p
                        v-if="getDimensionCount(item) > 0 && getDimensionCount(item) < 3"
                        class="text-xs text-amber-600 font-medium"
                    >
                        Isi lengkap Panjang, Lebar, dan Tinggi (cm) agar volumetrik dapat dihitung.
                    </p>
                    <InputError :message="errors[`items.${idx}.length`] || errors[`items.${idx}.width`] || errors[`items.${idx}.height`]" />
                </div>

                <div v-if="Number(item.qty) > 1" class="flex flex-wrap items-center justify-between gap-2 text-sm text-stone-500 pt-1 border-t border-stone-200/60">
                    <span>Subtotal berat: {{ ((item.qty || 1) * (item.weight || 100)).toLocaleString('id-ID') }} gr</span>
                    <span>Subtotal nilai: Rp {{ ((item.qty || 1) * (item.value || 0)).toLocaleString('id-ID') }}</span>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-1">
            <Button
                type="button"
                variant="outline"
                size="sm"
                @click="emit('addItem')"
                :disabled="items.length >= 10"
                class="w-full sm:w-auto justify-center text-sm font-medium text-[#853953] border border-stone-200 hover:bg-stone-50 h-9 px-3.5 rounded-lg cursor-pointer inline-flex items-center gap-1.5"
            >
                <Plus class="size-4" />
                <span>Tambah Barang</span>
            </Button>

            <span class="text-xs sm:text-sm text-stone-500">
                Maksimal 10 barang (total berat maks. 10 kg)
            </span>
        </div>

        <InputError :message="errors.items" />
        <InputError :message="errors.item_value" />
    </div>
</template>

