<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import PasskeyVerify from '@/components/PasskeyVerify.vue';

defineOptions({
    layout: {
        title: 'Konfirmasi Kata Sandi',
        description:
            'Ini adalah area aman aplikasi. Harap masukkan kata sandi Anda untuk melanjutkan.',
    },
});
</script>

<template>
    <Head title="Konfirmasi Kata Sandi" />

    <PasskeyVerify
        :routes="{
            options: confirmOptions(),
            submit: confirmStore(),
        }"
        label="Konfirmasi dengan passkey"
        loading-label="Memproses konfirmasi..."
        separator="Atau konfirmasi dengan kata sandi"
    />

    <Form
        v-bind="store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
    >
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label for="password" class="text-sm font-medium text-[#2C2C2C]">Kata Sandi</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    autofocus
                    placeholder="Masukkan kata sandi"
                />

                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center">
                <Button
                    size="lg"
                    class="w-full font-medium"
                    :disabled="processing"
                    data-test="confirm-password-button"
                >
                    <Spinner v-if="processing" />
                    <span>Konfirmasi Kata Sandi</span>
                </Button>
            </div>
        </div>
    </Form>
</template>
