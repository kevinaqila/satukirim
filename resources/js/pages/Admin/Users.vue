<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Check,
    CheckCircle2,
    DollarSign,
    Loader2,
    PackageCheck,
    Power,
    Search,
    SlidersHorizontal,
    Users,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AppTopBar from '@/components/AppTopBar.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
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

export interface UserItem {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    role: string;
    is_active: boolean;
    stores_count: number;
    shipments_count: number;
    created_at: string;
}

const props = defineProps<{
    users: {
        data: UserItem[];
        current_page: number;
        last_page: number;
        total: number;
        from?: number | null;
        to?: number | null;
        links?: any[];
    };
    stats?: {
        total_users: number;
        active_umkm: number;
        total_shipments: number;
        total_volume: number;
    };
    filters: {
        search: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Super Admin',
                href: '/admin/users',
            },
            {
                title: 'Kelola Pengguna',
                href: '/admin/users',
            },
        ],
    },
});

const searchQuery = ref(props.filters.search || '');
let searchTimer: any = null;
watch(searchQuery, (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/admin/users', { search: val }, { preserveState: true, preserveScroll: true, replace: true });
    }, 400);
});

type SortField = 'id' | 'name' | 'email' | 'phone' | 'role' | 'stores_count' | 'shipments_count' | 'created_at' | 'is_active';
const sortField = ref<SortField | null>(null);
const sortDirection = ref<'asc' | 'desc'>('asc');

const toggleSort = (field: SortField) => {
    if (sortField.value === field) {
        if (sortDirection.value === 'asc') {
            sortDirection.value = 'desc';
        } else {
            sortField.value = null;
            sortDirection.value = 'asc';
        }
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

const columnVisibility = ref({
    id: false,
    email: true,
    phone: true,
    role: true,
    stores: true,
    joined: true,
    status: true,
});

const activeTab = ref<'all' | 'umkm' | 'super_admin' | 'active' | 'inactive'>('all');

const displayedUsers = computed(() => {
    let list = [...props.users.data];

    if (activeTab.value === 'umkm') {
        list = list.filter((u) => u.role === 'umkm');
    } else if (activeTab.value === 'super_admin') {
        list = list.filter((u) => u.role === 'super_admin');
    } else if (activeTab.value === 'active') {
        list = list.filter((u) => u.is_active);
    } else if (activeTab.value === 'inactive') {
        list = list.filter((u) => !u.is_active);
    }

    if (sortField.value) {
        const field = sortField.value;
        const dir = sortDirection.value === 'asc' ? 1 : -1;
        list.sort((a, b) => {
            if (field === 'id') return (a.id - b.id) * dir;
            if (field === 'name') {
                return a.name.localeCompare(b.name) * dir;
            }
            if (field === 'email') {
                return a.email.localeCompare(b.email) * dir;
            }
            if (field === 'phone') {
                return (a.phone || '').localeCompare(b.phone || '') * dir;
            }
            if (field === 'role') {
                return a.role.localeCompare(b.role) * dir;
            }
            if (field === 'stores_count') {
                return (a.stores_count - b.stores_count) * dir;
            }
            if (field === 'shipments_count') {
                return (a.shipments_count - b.shipments_count) * dir;
            }
            if (field === 'created_at') {
                return (new Date(a.created_at).getTime() - new Date(b.created_at).getTime()) * dir;
            }
            if (field === 'is_active') {
                return (Number(a.is_active) - Number(b.is_active)) * dir;
            }
            return 0;
        });
    }

    return list;
});

const selectedIds = ref<number[]>([]);

const isAllSelected = computed(() => {
    if (displayedUsers.value.length === 0) return false;
    return displayedUsers.value.every((u) => selectedIds.value.includes(u.id));
});

const isSomeSelected = computed(() => {
    return displayedUsers.value.some((u) => selectedIds.value.includes(u.id)) && !isAllSelected.value;
});

const toggleSelectAll = (checked: boolean | 'indeterminate') => {
    if (checked === true) {
        selectedIds.value = displayedUsers.value.map((u) => u.id);
    } else {
        selectedIds.value = [];
    }
};

const toggleSelectRow = (id: number) => {
    const idx = selectedIds.value.indexOf(id);
    if (idx >= 0) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
};

const userToToggle = ref<UserItem | null>(null);
const isToggleModalOpen = ref(false);
const isToggleLoading = ref(false);

const openToggleStatusModal = (user: UserItem) => {
    userToToggle.value = user;
    isToggleModalOpen.value = true;
};

const confirmToggleStatus = () => {
    if (!userToToggle.value) return;
    isToggleLoading.value = true;
    router.patch(
        `/admin/users/${userToToggle.value.id}/toggle`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isToggleLoading.value = false;
                isToggleModalOpen.value = false;
                userToToggle.value = null;
            },
        },
    );
};

