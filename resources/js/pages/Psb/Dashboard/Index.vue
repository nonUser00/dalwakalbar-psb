<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PsbLayout from '@/Layouts/PsbLayout.vue';
import { formatHariKerja } from '@/lib/utils';
import { getPendaftarStatusBadge } from '@/types/enums';

defineOptions({ layout: PsbLayout });

const page = usePage();

interface PendaftarData {
    id: string;
    nomor_pendaftaran: string;
    nik: string;
    nama: string;
    email?: string;
    nomor_hp?: string;
    status: string;
    status_label: string;
    status_badge: string;
    tipe_pendaftaran: string;
    foto_url?: string;
    jenjang?: string;
    periode?: string;
    gelombang?: string;
    cabang?: string;
    submitted_at?: string;
    created_at?: string;
    personal_data?: any;
    parent_data?: any;
    address_data?: any;
    education_data?: any;
}

interface SummaryData {
    is_biodata_complete: boolean;
    has_personal_data: boolean;
    has_parent_data: boolean;
    has_address_data: boolean;
    uploaded_docs_count: number;
    total_required_docs: number;
    total_tagihan: number;
    total_paid: number;
    has_unpaid_tagihan: boolean;
    progress_percentage: number;
}

interface KontakData {
    wa: string;
    nama: string;
    jam_kerja?: string;
    hari_kerja?: string[];
    jam_mulai?: string;
    jam_selesai?: string;
}

const props = defineProps<{
    pendaftar: PendaftarData;
    summary: SummaryData;
    kontak: KontakData;
}>();

const badges = computed(() => (page.props as any).sidebar_badges || {});

const hariKerjaText = computed(() => {
    return formatHariKerja(props.kontak.hari_kerja);
});

const jamKerjaText = computed(() => {
    const jamMulai = props.kontak.jam_mulai || '08:00';
    const jamSelesai = props.kontak.jam_selesai || '17:00';
    return `${jamMulai} - ${jamSelesai} WIB`;
});

const formatRupiah = (amount?: number | string) => {
    const num = parseFloat(String(amount || 0));
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
};

const openCetakKartu = () => {
    window.open('/psb/cetak-kartu', '_blank');
};
</script>

