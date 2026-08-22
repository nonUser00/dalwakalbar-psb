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
import PenentuanKelasModal from '@/pages/Admin/Pendaftar/PenilaianInterview/Partials/PenentuanKelasModal.vue';
import { show } from '@/routes/admin/pendaftar';
import {
    index,
    reinterview,
    reset_password,
    destroy as destroyRoute,
    bulk_destroy,
    exportMethod,
} from '@/routes/admin/pendaftar/pengumuman';

defineOptions({ layout: AdminLayout });

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

interface ActiveTahunAkademik {
    id: string;
    name: string;
    is_active: boolean;
}

interface GelombangItem {
    id: string;
    periode_id?: string;
    name?: string;
    nama_gelombang?: string;
    periode_name?: string;
    periode_status?: string;
    is_open?: boolean;
    is_in_range?: boolean;
    is_currently_open?: boolean;
    start_date?: string;
    end_date?: string;
    start_date_raw?: string;
    end_date_raw?: string;
    periode?: {
        id?: string;
        name?: string;
        status?: string;
    };
}

interface KelompokUjianItem {
    id: string;
    nama_kelompok: string;
    tanggal_ujian?: string;
    waktu_mulai?: string;
    waktu_selesai?: string;
    lokasi?: string;
    status?: string;
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
    hasActiveTahunAkademik: boolean;
    gelombangs: GelombangItem[];
    kelompokUjians: KelompokUjianItem[];
    filters: {
        search: string;
        limit: number;
        jenjang_id: string;
        cabang_id: string;
        gelombang_id: string;
        gender: string;
        status_kelulusan: string;
        status: string;
        kelompok_ujian_id: string;
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
        const orderA = jenjangOrderMap[(a.code || a.singkatan || '').toUpperCase()] ?? 99;
        const orderB = jenjangOrderMap[(b.code || b.singkatan || '').toUpperCase()] ?? 99;

        return orderA - orderB;
    });
});

const activeJenjangId = computed(() => props.filters?.jenjang_id || '');

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
        (j) => (j.code || j.singkatan || '').toUpperCase() === (code || '').toUpperCase(),
    );

    if (found?.logo_path) {
        return found.logo_path.startsWith('/') ? found.logo_path : `/${found.logo_path}`;
    }

    const c = (code || '').toUpperCase();
    if (c === 'MTS') return '/image/logos/jenjang/logo-mts.png';
    if (c === 'MA') return '/image/logos/jenjang/logo-ma.png';
    if (c === 'S1') return '/image/logos/jenjang/logo-s1.png';
    if (c === 'S2') return '/image/logos/jenjang/logo-s2.png';
    if (c === 'S3') return '/image/logos/jenjang/logo-s3.png';

    return '/image/logos/logo-1.png';
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
        { preserveState: true, preserveScroll: true },
    );
};

// ==========================================
// TABLE CONFIGURATION & COLUMNS
// Note: 'Aksi' column is generated automatically by DataTable.vue
// ==========================================
const columns = [
    { key: 'nomor_pendaftaran', label: 'NO REGISTRASI', sortable: true },
    { key: 'pendaftar', label: 'CALON SANTRI', sortable: true },
    { key: 'hasil_wawancara', label: 'HASIL WAWANCARA', sortable: false },
    { key: 'tes_membaca', label: 'TES MEMBACA', sortable: false },
    { key: 'tes_menulis', label: 'TES MENULIS', sortable: false },
    { key: 'tes_hafalan', label: 'TES HAFALAN', sortable: false },
    { key: 'kelas', label: 'KELAS', sortable: false },
    { key: 'status_kelulusan', label: 'LULUS / TIDAK', sortable: true },
];

// ==========================================
// SEARCH & FILTER HANDLING
// ==========================================
const search = ref(props.filters.search || '');
let searchTimeout: any = null;
const isFilterModalOpen = ref(false);

