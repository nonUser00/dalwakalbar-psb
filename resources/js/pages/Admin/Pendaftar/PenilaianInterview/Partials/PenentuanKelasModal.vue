<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import TextareaInput from '@/Components/Form/TextareaInput.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { store_penentuan_kelas } from '@/routes/admin/pendaftar/penilaian_interview';

interface Props {
    show: boolean;
    pendaftar: any | null;
    kelompokUjianId: string;
}

const props = defineProps<Props>();
const emit = defineEmits(['close', 'success']);

const imageErrorMap = ref<Record<string, boolean>>({});

const form = useForm({
    pendaftar_id: '',
    kelompok_ujian_id: '',
    rekomendasi_kelas_pondok: '',
    catatan_final: '',
});

const isKelasSelected = (val: string) => {
    if (!form.rekomendasi_kelas_pondok) {
        return false;
    }
    const current = form.rekomendasi_kelas_pondok.toLowerCase().replace('kelas ', '').trim();
    const target = val.toLowerCase().replace('kelas ', '').trim();

    return current === target;
};

const selectKelas = (value: string) => {
    if (!isScoresComplete.value || isTidakLulus.value) {
        return;
    }

    if (isKelasSelected(value)) {
        form.rekomendasi_kelas_pondok = '';
    } else {
        form.rekomendasi_kelas_pondok = value;
    }
};

const hasilUjian = computed(() => {
    return props.pendaftar?.hasil_ujian || props.pendaftar?.hasilUjian || null;
});

// Check assessment completeness
const hasWawancara = computed(() => {
    const val = hasilUjian.value?.hasil_wawancara;

    return Boolean(val && ['A', 'C', 'D'].includes(val));
});

const hasBaca = computed(() => {
    const val = Number(hasilUjian.value?.nilai_baca_kitab || 0);

    if (val > 0) {
        return true;
    }

    return (props.pendaftar?.penilaians || []).some((p: any) => {
        const cat = p.aspek?.kategori?.nama_kategori?.toLowerCase() || '';

        return cat.includes('baca') && Number(p.nilai) > 0;
    });
});

const hasMenulis = computed(() => {
    const val = Number(hasilUjian.value?.nilai_menulis || 0);

    if (val > 0) {
        return true;
    }

    return (props.pendaftar?.penilaians || []).some((p: any) => {
        const cat = p.aspek?.kategori?.nama_kategori?.toLowerCase() || '';

        return (cat.includes('tulis') || cat.includes('menulis')) && Number(p.nilai) > 0;
    });
});

const hasHafalan = computed(() => {
    const val = Number(hasilUjian.value?.nilai_hafalan || 0);

    if (val > 0) {
        return true;
    }

    return (props.pendaftar?.penilaians || []).some((p: any) => {
        const cat = p.aspek?.kategori?.nama_kategori?.toLowerCase() || '';

        return cat.includes('hafal') && Number(p.nilai) > 0;
    });
});

const isScoresComplete = computed(() => {
    return hasWawancara.value && hasBaca.value && hasMenulis.value && hasHafalan.value;
});

const isTidakLulus = computed(() => {
    const s = hasilUjian.value?.status_kelulusan?.value || hasilUjian.value?.status_kelulusan || '';
    return typeof s === 'string' && s.toLowerCase() === 'tidak_lulus';
});

const missingAssessments = computed(() => {
    const missing: string[] = [];

    if (!hasWawancara.value) {
        missing.push('Wawancara Calon Santri');
    }

    if (!hasBaca.value) {
        missing.push('Tes Membaca');
    }

    if (!hasMenulis.value) {
        missing.push('Tes Menulis');
    }

    if (!hasHafalan.value) {
        missing.push('Tes Hafalan');
    }

    return missing;
});

const getPendaftarPhoto = (pendaftar: any): string | null => {
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
        .split(' ')
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

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen && props.pendaftar) {
            form.clearErrors();
            form.pendaftar_id = props.pendaftar.id;
            form.kelompok_ujian_id = props.kelompokUjianId;
            form.rekomendasi_kelas_pondok = hasilUjian.value?.rekomendasi_kelas_pondok || '';
            form.catatan_final = hasilUjian.value?.catatan_final || '';
        } else {
            form.reset();
            form.clearErrors();
        }
    }
);



