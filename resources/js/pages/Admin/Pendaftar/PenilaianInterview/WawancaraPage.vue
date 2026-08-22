<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import BackButton from '@/Components/BackButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import TextareaInput from '@/Components/Form/TextareaInput.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { store_wawancara } from '@/routes/admin/pendaftar/penilaian_interview';

defineOptions({ layout: AdminLayout });

const props = defineProps<{
    kelompok: any;
    pendaftar: any;
    kopSuratUrl: string;
    wawancaraData: any;
    hasilWawancara: string | null;
    rekomendasiKelasPondok?: string | null;
    catatanFinal?: string | null;
    activeStep: number;
    currentStep?: number;
    backUrl: string;
}>();

const isGroupLocked = computed(() => {
    return (
        (props.kelompok.status || '').toLowerCase() === 'completed' ||
        Boolean(props.pendaftar?.hasil_ujian?.locked_at || props.pendaftar?.hasilUjian?.locked_at)
    );
});

const steps = [
    { id: 1, title: 'A. Motivasi & Kesiapan', shortTitle: 'Motivasi' },
    { id: 2, title: 'B. Kebiasaan Sehari-hari', shortTitle: 'Kebiasaan' },
    { id: 3, title: 'C. Ibadah & Keagamaan', shortTitle: 'Ibadah' },
    { id: 4, title: 'D. Pelanggaran & Perilaku', shortTitle: 'Pelanggaran' },
    { id: 5, title: 'E. Prestasi Akademik', shortTitle: 'Prestasi' },
    { id: 6, title: 'F. Penguji & Penentuan Kelas', shortTitle: 'Hasil & Kelas' },
];

const form = useForm({
    pendaftar_id: props.pendaftar.id,
    kelompok_ujian_id: props.kelompok.id,
    current_step: props.activeStep || props.currentStep || 1,
    next_step: props.activeStep || props.currentStep || 1,
    is_finished: false,
    wawancara_data: {
        motivasi: {
            cita_cita: props.wawancaraData?.motivasi?.cita_cita || '',
            keinginan_mondok: props.wawancaraData?.motivasi?.keinginan_mondok || '',
            bersedia_mondok_4_tahun: props.wawancaraData?.motivasi?.bersedia_mondok_4_tahun || '',
            tidak_ambil_ijazah: props.wawancaraData?.motivasi?.tidak_ambil_ijazah || '',
            catatan_tidak_ambil_ijazah: props.wawancaraData?.motivasi?.catatan_tidak_ambil_ijazah || '',
            kenalan: {
                nama: props.wawancaraData?.motivasi?.kenalan?.nama || '',
                status: props.wawancaraData?.motivasi?.kenalan?.status || '',
            },
        },
        kebiasaan: {
            jam_tidur: props.wawancaraData?.kebiasaan?.jam_tidur || '',
            jam_bangun: props.wawancaraData?.kebiasaan?.jam_bangun || '',
            kegiatan_malam: props.wawancaraData?.kebiasaan?.kegiatan_malam || '',
            riwayat_penyakit: props.wawancaraData?.kebiasaan?.riwayat_penyakit || '',
        },
        ibadah: {
            sholat_5_waktu: props.wawancaraData?.ibadah?.sholat_5_waktu || '',
            sholat_berjamaah: props.wawancaraData?.ibadah?.sholat_berjamaah || '',
            shodaqoh: props.wawancaraData?.ibadah?.shodaqoh || '',
            membantu: props.wawancaraData?.ibadah?.membantu || '',
            catatan: props.wawancaraData?.ibadah?.catatan || '',
            bacaan_sholat: props.wawancaraData?.ibadah?.bacaan_sholat || {},
            catatan_bacaan: props.wawancaraData?.ibadah?.catatan_bacaan || '',
        },
        pelanggaran: {
            pernah_dilakukan: props.wawancaraData?.pelanggaran?.pernah_dilakukan || [],
            catatan: props.wawancaraData?.pelanggaran?.catatan || '',
        },
        prestasi: {
            items: props.wawancaraData?.prestasi?.items || [],
            catatan_pondok: props.wawancaraData?.prestasi?.catatan_pondok || '',
            catatan_sekolah: props.wawancaraData?.prestasi?.catatan_sekolah || '',
        },
    },
    hasil_wawancara: props.hasilWawancara || props.pendaftar?.hasil_ujian?.hasil_wawancara || props.pendaftar?.hasilUjian?.hasil_wawancara || '',
    rekomendasi_kelas_pondok: props.rekomendasiKelasPondok || props.pendaftar?.hasil_ujian?.rekomendasi_kelas_pondok || props.pendaftar?.hasilUjian?.rekomendasi_kelas_pondok || '',
    catatan_final: props.catatanFinal || props.pendaftar?.hasil_ujian?.catatan_final || props.pendaftar?.hasilUjian?.catatan_final || '',
});

const getInitialStep = (): number => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        const stepParam = parseInt(params.get('step') || '', 10);

        if (stepParam >= 1 && stepParam <= steps.length) {
            return stepParam;
        }
    }

    return props.activeStep || props.currentStep || 1;
};

const activeStep = ref<number>(getInitialStep());

watch(activeStep, (newStep: number) => {
    if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set('step', newStep.toString());
        window.history.replaceState({}, '', url);
    }
});

const errors = ref<Record<string, string>>({});

const validateStep = (stepNumber: number): boolean => {
    errors.value = {};
    const data = form.wawancara_data;

    if (stepNumber === 1) {
        if (!data.motivasi.keinginan_mondok) {
            errors.value.keinginan_mondok = 'Keinginan mondok wajib dipilih.';
        }

        if (!data.motivasi.bersedia_mondok_4_tahun) {
            errors.value.bersedia_mondok_4_tahun = 'Pernyataan bersedia mondok minimal 4 tahun wajib dipilih.';
        }

        if (!data.motivasi.tidak_ambil_ijazah) {
            errors.value.tidak_ambil_ijazah = 'Pernyataan ijazah wajib dipilih.';
        }

        if (data.motivasi.tidak_ambil_ijazah === 'Tidak' && !data.motivasi.catatan_tidak_ambil_ijazah) {
            errors.value.catatan_tidak_ambil_ijazah = 'Catatan apabila jawaban Tidak wajib diisi.';
        }
    } else if (stepNumber === 2) {
        if (!data.kebiasaan.jam_tidur) {
            errors.value.jam_tidur = 'Jam tidur wajib diisi.';
        }

        if (!data.kebiasaan.jam_bangun) {
            errors.value.jam_bangun = 'Jam bangun wajib diisi.';
        }
    } else if (stepNumber === 3) {
        if (!data.ibadah.sholat_5_waktu) {
errors.value.sholat_5_waktu = 'Aspek Sholat 5 Waktu wajib dipilih.';
}

        if (!data.ibadah.sholat_berjamaah) {
errors.value.sholat_berjamaah = 'Aspek Sholat Berjamaah wajib dipilih.';
}

        if (!data.ibadah.shodaqoh) {
errors.value.shodaqoh = 'Aspek Shodaqoh wajib dipilih.';
}

        if (!data.ibadah.membantu) {
errors.value.membantu = 'Aspek Membantu (Orang Lain) wajib dipilih.';
}
    } else if (stepNumber === 6) {
        if (!form.hasil_wawancara) {
            errors.value.hasil_wawancara = 'Keputusan Hasil Wawancara (A, C, atau D) wajib dipilih.';
        }
    }

    return Object.keys(errors.value).length === 0;
};

