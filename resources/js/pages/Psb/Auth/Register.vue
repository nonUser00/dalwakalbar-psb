<script setup lang="ts">
defineOptions({ layout: GuestLayout });
import { Head, Link, useForm } from '@inertiajs/vue3';
import BackButton from '@/Components/BackButton.vue';
import PasswordInput from '@/Components/Form/PasswordInput.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { login, register } from '@/routes/psb';

const props = defineProps<{
    waveInfo?: {
        is_open: boolean;
        tahun_akademik?: { id: string; name: string } | null;
        periode?: { id: string; name: string; start_date?: string; end_date?: string } | null;
        gelombang?: { id: string; name: string; start_date?: string; end_date?: string } | null;
        message?: string | null;
    };
}>();

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
                            class="mb-6 leading-relaxed font-medium text-neutral-warm"
                        >
                            Silakan isi data berikut untuk mendaftar sebagai
                            Santri Baru.
                        </p>

                        <!-- Active Academic Year & Wave Card -->
                        <div
                            v-if="props.waveInfo?.is_open"
                            class="mb-8 rounded-2xl border-2 border-primary/20 bg-gradient-to-br from-primary/5 via-primary/[0.02] to-transparent p-5 shadow-xs transition-all"
                        >
                            <!-- Top Row: Badge & Live Indicator -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="relative flex h-2 w-2">
                                        <span
                                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                                        ></span>
                                        <span
                                            class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"
                                        ></span>
                                    </span>
                                    <span
                                        class="text-[11px] font-extrabold tracking-wider text-primary uppercase"
                                    >
                                        T.A. {{ props.waveInfo.tahun_akademik?.name }}
                                    </span>
                                </div>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700"
                                >
                                    <svg
                                        class="h-3 w-3 text-emerald-600"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2.5"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                    Gelombang Buka
                                </span>
                            </div>

                            <!-- Middle Row: Wave Name & Icon -->
                            <div class="mt-3.5 flex items-center gap-3.5">
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary text-white shadow-md shadow-primary/20"
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
                                            stroke-width="1.8"
                                            d="M12 14l9-5-9-5-9 5 9 5z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M12 14v7"
                                        />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3
                                        class="truncate text-base font-black text-primary-dark sm:text-lg"
                                    >
                                        {{ props.waveInfo.gelombang?.name || props.waveInfo.periode?.name }}
                                    </h3>
                                    <p
                                        class="truncate text-xs font-semibold text-neutral-warm"
                                    >
                                        {{ props.waveInfo.periode?.name }}
                                    </p>
                                </div>
                            </div>

                            <!-- Bottom Row: Date Range -->
                            <div
                                v-if="props.waveInfo.gelombang?.start_date || props.waveInfo.periode?.start_date"
                                class="mt-4 flex items-center gap-1.5 border-t border-primary/10 pt-3 text-xs font-medium text-neutral-warm"
                            >
                                <svg
                                    class="h-3.5 w-3.5 text-primary/70 shrink-0"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>
                                <span>
                                    {{ props.waveInfo.gelombang?.start_date || props.waveInfo.periode?.start_date }}
                                    <template
                                        v-if="props.waveInfo.gelombang?.end_date || props.waveInfo.periode?.end_date"
                                    >
                                        s.d. {{ props.waveInfo.gelombang?.end_date || props.waveInfo.periode?.end_date }}
                                    </template>
                                </span>
                            </div>
                        </div>

                        <!-- Closed Registration Alert Card -->
                        <div
                            v-else-if="props.waveInfo && !props.waveInfo.is_open"
                            class="mb-8 rounded-2xl border-2 border-amber-500/20 bg-gradient-to-br from-amber-500/5 via-amber-500/[0.02] to-transparent p-5 shadow-xs"
                        >
                            <!-- Top Row: Badge & Alert Status -->
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-[11px] font-extrabold tracking-wider text-amber-900 uppercase"
                                >
                                    Informasi Pendaftaran
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-amber-300 bg-amber-100 px-2.5 py-0.5 text-[11px] font-bold text-amber-800"
                                >
                                    <svg
                                        class="h-3 w-3 text-amber-700"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    Pendaftaran Ditutup
                                </span>
                            </div>

                            <!-- Middle Row: Closed Info -->
                            <div class="mt-3.5 flex items-start gap-3.5">
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-600 text-white shadow-md shadow-amber-600/20"
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
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                        />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-sm font-bold text-amber-950">
                                        Pendaftaran Belum Dibuka
                                    </h3>
                                    <p class="mt-0.5 text-xs leading-relaxed font-medium text-amber-800">
                                        {{ props.waveInfo.message }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <TextInput
                                    label="NIK (Nomor Induk Kependudukan)"
                                    id="nik"
                                    type="text"
                                    class="block w-full"
                                    v-model="form.nik"
                                    :error="form.errors.nik"
                                    :disabled="props.waveInfo ? !props.waveInfo.is_open : false"
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
                                    :disabled="props.waveInfo ? !props.waveInfo.is_open : false"
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
                                    :disabled="props.waveInfo ? !props.waveInfo.is_open : false"
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
                                    :disabled="props.waveInfo ? !props.waveInfo.is_open : false"
                                    required
                                    placeholder="Ulangi password Anda"
                                />
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing || (props.waveInfo ? !props.waveInfo.is_open : false)"
                                class="mt-8 inline-flex w-full items-center justify-center rounded-full bg-primary px-8 py-4 text-[15px] font-bold text-white shadow-[0_8px_30px_rgb(39,59,94,0.2)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-primary-dark hover:shadow-[0_8px_30px_rgb(39,59,94,0.4)] disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <span v-if="form.processing">Mendaftar...</span>
                                <span v-else-if="props.waveInfo && !props.waveInfo.is_open">Pendaftaran Ditutup</span>
                                <span v-else>Daftar Sekarang</span>
                                <svg
                                    v-if="!form.processing && (!props.waveInfo || props.waveInfo.is_open)"
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