const filterForm = useForm({
    cabang_id: props.filters.cabang_id || '',
    gelombang_id: props.filters.gelombang_id || '',
    status_kelulusan: props.filters.status_kelulusan || '',
    gender: props.filters.gender || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

watch(
    () => props.filters,
    (newFilters) => {
        filterForm.cabang_id = newFilters.cabang_id || '';
        filterForm.gelombang_id = newFilters.gelombang_id || '';
        filterForm.status_kelulusan = newFilters.status_kelulusan || '';
        filterForm.gender = newFilters.gender || '';
        filterForm.start_date = newFilters.start_date || '';
        filterForm.end_date = newFilters.end_date || '';
    },
    { deep: true, immediate: true },
);

const onSelectGelombang = (gelombangId: string) => {
    filterForm.gelombang_id = gelombangId;
    applyGelombangFilter();
};

const isFilterActive = computed(() => {
    return (
        Boolean(filterForm.cabang_id) ||
        Boolean(filterForm.status_kelulusan) ||
        Boolean(filterForm.gender) ||
        Boolean(filterForm.start_date) ||
        Boolean(filterForm.end_date)
    );
});

const applyGelombangFilter = () => {
    selectedRows.value = [];
    router.get(
        index.url(),
        {
            search: search.value,
            limit: props.filters.limit,
            jenjang_id: props.filters.jenjang_id,
            cabang_id: filterForm.cabang_id,
            gelombang_id: filterForm.gelombang_id,
            status_kelulusan: filterForm.status_kelulusan,
            gender: filterForm.gender,
            start_date: filterForm.start_date,
            end_date: filterForm.end_date,
            page: 1,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const applyFilters = () => {
    isFilterModalOpen.value = false;
    selectedRows.value = [];
    router.get(
        index.url(),
        {
            search: search.value,
            limit: props.filters.limit,
            jenjang_id: props.filters.jenjang_id,
            cabang_id: filterForm.cabang_id,
            gelombang_id: filterForm.gelombang_id,
            status_kelulusan: filterForm.status_kelulusan,
            gender: filterForm.gender,
            start_date: filterForm.start_date,
            end_date: filterForm.end_date,
            page: 1,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const resetFilters = () => {
    filterForm.cabang_id = '';
    filterForm.status_kelulusan = '';
    filterForm.gender = '';
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
            { preserveState: true, replace: true },
        );
    }, 400);
};

const onPageChange = (page: number) => {
    router.get(
        index.url(),
        {
            ...props.filters,
            page: page,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const onLimitChange = (newLimit: number) => {
    router.get(
        index.url(),
        {
            ...props.filters,
            limit: newLimit,
            page: 1,
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
// PENENTUAN KELAS MODAL
// ==========================================
const isPenentuanKelasModalOpen = ref(false);
const selectedPendaftarForKelas = ref<any>(null);

const openPenentuanKelasModal = (row: any) => {
    selectedPendaftarForKelas.value = row;
    isPenentuanKelasModalOpen.value = true;
};

const closePenentuanKelasModal = () => {
    isPenentuanKelasModalOpen.value = false;
    selectedPendaftarForKelas.value = null;
};

const handlePenentuanKelasSuccess = () => {
    router.reload({ only: ['pendaftars'] });
};

// ==========================================
// INTERVIEW ULANG MODAL
// ==========================================
const isReinterviewModalOpen = ref(false);
const reinterviewTarget = ref<any>(null);
const reinterviewForm = useForm({});

const openReinterviewModal = (row: any) => {
    reinterviewTarget.value = row;
    reinterviewForm.reset();
    reinterviewForm.clearErrors();
    isReinterviewModalOpen.value = true;
};

const closeReinterviewModal = () => {
    isReinterviewModalOpen.value = false;
    reinterviewTarget.value = null;
    reinterviewForm.reset();
    reinterviewForm.clearErrors();
};

const submitReinterview = () => {
    if (!reinterviewTarget.value) return;

    reinterviewForm.post(reinterview.url({ pendaftar: reinterviewTarget.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            closeReinterviewModal();
        },
    });
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
    if (!resetPasswordTarget.value) return;

    resetPasswordForm.post(reset_password.url({ pendaftar: resetPasswordTarget.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            closeResetPasswordModal();
        },
    });
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
    if (!deleteTarget.value) return;

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
    if (selectedRows.value.length === 0) return;
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
// EXPORT DATA (CSV)
// ==========================================
const handleExport = () => {
    let url = exportMethod.url();
    const params = new URLSearchParams();

    if (selectedRows.value.length > 0) {
        params.append('ids', selectedRows.value.join(','));
    } else {
        if (activeJenjangId.value) params.append('jenjang_id', activeJenjangId.value);
        if (props.filters.search) params.append('search', props.filters.search);
        if (props.filters.cabang_id) params.append('cabang_id', props.filters.cabang_id);
        if (props.filters.gelombang_id) params.append('gelombang_id', props.filters.gelombang_id);
        if (props.filters.gender) params.append('gender', props.filters.gender);
        if (props.filters.status_kelulusan) params.append('status_kelulusan', props.filters.status_kelulusan);
        if (props.filters.start_date) params.append('start_date', props.filters.start_date);
        if (props.filters.end_date) params.append('end_date', props.filters.end_date);
    }

    const qs = params.toString();
    if (qs) {
        url += (url.includes('?') ? '&' : '?') + qs;
    }

    window.open(url, '_blank');
};

// ==========================================
// FORMATTING HELPERS
// ==========================================
const isSantriLocked = (row: any): boolean => {
    const h = row.hasil_ujian || row.hasilUjian;
    return Boolean(h?.locked_at);
};

const getWawancaraScore = (row: any): string | null => {
    const h = row.hasil_ujian || row.hasilUjian;
    return h?.hasil_wawancara || null;
};

const getBacaScore = (row: any): number | null => {
    const h = row.hasil_ujian || row.hasilUjian;
    if (h && Number(h.nilai_baca_kitab || 0) > 0) {
        return Math.round(Number(h.nilai_baca_kitab));
    }
    const match = (row.penilaians || []).find((p: any) => {
        const cat = p.aspek?.kategori?.nama_kategori?.toLowerCase() || '';
        return cat.includes('baca');
    });
    return match && Number(match.nilai) > 0 ? Math.round(Number(match.nilai)) : null;
};

const getMenulisScore = (row: any): number | null => {
    const h = row.hasil_ujian || row.hasilUjian;
    if (h && Number(h.nilai_menulis || 0) > 0) {
        return Math.round(Number(h.nilai_menulis));
    }
    const match = (row.penilaians || []).find((p: any) => {
        const cat = p.aspek?.kategori?.nama_kategori?.toLowerCase() || '';
        return cat.includes('tulis') || cat.includes('menulis');
    });
    return match && Number(match.nilai) > 0 ? Math.round(Number(match.nilai)) : null;
};

const getHafalanScore = (row: any): number | null => {
    const h = row.hasil_ujian || row.hasilUjian;
    if (h && Number(h.nilai_hafalan || 0) > 0) {
        return Math.round(Number(h.nilai_hafalan));
    }
    const match = (row.penilaians || []).find((p: any) => {
        const cat = p.aspek?.kategori?.nama_kategori?.toLowerCase() || '';
        return cat.includes('hafal');
    });
    return match && Number(match.nilai) > 0 ? Math.round(Number(match.nilai)) : null;
};

const imageErrorMap = ref<Record<string, boolean>>({});

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
                d.dokumen?.nama_dokumen?.toLowerCase().includes('foto') ||
                d.name?.toLowerCase().includes('foto') ||
                d.file_path?.toLowerCase().includes('foto') ||
                d.file_path?.toLowerCase().includes('pas_foto'),
        )?.file_path ||
        null;

    if (!raw) return null;

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
    if (!name) return 'CS';
    return name
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
};

const getDetailUrl = (id: string) => {
    let currentUrl = '/admin/pendaftar/pengunguman';

    if (typeof window !== 'undefined') {
        const searchParams = window.location.search;
        currentUrl += searchParams || (activeJenjangId.value ? `?jenjang_id=${activeJenjangId.value}` : '');
    } else if (activeJenjangId.value) {
        currentUrl += `?jenjang_id=${activeJenjangId.value}`;
    }

    return `${show.url(id)}?from=${encodeURIComponent(currentUrl)}`;
};

const getKelulusanBadge = (row: any) => {
    const statusKelulusan = (row.hasilUjian?.status_kelulusan || row.hasil_ujian?.status_kelulusan || '')?.toLowerCase();
    const mainStatus = (row.status || '').toUpperCase();

    if (statusKelulusan === 'lulus' || mainStatus === 'LULUS') {
        return {
            label: 'LULUS',
            classes: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800',
        };
    }
    if (statusKelulusan === 'tidak_lulus' || mainStatus === 'TIDAK_LULUS') {
        return {
            label: 'TIDAK LULUS',
            classes: 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800',
        };
    }

    return {
        label: 'BELUM DIPUTUSKAN',
        classes: 'bg-slate-50 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700',
    };
};
</script>

<template>
    <div class="relative min-h-screen w-full">
        <Head title="Pengumuman Kelulusan" />

        <!-- Page Header -->
        <div class="mb-6 flex flex-col gap-1">
            <h1 class="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl dark:text-slate-100">
                Pengumuman Kelulusan
            </h1>
            <p class="max-w-2xl text-xs leading-relaxed text-gray-500 sm:text-sm dark:text-slate-400">
                Kelola hasil penilaian seleksi, penempatan kelas, dan pengumuman status kelulusan calon santri.
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
        <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
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
                        <h4 class="truncate text-xs font-bold text-gray-900 dark:text-slate-100" :title="j.name">
                            {{ j.name }}
                        </h4>
                        <div class="mt-1 flex items-baseline gap-1.5">
                            <span class="font-mono text-xl font-black tracking-tight text-gray-900 dark:text-slate-100">
                                {{ formatStatCount(props.jenjangCounts[j.id]) }}
                            </span>
                            <span class="text-[11px] font-semibold text-gray-400 dark:text-slate-500">
                                Calon Santri
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Direct Action Button / Status Badge -->
                <div class="mt-3.5 border-t border-gray-100 pt-2.5 dark:border-slate-800">
                    <div
                        v-if="activeJenjangId === j.id"
                        class="flex items-center justify-between text-[11px] font-bold text-primary dark:text-blue-400"
                    >
                        <span class="flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                            Filter Aktif
                        </span>
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <button
                        v-else
                        type="button"
                        @click.stop="toggleJenjangFilter(j.id)"
                        class="flex w-full cursor-pointer items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-2.5 py-1.5 text-[11px] font-bold text-gray-600 transition-all group-hover:border-primary/40 group-hover:bg-primary/5 group-hover:text-primary dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-300 dark:group-hover:border-blue-500/40 dark:group-hover:bg-primary/20 dark:group-hover:text-blue-300"
                    >
                        <span>Filter Jenjang</span>
                        <svg class="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content & Data Table -->
        <div class="mt-6">
            <DataTable
                :columns="columns"
                :data="props.pendaftars.data"
                :pagination="props.pendaftars"
                :selectable="true"
                @search="onSearchInput"
                @limit="onLimitChange"
                @selection-change="handleSelection"
            >
                <!-- Bulk Actions Toolbar -->
                <template #bulk-actions="{ selectedIds }">
                    <div
                        v-if="selectedIds.length > 0"
                        class="flex items-center gap-2"
                    >
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

                <!-- Filters Toolbar & Filter Modal -->
                <template #filters>
                    <div class="flex items-center gap-2">
                        <!-- Export Button -->
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

                        <!-- Filter Trigger Button -->
                        <button
                            type="button"
                            @click="isFilterModalOpen = true"
                            class="group inline-flex cursor-pointer items-center rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50 focus:ring-2 focus:ring-primary/20 focus:outline-none sm:px-4 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            <svg
                                class="h-4 w-4 text-gray-400 transition-colors group-hover:text-primary dark:text-slate-500 dark:group-hover:text-blue-400"
                                :class="isFilterActive ? 'text-primary dark:text-blue-400' : ''"
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
                        title="Filter Pengumuman Kelulusan"
                        description="Saring data hasil seleksi dan kelulusan calon santri"
                        max-width="lg"
                        @close="isFilterModalOpen = false"
                        @reset="resetFilters"
                        @apply="applyFilters"
                    >
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400">
                                    Cabang Pendaftaran
                                </label>
                                <CustomSelect
                                    v-model="filterForm.cabang_id"
                                    :options="props.cabangs.map((c) => ({ value: c.id, label: c.name }))"
                                    placeholder="Semua Cabang"
                                />
                            </div>

                            <div>
                                <label class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400">
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

                            <div class="sm:col-span-2">
                                <label class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400">
                                    Status Kelulusan
                                </label>
                                <CustomSelect
                                    v-model="filterForm.status_kelulusan"
                                    :options="[
                                        { value: 'lulus', label: 'Lulus' },
                                        { value: 'tidak_lulus', label: 'Tidak Lulus' },
                                    ]"
                                    placeholder="Semua Status Kelulusan"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400">
                                    Rentang Tanggal Pengumuman
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

                <!-- 1. Column: NO REGISTRASI -->
                <template #cell-nomor_pendaftaran="{ row }">
                    <div class="flex flex-col">
                        <span class="font-mono text-sm font-bold text-primary-dark dark:text-blue-400">
                            {{ row.nomor_pendaftaran || '-' }}
                        </span>
                        <span class="mt-0.5 font-mono text-xs text-slate-500 dark:text-slate-400">
                            NIK: {{ row.nik || row.personal_data?.nik || '-' }}
                        </span>
                    </div>
                </template>

                <!-- 2. Column: CALON SANTRI -->
                <template #cell-pendaftar="{ row }">
                    <div class="flex items-center gap-3">
                        <img
                            v-if="getPendaftarPhoto(row) && !imageErrorMap[row.id]"
                            :src="getPendaftarPhoto(row)!"
                            @error="imageErrorMap[row.id] = true"
                            class="h-10 w-10 shrink-0 rounded-full border border-gray-100 object-cover shadow-xs dark:border-slate-800"
                        />
                        <div
                            v-else
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-emerald-200 bg-emerald-100 text-sm font-bold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300"
                        >
                            {{ getInitials(row.nama) }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <Link
                                    :href="getDetailUrl(row.id)"
                                    class="text-left text-sm font-bold text-slate-800 transition-colors hover:text-primary dark:text-slate-100 dark:hover:text-blue-400"
                                >
                                    {{ row.nama }}
                                </Link>
                                <span
                                    v-if="isSantriLocked(row)"
                                    class="inline-flex items-center gap-1 rounded-md border border-amber-200 bg-amber-50 px-1.5 py-0.5 text-[9px] font-black text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300 shrink-0"
                                    title="Nilai dan status kelulusan santri ini telah dikunci"
                                >
                                    <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    Terkunci
                                </span>
                                <span
                                    v-if="row.is_interview_ulang"
                                    class="inline-flex items-center gap-1 rounded-md border border-indigo-200 bg-indigo-50 px-1.5 py-0.5 text-[9px] font-black text-indigo-700 dark:border-indigo-900/60 dark:bg-indigo-950/40 dark:text-indigo-300 shrink-0"
                                    title="Calon santri mengikuti sesi interview ulang"
                                >
                                    <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Interview Ulang
                                </span>
                            </div>
                            <div class="mt-0.5 flex flex-wrap items-center gap-2">
                                <span
                                    v-if="(row.personal_data?.jenis_kelamin || row.gender)?.toLowerCase().includes('laki') || (row.personal_data?.jenis_kelamin || row.gender)?.toLowerCase() === 'l'"
                                    class="inline-flex items-center text-[11px] font-semibold text-blue-600 dark:text-blue-400"
                                >
                                    Laki-Laki
                                </span>
                                <span
                                    v-else-if="(row.personal_data?.jenis_kelamin || row.gender)?.toLowerCase().includes('perempuan') || (row.personal_data?.jenis_kelamin || row.gender)?.toLowerCase() === 'p'"
                                    class="inline-flex items-center text-[11px] font-semibold text-pink-600 dark:text-pink-400"
                                >
                                    Perempuan
                                </span>
                                <span class="text-gray-300 dark:text-slate-600">•</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ row.jenjang?.name || '-' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- 3. Column: HASIL WAWANCARA -->
                <template #cell-hasil_wawancara="{ row }">
                    <div class="flex flex-col items-center justify-center">
                        <div v-if="getWawancaraScore(row)" class="flex flex-col items-center gap-0.5">
                            <span
                                v-if="getWawancaraScore(row) === 'A'"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-xs font-black text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/30"
                                title="A - Memenuhi"
                            >
                                A
                            </span>
                            <span
                                v-else-if="getWawancaraScore(row) === 'C'"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-50 text-xs font-black text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-500/30"
                                title="C - Syarat Tertentu"
                            >
                                C
                            </span>
                            <span
                                v-else-if="getWawancaraScore(row) === 'D'"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-rose-50 text-xs font-black text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-500/30"
                                title="D - Tidak Memenuhi"
                            >
                                D
                            </span>
                            <span
                                v-else
                                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-xs font-black text-slate-700 ring-1 ring-slate-300 dark:bg-slate-800 dark:text-slate-200"
                            >
                                {{ getWawancaraScore(row) }}
                            </span>
                        </div>
                        <div v-else class="flex flex-col items-center">
                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                Belum Diisi
                            </span>
                        </div>
                    </div>
                </template>

                <!-- 4. Column: TES MEMBACA -->
                <template #cell-tes_membaca="{ row }">
                    <div class="text-center">
                        <span v-if="getBacaScore(row) !== null" class="font-mono text-sm font-bold text-gray-900 dark:text-slate-100">
                            {{ getBacaScore(row) }}
                        </span>
                        <span v-else class="font-medium text-xs text-gray-400 dark:text-slate-500">-</span>
                    </div>
                </template>

                <!-- 5. Column: TES MENULIS -->
                <template #cell-tes_menulis="{ row }">
                    <div class="text-center">
                        <span v-if="getMenulisScore(row) !== null" class="font-mono text-sm font-bold text-gray-900 dark:text-slate-100">
                            {{ getMenulisScore(row) }}
                        </span>
                        <span v-else class="font-medium text-xs text-gray-400 dark:text-slate-500">-</span>
                    </div>
                </template>

                <!-- 6. Column: TES HAFALAN -->
                <template #cell-tes_hafalan="{ row }">
                    <div class="text-center">
                        <span v-if="getHafalanScore(row) !== null" class="font-mono text-sm font-bold text-gray-900 dark:text-slate-100">
                            {{ getHafalanScore(row) }}
                        </span>
                        <span v-else class="font-medium text-xs text-gray-400 dark:text-slate-500">-</span>
                    </div>
                </template>

                <!-- 7. Column: KELAS (PENEMPATAN KELAS) -->
                <template #cell-kelas="{ row }">
                    <div class="flex flex-col items-center justify-center">
                        <!-- Jika santri Tidak Lulus: Tampilkan Tidak Ada Kelas -->
                        <div
                            v-if="getKelulusanBadge(row).label === 'TIDAK LULUS'"
                            class="flex flex-col items-center gap-1"
                            title="Calon santri tidak lulus"
                        >
                            <span class="inline-flex items-center gap-1 rounded-lg border border-rose-200/80 bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-600 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-400">
                                <svg class="h-3 w-3 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Tidak Ada Kelas
                            </span>
                        </div>

                        <!-- Jika santri Lulus / ada kelas -->
                        <div v-else-if="(row.hasil_ujian || row.hasilUjian)?.rekomendasi_kelas_pondok" class="flex flex-col items-center gap-0.5">
                            <span class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/50 dark:text-emerald-300">
                                {{ (row.hasil_ujian || row.hasilUjian).rekomendasi_kelas_pondok }}
                            </span>
                            <button
                                v-if="getKelulusanBadge(row).label !== 'TIDAK LULUS'"
                                type="button"
                                @click="openPenentuanKelasModal(row)"
                                class="cursor-pointer text-[11px] font-medium text-slate-400 underline decoration-dotted underline-offset-2 hover:text-primary dark:text-slate-500 dark:hover:text-blue-400"
                            >
                                Ubah Kelas
                            </button>
                        </div>

                        <div v-else class="flex flex-col items-center gap-1">
                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                Belum Ditentukan
                            </span>
                            <button
                                v-if="getKelulusanBadge(row).label !== 'TIDAK LULUS'"
                                type="button"
                                @click="openPenentuanKelasModal(row)"
                                class="cursor-pointer text-[11px] font-bold text-primary underline decoration-dotted underline-offset-2 hover:text-primary-dark dark:text-blue-400 dark:hover:text-blue-300"
                            >
                                Tentukan Kelas
                            </button>
                        </div>
                    </div>
                </template>

                <!-- 8. Column: STATUS KELULUSAN (LULUS / TIDAK) -->
                <template #cell-status_kelulusan="{ row }">
                    <div class="flex items-center justify-center">
                        <span
                            :class="[
                                'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold tracking-wider uppercase',
                                getKelulusanBadge(row).classes,
                            ]"
                        >
                            {{ getKelulusanBadge(row).label }}
                        </span>
                    </div>
                </template>

                <!-- Row Actions (Column Aksi) -->
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
                                <!-- Action 1: DETAIL SANTRI (PROFILE) -->
                                <Link
                                    :href="getDetailUrl(row.id)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                >
                                    <svg class="mr-3 h-4 w-4 text-sky-500 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Detail
                                </Link>

                                <!-- Action 2: PENENTUAN KELAS (Hanya untuk Lulus / Belum Diputuskan) -->
                                <button
                                    v-if="getKelulusanBadge(row).label !== 'TIDAK LULUS'"
                                    type="button"
                                    @click="openPenentuanKelasModal(row)"
                                    class="flex w-full cursor-pointer items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                >
                                    <svg class="mr-3 h-4 w-4 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    Penentuan Kelas
                                </button>

                                <!-- Action 3: INTERVIEW ULANG (Khusus Calon Santri TIDAK LULUS) -->
                                <button
                                    v-if="getKelulusanBadge(row).label === 'TIDAK LULUS' || row.status === 'TIDAK_LULUS'"
                                    type="button"
                                    @click="openReinterviewModal(row)"
                                    class="flex w-full cursor-pointer items-center px-3 py-2.5 text-left text-sm font-bold text-amber-600 transition-colors hover:bg-amber-50 sm:px-4 dark:text-amber-400 dark:hover:bg-amber-950/50"
                                >
                                    <svg class="mr-3 h-4 w-4 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Interview Ulang
                                </button>

                                <!-- Action 3: CETAK KARTU -->
                                <button
                                    @click="openPrintCard(row)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                >
                                    <svg class="mr-3 h-4 w-4 text-emerald-500 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    Cetak Kartu
                                </button>

                                <!-- Action 4: RESET SANDI -->
                                <button
                                    @click="openResetPasswordModal(row)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                >
                                    <svg class="mr-3 h-4 w-4 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                    </svg>
                                    Reset Sandi
                                </button>

                                <div class="my-1 border-t border-gray-100 dark:border-slate-800"></div>

                                <!-- Action 5: HAPUS PENDAFTAR -->
                                <button
                                    @click="openDeleteModal(row)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-rose-600 transition-colors hover:bg-rose-50 sm:px-4 dark:text-rose-400 dark:hover:bg-rose-950/50"
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

        <!-- ======================================================= -->
        <!-- MODAL: PENENTUAN KELAS PONDOK                          -->
        <!-- ======================================================= -->
        <PenentuanKelasModal
            :show="isPenentuanKelasModalOpen"
            :pendaftar="selectedPendaftarForKelas"
            :kelompok-ujian-id="selectedPendaftarForKelas?.kelompok_ujians?.[0]?.id || selectedPendaftarForKelas?.kelompokUjians?.[0]?.id || ''"
            @close="closePenentuanKelasModal"
            @success="handlePenentuanKelasSuccess"
        />

        <!-- ======================================================= -->
        <!-- MODAL: RESET PASSWORD                                  -->
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
        <!-- MODAL: KONFIRMASI HAPUS (SINGLE)                       -->
        <!-- ======================================================= -->
        <Modal
            :show="isDeleteModalOpen"
            @close="closeDeleteModal"
            maxWidth="sm"
            title="Hapus Pendaftar"
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
                Apakah Anda yakin ingin menghapus data pendaftar
                <strong class="font-bold text-gray-900 dark:text-slate-100">{{
                    deleteTarget.nama
                }}</strong>?
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
        <!-- MODAL: KONFIRMASI INTERVIEW ULANG                      -->
        <!-- ======================================================= -->
        <Modal
            :show="isReinterviewModalOpen"
            @close="closeReinterviewModal"
            maxWidth="md"
            title="Konfirmasi Interview Ulang"
            description="Atur sesi interview ulang untuk calon santri yang tidak lulus seleksi."
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
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                </div>
            </template>

            <div class="space-y-4">
                <div
                    v-if="reinterviewTarget"
                    class="rounded-xl border border-gray-100 bg-gray-50 p-3.5 text-xs text-gray-700 dark:border-slate-800 dark:bg-slate-800/70 dark:text-slate-300"
                >
                    <div>
                        Nama Santri:
                        <strong class="text-gray-900 dark:text-slate-100">{{
                            reinterviewTarget.nama
                        }}</strong>
                    </div>
                    <div class="mt-1 flex items-center gap-2">
                        <span>No. Registrasi: <strong class="font-mono text-gray-900 dark:text-slate-100">{{ reinterviewTarget.nomor_pendaftaran || '-' }}</strong></span>
                        <span class="text-gray-300 dark:text-slate-600">•</span>
                        <span>Jenjang: <strong class="text-gray-900 dark:text-slate-100">{{ reinterviewTarget.jenjang?.name || '-' }}</strong></span>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="rounded-xl border border-amber-200/80 bg-amber-50/70 p-3.5 text-xs text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200">
                    <div class="flex items-start gap-2.5">
                        <svg class="h-4 w-4 mt-0.5 shrink-0 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="space-y-1 text-[11px] leading-relaxed">
                            <p class="font-bold text-amber-950 dark:text-amber-100">
                                Aksi ini akan melakukan proses berikut:
                            </p>
                            <ul class="list-disc pl-4 space-y-0.5 text-amber-800 dark:text-amber-300">
                                <li>Status pendaftaran diubah kembali menjadi <strong>Tagihan</strong>.</li>
                                <li>Status pembuatan tagihan diset menjadi <strong>Belum Dibuat</strong> untuk tahap interview ulang.</li>
                                <li>Menandai calon santri sebagai peserta <strong>Interview Ulang</strong>.</li>
                                <li>Riwayat nilai tes, kelompok ujian, tagihan, dan pembayaran sebelumnya <strong>tetap tersimpan</strong>.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <SecondaryButton
                        type="button"
                        @click="closeReinterviewModal"
                        :disabled="reinterviewForm.processing"
                    >
                        Batal
                    </SecondaryButton>
                    <PrimaryButton
                        type="button"
                        @click="submitReinterview"
                        :disabled="reinterviewForm.processing"
                        class="bg-amber-600 hover:bg-amber-700 focus:ring-amber-500 dark:bg-amber-600 dark:hover:bg-amber-500"
                    >
                        <span v-if="reinterviewForm.processing">Memproses...</span>
                        <span v-else>Ya, Setujui Interview Ulang</span>
                    </PrimaryButton>
                </div>
            </template>
        </Modal>

        <!-- ======================================================= -->
        <!-- MODAL: BULK DELETE CONFIRMATION                        -->
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
                    >{{ selectedRows.length }} data pendaftar</strong
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

