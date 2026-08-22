<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { dashboard } from '@/routes/psb';

defineOptions({ layout: GuestLayout });

const props = defineProps<{
    pendaftar: {
        id: string;
        nomor_pendaftaran: string;
        nik: string;
        nama: string;
        status: string;
        status_label: string;
        tipe_pendaftaran: string;
        created_at: string;
    };
}>();

const copied = ref(false);

const copyNomorPendaftaran = async () => {
    try {
        await navigator.clipboard.writeText(props.pendaftar.nomor_pendaftaran);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2500);
    } catch (err) {
        console.error('Gagal menyalin:', err);
    }
};

const handlePrint = () => {
    window.print();
};
</script>

<template>
    <div class="h-full w-full">
        <Head title="Registrasi Berhasil - PSB Dalwa Kalbar" />

        <transition name="page" appear>
            <div
                class="custom-scrollbar relative z-10 flex h-full w-full flex-col overflow-y-auto"
            >
                <!-- Header / Logos & Back to Home -->
                <div class="flex-none p-6 sm:p-10 lg:p-12 xl:px-24 xl:py-10">
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

                        <Link
                            href="/"
                            class="inline-flex items-center gap-1.5 rounded-full border border-neutral-warm/20 bg-white px-4 py-2 text-xs font-bold text-neutral-warm transition-colors hover:border-primary hover:text-primary"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                                />
                            </svg>
                            <span>Beranda</span>
                        </Link>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div
                    class="flex flex-1 flex-col justify-center px-6 pb-16 sm:px-10 lg:px-16 xl:px-24"
                >
                    <div class="animate-fade-in-up mx-auto w-full max-w-lg">
                        <!-- Success Badge -->
                        <div class="mb-4">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3.5 py-1 text-xs font-bold text-emerald-700"
                            >
                                <svg
                                    class="h-4 w-4 text-emerald-600"
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
                                Registrasi Berhasil
                            </span>
                        </div>

                        <!-- Title -->
                        <h1
                            class="text-3xl font-extrabold tracking-tight text-primary-dark sm:text-4xl"
                        >
                            Akun Berhasil Dibuat!
                        </h1>
                        <p
                            class="mt-2 text-sm leading-relaxed text-neutral-warm sm:text-base"
                        >
                            Selamat! Akun pendaftaran santri baru Anda telah aktif. Simpan nomor pendaftaran ini untuk keperluan verifikasi dan login.
                        </p>

                        <!-- Nomor Pendaftaran Highlight Card -->
                        <div
                            class="mt-6 rounded-2xl border-2 border-primary/20 bg-gradient-to-br from-primary/5 via-primary/[0.02] to-transparent p-5 sm:p-6 shadow-sm"
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-bold tracking-wider text-primary uppercase"
                                    >Nomor Pendaftaran Resmi</span
                                >
                                <span
                                    class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-[11px] font-bold text-primary"
                                >
                                    {{ pendaftar.status_label || pendaftar.status }}
                                </span>
                            </div>

                            <div
                                class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div
                                    class="font-mono text-2xl font-black tracking-wide text-primary-dark sm:text-3xl"
                                >
                                    {{ pendaftar.nomor_pendaftaran }}
                                </div>

                                <button
                                    type="button"
                                    @click="copyNomorPendaftaran"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-primary/30 bg-white px-3.5 py-2 text-xs font-bold text-primary shadow-xs transition-all hover:bg-primary hover:text-white"
                                >
                                    <svg
                                        v-if="!copied"
                                        class="h-4 w-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        class="h-4 w-4 text-emerald-600"
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
                                    <span>{{ copied ? 'Tersalin!' : 'Salin Nomor' }}</span>
                                </button>
                            </div>

                            <p class="mt-3 text-xs text-neutral-warm/80">
                                Gunakan <strong>Nomor Pendaftaran</strong> atau <strong>NIK</strong> bersama password Anda untuk masuk kembali ke portal.
                            </p>
                        </div>

                        <!-- Data Calon Santri Summary -->
                        <div
                            class="mt-6 overflow-hidden rounded-2xl border border-neutral-warm/20 bg-surface/30 p-5"
                        >
                            <h2
                                class="text-xs font-bold tracking-wider text-neutral-warm uppercase"
                            >
                                Ringkasan Data Pendaftar
                            </h2>

                            <dl class="mt-4 grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2">
                                <div class="border-b border-neutral-warm/10 pb-2 sm:border-none sm:pb-0">
                                    <dt class="text-[11px] font-semibold text-neutral-warm/70">
                                        Nama Lengkap
                                    </dt>
                                    <dd class="mt-0.5 text-sm font-bold text-primary-dark">
                                        {{ pendaftar.nama }}
                                    </dd>
                                </div>

                                <div class="border-b border-neutral-warm/10 pb-2 sm:border-none sm:pb-0">
                                    <dt class="text-[11px] font-semibold text-neutral-warm/70">
                                        NIK (Kependudukan)
                                    </dt>
                                    <dd class="mt-0.5 text-sm font-bold text-primary-dark font-mono">
                                        {{ pendaftar.nik }}
                                    </dd>
                                </div>

                                <div class="border-b border-neutral-warm/10 pb-2 sm:border-none sm:pb-0">
                                    <dt class="text-[11px] font-semibold text-neutral-warm/70">
                                        Tipe Pendaftaran
                                    </dt>
                                    <dd class="mt-0.5 text-sm font-bold text-primary-dark">
                                        {{ pendaftar.tipe_pendaftaran }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-[11px] font-semibold text-neutral-warm/70">
                                        Waktu Registrasi
                                    </dt>
                                    <dd class="mt-0.5 text-sm font-bold text-primary-dark">
                                        {{ pendaftar.created_at }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Next Steps Info -->
                        <div
                            class="mt-6 rounded-2xl border border-blue-100 bg-blue-50/50 p-4 sm:p-5"
                        >
                            <h2
                                class="flex items-center gap-2 text-xs font-bold text-primary uppercase"
                            >
                                <svg
                                    class="h-4 w-4 text-primary"
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
                                Langkah Selanjutnya
                            </h2>
                            <ul class="mt-3 space-y-2 text-xs font-medium text-neutral-warm">
                                <li class="flex items-start gap-2">
                                    <span
                                        class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-white"
                                        >1</span
                                    >
                                    <span>Lengkapi data diri, riwayat pendidikan, dan biodata orang tua/wali santri.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span
                                        class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-white"
                                        >2</span
                                    >
                                    <span>Unggah dokumen persyaratan (KK, Akta Kelahiran, Pas Foto, Surat Sehat, dll).</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span
                                        class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-white"
                                        >3</span
                                    >
                                    <span>Lakukan submit berkas untuk diproses verifikasi administrasi & ujian seleksi.</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Actions -->
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <Link
                                :href="dashboard.url()"
                                class="inline-flex flex-1 items-center justify-center rounded-full bg-primary px-8 py-4 text-[15px] font-bold text-white shadow-[0_8px_30px_rgb(39,59,94,0.2)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-primary-dark hover:shadow-[0_8px_30px_rgb(39,59,94,0.4)]"
                            >
                                <span>Lengkapi Biodata Sekarang</span>
                                <svg
                                    class="ml-2.5 h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M14 5l7 7m0 0l-7 7m7-7H3"
                                    />
                                </svg>
                            </Link>

                            <button
                                type="button"
                                @click="handlePrint"
                                class="inline-flex items-center justify-center rounded-full border border-neutral-warm/30 bg-white px-6 py-4 text-[15px] font-bold text-primary-dark transition-all duration-300 hover:border-primary hover:bg-surface/20"
                            >
                                <svg
                                    class="mr-2 h-5 w-5 text-neutral-warm"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                    />
                                </svg>
                                <span>Cetak Bukti</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
@media print {
    body {
        background: white !important;
    }
}
</style>
