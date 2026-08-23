<script setup lang="ts">
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import BackButton from '@/Components/BackButton.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

interface AspekItem {
    id: string;
    kategori_id: string;
    nama_aspek: string;
    bobot: number;
    indikator?: string;
    urutan?: number;
}

interface KategoriItem {
    id: string;
    nama_kategori: string;
    keterangan?: string;
    aspek_penilaians: AspekItem[];
}

interface UserItem {
    id: string;
    name: string;
    email?: string;
    foto?: string;
}

const props = defineProps<{
    kelompok: {
        id: string;
        nama_kelompok: string;
        tanggal_ujian: string;
        waktu_mulai: string;
        waktu_selesai: string;
        lokasi: string;
        status: string;
    };
    kategori: KategoriItem;
    kategoriSlug: string;
    pendaftars: any[];
    examiners: UserItem[];
    isAssignedExaminer?: boolean;
    backUrl: string;
}>();

const page = usePage();
const currentUser = computed(() => (page.props.auth as any)?.user);

const isGroupLocked = computed(() => {
    return (props.kelompok.status || '').toLowerCase() === 'completed';
});

const isAssigned = computed(() => props.isAssignedExaminer !== false);
const canEdit = computed(() => isAssigned.value && !isGroupLocked.value);

// Page & Pedoman titles
const pageTitle = computed(() => {
    if (props.kategoriSlug === 'tes-hafalan') {
        return 'Tes Hafalan';
    }
    if (props.kategoriSlug === 'tes-menulis') {
        return 'Tes Menulis';
    }
    if (props.kategoriSlug === 'tes-membaca') {
        return 'Tes Membaca';
    }
    return props.kategori.nama_kategori;
});

const pedomanCardTitle = computed(() => {
    if (props.kategoriSlug === 'tes-hafalan') {
        return 'PEDOMAN PENILAIAN TES HAFALAN';
    }
    if (props.kategoriSlug === 'tes-menulis') {
        return 'PEDOMAN PENILAIAN TES MENULIS';
    }
    if (props.kategoriSlug === 'tes-membaca') {
        return 'PEDOMAN PENILAIAN TES MEMBACA';
    }
    return `PEDOMAN PENILAIAN ${props.kategori.nama_kategori.toUpperCase()}`;
});

// Default Rubrics & Criteria per category based on HAFALAN.xlsx
const defaultRubrics: Record<string, { indicator: string; bobot: number }[]> = {
    'tes-hafalan': [
        {
            indicator: 'Hafalan lancar tanpa berhenti/ragu-ragu; tidak ada pengulangan yang tidak perlu.',
            bobot: 30,
        },
        {
            indicator: 'Penerapan hukum nun mati/tanwin, mim mati, mad, ghunnah, qalqalah, waqaf, dan ibtida\'.',
            bobot: 30,
        },
        {
            indicator: 'Pengucapan huruf sesuai makharijul huruf; membedakan huruf serupa (ح/خ, س/ص, dll.).',
            bobot: 20,
        },
        {
            indicator: 'Membaca dengan tenang, khusyu\', tartil; memperhatikan waqaf dan washol.',
            bobot: 20,
        },
    ],
    'tes-membaca': [
        {
            indicator: 'Membaca teks dengan lancar, tidak banyak terhenti, mampu menyelesaikan bacaan sesuai waktu yang ditentukan.',
            bobot: 20,
        },
        {
            indicator: 'Memberikan harakat yang sesuai berdasarkan kaidah nahwu, menentukan posisi i\'rab dengan benar.',
            bobot: 25,
        },
        {
            indicator: 'Mampu menjelaskan fungsi kata dalam kalimat (nahwu: 15%) serta mengidentifikasi bentuk kata, wazan, dan tashrif (sharaf: 10%).',
            bobot: 25,
        },
        {
            indicator: 'Mengetahui arti mufradat, menerjemahkan kalimat secara tepat dan runtut, serta menjelaskan maksud/pokok bahasan isi bacaan.',
            bobot: 20,
        },
        {
            indicator: 'Menunjukkan sikap sopan, tenang, dan percaya diri saat membaca serta menjawab pertanyaan.',
            bobot: 10,
        },
    ],
    'tes-menulis': [
        {
            indicator: 'Penulisan huruf hijaiyah benar, tidak ada huruf yang tertukar (ض/ظ, ح/خ, س/ص, ث/ت, dll.). Penulisan harakat (fathah, kasrah, dhammah, sukun, tanwin) tepat dan lengkap.',
            bobot: 30,
        },
        {
            indicator: 'Seluruh kata dan kalimat yang didiktekan ditulis tanpa ada yang terlewat. Urutan kata dan struktur kalimat sesuai dengan yang didiktekan.',
            bobot: 25,
        },
        {
            indicator: 'Bentuk huruf jelas, proporsional, dan mudah dibaca (tidak ambigu). Penulisan rapi: tidak ada coretan berlebihan, jarak antar kata proporsional.',
            bobot: 25,
        },
        {
            indicator: 'Mampu menulis mengikuti kecepatan dikte tanpa ketinggalan.',
            bobot: 15,
        },
        {
            indicator: 'Mampu menulis dengan memperhatikan konteks kalimat (bukan sekadar menyalin bunyi).',
            bobot: 5,
        },
    ],
};

