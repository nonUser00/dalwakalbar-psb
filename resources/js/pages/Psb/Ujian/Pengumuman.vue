<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PsbLayout from '@/Layouts/PsbLayout.vue';
import { formatHariKerja } from '@/lib/utils';

defineOptions({ layout: PsbLayout });

interface KontakData {
    wa: string;
    nama: string;
    hari_kerja?: string[];
    jam_mulai?: string;
    jam_selesai?: string;
}

const props = defineProps<{
    pendaftar: any;
    kelompokUjians?: any[];
    hasilUjian?: any;
    kontak?: KontakData;
}>();

// Menentukan status pendaftaran & seleksi
const hasSchedule = computed(() => {
    return Array.isArray(props.kelompokUjians) && props.kelompokUjians.length > 0;
});

const isLulus = computed(() => {
    return props.pendaftar?.status === 'LULUS' || props.pendaftar?.status === 'KEDATANGAN' || props.pendaftar?.status === 'AKTIF';
});

const isTidakLulus = computed(() => {
    return props.pendaftar?.status === 'TIDAK_LULUS' || props.pendaftar?.status === 'DITOLAK';
});

const isEvaluasi = computed(() => {
    return !isLulus.value && !isTidakLulus.value && (hasSchedule.value || !!props.hasilUjian);
});

const isBelumJadwal = computed(() => {
    return !isLulus.value && !isTidakLulus.value && !hasSchedule.value && !props.hasilUjian;
});

const formatDate = (dateString?: string) => {
    if (!dateString) return '-';
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return dateString;
    return d.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatWaktu = (mulai?: string, selesai?: string) => {
    if (!mulai) return '08:00 - Selesai WIB';
    const cleanMulai = mulai.substring(0, 5);
    const cleanSelesai = selesai ? selesai.substring(0, 5) : 'Selesai';
    return `${cleanMulai} - ${cleanSelesai} WIB`;
};

const isPhotoError = ref(false);

const getPendaftarPhoto = (pendaftar: any): string | null => {
    if (!pendaftar) return null;
    const raw =
        pendaftar.foto_url ||
        pendaftar.foto ||
        pendaftar.personal_data?.foto_url ||
        pendaftar.personal_data?.foto ||
        pendaftar.personal_data?.pas_foto ||
        pendaftar.dokumens?.find(
            (d: any) =>
                d.dokumen?.is_profile_photo ||
                d.dokumen?.name?.toLowerCase().includes('foto') ||
                d.file_path?.toLowerCase().includes('foto') ||
                d.file_path?.toLowerCase().includes('pas_foto'),
        )?.file_path ||
        null;

    if (!raw) return null;
    if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('data:image') || raw.startsWith('/storage/') || raw.startsWith('/')) {
        return raw;
    }
    if (raw.startsWith('storage/')) {
        return `/${raw}`;
    }
    return `/storage/${raw.replace(/^\/+/, '')}`;
};

const openCetakKartu = () => {
    window.open('/psb/cetak-kartu', '_blank');
};

const hariKerjaText = computed(() => {
    return formatHariKerja(props.kontak?.hari_kerja);
});

const jamKerjaText = computed(() => {
    const jamMulai = props.kontak?.jam_mulai || '08:00';
    const jamSelesai = props.kontak?.jam_selesai || '17:00';
    return `${jamMulai} - ${jamSelesai} WIB`;
});

const cleanWaNumber = computed(() => {
    const raw = props.kontak?.wa || '081234567890';
    const cleaned = raw.replace(/[^0-9]/g, '');
    return cleaned.startsWith('0') ? '62' + cleaned.slice(1) : cleaned;
});
</script>

