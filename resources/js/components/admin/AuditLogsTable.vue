<script setup lang="ts">
import { Check, Copy } from '@lucide/vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TablePagination,
    TableRow,
} from '@/components/ui/table';
import { useCopyClipboard } from '@/composables/useCopyClipboard';
import { formatDateTime } from '@/lib/status';
import type { AdminAuditLogItem } from '@/types/admin';

defineProps<{
    logs: {
        data: AdminAuditLogItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    columnVisibility: {
        id: boolean;
        tanggal: boolean;
        pengguna: boolean;
        aksi: boolean;
        target: boolean;
        old_values: boolean;
        new_values: boolean;
        ip_address: boolean;
    };
}>();

const { copy, isCopied } = useCopyClipboard(1500);
</script>

<template>
    <div class="overflow-x-auto">
        <Table>
            <TableHeader>
                <TableRow class="bg-stone-50/80 hover:bg-stone-50/80">
                    <TableHead v-if="columnVisibility.id" class="w-16 pl-4 text-sm font-semibold text-stone-800">ID</TableHead>
                    <TableHead v-if="columnVisibility.tanggal" class="w-44 text-sm font-semibold text-stone-800">Tanggal</TableHead>
                    <TableHead v-if="columnVisibility.pengguna" class="w-48 text-sm font-semibold text-stone-800">Pengguna</TableHead>
                    <TableHead v-if="columnVisibility.aksi" class="w-24 text-sm font-semibold text-stone-800">Aksi</TableHead>
                    <TableHead v-if="columnVisibility.target" class="w-48 text-sm font-semibold text-stone-800">Target Data</TableHead>
                    <TableHead v-if="columnVisibility.old_values" class="w-48 max-w-[200px] text-sm font-semibold text-stone-800">Nilai Lama</TableHead>
                    <TableHead v-if="columnVisibility.new_values" class="w-48 max-w-[200px] text-sm font-semibold text-stone-800">Nilai Baru</TableHead>
                    <TableHead v-if="columnVisibility.ip_address" class="w-36 pr-4 text-sm font-semibold text-stone-800">IP Address</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow
                    v-for="log in logs.data"
                    :key="log.id"
                    class="hover:bg-stone-50/60 transition-colors"
                >
                    <TableCell v-if="columnVisibility.id" class="pl-4 font-mono text-sm font-medium text-stone-500 tabular-nums">
                        {{ log.id }}
                    </TableCell>

                    <TableCell v-if="columnVisibility.tanggal" class="text-sm font-medium text-stone-600 tabular-nums whitespace-nowrap">
                        {{ formatDateTime(log.created_at) }}
                    </TableCell>

                    <TableCell v-if="columnVisibility.pengguna">
                        <div v-if="log.user">
                            <div class="text-sm font-semibold text-stone-900 truncate" :title="log.user.name">
                                {{ log.user.name }}
                            </div>
                            <div class="text-xs text-stone-500 mt-0.5">
                                {{ log.user.role === 'super_admin' ? 'Super Admin' : 'Merchant UMKM' }}
                            </div>
                        </div>
                        <div v-else>
                            <div class="text-sm font-semibold text-stone-900">Sistem</div>
                            <div class="text-xs text-stone-500 mt-0.5">Otomatis</div>
                        </div>
                    </TableCell>

                    <TableCell v-if="columnVisibility.aksi" class="text-sm font-medium text-stone-700">
                        {{ log.event === 'created' ? 'Created' : (log.event === 'updated' ? 'Updated' : 'Deleted') }}
                    </TableCell>

                    <TableCell v-if="columnVisibility.target">
                        <div class="text-sm font-bold text-stone-900 truncate">
                            {{ log.auditable_label }}
                        </div>
                        <div class="text-xs text-stone-500">
                            {{ log.auditable_type === 'Shipment' ? 'Pengiriman' : (log.auditable_type === 'Store' ? 'Toko' : (log.auditable_type === 'User' ? 'Pengguna' : log.auditable_type)) }}
                        </div>
                    </TableCell>

                    <TableCell v-if="columnVisibility.old_values" class="align-top py-2.5 whitespace-normal w-48 max-w-[200px]">
                        <div v-if="log.old_formatted && log.old_formatted.length > 0" class="space-y-1 max-w-[185px] whitespace-normal">
                            <div
                                v-for="(item, idx) in log.old_formatted"
                                :key="idx"
                                class="flex items-start gap-1 text-xs text-stone-600 leading-snug whitespace-normal"
                            >
                                <span class="shrink-0 text-stone-400 select-none">-</span>
                                <span class="break-words min-w-0 whitespace-normal">{{ item }}</span>
                            </div>
                        </div>
                        <span v-else class="text-xs text-stone-400">-</span>
                    </TableCell>

                    <TableCell v-if="columnVisibility.new_values" class="align-top py-2.5 whitespace-normal w-48 max-w-[200px]">
                        <div v-if="log.new_formatted && log.new_formatted.length > 0" class="space-y-1 max-w-[185px] whitespace-normal">
                            <div
                                v-for="(item, idx) in log.new_formatted"
                                :key="idx"
                                class="flex items-start gap-1 text-xs text-stone-800 font-medium leading-snug whitespace-normal"
                            >
                                <span class="shrink-0 text-stone-400 select-none">-</span>
                                <span class="break-words min-w-0 whitespace-normal">{{ item }}</span>
                            </div>
                        </div>
                        <span v-else class="text-xs text-stone-400">-</span>
                    </TableCell>

                    <TableCell v-if="columnVisibility.ip_address" class="pr-4 text-sm font-mono text-stone-600 tabular-nums whitespace-nowrap">
                        <div class="inline-flex items-center gap-1.5">
                            <span>{{ log.ip_address || '127.0.0.1' }}</span>
                            <button
                                v-if="log.ip_address"
                                type="button"
                                @click="copy(`ip:${log.id}`, log.ip_address)"
                                class="text-stone-400 hover:text-stone-700 cursor-pointer"
                                title="Salin IP"
                            >
                                <Check v-if="isCopied(`ip:${log.id}`)" class="size-3 text-stone-700" />
                                <Copy v-else class="size-3" />
                            </button>
                        </div>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>

        <TablePagination
            :links="logs.links"
            :from="logs.from"
            :to="logs.to"
            :total="logs.total"
        />
    </div>
</template>