const bulkToggleStatus = ref<boolean | null>(null);
const isBulkModalOpen = ref(false);
const isBulkLoading = ref(false);

const openBulkToggleModal = (status: boolean) => {
    bulkToggleStatus.value = status;
    isBulkModalOpen.value = true;
};

const confirmBulkToggle = () => {
    if (bulkToggleStatus.value === null || selectedIds.value.length === 0) return;
    isBulkLoading.value = true;
    router.patch(
        '/admin/users/bulk-toggle',
        {
            user_ids: selectedIds.value,
            status: bulkToggleStatus.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
            },
            onFinish: () => {
                isBulkLoading.value = false;
                isBulkModalOpen.value = false;
                bulkToggleStatus.value = null;
            },
        },
    );
};
</script>

<template>
    <Head title="Kelola Pengguna - Super Admin SatuKirim" />

    <AppTopBar title="Kelola Pengguna" />

    <div class="flex h-full flex-1 flex-col gap-3.5 p-3 sm:p-4 lg:p-5 w-full">
        <div class="rounded-none border border-stone-200 bg-white overflow-hidden shadow-2xs">
            <div class="flex flex-wrap items-center justify-between gap-2 px-3.5 sm:px-4 border-b border-stone-200 bg-stone-50/60">
                <div class="flex items-center gap-1 overflow-x-auto -mb-px">
                    <button
                        v-for="tab in [
                            { id: 'all', label: 'Semua Pengguna' },
                            { id: 'umkm', label: 'Merchant UMKM' },
                            { id: 'super_admin', label: 'Super Admin' },
                            { id: 'active', label: 'Status Aktif' },
                            { id: 'inactive', label: 'Nonaktif' },
                        ]"
                        :key="tab.id"
                        type="button"
                        @click="activeTab = tab.id as any"
                        class="px-3 py-2.5 text-sm font-semibold border-b-2 transition-colors cursor-pointer whitespace-nowrap"
                        :class="
                            activeTab === tab.id
                                ? 'border-[#853953] text-[#853953] bg-white/80'
                                : 'border-transparent text-stone-600 hover:text-stone-900 hover:border-stone-300'
                        "
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 px-3.5 sm:px-4 py-2.5 border-b border-stone-200 bg-white">
                <div class="relative flex-1 max-w-md">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-stone-400 pointer-events-none" />
                    <Input
                        v-model="searchQuery"
                        placeholder="Cari nama pengguna, email, atau nomor HP..."
                        class="pl-9 pr-8 text-sm h-9 rounded-md border-stone-200"
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="searchQuery = ''"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-700 cursor-pointer"
                        aria-label="Bersihkan pencarian"
                    >
                        <X class="size-4" />
                    </button>
                </div>

                <div class="flex items-center justify-between sm:justify-end gap-2">
                    <template v-if="selectedIds.length > 0">
                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#853953]/10 border border-[#853953]/25">
                            <span class="text-sm font-bold text-[#853953] tabular-nums">
                                {{ selectedIds.length }} dipilih
                            </span>
                            <span class="text-[#853953]/30">|</span>
                            <button
                                type="button"
                                :disabled="isBulkLoading"
                                @click="openBulkToggleModal(true)"
                                class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-700 hover:underline cursor-pointer disabled:opacity-50"
                            >
                                <Check class="size-3.5" />
                                Aktifkan
                            </button>
                            <span class="text-stone-300">•</span>
                            <button
                                type="button"
                                :disabled="isBulkLoading"
                                @click="openBulkToggleModal(false)"
                                class="inline-flex items-center gap-1 text-sm font-semibold text-rose-700 hover:underline cursor-pointer disabled:opacity-50"
                            >
                                <Power class="size-3.5" />
                                Nonaktifkan
                            </button>
                            <button
                                type="button"
                                @click="selectedIds = []"
                                class="ml-1 text-stone-500 hover:text-stone-800 cursor-pointer"
                                title="Batal pilih"
                            >
                                <X class="size-3.5" />
                            </button>
                        </div>
                    </template>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="inline-flex h-9 items-center gap-1.5 px-3 text-sm font-semibold text-stone-700 rounded-md border border-stone-200 bg-white hover:bg-stone-50 hover:text-stone-900 transition-colors cursor-pointer shrink-0"
                                aria-label="Atur tampilan kolom"
                            >
                                <SlidersHorizontal class="size-4 text-stone-500" />
                                <span>Kolom</span>
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-48 text-sm">
                            <DropdownMenuLabel class="text-sm font-semibold text-stone-700">Tampilkan Kolom</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.id"
                                @update:checked="(val) => (columnVisibility.id = val)"
                            >
                                ID
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.email"
                                @update:checked="(val) => (columnVisibility.email = val)"
                            >
                                Email
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.phone"
                                @update:checked="(val) => (columnVisibility.phone = val)"
                            >
                                No. Telp
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.role"
                                @update:checked="(val) => (columnVisibility.role = val)"
                            >
                                Peran (Role)
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.stores"
                                @update:checked="(val) => (columnVisibility.stores = val)"
                            >
                                Aktivitas Toko & Paket
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.joined"
                                @update:checked="(val) => (columnVisibility.joined = val)"
                            >
                                Tanggal Gabung
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem
                                :checked="columnVisibility.status"
                                @update:checked="(val) => (columnVisibility.status = val)"
                            >
                                Status Akun
                            </DropdownMenuCheckboxItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <div v-if="displayedUsers.length > 0">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent border-b border-stone-200">
                            <TableHead class="w-10 pl-4 pr-2">
                                <Checkbox
                                    :checked="isAllSelected ? true : isSomeSelected ? 'indeterminate' : false"
                                    @update:checked="toggleSelectAll"
                                    aria-label="Pilih semua baris"
                                />
                            </TableHead>

                            <TableHead v-if="columnVisibility.id" class="w-16">
                                <button
                                    type="button"
                                    @click="toggleSort('id')"
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
                                    @click="toggleSort('name')"
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
                                    @click="toggleSort('email')"
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
                                    @click="toggleSort('phone')"
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
                                    @click="toggleSort('role')"
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
                                    @click="toggleSort('shipments_count')"
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
                                    @click="toggleSort('created_at')"
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
                                    @click="toggleSort('is_active')"
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
                                    @update:checked="() => toggleSelectRow(user.id)"
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
                                    <span class="text-stone-300">•</span>
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
                                    @click="openToggleStatusModal(user)"
                                    class="size-8 rounded-md cursor-pointer"
                                    :class="
                                        user.is_active
                                            ? 'text-stone-500 hover:text-rose-600 hover:bg-rose-50'
                                            : 'text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50'
                                    "
                                    :title="user.is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun'"
                                >
                                    <Power class="size-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <TablePagination
                    :links="users.links"
                    :from="users.from"
                    :to="users.to"
                    :total="users.total"
                    :selected-count="selectedIds.length"
                />
            </div>

            <div v-else class="py-12 text-center text-stone-500 text-xs">
                Tidak ada data pengguna yang sesuai filter atau pencarian.
            </div>
        </div>

        <Dialog :open="isToggleModalOpen" @update:open="(val) => (isToggleModalOpen = val)">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <div class="flex items-center gap-3">
                        <div
                            class="size-10 rounded-full flex items-center justify-center shrink-0"
                            :class="userToToggle?.is_active ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'"
                        >
                            <Power class="size-5" />
                        </div>
                        <DialogTitle class="text-base sm:text-lg font-bold text-stone-900">
                            {{ userToToggle?.is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}
                        </DialogTitle>
                    </div>
                    <DialogDescription class="text-xs sm:text-sm text-stone-600 mt-2">
                        <template v-if="userToToggle?.is_active">
                            Apakah Anda yakin ingin menonaktifkan akun <strong class="text-stone-900 font-semibold">{{ userToToggle?.name }}</strong>? Pengguna tidak akan dapat login atau melakukan transaksi hingga diaktifkan kembali.
                        </template>
                        <template v-else>
                            Apakah Anda yakin ingin mengaktifkan akun <strong class="text-stone-900 font-semibold">{{ userToToggle?.name }}</strong>? Pengguna akan dapat login dan mengakses layanannya kembali.
                        </template>
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="pt-3 flex items-center justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="isToggleLoading"
                        @click="isToggleModalOpen = false"
                        class="h-9 px-4 text-xs sm:text-sm font-semibold bg-white border-stone-200 text-stone-700 hover:bg-stone-50 cursor-pointer shadow-2xs"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        :disabled="isToggleLoading"
                        @click="confirmToggleStatus"
                        class="h-9 px-4 rounded-md text-xs sm:text-sm font-semibold shadow-xs cursor-pointer transition-colors"
                        :class="
                            userToToggle?.is_active
                                ? 'bg-rose-700 hover:bg-rose-800 text-white'
                                : 'bg-emerald-700 hover:bg-emerald-800 text-white'
                        "
                    >
                        <Loader2 v-if="isToggleLoading" class="mr-2 size-4 animate-spin" />
                        {{ userToToggle?.is_active ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog :open="isBulkModalOpen" @update:open="(val) => (isBulkModalOpen = val)">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <div class="flex items-center gap-3">
                        <div
                            class="size-10 rounded-full flex items-center justify-center shrink-0"
                            :class="bulkToggleStatus ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                        >
                            <Power class="size-5" />
                        </div>
                        <DialogTitle class="text-base sm:text-lg font-bold text-stone-900">
                            {{ bulkToggleStatus ? 'Aktifkan Akun Terpilih' : 'Nonaktifkan Akun Terpilih' }}
                        </DialogTitle>
                    </div>
                    <DialogDescription class="text-xs sm:text-sm text-stone-600 mt-2">
                        Apakah Anda yakin ingin {{ bulkToggleStatus ? 'mengaktifkan' : 'menonaktifkan' }}
                        <strong class="text-stone-900 font-semibold">{{ selectedIds.length }} akun</strong> pengguna terpilih?
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="pt-3 flex items-center justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="isBulkLoading"
                        @click="isBulkModalOpen = false"
                        class="h-9 px-4 text-xs sm:text-sm font-semibold bg-white border-stone-200 text-stone-700 hover:bg-stone-50 cursor-pointer shadow-2xs"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        :disabled="isBulkLoading"
                        @click="confirmBulkToggle"
                        class="h-9 px-4 rounded-md text-xs sm:text-sm font-semibold shadow-xs cursor-pointer transition-colors"
                        :class="
                            bulkToggleStatus
                                ? 'bg-emerald-700 hover:bg-emerald-800 text-white'
                                : 'bg-rose-700 hover:bg-rose-800 text-white'
                        "
                    >
                        <Loader2 v-if="isBulkLoading" class="mr-2 size-4 animate-spin" />
                        {{ bulkToggleStatus ? 'Ya, Aktifkan Semua' : 'Ya, Nonaktifkan Semua' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
