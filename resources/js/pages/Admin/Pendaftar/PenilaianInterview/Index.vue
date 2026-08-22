<script setup lang="ts">
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AkademikWaveFilterCards from '@/Components/AkademikWaveFilterCards.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DataTable from '@/Components/DataTable.vue';
import ActionMenu from '@/Components/Form/ActionMenu.vue';
import CustomDatePicker from '@/Components/Form/CustomDatePicker.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import FilterModal from '@/Components/Form/FilterModal.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { index, edit_kelompok, destroy_kelompok, show_kelompok } from '@/routes/admin/pendaftar/penilaian_interview';

defineOptions({ layout: AdminLayout });

interface ActiveTahunAkademik {
    id: string;
    name: string;
    is_active: boolean;
}

interface CabangItem {
    id: string;
    name: string;
}

interface PengujiItem {
    id: string;
    name: string;
    email?: string;
}

const props = defineProps<{
    kelompokUjians: any;
    metrics: {
        total_kelompok: number;
        total_peserta: number;
        belum_dinilai: number;
        sedang_dinilai: number;
        selesai_dinilai: number;
        nilai_terkunci: number;
    };
    jenjangs: any[];
    selectedJenjangId?: string;
    cabangs: CabangItem[];
    activeTahunAkademik?: ActiveTahunAkademik | null;
    hasActiveTahunAkademik?: boolean;
    gelombangs?: any[];
    pengujis: PengujiItem[];
    koordinator: any[];
    kategoriPenilaians: any[];
    filters: {
        limit?: number;
        search?: string;
        jenjang_id?: string;
        cabang_id?: string;
        gelombang_id?: string;
        gender?: string;
        status_penilaian?: string;
        penguji_id?: string;
        start_date?: string;
        end_date?: string;
    };
}>();

const columns = [
    { key: 'nama_kelompok', label: 'INFORMASI KELOMPOK', sortable: true },
    { key: 'jadwal', label: 'JADWAL & RUANGAN', sortable: true },
    { key: 'peserta', label: 'TOTAL SANTRI', sortable: true },
    { key: 'wawancara', label: 'WAWANCARA', sortable: false },
    { key: 'kelas', label: 'KELAS', sortable: false },
    { key: 'tes_membaca', label: 'TES MEMBACA', sortable: false },
    { key: 'tes_menulis', label: 'TES MENULIS', sortable: false },
    { key: 'tes_hafalan', label: 'TES HAFALAN', sortable: false },
    { key: 'status_kelompok', label: 'STATUS PENILAIAN', sortable: true },
];

// ==========================================
// SEARCH & FILTER HANDLING (HANYA TANGGAL & STATUS PENILAIAN)
// ==========================================
const search = ref(props.filters.search || '');
let searchTimeout: any = null;
const isFilterModalOpen = ref(false);

const filterForm = useForm({
    gelombang_id: props.filters.gelombang_id || '',
    status_penilaian: props.filters.status_penilaian || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

watch(
    () => props.filters,
    (newFilters) => {
        filterForm.gelombang_id = newFilters.gelombang_id || '';
        filterForm.status_penilaian = newFilters.status_penilaian || '';
        filterForm.start_date = newFilters.start_date || '';
        filterForm.end_date = newFilters.end_date || '';
    },
    { deep: true, immediate: true }
);

const onSelectGelombang = (gelombangId: string) => {
    filterForm.gelombang_id = gelombangId;
    applyFilters();
};

const isFilterActive = computed(() => {
    return (
        Boolean(filterForm.status_penilaian) ||
        Boolean(filterForm.start_date) ||
        Boolean(filterForm.end_date)
    );
});

const applyFilters = () => {
    isFilterModalOpen.value = false;
    router.get(
        index.url(),
        {
            search: search.value,
            limit: props.filters.limit,
            jenjang_id: props.filters.jenjang_id,
            gelombang_id: filterForm.gelombang_id,
            status_penilaian: filterForm.status_penilaian,
            start_date: filterForm.start_date,
            end_date: filterForm.end_date,
            page: 1,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    filterForm.status_penilaian = '';
    filterForm.start_date = '';
    filterForm.end_date = '';
    applyFilters();
};

const onSearchInput = (val: string) => {
    search.value = val;
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            index.url(),
            {
                ...props.filters,
                search: val,
                page: 1,
            },
            { preserveState: true, replace: true }
        );
    }, 300);
};

const onLimitChange = (newLimit: number) => {
    router.get(
        index.url(),
        {
            ...props.filters,
            limit: newLimit,
            page: 1,
        },
        { preserveState: true, replace: true }
    );
};

// ==========================================
// ACTIONS MENU HANDLERS FOR KELOMPOK
// ==========================================
const isDeleteModalOpen = ref(false);
const isCannotDeleteModalOpen = ref(false);
const deleteTarget = ref<any>(null);
const blockedDeleteTarget = ref<any>(null);
const deleteForm = useForm({});

const handleDetailKelompok = (_row: any) => {
    // Siap diarahkan sesuai instruksi user berikutnya
};

const handleEditKelompok = (_row: any) => {
    // Siap diarahkan sesuai instruksi user berikutnya
};

const handleDeleteKelompok = (row: any) => {
    if (!row.can_delete) {
        blockedDeleteTarget.value = row;
        isCannotDeleteModalOpen.value = true;
        return;
    }
    deleteTarget.value = row;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    deleteTarget.value = null;
};

const closeCannotDeleteModal = () => {
    isCannotDeleteModalOpen.value = false;
    blockedDeleteTarget.value = null;
};

const submitDelete = () => {
    if (!deleteTarget.value) {
        return;
    }

    deleteForm.delete(destroy_kelompok.url(deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
        },
    });
};

