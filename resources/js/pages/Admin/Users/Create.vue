<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Loader2, ShieldCheck } from '@lucide/vue';
import AppTopBar from '@/components/AppTopBar.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

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
            {
                title: 'Tambah Pengguna',
                href: '/admin/users/create',
            },
        ],
    },
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    role: '',
    password: '',
    password_confirmation: '',
});

const handlePhoneInput = (e: Event) => {
    const target = e.target as HTMLInputElement;
    let val = target.value.replace(/\D/g, '');
    if (val.startsWith('0')) {
        val = val.substring(1);
    } else if (val.startsWith('62')) {
        val = val.substring(2);
    }
    form.phone = val;
};

const submit = () => {
    form.post('/admin/users', {
        onError: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <div class="flex min-h-full flex-1 flex-col w-full">
        <Head title="Tambah Pengguna - Super Admin SatuKirim" />

        <AppTopBar title="Tambah Pengguna" back-href="/admin/users" back-mobile-only />

        <div class="flex flex-1 flex-col gap-3.5 sm:gap-4 p-3 sm:p-4 lg:p-5 w-full">
            <div class="rounded-xl border border-stone-200/80 bg-white p-4 sm:p-5 lg:p-6 shadow-xs">
                <form @submit.prevent="submit" class="space-y-4 sm:space-y-5">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
                        <div class="space-y-3 sm:space-y-3.5">
                            <div class="space-y-1.5">
                                <Label for="user-name" class="text-xs sm:text-sm font-semibold text-stone-800">
                                    Nama Lengkap<span class="text-red-500 ml-1">*</span>
                                </Label>
                                <Input
                                    id="user-name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Budi Santoso"
                                    class="h-10 text-xs sm:text-sm bg-white border-stone-200"
                                    autocomplete="name"
                                    required
                                />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="space-y-1.5">
                                <Label for="user-email" class="text-xs sm:text-sm font-semibold text-stone-800">
                                    Alamat Email<span class="text-red-500 ml-1">*</span>
                                </Label>
                                <Input
                                    id="user-email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="budi@tokoberkah.com"
                                    class="h-10 text-xs sm:text-sm bg-white border-stone-200"
                                    autocomplete="email"
                                    required
                                />
                                <InputError :message="form.errors.email" />
                            </div>

                            <div class="space-y-1.5">
                                <Label for="user-phone" class="text-xs sm:text-sm font-semibold text-stone-800">
                                    Nomor WhatsApp / HP <span class="text-stone-400 font-normal">(Opsional)</span>
                                </Label>
                                <div class="relative flex items-center rounded-xl border border-stone-200 bg-white shadow-xs focus-within:border-[#853953] focus-within:ring-[3px] focus-within:ring-[#853953]/20 transition-all overflow-hidden">
                                    <span class="inline-flex items-center px-3.5 text-xs sm:text-sm font-semibold text-stone-600 bg-stone-50 border-r border-stone-200 select-none h-10 shrink-0">
                                        +62
                                    </span>
                                    <input
                                        id="user-phone"
                                        :value="form.phone"
                                        type="tel"
                                        inputmode="numeric"
                                        autocomplete="tel-national"
                                        placeholder="81234567890"
                                        class="w-full h-10 px-3.5 bg-transparent text-xs sm:text-sm text-[#2C2C2C] placeholder:text-stone-400 outline-none"
                                        @input="handlePhoneInput"
                                    />
                                </div>
                                <InputError :message="form.errors.phone" />
                            </div>
                        </div>

                        <div class="space-y-3 sm:space-y-3.5">
                            <div class="space-y-1.5">
                                <Label for="user-role" class="text-xs sm:text-sm font-semibold text-stone-800">
                                    Peran Pengguna<span class="text-red-500 ml-1">*</span>
                                </Label>
                                <Select v-model="form.role">
                                    <SelectTrigger
                                        id="user-role"
                                        class="h-10 w-full rounded-xl border-stone-200 bg-white px-3.5 text-xs sm:text-sm text-stone-900 shadow-xs focus-visible:border-[#853953] focus-visible:ring-[#853953]/20"
                                    >
                                        <SelectValue placeholder="Pilih peran pengguna" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="umkm">UMKM</SelectItem>
                                        <SelectItem value="super_admin">Super Admin</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.role" />
                            </div>

                            <div class="space-y-1.5">
                                <Label for="user-password" class="text-xs sm:text-sm font-semibold text-stone-800">
                                    Kata Sandi<span class="text-red-500 ml-1">*</span>
                                </Label>
                                <PasswordInput
                                    id="user-password"
                                    v-model="form.password"
                                    placeholder="Minimal 8 karakter"
                                    class="h-10 text-xs sm:text-sm bg-white border-stone-200"
                                    autocomplete="new-password"
                                    required
                                />
                                <InputError :message="form.errors.password" />
                            </div>

                            <div class="space-y-1.5">
                                <Label for="user-password-confirmation" class="text-xs sm:text-sm font-semibold text-stone-800">
                                    Konfirmasi Kata Sandi<span class="text-red-500 ml-1">*</span>
                                </Label>
                                <PasswordInput
                                    id="user-password-confirmation"
                                    v-model="form.password_confirmation"
                                    placeholder="Ulangi kata sandi"
                                    class="h-10 text-xs sm:text-sm bg-white border-stone-200"
                                    autocomplete="new-password"
                                    required
                                />
                                <InputError :message="form.errors.password_confirmation" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-stone-200/80 bg-stone-50/70 p-3 sm:p-3.5">
                        <div class="flex items-start gap-2.5">
                            <ShieldCheck class="size-4 text-[#853953] mt-0.5 shrink-0" />
                            <div class="space-y-0.5">
                                <p class="text-xs sm:text-sm font-semibold text-stone-900">
                                    Status Akun Aktif Otomatis
                                </p>
                                <p class="text-[11px] sm:text-xs text-stone-500">
                                    Akun yang dibuat langsung berstatus aktif dan siap digunakan untuk masuk. Jika peran UMKM dipilih, dompet saldo akan dibuat secara otomatis.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-1 sm:pt-2">
                        <Link
                            href="/admin/users"
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
                            Tambah Pengguna
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