const goToStep = (stepIndex: number) => {
    if (stepIndex === activeStep.value) {
        return;
    }

    if (isGroupLocked.value) {
        activeStep.value = stepIndex;
        errors.value = {};

        if (typeof window !== 'undefined') {
            window.scrollTo({ top: 150, behavior: 'smooth' });
        }

        return;
    }

    if (stepIndex < activeStep.value) {
        activeStep.value = stepIndex;
        errors.value = {};

        if (typeof window !== 'undefined') {
            window.scrollTo({ top: 150, behavior: 'smooth' });
        }

        return;
    }

    if (!validateStep(activeStep.value)) {
        if (typeof window !== 'undefined') {
            window.scrollTo({ top: 150, behavior: 'smooth' });
        }

        return;
    }

    errors.value = {};
    form.is_finished = false;
    form.current_step = activeStep.value;
    form.next_step = stepIndex;
    form.post(store_wawancara.url(), {
        preserveScroll: true,
        onSuccess: () => {
            activeStep.value = stepIndex;

            if (typeof window !== 'undefined') {
                window.scrollTo({ top: 150, behavior: 'smooth' });
            }
        },
    });
};

const nextStep = () => {
    if (activeStep.value < steps.length) {
        if (isGroupLocked.value) {
            activeStep.value++;
            if (typeof window !== 'undefined') {
                window.scrollTo({ top: 150, behavior: 'smooth' });
            }
            return;
        }
        goToStep(activeStep.value + 1);
    }
};

const prevStep = () => {
    if (activeStep.value > 1) {
        activeStep.value--;
        errors.value = {};

        if (typeof window !== 'undefined') {
            window.scrollTo({ top: 150, behavior: 'smooth' });
        }
    }
};

const saveAndFinish = () => {
    if (isGroupLocked.value) {
        router.visit(props.backUrl);
        return;
    }

    if (!validateStep(activeStep.value)) {
        if (typeof window !== 'undefined') {
            window.scrollTo({ top: 150, behavior: 'smooth' });
        }

        return;
    }

    errors.value = {};
    form.current_step = 6;
    form.next_step = 6;
    form.is_finished = true;
    form.post(store_wawancara.url(), {
        preserveScroll: false,
    });
};

// Data Helpers
const bacaanList = [
    'Takbiratul Ihram', 'Surat Al-Fatihah', 'Tasyahud Akhir', 'Sholawat Nabi',
    'Salam Pertama', 'Doa Iftitah', 'Ta\'awudz', 'Membaca Surat/Ayat Al-Qur\'an',
    'Bacaan Ruku', 'Bacaan I\'tidal', 'Bacaan Sujud', 'Bacaan Duduk di Antara Dua Sujud',
    'Bacaan Tasyahud Awal', 'Doa Sebelum Salam', 'Bacaan Wirid Sholat', 'Bacaan Doa Sehari-hari'
];
const skorBacaanList = ['Kurang', 'Cukup', 'Baik', 'Tidak Bisa'];

const ibadahItems = ['sholat_5_waktu', 'sholat_berjamaah', 'shodaqoh', 'membantu'];
const labelIbadah: Record<string, string> = {
    'sholat_5_waktu': 'Sholat 5 Waktu',
    'sholat_berjamaah': 'Sholat Berjamaah',
    'shodaqoh': 'Shodaqoh',
    'membantu': 'Membantu (Orang Lain)',
};
const frekuensiList = ['Sering', 'Jarang', 'Tidak Pernah'];

const pelanggaranItems = [
    'BSN OT', 'BSH OT', 'Ghosob', 'Sariqoh', 'Thakossum', 'Dukhon',
    'Khamr', 'Mukhoddirot', 'Jawwal', 'Kholiah', 'Istimna\'', 'Liwath',
    'Hawian', 'PT', 'CP', 'KB', 'PS', 'PK', 'TB', 'D'
];

const prestasiItems = ['Juara Kelas', 'Tidak Naik Kelas', 'Bintang Pelajar', 'Juara Lomba (Akademik)', 'Juara Lomba (Non-Akademik)'];

// Quick Fill Helpers
const setAllBacaan = (score: string) => {
    bacaanList.forEach((bacaan) => {
        form.wawancara_data.ibadah.bacaan_sholat[bacaan] = score;
    });
};

const setAllPelanggaran = (frek: string) => {
    pelanggaranItems.forEach((_, idx) => {
        form.wawancara_data.pelanggaran.pernah_dilakukan[idx] = frek;
    });
};

const setAllPrestasi = (frek: string) => {
    prestasiItems.forEach((_, idx) => {
        form.wawancara_data.prestasi.items[idx] = frek;
    });
};

// Kelas Pondok Helpers
const isKelasSelected = (val: string) => {
    if (!form.rekomendasi_kelas_pondok) {
        return false;
    }
    const current = form.rekomendasi_kelas_pondok.toLowerCase().replace('kelas ', '').trim();
    const target = val.toLowerCase().replace('kelas ', '').trim();

    return current === target;
};

const selectKelasPondok = (val: string) => {
    if (isKelasSelected(val)) {
        form.rekomendasi_kelas_pondok = '';
    } else {
        form.rekomendasi_kelas_pondok = val;
    }
};

// Hasil Options
const hasilWawancaraCards = [
    {
        value: 'A',
        label: 'A - MEMENUHI',
        subLabel: 'Sangat Direkomendasikan',
        description: 'Calon santri memenuhi kriteria adab, motivasi, dan kesiapan untuk mondok.',
        badgeClass: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300',
        activeClass: 'border-emerald-500 bg-emerald-50/70 ring-2 ring-emerald-500/30 dark:bg-emerald-950/40 dark:border-emerald-500',
    },
    {
        value: 'C',
        label: 'C - SYARAT TERTENTU',
        subLabel: 'Dipertimbangkan',
        description: 'Calon santri dapat diterima dengan syarat atau pembinaan khusus.',
        badgeClass: 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
        activeClass: 'border-amber-500 bg-amber-50/70 ring-2 ring-amber-500/30 dark:bg-amber-950/40 dark:border-amber-500',
    },
    {
        value: 'D',
        label: 'D - TIDAK MEMENUHI',
        subLabel: 'Tidak Direkomendasikan',
        description: 'Calon santri belum memenuhi kriteria kesiapan atau memiliki kendala signifikan.',
        badgeClass: 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300',
        activeClass: 'border-rose-500 bg-rose-50/70 ring-2 ring-rose-500/30 dark:bg-rose-950/40 dark:border-rose-500',
    },
];
// Formatters
const formatIndonesianDate = (dateStr: string | null | undefined): string => {
    if (!dateStr) {
return '-';
}

    try {
        const cleanStr = String(dateStr).split('T')[0];
        const parts = cleanStr.split('-');

        if (parts.length === 3) {
            const months = [
                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];
            const year = parts[0];
            const monthIdx = parseInt(parts[1], 10) - 1;
            const day = parseInt(parts[2], 10);

            if (monthIdx >= 0 && monthIdx < 12) {
                return `${day} ${months[monthIdx]} ${year}`;
            }
        }

        const d = new Date(dateStr);

        if (!isNaN(d.getTime())) {
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        }

        return cleanStr;
    } catch {
        return String(dateStr).split('T')[0];
    }
};

const formatTimeRange = (start?: string, end?: string): string => {
    if (!start) {
return '08:00 - 12:00 WIB';
}

    const s = start.substring(0, 5);
    const e = end ? end.substring(0, 5) : '';
    return e ? `${s} - ${e} WIB` : `${s} WIB`;
};

// Candidate Image and Logo Helpers
const imageError = ref(false);

const getPendaftarPhoto = (pendaftar: any) => {
    if (!pendaftar) {
        return null;
    }

    const raw =
        pendaftar.foto_url ||
        pendaftar.foto ||
        pendaftar.personal_data?.foto_url ||
        pendaftar.personal_data?.foto ||
        pendaftar.personal_data?.pas_foto ||
        pendaftar.dokumens?.find(
            (d: any) =>
                d.dokumen?.is_profile_photo ||
                d.dokumen?.name?.toLowerCase().includes('foto'),
        )?.file_path ||
        null;

    if (!raw) {
        return null;
    }

    if (
        raw.startsWith('http://') ||
        raw.startsWith('https://') ||
        raw.startsWith('data:image') ||
        raw.startsWith('/storage/') ||
        raw.startsWith('/')
    ) {
        return raw;
    }

    if (raw.startsWith('storage/')) {
        return `/${raw}`;
    }

    return `/storage/${raw.replace(/^\/+/, '')}`;
};

