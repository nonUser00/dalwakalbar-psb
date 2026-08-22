<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import PsbLayout from '@/Layouts/PsbLayout.vue';

defineOptions({ layout: PsbLayout });

const props = defineProps<{
    pendaftar: any;
    hasilUjian?: any;
}>();

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
</script>

<template>
    <div class="w-full space-y-6">
        <Head title="Pengumuman Hasil Seleksi - PSB Dalwa Kalbar" />

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    Pengumuman Hasil Seleksi
                </h1>
                <p class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    Status kelulusan penerimaan santri baru Pondok Pesantren Darullughah Wadda'wah Kalbar.
                </p>
            </div>

            <!-- Tombol Cetak Kartu / Bukti Kelulusan -->
            <button
                type="button"
                @click="openCetakKartu"
                class="inline-flex cursor-pointer items-center gap-2 rounded-2xl bg-primary px-4 py-2.5 text-xs sm:text-sm font-extrabold text-white shadow-md shadow-primary/20 hover:bg-primary-dark transition-all self-start sm:self-auto"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak Kartu Registrasi</span>
            </button>
        </div>

        <!-- 2 Column Layout: Status Kelulusan (Left 8 cols) & Kartu Identitas Santri (Right 4 cols) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left Side: Result Card & Academic Evaluation (8 Cols) -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Result Hero Card -->
                <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <!-- Hero Banner -->
                    <div
                        class="p-6 sm:p-8 text-center space-y-4"
                        :class="[
                            props.pendaftar.status === 'LULUS'
                                ? 'bg-gradient-to-b from-emerald-50 via-emerald-50/30 to-white dark:from-emerald-950/40 dark:via-slate-900 dark:to-slate-900'
                                : props.pendaftar.status === 'TIDAK_LULUS' || props.pendaftar.status === 'DITOLAK'
                                  ? 'bg-gradient-to-b from-rose-50 via-rose-50/30 to-white dark:from-rose-950/40 dark:via-slate-900 dark:to-slate-900'
                                  : 'bg-gradient-to-b from-amber-50 via-amber-50/30 to-white dark:from-amber-950/40 dark:via-slate-900 dark:to-slate-900'
                        ]"
                    >
                        <div
                            class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl shadow-sm"
                            :class="[
                                props.pendaftar.status === 'LULUS'
                                    ? 'bg-emerald-500 text-white shadow-emerald-500/20'
                                    : props.pendaftar.status === 'TIDAK_LULUS' || props.pendaftar.status === 'DITOLAK'
                                      ? 'bg-rose-500 text-white shadow-rose-500/20'
                                      : 'bg-amber-500 text-white shadow-amber-500/20'
                            ]"
                        >
                            <svg v-if="props.pendaftar.status === 'LULUS'" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else-if="props.pendaftar.status === 'TIDAK_LULUS' || props.pendaftar.status === 'DITOLAK'" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                        <div class="space-y-1.5">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-black uppercase tracking-wider border"
                                :class="[
                                    props.pendaftar.status === 'LULUS'
                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300'
                                        : props.pendaftar.status === 'TIDAK_LULUS' || props.pendaftar.status === 'DITOLAK'
                                          ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/60 dark:text-rose-300'
                                          : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/60 dark:text-amber-300'
                                ]"
                            >
                                {{ props.pendaftar.status === 'LULUS' ? 'LULUS SELEKSI' : (props.pendaftar.status === 'TIDAK_LULUS' || props.pendaftar.status === 'DITOLAK' ? 'TIDAK LULUS' : 'MENUNGGU PENGUMUMAN') }}
                            </span>
                            <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                                {{
                                    props.pendaftar.status === 'LULUS'
                                        ? 'SELAMAT, ANDA DINYATAKAN LULUS!'
                                        : props.pendaftar.status === 'TIDAK_LULUS' || props.pendaftar.status === 'DITOLAK'
                                          ? 'MOHON MAAF, ANDA BELUM LULUS'
                                          : 'HASIL EVALUASI SELEKSI SEDANG BERLANGSUNG'
                                }}
                            </h2>
                            <p class="text-xs sm:text-sm text-slate-600 max-w-xl mx-auto dark:text-slate-400 leading-relaxed font-medium">
                                {{
                                    props.pendaftar.status === 'LULUS'
                                        ? 'Berdasarkan hasil rapat dewan penguji dan verifikasi berkas, calon santri dinyatakan memenuhi syarat penerimaan santri baru Ponpes Darullughah Wadda\'wah Kalbar.'
                                        : props.pendaftar.status === 'TIDAK_LULUS' || props.pendaftar.status === 'DITOLAK'
                                          ? 'Terima kasih atas partisipasi Anda dalam mengikuti proses seleksi penerimaan santri baru Ponpes Darullughah Wadda\'wah Kalbar.'
                                          : 'Proses penilaian dan penentuan hasil evaluasi tes interview masih berlangsung oleh tim panitia penguji PSB.'
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Rekomendasi Kelas Pondok & Aspek Penilaian (Jika Ada Hasil Ujian) -->
                    <div v-if="props.hasilUjian" class="p-6 sm:p-8 border-t border-gray-100 dark:border-slate-800 space-y-5">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-extrabold tracking-tight text-slate-900 dark:text-slate-100">
                                Rincian Hasil Evaluasi Ujian & Kelas
                            </h3>
                            <span v-if="props.hasilUjian.rekomendasi_kelas_pondok" class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700 dark:border-indigo-900/50 dark:bg-indigo-950/60 dark:text-indigo-300">
                                Rekomendasi: {{ props.hasilUjian.rekomendasi_kelas_pondok }}
                            </span>
                        </div>

                        <!-- Score Cards Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <div class="rounded-2xl border border-gray-100 bg-slate-50/70 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                <span class="text-[10px] font-bold uppercase text-slate-400">Wawancara</span>
                                <p class="text-sm font-black text-slate-900 dark:text-slate-100 mt-1 capitalize">
                                    {{ props.hasilUjian.hasil_wawancara || '-' }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-gray-100 bg-slate-50/70 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                <span class="text-[10px] font-bold uppercase text-slate-400">Baca Kitab</span>
                                <p class="text-sm font-black text-slate-900 dark:text-slate-100 mt-1">
                                    {{ props.hasilUjian.nilai_baca_kitab ? `${props.hasilUjian.nilai_baca_kitab} (${props.hasilUjian.predikat_baca_kitab || '-'})` : '-' }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-gray-100 bg-slate-50/70 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                <span class="text-[10px] font-bold uppercase text-slate-400">Tes Menulis</span>
                                <p class="text-sm font-black text-slate-900 dark:text-slate-100 mt-1">
                                    {{ props.hasilUjian.nilai_menulis ? `${props.hasilUjian.nilai_menulis} (${props.hasilUjian.predikat_menulis || '-'})` : '-' }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-gray-100 bg-slate-50/70 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                <span class="text-[10px] font-bold uppercase text-slate-400">Tes Hafalan</span>
                                <p class="text-sm font-black text-slate-900 dark:text-slate-100 mt-1">
                                    {{ props.hasilUjian.nilai_hafalan ? `${props.hasilUjian.nilai_hafalan} (${props.hasilUjian.predikat_hafalan || '-'})` : '-' }}
                                </p>
                            </div>
                        </div>

                        <div v-if="props.hasilUjian.catatan_penguji" class="rounded-2xl bg-slate-50 border border-gray-100 p-4 text-xs text-slate-600 dark:bg-slate-800/50 dark:border-slate-700/60 dark:text-slate-300 space-y-1">
                            <span class="text-[10.5px] font-bold uppercase text-slate-400">Catatan Dewan Penguji:</span>
                            <p class="font-medium leading-relaxed">{{ props.hasilUjian.catatan_penguji }}</p>
                        </div>
                    </div>

                    <!-- Next Steps Info for Lulus -->
                    <div v-if="props.pendaftar.status === 'LULUS'" class="p-6 sm:p-8 border-t border-gray-100 dark:border-slate-800">
                        <div class="rounded-3xl border border-emerald-200 bg-emerald-50/60 p-5 sm:p-6 dark:border-emerald-900/50 dark:bg-emerald-950/30 space-y-3">
                            <div class="flex items-center gap-2 text-emerald-800 dark:text-emerald-300">
                                <svg class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h4 class="text-xs font-black uppercase tracking-wider">
                                    Langkah Selanjutnya bagi Calon Santri:
                                </h4>
                            </div>
                            <ul class="list-disc list-inside text-xs sm:text-sm text-emerald-900 space-y-1.5 dark:text-emerald-200 font-medium leading-relaxed">
                                <li>Silakan lakukan pelunasan tagihan biaya pendidikan pada menu <strong>Keuangan & Tagihan</strong>.</li>
                                <li>Cetak Kartu Registrasi resmi dan tanda bukti kelulusan untuk dibawa saat kedatangan ke pondok.</li>
                                <li>Pilih jadwal dan rombongan keberangkatan santri menuju asrama pondok pesantren pada menu <strong>Keberangkatan</strong>.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Candidate Card Profile (4 Cols) -->
            <div class="lg:col-span-4 space-y-6">
                <div class="rounded-3xl border border-gray-200 bg-white p-6 sm:p-8 text-center shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-5">
                    <div class="mb-4 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-slate-800">
                        <h3 class="text-base font-extrabold tracking-tight text-gray-900 dark:text-slate-100">
                            Identitas Calon Santri
                        </h3>
                        <div class="flex h-7 w-7 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Profile Photo -->
                    <div class="mx-auto h-24 w-24 overflow-hidden rounded-full border-2 border-gray-100 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-800">
                        <img
                            v-if="getPendaftarPhoto(props.pendaftar) && !isPhotoError"
                            :src="getPendaftarPhoto(props.pendaftar)!"
                            @error="isPhotoError = true"
                            class="h-full w-full object-cover"
                            alt="Foto Profil"
                        />
                        <img
                            v-else
                            :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(props.pendaftar?.nama || 'CS')}&background=1e293b&color=fff&size=256`"
                            class="h-full w-full object-cover"
                            alt="Foto Profil"
                        />
                    </div>

                    <div>
                        <h4 class="text-base font-bold text-slate-900 dark:text-slate-100">
                            {{ props.pendaftar.nama }}
                        </h4>
                        <p class="font-mono text-xs font-bold text-primary dark:text-blue-400 mt-0.5">
                            {{ props.pendaftar.nomor_pendaftaran || '-' }}
                        </p>
                    </div>

                    <div class="border-t border-gray-100 pt-4 dark:border-slate-800 space-y-3 text-xs text-left">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Jenjang:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ props.pendaftar.jenjang?.name || '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Cabang:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ props.pendaftar.cabang?.name || 'Kalimantan Barat' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Gelombang:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">Gelombang {{ props.pendaftar.gelombang?.name || '1' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Periode:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ props.pendaftar.periode?.name || '-' }}</span>
                        </div>
                    </div>

                    <!-- Cetak Kartu Action Button -->
                    <div class="pt-2 border-t border-gray-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="openCetakKartu"
                            class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl bg-primary px-4 py-3 text-xs font-bold text-white shadow-md shadow-primary/20 hover:bg-primary-dark transition-all"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span>Cetak Kartu Registrasi</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
