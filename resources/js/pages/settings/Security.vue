<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/security';

type Props = {
    passwordRules?: string;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Keamanan & Kata Sandi',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <div class="space-y-6 sm:space-y-8">
        <Head title="Keamanan & Kata Sandi - SatuKirim" />

        <Form
            v-bind="SecurityController.update.form()"
            :options="{
                preserveScroll: true,
            }"
            reset-on-success
            :reset-on-error="[
                'password',
                'password_confirmation',
                'current_password',
            ]"
            class="space-y-5"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-1.5">
                <Label for="current_password" class="text-xs sm:text-sm font-semibold text-stone-800">
                    Kata Sandi Saat Ini
                </Label>
                <PasswordInput
                    id="current_password"
                    name="current_password"
                    class="h-9 sm:h-10 text-xs sm:text-sm bg-white border-stone-200"
                    autocomplete="current-password"
                    placeholder="Kata sandi saat ini"
                />
                <InputError :message="errors.current_password" />
            </div>

            <div class="grid gap-1.5">
                <Label for="password" class="text-xs sm:text-sm font-semibold text-stone-800">
                    Kata Sandi Baru
                </Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="h-9 sm:h-10 text-xs sm:text-sm bg-white border-stone-200"
                    autocomplete="new-password"
                    placeholder="Kata sandi baru"
                    :passwordrules="props.passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-1.5">
                <Label for="password_confirmation" class="text-xs sm:text-sm font-semibold text-stone-800">
                    Konfirmasi Kata Sandi Baru
                </Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    class="h-9 sm:h-10 text-xs sm:text-sm bg-white border-stone-200"
                    autocomplete="new-password"
                    placeholder="Konfirmasi kata sandi baru"
                    :passwordrules="props.passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="flex items-center gap-4 pt-1">
                <Button
                    type="submit"
                    :disabled="processing"
                    class="bg-[#853953] hover:bg-[#6c2e43] text-white font-semibold text-xs sm:text-sm h-9 sm:h-10 px-5 rounded-md shadow-xs cursor-pointer"
                    data-test="update-password-button"
                >
                    Perbarui Kata Sandi
                </Button>
            </div>
        </Form>
    </div>
</template>