const getInitials = (name?: string) => {
    if (!name) {
        return 'CS';
    }

    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase();
};

const isMale = computed(() => {
    const gender = (props.pendaftar?.personal_data?.jenis_kelamin || props.pendaftar?.gender || '').toLowerCase();
    return gender.includes('laki') || gender === 'l';
});

const isFemale = computed(() => {
    const gender = (props.pendaftar?.personal_data?.jenis_kelamin || props.pendaftar?.gender || '').toLowerCase();
    return gender.includes('perempuan') || gender === 'p';
});

const asalSekolah = computed(() => {
    return (
        props.pendaftar?.education_data?.nama_sekolah_asal ||
        props.pendaftar?.education_data?.asal_sekolah ||
        props.pendaftar?.address_data?.kabupaten_kota ||
        null
    );
});

const namaCabang = computed(() => {
    return (
        props.pendaftar?.cabang?.name ||
        props.pendaftar?.personal_data?.cabang_pendaftaran ||
        (props.pendaftar?.cabang_id ? 'Kalimantan Barat' : '-')
    );
});

const getJenjangLogo = (jenjangOrCode?: any) => {
    if (typeof jenjangOrCode === 'object' && jenjangOrCode?.logo_path) {
        return jenjangOrCode.logo_path.startsWith('/')
            ? jenjangOrCode.logo_path
            : `/${jenjangOrCode.logo_path}`;
    }

    const code = (
        typeof jenjangOrCode === 'string'
            ? jenjangOrCode
            : jenjangOrCode?.code || jenjangOrCode?.singkatan || jenjangOrCode?.name || ''
    ).toUpperCase();

    if (code === 'MTS' || code.includes('TSANAWIYAH')) {
        return '/image/logos/jenjang/logo-mts.png';
    }

    if (code === 'MA' || code.includes('ALIYAH')) {
        return '/image/logos/jenjang/logo-ma.png';
    }

    if (
        code === 'S1' ||
        code === 'S2' ||
        code === 'S3' ||
        code.includes('UII') ||
        code.includes('UNI') ||
        code.includes('DALWA') ||
        code.includes('SARJANA') ||
        code.includes('MAGISTER') ||
        code.includes('DOKTOR') ||
        code.includes('PASCASARJANA')
    ) {
        return '/image/logos/jenjang/logo-uii dalwa.png';
    }

    return '/image/logos/logo-1.png';
};
</script>

