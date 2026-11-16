<script setup lang="ts">
import { TrendingDown, TrendingUp } from '@lucide/vue';
import { computed } from 'vue';
import type { SevenDayTrendItem } from '@/types/dashboard';

const props = withDefaults(
    defineProps<{
        title?: string;
        sevenDayTrend?: SevenDayTrendItem[];
        weeklyGrowth?: number;
        deliveryRate: number;
        deliveredCount: number;
    }>(),
    {
        title: 'Volume 7 Hari Terakhir',
        sevenDayTrend: () => [],
        weeklyGrowth: 0,
    },
);

const maxTrendCount = computed(() => {
    if (!props.sevenDayTrend || props.sevenDayTrend.length === 0) return 1;
    const max = Math.max(...props.sevenDayTrend.map((t) => t.count));
    return max > 0 ? max : 1;
});

const total7DayVolume = computed(() => {
    if (!props.sevenDayTrend) return 0;
    return props.sevenDayTrend.reduce((acc, curr) => acc + curr.count, 0);
});

const peakDayTrend = computed(() => {
    if (!props.sevenDayTrend || props.sevenDayTrend.length === 0) {
        return { day: '-', count: 0 };
    }
    return props.sevenDayTrend.reduce(
        (best, curr) => (curr.count > best.count ? curr : best),
        props.sevenDayTrend[0],
    );
});
</script>

<template>
    <div class="p-4 rounded-xl bg-white border border-stone-200/80 shadow-xs flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between pb-1">
                <h2 class="text-base font-bold text-[#2C2C2C] tracking-tight">
                    {{ title }}
                </h2>
                <span class="text-sm font-bold text-stone-700 tabular-nums">
                    {{ total7DayVolume }} Paket
                </span>
            </div>

            <div class="mt-4 flex items-end justify-between gap-2 h-44 sm:h-48 px-1">
                <div
                    v-for="item in sevenDayTrend"
                    :key="item.date"
                    class="flex flex-col items-center flex-1 h-full justify-end group cursor-default"
                >
                    <span class="text-xs sm:text-sm font-bold text-stone-600 mb-1 opacity-80 group-hover:opacity-100 transition-opacity tabular-nums">
                        {{ item.count }}
                    </span>

                    <div class="w-full max-w-[28px] bg-stone-100 rounded-t-md relative flex items-end overflow-hidden h-32 sm:h-36">
                        <div
                            class="w-full rounded-t-md transition-all duration-500 ease-out bg-[#853953] group-hover:bg-[#6c2e43]"
                            :style="{
                                height: item.count > 0 ? `${Math.max(14, Math.round((item.count / maxTrendCount) * 100))}%` : '4px',
                                opacity: item.count > 0 ? '1' : '0.2',
                            }"
                        />
                    </div>

                    <span class="text-xs sm:text-sm font-semibold text-stone-600 mt-2">
                        {{ item.day }}
                    </span>
                </div>
            </div>
        </div>

        <div class="mt-3.5 grid grid-cols-2 gap-2">
            <div class="p-2.5 rounded-lg bg-stone-50/80">
                <span class="text-xs font-semibold text-stone-500 block">
                    Tren vs Pekan Lalu
                </span>
                <div class="mt-0.5 text-sm sm:text-base font-bold text-[#2C2C2C] flex items-center gap-1.5 truncate">
                    <span class="flex items-center gap-0.5 font-extrabold text-[#853953] tabular-nums">
                        <TrendingUp v-if="(weeklyGrowth ?? 0) >= 0" class="size-3.5 sm:size-4 inline shrink-0" />
                        <TrendingDown v-else class="size-3.5 sm:size-4 inline shrink-0" />
                        <span>{{ (weeklyGrowth ?? 0) > 0 ? `+${weeklyGrowth}%` : `${weeklyGrowth ?? 0}%` }}</span>
                    </span>
                    <span class="text-xs font-medium text-stone-500">
                        {{ (weeklyGrowth ?? 0) >= 0 ? 'Meningkat' : 'Menurun' }}
                    </span>
                </div>
            </div>

            <div class="p-2.5 rounded-lg bg-stone-50/80">
                <span class="text-xs font-semibold text-stone-500 block">
                    Rata-rata Harian
                </span>
                <div class="mt-0.5 text-sm sm:text-base font-bold text-[#2C2C2C] tabular-nums truncate">
                    ~{{ Math.round(total7DayVolume / 7) }} <span class="text-xs font-medium text-stone-500">paket / hari</span>
                </div>
            </div>

            <div class="p-2.5 rounded-lg bg-stone-50/80">
                <span class="text-xs font-semibold text-stone-500 block">
                    Puncak Harian
                </span>
                <div class="mt-0.5 text-sm sm:text-base font-bold text-[#2C2C2C] tabular-nums truncate">
                    {{ peakDayTrend.count }} <span class="text-xs font-medium text-stone-500">paket ({{ peakDayTrend.day }})</span>
                </div>
            </div>

            <div class="p-2.5 rounded-lg bg-stone-50/80">
                <span class="text-xs font-semibold text-stone-500 block">
                    Rasio Terkirim
                </span>
                <div class="mt-0.5 text-sm sm:text-base font-bold text-[#853953] tabular-nums truncate">
                    {{ deliveryRate }}%
                    <span class="text-xs font-medium text-stone-500">({{ deliveredCount }} selesai)</span>
                </div>
            </div>
        </div>
    </div>
</template>

