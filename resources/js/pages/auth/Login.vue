<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Masuk ke Akun',
        description: 'Kelola pengiriman toko dan pantau transaksi paket Anda',
        align: 'center',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const page = usePage();
const googleAuthError = computed(() => {
    const pageErrors = page.props.errors as Record<string, string> | undefined;
    return pageErrors?.google;
});
</script>

<template>
    <Head title="Masuk ke Akun" />

    <div
        v-if="status"
        class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-center text-sm font-medium text-emerald-700"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-3.5"
    >
        <div class="grid gap-3.5">
            <div class="grid gap-1.5">
                <Label for="email" class="text-sm font-medium text-[#2C2C2C]">Alamat Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="nama@toko.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-1.5">
                <div class="flex items-center justify-between">
                    <Label for="password" class="text-sm font-medium text-[#2C2C2C]">Kata Sandi</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-xs font-medium !no-underline"
                        :tabindex="5"
                    >
                        Lupa kata sandi?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Masukkan kata sandi"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between pt-0.5">
                <Label for="remember" class="flex items-center space-x-2 cursor-pointer text-sm text-[#2C2C2C]/80 font-normal">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span>Ingat saya di perangkat ini</span>
                </Label>
            </div>

            <Button
                type="submit"
                size="lg"
                class="mt-1 w-full font-medium"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                <span>Masuk Sekarang</span>
            </Button>
        </div>

        <div class="relative flex items-center justify-center my-0.5">
            <div class="w-full border-t border-stone-200"></div>
            <span class="absolute bg-white px-3 text-xs uppercase tracking-wider text-stone-400 font-medium">atau</span>
        </div>

        <a
            href="/auth/google/redirect"
            :tabindex="5"
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
            <span>Masuk dengan Google</span>
        </a>
        <InputError :message="googleAuthError" class="text-center" />

        <div class="pt-2 text-center text-sm text-[#2C2C2C]/70">
            Belum punya akun?
            <TextLink :href="register()" :tabindex="6" class="ml-1 font-semibold !no-underline">Daftar Akun</TextLink>
        </div>
    </Form>
</template>