<template>
    <div class="w-full space-y-6">
        <Head title="Pengumuman Hasil Seleksi - PSB Dalwa Kalbar" />

        <!-- 1. Header Page Title & Action Button -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200/80 pb-5 dark:border-slate-800">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-2.5 py-0.5 text-[10.5px] font-bold uppercase tracking-wider text-primary dark:bg-blue-950/60 dark:text-blue-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-primary dark:bg-blue-400"></span>
                        Seleksi Penerimaan Santri Baru
                    </span>
                </div>
                <h1 class="mt-1.5 text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    Pengumuman Hasil Seleksi
                </h1>
                <p class="mt-0.5 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    Status hasil evaluasi dan kelulusan penerimaan santri baru Pondok Pesantren Darullughah Wadda'wah Kalbar.
                </p>
            </div>

            <div class="flex items-center gap-2.5 self-start sm:self-auto">
                <Link
                    href="/psb/ujian/jadwal"
                    class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                >
                    <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Jadwal Interview</span>
                </Link>

                <button
                    type="button"
                    @click="openCetakKartu"
                    class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-white shadow-2xs hover:bg-primary-dark transition-all dark:bg-blue-600 dark:hover:bg-blue-700"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Cetak Kartu Registrasi</span>
                </button>
            </div>
        </div>

        <!-- 2. Main 2 Column Grid (8 Cols Left, 4 Cols Right) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left Side (8 Cols): Status Kelulusan Hero Card, Rincian Nilai, Petunjuk Lanjutan -->
            <div class="lg:col-span-8 space-y-6">
                <!-- A. Main Status Card -->
                <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xs dark:border-slate-800 dark:bg-slate-900">
                    <!-- Status Banner Header -->
                    <div
                        class="p-6 sm:p-8 text-center space-y-4"
                        :class="[
                            isLulus
                                ? 'bg-gradient-to-b from-emerald-50 via-emerald-50/20 to-white dark:from-emerald-950/30 dark:via-slate-900 dark:to-slate-900'
                                : isTidakLulus
                                  ? 'bg-gradient-to-b from-rose-50 via-rose-50/20 to-white dark:from-rose-950/30 dark:via-slate-900 dark:to-slate-900'
                                  : isBelumJadwal
                                    ? 'bg-gradient-to-b from-slate-50 via-slate-50/30 to-white dark:from-slate-800/30 dark:via-slate-900 dark:to-slate-900'
                                    : 'bg-gradient-to-b from-amber-50 via-amber-50/20 to-white dark:from-amber-950/30 dark:via-slate-900 dark:to-slate-900'
                        ]"
                    >
                        <!-- Icon Circle -->
                        <div
                            class="mx-auto flex h-16 w-16 sm:h-20 sm:w-20 items-center justify-center rounded-2xl sm:rounded-3xl shadow-2xs transition-all"
                            :class="[
                                isLulus
                                    ? 'bg-emerald-500 text-white shadow-emerald-500/20'
                                    : isTidakLulus
                                      ? 'bg-rose-500 text-white shadow-rose-500/20'
                                      : isBelumJadwal
                                        ? 'bg-primary/10 text-primary dark:bg-blue-950/60 dark:text-blue-400'
                                        : 'bg-amber-500 text-white shadow-amber-500/20'
                            ]"
                        >
                            <svg v-if="isLulus" class="h-8 w-8 sm:h-10 sm:w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else-if="isTidakLulus" class="h-8 w-8 sm:h-10 sm:w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else-if="isBelumJadwal" class="h-8 w-8 sm:h-10 sm:w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <svg v-else class="h-8 w-8 sm:h-10 sm:w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                        <!-- Status Heading & Description -->
                        <div class="space-y-2">
                            <span
                                class="inline-flex items-center rounded-full px-3 py-0.5 text-[11px] font-black uppercase tracking-wider border"
                                :class="[
                                    isLulus
                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300'
                                        : isTidakLulus
                                          ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/60 dark:text-rose-300'
                                          : isBelumJadwal
                                            ? 'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300'
                                            : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/60 dark:text-amber-300'
                                ]"
                            >
                                {{
                                    isLulus
                                        ? 'LULUS SELEKSI'
                                        : isTidakLulus
                                          ? 'TIDAK LULUS'
                                          : isBelumJadwal
                                            ? 'JADWAL BELUM DITETAPKAN'
                                            : 'MENUNGGU PENGUMUMAN'
                                }}
                            </span>

                            <h2 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                                {{
                                    isLulus
                                        ? 'SELAMAT, ANDA DINYATAKAN LULUS!'
                                        : isTidakLulus
                                          ? 'MOHON MAAF, ANDA BELUM LULUS'
                                          : isBelumJadwal
                                            ? 'JADWAL INTERVIEW BELUM DITETAPKAN'
                                            : 'HASIL EVALUASI SELEKSI SEDANG BERLANGSUNG'
                                }}
                            </h2>

                            <p class="text-xs sm:text-sm text-slate-600 max-w-lg mx-auto dark:text-slate-400 leading-relaxed font-medium">
                                {{
                                    isLulus
                                        ? 'Berdasarkan hasil evaluasi dewan penguji dan verifikasi berkas, calon santri dinyatakan memenuhi syarat penerimaan santri baru Ponpes Darullughah Wadda\'wah Kalbar.'
                                        : isTidakLulus
                                          ? 'Terima kasih atas partisipasi Anda dalam mengikuti rangkaian seleksi penerimaan santri baru Ponpes Darullughah Wadda\'wah Kalbar.'
                                          : isBelumJadwal
                                            ? 'Sesi ujian interview Anda saat ini belum dialokasikan oleh panitia PSB. Silakan periksa kembali berkala atau pantau menu Jadwal Interview.'
                                            : 'Proses penilaian dan penentuan hasil evaluasi tes interview masih berlangsung oleh tim panitia dewan penguji PSB.'
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- B. Detail Nilai Ujian (Jika Ada) -->
                    <div v-if="props.hasilUjian" class="p-5 sm:p-6 border-t border-slate-100 dark:border-slate-800 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-primary dark:bg-blue-400"></span>
                                <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                    Rincian Hasil Evaluasi Ujian & Kelas
                                </h3>
                            </div>
                            <span v-if="props.hasilUjian.rekomendasi_kelas_pondok" class="inline-flex items-center gap-1 rounded-md border border-primary/20 bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary dark:border-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                                Rekomendasi: {{ props.hasilUjian.rekomendasi_kelas_pondok }}
                            </span>
                        </div>

                        <!-- Score Cards Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Wawancara</span>
                                <p class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 mt-1 capitalize">
                                    {{ props.hasilUjian.hasil_wawancara || '-' }}
                                </p>
                            </div>
                            <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Baca Kitab</span>
                                <p class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 mt-1">
                                    {{ props.hasilUjian.nilai_baca_kitab ? `${props.hasilUjian.nilai_baca_kitab} (${props.hasilUjian.predikat_baca_kitab || '-'})` : '-' }}
                                </p>
                            </div>
                            <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tes Menulis</span>
                                <p class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 mt-1">
                                    {{ props.hasilUjian.nilai_menulis ? `${props.hasilUjian.nilai_menulis} (${props.hasilUjian.predikat_menulis || '-'})` : '-' }}
                                </p>
                            </div>
                            <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tes Hafalan</span>
                                <p class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 mt-1">
                                    {{ props.hasilUjian.nilai_hafalan ? `${props.hasilUjian.nilai_hafalan} (${props.hasilUjian.predikat_hafalan || '-'})` : '-' }}
                                </p>
                            </div>
                        </div>

                        <div v-if="props.hasilUjian.catatan_final || props.hasilUjian.catatan_penguji" class="rounded-xl bg-slate-50/80 border border-slate-200/80 p-4 text-xs text-slate-600 dark:bg-slate-800/50 dark:border-slate-700/60 dark:text-slate-300 space-y-1">
                            <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400">Catatan Dewan Penguji:</span>
                            <p class="font-medium leading-relaxed">{{ props.hasilUjian.catatan_final || props.hasilUjian.catatan_penguji }}</p>
                        </div>
                    </div>

                    <!-- C. Ringkasan Sesi Terjadwal (Jika sedang dalam evaluasi atau terjadwal) -->
                    <div v-if="hasSchedule && !isLulus && !isTidakLulus" class="p-5 sm:p-6 border-t border-slate-100 dark:border-slate-800 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                    Alokasi Sesi Interview Terdaftar
                                </h3>
                            </div>
                            <Link
                                href="/psb/ujian/jadwal"
                                class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline dark:text-blue-400"
                            >
                                <span>Lihat Jadwal Lengkap</span>
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>

                        <div
                            v-for="k in props.kelompokUjians"
                            :key="k.id"
                            class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-800/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
                        >
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-slate-100">{{ k.nama_kelompok || 'Kelompok Interview' }}</h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ formatDate(k.tanggal_ujian) }} • {{ formatWaktu(k.waktu_mulai, k.waktu_selesai) }}
                                </p>
                            </div>
                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider self-start sm:self-auto border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/60 dark:text-blue-300">
                                {{ k.status || 'SCHEDULED' }}
                            </span>
                        </div>
                    </div>

                    <!-- D. Next Steps Box for Lulus -->
                    <div v-if="isLulus" class="p-5 sm:p-6 border-t border-slate-100 dark:border-slate-800">
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-5 dark:border-emerald-900/50 dark:bg-emerald-950/20 space-y-3">
                            <div class="flex items-center gap-2 text-emerald-800 dark:text-emerald-300">
                                <svg class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h4 class="text-xs font-black uppercase tracking-wider">
                                    Langkah Selanjutnya bagi Calon Santri:
                                </h4>
                            </div>
                            <ul class="list-disc list-inside text-xs text-emerald-900 space-y-1.5 dark:text-emerald-200 font-medium leading-relaxed">
                                <li>Silakan lakukan pelunasan tagihan biaya pendidikan pada menu <Link href="/psb/keuangan/tagihan" class="underline font-bold">Keuangan & Tagihan</Link>.</li>
                                <li>Cetak Kartu Registrasi resmi dan tanda bukti kelulusan untuk dibawa saat kedatangan ke pondok.</li>
                                <li>Pilih jadwal dan rombongan keberangkatan santri menuju asrama pondok pesantren pada menu <strong>Keberangkatan</strong>.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- B. Important Notice / Information Card -->
                <div class="rounded-2xl border border-primary/20 bg-primary/[0.03] p-5 shadow-2xs dark:border-blue-900/40 dark:bg-blue-950/20 space-y-3">
                    <div class="flex items-start gap-3.5">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary text-white shadow-2xs dark:bg-blue-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="space-y-1 flex-1">
                            <h4 class="text-xs font-black uppercase tracking-wider text-primary dark:text-blue-300">
                                Catatan Panitia Penerimaan Santri Baru
                            </h4>
                            <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed font-medium">
                                Seluruh keputusan hasil kelulusan bersifat resmi dan final berdasarkan hasil evaluasi terpadu dewan penguji Pondok Pesantren Darullughah Wadda'wah Kalbar.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side (4 Cols): Candidate Card Profile & Helpdesk WhatsApp -->
            <div class="lg:col-span-4 space-y-6">
                <!-- 1. Candidate ID Card -->
                <div class="rounded-2xl border border-slate-200/90 bg-white p-5 text-center shadow-2xs dark:border-slate-800 dark:bg-slate-900 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">
                            Kartu Calon Santri
                        </h3>
                        <span class="rounded-md bg-primary/10 text-primary dark:bg-blue-950/50 dark:text-blue-300 px-2 py-0.5 text-[10px] font-bold">
                            {{ props.pendaftar.status || 'TERVERIFIKASI' }}
                        </span>
                    </div>

                    <!-- Profile Photo -->
                    <div class="mx-auto h-20 w-20 overflow-hidden rounded-full border-2 border-slate-100 shadow-2xs dark:border-slate-800 bg-slate-100 dark:bg-slate-800">
                        <img
                            v-if="getPendaftarPhoto(props.pendaftar) && !isPhotoError"
                            :src="getPendaftarPhoto(props.pendaftar)!"
                            @error="isPhotoError = true"
                            class="h-full w-full object-cover"
                            alt="Foto Profil"
                        />
                        <img
                            v-else
                            :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(props.pendaftar?.nama || 'CS')}&background=273b5e&color=fff&size=256`"
                            class="h-full w-full object-cover"
                            alt="Foto Profil"
                        />
                    </div>

                    <div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                            {{ props.pendaftar.nama }}
                        </h4>
                        <p class="font-mono text-xs font-bold text-primary dark:text-blue-400 mt-0.5">
                            {{ props.pendaftar.nomor_pendaftaran || '-' }}
                        </p>
                    </div>

                    <div class="border-t border-slate-100 pt-3 dark:border-slate-800 space-y-2 text-xs text-left">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400">Jenjang:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ props.pendaftar.jenjang?.name || '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400">Cabang:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ props.pendaftar.cabang?.name || 'Kalimantan Barat' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400">Gelombang:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">Gelombang {{ props.pendaftar.gelombang?.name || '1' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400">Periode:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ props.pendaftar.periode?.name || '-' }}</span>
                        </div>
                    </div>

                    <!-- Single Main Cetak Kartu Button -->
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="openCetakKartu"
                            class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-white shadow-2xs hover:bg-primary-dark transition-all dark:bg-blue-600 dark:hover:bg-blue-700"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span>Cetak Kartu Registrasi</span>
                        </button>
                    </div>
                </div>

                <!-- 2. Help Card (Pusat Bantuan WhatsApp Sejajar Sesuai Pola Jadwal & Dashboard) -->
                <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900 space-y-3.5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-2xs">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                Bantuan & Informasi
                            </h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                Panitia PSB Dalwa Kalbar
                            </p>
                        </div>
                    </div>

                    <!-- Contact Details Table -->
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-3.5 text-xs space-y-2 dark:border-slate-800 dark:bg-slate-800/50">
                        <div class="grid grid-cols-[75px_12px_1fr] items-baseline">
                            <span class="font-medium text-slate-500 dark:text-slate-400">Layanan</span>
                            <span class="text-slate-400 dark:text-slate-500">:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200 truncate">{{ props.kontak?.nama || "PP Dalwa Kalbar" }}</span>
                        </div>
                        <div class="grid grid-cols-[75px_12px_1fr] items-baseline">
                            <span class="font-medium text-slate-500 dark:text-slate-400">WhatsApp</span>
                            <span class="text-slate-400 dark:text-slate-500">:</span>
                            <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ props.kontak?.wa || '081234567890' }}</span>
                        </div>
                        <div class="grid grid-cols-[75px_12px_1fr] items-baseline">
                            <span class="font-medium text-slate-500 dark:text-slate-400">Hari Kerja</span>
                            <span class="text-slate-400 dark:text-slate-500">:</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ hariKerjaText }}</span>
                        </div>
                        <div class="grid grid-cols-[75px_12px_1fr] items-baseline">
                            <span class="font-medium text-slate-500 dark:text-slate-400">Jam Kerja</span>
                            <span class="text-slate-400 dark:text-slate-500">:</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300">{{ jamKerjaText }}</span>
                        </div>
                    </div>

                    <a
                        :href="`https://wa.me/${cleanWaNumber}?text=Assalamu%27alaikum%20Panitia%20PSB%20Dalwa%20Kalbar,%20saya%20${encodeURIComponent(props.pendaftar?.nama || '')}%20(No.%20Reg:%20${encodeURIComponent(props.pendaftar?.nomor_pendaftaran || '-')})%20ingin%20menanyakan%20perihal%20hasil%20seleksi.`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-2xs transition-all hover:bg-emerald-700"
                    >
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.031 21.464l-3.328 1.096 1.112-3.232a9.92 9.92 0 112.216 2.136z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                            <path d="M9.197 10.155c-.214.214-.265.513-.122.775 1.186 2.164 2.87 3.848 5.034 5.034.262.143.561.092.775-.122l.53-.53c.27-.27.674-.325 1.002-.136l2.176 1.25c.348.2.493.633.326.992a3 3 0 01-2.91 1.758c-4.468-.344-8.5-2.613-11.458-5.571-2.958-2.958-5.227-6.99-5.571-11.458a3 3 0 011.758-2.91c.36-.167.793-.022.993.326l1.25 2.176c.189.328.134.732-.136 1.002l-.53.53z" fill="currentColor"/>
                        </svg>
                        <span>Hubungi WhatsApp Panitia</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