<template>
    <div class="w-full space-y-6">
        <Head title="Dashboard Santri - PSB Dalwa Kalbar" />

        <!-- Alert Pemberitahuan Jika Ada Catatan Revisi dari Panitia -->
        <div
            v-if="pendaftar.status === 'DRAFT' && (pendaftar.personal_data?.catatan_revisi || pendaftar.personal_data?.catatan_personal || pendaftar.parent_data?.catatan_parent || pendaftar.address_data?.catatan_address || pendaftar.education_data?.catatan_education)"
            class="flex items-start gap-4 rounded-3xl border border-rose-200 bg-rose-50 p-5 shadow-xs dark:border-rose-900/50 dark:bg-rose-950/40"
        >
            <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-rose-100 dark:bg-rose-900/50 text-rose-600 dark:text-rose-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="flex-1 space-y-1.5">
                <div class="flex items-center gap-2">
                    <h4 class="text-sm font-bold text-rose-900 dark:text-rose-200">
                        Perhatian: Formulir / Dokumen Perlu Perbaikan
                    </h4>
                    <span class="inline-flex items-center rounded-full bg-rose-600 text-white px-2 py-0.2 text-[10px] font-black uppercase">
                        Revisi Diperlukan
                    </span>
                </div>
                <p v-if="pendaftar.personal_data?.catatan_revisi" class="text-xs sm:text-sm text-rose-800 dark:text-rose-300 leading-relaxed font-medium">
                    {{ pendaftar.personal_data.catatan_revisi }}
                </p>
                <div class="mt-2 flex flex-wrap items-center gap-2 pt-1">
                    <Link
                        href="/psb/biodata"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-rose-700 transition-colors"
                    >
                        <span>Perbaiki Formulir & Dokumen</span>
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>
            </div>
        </div>

        <!-- 1. Top Welcome Card (Clean Modern Dashboard Header) -->
        <div class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <!-- Background Decorative Accent -->
            <div class="pointer-events-none absolute -top-24 -right-24 h-72 w-72 rounded-full bg-primary/5 blur-3xl dark:bg-blue-500/10"></div>

            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 relative z-10">
                <!-- Left: Profile Identity -->
                <div class="flex items-start sm:items-center gap-4 sm:gap-5">
                    <div class="relative h-18 w-18 sm:h-20 sm:w-20 shrink-0 overflow-hidden rounded-2xl border-2 border-slate-100 bg-slate-100 shadow-sm dark:border-slate-800 dark:bg-slate-800">
                        <img
                            v-if="pendaftar.foto_url"
                            :src="pendaftar.foto_url"
                            class="h-full w-full object-cover"
                            alt="Foto Profil"
                        />
                        <img
                            v-else
                            :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(pendaftar.nama)}&background=273b5e&color=fff&size=256`"
                            class="h-full w-full object-cover"
                            alt="Foto Profil"
                        />
                    </div>

                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-bold text-slate-400 dark:text-slate-500">Selamat Datang,</span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider"
                                :class="getPendaftarStatusBadge(pendaftar.status).classes"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                {{ getPendaftarStatusBadge(pendaftar.status).label }}
                            </span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                            {{ pendaftar.nama }}
                        </h1>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                            <span>No. Reg: <strong class="font-mono font-bold text-slate-700 dark:text-slate-200">{{ pendaftar.nomor_pendaftaran || '-' }}</strong></span>
                            <span>•</span>
                            <span>NIK: <strong class="font-mono font-bold text-slate-700 dark:text-slate-200">{{ pendaftar.nik || '-' }}</strong></span>
                            <template v-if="pendaftar.jenjang">
                                <span>•</span>
                                <span class="font-bold text-primary dark:text-blue-400">{{ pendaftar.jenjang }}</span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Right: Action & Progress Summary -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 lg:border-l lg:border-slate-100 lg:pl-8 dark:border-slate-800">
                    <div class="flex flex-col gap-1.5 rounded-2xl border border-slate-100 bg-slate-50 p-4 sm:min-w-64 dark:border-slate-800 dark:bg-slate-800/50">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-700 dark:text-slate-300">
                            <span>Progres Kelengkapan</span>
                            <span class="font-mono text-primary dark:text-blue-400">{{ summary.progress_percentage }}%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                            <div
                                class="h-full rounded-full bg-primary transition-all duration-500 dark:bg-blue-500"
                                :style="{ width: `${summary.progress_percentage}%` }"
                            ></div>
                        </div>
                        <span class="text-[10px] font-medium text-slate-500 dark:text-slate-400">
                            {{ summary.progress_percentage >= 100 ? 'Semua tahapan telah lengkap' : 'Lengkapi biodata dan unggah dokumen' }}
                        </span>
                    </div>

                    <button
                        type="button"
                        @click="openCetakKartu"
                        class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl bg-primary px-5 py-3.5 text-xs font-bold text-white shadow-sm transition-all hover:bg-primary-dark hover:shadow-md"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Cetak Kartu</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Overview Metric Cards (4 Stats) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Biodata -->
            <div class="flex flex-col justify-between rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase">Biodata Santri</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-base font-black text-slate-900 dark:text-slate-100">
                        {{ summary.is_biodata_complete ? 'Biodata Lengkap' : 'Belum Lengkap' }}
                    </div>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        {{ summary.is_biodata_complete ? 'Data santri & orang tua tersimpan' : 'Harap melengkapi isian formulir' }}
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <span
                        class="text-[11px] font-bold"
                        :class="summary.is_biodata_complete ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'"
                    >
                        {{ summary.is_biodata_complete ? 'Tersimpan' : 'Perlu Diisi' }}
                    </span>
                    <Link href="/psb/biodata" class="text-[11px] font-bold text-primary hover:underline dark:text-blue-400">
                        Kelola &rarr;
                    </Link>
                </div>
            </div>

            <!-- Card 2: Dokumen -->
            <div class="flex flex-col justify-between rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase">Dokumen Berkas</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-base font-black text-slate-900 dark:text-slate-100">
                        {{ summary.uploaded_docs_count }} / {{ summary.total_required_docs || '-' }} Berkas
                    </div>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        KK, Akta Kelahiran, Pas Foto
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400">
                        {{ summary.uploaded_docs_count >= (summary.total_required_docs || 1) ? 'Semua Terunggah' : 'Sebagian Diunggah' }}
                    </span>
                    <Link href="/psb/biodata?step=5" class="text-[11px] font-bold text-primary hover:underline dark:text-blue-400">
                        Upload &rarr;
                    </Link>
                </div>
            </div>

            <!-- Card 3: Tagihan -->
            <div class="flex flex-col justify-between rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase">Tagihan Biaya</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-base font-black text-slate-900 dark:text-slate-100">
                        {{ summary.total_tagihan > 0 ? formatRupiah(summary.total_tagihan) : 'Rp 0' }}
                    </div>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        {{ summary.has_unpaid_tagihan ? 'Menunggu Pelunasan' : summary.total_tagihan > 0 ? 'Lunas / Diterima' : 'Belum Ada Tagihan' }}
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <span
                        class="text-[11px] font-bold"
                        :class="summary.has_unpaid_tagihan ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'"
                    >
                        {{ summary.has_unpaid_tagihan ? 'Ada Tagihan' : 'Lunas' }}
                    </span>
                    <Link href="/psb/keuangan" class="text-[11px] font-bold text-primary hover:underline dark:text-blue-400">
                        Rincian &rarr;
                    </Link>
                </div>
            </div>

            <!-- Card 4: Seleksi & Ujian -->
            <div class="flex flex-col justify-between rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase">Ujian & Seleksi</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-base font-black text-slate-900 dark:text-slate-100">
                        {{ pendaftar.status === 'LULUS' ? 'Lulus Seleksi' : 'Tes Wawancara' }}
                    </div>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        Informasi hasil tes & seleksi
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400">
                        {{ pendaftar.gelombang || 'Gelombang 1' }}
                    </span>
                    <Link href="/psb/ujian" class="text-[11px] font-bold text-primary hover:underline dark:text-blue-400">
                        Cek &rarr;
                    </Link>
                </div>
            </div>
        </div>

        <!-- 3. Quick Actions & Support Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- Left 2 Cols: Action Navigation -->
            <div class="lg:col-span-2 space-y-4">
                <h2 class="text-base font-black tracking-tight text-slate-900 dark:text-slate-100">
                    Menu & Layanan Utama
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Nav 1: Formulir & Dokumen -->
                    <Link
                        href="/psb/biodata"
                        class="group relative flex flex-col justify-between rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs transition-all hover:border-primary/40 hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                    >
                        <span
                            v-if="badges.psb_formulir"
                            class="absolute top-4 right-4 inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-[#ff2d55] px-1.5 text-[10px] font-black leading-none text-white shadow-xs"
                        >
                            {{ badges.psb_formulir }}
                        </span>
                        <div class="space-y-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400 transition-colors group-hover:bg-primary group-hover:text-white">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-slate-900 group-hover:text-primary dark:text-slate-100 dark:group-hover:text-blue-400 transition-colors">
                                    Formulir & Dokumen Pendaftaran
                                </h3>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Lengkapi identitas, data orang tua, alamat, riwayat sekolah, dan upload berkas dokumen digital.
                                </p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-1 text-xs font-bold text-primary group-hover:translate-x-1 transition-transform dark:text-blue-400">
                            <span>Isi Formulir</span>
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </Link>

                    <!-- Nav 3: Keuangan -->
                    <Link
                        href="/psb/keuangan"
                        class="group relative flex flex-col justify-between rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs transition-all hover:border-primary/40 hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                    >
                        <span
                            v-if="badges.psb_tagihan"
                            class="absolute top-4 right-4 inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-[#ff2d55] px-1.5 text-[10px] font-black leading-none text-white shadow-xs"
                        >
                            {{ badges.psb_tagihan }}
                        </span>
                        <div class="space-y-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 transition-colors group-hover:bg-primary group-hover:text-white">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-slate-900 group-hover:text-primary dark:text-slate-100 dark:group-hover:text-blue-400 transition-colors">
                                    Tagihan & Pembayaran
                                </h3>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Cek tagihan pendaftaran, nomor Virtual Account Bank, dan bukti pembayaran.
                                </p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-1 text-xs font-bold text-primary group-hover:translate-x-1 transition-transform dark:text-blue-400">
                            <span>Rincian Tagihan</span>
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </Link>

                    <!-- Nav 4: Ujian -->
                    <Link
                        href="/psb/ujian"
                        class="group relative flex flex-col justify-between rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs transition-all hover:border-primary/40 hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                    >
                        <span
                            v-if="badges.psb_jadwal"
                            class="absolute top-4 right-4 inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-[#ff2d55] px-1.5 text-[10px] font-black leading-none text-white shadow-xs"
                        >
                            {{ badges.psb_jadwal }}
                        </span>
                        <div class="space-y-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400 transition-colors group-hover:bg-primary group-hover:text-white">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-slate-900 group-hover:text-primary dark:text-slate-100 dark:group-hover:text-blue-400 transition-colors">
                                    Jadwal Interview & Kelulusan
                                </h3>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Informasi jadwal sesi interview masuk dan pengumuman hasil kelulusan.
                                </p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-1 text-xs font-bold text-primary group-hover:translate-x-1 transition-transform dark:text-blue-400">
                            <span>Lihat Jadwal</span>
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Right 1 Col: Help Card (Pusat Bantuan WhatsApp dengan Titik Dua Sejajar) -->
            <div class="space-y-4">
                <h2 class="text-base font-black tracking-tight text-slate-900 dark:text-slate-100">
                    Bantuan & Informasi
                </h2>

                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-4">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-xs">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">
                                Pusat Bantuan PSB
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Layanan Resmi Calon Santri
                            </p>
                        </div>
                    </div>

                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Mengalami kendala saat pengisian formulir atau pembayaran? Hubungi tim panitia PSB melalui WhatsApp resmi.
                    </p>

                    <!-- Contact Details Table with perfectly aligned colons -->
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 text-xs space-y-2.5 dark:border-slate-800 dark:bg-slate-800/60">
                        <div class="grid grid-cols-[85px_12px_1fr] items-baseline">
                            <span class="font-bold text-slate-500 dark:text-slate-400">Layanan</span>
                            <span class="text-slate-400 dark:text-slate-500">:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ kontak.nama }}</span>
                        </div>
                        <div class="grid grid-cols-[85px_12px_1fr] items-baseline">
                            <span class="font-bold text-slate-500 dark:text-slate-400">WhatsApp</span>
                            <span class="text-slate-400 dark:text-slate-500">:</span>
                            <span class="font-mono font-black text-emerald-600 dark:text-emerald-400">{{ kontak.wa }}</span>
                        </div>
                        <div class="grid grid-cols-[85px_12px_1fr] items-baseline">
                            <span class="font-bold text-slate-500 dark:text-slate-400">Hari Kerja</span>
                            <span class="text-slate-400 dark:text-slate-500">:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ hariKerjaText }}</span>
                        </div>
                        <div class="grid grid-cols-[85px_12px_1fr] items-baseline">
                            <span class="font-bold text-slate-500 dark:text-slate-400">Jam Kerja</span>
                            <span class="text-slate-400 dark:text-slate-500">:</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300">{{ jamKerjaText }}</span>
                        </div>
                    </div>

                    <a
                        v-if="cleanWa = kontak.wa.replace(/[^0-9]/g, '')"
                        :href="`https://wa.me/${cleanWa.startsWith('0') ? '62' + cleanWa.slice(1) : cleanWa}?text=Assalamu%27alaikum%20Panitia%20PSB%20Dalwa%20Kalbar,%20saya%20${encodeURIComponent(pendaftar.nama)}%20(No.%20Reg:%20${encodeURIComponent(pendaftar.nomor_pendaftaran || '-')})%20membutuhkan%20informasi.`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-xs font-bold text-white shadow-xs transition-all hover:bg-emerald-700 hover:shadow-md"
                    >
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.031 21.464l-3.328 1.096 1.112-3.232a9.92 9.92 0 112.216 2.136z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                            <path d="M9.197 10.155c-.214.214-.265.513-.122.775 1.186 2.164 2.87 3.848 5.034 5.034.262.143.561.092.775-.122l.53-.53c.27-.27.674-.325 1.002-.136l2.176 1.25c.348.2.493.633.326.992a3 3 0 01-2.91 1.758c-4.468-.344-8.5-2.613-11.458-5.571-2.958-2.958-5.227-6.99-5.571-11.458a3 3 0 011.758-2.91c.36-.167.793-.022.993.326l1.25 2.176c.189.328.134.732-.136 1.002l-.53.53z" fill="currentColor"/>
                        </svg>
                        <span>Chat WhatsApp Panitia</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