const rubrikList = computed(() => {
    const aspects = props.kategori.aspek_penilaians || [];
    const defaults = defaultRubrics[props.kategoriSlug] || [];

    return aspects.map((asp, idx) => {
        return {
            aspek: asp.nama_aspek,
            indicator: asp.indikator || defaults[idx]?.indicator || 'Penilaian sesuai aspek pengamatan penguji.',
            bobot: asp.bobot || defaults[idx]?.bobot || 0,
        };
    });
});

// Examiner name helper
const displayedExaminerName = computed(() => {
    if (props.examiners && props.examiners.length > 0) {
        return props.examiners.map((e) => e.name).join(', ');
    }

    return currentUser.value?.name || 'Panitia Ujian';
});

// Primary examiner object for avatar photo display
const primaryExaminer = computed<UserItem | null>(() => {
    if (props.examiners && props.examiners.length > 0) {
        return props.examiners[0];
    }
    return currentUser.value || null;
});

const getFotoPegawai = (foto?: string | null) => {
    if (!foto) {
        return null;
    }
    if (
        foto.startsWith('http://') ||
        foto.startsWith('https://') ||
        foto.startsWith('data:image') ||
        foto.startsWith('/storage/') ||
        foto.startsWith('/')
    ) {
        return foto;
    }
    if (foto.startsWith('storage/')) {
        return `/${foto}`;
    }
    return `/storage/${foto.replace(/^\/+/, '')}`;
};

