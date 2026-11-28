<script setup lang="ts">
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Power,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TablePagination,
    TableRow,
} from '@/components/ui/table';
import { formatDateTime } from '@/lib/status';
import type { AdminUserItem } from '@/types/admin';

export type AdminUserSortField =
    | 'id'
    | 'name'
    | 'email'
    | 'phone'
    | 'role'
    | 'stores_count'
    | 'shipments_count'
    | 'created_at'
    | 'is_active';

defineProps<{
    displayedUsers: AdminUserItem[];
    currentUserId?: number | null;
    selectedIds: number[];
    isAllSelected: boolean;
    isSomeSelected: boolean;
    sortField: AdminUserSortField | null;
    sortDirection: 'asc' | 'desc';
    columnVisibility: {
        id: boolean;
        email: boolean;
        phone: boolean;
        role: boolean;
        stores: boolean;
        joined: boolean;
        status: boolean;
    };
    pagination: {
        links?: Array<{ url: string | null; label: string; active: boolean }>;
        from?: number | null;
        to?: number | null;
        total: number;
    };
}>();

const emit = defineEmits<{
    toggleSelectAll: [checked: boolean | 'indeterminate'];
    toggleSelectRow: [id: number];
    toggleSort: [field: AdminUserSortField];
    openToggle: [user: AdminUserItem];
}>();
</script>

