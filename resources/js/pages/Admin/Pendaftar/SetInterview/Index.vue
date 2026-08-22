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
import PasswordInput from '@/Components/Form/PasswordInput.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { show } from '@/routes/admin/pendaftar';
import {
    index,
    create as createRoute,
    remove_schedule,
    reset_password,
    destroy as destroyRoute,
    bulk_destroy,
    exportMethod,
} from '@/routes/admin/pendaftar/set_interview';
import { getPendaftarStatusBadge } from '@/types/enums';

defineOptions({ layout: AdminLayout });

interface ActiveTahunAkademik {
    id: string;
    name: string;
    is_active: boolean;
}

interface JenjangItem {
    id: string;
    code?: string;
    name: string;
    singkatan?: string;
    logo_path?: string;
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

interface KelompokUjianItem {
    id: string;
    nama_kelompok: string;
    penguji_id?: string;
    tanggal_ujian?: string;
    waktu_mulai?: string;
    waktu_selesai?: string;
    lokasi?: string;
    pendaftars_count?: number;
    pengujis?: Array<{
        id: string;
        name: string;
    }>;
}

const props = defineProps<{
    pendaftars: any;
    jenjangs: JenjangItem[];
    jenjangCounts: Record<string, number>;
    selectedJenjangId?: string;
    cabangs: CabangItem[];
    activeTahunAkademik?: ActiveTahunAkademik | null;
    hasActiveTahunAkademik?: boolean;
    gelombangs?: any[];
    pengujis: PengujiItem[];
    koordinator?: PengujiItem[];
    pengawas?: PengujiItem[];
    kelompokUjians: KelompokUjianItem[];
    filters: {
        search: string;
        limit: number;
        jenjang_id: string;
        cabang_id: string;
        gelombang_id: string;
        gender: string;
        status_pembuatan_interview: string;
        penguji_id: string;
        start_date: string;
        end_date: string;
    };
}>();

// ==========================================
// JENJANG ORDER & HELPER
// ==========================================
const jenjangOrderMap: Record<string, number> = {
    MTS: 1,
    MA: 2,
    S1: 3,
    S2: 4,
    S3: 5,
};

const orderedJenjangs = computed(() => {
    if (!props.jenjangs) {
        return [];
    }

    return [...props.jenjangs].sort((a, b) => {
        const orderA =
            jenjangOrderMap[(a.code || a.singkatan || '').toUpperCase()] ?? 99;
        const orderB =
            jenjangOrderMap[(b.code || b.singkatan || '').toUpperCase()] ?? 99;

        return orderA - orderB;
    });
});

const activeJenjangId = computed(() => props.filters?.jenjang_id || '');

const totalInterviewCount = computed(() => {
    if (!props.jenjangCounts) {
        return 0;
    }

    return Object.values(props.jenjangCounts).reduce(
        (acc, count) => acc + (Number(count) || 0),
        0,
    );
});

const getJenjangLogo = (jenjangOrCode?: JenjangItem | string) => {
    if (typeof jenjangOrCode === 'object' && jenjangOrCode?.logo_path) {
        return jenjangOrCode.logo_path.startsWith('/')
            ? jenjangOrCode.logo_path
            : `/${jenjangOrCode.logo_path}`;
    }

    const code =
        typeof jenjangOrCode === 'string'
            ? jenjangOrCode
            : jenjangOrCode?.code || jenjangOrCode?.singkatan || '';
    const found = props.jenjangs?.find(
        (j) =>
            (j.code || j.singkatan || '').toUpperCase() ===
            (code || '').toUpperCase(),
    );

    if (found?.logo_path) {
        return found.logo_path.startsWith('/')
            ? found.logo_path
            : `/${found.logo_path}`;
    }

    const c = (code || '').toUpperCase();

    if (c === 'MTS') {
return '/image/logos/jenjang/logo-mts.png';
}

    if (c === 'MA') {
return '/image/logos/jenjang/logo-ma.png';
}

    if (c === 'S1') {
return '/image/logos/jenjang/logo-s1.png';
}

    if (c === 'S2') {
return '/image/logos/jenjang/logo-s2.png';
}

    if (c === 'S3') {
return '/image/logos/jenjang/logo-s3.png';
}

    return '/image/logos/logo-1.png';
};

const getEducationSubText = (row: any) => {
    const code = (row.jenjang?.code || '').toUpperCase();
    const edu = row.education_data || {};

    if (code === 'MTS') {
        if (edu.kelas_tingkat) {
return `Kelas ${edu.kelas_tingkat}`;
}

        return row.tipe_pendaftaran === 'Pindahan' ? 'Pindahan' : 'Kelas VII';
    }

    if (code === 'MA') {
        const jurusan = edu.jurusan_ma || edu.jurusan;

        if (jurusan) {
return `Jurusan ${jurusan}`;
}

        if (edu.kelas_tingkat) {
return `Kelas ${edu.kelas_tingkat}`;
}

        return row.tipe_pendaftaran === 'Pindahan' ? 'Pindahan' : 'Kelas X';
    }

    // S1, S2, S3
    const prodi = edu.fakultas_prodi_utama || edu.prodi_utama || edu.prodi;

    if (prodi) {
return `Prodi: ${prodi}`;
}

    return row.tipe_pendaftaran === 'Pindahan' ? 'Pindahan' : 'Reguler';
};

const formatStatCount = (val: any) => {
    const num = Number(val);

    return isNaN(num) ? '0' : num.toLocaleString('id-ID');
};

const toggleJenjangFilter = (id: string) => {
    selectedRows.value = [];
    const newJenjangId = activeJenjangId.value === id ? '' : id;

    router.get(
        index.url(),
        {
            ...props.filters,
            jenjang_id: newJenjangId,
            page: 1,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

// ==========================================
// DATATABLE COLUMNS DEFINITION
// ==========================================
const columns = [
    { key: 'nomor_pendaftaran', label: 'NO REGISTRASI', sortable: true },
    { key: 'pendaftar', label: 'CALON SANTRI', sortable: true },
    { key: 'gender', label: 'GENDER', sortable: true },
    { key: 'kontak', label: 'KONTAK', sortable: false },
    { key: 'asal_daerah', label: 'ASAL DAERAH', sortable: false },
    { key: 'cabang', label: 'CABANG', sortable: false },
    { key: 'jenjang', label: 'JENJANG', sortable: false },
    { key: 'status', label: 'STATUS', sortable: true },
];

// ==========================================
// SEARCH & FILTER HANDLING
// ==========================================
const search = ref(props.filters.search || '');
const isFilterModalOpen = ref(false);

const filterForm = useForm({
    jenjang_id: props.filters.jenjang_id || '',
    cabang_id: props.filters.cabang_id || '',
    gelombang_id: props.filters.gelombang_id || '',
    gender: props.filters.gender || '',
    status_pembuatan_interview: props.filters.status_pembuatan_interview || '',
    penguji_id: props.filters.penguji_id || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

// Keep filterForm values in sync with URL props
watch(
    () => props.filters,
    (newFilters) => {
        filterForm.jenjang_id = newFilters.jenjang_id || '';
        filterForm.cabang_id = newFilters.cabang_id || '';
        filterForm.gelombang_id = newFilters.gelombang_id || '';
        filterForm.gender = newFilters.gender || '';
        filterForm.status_pembuatan_interview = newFilters.status_pembuatan_interview || '';
        filterForm.penguji_id = newFilters.penguji_id || '';
        filterForm.start_date = newFilters.start_date || '';
        filterForm.end_date = newFilters.end_date || '';
    },
    { deep: true, immediate: true },
);

const onSelectGelombang = (gelombangId: string) => {
    filterForm.gelombang_id = gelombangId;
    applyFilters();
};

const isFilterActive = computed(() => {
    return (
        Boolean(props.filters.cabang_id) ||
        Boolean(props.filters.gender) ||
        Boolean(props.filters.status_pembuatan_interview) ||
        Boolean(props.filters.penguji_id) ||
        Boolean(props.filters.start_date) ||
        Boolean(props.filters.end_date)
    );
});

const applyFilters = () => {
    isFilterModalOpen.value = false;
    selectedRows.value = [];
    router.get(
        index.url(),
        {
            search: search.value,
            limit: props.filters.limit,
            jenjang_id: filterForm.jenjang_id,
            cabang_id: filterForm.cabang_id,
            gelombang_id: filterForm.gelombang_id,
            gender: filterForm.gender,
            status_pembuatan_interview: filterForm.status_pembuatan_interview,
            penguji_id: filterForm.penguji_id,
            start_date: filterForm.start_date,
            end_date: filterForm.end_date,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const resetFilters = () => {
    filterForm.cabang_id = '';
    filterForm.gender = '';
    filterForm.status_pembuatan_interview = '';
    filterForm.penguji_id = '';
    filterForm.start_date = '';
    filterForm.end_date = '';
    isFilterModalOpen.value = false;
    selectedRows.value = [];
    router.get(
        index.url(),
        {
            search: search.value,
            limit: props.filters.limit,
            jenjang_id: filterForm.jenjang_id,
            gelombang_id: filterForm.gelombang_id,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const onSearchInput = (query: string) => {
    search.value = query;
    router.get(
        index.url(),
        {
            ...props.filters,
            search: query,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const onLimitChange = (newLimit: number) => {
    router.get(
        index.url(),
        {
            ...props.filters,
            limit: newLimit,
        },
        { preserveState: true, preserveScroll: true },
    );
};

// ==========================================
// ROW SELECTION & BULK ACTIONS
// ==========================================
const selectedRows = ref<string[]>([]);

const handleSelection = (ids: string[]) => {
    selectedRows.value = ids;
};

// ==========================================
// NAVIGASI BUAT JADWAL INTERVIEW
// ==========================================
const navigateToCreatePage = (ids?: string[]) => {
    const targetIds = ids && ids.length > 0 ? ids : selectedRows.value;
    const queryParams: Record<string, any> = {};

    if (targetIds && targetIds.length > 0) {
        queryParams.ids = targetIds.join(',');
    }

    if (activeJenjangId.value) {
        queryParams.jenjang_id = activeJenjangId.value;
    }

    router.get(createRoute.url({ query: queryParams }));
};



// ==========================================
// RESET PASSWORD MODAL
// ==========================================
const isResetPasswordModalOpen = ref(false);
const resetPasswordTarget = ref<any>(null);

const resetPasswordForm = useForm({
    password: '',
    password_confirmation: '',
});

const openResetPasswordModal = (row: any) => {
    resetPasswordTarget.value = row;
    resetPasswordForm.reset();
    resetPasswordForm.clearErrors();
    isResetPasswordModalOpen.value = true;
};

const closeResetPasswordModal = () => {
    isResetPasswordModalOpen.value = false;
    resetPasswordTarget.value = null;
    resetPasswordForm.reset();
    resetPasswordForm.clearErrors();
};

const autofillBirthdatePassword = () => {
    const tgl = resetPasswordTarget.value?.personal_data?.tanggal_lahir;

    if (tgl) {
        const clean = String(tgl).replace(/[^0-9]/g, '');

        if (clean.length >= 6) {
            resetPasswordForm.password = clean;
            resetPasswordForm.password_confirmation = clean;
        }
    }
};

const submitResetPassword = () => {
    if (!resetPasswordTarget.value) {
return;
}

    resetPasswordForm.post(
        reset_password.url({ pendaftar: resetPasswordTarget.value.id }),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeResetPasswordModal();
            },
        },
    );
};

// ==========================================
// DELETE MODAL (SINGLE & BULK)
// ==========================================
const isDeleteModalOpen = ref(false);
const deleteTarget = ref<any>(null);

const deleteForm = useForm({});

const openDeleteModal = (row: any) => {
    deleteTarget.value = row;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    deleteTarget.value = null;
};

const submitDelete = () => {
    if (!deleteTarget.value) {
return;
}

    deleteForm.delete(destroyRoute.url({ pendaftar: deleteTarget.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
        },
    });
};

const isBulkDeleteModalOpen = ref(false);

const bulkDeleteForm = useForm({
    ids: [] as string[],
});

const openBulkDeleteModal = () => {
    if (selectedRows.value.length === 0) {
return;
}

    bulkDeleteForm.ids = [...selectedRows.value];
    isBulkDeleteModalOpen.value = true;
};

const closeBulkDeleteModal = () => {
    isBulkDeleteModalOpen.value = false;
    bulkDeleteForm.reset();
};

const submitBulkDelete = () => {
    bulkDeleteForm.post(bulk_destroy.url(), {
        preserveScroll: true,
        onSuccess: () => {
            closeBulkDeleteModal();
            selectedRows.value = [];
        },
    });
};

// ==========================================
// CETAK KARTU (NEW TAB)
// ==========================================
const openPrintCard = (row: any) => {
    window.open(`/admin/pendaftar/${row.id}/cetak-kartu`, '_blank');
};

// ==========================================
// EXPORT DATA (UNIFIED DIRECT EXPORT)
// ==========================================
const handleExport = () => {
    let url = exportMethod.url();
    const params = new URLSearchParams();

    if (selectedRows.value.length > 0) {
        params.append('ids', selectedRows.value.join(','));
    } else {
        if (activeJenjangId.value) {
params.append('jenjang_id', activeJenjangId.value);
}

        if (props.filters.search) {
params.append('search', props.filters.search);
}

        if (props.filters.cabang_id) {
params.append('cabang_id', props.filters.cabang_id);
}

        if (props.filters.periode_id) {
params.append('periode_id', props.filters.periode_id);
}

        if (props.filters.gelombang_id) {
params.append('gelombang_id', props.filters.gelombang_id);
}

        if (props.filters.gender) {
params.append('gender', props.filters.gender);
}

        if (props.filters.status_pembuatan_interview) {
params.append('status_pembuatan_interview', props.filters.status_pembuatan_interview);
}

        if (props.filters.penguji_id) {
params.append('penguji_id', props.filters.penguji_id);
}

        if (props.filters.start_date) {
params.append('start_date', props.filters.start_date);
}

        if (props.filters.end_date) {
params.append('end_date', props.filters.end_date);
}
    }

    if (params.toString()) {
        url += '?' + params.toString();
    }

    window.location.href = url;
};

// Helpers for UI display
const formatDateTime = (dateStr?: string) => {
    if (!dateStr) {
return '-';
}

    try {
        const d = new Date(dateStr);

        if (isNaN(d.getTime())) {
return dateStr;
}

        return d.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateStr;
    }
};

const imageErrorMap = ref<Record<string, boolean>>({});

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
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
};

const getDetailUrl = (id: string) => {
    let currentUrl = '/admin/pendaftar/set-interview';

    if (typeof window !== 'undefined') {
        const searchParams = window.location.search;
        currentUrl +=
            searchParams ||
            (activeJenjangId.value
                ? `?jenjang_id=${activeJenjangId.value}`
                : '');
    } else if (activeJenjangId.value) {
        currentUrl += `?jenjang_id=${activeJenjangId.value}`;
    }

    return `${show.url(id)}?from=${encodeURIComponent(currentUrl)}`;
};

const hasKelompok = (row: any) => {
    return Array.isArray(row.kelompok_ujians) && row.kelompok_ujians.length > 0;
};
</script>

<template>
    <div class="relative min-h-screen w-full">
        <Head title="Set Interview" />

        <!-- Main Page Header -->
        <div class="mb-6 flex flex-col gap-1">
            <h1
                class="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl dark:text-slate-100"
            >
                Set Interview
            </h1>
            <p
                class="max-w-2xl text-xs leading-relaxed text-gray-500 sm:text-sm dark:text-slate-400"
            >
                Kelola antrean data calon santri yang siap dijadwalkan untuk
                proses seleksi ujian dan interview.
            </p>
        </div>

        <!-- Academic Year & Wave Cards (With Inactive TA Overlay Blur) -->
        <AkademikWaveFilterCards
            :active-tahun-akademik="props.activeTahunAkademik"
            :has-active-tahun-akademik="props.hasActiveTahunAkademik"
            :gelombangs="props.gelombangs"
            :selected-gelombang-id="filterForm.gelombang_id"
            @select-gelombang="onSelectGelombang"
        />

        <!-- All Jenjang Statistics Overview Grid (Click card to auto-filter) -->
        <div
            class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5"
        >
            <div
                v-for="j in orderedJenjangs"
                :key="j.id"
                @click="toggleJenjangFilter(j.id)"
                class="group relative flex cursor-pointer flex-col justify-between rounded-2xl border p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                :class="[
                    activeJenjangId === j.id
                        ? 'border-primary bg-gradient-to-b from-primary/[0.04] to-white shadow-md ring-2 shadow-primary/10 ring-primary/20 dark:border-blue-500/60 dark:from-blue-500/10 dark:to-slate-900/80 dark:ring-blue-500/20'
                        : 'border-gray-200 bg-white shadow-xs hover:border-slate-300 dark:border-slate-700/80 dark:bg-slate-900 dark:hover:border-slate-700',
                ]"
            >
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <img
                            :src="getJenjangLogo(j.code)"
                            :alt="j.name"
                            class="h-7 w-auto shrink-0 object-contain transition-transform duration-200 group-hover:scale-105"
                        />
                        <span
                            class="rounded-md px-2 py-0.5 text-[10px] font-black tracking-wider uppercase transition-colors"
                            :class="[
                                activeJenjangId === j.id
                                    ? 'bg-primary text-white dark:bg-blue-600'
                                    : 'bg-slate-100 text-slate-600 group-hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:group-hover:bg-slate-700',
                            ]"
                        >
                            {{ j.code || j.singkatan }}
                        </span>
                    </div>

                    <div class="mt-3">
                        <h4
                            class="truncate text-xs font-bold text-gray-900 dark:text-slate-100"
                            :title="j.name"
                        >
                            {{ j.name }}
                        </h4>
                        <div class="mt-1 flex items-baseline gap-1.5">
                            <span
                                class="font-mono text-xl font-black tracking-tight text-gray-900 dark:text-slate-100"
                            >
                                {{ formatStatCount(props.jenjangCounts[j.id]) }}
                            </span>
                            <span
                                class="text-[11px] font-semibold text-gray-400 dark:text-slate-500"
                            >
                                Interview
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Direct Action Button / Status Badge -->
                <div class="mt-3 border-t border-gray-100 pt-2 dark:border-slate-800">
                    <div
                        v-if="activeJenjangId === j.id"
                        class="flex items-center justify-between text-[11px] font-bold text-primary dark:text-blue-400"
                    >
                        <span>Sedang Aktif</span>
                        <svg
                            class="h-3.5 w-3.5"
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
                    </div>
                    <button
                        v-else
                        type="button"
                        @click.stop="toggleJenjangFilter(j.id)"
                        class="flex w-full cursor-pointer items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-2.5 py-1.5 text-[11px] font-bold text-gray-600 transition-all group-hover:border-primary/40 group-hover:bg-primary/5 group-hover:text-primary dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-300 dark:group-hover:border-blue-500/40 dark:group-hover:bg-primary/20 dark:group-hover:text-blue-300"
                    >
                        <span>Filter Jenjang</span>
                        <svg
                            class="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-0.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <!-- Data Table -->
            <DataTable
                :columns="columns"
                :data="props.pendaftars.data"
                :pagination="props.pendaftars"
                :selectable="true"
                @search="onSearchInput"
                @limit="onLimitChange"
                @selection-change="handleSelection"
            >
                <template #bulk-actions="{ selectedIds }">
                    <div
                        v-if="selectedIds.length > 0"
                        class="flex items-center gap-2"
                    >
                        <!-- Bulk Action: Hapus -->
                        <button
                            type="button"
                            @click="openBulkDeleteModal"
                            class="group inline-flex cursor-pointer items-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm font-bold text-rose-700 shadow-sm transition-all hover:bg-rose-100 focus:ring-2 focus:ring-rose-500/20 focus:outline-none sm:px-4 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-300 dark:hover:bg-rose-900/50"
                            title="Hapus data terpilih"
                        >
                            <svg
                                class="h-4 w-4 text-rose-600 sm:mr-2 dark:text-rose-400"
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
                            <span class="hidden sm:inline">Hapus</span>
                        </button>
                    </div>
                </template>

                <template #filters>
                    <div class="flex items-center gap-2">
                        <!-- Unified Export Trigger Button -->
                        <button
                            type="button"
                            @click="handleExport"
                            class="group inline-flex cursor-pointer items-center rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50 focus:ring-2 focus:ring-primary/20 focus:outline-none sm:px-4 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                            :title="
                                selectedRows.length > 0
                                    ? `Export ${selectedRows.length} data terpilih`
                                    : 'Export data'
                            "
                        >
                            <svg
                                class="h-4 w-4 text-gray-400 transition-colors group-hover:text-primary dark:text-slate-500 dark:group-hover:text-blue-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>
                            <span class="ml-2 hidden sm:inline">Export</span>
                        </button>

                        <!-- Trigger Button in Toolbar -->
                        <button
                            @click="isFilterModalOpen = true"
                            class="group inline-flex cursor-pointer items-center rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50 focus:ring-2 focus:ring-primary/20 focus:outline-none sm:px-4 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            <svg
                                class="h-4 w-4 text-gray-400 transition-colors group-hover:text-primary dark:text-slate-500 dark:group-hover:text-blue-400"
                                :class="
                                    isFilterActive
                                        ? 'text-primary dark:text-blue-400'
                                        : ''
                                "
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                                />
                            </svg>
                            <span class="ml-2 hidden sm:inline">Filter</span>
                            <span
                                v-if="isFilterActive"
                                class="ml-1.5 h-2 w-2 animate-pulse rounded-full bg-primary sm:ml-2 dark:bg-blue-400"
                            ></span>
                        </button>
                    </div>

                    <!-- Filter Modal inside slot -->
                    <FilterModal
                        :show="isFilterModalOpen"
                        title="Filter Data Set Interview"
                        description="Saring data antrean calon santri berdasarkan status penjadwalan & penguji"
                        max-width="lg"
                        @close="isFilterModalOpen = false"
                        @reset="resetFilters"
                        @apply="applyFilters"
                    >
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label
                                    class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    Cabang Pendaftaran
                                </label>
                                <CustomSelect
                                    v-model="filterForm.cabang_id"
                                    :options="
                                        props.cabangs.map((c) => ({
                                            value: c.id,
                                            label: c.name,
                                        }))
                                    "
                                    placeholder="Semua Cabang"
                                />
                            </div>

                            <div>
                                <label
                                    class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    Jenis Kelamin
                                </label>
                                <CustomSelect
                                    v-model="filterForm.gender"
                                    :options="[
                                        { value: 'Laki-Laki', label: 'Laki-Laki' },
                                        { value: 'Perempuan', label: 'Perempuan' },
                                    ]"
                                    placeholder="Semua Jenis Kelamin"
                                />
                            </div>

                            <div>
                                <label
                                    class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    Status Penjadwalan
                                </label>
                                <CustomSelect
                                    v-model="filterForm.status_pembuatan_interview"
                                    :options="[
                                        {
                                            value: 'dijadwalkan',
                                            label: 'Sudah Masuk Kelompok',
                                        },
                                        {
                                            value: 'belum',
                                            label: 'Belum Dijadwalkan (Antrean)',
                                        },
                                    ]"
                                    placeholder="Semua Status"
                                />
                            </div>

                            <div>
                                <label
                                    class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    Penguji / Asesor
                                </label>
                                <CustomSelect
                                    v-model="filterForm.penguji_id"
                                    :options="
                                        props.pengujis.map((p) => ({
                                            value: p.id,
                                            label: p.name,
                                        }))
                                    "
                                    placeholder="Semua Penguji"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <label
                                    class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    Rentang Tanggal Penjadwalan
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
                </template>

                <template #actions>
                    <button
                        type="button"
                        @click="navigateToCreatePage()"
                        class="group inline-flex cursor-pointer items-center rounded-xl bg-primary px-3.5 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-primary-dark focus:ring-2 focus:ring-primary/20 focus:outline-none dark:bg-blue-600 dark:hover:bg-blue-500"
                        :title="
                            selectedRows.length > 0
                                ? `Buat jadwal untuk ${selectedRows.length} calon terpilih`
                                : 'Buat jadwal ujian/interview'
                        "
                    >
                        <svg
                            class="mr-2 h-4 w-4 text-white"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        <span>Buat Jadwal</span>
                    </button>
                </template>

                <!-- Column: NO REGISTRASI -->
                <template #cell-nomor_pendaftaran="{ row }">
                    <div class="flex flex-col">
                        <span
                            class="font-mono text-[14px] font-bold text-primary-dark dark:text-blue-400"
                        >
                            {{ row.nomor_pendaftaran || '-' }}
                        </span>
                        <span
                            class="mt-0.5 text-[12px] text-slate-400 dark:text-slate-500"
                        >
                            {{
                                formatDateTime(
                                    row.submitted_at || row.created_at,
                                )
                            }}
                        </span>
                    </div>
                </template>

                <!-- Column: CALON SANTRI -->
                <template #cell-pendaftar="{ row }">
                    <div class="flex items-center gap-4">
                        <img
                            v-if="getPendaftarPhoto(row) && !imageErrorMap[row.id]"
                            :src="getPendaftarPhoto(row)!"
                            @error="imageErrorMap[row.id] = true"
                            class="h-11 w-11 shrink-0 rounded-full border border-gray-100 object-cover shadow-xs dark:border-slate-800"
                        />
                        <div
                            v-else
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-emerald-200 bg-emerald-100 text-lg font-bold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300"
                        >
                            {{ getInitials(row.nama) }}
                        </div>
                        <div>
                            <Link
                                :href="getDetailUrl(row.id)"
                                class="text-left text-[15px] font-bold text-slate-800 transition-colors hover:text-primary dark:text-slate-100 dark:hover:text-blue-400"
                            >
                                {{ row.nama }}
                            </Link>
                            <p
                                class="mt-0.5 font-mono text-[13px] text-slate-500 dark:text-slate-400"
                            >
                                NIK: {{ row.nik || '-' }}
                            </p>
                        </div>
                    </div>
                </template>

                <!-- Column: GENDER / JENIS KELAMIN -->
                <template #cell-gender="{ row }">
                    <div
                        v-if="
                            (row.personal_data?.jenis_kelamin || row.gender)
                                ?.toLowerCase()
                                .includes('laki') ||
                            (
                                row.personal_data?.jenis_kelamin || row.gender
                            )?.toLowerCase() === 'l'
                        "
                        class="flex items-center text-[13px] font-medium text-blue-600 dark:text-blue-400"
                    >
                        <svg
                            class="mr-1.5 h-4 w-4 shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 8v-4h-4M16 4l-5 5"
                            />
                            <circle cx="9" cy="13" r="5" stroke-width="2" />
                        </svg>
                        Laki-Laki
                    </div>
                    <div
                        v-else-if="
                            (row.personal_data?.jenis_kelamin || row.gender)
                                ?.toLowerCase()
                                .includes('perempuan') ||
                            (
                                row.personal_data?.jenis_kelamin || row.gender
                            )?.toLowerCase() === 'p'
                        "
                        class="flex items-center text-[13px] font-medium text-pink-600 dark:text-pink-400"
                    >
                        <svg
                            class="mr-1.5 h-4 w-4 shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 15v6m-3-3h6"
                            />
                            <circle cx="12" cy="9" r="6" stroke-width="2" />
                        </svg>
                        Perempuan
                    </div>
                    <span
                        v-else
                        class="text-[13px] text-gray-400 dark:text-slate-500"
                        >-</span
                    >
                </template>

                <!-- Column: KONTAK -->
                <template #cell-kontak="{ row }">
                    <div class="flex flex-col gap-1.5 text-[13px]">
                        <div
                            class="flex items-center text-slate-600 dark:text-slate-300"
                        >
                            <svg
                                class="mr-2 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                />
                            </svg>
                            <span>{{
                                row.nomor_hp ||
                                row.personal_data?.no_whatsapp ||
                                row.personal_data?.nomor_hp ||
                                '-'
                            }}</span>
                        </div>
                        <div
                            v-if="row.email"
                            class="flex items-center text-slate-500 dark:text-slate-400"
                        >
                            <svg
                                class="mr-2 h-4 w-4 shrink-0 text-slate-400 dark:text-slate-500"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                />
                            </svg>
                            <span class="max-w-48 truncate">{{
                                row.email
                            }}</span>
                        </div>
                    </div>
                </template>

                <!-- Column: ASAL DAERAH / DOMISILI -->
                <template #cell-asal_daerah="{ row }">
                    <div class="flex flex-col text-[13px]">
                        <span
                            class="font-bold text-slate-800 dark:text-slate-100"
                        >
                            {{ row.address_data?.kabupaten_kota || '-' }}
                        </span>
                        <span
                            class="text-[12px] text-slate-500 dark:text-slate-400"
                        >
                            {{ row.address_data?.provinsi || '-' }}
                        </span>
                    </div>
                </template>

                <!-- Column: CABANG -->
                <template #cell-cabang="{ row }">
                    <span
                        class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                    >
                        {{
                            row.cabang?.name ||
                            row.personal_data?.cabang_pendaftaran ||
                            '-'
                        }}
                    </span>
                </template>

                <!-- Column: JENJANG -->
                <template #cell-jenjang="{ row }">
                    <div class="flex items-center gap-2.5">
                        <img
                            :src="getJenjangLogo(row.jenjang?.code)"
                            :alt="row.jenjang?.name"
                            class="h-6 w-6 shrink-0 object-contain"
                        />
                        <div class="flex flex-col">
                            <span
                                class="text-[13px] font-bold text-slate-800 dark:text-slate-100"
                            >
                                {{ row.jenjang?.name || '-' }}
                            </span>
                            <span
                                v-if="getEducationSubText(row)"
                                class="max-w-[180px] truncate text-[12px] text-slate-400 dark:text-slate-500"
                                :title="getEducationSubText(row)"
                            >
                                {{ getEducationSubText(row) }}
                            </span>
                        </div>
                    </div>
                </template>

                <!-- Column: STATUS -->
                <template #cell-status="{ row }">
                    <span
                        :class="[
                            'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold tracking-wider uppercase',
                            getPendaftarStatusBadge(row.status).classes,
                        ]"
                    >
                        {{ getPendaftarStatusBadge(row.status).label }}
                    </span>
                </template>

                <!-- Column: AKSI -->
                <template #row-actions="{ row }">
                    <div class="flex justify-end">
                        <ActionMenu width="52">
                            <template #trigger>
                                <button
                                    class="cursor-pointer rounded-full border border-transparent p-2 text-gray-500 transition-colors hover:border-gray-200 hover:bg-gray-100 hover:text-gray-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:bg-slate-700 dark:hover:text-slate-200"
                                    title="Opsi Aksi"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"
                                        />
                                    </svg>
                                </button>
                            </template>
                            <template #content>
                                <!-- Primary Action: BUAT JADWAL -->
                                <button
                                    @click="navigateToCreatePage([row.id])"
                                    class="flex w-full items-center bg-primary/5 px-3 py-2.5 text-left text-sm font-bold text-primary transition-colors hover:bg-primary/10 sm:px-4 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
                                >
                                    <svg
                                        class="mr-3 h-4 w-4 text-primary dark:text-blue-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 4v16m8-8H4"
                                        />
                                    </svg>
                                    Buat Jadwal
                                </button>

                                <div
                                    class="my-1 border-t border-gray-100 dark:border-slate-800"
                                ></div>

                                <!-- Action 2: DETAIL SANTRI (PROFILE) -->
                                <Link
                                    :href="getDetailUrl(row.id)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                >
                                    <svg
                                        class="mr-3 h-4 w-4 text-sky-500 dark:text-sky-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        />
                                    </svg>
                                    Detail
                                </Link>

                                <!-- Action 3: CETAK KARTU -->
                                <button
                                    @click="openPrintCard(row)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                >
                                    <svg
                                        class="mr-3 h-4 w-4 text-emerald-500 dark:text-emerald-400"
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
                                    Cetak Kartu
                                </button>

                                <!-- Action 4: SANDI (RESET PASSWORD) -->
                                <button
                                    @click="openResetPasswordModal(row)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                >
                                    <svg
                                        class="mr-3 h-4 w-4 text-amber-500 dark:text-amber-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                                        />
                                    </svg>
                                    Reset Sandi
                                </button>

                                <div
                                    class="my-1 border-t border-gray-100 dark:border-slate-800"
                                ></div>

                                <!-- Action 5: HAPUS PENDAFTAR -->
                                <button
                                    @click="openDeleteModal(row)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-rose-600 transition-colors hover:bg-rose-50 sm:px-4 dark:text-rose-400 dark:hover:bg-rose-950/50"
                                >
                                    <svg
                                        class="mr-3 h-4 w-4 text-rose-500 dark:text-rose-400"
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
                                    Hapus
                                </button>
                            </template>
                        </ActionMenu>
                    </div>
                </template>
            </DataTable>
        </div>



        <!-- ======================================================= -->
        <!-- MODAL: RESET PASSWORD -->
        <!-- ======================================================= -->
        <Modal
            :show="isResetPasswordModalOpen"
            @close="closeResetPasswordModal"
            maxWidth="md"
            title="Reset Kata Sandi Pendaftar"
            description="Perbarui kata sandi akun pendaftar calon santri."
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400"
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
                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                        />
                    </svg>
                </div>
            </template>

            <form @submit.prevent="submitResetPassword" class="space-y-4">
                <div
                    v-if="resetPasswordTarget"
                    class="rounded-xl border border-gray-100 bg-gray-50 p-3.5 text-xs text-gray-700 dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-800/70 dark:text-slate-200 dark:text-slate-300"
                >
                    <div>
                        Pendaftar:
                        <strong class="text-gray-900 dark:text-slate-100">{{
                            resetPasswordTarget.nama
                        }}</strong>
                    </div>
                    <div class="mt-0.5">
                        NIK:
                        <strong
                            class="font-mono text-gray-900 dark:text-slate-100"
                            >{{ resetPasswordTarget.nik || '-' }}</strong
                        >
                    </div>
                    <div
                        v-if="resetPasswordTarget.personal_data?.tanggal_lahir"
                        class="mt-2 flex items-center justify-between border-t border-gray-200 pt-2 dark:border-slate-700/60 dark:border-slate-800"
                    >
                        <span class="text-gray-500 dark:text-slate-400"
                            >Tgl Lahir:
                            {{
                                resetPasswordTarget.personal_data.tanggal_lahir
                            }}</span
                        >
                        <button
                            type="button"
                            @click="autofillBirthdatePassword"
                            class="cursor-pointer text-[11px] font-bold text-primary hover:underline dark:text-blue-400"
                        >
                            Gunakan Tgl Lahir
                        </button>
                    </div>
                </div>

                <PasswordInput
                    label="Kata Sandi Baru"
                    v-model="resetPasswordForm.password"
                    :error="resetPasswordForm.errors.password"
                    placeholder="Minimal 6 karakter"
                    required
                />

                <PasswordInput
                    label="Konfirmasi Kata Sandi Baru"
                    v-model="resetPasswordForm.password_confirmation"
                    :error="resetPasswordForm.errors.password_confirmation"
                    placeholder="Ulangi kata sandi baru"
                    required
                />
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <SecondaryButton
                        @click="closeResetPasswordModal"
                        type="button"
                    >
                        Batal
                    </SecondaryButton>
                    <PrimaryButton
                        @click="submitResetPassword"
                        type="button"
                        :disabled="resetPasswordForm.processing"
                        :loading="resetPasswordForm.processing"
                    >
                        Simpan Password Baru
                    </PrimaryButton>
                </div>
            </template>
        </Modal>



        <!-- ======================================================= -->
        <!-- MODAL: KONFIRMASI HAPUS (SINGLE) -->
        <!-- ======================================================= -->
        <Modal
            :show="isDeleteModalOpen"
            @close="closeDeleteModal"
            maxWidth="sm"
            title="Hapus Pendaftar Interview"
            description="Konfirmasi penghapusan data pendaftar."
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
                Apakah Anda yakin ingin menghapus data pendaftar interview
                <strong class="font-bold text-gray-900 dark:text-slate-100">{{
                    deleteTarget.nama
                }}</strong
                >?
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
                        Hapus Pendaftar
                    </DangerButton>
                </div>
            </template>
        </Modal>

        <!-- ======================================================= -->
        <!-- MODAL: BULK DELETE CONFIRMATION -->
        <!-- ======================================================= -->
        <Modal
            :show="isBulkDeleteModalOpen"
            @close="closeBulkDeleteModal"
            maxWidth="sm"
            title="Hapus Massal Pendaftar"
            description="Konfirmasi penghapusan seluruh data terpilih."
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

            <div class="text-center text-sm text-gray-600 dark:text-slate-300">
                Apakah Anda yakin ingin menghapus sebanyak
                <strong class="font-bold text-rose-600 dark:text-rose-400"
                    >{{ selectedRows.length }} data pendaftar interview</strong
                >
                yang telah dipilih?
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <SecondaryButton
                        @click="closeBulkDeleteModal"
                        type="button"
                    >
                        Batal
                    </SecondaryButton>
                    <DangerButton
                        @click="submitBulkDelete"
                        type="button"
                        :disabled="bulkDeleteForm.processing"
                        :loading="bulkDeleteForm.processing"
                    >
                        Hapus {{ selectedRows.length }} Data
                    </DangerButton>
                </div>
            </template>
        </Modal>
    </div>
</template>
