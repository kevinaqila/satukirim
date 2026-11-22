<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Daftar Akun Baru',
        description: 'Mulai kelola pengiriman tokomu lebih hemat & rapi',
        align: 'start',
    },
});

const handlePhoneInput = (e: Event) => {
    const target = e.target as HTMLInputElement;
    let val = target.value.replace(/\D/g, '');
    if (val.startsWith('0')) {
        val = val.substring(1);
    } else if (val.startsWith('62')) {
        val = val.substring(2);
    }
    target.value = val;
};
</script>

<template>
    <Head title="Daftar Akun Baru" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-3.5"
    >
        <div class="grid gap-3">
            <div class="grid gap-1.5">
                <Label for="name" class="text-sm font-medium text-[#2C2C2C]">Nama Lengkap / Nama Toko</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Toko Berkah Mandiri"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-1.5">
                <Label for="phone" class="text-sm font-medium text-[#2C2C2C]">Nomor WhatsApp / HP</Label>
                <div class="relative flex items-center rounded-xl border border-stone-200 bg-white shadow-xs focus-within:border-[#853953] focus-within:ring-[3px] focus-within:ring-[#853953]/20 transition-all overflow-hidden">
                    <span class="inline-flex items-center px-3.5 text-sm font-semibold text-stone-600 bg-stone-50 border-r border-stone-200 select-none h-10 shrink-0">
                        +62
                    </span>
                    <input
                        id="phone"
                        type="tel"
                        inputmode="numeric"
                        required
                        :tabindex="2"
                        autocomplete="tel-national"
                        name="phone"
                        placeholder="81234567890"
                        class="w-full h-10 px-3.5 bg-transparent text-sm text-[#2C2C2C] placeholder:text-stone-400 outline-none"
                        @input="handlePhoneInput"
                    />
                </div>
                <InputError :message="errors.phone" />
            </div>

            <div class="grid gap-1.5">
                <Label for="email" class="text-sm font-medium text-[#2C2C2C]">Alamat Email</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="3"
                    autocomplete="email"
                    name="email"
                    placeholder="nama@toko.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-1.5">
                <Label for="password" class="text-sm font-medium text-[#2C2C2C]">Kata Sandi</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Minimal 8 karakter"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-1.5">
                <Label for="password_confirmation" class="text-sm font-medium text-[#2C2C2C]">Konfirmasi Kata Sandi</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="5"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Ulangi kata sandi"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="pt-0.5">
                <Label for="terms" class="flex items-start space-x-2.5 cursor-pointer text-xs text-[#2C2C2C]/80 font-normal leading-relaxed select-none">
                    <Checkbox id="terms" name="terms" required :tabindex="6" class="mt-0.5 shrink-0" />
                    <span>
                        Saya telah membaca dan menyetujui
                        <a href="#" @click.prevent class="text-[#853953] font-semibold hover:underline">Syarat & Ketentuan</a>
                        serta
                        <a href="#" @click.prevent class="text-[#853953] font-semibold hover:underline">Kebijakan Privasi</a>
                        SatuKirim.
                    </span>
                </Label>
                <InputError :message="errors.terms" />
            </div>

            <Button
                type="submit"
                size="lg"
                class="mt-1 w-full font-medium"
                :tabindex="7"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                <span>Daftar Akun Sekarang</span>
            </Button>
        </div>

        <div class="relative flex items-center justify-center my-0.5">
            <div class="w-full border-t border-stone-200"></div>
            <span class="absolute bg-white px-3 text-xs uppercase tracking-wider text-stone-400 font-medium">atau</span>
        </div>

        <a
            href="/auth/google/redirect"
            :tabindex="8"
            class="flex w-full items-center justify-center gap-3 rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-[#2C2C2C] shadow-xs transition-all hover:bg-stone-50 hover:border-stone-300 active:scale-[0.99] focus:outline-hidden focus:ring-2 focus:ring-[#853953]/20 cursor-pointer"
        >
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24">
                <path
                    fill="#4285F4"
                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                />
                <path
                    fill="#34A853"
                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                />
                <path
                    fill="#FBBC05"
                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"
                />
                <path
                    fill="#EA4335"
                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"
                />
            </svg>
            <span>Daftar dengan Google</span>
        </a>

        <div class="pt-3 text-center text-sm text-[#2C2C2C]/70">
            Sudah memiliki akun?
            <TextLink
                :href="login()"
                :tabindex="8"
                class="ml-1 font-semibold !no-underline"
                >Masuk Akun</TextLink
            >
        </div>
    </Form>
</template>
