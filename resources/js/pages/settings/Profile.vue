<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profil Akun',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <div class="space-y-4 sm:space-y-6">
        <Head title="Profil Akun - SatuKirim" />

        <div class="rounded-xl bg-white border border-stone-200/80 p-4 sm:p-5 shadow-2xs space-y-4">
            <div class="space-y-0.5 pb-2 border-b border-stone-100">
                <h3 class="text-sm font-bold text-stone-900">
                    Informasi Profil
                </h3>
                <p class="text-xs text-stone-500">
                    Perbarui nama lengkap dan alamat email akun Anda.
                </p>
            </div>

            <Form
                v-bind="ProfileController.update.form()"
                class="space-y-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-1.5">
                    <Label for="name" class="text-xs sm:text-sm font-semibold text-stone-800">
                        Nama Lengkap
                    </Label>
                    <Input
                        id="name"
                        class="h-9 sm:h-10 text-xs sm:text-sm bg-white border-stone-200"
                        name="name"
                        :default-value="user.name"
                        required
                        autocomplete="name"
                        placeholder="Nama lengkap Anda"
                    />
                    <InputError class="mt-1" :message="errors.name" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="email" class="text-xs sm:text-sm font-semibold text-stone-800">
                        Alamat Email
                    </Label>
                    <Input
                        id="email"
                        type="email"
                        class="h-9 sm:h-10 text-xs sm:text-sm bg-white border-stone-200"
                        name="email"
                        :default-value="user.email"
                        required
                        autocomplete="username"
                        placeholder="nama@contoh.com"
                    />
                    <InputError class="mt-1" :message="errors.email" />
                </div>

                <div
                    v-if="page.props.mustVerifyEmail && !user.email_verified_at"
                    class="p-3.5 bg-amber-50 border border-amber-200 rounded-lg text-xs sm:text-sm text-amber-800 space-y-1.5"
                >
                    <p>
                        Alamat email Anda belum diverifikasi.
                        <Link
                            :href="send()"
                            as="button"
                            class="underline font-semibold hover:text-amber-900 ml-1 cursor-pointer"
                        >
                            Klik di sini untuk mengirim ulang email verifikasi.
                        </Link>
                    </p>

                    <div
                        v-if="page.props.status === 'verification-link-sent'"
                        class="text-xs font-semibold text-emerald-700"
                    >
                        Tautan verifikasi baru telah berhasil dikirimkan ke alamat email Anda.
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-1">
                    <Button
                        type="submit"
                        :disabled="processing"
                        class="bg-[#853953] hover:bg-[#6c2e43] text-white font-semibold text-xs sm:text-sm h-9 sm:h-10 px-5 rounded-md shadow-xs cursor-pointer"
                        data-test="update-profile-button"
                    >
                        Simpan Perubahan
                    </Button>
                </div>
            </Form>
        </div>

        <div class="rounded-xl bg-white border border-stone-200/80 p-4 sm:p-5 shadow-2xs">
            <DeleteUser />
        </div>
    </div>
</template>
