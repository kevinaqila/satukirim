<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-1">
        <div class="space-y-0.5">
            <h4 class="text-xs sm:text-sm font-semibold text-stone-900">
                Hapus Akun
            </h4>
            <p class="text-xs text-stone-500 leading-relaxed">
                Hapus akun dan seluruh data pengiriman secara permanen.
            </p>
        </div>

        <Dialog>
            <DialogTrigger as-child>
                <Button
                    type="button"
                    class="text-xs font-semibold text-white bg-red-600 hover:bg-red-700 h-8.5 px-3.5 rounded-lg transition-colors shrink-0 cursor-pointer self-start sm:self-auto shadow-xs border-0"
                    data-test="delete-user-button"
                >
                    Hapus Akun
                </Button>
            </DialogTrigger>
                    <DialogContent class="sm:max-w-md">
                        <Form
                            v-bind="ProfileController.destroy.form()"
                            reset-on-success
                            @error="() => passwordInput?.focus()"
                            :options="{
                                preserveScroll: true,
                            }"
                            class="space-y-5"
                            v-slot="{ errors, processing, reset, clearErrors }"
                        >
                            <DialogHeader class="space-y-2">
                                <DialogTitle class="text-base sm:text-lg font-bold text-stone-900">
                                    Apakah Anda yakin ingin menghapus akun?
                                </DialogTitle>
                                <DialogDescription class="text-xs sm:text-sm text-stone-500 leading-relaxed">
                                    Setelah akun Anda dihapus, semua data toko, pengiriman, dan riwayat transaksi akan dihapus secara permanen. Masukkan kata sandi akun Anda untuk mengonfirmasi penghapusan.
                                </DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-1.5">
                                <Label for="delete-password" class="text-xs sm:text-sm font-semibold text-stone-800">
                                    Kata Sandi Konfirmasi
                                </Label>
                                <PasswordInput
                                    id="delete-password"
                                    name="password"
                                    ref="passwordInput"
                                    placeholder="Masukkan kata sandi Anda"
                                    class="h-9 sm:h-10 text-xs sm:text-sm bg-white border-stone-200"
                                />
                                <InputError :message="errors.password" />
                            </div>

                            <DialogFooter class="flex items-center justify-end gap-2 pt-2">
                                <DialogClose as-child>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="() => { clearErrors(); reset(); }"
                                        class="h-9 text-xs sm:text-sm font-semibold bg-white border-stone-200 text-stone-700 hover:bg-stone-50 cursor-pointer shadow-2xs"
                                    >
                                        Batal
                                    </Button>
                                </DialogClose>

                                <Button
                                    type="submit"
                                    variant="destructive"
                                    :disabled="processing"
                                    class="h-9 text-xs sm:text-sm font-semibold bg-red-600 hover:bg-red-700 text-white cursor-pointer shadow-xs"
                                    data-test="confirm-delete-user-button"
                                >
                                    Ya, Hapus Akun Saya
                                </Button>
                            </DialogFooter>
                        </Form>
                    </DialogContent>
                </Dialog>
    </div>
</template>