const submit = () => {
    if (!isScoresComplete.value || isTidakLulus.value) {
        return;
    }

    if (!form.rekomendasi_kelas_pondok) {
        form.setError('rekomendasi_kelas_pondok', 'Silakan pilih salah satu kelas pondok.');

        return;
    }

    form.post(store_penentuan_kelas.url(), {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            emit('close');
        },
    });
};
</script>

<template>
    <Modal
        :show="show"
        maxWidth="2xl"
        title="Penentuan Kelas Pondok"
        description="Penetapan kelas kepesantrenan calon santri berdasarkan hasil evaluasi ujian."
        @close="emit('close')"
    >
        <template #icon>
            <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-primary/20 bg-primary/10 text-primary dark:border-blue-900/40 dark:bg-blue-950/50 dark:text-blue-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
        </template>

        <div class="space-y-4">
            <!-- TOP CANDIDATE PROFILE HERO CARD -->
            <div
                v-if="pendaftar"
                class="relative overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-br from-white via-slate-50/80 to-slate-100/50 p-4 sm:p-5 shadow-xs dark:border-slate-800 dark:from-slate-900 dark:via-slate-900/90 dark:to-slate-950"
            >
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <!-- Left: Avatar + Details -->
                    <div class="flex items-center gap-3.5 sm:gap-4 min-w-0">
                        <!-- Avatar / Photo Frame -->
                        <div
                            class="relative h-16 w-16 sm:h-18 sm:w-18 shrink-0 overflow-hidden rounded-2xl border-2 border-white bg-slate-100 shadow-md ring-2 ring-slate-100 dark:border-slate-800 dark:bg-slate-800 dark:ring-slate-800/60"
                        >
                            <img
                                v-if="getPendaftarPhoto(pendaftar) && !imageErrorMap[pendaftar.id]"
                                :src="getPendaftarPhoto(pendaftar)!"
                                :alt="pendaftar.nama"
                                @error="imageErrorMap[pendaftar.id] = true"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full flex-col items-center justify-center font-black text-white shadow-inner"
                                :class="isFemale ? 'bg-gradient-to-br from-rose-500 via-pink-600 to-rose-700' : 'bg-gradient-to-br from-blue-600 via-indigo-600 to-blue-800'"
                            >
                                <span class="text-base sm:text-lg tracking-wider">{{ getInitials(pendaftar.nama) }}</span>
                            </div>
                        </div>

                        <!-- Candidate Info -->
                        <div class="min-w-0 flex-1 space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4
                                    class="text-base font-black text-slate-900 leading-tight truncate dark:text-slate-100"
                                    :title="pendaftar.nama"
                                >
                                    {{ pendaftar.nama }}
                                </h4>

                                <!-- Gender Badge (Pure SVG) -->
                                <span
                                    v-if="isMale"
                                    class="inline-flex items-center gap-1 rounded-full bg-blue-50 border border-blue-200/70 px-2 py-0.5 text-[9px] font-black text-blue-700 uppercase dark:bg-blue-950/60 dark:border-blue-800/60 dark:text-blue-300"
                                >
                                    <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 8v-4h-4M16 4l-5 5" />
                                        <circle cx="9" cy="13" r="5" stroke-width="2.5" />
                                    </svg>
                                    Laki-Laki
                                </span>
                                <span
                                    v-else-if="isFemale"
                                    class="inline-flex items-center gap-1 rounded-full bg-pink-50 border border-pink-200/70 px-2 py-0.5 text-[9px] font-black text-pink-700 uppercase dark:bg-pink-950/60 dark:border-pink-800/60 dark:text-pink-300"
                                >
                                    <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v6m-3-3h6" />
                                        <circle cx="12" cy="9" r="6" stroke-width="2.5" />
                                    </svg>
                                    Perempuan
                                </span>

                                <span
                                    v-if="pendaftar.tipe_pendaftaran || pendaftar.education_data?.tipe_pendaftaran"
                                    class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-bold text-slate-600 border border-slate-200/70 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700"
                                >
                                    {{ pendaftar.tipe_pendaftaran || pendaftar.education_data?.tipe_pendaftaran }}
                                </span>
                            </div>

                            <!-- Detailed Metadata Line -->
                            <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                                <div>
                                    <span class="text-slate-400">No. Reg:</span>
                                    <span class="ml-1 font-mono font-bold text-primary dark:text-blue-400">{{ pendaftar.nomor_pendaftaran || '-' }}</span>
                                </div>
                                <span class="text-slate-300 dark:text-slate-700">•</span>
                                <div>
                                    <span class="text-slate-400">NIK:</span>
                                    <span class="ml-1 font-mono font-bold text-slate-800 dark:text-slate-200">{{ pendaftar.nik || pendaftar.personal_data?.nik || '-' }}</span>
                                </div>
                                <span class="text-slate-300 dark:text-slate-700">•</span>
                                <div>
                                    <span class="text-slate-400">Cabang:</span>
                                    <span class="ml-1 font-semibold text-slate-800 dark:text-slate-200">{{ namaCabang }}</span>
                                </div>
                            </div>

                            <!-- Jenjang & Asal Sekolah Badges -->
                            <div class="flex flex-wrap items-center gap-2 pt-0.5">
                                <!-- Jenjang Badge with Official Logo -->
                                <span
                                    v-if="pendaftar.jenjang?.name || pendaftar.education_data?.jenjang"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-primary/25 bg-primary/5 px-2.5 py-0.5 text-[11px] font-bold text-primary dark:border-blue-500/30 dark:bg-blue-950/50 dark:text-blue-300 shadow-2xs"
                                >
                                    <img
                                        :src="getJenjangLogo(pendaftar.jenjang || pendaftar.education_data?.jenjang)"
                                        :alt="pendaftar.jenjang?.name"
                                        class="h-3.5 w-3.5 object-contain"
                                    />
                                    <span>{{ pendaftar.jenjang?.name || pendaftar.education_data?.jenjang }}</span>
                                </span>

                                <span
                                    v-if="asalSekolah"
                                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200/80 bg-white px-2 py-0.5 text-[10px] font-medium text-slate-600 truncate max-w-[220px] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                    :title="asalSekolah"
                                >
                                    <svg class="h-3 w-3 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span class="truncate">{{ asalSekolah }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Current Placement Status Pill -->
                    <div class="flex sm:flex-col items-start sm:items-end justify-between sm:justify-center gap-1.5 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-800/80">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Kelas Terpilih
                        </span>
                        <span
                            v-if="form.rekomendasi_kelas_pondok || hasilUjian?.rekomendasi_kelas_pondok"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 border border-emerald-200/90 px-3 py-1 text-xs font-black text-emerald-800 dark:bg-emerald-950/60 dark:border-emerald-800/70 dark:text-emerald-300 shadow-2xs"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            {{ form.rekomendasi_kelas_pondok || hasilUjian?.rekomendasi_kelas_pondok }}
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center rounded-xl bg-slate-100 border border-slate-200/80 px-2.5 py-1 text-[11px] font-semibold text-slate-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400"
                        >
                            Belum Ditentukan
                        </span>
                    </div>
                </div>
            </div>



            <!-- Score Summary Grid -->
            <div class="space-y-1.5">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                    Status Kelengkapan Nilai Calon Santri
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <!-- 1. Wawancara -->
                    <div
                        class="flex flex-col justify-between rounded-xl border p-2.5 transition-colors"
                        :class="hasWawancara
                            ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900/50 dark:bg-emerald-950/20'
                            : 'border-amber-200 bg-amber-50/50 dark:border-amber-900/50 dark:bg-amber-950/20'"
                    >
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 block">Wawancara</span>
                        <div class="mt-1.5 flex items-center justify-between gap-1">
                            <span v-if="hasWawancara" class="text-xs font-black text-emerald-700 dark:text-emerald-300">
                                Hasil: {{ hasilUjian?.hasil_wawancara }}
                            </span>
                            <span v-else class="text-[11px] font-bold text-amber-700 dark:text-amber-400">
                                Belum Diisi
                            </span>
                            <span
                                class="h-2 w-2 rounded-full"
                                :class="hasWawancara ? 'bg-emerald-500' : 'bg-amber-500'"
                            ></span>
                        </div>
                    </div>
                    <!-- 2. Membaca -->
                    <div
                        class="flex flex-col justify-between rounded-xl border p-2.5 transition-colors"
                        :class="hasBaca
                            ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900/50 dark:bg-emerald-950/20'
                            : 'border-amber-200 bg-amber-50/50 dark:border-amber-900/50 dark:bg-amber-950/20'"
                    >
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 block">Tes Membaca</span>
                        <div class="mt-1.5 flex items-center justify-between gap-1">
                            <span v-if="hasBaca" class="text-xs font-black text-emerald-700 dark:text-emerald-300">
                                Skor: {{ Number(hasilUjian?.nilai_baca_kitab || 0).toFixed(0) }}
                            </span>
                            <span v-else class="text-[11px] font-bold text-amber-700 dark:text-amber-400">
                                Belum Dinilai
                            </span>
                            <span
                                class="h-2 w-2 rounded-full"
                                :class="hasBaca ? 'bg-emerald-500' : 'bg-amber-500'"
                            ></span>
                        </div>
                    </div>

                    <!-- 3. Menulis -->
                    <div
                        class="flex flex-col justify-between rounded-xl border p-2.5 transition-colors"
                        :class="hasMenulis
                            ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900/50 dark:bg-emerald-950/20'
                            : 'border-amber-200 bg-amber-50/50 dark:border-amber-900/50 dark:bg-amber-950/20'"
                    >
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 block">Tes Menulis</span>
                        <div class="mt-1.5 flex items-center justify-between gap-1">
                            <span v-if="hasMenulis" class="text-xs font-black text-emerald-700 dark:text-emerald-300">
                                Skor: {{ Number(hasilUjian?.nilai_menulis || 0).toFixed(0) }}
                            </span>
                            <span v-else class="text-[11px] font-bold text-amber-700 dark:text-amber-400">
                                Belum Dinilai
                            </span>
                            <span
                                class="h-2 w-2 rounded-full"
                                :class="hasMenulis ? 'bg-emerald-500' : 'bg-amber-500'"
                            ></span>
                        </div>
                    </div>

                    <!-- 4. Hafalan -->
                    <div
                        class="flex flex-col justify-between rounded-xl border p-2.5 transition-colors"
                        :class="hasHafalan
                            ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900/50 dark:bg-emerald-950/20'
                            : 'border-amber-200 bg-amber-50/50 dark:border-amber-900/50 dark:bg-amber-950/20'"
                    >
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 block">Tes Hafalan</span>
                        <div class="mt-1.5 flex items-center justify-between gap-1">
                            <span v-if="hasHafalan" class="text-xs font-black text-emerald-700 dark:text-emerald-300">
                                Skor: {{ Number(hasilUjian?.nilai_hafalan || 0).toFixed(0) }}
                            </span>
                            <span v-else class="text-[11px] font-bold text-amber-700 dark:text-amber-400">
                                Belum Dinilai
                            </span>
                            <span
                                class="h-2 w-2 rounded-full"
                                :class="hasHafalan ? 'bg-emerald-500' : 'bg-amber-500'"
                            ></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Notice: Tidak Lulus -->
            <div
                v-if="isTidakLulus"
                class="flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50/90 p-3 dark:border-rose-900/40 dark:bg-rose-950/30"
            >
                <svg class="h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-[11px]">
                    <p class="font-bold text-rose-800 dark:text-rose-300">
                        Calon Santri Tidak Lulus
                    </p>
                    <p class="mt-0.5 text-rose-700 leading-relaxed dark:text-rose-400">
                        Santri ini telah diputuskan berstatus <strong>Tidak Lulus</strong>. Penentuan kelas pondok dinonaktifkan.
                    </p>
                </div>
            </div>

            <!-- Status Notice: Nilai Belum Lengkap -->
            <div
                v-else-if="!isScoresComplete"
                class="flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50/80 p-3 dark:border-amber-900/40 dark:bg-amber-950/30"
            >
                <svg class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="text-[11px]">
                    <p class="font-bold text-amber-800 dark:text-amber-300">
                        Nilai Belum Lengkap!
                    </p>
                    <p class="mt-0.5 text-amber-700 leading-relaxed dark:text-amber-400">
                        Penentuan kelas pondok hanya dapat dilakukan jika semua nilai ujian sudah lengkap. Komponen yang belum dinilai:
                        <span class="font-bold underline">{{ missingAssessments.join(', ') }}</span>.
                    </p>
                </div>
            </div>

            <div
                v-else
                class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50/80 p-3 dark:border-emerald-900/40 dark:bg-emerald-950/30"
            >
                <svg class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-[11px] font-bold text-emerald-800 dark:text-emerald-300">
                    Seluruh nilai ujian telah lengkap. Silakan tentukan kelas pondok untuk calon santri ini.
                </p>
            </div>

            <!-- Class Selection Groups -->
            <div class="space-y-3 pt-1">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-black uppercase tracking-wider text-gray-800 dark:text-slate-200">
                        Pilihan Kelas Pondok <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <span v-if="form.rekomendasi_kelas_pondok" class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary dark:bg-blue-950/60 dark:text-blue-300">
                        Terpilih: {{ form.rekomendasi_kelas_pondok }}
                    </span>
                </div>

                <!-- 1. Group Kelas I'dadi (1 & 2) -->
                <div class="rounded-2xl border border-amber-200/80 bg-amber-50/40 p-3 dark:border-amber-900/40 dark:bg-amber-950/20 space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="flex h-2 w-2 rounded-full bg-amber-500"></span>
                            <h5 class="text-xs font-black text-amber-900 uppercase tracking-wider dark:text-amber-300">
                                Pilihan Kelas I'dadi
                            </h5>
                        </div>
                        <span class="text-[10px] font-bold text-amber-700 dark:text-amber-400">Kelas Persiapan</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            v-for="opt in ['I\'dadi 1', 'I\'dadi 2']"
                            :key="opt"
                            type="button"
                            :disabled="!isScoresComplete"
                            @click="selectKelas(opt)"
                            class="flex items-center justify-between rounded-xl border p-2.5 text-left transition-all duration-150"
                            :class="[
                                !isScoresComplete
                                    ? 'cursor-not-allowed opacity-50 border-gray-200 bg-gray-50 dark:border-slate-800 dark:bg-slate-900'
                                    : isKelasSelected(opt)
                                        ? 'border-amber-500 bg-amber-100 text-amber-950 shadow-xs ring-2 ring-amber-500/30 dark:border-amber-400 dark:bg-amber-950/80 dark:text-amber-200 cursor-pointer'
                                        : 'border-white/80 bg-white text-slate-700 shadow-2xs hover:border-amber-300 hover:bg-amber-50/50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-amber-700 cursor-pointer'
                            ]"
                        >
                            <div>
                                <span class="block text-xs font-black">{{ opt }}</span>
                                <span class="block text-[10px] text-gray-500 dark:text-slate-400">Tingkat I'dadi</span>
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
                <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/40 p-3 dark:border-emerald-900/40 dark:bg-emerald-950/20 space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            <h5 class="text-xs font-black text-emerald-900 uppercase tracking-wider dark:text-emerald-300">
                                Pilihan Kelas Ibtidaiyah
                            </h5>
                        </div>
                        <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400">Tingkat Dasar (1 - 4)</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <button
                            v-for="opt in ['Ibtidaiyah 1', 'Ibtidaiyah 2', 'Ibtidaiyah 3', 'Ibtidaiyah 4']"
                            :key="opt"
                            type="button"
                            :disabled="!isScoresComplete"
                            @click="selectKelas(opt)"
                            class="flex items-center justify-between rounded-xl border p-2.5 text-left transition-all duration-150"
                            :class="[
                                !isScoresComplete
                                    ? 'cursor-not-allowed opacity-50 border-gray-200 bg-gray-50 dark:border-slate-800 dark:bg-slate-900'
                                    : isKelasSelected(opt)
                                        ? 'border-emerald-500 bg-emerald-100 text-emerald-950 shadow-xs ring-2 ring-emerald-500/30 dark:border-emerald-400 dark:bg-emerald-950/80 dark:text-emerald-200 cursor-pointer'
                                        : 'border-white/80 bg-white text-slate-700 shadow-2xs hover:border-emerald-300 hover:bg-emerald-50/50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-emerald-700 cursor-pointer'
                            ]"
                        >
                            <div>
                                <span class="block text-xs font-black">{{ opt }}</span>
                                <span class="block text-[10px] text-gray-500 dark:text-slate-400">Tingkat Dasar</span>
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
                <div class="rounded-2xl border border-sky-200/80 bg-sky-50/40 p-3 dark:border-sky-900/40 dark:bg-sky-950/20 space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="flex h-2 w-2 rounded-full bg-sky-500"></span>
                            <h5 class="text-xs font-black text-sky-900 uppercase tracking-wider dark:text-sky-300">
                                Pilihan Kelas Tsanawiyah
                            </h5>
                        </div>
                        <span class="text-[10px] font-bold text-sky-700 dark:text-sky-400">Tingkat Menengah (1 - 3)</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <button
                            v-for="opt in ['Tsanawiyah 1', 'Tsanawiyah 2', 'Tsanawiyah 3']"
                            :key="opt"
                            type="button"
                            :disabled="!isScoresComplete"
                            @click="selectKelas(opt)"
                            class="flex items-center justify-between rounded-xl border p-2.5 text-left transition-all duration-150"
                            :class="[
                                !isScoresComplete
                                    ? 'cursor-not-allowed opacity-50 border-gray-200 bg-gray-50 dark:border-slate-800 dark:bg-slate-900'
                                    : isKelasSelected(opt)
                                        ? 'border-sky-500 bg-sky-100 text-sky-950 shadow-xs ring-2 ring-sky-500/30 dark:border-sky-400 dark:bg-sky-950/80 dark:text-sky-200 cursor-pointer'
                                        : 'border-white/80 bg-white text-slate-700 shadow-2xs hover:border-sky-300 hover:bg-sky-50/50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-sky-700 cursor-pointer'
                            ]"
                        >
                            <div>
                                <span class="block text-xs font-black">{{ opt }}</span>
                                <span class="block text-[10px] text-gray-500 dark:text-slate-400">Tingkat Menengah</span>
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

                <p v-if="form.errors.rekomendasi_kelas_pondok" class="text-xs font-semibold text-rose-500">
                    {{ form.errors.rekomendasi_kelas_pondok }}
                </p>
            </div>
            <!-- Evaluation Note Textarea -->
            <div class="space-y-1 pt-1">
                <TextareaInput
                    v-model="form.catatan_final"
                    label="Catatan Evaluasi / Rekomendasi Penguji (Opsional)"
                    placeholder="Tuliskan catatan khusus atau pertimbangan penempatan kelas santri..."
                    :rows="3"
                    :disabled="!isScoresComplete || isTidakLulus"
                />
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <SecondaryButton type="button" @click="emit('close')">
                    Batal
                </SecondaryButton>
                <PrimaryButton
                    type="button"
                    @click="submit"
                    :disabled="!isScoresComplete || isTidakLulus || !form.rekomendasi_kelas_pondok || form.processing"
                    :class="{ 'opacity-50 cursor-not-allowed': !isScoresComplete || isTidakLulus || !form.rekomendasi_kelas_pondok || form.processing }"
                >
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Penentuan Kelas' }}
                </PrimaryButton>
            </div>
        </template>
    </Modal>
</template>
