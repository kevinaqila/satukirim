<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'Verifikasi Alamat Email',
        description:
            'Harap verifikasi email Anda dengan mengeklik tautan yang baru saja kami kirimkan ke email Anda.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Verifikasi Alamat Email" />

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-center text-sm font-medium text-emerald-700"
    >
        Tautan verifikasi baru telah dikirimkan ke alamat email yang Anda daftarkan.
    </div>

    <Form
        v-bind="send.form()"
        class="space-y-6 text-center"
        v-slot="{ processing }"
    >
        <Button :disabled="processing" size="lg" class="w-full font-medium">
            <Spinner v-if="processing" />
            <span>Kirim Ulang Email Verifikasi</span>
        </Button>

        <TextLink :href="logout()" as="button" class="mx-auto block text-sm font-semibold">
            Keluar dari Akun
        </TextLink>
    </Form>
</template>
