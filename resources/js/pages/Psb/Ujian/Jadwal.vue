<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
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

interface AspekPenilaianData {
    id: string;
    nama_aspek: string;
    bobot: number;
    indikator?: string;
    urutan?: number;
}

interface KategoriPenilaianData {
    id: string;
    nama_kategori: string;
    keterangan?: string;
    aspek_penilaians?: AspekPenilaianData[];
}

const props = defineProps<{
    pendaftar: any;
    kelompokUjians: any[];
    kategoriPenilaians?: KategoriPenilaianData[];
    kontak?: KontakData;
}>();

const activeAccordionKategori = ref<string | null>(null);

onMounted(() => {
    if (props.kategoriPenilaians && props.kategoriPenilaians.length > 0) {
        activeAccordionKategori.value = props.kategoriPenilaians[0].id;
    }
});

const toggleAccordion = (id: string) => {
    activeAccordionKategori.value = activeAccordionKategori.value === id ? null : id;
};

const formatDate = (dateString?: string) => {
    if (!dateString) return '-';
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return dateString;
    return d.toLocaleDateString('id-ID', {
        weekday: 'long',
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

const primaryKelompok = computed(() => {
    if (!props.kelompokUjians || props.kelompokUjians.length === 0) return null;
    return props.kelompokUjians[0];
});

// Perhitungan Countdown Hari Menuju Ujian
const countdownInfo = computed(() => {
    if (!primaryKelompok.value || !primaryKelompok.value.tanggal_ujian) return null;

    const examDateStr = primaryKelompok.value.tanggal_ujian;
    const examDate = new Date(examDateStr);
    if (isNaN(examDate.getTime())) return null;

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const targetDate = new Date(examDate);
    targetDate.setHours(0, 0, 0, 0);

    const diffTime = targetDate.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0) {
        return {
            badge: 'Hari Ini',
            badgeClass: 'bg-emerald-600 text-white animate-pulse',
            title: 'Pelaksanaan Ujian Hari Ini',
            desc: 'Pastikan Anda telah hadir di lokasi ujian 15 menit sebelum sesi dimulai.',
        };
    } else if (diffDays === 1) {
        return {
            badge: 'Besok',
            badgeClass: 'bg-amber-600 text-white',
            title: 'Ujian Dilaksanakan Besok',
            desc: 'Persiapkan fisik, kartu ujian, serta perlengkapan menulis yang dibutuhkan.',
        };
    } else if (diffDays > 1) {
        return {
            badge: `${diffDays} Hari Lagi`,
            badgeClass: 'bg-primary text-white dark:bg-blue-600',
            title: `Sesi Ujian dalam ${diffDays} Hari`,
            desc: `Pelaksanaan pada ${formatDate(examDateStr)}. Persiapkan hafalan & materi dengan maksimal.`,
        };
    } else {
        return {
            badge: 'Selesai',
            badgeClass: 'bg-slate-500 text-white',
            title: 'Sesi Ujian Telah Berlangsung',
            desc: 'Hasil kelulusan dapat dipantau secara berkala melalui menu Pengumuman.',
        };
    }
});

const getPetugasList = (k: any) => {
    const pengujis = (k.pengujis || []) as any[];
    const koordinator = k.koordinator;

    const findByRole = (role: string) => {
        return pengujis.find((p: any) => p.pivot?.peran === role);
    };

    const pewawancara = findByRole('interview');
    const membaca = findByRole('tes_membaca');
    const menulis = findByRole('tes_menulis');
    const hafalan = findByRole('tes_hafalan');

    const fallbackPengujis = pengujis.filter((p: any) => !p.pivot?.peran);

    return [
        {
            key: 'interview',
            roleTitle: 'Pewawancara',
            roleSub: 'Interview & Kepribadian Santri/Wali',
            officerName: pewawancara?.name || (fallbackPengujis[0] ? fallbackPengujis[0].name : null),
            accentClass: 'border-l-primary text-primary',
            badgeClass: 'bg-primary/10 text-primary border-primary/20 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800',
        },
        {
            key: 'tes_membaca',
            roleTitle: 'Penguji Membaca',
            roleSub: "Al-Qur'an, Tajwid & Baca Kitab",
            officerName: membaca?.name || (fallbackPengujis[1] ? fallbackPengujis[1].name : null),
            accentClass: 'border-l-emerald-600 text-emerald-600',
            badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800',
        },
        {
            key: 'tes_menulis',
            roleTitle: 'Penguji Menulis',
            roleSub: "Imla' Dikte & Khath Arab",
            officerName: menulis?.name || (fallbackPengujis[2] ? fallbackPengujis[2].name : null),
            accentClass: 'border-l-indigo-600 text-indigo-600',
            badgeClass: 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800',
        },
        {
            key: 'tes_hafalan',
            roleTitle: 'Penguji Hafalan',
            roleSub: "Juz 'Amma, Sholat & Doa Harian",
            officerName: hafalan?.name || (fallbackPengujis[3] ? fallbackPengujis[3].name : null),
            accentClass: 'border-l-purple-600 text-purple-600',
            badgeClass: 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800',
        },
        {
            key: 'koordinator',
            roleTitle: 'Koordinator PSB',
            roleSub: 'Penanggung Jawab Sesi Ujian',
            officerName: koordinator && koordinator.length > 0 ? koordinator[0].name : null,
            accentClass: 'border-l-amber-600 text-amber-600',
            badgeClass: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800',
        },
    ];
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

const tataTertib = [
    {
        title: 'Kehadiran Tepat Waktu',
        desc: 'Hadir di lokasi 15-30 menit sebelum ujian dimulai untuk registrasi fisik.',
        icon: 'clock',
    },
    {
        title: 'Kartu Ujian & Identitas',
        desc: 'Wajib membawa Kartu Peserta Ujian tercetak & dokumen KK / KTP / Pelajar.',
        icon: 'card',
    },
    {
        title: 'Busana Syar\'i & Rapi',
        desc: 'Putra: Koko/Kemeja putih, sarung/celana kain & peci. Putri: Busana muslimah & jilbab.',
        icon: 'cloth',
    },
    {
        title: 'Alat Tulis Pribadi',
        desc: 'Membawa pulpen hitam/biru & pensil pribadi untuk tes tertulis imla\'.',
        icon: 'pencil',
    },
];

const defaultMateriList = [
    {
        nama: 'Wawancara & Kepribadian',
        deskripsi: 'Penilaian motivasi calon santri, kesiapan mental di asrama, dan wawancara wali santri.',
        badge: 'Bobot 30%',
    },
    {
        nama: "Tes Membaca Al-Qur'an & Kitab",
        deskripsi: 'Kelancaran membaca Al-Qur\'an, ketepatan makhraj, tajwid, serta membaca kitab.',
        badge: 'Bobot 25%',
    },
    {
        nama: "Tes Menulis (Imla' & Khath)",
        deskripsi: 'Menulis dikte huruf hijaiyah, kalimat Arab sederhana, dan kebenaran ejaan imla\'.',
        badge: 'Bobot 20%',
    },
    {
        nama: "Tes Hafalan (Surah & Doa)",
        deskripsi: 'Hafalan surat pendek Juz 30, bacaan sholat fardhu, dan doa-doa harian.',
        badge: 'Bobot 25%',
    },
];
</script>

<template>
    <div class="w-full space-y-6">
        <Head title="Jadwal Interview & Tes Masuk - PSB Dalwa Kalbar" />

        <!-- 1. Header Page Title & Subtitle (Clean & Concise) -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200/80 pb-5 dark:border-slate-800">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-2.5 py-0.5 text-[10.5px] font-bold uppercase tracking-wider text-primary dark:bg-blue-950/60 dark:text-blue-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-primary dark:bg-blue-400"></span>
                        Seleksi Penerimaan Santri Baru
                    </span>
                </div>
                <h1 class="mt-1.5 text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    Jadwal Interview & Tes Masuk
                </h1>
                <p class="mt-0.5 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    Informasi sesi ujian seleksi, lokasi ruangan, materi pengujian, dan dewan penguji.
                </p>
            </div>

            <Link
                href="/psb/ujian/pengumuman"
                class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 self-start sm:self-auto"
            >
                <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Lihat Pengumuman</span>
            </Link>
        </div>

        <!-- 2. Waktu & Instruksi Banner (Unified Color Theme) -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-stretch">
            <!-- Countdown Highlight Card -->
            <div
                v-if="countdownInfo"
                class="md:col-span-5 rounded-2xl border border-slate-200/90 bg-white p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        Status Pelaksanaan
                    </span>
                    <span
                        class="rounded-full px-2.5 py-0.5 text-[10.5px] font-black uppercase tracking-wider"
                        :class="countdownInfo.badgeClass"
                    >
                        {{ countdownInfo.badge }}
                    </span>
                </div>

                <div class="my-3 space-y-1">
                    <h3 class="text-base font-black text-slate-900 dark:text-slate-100">
                        {{ countdownInfo.title }}
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        {{ countdownInfo.desc }}
                    </p>
                </div>

                <div class="flex items-center gap-2 pt-2.5 border-t border-slate-100 dark:border-slate-800 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                    <svg class="h-3.5 w-3.5 text-primary shrink-0 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Harap hadir di lokasi 15 menit sebelum dimulai.</span>
                </div>
            </div>

            <!-- Important Notice Banner -->
            <div
                :class="[
                    countdownInfo ? 'md:col-span-7' : 'md:col-span-12',
                    'rounded-2xl border border-primary/20 bg-primary/[0.03] p-5 shadow-2xs dark:border-blue-900/40 dark:bg-blue-950/20 flex flex-col justify-between'
                ]"
            >
                <div class="flex items-start gap-3.5">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary text-white shadow-2xs dark:bg-blue-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="space-y-1 flex-1">
                        <h4 class="text-xs font-black uppercase tracking-wider text-primary dark:text-blue-300">
                            Petunjuk Penting Pelaksanaan Ujian
                        </h4>
                        <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed font-medium">
                            Kartu Ujian & Formulir Pendaftaran fisik wajib dibawa dan ditunjukkan kepada dewan penguji saat absensi registrasi di lokasi.
                        </p>
                    </div>
                </div>

                <div class="mt-3 pt-2.5 border-t border-primary/10 dark:border-blue-900/30 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span class="font-medium">Ukuran Cetak Resmi: A4</span>
                    <span class="font-bold text-primary dark:text-blue-300">Gunakan Tombol 'Cetak Kartu Ujian' pada Kartu Santri</span>
                </div>
            </div>
        </div>

        <!-- 3. Main 2 Column Grid (8 Cols Left, 4 Cols Right) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left Side (8 Cols): Sesi Kelompok, Materi Penilaian, Tata Tertib -->
            <div class="lg:col-span-8 space-y-6">
                <!-- A. Schedule Detail Cards -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-primary dark:bg-blue-400"></span>
                            <h2 class="text-sm font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                Jadwal & Alokasi Kelompok Ujian
                            </h2>
                        </div>
                        <span
                            v-if="props.kelompokUjians && props.kelompokUjians.length > 0"
                            class="text-xs font-bold text-slate-500 dark:text-slate-400"
                        >
                            {{ props.kelompokUjians.length }} Sesi Terdaftar
                        </span>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="!props.kelompokUjians || props.kelompokUjians.length === 0"
                        class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-white p-10 text-center shadow-2xs dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary dark:bg-blue-950/60 dark:text-blue-400">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="mt-3.5 text-sm font-black text-slate-900 dark:text-slate-100">
                            Jadwal Interview Belum Ditetapkan
                        </h3>
                        <p class="mt-1 max-w-sm text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Panitia PSB sedang menyusun alokasi kelompok & dewan penguji. Silakan periksa kembali secara berkala.
                        </p>
                    </div>

                    <!-- List of Kelompok Ujian -->
                    <div v-else class="space-y-4">
                        <div
                            v-for="k in props.kelompokUjians"
                            :key="k.id"
                            class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xs dark:border-slate-800 dark:bg-slate-900"
                        >
                            <!-- Card Header -->
                            <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white p-5 sm:px-6 dark:border-slate-800 dark:from-slate-900 dark:to-slate-900">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center rounded-md bg-primary/10 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-primary dark:bg-blue-950/60 dark:text-blue-300">
                                                Sesi Resmi Ujian Seleksi
                                            </span>
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider border"
                                                :class="[
                                                    k.status === 'COMPLETED' || k.status === 'SELESAI' || k.status === 'completed'
                                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300'
                                                        : k.status === 'IN_PROGRESS' || k.status === 'in_progress'
                                                          ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/60 dark:text-amber-300'
                                                          : 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/60 dark:text-blue-300'
                                                ]"
                                            >
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full"
                                                    :class="[
                                                        k.status === 'COMPLETED' || k.status === 'SELESAI' || k.status === 'completed'
                                                            ? 'bg-emerald-500'
                                                            : k.status === 'IN_PROGRESS' || k.status === 'in_progress'
                                                              ? 'bg-amber-500 animate-ping'
                                                              : 'bg-blue-500'
                                                    ]"
                                                ></span>
                                                {{ k.status || 'SCHEDULED' }}
                                            </span>
                                        </div>
                                        <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100">
                                            {{ k.nama_kelompok || k.name || 'Kelompok Interview & Tes' }}
                                        </h3>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Body: Date / Time / Room Grid -->
                            <div class="p-5 sm:p-6 space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <!-- Tanggal -->
                                    <div class="flex items-start gap-3 rounded-xl border border-slate-200/80 bg-slate-50/60 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-blue-950/60 dark:text-blue-400">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div class="space-y-0.5">
                                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Hari & Tanggal</p>
                                            <p class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">
                                                {{ formatDate(k.tanggal_ujian || k.date) }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Waktu -->
                                    <div class="flex items-start gap-3 rounded-xl border border-slate-200/80 bg-slate-50/60 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="space-y-0.5">
                                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Waktu / Sesi</p>
                                            <p class="font-mono text-xs sm:text-sm font-bold text-emerald-700 dark:text-emerald-300">
                                                {{ formatWaktu(k.waktu_mulai || k.jam_mulai, k.waktu_selesai || k.jam_selesai) }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Lokasi -->
                                    <div class="flex items-start gap-3 rounded-xl border border-slate-200/80 bg-slate-50/60 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <div class="space-y-0.5">
                                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Ruangan & Lokasi</p>
                                            <p class="text-xs font-bold text-slate-900 dark:text-slate-100 leading-snug">
                                                {{ k.lokasi || k.ruangan || 'Gedung Utama Ponpes Dalwa' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dewan Penguji List (Clean List Layout with Clear Details) -->
                                <div class="space-y-3 pt-1">
                                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5 dark:border-slate-800">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                                Dewan Penguji & Koordinator
                                            </span>
                                            <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:text-slate-300">
                                                5 Pos Penilaian
                                            </span>
                                        </div>
                                        <span class="text-[11px] font-medium text-slate-400">
                                            Penilaian Terintegrasi
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                                        <div
                                            v-for="officer in getPetugasList(k)"
                                            :key="officer.key"
                                            class="flex items-start gap-3 rounded-xl border border-slate-200/80 bg-slate-50/50 p-3.5 dark:border-slate-800 dark:bg-slate-800/40 border-l-3"
                                            :class="officer.accentClass"
                                        >
                                            <div class="flex-1 min-w-0 space-y-1">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span
                                                        class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-black uppercase tracking-wide border"
                                                        :class="officer.badgeClass"
                                                    >
                                                        {{ officer.roleTitle }}
                                                    </span>
                                                </div>

                                                <p class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100 leading-snug break-words">
                                                    {{ officer.officerName || 'Belum Diplot Panitia' }}
                                                </p>

                                                <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400 leading-tight">
                                                    {{ officer.roleSub }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- B. Materi Ujian & Rincian Aspek Penilaian (CSS Grid Smooth Accordion) -->
                <div class="rounded-2xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                <h3 class="text-sm font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                    Materi Tes & Aspek Penilaian
                                </h3>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Klik kategori materi di bawah untuk melihat rincian indikator & bobot pengujian
                            </p>
                        </div>

                        <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10.5px] font-bold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                            Terintegrasi
                        </span>
                    </div>

                    <!-- Dynamic Kategori Accordion with CSS Grid Smooth Transition -->
                    <div v-if="props.kategoriPenilaians && props.kategoriPenilaians.length > 0" class="space-y-3">
                        <div
                            v-for="kategori in props.kategoriPenilaians"
                            :key="kategori.id"
                            class="rounded-xl border border-slate-200/80 bg-slate-50/50 dark:border-slate-800 dark:bg-slate-800/40 transition-colors"
                            :class="activeAccordionKategori === kategori.id ? 'bg-white dark:bg-slate-800/70 border-primary/30 dark:border-blue-500/40 shadow-2xs' : ''"
                        >
                            <button
                                type="button"
                                @click="toggleAccordion(kategori.id)"
                                class="w-full flex items-center justify-between p-4 text-left cursor-pointer hover:bg-slate-100/60 dark:hover:bg-slate-800/80 transition-colors rounded-xl"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-2.5 w-2.5 rounded-full shrink-0 transition-colors"
                                        :class="activeAccordionKategori === kategori.id ? 'bg-primary dark:bg-blue-400' : 'bg-slate-300 dark:bg-slate-600'"
                                    ></div>
                                    <div>
                                        <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">
                                            {{ kategori.nama_kategori }}
                                        </h4>
                                        <p v-if="kategori.keterangan" class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                            {{ kategori.keterangan }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2.5 shrink-0">
                                    <span class="rounded-md bg-white dark:bg-slate-900 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        {{ kategori.aspek_penilaians?.length || 0 }} Indikator
                                    </span>
                                    <div
                                        class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 transition-transform duration-300"
                                        :class="activeAccordionKategori === kategori.id ? 'rotate-180 bg-primary/10 text-primary dark:bg-blue-950/60 dark:text-blue-300' : ''"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </button>

                            <!-- Smooth Grid Transition Body -->
                            <div
                                class="grid transition-all duration-300 ease-in-out"
                                :class="activeAccordionKategori === kategori.id ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'"
                            >
                                <div class="overflow-hidden">
                                    <div class="border-t border-slate-200/80 bg-white p-4 sm:p-5 dark:border-slate-800 dark:bg-slate-900/80 space-y-3">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                            <div
                                                v-for="aspek in (kategori.aspek_penilaians || [])"
                                                :key="aspek.id"
                                                class="rounded-xl border border-slate-100 bg-slate-50/70 p-3.5 dark:border-slate-800 dark:bg-slate-800/50 space-y-1.5"
                                            >
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="font-bold text-slate-900 dark:text-slate-100">
                                                        {{ aspek.nama_aspek }}
                                                    </span>
                                                    <span class="rounded-md bg-primary/10 text-primary dark:bg-blue-950/60 dark:text-blue-300 px-1.5 py-0.5 text-[10px] font-black shrink-0">
                                                        Bobot {{ aspek.bobot }}%
                                                    </span>
                                                </div>
                                                <p v-if="aspek.indikator" class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                                    {{ aspek.indikator }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Default Fallback Cards if no dynamic kategori -->
                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div
                            v-for="materi in defaultMateriList"
                            :key="materi.nama"
                            class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-4 space-y-1.5 dark:border-slate-800 dark:bg-slate-800/40"
                        >
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ materi.nama }}</h4>
                                <span class="rounded-md bg-primary/10 text-primary dark:bg-blue-950/60 dark:text-blue-300 px-2 py-0.5 text-[10px] font-bold">{{ materi.badge }}</span>
                            </div>
                            <p class="text-[11.5px] leading-relaxed text-slate-600 dark:text-slate-400">
                                {{ materi.deskripsi }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- C. Tata Tertib & Ketentuan Pelaksanaan -->
                <div class="rounded-2xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900 space-y-4">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                        <h3 class="text-sm font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">
                            Tata Tertib & Persiapan Peserta
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div
                            v-for="item in tataTertib"
                            :key="item.title"
                            class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50/60 p-3.5 dark:border-slate-800 dark:bg-slate-800/40"
                        >
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-blue-950/60 dark:text-blue-400 font-bold">
                                <svg v-if="item.icon === 'clock'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg v-else-if="item.icon === 'card'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                </svg>
                                <svg v-else-if="item.icon === 'cloth'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </div>
                            <div class="space-y-0.5">
                                <h4 class="font-bold text-slate-900 dark:text-slate-100">
                                    {{ item.title }}
                                </h4>
                                <p class="text-slate-500 dark:text-slate-400 leading-relaxed text-[11px]">
                                    {{ item.desc }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side (4 Cols): Kartu Calon Santri & Helpdesk -->
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
                            <span>Cetak Kartu Ujian</span>
                        </button>
                    </div>
                </div>

                <!-- 2. Help Card (Pusat Bantuan WhatsApp Sejajar) -->
                <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-2xs dark:border-slate-800 dark:bg-slate-900 space-y-3.5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-2xs">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                Bantuan & Info Jadwal
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
                        :href="`https://wa.me/${cleanWaNumber}?text=Assalamu%27alaikum%20Panitia%20PSB%20Dalwa%20Kalbar,%20saya%20${encodeURIComponent(props.pendaftar?.nama || '')}%20(No.%20Reg:%20${encodeURIComponent(props.pendaftar?.nomor_pendaftaran || '-')})%20ingin%20menanyakan%20perihal%20jadwal%20ujian.`"
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