const getInitials = (name?: string) => {
    if (!name) {
        return 'P';
    }
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

// Form state for batch table
interface SantriRowForm {
    pendaftar_id: string;
    scores: Record<string, number | string>;
    catatan: string;
}

const batchForm = useForm({
    rows: [] as SantriRowForm[],
});

// Initialize form data
const initFormData = () => {
    const rows: SantriRowForm[] = [];
    const aspects = props.kategori.aspek_penilaians || [];

    (props.pendaftars || []).forEach((pendaftar) => {
        const scoresMap: Record<string, number | string> = {};
        let initialCatatan = '';

        aspects.forEach((asp) => {
            scoresMap[asp.id] = '';
        });

        (pendaftar.penilaians || []).forEach((p: any) => {
            if (p.aspek_id && scoresMap[p.aspek_id] !== undefined) {
                scoresMap[p.aspek_id] = p.nilai !== null && p.nilai !== undefined ? Number(p.nilai) : '';

                if (p.catatan) {
                    initialCatatan = p.catatan;
                }
            }
        });

        rows.push({
            pendaftar_id: pendaftar.id,
            scores: scoresMap,
            catatan: initialCatatan,
        });
    });

    batchForm.rows = rows;
};

watch(
    () => [props.pendaftars, props.kategori],
    () => {
        initFormData();
    },
    { immediate: true, deep: true }
);

const getRowForm = (pendaftarId: string) => {
    let row = batchForm.rows.find((r) => r.pendaftar_id === pendaftarId);

    if (!row) {
        row = {
            pendaftar_id: pendaftarId,
            scores: {},
            catatan: '',
        };
        batchForm.rows.push(row);
    }

    return row;
};

// Calculate total score by direct summation of component scores
const calcRowScore = (pendaftarId: string) => {
    const row = getRowForm(pendaftarId);
    const aspects = props.kategori.aspek_penilaians || [];
    let sum = 0;
    let hasValue = false;

    aspects.forEach((asp) => {
        const val = row.scores[asp.id];

        if (val !== '' && val !== null && val !== undefined && !isNaN(Number(val))) {
            const num = Math.max(0, Math.min(asp.bobot || 100, Number(val)));
            sum += num;
            hasValue = true;
        }
    });

    const total = Math.round(sum * 100) / 100;
    let predicate = 'KURANG';

    if (total >= 86) {
predicate = 'BAIK SEKALI';
} else if (total >= 71) {
predicate = 'BAIK';
} else if (total >= 56) {
predicate = 'CUKUP';
}

    return {
        total,
        predicate: hasValue ? predicate : '-',
        hasValue,
    };
};

const getPredicateBadge = (predikat: string) => {
    if (predikat === 'BAIK SEKALI') {
        return {
            short: 'BS',
            full: 'Baik Sekali (86–100)',
            class: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-500/30 font-black',
        };
    } else if (predikat === 'BAIK') {
        return {
            short: 'B',
            full: 'Baik (71–85)',
            class: 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20 dark:bg-blue-950/50 dark:text-blue-300 dark:ring-blue-500/30 font-black',
        };
    } else if (predikat === 'CUKUP') {
        return {
            short: 'C',
            full: 'Cukup (56–70)',
            class: 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/50 dark:text-amber-300 dark:ring-amber-500/30 font-black',
        };
    } else if (predikat === 'KURANG') {
        return {
            short: 'K',
            full: 'Kurang (<56)',
            class: 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/50 dark:text-rose-300 dark:ring-rose-500/30 font-black',
        };
    }

    return {
        short: '-',
        full: 'Belum Dinilai',
        class: 'bg-gray-100 text-gray-500 dark:bg-slate-800 dark:text-slate-400 font-medium',
    };
};

// Search filter
const searchFilter = ref('');
const filteredPendaftars = computed(() => {
    const q = searchFilter.value.trim().toLowerCase();

    if (!q) {
return props.pendaftars || [];
}

    return (props.pendaftars || []).filter((p) => {
        const no = (p.nomor_pendaftaran || '').toLowerCase();
        const nama = (p.nama || '').toLowerCase();
        const nik = (p.nik || '').toLowerCase();

        return no.includes(q) || nama.includes(q) || nik.includes(q);
    });
});

// Quick Helper presets
const fillAllScoresMax = () => {
    const aspects = props.kategori.aspek_penilaians || [];
    batchForm.rows.forEach((row) => {
        aspects.forEach((asp) => {
            row.scores[asp.id] = asp.bobot || 0;
        });
    });
};

const fillAllScores80 = () => {
    const aspects = props.kategori.aspek_penilaians || [];
    batchForm.rows.forEach((row) => {
        aspects.forEach((asp) => {
            row.scores[asp.id] = Math.round((asp.bobot || 0) * 0.85);
        });
    });
};

const clearAllScores = () => {
    const aspects = props.kategori.aspek_penilaians || [];
    batchForm.rows.forEach((row) => {
        aspects.forEach((asp) => {
            row.scores[asp.id] = '';
        });
    });
};

// Handle Score Input with bounds restriction
const handleScoreInput = (pendaftarId: string, aspekId: string, maxBobot: number, event: Event) => {
    const input = event.target as HTMLInputElement;
    const val = input.value;

    if (val === '') {
        getRowForm(pendaftarId).scores[aspekId] = '';
        return;
    }

    let num = Number(val);
    if (isNaN(num)) {
        num = 0;
    }
    if (num > maxBobot) {
        num = maxBobot;
        input.value = String(maxBobot);
    }
    if (num < 0) {
        num = 0;
        input.value = '0';
    }

    getRowForm(pendaftarId).scores[aspekId] = num;
};

// Save Batch
const isSubmitting = ref(false);
const saveBatch = () => {
    isSubmitting.value = true;
    batchForm.post(`/admin/pendaftar/penilaian-interview/kelompok/${props.kelompok.id}/tes/${props.kategoriSlug}/save-batch`, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
        },
        onError: () => {
            isSubmitting.value = false;
        },
    });
};

