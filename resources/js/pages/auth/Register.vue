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

        <div class="pt-3 text-left text-sm text-[#2C2C2C]/70">
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
