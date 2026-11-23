<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Check,
    Power,
    Search,
    SlidersHorizontal,
    UserPlus,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AdminUsersTable, { type AdminUserSortField } from '@/components/admin/AdminUsersTable.vue';
import UserToggleDialogs from '@/components/admin/UserToggleDialogs.vue';
import AppTopBar from '@/components/AppTopBar.vue';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import type { AdminUserItem } from '@/types/admin';

const props = defineProps<{
    users: {
        data: AdminUserItem[];
        current_page: number;
        last_page: number;
        total: number;
        from?: number | null;
        to?: number | null;
        links?: Array<{ url: string | null; label: string; active: boolean }>;
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
let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(searchQuery, (val) => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/admin/users', { search: val }, { preserveState: true, preserveScroll: true, replace: true });
    }, 400);
});

const sortField = ref<AdminUserSortField | null>(null);
const sortDirection = ref<'asc' | 'desc'>('asc');

const toggleSort = (field: AdminUserSortField) => {
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

type UserTabId = 'all' | 'umkm' | 'super_admin' | 'active' | 'inactive';
const activeTab = ref<UserTabId>('all');

const userTabs: Array<{ id: UserTabId; label: string }> = [
    { id: 'all', label: 'Semua Pengguna' },
    { id: 'umkm', label: 'Merchant UMKM' },
    { id: 'super_admin', label: 'Super Admin' },
    { id: 'active', label: 'Status Aktif' },
    { id: 'inactive', label: 'Nonaktif' },
];

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
            if (field === 'name') return a.name.localeCompare(b.name) * dir;
            if (field === 'email') return a.email.localeCompare(b.email) * dir;
            if (field === 'phone') return (a.phone || '').localeCompare(b.phone || '') * dir;
            if (field === 'role') return a.role.localeCompare(b.role) * dir;
            if (field === 'stores_count') return (a.stores_count - b.stores_count) * dir;
            if (field === 'shipments_count') return (a.shipments_count - b.shipments_count) * dir;
            if (field === 'created_at') return (new Date(a.created_at).getTime() - new Date(b.created_at).getTime()) * dir;
            if (field === 'is_active') return (Number(a.is_active) - Number(b.is_active)) * dir;
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
    selectedIds.value = checked === true ? displayedUsers.value.map((u) => u.id) : [];
};

const toggleSelectRow = (id: number) => {
    const idx = selectedIds.value.indexOf(id);
    if (idx >= 0) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
};

const userToToggle = ref<AdminUserItem | null>(null);
const isToggleModalOpen = ref(false);
const isToggleLoading = ref(false);

const openToggleStatusModal = (user: AdminUserItem) => {
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
                        v-for="tab in userTabs"
                        :key="tab.id"
                        type="button"
                        @click="activeTab = tab.id"
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

                <div class="flex flex-wrap items-center justify-between sm:justify-end gap-2">
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
                            <span class="text-stone-300">-</span>
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
                            <DropdownMenuCheckboxItem :checked="columnVisibility.id" @update:checked="(val) => (columnVisibility.id = val)">
                                ID
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem :checked="columnVisibility.email" @update:checked="(val) => (columnVisibility.email = val)">
                                Email
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem :checked="columnVisibility.phone" @update:checked="(val) => (columnVisibility.phone = val)">
                                No. Telp
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem :checked="columnVisibility.role" @update:checked="(val) => (columnVisibility.role = val)">
                                Peran (Role)
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem :checked="columnVisibility.stores" @update:checked="(val) => (columnVisibility.stores = val)">
                                Aktivitas Toko & Paket
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem :checked="columnVisibility.joined" @update:checked="(val) => (columnVisibility.joined = val)">
                                Tanggal Gabung
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuCheckboxItem :checked="columnVisibility.status" @update:checked="(val) => (columnVisibility.status = val)">
                                Status Akun
                            </DropdownMenuCheckboxItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <Link
                        href="/admin/users/create"
                        class="inline-flex h-9 items-center gap-1.5 px-3 text-sm font-semibold text-white rounded-md bg-[#853953] hover:bg-[#6c2e43] transition-colors shrink-0 shadow-2xs"
                    >
                        <UserPlus class="size-4" />
                        <span>Tambah Pengguna</span>
                    </Link>
                </div>
            </div>

            <AdminUsersTable
                v-if="displayedUsers.length > 0"
                :displayed-users="displayedUsers"
                :selected-ids="selectedIds"
                :is-all-selected="isAllSelected"
                :is-some-selected="isSomeSelected"
                :sort-field="sortField"
                :sort-direction="sortDirection"
                :column-visibility="columnVisibility"
                :pagination="{
                    links: users.links,
                    from: users.from,
                    to: users.to,
                    total: users.total,
                }"
                @toggle-select-all="toggleSelectAll"
                @toggle-select-row="toggleSelectRow"
                @toggle-sort="toggleSort"
                @open-toggle="openToggleStatusModal"
            />

            <div v-else class="py-12 text-center text-stone-500 text-xs">
                Tidak ada data pengguna yang sesuai filter atau pencarian.
            </div>
        </div>

        <UserToggleDialogs
            v-model:single-open="isToggleModalOpen"
            :user-to-toggle="userToToggle"
            :is-single-loading="isToggleLoading"
            v-model:bulk-open="isBulkModalOpen"
            :bulk-toggle-status="bulkToggleStatus"
            :selected-count="selectedIds.length"
            :is-bulk-loading="isBulkLoading"
            @confirm-single="confirmToggleStatus"
            @confirm-bulk="confirmBulkToggle"
        />
    </div>
</template>
