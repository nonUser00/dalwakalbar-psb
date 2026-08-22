<script setup lang="ts">
defineOptions({ layout: GuestLayout });
import { Head, Link, useForm } from '@inertiajs/vue3';
import BackButton from '@/Components/BackButton.vue';
import PasswordInput from '@/Components/Form/PasswordInput.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { login, register } from '@/routes/psb';

const form = useForm({
    nik: '',
    nama: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(register.url(), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="h-full w-full">
        <Head title="Pendaftaran Santri Baru" />

        <transition name="page" appear>
            <div
                class="custom-scrollbar relative z-10 flex h-full w-full flex-col overflow-y-auto"
            >
                <!-- Header / Logos & Back Button -->
                <div class="flex-none p-6 sm:p-10 lg:p-12 xl:px-24 xl:py-12">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3 sm:space-x-4">
                            <Link
                                href="/"
                                class="flex items-center space-x-3 transition-opacity hover:opacity-80"
                            >
                                <img
                                    src="/image/logos/logo-1.png"
                                    alt="Logo"
                                    class="h-10 w-auto sm:h-12"
                                />
                                <div
                                    class="hidden h-8 w-px bg-neutral-warm/30 sm:block"
                                ></div>
                                <img
                                    src="/image/logos/logo-2.png"
                                    alt="Logo 2"
                                    class="hidden h-10 w-auto sm:block sm:h-12"
                                />
                            </Link>
                            <div class="flex flex-col">
                                <span
                                    class="text-[13px] leading-tight font-extrabold text-primary-dark sm:text-[15px]"
                                    >PSB Dalwa</span
                                >
                                <span
                                    class="text-[10px] leading-tight font-bold text-neutral-warm sm:text-[11px]"
                                    >Perwakilan Kalbar</span
                                >
                            </div>
                        </div>

                        <BackButton :href="login.url()">Kembali</BackButton>
                    </div>
                </div>

                <!-- Main Form Area -->
                <div
                    class="flex flex-1 flex-col justify-center px-6 pb-16 sm:px-10 lg:px-16 xl:px-24"
                >
                    <div class="animate-fade-in-up mx-auto w-full max-w-md">
                        <div
                            class="mb-8 inline-flex items-center rounded-full border border-primary/10 bg-primary/5 px-4 py-1.5 text-[11px] font-bold tracking-widest text-primary uppercase sm:text-xs"
                        >
                            Daftar Akun
                        </div>

                        <h2
                            class="mb-2 text-3xl leading-tight font-extrabold tracking-tight text-primary-dark sm:text-4xl"
                        >
                            Buat Akun PSB
                        </h2>
                        <p
                            class="mb-10 leading-relaxed font-medium text-neutral-warm"
                        >
                            Silakan isi data berikut untuk mendaftar sebagai
                            Santri Baru.
                        </p>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <TextInput
                                    label="NIK (Nomor Induk Kependudukan)"
                                    id="nik"
                                    type="text"
                                    class="block w-full"
                                    v-model="form.nik"
                                    :error="form.errors.nik"
                                    required
                                    autofocus
                                    placeholder="Masukkan NIK valid"
                                />
                            </div>

                            <div>
                                <TextInput
                                    label="Nama Lengkap"
                                    id="nama"
                                    type="text"
                                    class="block w-full"
                                    v-model="form.nama"
                                    :error="form.errors.nama"
                                    required
                                    placeholder="Nama sesuai dokumen resmi"
                                />
                            </div>

                            <div>
                                <PasswordInput
                                    label="Password"
                                    id="password"
                                    class="block w-full"
                                    v-model="form.password"
                                    :error="form.errors.password"
                                    required
                                    placeholder="Buat password Anda"
                                />
                            </div>

                            <div>
                                <PasswordInput
                                    label="Konfirmasi Password"
                                    id="password_confirmation"
                                    class="block w-full"
                                    v-model="form.password_confirmation"
                                    :error="form.errors.password_confirmation"
                                    required
                                    placeholder="Ulangi password Anda"
                                />
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="mt-8 inline-flex w-full items-center justify-center rounded-full bg-primary px-8 py-4 text-[15px] font-bold text-white shadow-[0_8px_30px_rgb(39,59,94,0.2)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-primary-dark hover:shadow-[0_8px_30px_rgb(39,59,94,0.4)] disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                <span v-if="form.processing">Mendaftar...</span>
                                <span v-else>Daftar Sekarang</span>
                                <svg
                                    v-if="!form.processing"
                                    class="ml-2.5 h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 8l4 4m0 0l-4 4m4-4H3"
                                    />
                                </svg>
                            </button>
                        </form>

                        <div class="mt-10 text-center">
                            <p
                                class="text-sm font-medium text-gray-500"
                            >
                                Sudah memiliki akun?
                            </p>
                            <Link
                                :href="login.url()"
                                class="mt-2 inline-block text-sm font-bold text-primary transition-colors hover:text-primary-dark"
                            >
                                Masuk ke Portal &rarr;
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