<template>
    <div>
        <Table>
            <TableHeader>
                <TableRow class="hover:bg-transparent border-b border-stone-200">
                    <TableHead class="w-10 pl-4 pr-2">
                        <Checkbox
                            :checked="isAllSelected ? true : isSomeSelected ? 'indeterminate' : false"
                            :disabled="displayedUsers.every((u) => u.id === currentUserId)"
                            @update:checked="emit('toggleSelectAll', $event)"
                            aria-label="Pilih semua baris"
                        />
                    </TableHead>

                    <TableHead v-if="columnVisibility.id" class="w-16">
                        <button
                            type="button"
                            @click="emit('toggleSort', 'id')"
                            class="group/th inline-flex items-center gap-1.5 text-sm font-semibold text-stone-800 hover:text-[#853953] cursor-pointer select-none"
                        >
                            <span>ID</span>
                            <ArrowUp v-if="sortField === 'id' && sortDirection === 'asc'" class="size-3.5 text-[#853953]" />
                            <ArrowDown v-else-if="sortField === 'id' && sortDirection === 'desc'" class="size-3.5 text-[#853953]" />
                            <ArrowUpDown v-else class="size-3 text-stone-300 group-hover/th:text-stone-500 transition-colors" />
                        </button>
                    </TableHead>

                    <TableHead>
                        <button
                            type="button"
                            @click="emit('toggleSort', 'name')"
                            class="group/th inline-flex items-center gap-1.5 text-sm font-semibold text-stone-800 hover:text-[#853953] cursor-pointer select-none"
                        >
                            <span>Nama</span>
                            <ArrowUp v-if="sortField === 'name' && sortDirection === 'asc'" class="size-3.5 text-[#853953]" />
                            <ArrowDown v-else-if="sortField === 'name' && sortDirection === 'desc'" class="size-3.5 text-[#853953]" />
                            <ArrowUpDown v-else class="size-3 text-stone-300 group-hover/th:text-stone-500 transition-colors" />
                        </button>
                    </TableHead>

                    <TableHead v-if="columnVisibility.email">
                        <button
                            type="button"
                            @click="emit('toggleSort', 'email')"
                            class="group/th inline-flex items-center gap-1.5 text-sm font-semibold text-stone-800 hover:text-[#853953] cursor-pointer select-none"
                        >
                            <span>Email</span>
                            <ArrowUp v-if="sortField === 'email' && sortDirection === 'asc'" class="size-3.5 text-[#853953]" />
                            <ArrowDown v-else-if="sortField === 'email' && sortDirection === 'desc'" class="size-3.5 text-[#853953]" />
                            <ArrowUpDown v-else class="size-3 text-stone-300 group-hover/th:text-stone-500 transition-colors" />
                        </button>
                    </TableHead>

                    <TableHead v-if="columnVisibility.phone">
                        <button
                            type="button"
                            @click="emit('toggleSort', 'phone')"
                            class="group/th inline-flex items-center gap-1.5 text-sm font-semibold text-stone-800 hover:text-[#853953] cursor-pointer select-none"
                        >
                            <span>No. Telp</span>
                            <ArrowUp v-if="sortField === 'phone' && sortDirection === 'asc'" class="size-3.5 text-[#853953]" />
                            <ArrowDown v-else-if="sortField === 'phone' && sortDirection === 'desc'" class="size-3.5 text-[#853953]" />
                            <ArrowUpDown v-else class="size-3 text-stone-300 group-hover/th:text-stone-500 transition-colors" />
                        </button>
                    </TableHead>

                    <TableHead v-if="columnVisibility.role">
                        <button
                            type="button"
                            @click="emit('toggleSort', 'role')"
                            class="group/th inline-flex items-center gap-1.5 text-sm font-semibold text-stone-800 hover:text-[#853953] cursor-pointer select-none"
                        >
                            <span>Peran</span>
                            <ArrowUp v-if="sortField === 'role' && sortDirection === 'asc'" class="size-3.5 text-[#853953]" />
                            <ArrowDown v-else-if="sortField === 'role' && sortDirection === 'desc'" class="size-3.5 text-[#853953]" />
                            <ArrowUpDown v-else class="size-3 text-stone-300 group-hover/th:text-stone-500 transition-colors" />
                        </button>
                    </TableHead>

                    <TableHead v-if="columnVisibility.stores">
                        <button
                            type="button"
                            @click="emit('toggleSort', 'shipments_count')"
                            class="group/th inline-flex items-center gap-1.5 text-sm font-semibold text-stone-800 hover:text-[#853953] cursor-pointer select-none"
                        >
                            <span>Toko & Paket</span>
                            <ArrowUp v-if="sortField === 'shipments_count' && sortDirection === 'asc'" class="size-3.5 text-[#853953]" />
                            <ArrowDown v-else-if="sortField === 'shipments_count' && sortDirection === 'desc'" class="size-3.5 text-[#853953]" />
                            <ArrowUpDown v-else class="size-3 text-stone-300 group-hover/th:text-stone-500 transition-colors" />
                        </button>
                    </TableHead>

                    <TableHead v-if="columnVisibility.joined">
                        <button
                            type="button"
                            @click="emit('toggleSort', 'created_at')"
                            class="group/th inline-flex items-center gap-1.5 text-sm font-semibold text-stone-800 hover:text-[#853953] cursor-pointer select-none"
                        >
                            <span>Tanggal Gabung</span>
                            <ArrowUp v-if="sortField === 'created_at' && sortDirection === 'asc'" class="size-3.5 text-[#853953]" />
                            <ArrowDown v-else-if="sortField === 'created_at' && sortDirection === 'desc'" class="size-3.5 text-[#853953]" />
                            <ArrowUpDown v-else class="size-3 text-stone-300 group-hover/th:text-stone-500 transition-colors" />
                        </button>
                    </TableHead>

                    <TableHead v-if="columnVisibility.status" class="text-center">
                        <button
                            type="button"
                            @click="emit('toggleSort', 'is_active')"
                            class="group/th inline-flex items-center justify-center gap-1.5 text-sm font-semibold text-stone-800 hover:text-[#853953] cursor-pointer select-none mx-auto"
                        >
                            <span>Status</span>
                            <ArrowUp v-if="sortField === 'is_active' && sortDirection === 'asc'" class="size-3.5 text-[#853953]" />
                            <ArrowDown v-else-if="sortField === 'is_active' && sortDirection === 'desc'" class="size-3.5 text-[#853953]" />
                            <ArrowUpDown v-else class="size-3 text-stone-300 group-hover/th:text-stone-500 transition-colors" />
                        </button>
                    </TableHead>

                    <TableHead class="w-14 pr-4 text-center">
                        <span class="text-sm font-semibold text-stone-800">Aksi</span>
                    </TableHead>
                </TableRow>
            </TableHeader>

            <TableBody>
                <TableRow
                    v-for="user in displayedUsers"
                    :key="user.id"
                    :data-state="selectedIds.includes(user.id) ? 'selected' : undefined"
                    class="group"
                >
                    <TableCell class="pl-4 pr-2">
                        <Checkbox
                            :checked="selectedIds.includes(user.id)"
                            :disabled="user.id === currentUserId"
                            @update:checked="() => emit('toggleSelectRow', user.id)"
                            :aria-label="`Pilih ${user.name}`"
                        />
                    </TableCell>

                    <TableCell v-if="columnVisibility.id" class="text-sm font-mono font-medium text-stone-500 tabular-nums whitespace-nowrap">
                        {{ user.id }}
                    </TableCell>

                    <TableCell>
                        <div class="font-semibold text-sm text-stone-900 truncate max-w-[200px]" :title="user.name">
                            {{ user.name }}
                        </div>
                    </TableCell>

                    <TableCell v-if="columnVisibility.email">
                        <div class="text-sm text-stone-600 truncate max-w-[220px]" :title="user.email">
                            {{ user.email }}
                        </div>
                    </TableCell>

                    <TableCell v-if="columnVisibility.phone" class="text-sm text-stone-600 tabular-nums whitespace-nowrap">
                        {{ user.phone || '-' }}
                    </TableCell>

                    <TableCell v-if="columnVisibility.role">
                        <span class="text-sm font-semibold" :class="user.role === 'super_admin' ? 'text-primary' : 'text-stone-700'">
                            {{ user.role === 'super_admin' ? 'Super Admin' : 'Merchant UMKM' }}
                        </span>
                    </TableCell>

                    <TableCell v-if="columnVisibility.stores">
                        <div class="inline-flex items-center gap-2 text-sm text-stone-700 tabular-nums">
                            <span><strong class="font-bold text-stone-900">{{ user.stores_count }}</strong> Toko</span>
                            <span class="text-stone-300">-</span>
                            <span><strong class="font-bold text-stone-900">{{ user.shipments_count }}</strong> Paket</span>
                        </div>
                    </TableCell>

                    <TableCell v-if="columnVisibility.joined" class="text-sm font-medium text-stone-600 tabular-nums whitespace-nowrap">
                        {{ formatDateTime(user.created_at) }}
                    </TableCell>

                    <TableCell v-if="columnVisibility.status" class="text-center">
                        <span class="text-sm font-bold text-primary">
                            {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </TableCell>

                    <TableCell class="pr-4 text-center">
                        <Button
                            size="icon-sm"
                            variant="ghost"
                            :disabled="user.id === currentUserId"
                            @click="emit('openToggle', user)"
                            class="size-8 rounded-md"
                            :class="
                                user.id === currentUserId
                                    ? 'text-stone-300 cursor-not-allowed'
                                    : user.is_active
                                        ? 'text-stone-500 hover:text-rose-600 hover:bg-rose-50 cursor-pointer'
                                        : 'text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 cursor-pointer'
                            "
                            :title="
                                user.id === currentUserId
                                    ? 'Tidak dapat menonaktifkan akun sendiri'
                                    : user.is_active
                                        ? 'Nonaktifkan Akun'
                                        : 'Aktifkan Akun'
                            "
                        >
                            <Power class="size-4" />
                        </Button>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>

        <TablePagination
            :links="pagination.links"
            :from="pagination.from"
            :to="pagination.to"
            :total="pagination.total"
            :selected-count="selectedIds.length"
        />
    </div>
</template>

