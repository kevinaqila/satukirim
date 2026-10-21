<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Lupa Kata Sandi',
        description: 'Masukkan email tokomu untuk menerima tautan pemulihan kata sandi',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Lupa Kata Sandi" />

    <div
        v-if="status"
        class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-center text-sm font-medium text-emerald-700"
    >
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="email" class="text-sm font-medium text-[#2C2C2C]">Alamat Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    autofocus
                    placeholder="nama@toko.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    size="lg"
                    class="w-full font-medium"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    <span>Kirim Tautan Pemulihan</span>
                </Button>
            </div>
        </Form>

        <div class="pt-2 text-left text-sm text-[#2C2C2C]/70">
            <span>Ingat kata sandi Anda?</span>
            <TextLink :href="login()" class="ml-1 font-semibold !no-underline">Masuk ke Akun</TextLink>
        </div>
    </div>
</template>