// ==========================================
// UI HELPERS
// ==========================================
const formatStatCount = (val: any) => {
    const num = Number(val ?? 0);

    return num > 99 ? '99+' : num.toLocaleString('id-ID');
};

const getStatusKelompokBadge = (status?: string) => {
    switch (status) {
        case 'Terkunci':
            return {
                label: 'Nilai Terkunci',
                classes: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/50 dark:text-emerald-300',
                dotClass: 'bg-emerald-500',
            };
        case 'Selesai':
            return {
                label: 'Selesai Dinilai',
                classes: 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/50 dark:bg-sky-950/50 dark:text-sky-300',
                dotClass: 'bg-sky-500',
            };
        case 'Sedang Dinilai':
            return {
                label: 'Sedang Berlangsung',
                classes: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-300',
                dotClass: 'bg-amber-500',
            };
        case 'Kosong':
            return {
                label: 'Belum Ada Santri',
                classes: 'border-slate-200 bg-slate-50 text-slate-500 dark:border-slate-800 dark:bg-slate-800/80 dark:text-slate-400',
                dotClass: 'bg-slate-400',
            };
        default:
            return {
                label: 'Belum Dinilai',
                classes: 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-300',
                dotClass: 'bg-rose-500',
            };
    }
};

const getInitials = (name?: string) => {
    if (!name) {
return 'PG';
}

    return name
        .split(' ')
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase();
};

const formatTanggal = (dateStr?: string) => {
    if (!dateStr) {
return '-';
}

    try {
        const d = new Date(dateStr);

        return d.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    } catch {
        return dateStr;
    }
};

const formatWaktu = (mulai?: string, selesai?: string) => {
    if (!mulai) {
return '-';
}

    const m = mulai.substring(0, 5);
    const s = selesai ? selesai.substring(0, 5) : '';

    return s ? `${m} - ${s} WIB` : `${m} WIB`;
};
</script>

