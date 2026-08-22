<script setup lang="ts">
defineOptions({ layout: GuestLayout });
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import BackButton from '@/Components/BackButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PasswordInput from '@/Components/Form/PasswordInput.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { login, register } from '@/routes/psb';

import { formatHariKerja } from '@/lib/utils';

const page = usePage();
const appSettings = computed(() => (page.props as any).app_settings || {});
const kontakWa = computed(() => appSettings.value.kontak_darurat_wa || '081234567890');
const namaContact = computed(() => appSettings.value.nama_contact || "Panitia PSB Dalwa Kalbar");

const hariKerjaText = computed(() => {
    const rawHari = appSettings.value.hari_kerja;
    let hariArr: string[] = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    if (rawHari) {
        try {
            const parsed = JSON.parse(rawHari);
            if (Array.isArray(parsed) && parsed.length > 0) hariArr = parsed;
        } catch {
            if (typeof rawHari === 'string') hariArr = rawHari.split(',').map((s: string) => s.trim());
        }
    }
    return formatHariKerja(hariArr);
});

const jamKerjaText = computed(() => {
    const jamMulai = appSettings.value.jam_kerja_mulai || '08:00';
    const jamSelesai = appSettings.value.jam_kerja_selesai || '17:00';
    return `${jamMulai} - ${jamSelesai} WIB`;
});

const isHelpModalOpen = ref(false);

const form = useForm({
    identifier: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(login.url(), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="h-full w-full">
        <Head title="Login Pendaftar" />

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

                        <BackButton href="/">Kembali</BackButton>
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
                            Portal Pendaftar
                        </div>

                        <h2
                            class="mb-2 text-3xl leading-tight font-extrabold tracking-tight text-primary-dark sm:text-4xl"
                        >
                            Selamat Datang
                        </h2>
                        <p
                            class="mb-10 leading-relaxed font-medium text-neutral-warm"
                        >
                            Silakan masuk menggunakan NIK atau Nomor Pendaftaran
                            Anda.
                        </p>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <TextInput
                                    label="NIK / No. Pendaftaran"
                                    id="identifier"
                                    type="text"
                                    class="block w-full"
                                    v-model="form.identifier"
                                    :error="form.errors.identifier"
                                    required
                                    autofocus
                                    placeholder="Masukkan NIK atau No. Pendaftaran"
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
                                    placeholder="Masukkan password Anda"
                                />
                            </div>

                            <div class="flex items-center justify-between">
                                <label
                                    for="remember"
                                    class="group flex cursor-pointer items-center select-none"
                                >
                                    <Checkbox
                                        id="remember"
                                        name="remember"
                                        v-model:checked="form.remember"
                                    />
                                    <span
                                        class="ml-2.5 text-sm font-medium text-gray-600 transition-colors group-hover:text-primary"
                                        >Ingat Saya</span
                                    >
                                </label>

                                <button
                                    type="button"
                                    @click="isHelpModalOpen = true"
                                    class="cursor-pointer text-sm font-bold text-primary transition-colors hover:text-primary-dark"
                                >
                                    Lupa Password?
                                </button>
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="mt-4 inline-flex w-full items-center justify-center rounded-full bg-primary px-8 py-4 text-[15px] font-bold text-white shadow-[0_8px_30px_rgb(39,59,94,0.2)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-primary-dark hover:shadow-[0_8px_30px_rgb(39,59,94,0.4)] disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                <span v-if="form.processing">Memproses...</span>
                                <span v-else>Masuk ke Portal</span>
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
                                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"
                                    />
                                </svg>
                            </button>
                        </form>

                        <div class="mt-10 text-center">
                            <p
                                class="text-sm font-medium text-gray-500"
                            >
                                Belum memiliki akun?
                            </p>
                            <Link
                                :href="register.url()"
                                class="mt-2 inline-block text-sm font-bold text-primary transition-colors hover:text-primary-dark"
                            >
                                Mendaftar sebagai Santri Baru &rarr;
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Modal Bantuan Lupa Password Pendaftar -->
        <Modal
            :show="isHelpModalOpen"
            maxWidth="md"
            @close="isHelpModalOpen = false"
        >
            <div class="p-6">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400"
                    >
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    <div>
                        <h3
                            class="text-lg font-extrabold tracking-tight text-slate-900 dark:text-slate-100"
                        >
                            Lupa Password Pendaftar
                        </h3>
                        <p
                            class="text-xs font-medium text-slate-500 dark:text-slate-400"
                        >
                            Bantuan reset password calon santri
                        </p>
                    </div>
                </div>

                <div
                    class="mt-5 rounded-2xl border border-blue-200/80 bg-blue-50/60 p-4 text-xs leading-relaxed text-blue-950 dark:border-blue-900/50 dark:bg-blue-950/30 dark:text-blue-200"
                >
                    <p class="font-bold">
                        Untuk keamanan data calon santri, reset password dilakukan melalui Panitia PSB.
                    </p>
                    <p class="mt-1.5 text-slate-600 dark:text-slate-300">
                        Silakan hubungi kontak panitia dengan menyertakan <strong>Nama Lengkap</strong> dan <strong>NIK / No. Pendaftaran</strong> Anda.
                    </p>
                </div>

                <div
                    class="mt-4 space-y-2 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 text-xs dark:border-slate-800 dark:bg-slate-800/60"
                >
                    <div class="grid grid-cols-[120px_12px_1fr] items-baseline">
                        <span class="font-bold text-slate-500 dark:text-slate-400">Panitia / Layanan</span>
                        <span class="text-slate-400 dark:text-slate-500">:</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ namaContact }}</span>
                    </div>
                    <div class="grid grid-cols-[120px_12px_1fr] items-baseline">
                        <span class="font-bold text-slate-500 dark:text-slate-400">WhatsApp Resmi</span>
                        <span class="text-slate-400 dark:text-slate-500">:</span>
                        <span class="font-mono font-black text-primary dark:text-blue-400">{{ kontakWa }}</span>
                    </div>
                    <div class="grid grid-cols-[120px_12px_1fr] items-baseline">
                        <span class="font-bold text-slate-500 dark:text-slate-400">Hari Kerja</span>
                        <span class="text-slate-400 dark:text-slate-500">:</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ hariKerjaText }}</span>
                    </div>
                    <div class="grid grid-cols-[120px_12px_1fr] items-baseline">
                        <span class="font-bold text-slate-500 dark:text-slate-400">Jam Kerja</span>
                        <span class="text-slate-400 dark:text-slate-500">:</span>
                        <span class="font-semibold text-slate-700 dark:text-slate-300">{{ jamKerjaText }}</span>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <SecondaryButton
                        type="button"
                        @click="isHelpModalOpen = false"
                        class="w-full sm:w-auto justify-center"
                    >
                        Tutup
                    </SecondaryButton>
                    <a
                        v-if="kontkWaClean = kontakWa.replace(/[^0-9]/g, '')"
                        :href="`https://wa.me/${kontkWaClean.startsWith('0') ? '62' + kontkWaClean.slice(1) : kontkWaClean}?text=Assalamu%27alaikum%20Panitia%20PSB,%20saya%20membutuhkan%20bantuan%20reset%20password%20akun%20calon%20santri.`"
                        target="_blank"
                        class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition-all hover:bg-emerald-700 focus:outline-none"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M12.031 21.464l-3.328 1.096 1.112-3.232a9.92 9.92 0 112.216 2.136z"
                                stroke="currentColor"
                                stroke-width="2"
                            />
                        </svg>
                        <span>Hubungi via WhatsApp</span>
                    </a>
                </div>
            </div>
        </Modal>
    </div>
</template>