// Save Single Row
const savingRowId = ref<string | null>(null);
const saveSingle = (pendaftar: any) => {
    const row = getRowForm(pendaftar.id);
    savingRowId.value = pendaftar.id;

    router.post(
        `/admin/pendaftar/penilaian-interview/kelompok/${props.kelompok.id}/tes/${props.kategoriSlug}/save-single`,
        {
            pendaftar_id: pendaftar.id,
            scores: row.scores,
            catatan: row.catatan,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                savingRowId.value = null;
            },
        }
    );
};

// Note Modal State
const isNoteModalOpen = ref(false);
const activeNotePendaftar = ref<any>(null);
const tempNote = ref('');

const openNoteModal = (pendaftar: any) => {
    activeNotePendaftar.value = pendaftar;
    const row = getRowForm(pendaftar.id);
    tempNote.value = row.catatan || '';
    isNoteModalOpen.value = true;
};

const saveNoteFromModal = () => {
    if (activeNotePendaftar.value) {
        const row = getRowForm(activeNotePendaftar.value.id);
        row.catatan = tempNote.value;
        saveSingle(activeNotePendaftar.value);
    }

    isNoteModalOpen.value = false;
};
</script>

<template>
    <div class="w-full space-y-6">
        <Head :title="`Lembar Penilaian ${pageTitle} - ${kelompok.nama_kelompok}`" />

        <!-- PAGE HEADER -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl dark:text-slate-100">
                    Lembar Penilaian {{ pageTitle }}
                </h1>
                <div class="mt-2 flex items-center gap-2 text-xs text-gray-500 sm:text-sm dark:text-slate-400">
                    <span>Kelompok: </span>
                    <span class="rounded-full bg-primary/10 px-2.5 py-0.5 font-bold text-primary dark:bg-blue-950/60 dark:text-blue-300">
                        {{ kelompok.nama_kelompok }}
                    </span>
                </div>
            </div>
            <div class="flex shrink-0 items-center justify-end">
                <BackButton :href="backUrl">Kembali</BackButton>
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- 1. TOP CARD: EXAMINER & SCHEDULE INFO                   -->
        <!-- ======================================================= -->
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <!-- Examiner & Schedule Metadata Box -->
            <div class="grid grid-cols-1 divide-y divide-gray-100 sm:grid-cols-3 sm:divide-x sm:divide-y-0 dark:divide-slate-800">
                <div class="flex items-center gap-3.5 p-5">
                    <div class="relative h-11 w-11 shrink-0">
                        <img
                            v-if="primaryExaminer?.foto"
                            :src="getFotoPegawai(primaryExaminer.foto)!"
                            :alt="displayedExaminerName"
                            class="h-11 w-11 rounded-full border border-gray-100 object-cover shadow-xs dark:border-slate-700"
                        />
                        <div
                            v-else
                            class="flex h-11 w-11 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-sm font-bold text-emerald-700 shadow-xs dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300"
                        >
                            {{ getInitials(displayedExaminerName) }}
                        </div>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-400">Nama Penguji</span>
                        <p class="text-sm font-bold text-gray-900 dark:text-slate-100">
                            {{ displayedExaminerName }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3.5 p-5">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700 ring-1 ring-blue-600/20 dark:bg-blue-950/50 dark:text-blue-300">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-800 dark:text-blue-400">Tanggal Pelaksanaan</span>
                        <p class="text-sm font-bold text-gray-900 dark:text-slate-100">
                            {{ kelompok.tanggal_ujian || 'Hari Ini' }} &bull; {{ kelompok.waktu_mulai }} - {{ kelompok.waktu_selesai }} WIB
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3.5 p-5">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-700 ring-1 ring-purple-600/20 dark:bg-purple-950/50 dark:text-purple-300">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 dark:text-purple-400">Ruangan & Peserta</span>
                        <p class="text-sm font-bold text-gray-900 dark:text-slate-100">
                            {{ kelompok.lokasi || 'Ruang Ujian' }} &bull; {{ pendaftars.length }} Santri
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Banner Mode Preview (Jika Ujian Telah Selesai / Terkunci) -->
        <div
            v-if="isGroupLocked"
            class="flex items-center gap-3.5 rounded-2xl border border-amber-200 bg-amber-50/90 p-4 text-xs font-bold text-amber-900 shadow-2xs dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300"
        >
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div>
                <p class="font-black text-sm text-amber-950 dark:text-amber-200">Mode Pratinjau (Hasil Ujian Telah Dikunci)</p>
                <p class="mt-0.5 text-[11px] font-medium text-amber-800/90 dark:text-amber-300/90">
                    Kelompok interview ini telah selesai dan seluruh penilaian telah dikunci. Lembar penilaian hanya dapat dilihat dan tidak dapat diedit lagi.
                </p>
            </div>
        </div>

        <!-- Banner Mode Lihat Saja (Bukan Petugas yang Ditugaskan) -->
        <div
            v-else-if="!isAssigned"
            class="flex items-center gap-3.5 rounded-2xl border border-sky-200 bg-sky-50/90 p-4 text-xs font-bold text-sky-900 shadow-2xs dark:border-sky-900/60 dark:bg-sky-950/40 dark:text-sky-300"
        >
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-900/60 dark:text-sky-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="font-black text-sm text-sky-950 dark:text-sky-200">Mode Lihat Saja (Bukan Penguji yang Ditugaskan)</p>
                <p class="mt-0.5 text-[11px] font-medium text-sky-800/90 dark:text-sky-300/90">
                    Anda sedang membuka lembar tes kelompok ini sebagai peninjau. Pengisian dan penyimpanan nilai hanya dapat dilakukan oleh pegawai yang ditugaskan sebagai penguji pada kelompok ini.
                </p>
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- 2. MAIN TABLE CARD                                      -->
        <!-- ======================================================= -->
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <!-- Toolbar -->
            <div class="flex flex-col gap-4 border-b border-gray-100 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                <!-- Search Input -->
                <div class="relative w-full max-w-xs">
                    <input
                        v-model="searchFilter"
                        type="text"
                        placeholder="Cari NIK atau Nama Peserta..."
                        class="w-full rounded-xl border border-gray-300 bg-white py-2 pl-9 pr-3 text-xs text-gray-900 placeholder-gray-400 shadow-xs focus:border-primary focus:outline-hidden focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500"
                    />
                    <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Quick Presets & Save Button (Hanya jika belum dikunci) -->
                <div v-if="!isGroupLocked" class="flex flex-wrap items-center gap-2">
                    <div class="hidden items-center gap-1.5 sm:flex">
                        <button
                            type="button"
                            @click="fillAllScoresMax"
                            class="cursor-pointer rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition-colors hover:bg-emerald-100 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300"
                            title="Isi semua nilai maksimal"
                        >
                            Set Nilai Maksimal
                        </button>
                        <button
                            type="button"
                            @click="fillAllScores80"
                            class="cursor-pointer rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 transition-colors hover:bg-blue-100 dark:border-blue-900/40 dark:bg-blue-950/40 dark:text-blue-300"
                            title="Isi semua nilai Baik (85%)"
                        >
                            Set Nilai 85%
                        </button>
                        <button
                            type="button"
                            @click="clearAllScores"
                            class="cursor-pointer rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-600 transition-colors hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                            title="Kosongkan nilai"
                        >
                            Kosongkan
                        </button>
                    </div>

                    <PrimaryButton
                        type="button"
                        @click="saveBatch"
                        :disabled="isSubmitting"
                        class="py-2! px-4! text-xs!"
                    >
                        <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        {{ isSubmitting ? 'Menyimpan...' : 'Simpan Semua' }}
                    </PrimaryButton>
                </div>
            </div>

            <!-- Table -->
            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:border-slate-800 dark:bg-slate-800/80 dark:text-slate-400">
                        <tr>
                            <th class="w-12 px-4 py-3.5 text-center whitespace-nowrap">NO.</th>
                            <th class="min-w-[180px] px-4 py-3.5 whitespace-nowrap">NO REGISTRASI</th>
                            <th class="min-w-[240px] px-4 py-3.5 whitespace-nowrap">CALON SANTRI</th>

                            <!-- Aspect Columns (Max = Bobot) -->
                            <th
                                v-for="aspek in kategori.aspek_penilaians"
                                :key="aspek.id"
                                class="min-w-[130px] px-3 py-3.5 text-center whitespace-nowrap"
                            >
                                <div class="text-xs font-bold text-gray-700 dark:text-slate-200 leading-tight">{{ aspek.nama_aspek }}</div>
                                <div class="mt-0.5 text-[10px] font-bold text-primary dark:text-blue-400">
                                    (Maks. {{ aspek.bobot }})
                                </div>
                            </th>

                            <!-- Total -->
                            <th class="w-20 px-4 py-3.5 text-center whitespace-nowrap">
                                TOTAL
                            </th>

                            <!-- Predikat -->
                            <th class="w-28 px-4 py-3.5 text-center whitespace-nowrap">
                                PREDIKAT
                            </th>

                            <!-- Catatan -->
                            <th class="min-w-[200px] px-4 py-3.5 whitespace-nowrap">
                                CATATAN
                            </th>

                            <!-- AKSI -->
                            <th class="w-24 px-4 py-3.5 text-center whitespace-nowrap">
                                AKSI
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                        <tr
                            v-for="(pendaftar, idx) in filteredPendaftars"
                            :key="pendaftar.id"
                            class="bg-white transition-colors duration-150 hover:bg-gray-50/80 dark:bg-slate-900 dark:hover:bg-slate-800/50"
                        >
                            <!-- No. -->
                            <td class="px-4 py-3.5 text-center font-bold text-xs text-gray-400 dark:text-slate-500 whitespace-nowrap">
                                {{ idx + 1 }}
                            </td>

                            <!-- 1. NO REGISTRASI & NIK -->
                            <td class="min-w-[180px] px-4 py-3.5 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="font-mono text-sm font-bold text-primary-dark dark:text-blue-400">
                                        {{ pendaftar.nomor_pendaftaran || '-' }}
                                    </span>
                                    <span class="mt-0.5 font-mono text-xs text-slate-500 dark:text-slate-400">
                                        NIK: {{ pendaftar.nik || pendaftar.personal_data?.nik || '-' }}
                                    </span>
                                </div>
                            </td>

                            <!-- 2. CALON SANTRI -->
                            <td class="min-w-[240px] px-4 py-3.5 whitespace-nowrap">
                                <div class="text-left text-sm font-bold whitespace-nowrap text-slate-800 dark:text-slate-100">
                                    {{ pendaftar.nama }}
                                </div>
                                <div v-if="pendaftar.jenjang" class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">
                                    {{ pendaftar.jenjang.name }}
                                </div>
                            </td>

                            <!-- Component Inputs (Clean standard number input with max restriction) -->
                            <td
                                v-for="aspek in kategori.aspek_penilaians"
                                :key="aspek.id"
                                class="px-2 py-3.5 text-center whitespace-nowrap"
                            >
                                <div class="flex justify-center">
                                    <input
                                        :value="getRowForm(pendaftar.id).scores[aspek.id]"
                                        @input="handleScoreInput(pendaftar.id, aspek.id, aspek.bobot, $event)"
                                        :disabled="!canEdit"
                                        type="number"
                                        min="0"
                                        :max="aspek.bobot"
                                        step="1"
                                        :placeholder="`0-${aspek.bobot}`"
                                        class="w-16 rounded-xl border border-gray-300 bg-white px-2 py-1.5 text-center text-sm font-bold text-gray-800 shadow-xs transition-colors [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none focus:border-primary focus:outline-hidden focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-primary dark:disabled:bg-slate-900 dark:disabled:text-slate-500"
                                    />
                                </div>
                            </td>

                            <!-- Total Nilai -->
                            <td class="px-4 py-3.5 text-center font-bold text-sm whitespace-nowrap">
                                <span :class="calcRowScore(pendaftar.id).hasValue ? 'text-gray-900 dark:text-slate-100' : 'text-gray-400 dark:text-slate-500 font-normal'">
                                    {{ calcRowScore(pendaftar.id).hasValue ? calcRowScore(pendaftar.id).total : '-' }}
                                </span>
                            </td>

                            <!-- Predikat -->
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <span
                                    v-if="calcRowScore(pendaftar.id).hasValue"
                                    :class="['inline-flex items-center justify-center rounded-lg px-2.5 py-1 text-xs font-bold', getPredicateBadge(calcRowScore(pendaftar.id).predicate).class]"
                                >
                                    {{ calcRowScore(pendaftar.id).predicate }}
                                </span>
                                <span v-else class="text-xs text-gray-400 dark:text-slate-500 font-medium">-</span>
                            </td>

                            <!-- CATATAN (Plain Text) -->
                            <td class="min-w-[180px] max-w-xs px-4 py-3.5">
                                <span
                                    class="text-xs text-gray-700 dark:text-slate-300 line-clamp-2"
                                    :title="getRowForm(pendaftar.id).catatan || ''"
                                >
                                    {{ getRowForm(pendaftar.id).catatan || '-' }}
                                </span>
                            </td>

                            <!-- AKSI (Circular Icon Buttons) -->
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-2">
                                    <button
                                        v-if="canEdit"
                                        type="button"
                                        @click="saveSingle(pendaftar)"
                                        :disabled="savingRowId === pendaftar.id"
                                        class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-primary text-white shadow-xs transition-all hover:bg-primary-dark disabled:opacity-50 dark:bg-primary"
                                        title="Simpan Nilai Santri"
                                    >
                                        <svg v-if="savingRowId === pendaftar.id" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>

                                    <button
                                        type="button"
                                        @click="openNoteModal(pendaftar)"
                                        class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-full border border-gray-300 bg-white text-gray-600 shadow-xs transition-all hover:bg-gray-100 hover:text-gray-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white"
                                        :title="isGroupLocked ? 'Lihat Catatan' : 'Tulis Catatan Lengkap'"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="filteredPendaftars.length === 0">
                            <td :colspan="6 + (kategori.aspek_penilaians?.length || 0)" class="px-6 py-12 text-center text-gray-400 dark:text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="mb-3 h-10 w-10 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <p class="text-sm font-bold text-gray-600 dark:text-slate-400">Tidak ada peserta ditemukan</p>
                                    <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">Coba ubah kata kunci pencarian Anda.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- 3. BOTTOM CARD: RUBRIK PENILAIAN (Exact Excel layout)   -->
        <!-- ======================================================= -->
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-6">
            <div class="border-b border-gray-100 pb-3 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950 dark:text-emerald-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-black uppercase tracking-wider text-gray-900 dark:text-slate-100">
                        {{ pedomanCardTitle }}
                    </h3>
                </div>
            </div>

            <!-- Rubric Reference Table matching Excel layout -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border border-gray-200 rounded-2xl overflow-hidden dark:border-slate-700">
                    <thead class="bg-gray-100 text-gray-800 uppercase font-black dark:bg-slate-800 dark:text-slate-200">
                        <tr>
                            <th class="w-48 px-4 py-3.5 border-r border-gray-200 dark:border-slate-700">Aspek Penilaian</th>
                            <th class="min-w-[300px] px-4 py-3.5 border-r border-gray-200 dark:border-slate-700">Indikator</th>
                            <th class="w-28 px-4 py-3.5 text-center">Bobot</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                        <tr
                            v-for="(item, rIdx) in rubrikList"
                            :key="rIdx"
                            class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30"
                        >
                            <td class="px-4 py-3.5 font-bold text-gray-900 border-r border-gray-200 dark:border-slate-700 dark:text-slate-100">
                                {{ item.aspek }}
                            </td>
                            <td class="px-4 py-3.5 text-gray-700 border-r border-gray-200 leading-relaxed dark:border-slate-700 dark:text-slate-300">
                                {{ item.indicator }}
                            </td>
                            <td class="px-4 py-3.5 text-center font-black text-primary dark:text-blue-400">
                                {{ item.bobot }}%
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Keterangan Scale Legend matching Excel Footer -->
            <div class="rounded-2xl border border-blue-100 bg-blue-50/60 p-3.5 text-center text-xs font-bold text-blue-900 dark:border-blue-950 dark:bg-blue-950/30 dark:text-blue-300">
                Keterangan:
                <span class="text-emerald-700 dark:text-emerald-400 ml-1">BS = Baik Sekali (86–100)</span> &nbsp;|&nbsp;
                <span class="text-blue-700 dark:text-blue-400">B = Baik (71–85)</span> &nbsp;|&nbsp;
                <span class="text-amber-700 dark:text-amber-400">C = Cukup (56–70)</span> &nbsp;|&nbsp;
                <span class="text-rose-700 dark:text-rose-400">K = Kurang (&lt; 56)</span>
            </div>

            <!-- Signature block matching Excel Mengetahui -->
            <div class="grid grid-cols-2 gap-6 pt-6 border-t border-gray-100 text-center text-xs dark:border-slate-800">
                <div class="space-y-12">
                    <p class="text-gray-500 dark:text-slate-400">Mengetahui,<br><span class="font-bold text-gray-800 dark:text-slate-200">Koordinator PSB</span></p>
                    <p class="font-bold text-gray-700 dark:text-slate-300">( .................................................... )</p>
                </div>
                <div class="space-y-12">
                    <p class="text-gray-500 dark:text-slate-400">Penguji Ujian {{ pageTitle }},<br><span class="font-bold text-gray-800 dark:text-slate-200">{{ displayedExaminerName }}</span></p>
                    <p class="font-bold text-gray-700 dark:text-slate-300">( {{ displayedExaminerName }} )</p>
                </div>
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- 4. NOTE MODAL                                           -->
        <!-- ======================================================= -->
        <Modal
            :show="isNoteModalOpen"
            title="Catatan Hasil Penilaian"
            :description="activeNotePendaftar ? `Tulis catatan penilaian untuk ${activeNotePendaftar.nama}` : ''"
            maxWidth="lg"
            @close="isNoteModalOpen = false"
        >
            <template #icon>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-blue-950 dark:text-blue-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
            </template>

            <div class="space-y-4">
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-3 text-xs dark:border-slate-800 dark:bg-slate-800/50">
                    <div class="font-bold text-gray-800 dark:text-slate-200">{{ activeNotePendaftar?.nama }}</div>
                    <div class="font-mono text-[11px] text-gray-400">{{ activeNotePendaftar?.nomor_pendaftaran }} &bull; {{ activeNotePendaftar?.jenjang?.name }}</div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-slate-300 mb-1.5">
                        Catatan Penguji:
                    </label>
                    <textarea
                        v-model="tempNote"
                        :disabled="!canEdit"
                        rows="4"
                        placeholder="Contoh: Makharijul huruf sangat fasih, tajwid perlu sedikit ditingkatkan pada hukum ghunnah..."
                        class="w-full rounded-2xl border border-gray-200 bg-white p-3 text-xs text-gray-900 placeholder-gray-400 focus:border-primary focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:disabled:bg-slate-900 dark:disabled:text-slate-400"
                    ></textarea>
                </div>
            </div>

            <template #footer>
                <SecondaryButton type="button" @click="isNoteModalOpen = false">
                    {{ canEdit ? 'Batal' : 'Tutup' }}
                </SecondaryButton>
                <PrimaryButton v-if="canEdit" type="button" @click="saveNoteFromModal">
                    Simpan Catatan
                </PrimaryButton>
            </template>
        </Modal>
    </div>
</template>
