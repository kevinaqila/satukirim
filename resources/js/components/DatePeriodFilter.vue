<script setup lang="ts">
import { CalendarDays, Check, ChevronDown } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';

export interface DatePresetOption {
    id: string;
    label: string;
}

const props = withDefaults(
    defineProps<{
        preset: string;
        dateFrom: string;
        dateTo: string;
        presets?: DatePresetOption[];
        align?: 'start' | 'end' | 'center';
        buttonClass?: string;
        ariaLabel?: string;
        manualRanges?: boolean;
    }>(),
    {
        presets: () => [
            { id: 'all', label: 'Semua Periode' },
            { id: 'today', label: 'Hari Ini' },
            { id: '7d', label: '7 Hari Terakhir' },
            { id: '30d', label: '30 Hari Terakhir' },
            { id: 'this_month', label: 'Bulan Ini' },
            { id: 'last_month', label: 'Bulan Lalu' },
        ],
        align: 'end',
        buttonClass: '',
        ariaLabel: 'Filter periode',
        manualRanges: false,
    },
);

const emit = defineEmits<{
    change: [payload: { preset: string; dateFrom: string; dateTo: string }];
}>();

const customFrom = ref(props.dateFrom || '');
const customTo = ref(props.dateTo || '');

watch(
    () => [props.dateFrom, props.dateTo],
    ([from, to]) => {
        customFrom.value = from || '';
        customTo.value = to || '';
    },
);

const toLocalIso = (date: Date): string => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

const formatShortDate = (iso: string): string => {
    if (!iso) return '...';
    const d = new Date(`${iso}T00:00:00`);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    return `${day}/${month}`;
};

const activeDateLabel = computed(() => {
    if (props.preset === 'custom') {
        if (props.dateFrom && props.dateTo) {
            return `${formatShortDate(props.dateFrom)} - ${formatShortDate(props.dateTo)}`;
        }
        if (props.dateFrom) return `Sejak ${formatShortDate(props.dateFrom)}`;
        if (props.dateTo) return `Hingga ${formatShortDate(props.dateTo)}`;
        return 'Rentang Kustom';
    }
    if (props.preset === 'all') {
        return 'Periode';
    }
    return props.presets.find((p) => p.id === props.preset)?.label ?? 'Periode';
});

const selectPreset = (id: string) => {
    if (props.manualRanges) {
        if (id !== 'custom') {
            customFrom.value = '';
            customTo.value = '';
        }
        emit('change', {
            preset: id,
            dateFrom: id === 'custom' ? customFrom.value : '',
            dateTo: id === 'custom' ? customTo.value : '',
        });
        return;
    }

    const today = new Date();
    let from = '';
    let to = '';

    if (id === 'today') {
        from = to = toLocalIso(today);
    } else if (id === '7d' || id === '7days' || id === '30d' || id === '30days') {
        const days = id.startsWith('7') ? 6 : 29;
        const start = new Date(today);
        start.setDate(today.getDate() - days);
        from = toLocalIso(start);
        to = toLocalIso(today);
    } else if (id === 'this_month') {
        from = toLocalIso(new Date(today.getFullYear(), today.getMonth(), 1));
        to = toLocalIso(today);
    } else if (id === 'last_month') {
        from = toLocalIso(new Date(today.getFullYear(), today.getMonth() - 1, 1));
        to = toLocalIso(new Date(today.getFullYear(), today.getMonth(), 0));
    }

    customFrom.value = from;
    customTo.value = to;
    emit('change', { preset: id, dateFrom: from, dateTo: to });
};

const applyCustomDate = () => {
    if (!customFrom.value && !customTo.value) return;
    emit('change', {
        preset: 'custom',
        dateFrom: customFrom.value,
        dateTo: customTo.value,
    });
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="flex h-9 items-center justify-between gap-2 rounded-md border bg-white px-3 text-xs sm:text-sm font-semibold cursor-pointer shrink-0 transition-colors"
                :class="[
                    preset === 'all' ? 'border-stone-200 text-stone-700 hover:bg-stone-50' : 'border-[#853953]/40 text-[#853953]',
                    buttonClass,
                ]"
                :aria-label="ariaLabel"
            >
                <span class="flex items-center gap-1.5 min-w-0">
                    <CalendarDays class="size-4 shrink-0 text-stone-400" />
                    <span class="truncate">{{ activeDateLabel }}</span>
                </span>
                <ChevronDown class="size-4 shrink-0 text-stone-400" />
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent :align="align" class="w-64 max-w-[calc(100vw-1.5rem)]">
            <DropdownMenuItem
                v-for="item in presets"
                :key="item.id"
                class="justify-between text-sm cursor-pointer"
                :class="preset === item.id ? 'text-[#853953] font-bold' : ''"
                @select="selectPreset(item.id)"
            >
                {{ item.label }}
                <Check v-if="preset === item.id" class="size-4" />
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <div class="space-y-2 p-2" @keydown.stop>
                <p class="text-xs font-semibold text-stone-500">Rentang kustom</p>
                <div class="grid grid-cols-2 gap-2">
                    <label class="space-y-1 text-xs text-stone-500">
                        Dari
                        <Input v-model="customFrom" type="date" :max="customTo || undefined" class="h-9 text-xs px-2" />
                    </label>
                    <label class="space-y-1 text-xs text-stone-500">
                        Sampai
                        <Input v-model="customTo" type="date" :min="customFrom || undefined" class="h-9 text-xs px-2" />
                    </label>
                </div>
                <Button
                    type="button"
                    size="sm"
                    class="w-full h-9 bg-[#853953] hover:bg-[#6c2e43] text-white text-sm font-semibold cursor-pointer"
                    :disabled="!customFrom && !customTo"
                    @click="applyCustomDate"
                >
                    Terapkan
                </Button>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

