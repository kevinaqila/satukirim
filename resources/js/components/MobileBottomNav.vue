<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    PackagePlus,
    ReceiptText,
    ShieldCheck,
    Store,
    Truck,
    User,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import { edit } from '@/routes/profile';

const { isCurrentOrParentUrl } = useCurrentUrl();
const page = usePage();
const user = computed(() => page.props.auth?.user);
const isSuperAdmin = computed(() => user.value?.role === 'super_admin');
</script>

<template>
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md border-t border-stone-200/90 shadow-[0_-4px_20px_rgba(0,0,0,0.04)] select-none">
        <div v-if="isSuperAdmin" class="grid grid-cols-5 items-center justify-around h-16 max-w-md mx-auto px-1 pb-safe">
            <Link
                :href="dashboard()"
                :class="[
                    'flex flex-col items-center justify-center py-1 transition-colors group',
                    isCurrentOrParentUrl(dashboard())
                        ? 'text-[#853953] font-semibold'
                        : 'text-stone-500 hover:text-stone-800 font-medium',
                ]"
            >
                <div class="p-1">
                    <LayoutGrid class="size-5 transition-transform group-hover:scale-110" />
                </div>
                <span class="text-[10px] tracking-tight">Beranda</span>
            </Link>

            <Link
                href="/admin/users"
                :class="[
                    'flex flex-col items-center justify-center py-1 transition-colors group',
                    isCurrentOrParentUrl('/admin/users')
                        ? 'text-[#853953] font-semibold'
                        : 'text-stone-500 hover:text-stone-800 font-medium',
                ]"
            >
                <div class="p-1">
                    <Users class="size-5 transition-transform group-hover:scale-110" />
                </div>
                <span class="text-[10px] tracking-tight">Pengguna</span>
            </Link>

            <Link
                href="/admin/transactions"
                :class="[
                    'flex flex-col items-center justify-center py-1 transition-colors group',
                    isCurrentOrParentUrl('/admin/transactions')
                        ? 'text-[#853953] font-semibold'
                        : 'text-stone-500 hover:text-stone-800 font-medium',
                ]"
            >
                <div class="p-1">
                    <ReceiptText class="size-5 transition-transform group-hover:scale-110" />
                </div>
                <span class="text-[10px] tracking-tight">Transaksi</span>
            </Link>

            <Link
                href="/admin/audit-logs"
                :class="[
                    'flex flex-col items-center justify-center py-1 transition-colors group',
                    isCurrentOrParentUrl('/admin/audit-logs')
                        ? 'text-[#853953] font-semibold'
                        : 'text-stone-500 hover:text-stone-800 font-medium',
                ]"
            >
                <div class="p-1">
                    <ShieldCheck class="size-5 transition-transform group-hover:scale-110" />
                </div>
                <span class="text-[10px] tracking-tight">Audit</span>
            </Link>

            <Link
                href="/settings"
                :class="[
                    'flex flex-col items-center justify-center py-1 transition-colors group',
                    isCurrentOrParentUrl(edit()) || isCurrentOrParentUrl('/settings')
                        ? 'text-[#853953] font-semibold'
                        : 'text-stone-500 hover:text-stone-800 font-medium',
                ]"
            >
                <div class="p-1">
                    <User class="size-5 transition-transform group-hover:scale-110" />
                </div>
                <span class="text-[10px] tracking-tight">Akun</span>
            </Link>
        </div>

        <div v-else class="grid grid-cols-5 items-end justify-around h-16 max-w-md mx-auto px-1 pb-safe">
            <Link
                :href="dashboard()"
                :class="[
                    'flex flex-col items-center justify-center py-1 mb-2 transition-colors group',
                    isCurrentOrParentUrl(dashboard())
                        ? 'text-[#853953] font-semibold'
                        : 'text-stone-500 hover:text-stone-800 font-medium',
                ]"
            >
                <div class="p-1">
                    <LayoutGrid class="size-5 transition-transform group-hover:scale-110" />
                </div>
                <span class="text-[10px] tracking-tight">Beranda</span>
            </Link>

            <Link
                href="/shipments"
                :class="[
                    'flex flex-col items-center justify-center py-1 mb-2 transition-colors group',
                    isCurrentOrParentUrl('/shipments') && !isCurrentOrParentUrl('/shipments/create')
                        ? 'text-[#853953] font-semibold'
                        : 'text-stone-500 hover:text-stone-800 font-medium',
                ]"
            >
                <div class="p-1">
                    <Truck class="size-5 transition-transform group-hover:scale-110" />
                </div>
                <span class="text-[10px] tracking-tight">Pengiriman</span>
            </Link>

            <Link
                href="/shipments/create"
                class="flex flex-col items-center justify-center -mt-5 group"
            >
                <div class="size-[52px] rounded-full bg-gradient-to-tr from-[#853953] to-[#A23F5C] text-white flex items-center justify-center shadow-lg shadow-[#853953]/35 border-[3.5px] border-white transition-all duration-200 group-hover:scale-105 group-active:scale-95">
                    <PackagePlus class="size-6" />
                </div>
                <span class="text-[10px] font-bold text-[#853953] mt-0.5 tracking-tight">Kirim</span>
            </Link>

            <Link
                href="/wallet"
                :class="[
                    'flex flex-col items-center justify-center py-1 mb-2 transition-colors group',
                    isCurrentOrParentUrl('/wallet')
                        ? 'text-[#853953] font-semibold'
                        : 'text-stone-500 hover:text-stone-800 font-medium',
                ]"
            >
                <div class="p-1">
                    <Wallet class="size-5 transition-transform group-hover:scale-110" />
                </div>
                <span class="text-[10px] tracking-tight">Dompet</span>
            </Link>

            <Link
                href="/settings"
                :class="[
                    'flex flex-col items-center justify-center py-1 mb-2 transition-colors group',
                    isCurrentOrParentUrl(edit()) || isCurrentOrParentUrl('/settings')
                        ? 'text-[#853953] font-semibold'
                        : 'text-stone-500 hover:text-stone-800 font-medium',
                ]"
            >
                <div class="p-1">
                    <User class="size-5 transition-transform group-hover:scale-110" />
                </div>
                <span class="text-[10px] tracking-tight">Akun</span>
            </Link>
        </div>
    </nav>
</template>