<template>
    <div class="w-full min-h-screen bg-slate-50/50 pb-16 dark:bg-slate-950">
        <Head :title="`Formulir Wawancara - ${pendaftar.nama}`" />

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- TOP BAR: TITLE & BACK BUTTON -->
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-slate-100">
                        Formulir Wawancara Calon Santri
                    </h1>
                    <p class="mt-1 text-xs text-gray-500 sm:text-sm dark:text-slate-400">
                        Penerimaan Santri Baru Pondok Pesantren Dalwa Kalbar
                    </p>
                </div>
                <div class="flex shrink-0 items-center">
                    <BackButton :href="backUrl">Kembali</BackButton>
                </div>
            </div>

            <!-- CANDIDATE PROFILE HERO CARD -->
            <div class="mb-8 relative overflow-hidden rounded-3xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <!-- Subtle decorative background gradient accents -->
                <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-primary/5 blur-3xl dark:bg-blue-500/5"></div>
                <div class="pointer-events-none absolute -left-16 -bottom-16 h-64 w-64 rounded-full bg-emerald-500/5 blur-3xl dark:bg-emerald-500/5"></div>

                <div class="relative grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-center">
                    <!-- Left: Avatar Photo + Santri Details (Col span 7) -->
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-5 min-w-0 lg:col-span-7">
                        <!-- Photo/Avatar Box with refined frame -->
                        <div class="relative h-20 w-20 sm:h-24 sm:w-24 shrink-0 overflow-hidden rounded-2xl border-2 border-white bg-slate-100 shadow-md ring-4 ring-slate-100/90 dark:border-slate-800 dark:bg-slate-800 dark:ring-slate-800/60">
                            <img
                                v-if="getPendaftarPhoto(pendaftar) && !imageError"
                                :src="getPendaftarPhoto(pendaftar)!"
                                :alt="pendaftar.nama"
                                @error="imageError = true"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full flex-col items-center justify-center font-black text-white shadow-inner"
                                :class="isFemale ? 'bg-gradient-to-br from-rose-500 via-pink-600 to-rose-700' : 'bg-gradient-to-br from-blue-600 via-indigo-600 to-blue-800'"
                            >
                                <span class="text-xl sm:text-2xl tracking-wider">{{ getInitials(pendaftar.nama) }}</span>
                            </div>
                        </div>

                        <!-- Identity Information -->
                        <div class="min-w-0 flex-1 space-y-2">
                            <!-- Name & Gender + Type Badges -->
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg sm:text-2xl font-black text-slate-900 tracking-tight truncate dark:text-slate-100" :title="pendaftar.nama">
                                    {{ pendaftar.nama }}
                                </h2>

                                <!-- Gender Badge (Pure SVG, No Emoji) -->
                                <span
                                    v-if="isMale"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 border border-blue-200/70 px-2.5 py-0.5 text-[10px] font-extrabold text-blue-700 uppercase tracking-wider dark:bg-blue-950/60 dark:border-blue-800/60 dark:text-blue-300"
                                >
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 8v-4h-4M16 4l-5 5" />
                                        <circle cx="9" cy="13" r="5" stroke-width="2.5" />
                                    </svg>
                                    Laki-Laki
                                </span>
                                <span
                                    v-else-if="isFemale"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-pink-50 border border-pink-200/70 px-2.5 py-0.5 text-[10px] font-extrabold text-pink-700 uppercase tracking-wider dark:bg-pink-950/60 dark:border-pink-800/60 dark:text-pink-300"
                                >
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v6m-3-3h6" />
                                        <circle cx="12" cy="9" r="6" stroke-width="2.5" />
                                    </svg>
                                    Perempuan
                                </span>

                                <span
                                    v-if="pendaftar.tipe_pendaftaran || pendaftar.education_data?.tipe_pendaftaran"
                                    class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 border border-slate-200/70 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700"
                                >
                                    {{ pendaftar.tipe_pendaftaran || pendaftar.education_data?.tipe_pendaftaran }}
                                </span>
                            </div>

                            <!-- Detailed Metadata Row -->
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-slate-500 dark:text-slate-400">
                                <div class="flex items-center gap-1">
                                    <span class="text-slate-400 font-normal">No. Reg:</span>
                                    <span class="font-mono font-black text-primary dark:text-blue-400">{{ pendaftar.nomor_pendaftaran || '-' }}</span>
                                </div>
                                <span class="text-slate-300 dark:text-slate-700">•</span>
                                <div class="flex items-center gap-1">
                                    <span class="text-slate-400 font-normal">NIK:</span>
                                    <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ pendaftar.nik || pendaftar.personal_data?.nik || '-' }}</span>
                                </div>
                                <span class="text-slate-300 dark:text-slate-700">•</span>
                                <div class="flex items-center gap-1">
                                    <span class="text-slate-400 font-normal">Cabang:</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ namaCabang }}</span>
                                </div>
                                <template v-if="asalSekolah">
                                    <span class="text-slate-300 dark:text-slate-700">•</span>
                                    <div class="flex items-center gap-1 truncate max-w-[240px]" :title="asalSekolah">
                                        <span class="text-slate-400 font-normal">Asal:</span>
                                        <span class="font-medium text-slate-700 dark:text-slate-300 truncate">{{ asalSekolah }}</span>
                                    </div>
                                </template>
                            </div>

                            <!-- Badges Row: Jenjang with Logo & Gelombang / Periode -->
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <!-- Jenjang Badge with Official Logo -->
                                <span
                                    v-if="pendaftar.jenjang?.name || pendaftar.education_data?.jenjang"
                                    class="inline-flex items-center gap-2 rounded-xl border border-primary/25 bg-primary/5 px-3 py-1 text-xs font-black text-primary dark:border-blue-500/30 dark:bg-blue-950/50 dark:text-blue-300 shadow-2xs"
                                >
                                    <img
                                        :src="getJenjangLogo(pendaftar.jenjang || pendaftar.education_data?.jenjang)"
                                        :alt="pendaftar.jenjang?.name"
                                        class="h-4 w-4 object-contain"
                                    />
                                    <span>{{ pendaftar.jenjang?.name || pendaftar.education_data?.jenjang }}</span>
                                </span>

                                <!-- Gelombang Badge -->
                                <span
                                    v-if="pendaftar.gelombang?.nama_gelombang || pendaftar.gelombang?.name"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50/80 px-2.5 py-1 text-xs font-bold text-slate-700 dark:border-slate-700/80 dark:bg-slate-800 dark:text-slate-300"
                                >
                                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    {{ pendaftar.gelombang?.nama_gelombang || pendaftar.gelombang?.name }}
                                </span>

                                <!-- Periode Badge -->
                                <span
                                    v-if="pendaftar.periode?.nama_periode || pendaftar.periode?.name"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50/80 px-2.5 py-1 text-xs font-bold text-slate-700 dark:border-slate-700/80 dark:bg-slate-800 dark:text-slate-300"
                                >
                                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Periode {{ pendaftar.periode?.nama_periode || pendaftar.periode?.name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Exam Schedule & Interview Status Widgets (Col span 5) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 lg:col-span-5 border-t border-slate-100 pt-4 lg:border-t-0 lg:pt-0 dark:border-slate-800/80">
                        <!-- Widget 1: Jadwal & Ruang Ujian -->
                        <div class="flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-slate-50/60 p-3.5 shadow-2xs transition-colors hover:border-slate-300 dark:border-slate-800/80 dark:bg-slate-950/40 dark:hover:border-slate-700">
                            <div>
                                <div class="flex items-center justify-between gap-1.5 mb-1.5">
                                    <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                                        <svg class="h-3.5 w-3.5 text-primary dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="text-[10px] font-black uppercase tracking-wider">Jadwal & Ruang</span>
                                    </div>
                                    <span v-if="kelompok.nama_kelompok" class="truncate max-w-[100px] text-[9px] font-black uppercase px-1.5 py-0.5 rounded-md bg-white border border-slate-200 text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300" :title="kelompok.nama_kelompok">
                                        {{ kelompok.nama_kelompok }}
                                    </span>
                                </div>
                                <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">
                                    {{ formatIndonesianDate(kelompok.tanggal_ujian) }}
                                </div>
                            </div>
                            <div class="mt-2 text-[11px] font-medium text-slate-500 dark:text-slate-400 leading-tight">
                                <span>{{ kelompok.lokasi || 'Ruang Wawancara' }}</span>
                                <span class="block text-[10px] text-slate-400 mt-0.5">{{ formatTimeRange(kelompok.waktu_mulai, kelompok.waktu_selesai) }}</span>
                            </div>
                        </div>

                        <!-- Widget 2: Hasil & Penentuan Kelas -->
                        <div class="flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-slate-50/60 p-3.5 shadow-2xs transition-colors hover:border-slate-300 dark:border-slate-800/80 dark:bg-slate-950/40 dark:hover:border-slate-700">
                            <div>
                                <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400 mb-1.5">
                                    <svg class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-[10px] font-black uppercase tracking-wider">Hasil Wawancara</span>
                                </div>
                                <div>
                                    <span v-if="form.hasil_wawancara === 'A'" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100/80 px-2.5 py-0.5 text-xs font-black text-emerald-800 ring-1 ring-emerald-600/30 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        A - Memenuhi
                                    </span>
                                    <span v-else-if="form.hasil_wawancara === 'C'" class="inline-flex items-center gap-1.5 rounded-full bg-amber-100/80 px-2.5 py-0.5 text-xs font-black text-amber-800 ring-1 ring-amber-600/30 dark:bg-amber-950/60 dark:text-amber-300">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                        C - Syarat Tertentu
                                    </span>
                                    <span v-else-if="form.hasil_wawancara === 'D'" class="inline-flex items-center gap-1.5 rounded-full bg-rose-100/80 px-2.5 py-0.5 text-xs font-black text-rose-800 ring-1 ring-rose-600/30 dark:bg-rose-950/60 dark:text-rose-300">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                        D - Tidak Memenuhi
                                    </span>
                                    <span v-else class="inline-flex items-center rounded-lg bg-slate-200/60 px-2 py-0.5 text-[11px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                        Belum Dinilai
                                    </span>
                                </div>
                            </div>

                            <div class="mt-2 pt-1 border-t border-slate-200/50 dark:border-slate-800/50 flex items-center justify-between text-[11px]">
                                <span class="text-slate-400">Kelas:</span>
                                <span v-if="form.rekomendasi_kelas_pondok" class="font-black text-primary dark:text-blue-400">
                                    {{ form.rekomendasi_kelas_pondok }}
                                </span>
                                <span v-else class="text-slate-400 italic text-[10px]">
                                    Belum Ditentukan
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MAIN FORM LAYOUT WITH STEPPER -->
            <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
                <!-- SIDEBAR STEPPER (STICKY) -->
                <div class="sticky top-24 lg:col-span-4 space-y-4">
                    <div class="overflow-hidden rounded-3xl border border-gray-200/80 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="px-3 py-2 border-b border-gray-100 dark:border-slate-800 mb-2">
                            <h3 class="text-xs font-black tracking-wider uppercase text-gray-400">Daftar Bagian Formulir</h3>
                        </div>
                        <nav class="space-y-1.5">
                            <button
                                v-for="step in steps"
                                :key="step.id"
                                type="button"
                                @click="goToStep(step.id)"
                                class="group flex w-full items-center justify-between rounded-2xl px-3.5 py-3 text-left transition-all duration-200 cursor-pointer"
                                :class="[
                                    activeStep === step.id
                                        ? 'bg-primary text-white font-bold shadow-md shadow-primary/20 dark:bg-blue-600 dark:text-white'
                                        : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-slate-300 dark:hover:bg-slate-800/60'
                                ]"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-black transition-all"
                                        :class="[
                                            activeStep === step.id
                                                ? 'bg-white text-primary dark:bg-slate-900 dark:text-blue-400'
                                                : activeStep > step.id
                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400'
                                                    : 'bg-gray-100 text-gray-500 dark:bg-slate-800 dark:text-slate-400'
                                        ]"
                                    >
                                        <svg v-if="activeStep > step.id" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span v-else>{{ step.id }}</span>
                                    </span>
                                    <span class="text-xs sm:text-sm font-semibold truncate">{{ step.title }}</span>
                                </div>

                                <svg v-if="activeStep === step.id" class="h-4 w-4 shrink-0 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </nav>
                    </div>

                    <!-- CONFIDENTIALITY NOTICE -->
                    <div class="rounded-3xl border border-amber-200/80 bg-amber-50/70 p-4.5 text-xs leading-relaxed text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300 space-y-1.5">
                        <div class="flex items-center gap-2 font-bold">
                            <svg class="h-4 w-4 text-amber-600 dark:text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <span>Sifat Dokumen: Rahasia</span>
                        </div>
                        <p>
                            Formulir ini bersifat <strong>RAHASIA</strong> dan hanya dipergunakan untuk keperluan seleksi internal Pondok Pesantren Dalwa Kalbar.
                        </p>
                    </div>
                </div>

                <!-- MAIN FORM CONTAINER -->
                <div class="lg:col-span-8">
                    <div class="overflow-hidden rounded-4xl border border-gray-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <!-- KOP SURAT PRESENTATION -->
                        <div class="border-b border-gray-100 bg-white p-4 sm:p-6 flex justify-center dark:border-slate-800 dark:bg-slate-900">
                            <img :src="kopSuratUrl" alt="Kop Surat" class="w-full max-w-3xl h-auto object-contain rounded-2xl shadow-2xs" />
                        </div>

                        <!-- FORM BODY -->
                        <div class="p-6 sm:p-8">
                            <!-- BANNER MODE PREVIEW (TERKUNCI) -->
                            <div
                                v-if="isGroupLocked"
                                class="mb-6 flex items-center gap-3.5 rounded-2xl border border-amber-200 bg-amber-50/90 p-4 text-xs font-bold text-amber-900 shadow-2xs dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300"
                            >
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-black text-sm text-amber-950 dark:text-amber-200">Mode Pratinjau (Hasil Wawancara & Ujian Telah Dikunci)</p>
                                    <p class="mt-0.5 text-[11px] font-medium text-amber-800/90 dark:text-amber-300/90">
                                        Hasil wawancara calon santri ini telah dikunci. Data formulir ditampilkan dalam mode pratinjau (hanya lihat).
                                    </p>
                                </div>
                            </div>

                            <form @submit.prevent="activeStep === steps.length ? saveAndFinish() : nextStep()">
                                
                                <!-- ERROR BANNER -->
                                <div v-if="Object.keys(errors).length > 0" class="mb-6 rounded-2xl bg-rose-50 p-4 border border-rose-200 dark:bg-rose-950/40 dark:border-rose-900/50">
                                    <div class="flex items-center gap-3">
                                        <svg class="h-5 w-5 text-rose-600 dark:text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="text-sm font-semibold text-rose-800 dark:text-rose-200">
                                            Mohon lengkapi seluruh bidang data yang wajib diisi (<span class="text-rose-500 font-bold">*</span>) sebelum melanjutkan.
                                        </p>
                                    </div>
                                </div>

                                <!-- ========================================== -->
                                <!-- STEP 1: A. MOTIVASI & KESIAPAN             -->
                                <!-- ========================================== -->
                                <div v-show="activeStep === 1" class="space-y-6">
                                    <div class="border-b border-gray-100 pb-4 dark:border-slate-800">
                                        <h3 class="text-lg font-black text-gray-900 dark:text-slate-100">
                                            A. Motivasi & Kesiapan
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-slate-400">
                                            Evaluasi dorongan dan kesiapan santri untuk menempuh pendidikan di pondok.
                                        </p>
                                    </div>

                                    <!-- 1. Keinginan Mondok -->
                                    <div class="space-y-2.5">
                                        <label class="block text-sm font-bold text-gray-800 dark:text-slate-200">
                                            1. Keinginan Mondok <span class="text-rose-500 font-bold">*</span>
                                        </label>
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            <label
                                                v-for="opt in ['Sendiri', 'Orang Tua', 'Orang Lain', 'Sendiri & Orang Tua']"
                                                :key="opt"
                                                class="group flex cursor-pointer items-center justify-between rounded-2xl border p-4 transition-all duration-200"
                                                :class="[
                                                    form.wawancara_data.motivasi.keinginan_mondok === opt
                                                        ? 'border-primary bg-primary/5 ring-2 ring-primary/20 dark:border-blue-500/60 dark:bg-primary/20 dark:ring-blue-500/20'
                                                        : 'border-gray-200 bg-white hover:border-slate-300 hover:bg-gray-50/60 dark:border-slate-700 dark:bg-slate-900'
                                                ]"
                                            >
                                                <span class="text-sm font-bold text-gray-800 dark:text-slate-200">{{ opt }}</span>
                                                <Checkbox
                                                    :checked="form.wawancara_data.motivasi.keinginan_mondok === opt"
                                                    @change="form.wawancara_data.motivasi.keinginan_mondok = opt"
                                                    shape="circle"
                                                />
                                            </label>
                                        </div>
                                        <p v-if="errors.keinginan_mondok" class="text-xs font-semibold text-rose-500">
                                            {{ errors.keinginan_mondok }}
                                        </p>
                                    </div>

                                    <!-- 2. Bersedia Mondok Minimal 4 Tahun? -->
                                    <div class="space-y-2.5">
                                        <label class="block text-sm font-bold text-gray-800 dark:text-slate-200">
                                            2. Bersedia Mondok Minimal 4 Tahun? <span class="text-rose-500 font-bold">*</span>
                                        </label>
                                        <div class="grid grid-cols-2 gap-3 sm:w-80">
                                            <label
                                                v-for="opt in ['Ya', 'Tidak']"
                                                :key="opt"
                                                class="group flex cursor-pointer items-center justify-between rounded-2xl border p-3.5 transition-all duration-200"
                                                :class="[
                                                    form.wawancara_data.motivasi.bersedia_mondok_4_tahun === opt
                                                        ? 'border-primary bg-primary/5 ring-2 ring-primary/20 dark:border-blue-500/60 dark:bg-primary/20 dark:ring-blue-500/20'
                                                        : 'border-gray-200 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900'
                                                ]"
                                            >
                                                <span class="text-sm font-bold text-gray-800 dark:text-slate-200">{{ opt }}</span>
                                                <Checkbox
                                                    :checked="form.wawancara_data.motivasi.bersedia_mondok_4_tahun === opt"
                                                    @change="form.wawancara_data.motivasi.bersedia_mondok_4_tahun = opt"
                                                    shape="circle"
                                                />
                                            </label>
                                        </div>
                                        <p v-if="errors.bersedia_mondok_4_tahun" class="text-xs font-semibold text-rose-500">
                                            {{ errors.bersedia_mondok_4_tahun }}
                                        </p>
                                    </div>

                                    <!-- 3. Tidak mengambil Ijazah setelah 1 tahun kelulusan -->
                                    <div class="space-y-2.5">
                                        <label class="block text-sm font-bold text-gray-800 dark:text-slate-200 leading-snug">
                                            3. Tidak mengambil Surat Keterangan Kelulusan/Ijazah dan hal terkait setelah 1 tahun dari tanggal kelulusan: <span class="text-rose-500 font-bold">*</span>
                                        </label>
                                        <div class="grid grid-cols-2 gap-3 sm:w-80">
                                            <label
                                                v-for="opt in ['Ya', 'Tidak']"
                                                :key="opt"
                                                class="group flex cursor-pointer items-center justify-between rounded-2xl border p-3.5 transition-all duration-200"
                                                :class="[
                                                    form.wawancara_data.motivasi.tidak_ambil_ijazah === opt
                                                        ? 'border-primary bg-primary/5 ring-2 ring-primary/20 dark:border-blue-500/60 dark:bg-primary/20 dark:ring-blue-500/20'
                                                        : 'border-gray-200 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900'
                                                ]"
                                            >
                                                <span class="text-sm font-bold text-gray-800 dark:text-slate-200">{{ opt }}</span>
                                                <Checkbox
                                                    :checked="form.wawancara_data.motivasi.tidak_ambil_ijazah === opt"
                                                    @change="form.wawancara_data.motivasi.tidak_ambil_ijazah = opt"
                                                    shape="circle"
                                                />
                                            </label>
                                        </div>
                                        <p v-if="errors.tidak_ambil_ijazah" class="text-xs font-semibold text-rose-500">
                                            {{ errors.tidak_ambil_ijazah }}
                                        </p>
                                    </div>

                                    <!-- 4. Catatan apabila jawaban "Tidak" -->
                                    <div v-if="form.wawancara_data.motivasi.tidak_ambil_ijazah === 'Tidak'">
                                        <TextareaInput
                                            v-model="form.wawancara_data.motivasi.catatan_tidak_ambil_ijazah"
                                            label="Catatan apabila jawaban 'Tidak'"
                                            placeholder="Tuliskan catatan / komitmen wali santri..."
                                            :rows="4"
                                        />
                                    </div>

                                    <!-- 5. Cita-cita -->
                                    <div>
                                        <TextInput
                                            v-model="form.wawancara_data.motivasi.cita_cita"
                                            label="Cita-cita Calon Santri"
                                            placeholder="Contoh: Ulama, Guru, Pengusaha, Dokter, dll."
                                        />
                                    </div>

                                    <!-- 6. Kenalan di Pondok -->
                                    <div class="rounded-3xl border border-gray-200/80 bg-gray-50/60 p-5 dark:border-slate-800 dark:bg-slate-800/40 space-y-4">
                                        <h4 class="text-xs font-black tracking-wider uppercase text-gray-700 dark:text-slate-300">
                                            Kenalan di Pondok (Jika Ada)
                                        </h4>
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <TextInput
                                                v-model="form.wawancara_data.motivasi.kenalan.nama"
                                                label="Nama Kenalan / Santri di Pondok"
                                                placeholder="Nama lengkap kenalan..."
                                            />
                                            <TextInput
                                                v-model="form.wawancara_data.motivasi.kenalan.status"
                                                label="Status / Hubungan"
                                                placeholder="Contoh: Kakak Kandung, Paman, Sepupu, Tetangga"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- ========================================== -->
                                <!-- STEP 2: B. KEBIASAAN SEHARI-HARI           -->
                                <!-- ========================================== -->
                                <div v-show="activeStep === 2" class="space-y-6">
                                    <div class="border-b border-gray-100 pb-4 dark:border-slate-800">
                                        <h3 class="text-lg font-black text-gray-900 dark:text-slate-100">
                                            B. Kebiasaan Sehari-hari
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-slate-400">
                                            Pola tidur, istirahat, kegiatan malam, dan riwayat kesehatan.
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                        <div>
                                            <TextInput
                                                type="time"
                                                v-model="form.wawancara_data.kebiasaan.jam_tidur"
                                                label="Jam Tidur Biasa *"
                                            />
                                            <p v-if="errors.jam_tidur" class="mt-1 text-xs font-semibold text-rose-500">
                                                {{ errors.jam_tidur }}
                                            </p>
                                        </div>
                                        <div>
                                            <TextInput
                                                type="time"
                                                v-model="form.wawancara_data.kebiasaan.jam_bangun"
                                                label="Jam Bangun Pagi *"
                                            />
                                            <p v-if="errors.jam_bangun" class="mt-1 text-xs font-semibold text-rose-500">
                                                {{ errors.jam_bangun }}
                                            </p>
                                        </div>
                                    </div>

                                    <TextareaInput
                                        v-model="form.wawancara_data.kebiasaan.kegiatan_malam"
                                        label="Kegiatan di Atas Jam 22:00 WIB"
                                        placeholder="Contoh: Belajar, menggunakan gawai/HP, bermain, atau sudah tidur..."
                                        :rows="4"
                                    />

                                    <TextareaInput
                                        v-model="form.wawancara_data.kebiasaan.riwayat_penyakit"
                                        label="Riwayat Penyakit / Alergi"
                                        placeholder="Tuliskan riwayat asma, maag, alergi makanan, atau kondisi khusus jika ada..."
                                        :rows="4"
                                    />
                                </div>

                                <!-- ========================================== -->
                                <!-- STEP 3: C. IBADAH & KEAGAMAAN              -->
                                <!-- ========================================== -->
                                <div v-show="activeStep === 3" class="space-y-8">
                                    <div class="border-b border-gray-100 pb-4 dark:border-slate-800">
                                        <h3 class="text-lg font-black text-gray-900 dark:text-slate-100">
                                            C. Ibadah & Keagamaan
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-slate-400">
                                            Pemeriksaan aspek ibadah praktis dan kefasihan bacaan sholat calon santri.
                                        </p>
                                    </div>

                                    <!-- 1. Aspek Ibadah Table -->
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-bold text-gray-800 dark:text-slate-200">
                                                1. Aspek Ibadah <span class="text-rose-500">*</span>
                                            </h4>
                                        </div>

                                        <div class="overflow-x-auto rounded-3xl border border-gray-200/80 shadow-2xs dark:border-slate-800">
                                            <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-slate-800">
                                                <thead class="bg-gray-50 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:bg-slate-800/80 dark:text-slate-400">
                                                    <tr>
                                                        <th class="px-5 py-3.5">Aspek Ibadah</th>
                                                        <th v-for="frek in frekuensiList" :key="frek" class="px-4 py-3.5 text-center">{{ frek }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100 bg-white dark:divide-slate-800 dark:bg-slate-900">
                                                    <tr
                                                        v-for="ibadah in ibadahItems"
                                                        :key="ibadah"
                                                        class="hover:bg-gray-50/60 dark:hover:bg-slate-800/40 transition-colors"
                                                    >
                                                        <td class="px-5 py-3 font-semibold text-gray-800 dark:text-slate-200">
                                                            {{ labelIbadah[ibadah] }} <span class="text-rose-500 font-bold">*</span>
                                                        </td>
                                                        <td v-for="frek in frekuensiList" :key="frek" class="px-4 py-3 text-center">
                                                            <label class="inline-flex cursor-pointer items-center justify-center p-1">
                                                                <Checkbox
                                                                    :checked="form.wawancara_data.ibadah[ibadah] === frek"
                                                                    @change="form.wawancara_data.ibadah[ibadah] = frek"
                                                                    shape="circle"
                                                                />
                                                            </label>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div v-if="errors.sholat_5_waktu || errors.sholat_berjamaah || errors.shodaqoh || errors.membantu" class="rounded-xl bg-rose-50 p-3 text-xs font-semibold text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                                            Mohon lengkapi pilihan untuk seluruh 4 aspek ibadah di atas.
                                        </div>

                                        <TextareaInput
                                            v-model="form.wawancara_data.ibadah.catatan"
                                            label="Catatan Aspek Ibadah"
                                            placeholder="Catatan tambahan aspek ibadah jika ada..."
                                            :rows="4"
                                        />
                                    </div>

                                    <!-- 2. Kemampuan Bacaan Sholat -->
                                    <div class="space-y-3 pt-4 border-t border-gray-100 dark:border-slate-800">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-bold text-gray-800 dark:text-slate-200">
                                                2. Kemampuan Bacaan Sholat (16 Poin)
                                            </h4>
                                            <div class="flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    @click="setAllBacaan('Baik')"
                                                    class="rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300 cursor-pointer"
                                                >
                                                    Set Semua Baik
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="setAllBacaan('Cukup')"
                                                    class="rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 hover:bg-amber-100 dark:bg-amber-950/60 dark:text-amber-300 cursor-pointer"
                                                >
                                                    Set Semua Cukup
                                                </button>
                                            </div>
                                        </div>

                                        <div class="overflow-x-auto rounded-3xl border border-gray-200/80 shadow-2xs dark:border-slate-800">
                                            <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-slate-800">
                                                <thead class="bg-gray-50 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:bg-slate-800/80 dark:text-slate-400">
                                                    <tr>
                                                        <th class="px-5 py-3.5">Kemampuan Bacaan</th>
                                                        <th v-for="skor in skorBacaanList" :key="skor" class="px-4 py-3.5 text-center">{{ skor }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100 bg-white dark:divide-slate-800 dark:bg-slate-900">
                                                    <tr
                                                        v-for="(bacaan, index) in bacaanList"
                                                        :key="index"
                                                        class="hover:bg-gray-50/60 dark:hover:bg-slate-800/40 transition-colors"
                                                    >
                                                        <td class="px-5 py-2.5 font-semibold text-gray-800 dark:text-slate-200">
                                                            {{ bacaan }}
                                                        </td>
                                                        <td v-for="skor in skorBacaanList" :key="skor" class="px-4 py-2.5 text-center">
                                                            <label class="inline-flex cursor-pointer items-center justify-center p-1">
                                                                <Checkbox
                                                                    :checked="form.wawancara_data.ibadah.bacaan_sholat[bacaan] === skor"
                                                                    @change="form.wawancara_data.ibadah.bacaan_sholat[bacaan] = skor"
                                                                    shape="circle"
                                                                />
                                                            </label>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <TextareaInput
                                            v-model="form.wawancara_data.ibadah.catatan_bacaan"
                                            label="Catatan Kemampuan Bacaan Sholat"
                                            placeholder="Catatan pelafalan makharijul huruf atau tajwid..."
                                            :rows="4"
                                        />
                                    </div>
                                </div>

                                <!-- ========================================== -->
                                <!-- STEP 4: D. PELANGGARAN & PERILAKU          -->
                                <!-- ========================================== -->
                                <div v-show="activeStep === 4" class="space-y-6">
                                    <div class="border-b border-gray-100 pb-4 dark:border-slate-800">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h3 class="text-lg font-black text-gray-900 dark:text-slate-100">
                                                    D. Pelanggaran & Perilaku
                                                </h3>
                                                <p class="text-xs text-gray-500 dark:text-slate-400">
                                                    Deteksi riwayat pelanggaran tata tertib dan perilaku calon santri.
                                                </p>
                                            </div>
                                            <button
                                                type="button"
                                                @click="setAllPelanggaran('Tidak Pernah')"
                                                class="rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300 cursor-pointer"
                                            >
                                                Set Semua Tidak Pernah
                                            </button>
                                        </div>
                                    </div>

                                    <div class="overflow-x-auto rounded-3xl border border-gray-200/80 shadow-2xs dark:border-slate-800">
                                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-slate-800">
                                            <thead class="bg-gray-50 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:bg-slate-800/80 dark:text-slate-400">
                                                <tr>
                                                    <th class="px-5 py-3.5">Item Pelanggaran</th>
                                                    <th v-for="frek in frekuensiList" :key="frek" class="px-4 py-3.5 text-center">{{ frek }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 bg-white dark:divide-slate-800 dark:bg-slate-900">
                                                <tr
                                                    v-for="(pel, index) in pelanggaranItems"
                                                    :key="index"
                                                    class="hover:bg-gray-50/60 dark:hover:bg-slate-800/40 transition-colors"
                                                >
                                                    <td class="px-5 py-2.5 font-semibold text-gray-800 dark:text-slate-200">
                                                        {{ pel }}
                                                    </td>
                                                    <td v-for="frek in frekuensiList" :key="frek" class="px-4 py-2.5 text-center">
                                                        <label class="inline-flex cursor-pointer items-center justify-center p-1">
                                                            <Checkbox
                                                                :checked="form.wawancara_data.pelanggaran.pernah_dilakukan[index] === frek"
                                                                @change="form.wawancara_data.pelanggaran.pernah_dilakukan[index] = frek"
                                                                shape="circle"
                                                            />
                                                        </label>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <TextareaInput
                                        v-model="form.wawancara_data.pelanggaran.catatan"
                                        label="Catatan Pelanggaran & Perilaku"
                                        placeholder="Catatan tambahan seputar perilaku calon santri..."
                                        :rows="4"
                                    />
                                </div>

                                <!-- ========================================== -->
                                <!-- STEP 5: E. PRESTASI AKADEMIK               -->
                                <!-- ========================================== -->
                                <div v-show="activeStep === 5" class="space-y-6">
                                    <div class="border-b border-gray-100 pb-4 dark:border-slate-800">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h3 class="text-lg font-black text-gray-900 dark:text-slate-100">
                                                    E. Prestasi Akademik
                                                </h3>
                                                <p class="text-xs text-gray-500 dark:text-slate-400">
                                                    Riwayat prestasi, kejuaraan, atau catatan nilai di sekolah/pondok asal.
                                                </p>
                                            </div>
                                            <button
                                                type="button"
                                                @click="setAllPrestasi('Tidak Pernah')"
                                                class="rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 cursor-pointer"
                                            >
                                                Reset Pilihan
                                            </button>
                                        </div>
                                    </div>

                                    <div class="overflow-x-auto rounded-3xl border border-gray-200/80 shadow-2xs dark:border-slate-800">
                                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-slate-800">
                                            <thead class="bg-gray-50 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:bg-slate-800/80 dark:text-slate-400">
                                                <tr>
                                                    <th class="px-5 py-3.5">Prestasi</th>
                                                    <th v-for="frek in frekuensiList" :key="frek" class="px-4 py-3.5 text-center">{{ frek }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 bg-white dark:divide-slate-800 dark:bg-slate-900">
                                                <tr
                                                    v-for="(pres, index) in prestasiItems"
                                                    :key="index"
                                                    class="hover:bg-gray-50/60 dark:hover:bg-slate-800/40 transition-colors"
                                                >
                                                    <td class="px-5 py-2.5 font-semibold text-gray-800 dark:text-slate-200">
                                                        {{ pres }}
                                                    </td>
                                                    <td v-for="frek in frekuensiList" :key="frek" class="px-4 py-2.5 text-center">
                                                        <label class="inline-flex cursor-pointer items-center justify-center p-1">
                                                            <Checkbox
                                                                :checked="form.wawancara_data.prestasi.items[index] === frek"
                                                                @change="form.wawancara_data.prestasi.items[index] = frek"
                                                                shape="circle"
                                                            />
                                                        </label>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                        <TextareaInput
                                            v-model="form.wawancara_data.prestasi.catatan_pondok"
                                            label="Catatan Pondok Asal"
                                            placeholder="Catatan dari pondok sebelumnya jika ada..."
                                            :rows="4"
                                        />
                                        <TextareaInput
                                            v-model="form.wawancara_data.prestasi.catatan_sekolah"
                                            label="Catatan Sekolah Asal"
                                            placeholder="Catatan dari sekolah umum sebelumnya..."
                                            :rows="4"
                                        />
                                    </div>
                                </div>

                                <!-- ========================================== -->
                                <!-- STEP 6: F. PENGUJI & PENENTUAN KELAS       -->
                                <!-- ========================================== -->
                                <div v-show="activeStep === 6" class="space-y-6">
                                    <div class="border-b border-gray-100 pb-4 dark:border-slate-800">
                                        <h3 class="text-lg font-black text-gray-900 dark:text-slate-100">
                                            F. Penguji & Penentuan Kelas Pondok
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-slate-400">
                                            Keputusan final hasil wawancara, penempatan kelas pondok, dan evaluasi penguji.
                                        </p>
                                    </div>

                                    <!-- 1. Keputusan Hasil Wawancara (A, C, D) -->
                                    <div class="space-y-3">
                                        <label class="block text-sm font-bold text-gray-800 dark:text-slate-200">
                                            Keputusan Hasil Wawancara <span class="text-rose-500 font-bold">*</span>
                                        </label>
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                            <div
                                                v-for="card in hasilWawancaraCards"
                                                :key="card.value"
                                                @click="form.hasil_wawancara = card.value"
                                                class="group relative flex cursor-pointer flex-col justify-between rounded-3xl border p-5 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                                                :class="[
                                                    form.hasil_wawancara === card.value
                                                        ? card.activeClass
                                                        : 'border-gray-200 bg-white hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900'
                                                ]"
                                            >
                                                <div>
                                                    <div class="flex items-center justify-between gap-2">
                                                        <span :class="['rounded-full px-3 py-1 text-xs font-black tracking-wider uppercase', card.badgeClass]">
                                                            {{ card.label }}
                                                        </span>
                                                        <Checkbox
                                                            :checked="form.hasil_wawancara === card.value"
                                                            shape="circle"
                                                            class="pointer-events-none"
                                                        />
                                                    </div>
                                                    <h4 class="mt-3 text-sm font-bold text-gray-900 dark:text-slate-100">
                                                        {{ card.subLabel }}
                                                    </h4>
                                                    <p class="mt-1 text-xs text-gray-500 leading-relaxed dark:text-slate-400">
                                                        {{ card.description }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <p v-if="errors.hasil_wawancara" class="text-xs font-semibold text-rose-500">
                                            {{ errors.hasil_wawancara }}
                                        </p>
                                    </div>

                                    <!-- 2. Penentuan Kelas Pondok (Categorized Groups - Optional) -->
                                    <div class="space-y-3.5 pt-2">
                                        <div class="flex items-center justify-between">
                                            <label class="block text-sm font-bold text-gray-800 dark:text-slate-200">
                                                Penentuan Kelas Pondok <span class="text-xs font-normal text-gray-400 dark:text-slate-500">(Opsional / Boleh Dikosongkan)</span>
                                            </label>
                                            <span v-if="form.rekomendasi_kelas_pondok" class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary dark:bg-blue-950/60 dark:text-blue-300">
                                                Terpilih: {{ form.rekomendasi_kelas_pondok }}
                                            </span>
                                        </div>

                                        <!-- 1. Group Kelas I'dadi (1 & 2) -->
                                        <div class="rounded-2xl border border-amber-200/80 bg-amber-50/40 p-4 dark:border-amber-900/40 dark:bg-amber-950/20 space-y-2.5">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="flex h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                                                    <h5 class="text-xs font-black text-amber-900 uppercase tracking-wider dark:text-amber-300">
                                                        Pilihan Kelas I'dadi
                                                    </h5>
                                                </div>
                                                <span class="text-[11px] font-bold text-amber-700 dark:text-amber-400">Kelas Persiapan (Matrikulasi)</span>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <button
                                                    v-for="opt in ['I\'dadi 1', 'I\'dadi 2']"
                                                    :key="opt"
                                                    type="button"
                                                    @click="selectKelasPondok(opt)"
                                                    class="flex items-center justify-between rounded-xl border p-3.5 text-left transition-all duration-150"
                                                    :class="[
                                                        isKelasSelected(opt)
                                                            ? 'border-amber-500 bg-amber-100 text-amber-950 shadow-xs ring-2 ring-amber-500/30 dark:border-amber-400 dark:bg-amber-950/80 dark:text-amber-200 cursor-pointer'
                                                            : 'border-white/80 bg-white text-slate-700 shadow-2xs hover:border-amber-300 hover:bg-amber-50/50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-amber-700 cursor-pointer'
                                                    ]"
                                                >
                                                    <div>
                                                        <span class="block text-sm font-black">{{ opt }}</span>
                                                        <span class="block text-xs text-gray-500 dark:text-slate-400">Tingkat I'dadi (Persiapan)</span>
                                                    </div>
                                                    <div
                                                        class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full border"
                                                        :class="isKelasSelected(opt) ? 'border-amber-600 bg-amber-600 text-white dark:border-amber-400 dark:bg-amber-400 dark:text-slate-950' : 'border-gray-300 bg-white dark:border-slate-700 dark:bg-slate-800'"
                                                    >
                                                        <svg v-if="isKelasSelected(opt)" class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 12 12">
                                                            <circle cx="6" cy="6" r="3" />
                                                        </svg>
                                                    </div>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- 2. Group Kelas Ibtidaiyah (1, 2, 3, 4) -->
                                        <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/40 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/20 space-y-2.5">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                                    <h5 class="text-xs font-black text-emerald-900 uppercase tracking-wider dark:text-emerald-300">
                                                        Pilihan Kelas Ibtidaiyah
                                                    </h5>
                                                </div>
                                                <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400">Tingkat Dasar (1 - 4)</span>
                                            </div>
                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                                <button
                                                    v-for="opt in ['Ibtidaiyah 1', 'Ibtidaiyah 2', 'Ibtidaiyah 3', 'Ibtidaiyah 4']"
                                                    :key="opt"
                                                    type="button"
                                                    @click="selectKelasPondok(opt)"
                                                    class="flex items-center justify-between rounded-xl border p-3.5 text-left transition-all duration-150"
                                                    :class="[
                                                        isKelasSelected(opt)
                                                            ? 'border-emerald-500 bg-emerald-100 text-emerald-950 shadow-xs ring-2 ring-emerald-500/30 dark:border-emerald-400 dark:bg-emerald-950/80 dark:text-emerald-200 cursor-pointer'
                                                            : 'border-white/80 bg-white text-slate-700 shadow-2xs hover:border-emerald-300 hover:bg-emerald-50/50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-emerald-700 cursor-pointer'
                                                    ]"
                                                >
                                                    <div>
                                                        <span class="block text-sm font-black">{{ opt }}</span>
                                                        <span class="block text-xs text-gray-500 dark:text-slate-400">Tingkat Dasar</span>
                                                    </div>
                                                    <div
                                                        class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full border"
                                                        :class="isKelasSelected(opt) ? 'border-emerald-600 bg-emerald-600 text-white dark:border-emerald-400 dark:bg-emerald-400 dark:text-slate-950' : 'border-gray-300 bg-white dark:border-slate-700 dark:bg-slate-800'"
                                                    >
                                                        <svg v-if="isKelasSelected(opt)" class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 12 12">
                                                            <circle cx="6" cy="6" r="3" />
                                                        </svg>
                                                    </div>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- 3. Group Kelas Tsanawiyah (1, 2, 3) -->
                                        <div class="rounded-2xl border border-sky-200/80 bg-sky-50/40 p-4 dark:border-sky-900/40 dark:bg-sky-950/20 space-y-2.5">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="flex h-2.5 w-2.5 rounded-full bg-sky-500"></span>
                                                    <h5 class="text-xs font-black text-sky-900 uppercase tracking-wider dark:text-sky-300">
                                                        Pilihan Kelas Tsanawiyah
                                                    </h5>
                                                </div>
                                                <span class="text-[11px] font-bold text-sky-700 dark:text-sky-400">Tingkat Menengah (1 - 3)</span>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                                <button
                                                    v-for="opt in ['Tsanawiyah 1', 'Tsanawiyah 2', 'Tsanawiyah 3']"
                                                    :key="opt"
                                                    type="button"
                                                    @click="selectKelasPondok(opt)"
                                                    class="flex items-center justify-between rounded-xl border p-3.5 text-left transition-all duration-150"
                                                    :class="[
                                                        isKelasSelected(opt)
                                                            ? 'border-sky-500 bg-sky-100 text-sky-950 shadow-xs ring-2 ring-sky-500/30 dark:border-sky-400 dark:bg-sky-950/80 dark:text-sky-200 cursor-pointer'
                                                            : 'border-white/80 bg-white text-slate-700 shadow-2xs hover:border-sky-300 hover:bg-sky-50/50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-sky-700 cursor-pointer'
                                                    ]"
                                                >
                                                    <div>
                                                        <span class="block text-sm font-black">{{ opt }}</span>
                                                        <span class="block text-xs text-gray-500 dark:text-slate-400">Tingkat Menengah</span>
                                                    </div>
                                                    <div
                                                        class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full border"
                                                        :class="isKelasSelected(opt) ? 'border-sky-600 bg-sky-600 text-white dark:border-sky-400 dark:bg-sky-400 dark:text-slate-950' : 'border-gray-300 bg-white dark:border-slate-700 dark:bg-slate-800'"
                                                    >
                                                        <svg v-if="isKelasSelected(opt)" class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 12 12">
                                                            <circle cx="6" cy="6" r="3" />
                                                        </svg>
                                                    </div>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 3. Catatan Hasil Wawancara -->
                                    <div class="space-y-2 pt-2">
                                        <TextareaInput
                                            v-model="form.catatan_final"
                                            label="Catatan Hasil Wawancara (Evaluasi Penguji)"
                                            placeholder="Tuliskan evaluasi komprehensif, rekomendasi pembinaan, atau catatan khusus dari pewawancara..."
                                            :rows="5"
                                        />
                                    </div>
                                </div>

                                <!-- FOOTER NAVIGATION BUTTONS -->
                                <div class="mt-8 flex items-center justify-between border-t border-gray-100 pt-6 dark:border-slate-800">
                                    <div>
                                        <SecondaryButton
                                            v-if="activeStep > 1"
                                            type="button"
                                            @click="prevStep"
                                            :disabled="form.processing"
                                        >
                                            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                            </svg>
                                            Langkah Sebelumnya
                                        </SecondaryButton>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <PrimaryButton type="submit" :disabled="form.processing">
                                            <svg v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span v-if="isGroupLocked">{{ activeStep === steps.length ? 'Kembali ke Kelompok' : 'Langkah Berikutnya' }}</span>
                                            <span v-else>{{ activeStep === steps.length ? 'Simpan & Selesai' : 'Simpan & Lanjutkan' }}</span>
                                            <svg v-if="activeStep < steps.length && !form.processing" class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </PrimaryButton>
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