<template>
    <div class="relative min-h-screen w-full">
        <Head title="Penilaian Interview" />

        <!-- Header Section -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl dark:text-slate-100">
                    Penilaian Interview
                </h1>
                <p class="max-w-2xl text-xs leading-relaxed text-gray-500 sm:text-sm dark:text-slate-400">
                    Kelola evaluasi ujian seleksi per kelompok interview, pengisian nilai massal spreadsheet, dan rekapitulasi kelulusan santri.
                </p>
            </div>
        </div>

        <!-- Academic Year & Wave Cards (With Inactive TA Overlay Blur) -->
        <AkademikWaveFilterCards
            :active-tahun-akademik="props.activeTahunAkademik"
            :has-active-tahun-akademik="props.hasActiveTahunAkademik"
            :gelombangs="props.gelombangs"
            :selected-gelombang-id="filterForm.gelombang_id"
            @select-gelombang="onSelectGelombang"
        />

        <!-- Informative Metrics Summary Cards Grid -->
        <div class="mb-6 grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            <!-- Card 1: Total Kelompok -->
            <div class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-xs transition-all duration-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                        Total Kelompok
                    </span>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-blue-600 dark:border-blue-900/50 dark:bg-blue-950/60 dark:text-blue-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="font-mono text-2xl font-black tracking-tight text-gray-900 dark:text-slate-100">
                        {{ formatStatCount(props.metrics.total_kelompok) }}
                    </div>
                    <p class="mt-0.5 text-xs font-semibold text-gray-400 dark:text-slate-500">
                        Sesi Interview Aktif
                    </p>
                </div>
            </div>

            <!-- Card 2: Total Peserta Santri -->
            <div class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-xs transition-all duration-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                        Total Santri Ujian
                    </span>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 text-indigo-600 dark:border-indigo-900/50 dark:bg-indigo-950/60 dark:text-indigo-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="font-mono text-2xl font-black tracking-tight text-gray-900 dark:text-slate-100">
                        {{ formatStatCount(props.metrics.total_peserta) }}
                    </div>
                    <p class="mt-0.5 text-xs font-semibold text-gray-400 dark:text-slate-500">
                        Peserta Terjadwal
                    </p>
                </div>
            </div>

            <!-- Card 3: Belum Dinilai -->
            <div class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-xs transition-all duration-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-rose-600 dark:text-rose-400">
                        Belum Dinilai
                    </span>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 dark:border-rose-900/50 dark:bg-rose-950/60 dark:text-rose-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="font-mono text-2xl font-black tracking-tight text-rose-600 dark:text-rose-400">
                        {{ formatStatCount(props.metrics.belum_dinilai) }}
                    </div>
                    <p class="mt-0.5 text-xs font-semibold text-gray-400 dark:text-slate-500">
                        Santri Menunggu Ujian
                    </p>
                </div>
            </div>

            <!-- Card 4: Sedang Dinilai / Selesai (Draft) -->
            <div class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-xs transition-all duration-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-sky-600 dark:text-sky-400">
                        Selesai (Draft)
                    </span>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-sky-200 bg-sky-50 text-sky-600 dark:border-sky-900/50 dark:bg-sky-950/60 dark:text-sky-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="font-mono text-2xl font-black tracking-tight text-sky-600 dark:text-sky-400">
                        {{ formatStatCount(props.metrics.selesai_dinilai + props.metrics.sedang_dinilai) }}
                    </div>
                    <p class="mt-0.5 text-xs font-semibold text-gray-400 dark:text-slate-500">
                        Evaluasi Belum Dikunci
                    </p>
                </div>
            </div>

            <!-- Card 5: Nilai Terkunci (Final) -->
            <div class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-xs transition-all duration-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                        Nilai Terkunci
                    </span>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-600 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="font-mono text-2xl font-black tracking-tight text-emerald-600 dark:text-emerald-400">
                        {{ formatStatCount(props.metrics.nilai_terkunci) }}
                    </div>
                    <p class="mt-0.5 text-xs font-semibold text-gray-400 dark:text-slate-500">
                        Hasil Final & Terverifikasi
                    </p>
                </div>
            </div>
        </div>

        <!-- DataTable Container for Kelompok Interview -->
        <div class="mt-6">
            <DataTable
                :columns="columns"
                :data="props.kelompokUjians.data"
                :pagination="props.kelompokUjians"
                @search="onSearchInput"
                @limit="onLimitChange"
            >
                <template #filters>
                    <div class="flex items-center gap-2">
                        <!-- Filter Trigger Button -->
                        <button
                            type="button"
                            @click="isFilterModalOpen = true"
                            class="group inline-flex items-center rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold text-gray-700 shadow-xs transition-all hover:bg-gray-50 focus:ring-2 focus:ring-primary/20 focus:outline-none sm:px-4 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            <svg
                                class="h-4 w-4 text-gray-400 transition-colors group-hover:text-primary dark:text-slate-500 dark:group-hover:text-blue-400"
                                :class="isFilterActive ? 'text-primary dark:text-blue-400' : ''"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            <span class="ml-2 hidden sm:inline">Filter</span>
                            <span
                                v-if="isFilterActive"
                                class="ml-1.5 h-2 w-2 animate-pulse rounded-full bg-primary sm:ml-2 dark:bg-blue-400"
                            ></span>
                        </button>
                    </div>
                </template>

                <!-- 1. Custom Cell: Nama Kelompok & Ruang -->
                <template #cell-nama_kelompok="{ row }">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-blue-200 bg-blue-50 text-blue-600 dark:border-blue-900/50 dark:bg-blue-950/60 dark:text-blue-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div>
                            <Link
                                :href="show_kelompok.url(row.id)"
                                class="text-sm font-extrabold text-slate-800 transition-colors hover:text-primary dark:text-slate-100 dark:hover:text-blue-400"
                            >
                                {{ row.nama_kelompok }}
                            </Link>
                            <div class="mt-0.5 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                                <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>{{ row.lokasi || 'Ruang Ujian Utama' }}</span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- 2. Custom Cell: Jadwal & Waktu -->
                <template #cell-jadwal="{ row }">
                    <div class="flex flex-col">
                        <div class="flex items-center gap-1.5 text-xs font-bold text-slate-800 dark:text-slate-200">
                            <svg class="h-3.5 w-3.5 text-blue-500 shrink-0 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>{{ formatTanggal(row.tanggal_ujian) }}</span>
                        </div>
                        <div class="mt-0.5 font-mono text-[11px] text-slate-500 dark:text-slate-400">
                            {{ formatWaktu(row.waktu_mulai, row.waktu_selesai) }}
                        </div>
                    </div>
                </template>

                <!-- 3. Custom Cell: Total Peserta -->
                <template #cell-peserta="{ row }">
                    <span class="inline-flex w-fit items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        <svg class="h-3.5 w-3.5 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>{{ row.total_peserta ?? row.pendaftars_count ?? row.pendaftars?.length ?? 0 }} Santri</span>
                    </span>
                </template>

                <!-- 4. Custom Cell: Wawancara -->
                <template #cell-wawancara="{ row }">
                    <div class="w-24 sm:w-28 space-y-1">
                        <div class="flex items-center justify-between text-[11px] font-bold">
                            <span
                                :class="[
                                    (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0) > 0 && row.total_wawancara === (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0)
                                        ? 'text-emerald-700 dark:text-emerald-400'
                                        : (row.total_wawancara > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400')
                                ]"
                            >
                                {{ row.total_wawancara || 0 }}/{{ row.total_peserta ?? row.pendaftars_count ?? row.pendaftars?.length ?? 0 }}
                            </span>
                            <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500">
                                {{ (row.total_peserta || row.pendaftars_count || row.pendaftars?.length) ? Math.round(((row.total_wawancara || 0) / (row.total_peserta || row.pendaftars_count || row.pendaftars?.length)) * 100) : 0 }}%
                            </span>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div
                                class="h-full rounded-full transition-all duration-300"
                                :class="[
                                    (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0) > 0 && row.total_wawancara === (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0)
                                        ? 'bg-emerald-500'
                                        : (row.total_wawancara > 0 ? 'bg-amber-500' : 'bg-slate-300 dark:bg-slate-700')
                                ]"
                                :style="{ width: `${(row.total_peserta || row.pendaftars_count || row.pendaftars?.length) ? Math.round(((row.total_wawancara || 0) / (row.total_peserta || row.pendaftars_count || row.pendaftars?.length)) * 100) : 0}%` }"
                            ></div>
                        </div>
                    </div>
                </template>

                <!-- 5. Custom Cell: Penentuan Kelas -->
                <template #cell-kelas="{ row }">
                    <div class="w-24 sm:w-28 space-y-1">
                        <div class="flex items-center justify-between text-[11px] font-bold">
                            <span
                                :class="[
                                    (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0) > 0 && row.total_kelas === (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0)
                                        ? 'text-emerald-700 dark:text-emerald-400'
                                        : (row.total_kelas > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400')
                                ]"
                            >
                                {{ row.total_kelas || 0 }}/{{ row.total_peserta ?? row.pendaftars_count ?? row.pendaftars?.length ?? 0 }}
                            </span>
                            <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500">
                                {{ (row.total_peserta || row.pendaftars_count || row.pendaftars?.length) ? Math.round(((row.total_kelas || 0) / (row.total_peserta || row.pendaftars_count || row.pendaftars?.length)) * 100) : 0 }}%
                            </span>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div
                                class="h-full rounded-full transition-all duration-300"
                                :class="[
                                    (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0) > 0 && row.total_kelas === (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0)
                                        ? 'bg-emerald-500'
                                        : (row.total_kelas > 0 ? 'bg-amber-500' : 'bg-slate-300 dark:bg-slate-700')
                                ]"
                                :style="{ width: `${(row.total_peserta || row.pendaftars_count || row.pendaftars?.length) ? Math.round(((row.total_kelas || 0) / (row.total_peserta || row.pendaftars_count || row.pendaftars?.length)) * 100) : 0}%` }"
                            ></div>
                        </div>
                    </div>
                </template>

                <!-- 6. Custom Cell: Tes Membaca -->
                <template #cell-tes_membaca="{ row }">
                    <div class="w-24 sm:w-28 space-y-1">
                        <div class="flex items-center justify-between text-[11px] font-bold">
                            <span
                                :class="[
                                    (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0) > 0 && row.total_baca === (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0)
                                        ? 'text-emerald-700 dark:text-emerald-400'
                                        : (row.total_baca > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400')
                                ]"
                            >
                                {{ row.total_baca || 0 }}/{{ row.total_peserta ?? row.pendaftars_count ?? row.pendaftars?.length ?? 0 }}
                            </span>
                            <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500">
                                {{ (row.total_peserta || row.pendaftars_count || row.pendaftars?.length) ? Math.round(((row.total_baca || 0) / (row.total_peserta || row.pendaftars_count || row.pendaftars?.length)) * 100) : 0 }}%
                            </span>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div
                                class="h-full rounded-full transition-all duration-300"
                                :class="[
                                    (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0) > 0 && row.total_baca === (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0)
                                        ? 'bg-emerald-500'
                                        : (row.total_baca > 0 ? 'bg-amber-500' : 'bg-slate-300 dark:bg-slate-700')
                                ]"
                                :style="{ width: `${(row.total_peserta || row.pendaftars_count || row.pendaftars?.length) ? Math.round(((row.total_baca || 0) / (row.total_peserta || row.pendaftars_count || row.pendaftars?.length)) * 100) : 0}%` }"
                            ></div>
                        </div>
                    </div>
                </template>

                <!-- 7. Custom Cell: Tes Menulis -->
                <template #cell-tes_menulis="{ row }">
                    <div class="w-24 sm:w-28 space-y-1">
                        <div class="flex items-center justify-between text-[11px] font-bold">
                            <span
                                :class="[
                                    (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0) > 0 && row.total_menulis === (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0)
                                        ? 'text-emerald-700 dark:text-emerald-400'
                                        : (row.total_menulis > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400')
                                ]"
                            >
                                {{ row.total_menulis || 0 }}/{{ row.total_peserta ?? row.pendaftars_count ?? row.pendaftars?.length ?? 0 }}
                            </span>
                            <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500">
                                {{ (row.total_peserta || row.pendaftars_count || row.pendaftars?.length) ? Math.round(((row.total_menulis || 0) / (row.total_peserta || row.pendaftars_count || row.pendaftars?.length)) * 100) : 0 }}%
                            </span>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div
                                class="h-full rounded-full transition-all duration-300"
                                :class="[
                                    (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0) > 0 && row.total_menulis === (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0)
                                        ? 'bg-emerald-500'
                                        : (row.total_menulis > 0 ? 'bg-amber-500' : 'bg-slate-300 dark:bg-slate-700')
                                ]"
                                :style="{ width: `${(row.total_peserta || row.pendaftars_count || row.pendaftars?.length) ? Math.round(((row.total_menulis || 0) / (row.total_peserta || row.pendaftars_count || row.pendaftars?.length)) * 100) : 0}%` }"
                            ></div>
                        </div>
                    </div>
                </template>

                <!-- 8. Custom Cell: Tes Hafalan -->
                <template #cell-tes_hafalan="{ row }">
                    <div class="w-24 sm:w-28 space-y-1">
                        <div class="flex items-center justify-between text-[11px] font-bold">
                            <span
                                :class="[
                                    (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0) > 0 && row.total_hafalan === (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0)
                                        ? 'text-emerald-700 dark:text-emerald-400'
                                        : (row.total_hafalan > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400')
                                ]"
                            >
                                {{ row.total_hafalan || 0 }}/{{ row.total_peserta ?? row.pendaftars_count ?? row.pendaftars?.length ?? 0 }}
                            </span>
                            <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500">
                                {{ (row.total_peserta || row.pendaftars_count || row.pendaftars?.length) ? Math.round(((row.total_hafalan || 0) / (row.total_peserta || row.pendaftars_count || row.pendaftars?.length)) * 100) : 0 }}%
                            </span>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div
                                class="h-full rounded-full transition-all duration-300"
                                :class="[
                                    (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0) > 0 && row.total_hafalan === (row.total_peserta || row.pendaftars_count || row.pendaftars?.length || 0)
                                        ? 'bg-emerald-500'
                                        : (row.total_hafalan > 0 ? 'bg-amber-500' : 'bg-slate-300 dark:bg-slate-700')
                                ]"
                                :style="{ width: `${(row.total_peserta || row.pendaftars_count || row.pendaftars?.length) ? Math.round(((row.total_hafalan || 0) / (row.total_peserta || row.pendaftars_count || row.pendaftars?.length)) * 100) : 0}%` }"
                            ></div>
                        </div>
                    </div>
                </template>

                <!-- 9. Custom Cell: Status Penilaian -->
                <template #cell-status_kelompok="{ row }">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold"
                        :class="getStatusKelompokBadge(row.status_kelompok).classes"
                    >
                        <span class="h-1.5 w-1.5 rounded-full" :class="getStatusKelompokBadge(row.status_kelompok).dotClass"></span>
                        {{ getStatusKelompokBadge(row.status_kelompok).label }}
                    </span>
                </template>

                <!-- Row Actions Menu for Kelompok -->
                <template #row-actions="{ row }">
                    <div class="flex justify-end">
                        <ActionMenu width="52">
                            <template #trigger>
                                <button
                                    class="cursor-pointer rounded-full border border-transparent p-2 text-gray-500 transition-colors hover:border-gray-200 hover:bg-gray-100 hover:text-gray-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:bg-slate-700 dark:hover:text-slate-200"
                                    title="Opsi Aksi"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                </button>
                            </template>
                            <template #content>
                                <!-- 1. Detail -->
                                <Link
                                    :href="show_kelompok.url(row.id)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:text-slate-200 dark:hover:bg-slate-700"
                                >
                                    <svg class="mr-3 h-4 w-4 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Detail
                                </Link>

                                <!-- 2. Edit (Hanya jika belum hari H & belum dinilai) -->
                                <Link
                                    v-if="row.can_edit"
                                    :href="edit_kelompok.url(row.id)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:text-slate-200 dark:hover:bg-slate-700"
                                >
                                    <svg class="mr-3 h-4 w-4 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </Link>

                                <div class="my-1 border-t border-gray-100 dark:border-slate-800"></div>

                                <!-- 3. Hapus -->
                                <button
                                    type="button"
                                    @click="handleDeleteKelompok(row)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-rose-600 transition-colors hover:bg-rose-50 sm:px-4 dark:text-rose-400 dark:hover:bg-rose-950/40"
                                >
                                    <svg class="mr-3 h-4 w-4 text-rose-500 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Hapus
                                </button>
                            </template>
                        </ActionMenu>
                    </div>
                </template>
            </DataTable>
        </div>

        <!-- Filter Modal (HANYA TANGGAL DAN STATUS PENILAIAN) -->
        <FilterModal
            :show="isFilterModalOpen"
            title="Filter Penilaian & Kelompok Ujian"
            description="Saring data kelompok ujian berdasarkan status penilaian & tanggal pelaksanaan"
            max-width="lg"
            @close="isFilterModalOpen = false"
            @reset="resetFilters"
            @apply="applyFilters"
        >
            <div class="space-y-4">
                <!-- 1. Status Penilaian -->
                <div>
                    <label class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400">
                        Status Penilaian
                    </label>
                    <CustomSelect
                        v-model="filterForm.status_penilaian"
                        placeholder="Semua Status Penilaian"
                        :options="[
                            { label: 'Belum Dinilai', value: 'belum_dinilai' },
                            { label: 'Sedang Berlangsung / Sedang Dinilai', value: 'sebagian_dinilai' },
                            { label: 'Selesai Dinilai (Draft)', value: 'selesai_dinilai' },
                            { label: 'Nilai Terkunci (Final)', value: 'terkunci' },
                        ]"
                    />
                </div>

                <!-- 2. Rentang Tanggal Ujian -->
                <div>
                    <label class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400">
                        Rentang Tanggal Ujian
                    </label>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <span class="mb-1 block text-[11px] font-medium text-gray-400 dark:text-slate-500">Dari Tanggal</span>
                            <CustomDatePicker
                                v-model="filterForm.start_date"
                            />
                        </div>
                        <div>
                            <span class="mb-1 block text-[11px] font-medium text-gray-400 dark:text-slate-500">Sampai Tanggal</span>
                            <CustomDatePicker
                                v-model="filterForm.end_date"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </FilterModal>

        <!-- ======================================================= -->
        <!-- MODAL: KELOMPOK TIDAK DAPAT DIHAPUS (PERINGATAN)        -->
        <!-- ======================================================= -->
        <Modal
            :show="isCannotDeleteModalOpen"
            @close="closeCannotDeleteModal"
            maxWidth="md"
            title="Kelompok Tidak Dapat Dihapus"
            description="Informasi pembatasan penghapusan kelompok ujian."
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400"
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
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                </div>
            </template>

            <div v-if="blockedDeleteTarget" class="space-y-3 text-left">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                    Kelompok ujian <strong class="font-extrabold text-slate-900 dark:text-white">{{ blockedDeleteTarget.nama_kelompok }}</strong> tidak dapat dihapus karena:
                </p>

                <div class="space-y-2 rounded-2xl border border-amber-100 bg-amber-50/60 p-4 dark:border-amber-900/40 dark:bg-amber-950/20">
                    <div class="flex items-start gap-2.5">
                        <div class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-amber-200 text-amber-800 dark:bg-amber-900 dark:text-amber-300">
                            <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <p class="text-xs font-medium text-amber-900 dark:text-amber-200">
                            Jadwal ujian sudah memasuki hari H atau telah lewat dari jadwal yang ditentukan, atau
                        </p>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <div class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-amber-200 text-amber-800 dark:bg-amber-900 dark:text-amber-300">
                            <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <p class="text-xs font-medium text-amber-900 dark:text-amber-200">
                            Sudah terdapat entri penilaian atau catatan wawancara yang dilakukan oleh tim penguji.
                        </p>
                    </div>
                </div>

                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Penghapusan hanya diizinkan untuk kelompok ujian berstatus terjadwal minimal 1 hari sebelum hari H (H-1) dan belum dilakukan penilaian sama sekali.
                </p>
            </div>

            <template #footer>
                <div class="flex justify-end">
                    <PrimaryButton
                        type="button"
                        @click="closeCannotDeleteModal"
                        class="w-full font-bold sm:w-auto"
                    >
                        Saya Mengerti
                    </PrimaryButton>
                </div>
            </template>
        </Modal>

        <!-- ======================================================= -->
        <!-- MODAL: KONFIRMASI HAPUS KELOMPOK INTERVIEW -->
        <!-- ======================================================= -->
        <Modal
            :show="isDeleteModalOpen"
            @close="closeDeleteModal"
            maxWidth="sm"
            title="Hapus Kelompok"
            description="Konfirmasi penghapusan kelompok."
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400"
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
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                    </svg>
                </div>
            </template>

            <div
                v-if="deleteTarget"
                class="text-center text-sm text-gray-600 dark:text-slate-300"
            >
                Apakah Anda yakin ingin menghapus kelompok ujian
                <strong class="font-bold text-gray-900 dark:text-slate-100">{{
                    deleteTarget.nama_kelompok
                }}</strong>?
                <p class="mt-2.5 text-xs font-medium text-amber-700 dark:text-amber-400">
                    Seluruh calon santri ({{ deleteTarget.total_peserta || 0 }} santri) yang tergabung di kelompok ini akan dikembalikan ke status <strong>INTERVIEW (Belum Dijadwalkan)</strong>.
                </p>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <SecondaryButton @click="closeDeleteModal" type="button">
                        Batal
                    </SecondaryButton>
                    <DangerButton
                        @click="submitDelete"
                        type="button"
                        :disabled="deleteForm.processing"
                        :loading="deleteForm.processing"
                    >
                        Hapus Kelompok
                    </DangerButton>
                </div>
            </template>
        </Modal>
    </div>
</template>
