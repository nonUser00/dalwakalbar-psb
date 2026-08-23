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
    verify as verifyRoute,
    bulk_verify,
    reset_password,
    destroy as destroyRoute,
    bulk_destroy,
    exportMethod,
} from '@/routes/admin/pendaftar/submit';
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

interface MasterDokumenItem {
    id: string;
    name: string;
    type: 'gambar' | 'pdf' | string;
    jalur_pendaftaran: string;
    is_required: boolean;
    is_profile_photo: boolean;
    jenjangs?: Array<{
        id: string;
        name: string;
        code?: string;
        singkatan?: string;
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
    masterDokumens?: MasterDokumenItem[];
    filters: {
        search: string;
        limit: number;
        jenjang_id: string;
        cabang_id: string;
        gelombang_id: string;
        gender: string;
        tipe_pendaftaran: string;
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

const totalSubmitCount = computed(() => {
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
    const tipe = row.tipe_pendaftaran ? ` (${row.tipe_pendaftaran})` : '';

    if (code === 'MTS') {
        if (edu.kelas_tingkat) {
return `Kelas ${edu.kelas_tingkat}${tipe}`;
}

        return row.tipe_pendaftaran === 'Pindahan' ? 'Pindahan' : `Kelas VII${tipe}`;
    }

    if (code === 'MA') {
        const jurusan = edu.jurusan_ma || edu.jurusan;

        if (jurusan) {
return `Jurusan ${jurusan}${tipe}`;
}

        if (edu.kelas_tingkat) {
return `Kelas ${edu.kelas_tingkat}${tipe}`;
}

        return row.tipe_pendaftaran === 'Pindahan' ? 'Pindahan' : `Kelas X${tipe}`;
    }

    // S1, S2, S3
    const prodi = edu.fakultas_prodi_utama || edu.prodi_utama || edu.prodi;

    if (prodi) {
return `Prodi: ${prodi}${tipe}`;
}

    return row.tipe_pendaftaran ? `${row.tipe_pendaftaran}` : 'Reguler';
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
// DYNAMIC DOCUMENT REQUIREMENT HELPERS
// ==========================================
/**
 * Mendapatkan daftar master dokumen yang berlaku untuk pendaftar tertentu
 * berdasarkan jenjang_id dan tipe_pendaftaran (jalur).
 */
const getApplicantApplicableDocuments = (pendaftar: any): MasterDokumenItem[] => {
    if (!props.masterDokumens || props.masterDokumens.length === 0) {
        return [];
    }

    const jenjangId = pendaftar?.jenjang_id || pendaftar?.jenjang?.id;
    const rawTipe = pendaftar?.tipe_pendaftaran || pendaftar?.education_data?.tipe_pendaftaran || 'Reguler';
    const tipe = (typeof rawTipe === 'object' && rawTipe?.value ? rawTipe.value : String(rawTipe)).toLowerCase();

    return props.masterDokumens.filter((doc: any) => {
        // 1. Cek kesesuaian Jenjang (jika doc.jenjangs kosong = berlaku semua jenjang)
        const matchJenjang =
            !doc.jenjangs ||
            doc.jenjangs.length === 0 ||
            doc.jenjangs.some((j: any) => j.id === jenjangId || j === jenjangId);

        if (!matchJenjang) {
            return false;
        }

        // 2. Cek kesesuaian Jalur Pendaftaran
        const rawDocJalur = doc.jalur_pendaftaran || 'Semua';
        const docJalur = (typeof rawDocJalur === 'object' && rawDocJalur?.value ? rawDocJalur.value : String(rawDocJalur)).toLowerCase();

        if (docJalur === 'semua') {
            return true;
        }

        if (docJalur === tipe) {
            return true;
        }

        return false;
    });
};

/**
 * Menghitung ringkasan status submit dokumen untuk pendaftar
 */
const getApplicantDocumentSummary = (pendaftar: any) => {
    const applicableDocs = getApplicantApplicableDocuments(pendaftar);
    const requiredDocs = applicableDocs.filter((d) => d.is_required);
    const uploadedDocs = pendaftar?.dokumens || [];

    // Menghitung berapa dokumen wajib yang sudah diunggah
    const uploadedRequiredCount = requiredDocs.filter((reqDoc) =>
        uploadedDocs.some(
            (u: any) =>
                u.dokumen_id === reqDoc.id &&
                (u.file_path || u.file_url || u.path),
        ),
    ).length;

    // Total dokumen yang sudah diunggah dari seluruh dokumen yang berlaku
    const totalUploadedCount = applicableDocs.filter((appDoc) =>
        uploadedDocs.some(
            (u: any) =>
                u.dokumen_id === appDoc.id &&
                (u.file_path || u.file_url || u.path),
        ),
    ).length;

    const totalRequired = requiredDocs.length;
    const isComplete =
        totalRequired > 0
            ? uploadedRequiredCount >= totalRequired
            : totalUploadedCount > 0;

    const percentage =
        totalRequired > 0
            ? Math.round((uploadedRequiredCount / totalRequired) * 100)
            : totalUploadedCount > 0
              ? 100
              : 0;

    return {
        applicableCount: applicableDocs.length,
        requiredCount: totalRequired,
        uploadedRequiredCount,
        totalUploadedCount,
        isComplete,
        percentage,
    };
};

/**
 * Menghitung kelengkapan pengisian data formulir dari 5 tahapan
 */
const getSubmitProgress = (row: any) => {
    const docSummary = getApplicantDocumentSummary(row);

    const steps = [
        {
            step: 1,
            name: 'Data Personal',
            isComplete: Boolean(
                row.personal_data &&
                (
                    row.personal_data.tempat_lahir ||
                    row.personal_data.tanggal_lahir ||
                    row.personal_data.nik
                ) &&
                (row.personal_data.jenis_kelamin || row.gender)
            ),
        },
        {
            step: 2,
            name: 'Data Orang Tua',
            isComplete: Boolean(
                row.parent_data &&
                (
                    row.parent_data.ayah?.nama ||
                    row.parent_data.ibu?.nama ||
                    row.parent_data.nama_ayah ||
                    row.parent_data.nama_ibu
                )
            ),
        },
        {
            step: 3,
            name: 'Data Alamat',
            isComplete: Boolean(
                row.address_data &&
                (
                    row.address_data.alamat_lengkap ||
                    row.address_data.alamat ||
                    row.address_data.kabupaten_kota
                )
            ),
        },
        {
            step: 4,
            name: 'Data Pendidikan',
            isComplete: Boolean(
                row.education_data &&
                (
                    row.education_data.asal_sekolah ||
                    row.education_data.nama_sekolah_asal ||
                    row.education_data.pendidikan_sebelumnya?.nama_sekolah ||
                    row.education_data.kelas_tingkat ||
                    row.education_data.prodi ||
                    row.education_data.jurusan ||
                    row.education_data.prodi_utama
                ) || row.jenjang_id
            ),
        },
        {
            step: 5,
            name: 'Dokumen Persyaratan',
            isComplete: Boolean(docSummary.isComplete),
        },
    ];

    const completedSteps = steps.filter((s) => s.isComplete).length;
    const totalSteps = 5;
    const percentage = Math.round((completedSteps / totalSteps) * 100);

    return {
        steps,
        completedSteps,
        totalSteps,
        percentage,
        isAllComplete: completedSteps === totalSteps,
    };
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
    { key: 'progress', label: 'KELENGKAPAN', sortable: false },
    { key: 'dokumen', label: 'DOKUMEN', sortable: false },
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
    tipe_pendaftaran: props.filters.tipe_pendaftaran || '',
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
        filterForm.tipe_pendaftaran = newFilters.tipe_pendaftaran || '';
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
        Boolean(props.filters.tipe_pendaftaran) ||
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
            tipe_pendaftaran: filterForm.tipe_pendaftaran,
            start_date: filterForm.start_date,
            end_date: filterForm.end_date,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const resetFilters = () => {
    filterForm.cabang_id = '';
    filterForm.gender = '';
    filterForm.tipe_pendaftaran = '';
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
// MODAL: DETAIL LENGKAP PENDAFTAR SUBMIT
// ==========================================
const isDetailModalOpen = ref(false);
const detailModalTarget = ref<any>(null);
const activeDetailStep = ref<number>(1);

const openDetailModal = (pendaftar: any, initialStep: number = 1) => {
    detailModalTarget.value = pendaftar;
    activeDetailStep.value = initialStep;
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    detailModalTarget.value = null;
    activeDetailStep.value = 1;
};

const getTargetApplicableDocuments = computed(() => {
    if (!detailModalTarget.value) {
return [];
}

    return getApplicantApplicableDocuments(detailModalTarget.value);
});

const getUploadedDocumentRecord = (docId: string) => {
    if (!detailModalTarget.value?.dokumens) {
return null;
}

    return detailModalTarget.value.dokumens.find(
        (d: any) => d.dokumen_id === docId || d.dokumen?.id === docId,
    );
};

const getDocumentFileUrl = (uploadedDoc: any): string | null => {
    if (!uploadedDoc) {
return null;
}

    const raw =
        uploadedDoc.file_path || uploadedDoc.file_url || uploadedDoc.path;

    if (!raw) {
return null;
}

    if (
        raw.startsWith('http://') ||
        raw.startsWith('https://') ||
        raw.startsWith('data:') ||
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


const isImageFile = (pathOrUrl?: string | null) => {
    if (!pathOrUrl) return false;
    const clean = pathOrUrl.split('?')[0].toLowerCase();
    return (
        clean.endsWith('.jpg') ||
        clean.endsWith('.jpeg') ||
        clean.endsWith('.png') ||
        clean.endsWith('.webp') ||
        clean.endsWith('.gif') ||
        clean.endsWith('.svg')
    );
};

const isPdfFile = (pathOrUrl?: string | null) => {
    if (!pathOrUrl) return false;
    const clean = pathOrUrl.split('?')[0].toLowerCase();
    return clean.endsWith('.pdf');
};

const getDocFileName = (dok: any) => {
    if (!dok) return '';
    if (dok.file_name) return dok.file_name;
    if (dok.file_path) {
        const parts = dok.file_path.split('/');
        return parts[parts.length - 1];
    }
    return dok.dokumen?.name || dok.dokumen?.nama || 'Berkas Lampiran';
};

// ==========================================
// VERIFIKASI SINGLE & BULK
// ==========================================
const isVerifyModalOpen = ref(false);
const verifyTarget = ref<any>(null);

const isPerbaikanModalOpen = ref(false);

const verifyForm = useForm({
    action: 'terima' as 'terima' | 'tolak',
    catatan_revisi: '',
    catatan_personal: '',
    catatan_parent: '',
    catatan_address: '',
    catatan_education: '',
    dokumen_catatan: {} as Record<string, string>,
});

const openVerifyModal = (row: any) => {
    verifyTarget.value = row;
    verifyForm.reset();
    verifyForm.clearErrors();
    verifyForm.action = 'terima';
    verifyForm.catatan_revisi = '';
    isVerifyModalOpen.value = true;
};

const closeVerifyModal = () => {
    isVerifyModalOpen.value = false;
    verifyTarget.value = null;
    verifyForm.reset();
    verifyForm.clearErrors();
};

const submitTerima = () => {
    if (!verifyTarget.value) return;
    verifyForm.action = 'terima';
    verifyForm.post(verifyRoute.url(verifyTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeVerifyModal();
        },
    });
};

const openPerbaikanModalFromVerify = () => {
    if (!verifyTarget.value) return;
    const target = verifyTarget.value;
    isVerifyModalOpen.value = false;

    // Populate perbaikan form
    verifyForm.reset();
    verifyForm.clearErrors();
    verifyForm.action = 'tolak';
    verifyForm.catatan_revisi = target.personal_data?.catatan_revisi || '';
    verifyForm.catatan_personal = target.personal_data?.catatan_personal || '';
    verifyForm.catatan_parent = target.parent_data?.catatan_parent || '';
    verifyForm.catatan_address = target.address_data?.catatan_address || '';
    verifyForm.catatan_education = target.education_data?.catatan_education || '';
    const docNotes: Record<string, string> = {};
    (target.dokumens || []).forEach((d: any) => {
        if (d.dokumen_id) {
            docNotes[d.dokumen_id] = d.catatan || '';
        }
    });
    verifyForm.dokumen_catatan = docNotes;

    detailModalTarget.value = target;
    activeDetailStep.value = 1;
    isPerbaikanModalOpen.value = true;
};

const closePerbaikanModal = () => {
    isPerbaikanModalOpen.value = false;
    verifyTarget.value = null;
    verifyForm.reset();
    verifyForm.clearErrors();
};

const submitPerbaikan = () => {
    if (!verifyTarget.value && !detailModalTarget.value) return;
    const id = verifyTarget.value?.id || detailModalTarget.value?.id;
    verifyForm.action = 'tolak';
    verifyForm.post(verifyRoute.url(id), {
        preserveScroll: true,
        onSuccess: () => {
            closePerbaikanModal();
        },
    });
};

const isBulkVerifyModalOpen = ref(false);
const bulkVerifyAction = ref<'terima' | 'tolak'>('terima');

const bulkVerifyForm = useForm({
    ids: [] as string[],
    action: 'terima' as 'terima' | 'tolak',
    catatan_revisi: '',
});

const openBulkVerifyModal = () => {
    if (selectedRows.value.length === 0) {
        return;
    }

    bulkVerifyAction.value = 'terima';
    bulkVerifyForm.reset();
    bulkVerifyForm.clearErrors();
    bulkVerifyForm.ids = [...selectedRows.value];
    bulkVerifyForm.action = 'terima';
    bulkVerifyForm.catatan_revisi = '';
    isBulkVerifyModalOpen.value = true;
};

const closeBulkVerifyModal = () => {
    isBulkVerifyModalOpen.value = false;
    bulkVerifyForm.reset();
    bulkVerifyForm.clearErrors();
};

const submitBulkVerify = () => {
    bulkVerifyForm.post(bulk_verify.url(), {
        preserveScroll: true,
        onSuccess: () => {
            closeBulkVerifyModal();
            selectedRows.value = [];
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
    if (!resetPasswordTarget.value) {
return;
}

    resetPasswordForm.post(reset_password.url(resetPasswordTarget.value.id), {
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
    if (!deleteTarget.value) {
return;
}

    deleteForm.delete(destroyRoute.url(deleteTarget.value.id), {
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
// EXPORT DATA (UNIFIED: SELECTED OR ALL)
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

        if (search.value) {
            params.append('search', search.value);
        }

        if (filterForm.cabang_id) {
            params.append('cabang_id', filterForm.cabang_id);
        }

        if (filterForm.periode_id) {
            params.append('periode_id', filterForm.periode_id);
        }

        if (filterForm.gelombang_id) {
            params.append('gelombang_id', filterForm.gelombang_id);
        }

        if (filterForm.gender) {
            params.append('gender', filterForm.gender);
        }

        if (filterForm.tipe_pendaftaran) {
            params.append('tipe_pendaftaran', filterForm.tipe_pendaftaran);
        }

        if (filterForm.start_date) {
            params.append('start_date', filterForm.start_date);
        }

        if (filterForm.end_date) {
            params.append('end_date', filterForm.end_date);
        }
    }

    const queryString = params.toString();

    if (queryString) {
        url += (url.includes('?') ? '&' : '?') + queryString;
    }

    window.open(url, '_blank');
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

const formatDateOnly = (dateStr?: string) => {
    if (!dateStr) {
return '-';
}

    try {
        const d = new Date(dateStr);

        if (isNaN(d.getTime())) {
return dateStr;
}

        return d.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
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
    let currentUrl = '/admin/pendaftar/submit';

    if (typeof window !== 'undefined') {
        const querySearch = window.location.search;
        currentUrl +=
            querySearch ||
            (activeJenjangId.value
                ? `?jenjang_id=${activeJenjangId.value}`
                : '');
    } else if (activeJenjangId.value) {
        currentUrl += `?jenjang_id=${activeJenjangId.value}`;
    }

    return `${show.url(id)}?from=${encodeURIComponent(currentUrl)}`;
};
</script>

<template>
    <div class="relative min-h-screen w-full">
        <Head title="Pendaftar Submit" />

        <!-- Main Page Header -->
        <div
            class="relative mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div>
                <h1
                    class="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl dark:text-slate-100"
                >
                    Pendaftar Submit
                </h1>
                <p
                    class="mt-1 max-w-2xl text-xs leading-relaxed text-gray-500 sm:text-sm dark:text-slate-400"
                >
                    Kelola dan verifikasi data calon santri yang telah melakukan
                    submit formulir dan berkas persyaratan pendaftaran.
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

        <!-- All Jenjang Statistics Overview Grid (Acts as Filter Toggle) -->
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
                                Submit
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
                        <!-- Primary Action: Verifikasi Berkas -->
                        <button
                            type="button"
                            @click="openBulkVerifyModal"
                            class="group inline-flex cursor-pointer items-center rounded-xl bg-emerald-600 px-3 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none sm:px-4"
                            title="Verifikasi dokumen calon santri terpilih"
                        >
                            <svg
                                class="h-4 w-4 sm:mr-2"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            <span class="hidden sm:inline"
                                >Verifikasi Terpilih</span
                            >
                        </button>

                        <!-- Secondary Action: Hapus -->
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
                        title="Filter Data Pendaftar Submit"
                        description="Saring data pendaftar berdasarkan kriteria pendaftaran dan berkas"
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

                            <div class="sm:col-span-2">
                                <label
                                    class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    Jalur Pendaftaran
                                </label>
                                <CustomSelect
                                    v-model="filterForm.tipe_pendaftaran"
                                    :options="[
                                        { value: 'Reguler', label: 'Reguler' },
                                        { value: 'Pindahan', label: 'Pindahan' },
                                        { value: 'Prestasi', label: 'Prestasi' },
                                        { value: 'Beasiswa', label: 'Beasiswa' },
                                    ]"
                                    placeholder="Semua Jalur Pendaftaran"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <label
                                    class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    Rentang Tanggal Submit
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

                <!-- Column: KELENGKAPAN (5 Step Chips) -->
                <template #cell-progress="{ row }">
                    <div class="flex flex-col gap-1.5 py-0.5 min-w-[130px]">
                        <!-- 5 Individual Step Chips (1 s.d. 5) -->
                        <div class="flex items-center gap-1.5">
                            <button
                                v-for="st in getSubmitProgress(row).steps"
                                :key="st.step"
                                type="button"
                                @click="openDetailModal(row, st.step)"
                                class="flex h-6 w-6 cursor-pointer items-center justify-center rounded-lg text-xs font-black shadow-2xs transition-all hover:scale-110"
                                :class="[
                                    st.isComplete
                                        ? 'border border-primary-500 bg-primary text-white shadow-primary/20 dark:border-blue-600 dark:bg-blue-600'
                                        : 'border border-gray-200 bg-gray-50 text-gray-400 hover:border-gray-300 hover:bg-white hover:text-gray-600 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-500 dark:hover:bg-slate-700 dark:hover:text-slate-300',
                                ]"
                                :title="`Step ${st.step}: ${st.name} (${st.isComplete ? 'Selesai / Terisi' : 'Belum Diisi'}) - Klik untuk melihat`"
                            >
                                {{ st.step }}
                            </button>
                        </div>

                        <!-- Progress Summary Subtext -->
                        <div class="flex items-center justify-between gap-2 w-full mt-0.5">
                            <div class="flex-1 h-1.5 rounded-full bg-gray-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full bg-primary dark:bg-blue-500 rounded-full transition-all duration-300" :style="`width: ${getSubmitProgress(row).percentage}%`"></div>
                            </div>
                            <button
                                type="button"
                                @click="openDetailModal(row, 1)"
                                class="shrink-0 text-[10px] font-semibold text-primary transition-colors hover:text-primary-600 dark:text-blue-400 dark:hover:text-blue-300 whitespace-nowrap"
                                title="Lihat detail form pendaftar"
                            >
                                {{ getSubmitProgress(row).percentage }}%
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Column: DOKUMEN -->
                <template #cell-dokumen="{ row }">
                    <button
                        type="button"
                        @click="openDetailModal(row, 5)"
                        class="group inline-flex flex-col items-start gap-1 py-1 text-left cursor-pointer transition-colors"
                        title="Klik untuk melihat berkas lampiran"
                    >
                        <!-- Status Badge + Ratio -->
                        <div class="flex items-center gap-1.5">
                            <span
                                v-if="!row.jenjang && !row.education_data?.jenjang"
                                class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-bold text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                Belum Diisi
                            </span>
                            <span
                                v-else-if="getApplicantDocumentSummary(row).isComplete"
                                class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/50 dark:text-emerald-400"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Lengkap
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-400"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                Belum Lengkap
                            </span>

                            <span
                                v-if="getApplicantDocumentSummary(row).requiredCount > 0"
                                class="font-mono text-[11px] font-bold text-slate-600 dark:text-slate-400"
                            >
                                {{ getApplicantDocumentSummary(row).uploadedRequiredCount }}/{{ getApplicantDocumentSummary(row).requiredCount }}
                            </span>
                        </div>

                        <!-- Subtext detail link -->
                        <span
                            class="flex items-center gap-1 text-[11px] font-medium text-slate-400 transition-colors group-hover:text-primary dark:text-slate-500 dark:group-hover:text-blue-400"
                        >
                            <span>{{ getApplicantDocumentSummary(row).totalUploadedCount }} Terunggah</span>
                            <span class="text-[10px]">&bull;</span>
                            <span class="underline decoration-dotted group-hover:decoration-solid">Lihat Berkas</span>
                        </span>
                    </button>
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
                                <!-- Action 1: VERIFIKASI -->
                                <button
                                    @click="openVerifyModal(row)"
                                    class="flex w-full items-center bg-emerald-50/50 px-3 py-2.5 text-left text-sm font-bold text-emerald-700 transition-colors hover:bg-emerald-100 sm:px-4 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                                >
                                    <svg
                                        class="mr-3 h-4 w-4 text-emerald-600 dark:text-emerald-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    Verifikasi
                                </button>

                                <!-- Action 2: DETAIL PENDAFTAR -->
                                <Link
                                    :href="getDetailUrl(row.id)"
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
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        />
                                    </svg>
                                    Detail Pendaftar
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

                                <!-- Action 4: RESET PASSWORD -->
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
        <!-- MODAL: DETAIL LENGKAP PENDAFTAR SUBMIT (5 STEPS) -->
        <!-- ======================================================= -->
        <Modal
            :show="isDetailModalOpen"
            @close="closeDetailModal"
            maxWidth="5xl"
            title="Rincian Data Pendaftar Submit"
            description="Tinjauan isian formulir calon santri step 1 sampai dengan step 5."
        >
            <template #icon>
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary/10 text-primary dark:bg-blue-950/50 dark:text-blue-400"
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
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                </div>
            </template>

            <div v-if="detailModalTarget" class="space-y-4 sm:space-y-5">
                <!-- TOP PROFILE HERO CARD -->
                <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-gradient-to-r from-gray-50/90 via-white to-gray-50/70 p-4 sm:p-5 shadow-2xs dark:border-slate-800 dark:from-slate-900/90 dark:via-slate-900/80 dark:to-slate-950/60"
                >
                    <div class="flex items-center gap-3.5 sm:gap-4">
                        <!-- Avatar / Photo -->
                        <div
                            class="relative h-14 w-14 sm:h-16 sm:w-16 shrink-0 overflow-hidden rounded-2xl border-2 border-white bg-gray-100 shadow-sm dark:border-slate-800 dark:bg-slate-800"
                        >
                            <img
                                v-if="
                                    getPendaftarPhoto(detailModalTarget) &&
                                    !imageErrorMap[detailModalTarget.id]
                                "
                                :src="getPendaftarPhoto(detailModalTarget)!"
                                @error="imageErrorMap[detailModalTarget.id] = true"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center bg-primary/10 text-base sm:text-lg font-black text-primary dark:bg-blue-500/20 dark:text-blue-300"
                            >
                                {{ getInitials(detailModalTarget.nama) }}
                            </div>
                        </div>

                        <!-- Candidate Info -->
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h4
                                    class="text-base sm:text-lg font-bold text-gray-900 leading-snug dark:text-slate-100"
                                >
                                    {{ detailModalTarget.nama }}
                                </h4>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-blue-200 bg-blue-50 px-2.5 py-0.5 text-[10px] font-bold text-blue-700 uppercase dark:border-blue-900/50 dark:bg-blue-950/50 dark:text-blue-300"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                    Submit ({{ getSubmitProgress(detailModalTarget).percentage }}%)
                                </span>
                            </div>

                            <p
                                class="mt-1 font-mono text-xs text-gray-500 dark:text-slate-400"
                            >
                                No. Reg: <strong class="text-gray-900 dark:text-slate-200">{{ detailModalTarget.nomor_pendaftaran || '-' }}</strong> &bull; NIK: <strong class="text-gray-900 dark:text-slate-200">{{ detailModalTarget.nik || '-' }}</strong>
                            </p>
                        </div>
                    </div>

                    <!-- Right Badges: Cabang & Jenjang -->
                    <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                        <!-- Cabang Badge -->
                        <span
                            v-if="detailModalTarget.cabang?.name || detailModalTarget.personal_data?.cabang_pendaftaran"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            {{ detailModalTarget.cabang?.name || detailModalTarget.personal_data?.cabang_pendaftaran }}
                        </span>

                        <!-- Jenjang Badge (With Logo) -->
                        <span
                            v-if="detailModalTarget.jenjang?.name || detailModalTarget.education_data?.jenjang"
                            class="inline-flex items-center gap-2 rounded-xl border border-primary/20 bg-primary/5 px-3 py-1.5 text-xs font-bold text-primary dark:border-blue-500/30 dark:bg-blue-950/40 dark:text-blue-300"
                        >
                            <img
                                :src="getJenjangLogo(detailModalTarget.jenjang?.code || detailModalTarget.education_data?.jenjang)"
                                class="h-4 w-4 object-contain"
                            />
                            {{ detailModalTarget.jenjang?.name || detailModalTarget.education_data?.jenjang }}
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center rounded-xl border border-dashed border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-400 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-500"
                        >
                            Belum Memilih Jenjang (Step 4)
                        </span>

                        <span
                            class="rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 shadow-2xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >
                            {{ detailModalTarget.tipe_pendaftaran || detailModalTarget.education_data?.tipe_pendaftaran || 'Reguler' }}
                        </span>
                    </div>
                </div>

                <!-- Step Tab Bar -->
                <div
                    class="rounded-2xl border border-gray-200/90 bg-white p-2 sm:p-2.5 shadow-2xs dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex overflow-x-auto no-scrollbar sm:grid sm:grid-cols-5 gap-2 pb-0.5 sm:pb-0"
                    >
                        <button
                            v-for="st in getSubmitProgress(detailModalTarget).steps"
                            :key="st.step"
                            type="button"
                            @click="activeDetailStep = st.step"
                            :class="[
                                activeDetailStep === st.step
                                    ? 'bg-primary text-white shadow-sm ring-1 ring-primary-600 dark:bg-blue-600 dark:ring-blue-500'
                                    : 'border border-gray-100 bg-gray-50/80 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-300 dark:hover:bg-slate-800',
                                'flex shrink-0 min-w-[145px] sm:min-w-0 flex-1 cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl p-2.5 sm:p-3 text-center transition-all duration-150',
                            ]"
                        >
                            <!-- Top: Step Number (Centered) -->
                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-lg text-[11px] font-black transition-colors"
                                :class="
                                    activeDetailStep === st.step
                                        ? 'bg-white/20 text-white'
                                        : 'bg-white text-gray-700 shadow-2xs dark:bg-slate-900 dark:text-slate-300'
                                "
                            >
                                {{ st.step }}
                            </span>

                            <!-- Middle: Tab Title (Centered) -->
                            <span class="text-xs font-bold leading-tight whitespace-nowrap sm:whitespace-normal">{{ st.name }}</span>

                            <!-- Bottom: Status Badge (Selesai / Belum - Centered) -->
                            <span
                                :class="[
                                    activeDetailStep === st.step
                                        ? 'bg-white/20 text-white'
                                        : st.isComplete
                                          ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                          : 'bg-slate-200/80 text-slate-500 dark:bg-slate-700 dark:text-slate-400',
                                    'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[9px] font-black uppercase tracking-wider',
                                ]"
                            >
                                <span
                                    v-if="st.isComplete"
                                    class="h-1.5 w-1.5 rounded-full"
                                    :class="activeDetailStep === st.step ? 'bg-white' : 'bg-emerald-500'"
                                ></span>
                                {{ st.isComplete ? 'Selesai' : 'Belum' }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- TAB 1: DATA PERSONAL -->
                <div v-show="activeDetailStep === 1" class="space-y-4">
                    <div
                        class="rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="mb-5 flex items-center justify-between border-b border-gray-100 pb-3.5 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-indigo-100 bg-indigo-50 text-indigo-600 dark:border-indigo-900/50 dark:bg-indigo-950/50 dark:text-indigo-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm sm:text-base font-bold text-gray-900 dark:text-slate-100">
                                        Identitas Calon Santri & Kontak
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-slate-400">Informasi personal dan biodata calon santri</p>
                                </div>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-400 uppercase tracking-wider">Tahap 1</span>
                        </div>

                        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Nama Lengkap</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.nama || detailModalTarget.personal_data?.nama || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">NIK (Nomor Induk Kependudukan)</p>
                                <p class="font-mono text-xs sm:text-sm font-bold text-primary dark:text-blue-400">{{ detailModalTarget.nik || detailModalTarget.personal_data?.nik || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">No. Kartu Keluarga (KK)</p>
                                <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.personal_data?.no_kk || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Jenis Kelamin</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.personal_data?.jenis_kelamin || detailModalTarget.gender || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Tempat, Tanggal Lahir</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">
                                    {{ detailModalTarget.personal_data?.tempat_lahir || '-' }},
                                    {{ formatDateOnly(detailModalTarget.personal_data?.tanggal_lahir) }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Ukuran Baju Seragam</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.personal_data?.ukuran_baju || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">No. WhatsApp / HP</p>
                                <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.nomor_hp || detailModalTarget.personal_data?.nomor_hp || detailModalTarget.personal_data?.no_whatsapp || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Email</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.email || detailModalTarget.personal_data?.email || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Cabang Pendaftaran</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.cabang?.name || detailModalTarget.personal_data?.cabang_pendaftaran || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Hobi & Cita-Cita</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">
                                    {{ detailModalTarget.personal_data?.hobi || '-' }} &bull; {{ detailModalTarget.personal_data?.cita_cita || '-' }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800 sm:col-span-2">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Urutan Anak & Saudara</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">
                                    Anak ke-{{ detailModalTarget.personal_data?.anak_ke || '-' }} dari {{ detailModalTarget.personal_data?.jumlah_saudara || '-' }} bersaudara
                                    <span v-if="detailModalTarget.personal_data?.jumlah_saudara_di_dalwa" class="font-bold text-primary dark:text-blue-400">({{ detailModalTarget.personal_data.jumlah_saudara_di_dalwa }} saudara di Dalwa)</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: DATA ORANG TUA & WALI -->
                <div v-show="activeDetailStep === 2" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <!-- Ayah -->
                        <div class="rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
                            <div class="mb-5 flex items-center justify-between border-b border-gray-100 pb-3.5 dark:border-slate-800">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-blue-100 bg-blue-50 text-blue-600 dark:border-blue-900/50 dark:bg-blue-950/50 dark:text-blue-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm sm:text-base font-bold text-gray-900 dark:text-slate-100">
                                            Data Ayah Kandung
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-slate-400">Informasi biodata & kontak ayah</p>
                                    </div>
                                </div>
                                <span
                                    class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider"
                                    :class="[
                                        (detailModalTarget.parent_data?.ayah?.status || detailModalTarget.parent_data?.status_ayah || 'Hidup').toLowerCase().includes('meninggal')
                                            ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                                            : 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-900/50'
                                    ]"
                                >
                                    {{ detailModalTarget.parent_data?.ayah?.status || detailModalTarget.parent_data?.status_ayah || 'Masih Hidup' }}
                                </span>
                            </div>

                            <div class="space-y-3">
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-blue-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Nama Ayah</p>
                                    <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ayah?.nama || detailModalTarget.parent_data?.nama_ayah || '-' }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-blue-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">NIK Ayah</p>
                                        <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ayah?.nik || detailModalTarget.parent_data?.nik_ayah || '-' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-blue-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">No. HP Ayah</p>
                                        <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ayah?.nomor_hp || detailModalTarget.parent_data?.nomor_hp_ayah || '-' }}</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-blue-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Pendidikan</p>
                                        <p class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ayah?.pendidikan || detailModalTarget.parent_data?.pendidikan_ayah || '-' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-blue-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Pekerjaan</p>
                                        <p class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ayah?.pekerjaan || detailModalTarget.parent_data?.pekerjaan_ayah || '-' }}</p>
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-blue-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Penghasilan Bulanan</p>
                                    <p class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ayah?.penghasilan || detailModalTarget.parent_data?.penghasilan_ayah || '-' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Ibu -->
                        <div class="rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
                            <div class="mb-5 flex items-center justify-between border-b border-gray-100 pb-3.5 dark:border-slate-800">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-rose-100 bg-rose-50 text-rose-600 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm sm:text-base font-bold text-gray-900 dark:text-slate-100">
                                            Data Ibu Kandung
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-slate-400">Informasi biodata & kontak ibu</p>
                                    </div>
                                </div>
                                <span
                                    class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider"
                                    :class="[
                                        (detailModalTarget.parent_data?.ibu?.status || detailModalTarget.parent_data?.status_ibu || 'Hidup').toLowerCase().includes('meninggal')
                                            ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                                            : 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-900/50'
                                    ]"
                                >
                                    {{ detailModalTarget.parent_data?.ibu?.status || detailModalTarget.parent_data?.status_ibu || 'Masih Hidup' }}
                                </span>
                            </div>

                            <div class="space-y-3">
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-rose-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Nama Ibu</p>
                                    <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ibu?.nama || detailModalTarget.parent_data?.nama_ibu || '-' }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-rose-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">NIK Ibu</p>
                                        <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ibu?.nik || detailModalTarget.parent_data?.nik_ibu || '-' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-rose-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">No. HP Ibu</p>
                                        <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ibu?.nomor_hp || detailModalTarget.parent_data?.nomor_hp_ibu || '-' }}</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-rose-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Pendidikan</p>
                                        <p class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ibu?.pendidikan || detailModalTarget.parent_data?.pendidikan_ibu || '-' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-rose-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Pekerjaan</p>
                                        <p class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ibu?.pekerjaan || detailModalTarget.parent_data?.pekerjaan_ibu || '-' }}</p>
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-rose-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Penghasilan Bulanan</p>
                                    <p class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ibu?.penghasilan || detailModalTarget.parent_data?.penghasilan_ibu || '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Wali -->
                    <div
                        v-if="detailModalTarget.parent_data?.wali?.nama || detailModalTarget.parent_data?.nama_wali"
                        class="rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="mb-4 flex items-center justify-between border-b border-gray-100 pb-3.5 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-amber-100 bg-amber-50 text-amber-600 dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm sm:text-base font-bold text-gray-900 dark:text-slate-100">
                                        Data Wali Santri
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-slate-400">Informasi wali penanggung jawab</p>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-amber-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Nama Wali</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.wali?.nama || detailModalTarget.parent_data?.nama_wali || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-amber-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">NIK Wali</p>
                                <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.wali?.nik || detailModalTarget.parent_data?.nik_wali || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-amber-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">No. HP Wali</p>
                                <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.wali?.nomor_hp || detailModalTarget.parent_data?.nomor_hp_wali || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-amber-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Pekerjaan & Penghasilan</p>
                                <p class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.wali?.pekerjaan || detailModalTarget.parent_data?.pekerjaan_wali || '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: DATA ALAMAT & DOMISILI -->
                <div v-show="activeDetailStep === 3" class="space-y-4">
                    <div
                        class="rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="mb-5 flex items-center justify-between border-b border-gray-100 pb-3.5 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-100 bg-emerald-50 text-emerald-600 dark:border-emerald-900/50 dark:bg-emerald-950/50 dark:text-emerald-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm sm:text-base font-bold text-gray-900 dark:text-slate-100">
                                        Alamat Domisili & Transportasi
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-slate-400">Informasi tempat tinggal dan rute ke pesantren</p>
                                </div>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-400 uppercase tracking-wider">Tahap 3</span>
                        </div>

                        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="sm:col-span-2 lg:col-span-3 rounded-2xl border border-gray-100/90 bg-gray-50/70 p-4 transition-colors hover:bg-emerald-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Alamat Lengkap</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100 leading-relaxed">{{ detailModalTarget.address_data?.alamat_lengkap || detailModalTarget.address_data?.alamat || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-emerald-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">RT / RW</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">RT {{ detailModalTarget.address_data?.rt || '-' }} / RW {{ detailModalTarget.address_data?.rw || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-emerald-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Kelurahan / Desa</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.address_data?.kelurahan_desa || detailModalTarget.address_data?.kelurahan || detailModalTarget.address_data?.desa || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-emerald-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Kecamatan</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.address_data?.kecamatan || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-emerald-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Kabupaten / Kota</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.address_data?.kabupaten_kota || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-emerald-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Provinsi</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.address_data?.provinsi || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-emerald-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Kode Pos</p>
                                <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.address_data?.kode_pos || '-' }}</p>
                            </div>
                            <div class="sm:col-span-2 lg:col-span-3 rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-emerald-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Jarak & Transportasi ke Pesantren</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">
                                    {{ detailModalTarget.address_data?.jarak_ke_pondok || '-' }} &bull; Transportasi: {{ detailModalTarget.address_data?.transportasi || '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: DATA PENDIDIKAN & PILIHAN -->
                <div v-show="activeDetailStep === 4" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <!-- Pilihan Masuk / Program Jenjang Tujuan -->
                        <div class="rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
                            <div class="mb-5 flex items-center justify-between border-b border-gray-100 pb-3.5 dark:border-slate-800">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-primary/20 bg-primary/10 text-primary dark:border-blue-900/50 dark:bg-blue-950/50 dark:text-blue-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm sm:text-base font-bold text-gray-900 dark:text-slate-100">
                                            Pilihan Program Tujuan
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-slate-400">Jenjang & program yang dipilih</p>
                                    </div>
                                </div>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-400 uppercase tracking-wider">Tahap 4</span>
                            </div>

                            <!-- If Jenjang Selected -->
                            <div v-if="detailModalTarget.jenjang?.name || detailModalTarget.education_data?.jenjang" class="space-y-3">
                                <div class="flex items-center gap-3 rounded-2xl border border-primary/20 bg-primary/5 p-4 dark:border-blue-500/30 dark:bg-blue-950/30">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white p-1.5 shadow-2xs dark:bg-slate-800">
                                        <img
                                            :src="getJenjangLogo(detailModalTarget.jenjang?.code || detailModalTarget.education_data?.jenjang)"
                                            class="h-full w-full object-contain"
                                        />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold tracking-wider text-primary uppercase dark:text-blue-400">Jenjang Pendidikan Tujuan</p>
                                        <h5 class="text-base font-extrabold text-gray-900 dark:text-slate-100">{{ detailModalTarget.jenjang?.name || detailModalTarget.education_data?.jenjang }}</h5>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Jalur Pendaftaran</p>
                                        <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.tipe_pendaftaran || detailModalTarget.education_data?.tipe_pendaftaran || 'Reguler' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Kelas / Program</p>
                                        <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ getEducationSubText(detailModalTarget) || '-' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- If Jenjang NOT Selected -->
                            <div v-else class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 p-6 text-center dark:border-slate-800 dark:bg-slate-800/40">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 14l9-5-9-5-9 5 9 5z" />
                                    </svg>
                                </div>
                                <p class="mt-2 text-xs font-semibold text-slate-600 dark:text-slate-400">Jenjang Belum Dipilih</p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Calon santri baru berada di tahap 1-3 formulir.</p>
                            </div>
                        </div>

                        <!-- Sekolah Asal -->
                        <div class="rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
                            <div class="mb-5 flex items-center justify-between border-b border-gray-100 pb-3.5 dark:border-slate-800">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-amber-100 bg-amber-50 text-amber-600 dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm sm:text-base font-bold text-gray-900 dark:text-slate-100">
                                            Riwayat Sekolah Sebelumnya
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-slate-400">Sekolah asal calon santri</p>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-amber-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Nama Sekolah Asal</p>
                                    <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.education_data?.pendidikan_sebelumnya?.nama_sekolah || detailModalTarget.education_data?.asal_sekolah || '-' }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-amber-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">NISN</p>
                                        <p class="font-mono text-xs sm:text-sm font-bold text-primary dark:text-blue-400">{{ detailModalTarget.education_data?.pendidikan_sebelumnya?.nisn || detailModalTarget.education_data?.nisn || '-' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-amber-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">NPSN</p>
                                        <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.education_data?.pendidikan_sebelumnya?.npsn || detailModalTarget.education_data?.npsn || '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: DOKUMEN PERSYARATAN -->
                <div v-show="activeDetailStep === 5" class="space-y-4">
                    <!-- Summary Alert Banner -->
                    <div
                        class="flex items-center justify-between rounded-2xl border p-4 shadow-2xs"
                        :class="[
                            getApplicantDocumentSummary(detailModalTarget).isComplete
                                ? 'border-emerald-200 bg-emerald-50/90 text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300'
                                : 'border-amber-200 bg-amber-50/90 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/60 dark:text-amber-300',
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl font-bold shadow-2xs"
                                :class="getApplicantDocumentSummary(detailModalTarget).isComplete ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-white'"
                            >
                                {{ getApplicantDocumentSummary(detailModalTarget).isComplete ? '✓' : '!' }}
                            </span>
                            <div>
                                <h5 class="text-xs sm:text-sm font-bold">
                                    {{
                                        getApplicantDocumentSummary(detailModalTarget).isComplete
                                            ? 'Dokumen Persyaratan Wajib Lengkap'
                                            : 'Dokumen Persyaratan Wajib Belum Lengkap'
                                    }}
                                </h5>
                                <p class="text-xs opacity-80">
                                    Disesuaikan untuk jenjang {{ detailModalTarget.jenjang?.name || '-' }} (Jalur {{ detailModalTarget.tipe_pendaftaran || 'Reguler' }}).
                                </p>
                            </div>
                        </div>
                        <span class="font-mono text-xs font-bold shrink-0">
                            {{ getApplicantDocumentSummary(detailModalTarget).uploadedRequiredCount }}/{{ getApplicantDocumentSummary(detailModalTarget).requiredCount }} Wajib
                        </span>
                    </div>

                    <!-- Document Items List (Compact Grid Cards matching Profile Show Page with Previews) -->
                    <div class="space-y-3">
                        <div
                            v-if="getTargetApplicableDocuments.length === 0"
                            class="rounded-2xl border border-dashed border-gray-200 p-8 text-center text-xs text-gray-500 dark:border-slate-800 dark:text-slate-400"
                        >
                            Tidak ada persyaratan dokumen khusus untuk jenjang dan jalur ini.
                        </div>

                        <div
                            v-else
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
                        >
                            <div
                                v-for="doc in getTargetApplicableDocuments"
                                :key="doc.id"
                                class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-gray-200/90 bg-white p-3 shadow-2xs transition-all duration-200 hover:border-primary/40 hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                            >
                                <!-- Top Bar: Document Type & Required Status -->
                                <div class="flex items-center justify-between gap-1.5 mb-2.5">
                                    <span class="truncate text-xs font-black text-slate-800 dark:text-slate-100" :title="doc.name">
                                        {{ doc.name }}
                                    </span>
                                    <span
                                        v-if="doc.is_required"
                                        class="shrink-0 rounded-full bg-rose-50 border border-rose-200 px-2 py-0.2 text-[9px] font-black uppercase text-rose-700 dark:bg-rose-950/60 dark:border-rose-900/50 dark:text-rose-300"
                                    >
                                        Wajib
                                    </span>
                                    <span
                                        v-else
                                        class="shrink-0 rounded-full bg-slate-100 px-2 py-0.2 text-[9px] font-black uppercase text-slate-500 dark:bg-slate-800 dark:text-slate-400"
                                    >
                                        Opsional
                                    </span>
                                </div>

                                <!-- Visual Preview Container (Compact Thumbnail & Direct Open) -->
                                <div class="relative h-32 w-full overflow-hidden rounded-xl border border-gray-100 bg-gray-50 dark:border-slate-800 dark:bg-slate-800/60 flex items-center justify-center">
                                    <template v-if="getUploadedDocumentRecord(doc.id)">
                                        <!-- Image Preview -->
                                        <template v-if="isImageFile(getDocumentFileUrl(getUploadedDocumentRecord(doc.id)))">
                                            <img
                                                :src="getDocumentFileUrl(getUploadedDocumentRecord(doc.id))!"
                                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                                :alt="doc.name"
                                            />
                                        </template>

                                        <!-- PDF Preview -->
                                        <template v-else-if="isPdfFile(getDocumentFileUrl(getUploadedDocumentRecord(doc.id)))">
                                            <div class="relative h-full w-full bg-slate-100 dark:bg-slate-950 flex flex-col items-center justify-center p-2">
                                                <svg class="h-8 w-8 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                                <span class="mt-1 text-[10px] font-black uppercase text-rose-600">Dokumen PDF</span>
                                            </div>
                                        </template>

                                        <!-- Fallback File -->
                                        <template v-else>
                                            <div class="flex flex-col items-center justify-center p-2 text-slate-400">
                                                <svg class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span class="mt-1 text-[10px] font-bold">Berkas Terunggah</span>
                                            </div>
                                        </template>

                                        <!-- Hover Action Overlay -->
                                        <div class="absolute inset-0 bg-black/40 opacity-0 backdrop-blur-2xs transition-opacity group-hover:opacity-100 flex items-center justify-center gap-1.5 p-2">
                                            <a
                                                v-if="getDocumentFileUrl(getUploadedDocumentRecord(doc.id))"
                                                :href="getDocumentFileUrl(getUploadedDocumentRecord(doc.id))!"
                                                target="_blank"
                                                class="inline-flex items-center gap-1 rounded-lg bg-white px-2.5 py-1 text-[11px] font-bold text-slate-900 shadow-md transition-transform hover:scale-105"
                                            >
                                                <svg class="h-3.5 w-3.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span>Buka Tab</span>
                                            </a>
                                        </div>
                                    </template>

                                    <!-- Empty / Not uploaded yet -->
                                    <template v-else>
                                        <div class="flex flex-col items-center justify-center p-3 text-center">
                                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                                </svg>
                                            </div>
                                            <span class="mt-1.5 text-[10.5px] font-semibold text-slate-400 dark:text-slate-500">
                                                {{ doc.is_required ? 'Belum Diunggah (Wajib)' : 'Belum Diunggah' }}
                                            </span>
                                        </div>
                                    </template>
                                </div>

                                <!-- Card Footer: Document Details & Revision Note -->
                                <div class="mt-2.5 space-y-1">
                                    <div class="flex items-center justify-between text-[10.5px] text-slate-500 dark:text-slate-400">
                                        <span class="uppercase font-bold">{{ doc.type === 'gambar' ? 'Gambar' : doc.type === 'semua' ? 'PDF / Gambar' : 'PDF' }}</span>
                                        <span v-if="getUploadedDocumentRecord(doc.id)">{{ formatDateTime(getUploadedDocumentRecord(doc.id)?.created_at) }}</span>
                                        <span v-else class="text-rose-500 font-bold">Kosong</span>
                                    </div>
                                    <p
                                        v-if="getUploadedDocumentRecord(doc.id)?.catatan"
                                        class="rounded-lg bg-rose-50 border border-rose-200 p-1.5 text-[10px] font-medium text-rose-700 dark:bg-rose-950/40 dark:border-rose-900/50 dark:text-rose-300"
                                    >
                                        <strong class="font-bold">Catatan:</strong> {{ getUploadedDocumentRecord(doc.id)?.catatan }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex w-full items-center justify-between">
                    <Link
                        v-if="detailModalTarget"
                        :href="getDetailUrl(detailModalTarget.id)"
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-primary transition-colors hover:underline dark:text-blue-400"
                    >
                        <span>Detail Pendaftar &rarr;</span>
                    </Link>
                    <div v-else></div>
                    <SecondaryButton @click="closeDetailModal" type="button">
                        Tutup
                    </SecondaryButton>
                </div>
            </template>
        </Modal>

        <!-- ======================================================= -->
        <!-- MODAL 1: VERIFIKASI AWAL (SEUKURAN RESET PASSWORD - 2 OPSI) -->
        <!-- ======================================================= -->
        <Modal
            :show="isVerifyModalOpen"
            @close="closeVerifyModal"
            maxWidth="md"
            title="Verifikasi Berkas Calon Santri"
            description="Tentukan keputusan verifikasi pendaftaran calon santri."
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </template>

            <div v-if="verifyTarget" class="space-y-4">
                <div
                    class="rounded-xl border border-gray-100 bg-gray-50 p-3.5 text-xs text-gray-700 dark:border-slate-800 dark:bg-slate-800/70 dark:text-slate-300"
                >
                    <div>
                        Pendaftar:
                        <strong class="text-gray-900 dark:text-slate-100">{{ verifyTarget.nama }}</strong>
                    </div>
                    <div class="mt-0.5">
                        No. Registrasi:
                        <strong class="font-mono text-gray-900 dark:text-slate-100">{{ verifyTarget.nomor_pendaftaran || '-' }}</strong>
                    </div>
                    <div class="mt-0.5 flex items-center gap-2">
                        <span>Jenjang: <strong class="text-primary dark:text-blue-400 font-bold">{{ verifyTarget.jenjang?.name || '-' }}</strong></span>
                        <span>•</span>
                        <span>Jalur: <strong>{{ verifyTarget.tipe_pendaftaran || 'Reguler' }}</strong></span>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-300">
                        Pilih Keputusan Verifikasi:
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            @click="submitTerima"
                            :disabled="verifyForm.processing"
                            class="flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-2xl border border-emerald-300 bg-emerald-50/80 p-4 text-center text-emerald-800 transition-all hover:bg-emerald-100 hover:shadow-xs focus:ring-2 focus:ring-emerald-500/20 dark:border-emerald-900/60 dark:bg-emerald-950/50 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                        >
                            <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-xs font-black">TERIMA</span>
                            <span class="text-[10px] text-emerald-600/90 dark:text-emerald-400/90 leading-tight">Lanjut ke Tagihan</span>
                        </button>

                        <button
                            type="button"
                            @click="openPerbaikanModalFromVerify"
                            class="flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-2xl border border-rose-300 bg-rose-50/80 p-4 text-center text-rose-800 transition-all hover:bg-rose-100 hover:shadow-xs focus:ring-2 focus:ring-rose-500/20 dark:border-rose-900/60 dark:bg-rose-950/50 dark:text-rose-300 dark:hover:bg-rose-900/50"
                        >
                            <svg class="h-6 w-6 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <span class="text-xs font-black">PERBAIKAN</span>
                            <span class="text-[10px] text-rose-600/90 dark:text-rose-400/90 leading-tight">Beri Catatan Revisi</span>
                        </button>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end">
                    <SecondaryButton @click="closeVerifyModal" type="button">
                        Tutup
                    </SecondaryButton>
                </div>
            </template>
        </Modal>

        <!-- ======================================================= -->
        <!-- MODAL 2: FORM CATATAN PERBAIKAN & RINCIAN LENGKAP -->
        <!-- ======================================================= -->
        <Modal
            :show="isPerbaikanModalOpen"
            @close="closePerbaikanModal"
            maxWidth="5xl"
            title="Form Catatan Perbaikan Berkas Pendaftar"
            description="Tinjau isian formulir calon santri dan berikan catatan revisi pada bagian yang perlu diperbaiki."
        >
            <template #icon>
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
            </template>

            <div v-if="detailModalTarget" class="space-y-4 sm:space-y-5">
                <!-- TOP PROFILE HERO CARD (100% SAMA PERSIS DENGAN RINCIAN MODAL) -->
                <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-gradient-to-r from-gray-50/90 via-white to-gray-50/70 p-4 sm:p-5 shadow-2xs dark:border-slate-800 dark:from-slate-900/90 dark:via-slate-900/80 dark:to-slate-950/60"
                >
                    <div class="flex items-center gap-3.5 sm:gap-4">
                        <!-- Avatar / Photo -->
                        <div
                            class="relative h-14 w-14 sm:h-16 sm:w-16 shrink-0 overflow-hidden rounded-2xl border-2 border-white bg-gray-100 shadow-sm dark:border-slate-800 dark:bg-slate-800"
                        >
                            <img
                                v-if="
                                    getPendaftarPhoto(detailModalTarget) &&
                                    !imageErrorMap[detailModalTarget.id]
                                "
                                :src="getPendaftarPhoto(detailModalTarget)!"
                                @error="imageErrorMap[detailModalTarget.id] = true"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center bg-primary/10 text-base sm:text-lg font-black text-primary dark:bg-blue-500/20 dark:text-blue-300"
                            >
                                {{ getInitials(detailModalTarget.nama) }}
                            </div>
                        </div>

                        <!-- Candidate Info -->
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h4
                                    class="text-base sm:text-lg font-bold text-gray-900 leading-snug dark:text-slate-100"
                                >
                                    {{ detailModalTarget.nama }}
                                </h4>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-0.5 text-[10px] font-bold text-rose-700 uppercase dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-300"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                    Tahap Revisi
                                </span>
                            </div>

                            <p
                                class="mt-1 font-mono text-xs text-gray-500 dark:text-slate-400"
                            >
                                No. Reg: <strong class="text-gray-900 dark:text-slate-200">{{ detailModalTarget.nomor_pendaftaran || '-' }}</strong> &bull; NIK: <strong class="text-gray-900 dark:text-slate-200">{{ detailModalTarget.nik || '-' }}</strong>
                            </p>
                        </div>
                    </div>

                    <!-- Right Badges: Cabang & Jenjang -->
                    <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                        <!-- Cabang Badge -->
                        <span
                            v-if="detailModalTarget.cabang?.name || detailModalTarget.personal_data?.cabang_pendaftaran"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            {{ detailModalTarget.cabang?.name || detailModalTarget.personal_data?.cabang_pendaftaran }}
                        </span>

                        <!-- Jenjang Badge (With Logo) -->
                        <span
                            v-if="detailModalTarget.jenjang?.name || detailModalTarget.education_data?.jenjang"
                            class="inline-flex items-center gap-2 rounded-xl border border-primary/20 bg-primary/5 px-3 py-1.5 text-xs font-bold text-primary dark:border-blue-500/30 dark:bg-blue-950/40 dark:text-blue-300"
                        >
                            <img
                                :src="getJenjangLogo(detailModalTarget.jenjang?.code || detailModalTarget.education_data?.jenjang)"
                                class="h-4 w-4 object-contain"
                            />
                            {{ detailModalTarget.jenjang?.name || detailModalTarget.education_data?.jenjang }}
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center rounded-xl border border-dashed border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-400 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-500"
                        >
                            Belum Memilih Jenjang (Step 4)
                        </span>

                        <span
                            class="rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 shadow-2xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >
                            {{ detailModalTarget.tipe_pendaftaran || detailModalTarget.education_data?.tipe_pendaftaran || 'Reguler' }}
                        </span>
                    </div>
                </div>

                <!-- Step Tab Bar (100% SAMA PERSIS DENGAN RINCIAN MODAL) -->
                <div
                    class="rounded-2xl border border-gray-200/90 bg-white p-2 sm:p-2.5 shadow-2xs dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex overflow-x-auto no-scrollbar sm:grid sm:grid-cols-5 gap-2 pb-0.5 sm:pb-0"
                    >
                        <button
                            v-for="st in getSubmitProgress(detailModalTarget).steps"
                            :key="st.step"
                            type="button"
                            @click="activeDetailStep = st.step"
                            :class="[
                                activeDetailStep === st.step
                                    ? 'bg-primary text-white shadow-sm ring-1 ring-primary-600 dark:bg-blue-600 dark:ring-blue-500'
                                    : 'border border-gray-100 bg-gray-50/80 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-300 dark:hover:bg-slate-800',
                                'flex shrink-0 min-w-[145px] sm:min-w-0 flex-1 cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl p-2.5 sm:p-3 text-center transition-all duration-150',
                            ]"
                        >
                            <!-- Top: Step Number (Centered) -->
                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-lg text-[11px] font-black transition-colors"
                                :class="
                                    activeDetailStep === st.step
                                        ? 'bg-white/20 text-white'
                                        : 'bg-white text-gray-700 shadow-2xs dark:bg-slate-900 dark:text-slate-300'
                                "
                            >
                                {{ st.step }}
                            </span>

                            <!-- Middle: Tab Title (Centered) -->
                            <span class="text-xs font-bold leading-tight whitespace-nowrap sm:whitespace-normal">{{ st.name }}</span>

                            <!-- Bottom: Status Badge (Selesai / Belum - Centered) -->
                            <span
                                :class="[
                                    activeDetailStep === st.step
                                        ? 'bg-white/20 text-white'
                                        : st.isComplete
                                          ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                          : 'bg-slate-200/80 text-slate-500 dark:bg-slate-700 dark:text-slate-400',
                                    'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[9px] font-black uppercase tracking-wider',
                                ]"
                            >
                                <span
                                    v-if="st.isComplete"
                                    class="h-1.5 w-1.5 rounded-full"
                                    :class="activeDetailStep === st.step ? 'bg-white' : 'bg-emerald-500'"
                                ></span>
                                {{ st.isComplete ? 'Selesai' : 'Belum' }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- TAB 1: DATA PERSONAL -->
                <div v-show="activeDetailStep === 1" class="space-y-4">
                    <!-- Catatan Khusus Textarea -->
                    <div class="rounded-2xl border border-rose-200 bg-rose-50/50 p-4 dark:border-rose-900/50 dark:bg-rose-950/30 space-y-2">
                        <label class="block text-xs font-black text-rose-800 dark:text-rose-300 uppercase tracking-wider">
                            Catatan Khusus Data Personal
                        </label>
                        <textarea
                            v-model="verifyForm.catatan_personal"
                            rows="3"
                            placeholder="Tuliskan catatan perbaikan khusus untuk data personal calon santri (misal: NIK, nama lengkap, atau tanggal lahir tidak sesuai dokumen KK)..."
                            class="w-full rounded-xl border border-rose-300 bg-white p-3 text-xs sm:text-sm text-gray-900 shadow-xs focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 focus:outline-none dark:border-rose-800 dark:bg-slate-900 dark:text-slate-100"
                        ></textarea>
                    </div>

                    <!-- Identitas Calon Santri & Kontak Card (100% PERSIS RINCIAN MODAL) -->
                    <div
                        class="rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="mb-5 flex items-center justify-between border-b border-gray-100 pb-3.5 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-indigo-100 bg-indigo-50 text-indigo-600 dark:border-indigo-900/50 dark:bg-indigo-950/50 dark:text-indigo-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm sm:text-base font-bold text-gray-900 dark:text-slate-100">
                                        Identitas Calon Santri & Kontak
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-slate-400">Informasi personal dan biodata calon santri</p>
                                </div>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-400 uppercase tracking-wider">Tahap 1</span>
                        </div>

                        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Nama Lengkap</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.nama || detailModalTarget.personal_data?.nama || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">NIK (Nomor Induk Kependudukan)</p>
                                <p class="font-mono text-xs sm:text-sm font-bold text-primary dark:text-blue-400">{{ detailModalTarget.nik || detailModalTarget.personal_data?.nik || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">No. Kartu Keluarga (KK)</p>
                                <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.personal_data?.no_kk || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Jenis Kelamin</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.personal_data?.jenis_kelamin || detailModalTarget.gender || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Tempat, Tanggal Lahir</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">
                                    {{ detailModalTarget.personal_data?.tempat_lahir || '-' }},
                                    {{ formatDateOnly(detailModalTarget.personal_data?.tanggal_lahir) }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Ukuran Baju Seragam</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.personal_data?.ukuran_baju || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">No. WhatsApp / HP</p>
                                <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.nomor_hp || detailModalTarget.personal_data?.nomor_hp || detailModalTarget.personal_data?.no_whatsapp || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Email</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.email || detailModalTarget.personal_data?.email || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Cabang Pendaftaran</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.cabang?.name || detailModalTarget.personal_data?.cabang_pendaftaran || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Hobi & Cita-Cita</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">
                                    {{ detailModalTarget.personal_data?.hobi || '-' }} &bull; {{ detailModalTarget.personal_data?.cita_cita || '-' }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800 sm:col-span-2">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Urutan Anak & Saudara</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">
                                    Anak ke-{{ detailModalTarget.personal_data?.anak_ke || '-' }} dari {{ detailModalTarget.personal_data?.jumlah_saudara || '-' }} bersaudara
                                    <span v-if="detailModalTarget.personal_data?.jumlah_saudara_di_dalwa" class="font-bold text-primary dark:text-blue-400">({{ detailModalTarget.personal_data.jumlah_saudara_di_dalwa }} saudara di Dalwa)</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: DATA ORANG TUA & WALI -->
                <div v-show="activeDetailStep === 2" class="space-y-4">
                    <!-- Catatan Khusus Textarea -->
                    <div class="rounded-2xl border border-rose-200 bg-rose-50/50 p-4 dark:border-rose-900/50 dark:bg-rose-950/30 space-y-2">
                        <label class="block text-xs font-black text-rose-800 dark:text-rose-300 uppercase tracking-wider">
                            Catatan Khusus Data Orang Tua / Wali
                        </label>
                        <textarea
                            v-model="verifyForm.catatan_parent"
                            rows="3"
                            placeholder="Tuliskan catatan perbaikan data orang tua / wali (misal: Nomor kontak orang tua salah, nama ayah tidak sesuai KK)..."
                            class="w-full rounded-xl border border-rose-300 bg-white p-3 text-xs sm:text-sm text-gray-900 shadow-xs focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 focus:outline-none dark:border-rose-800 dark:bg-slate-900 dark:text-slate-100"
                        ></textarea>
                    </div>

                    <!-- Cards Ayah, Ibu, Wali (100% PERSIS RINCIAN MODAL) -->
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <!-- Ayah -->
                        <div class="rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
                            <div class="mb-5 flex items-center justify-between border-b border-gray-100 pb-3.5 dark:border-slate-800">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-blue-100 bg-blue-50 text-blue-600 dark:border-blue-900/50 dark:bg-blue-950/50 dark:text-blue-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm sm:text-base font-bold text-gray-900 dark:text-slate-100">
                                            Data Ayah Kandung
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-slate-400">Informasi biodata & kontak ayah</p>
                                    </div>
                                </div>
                                <span
                                    class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider"
                                    :class="[
                                        (detailModalTarget.parent_data?.ayah?.status || detailModalTarget.parent_data?.status_ayah || 'Hidup').toLowerCase().includes('meninggal')
                                            ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                                            : 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-900/50'
                                    ]"
                                >
                                    {{ detailModalTarget.parent_data?.ayah?.status || detailModalTarget.parent_data?.status_ayah || 'Masih Hidup' }}
                                </span>
                            </div>

                            <div class="space-y-3">
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-blue-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Nama Ayah</p>
                                    <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ayah?.nama || detailModalTarget.parent_data?.nama_ayah || '-' }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-blue-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">NIK Ayah</p>
                                        <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ayah?.nik || detailModalTarget.parent_data?.nik_ayah || '-' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-blue-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">No. HP Ayah</p>
                                        <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ayah?.nomor_hp || detailModalTarget.parent_data?.nomor_hp_ayah || '-' }}</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-blue-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Pendidikan</p>
                                        <p class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ayah?.pendidikan || detailModalTarget.parent_data?.pendidikan_ayah || '-' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-blue-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Pekerjaan</p>
                                        <p class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ayah?.pekerjaan || detailModalTarget.parent_data?.pekerjaan_ayah || '-' }}</p>
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-blue-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Penghasilan Bulanan</p>
                                    <p class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ayah?.penghasilan || detailModalTarget.parent_data?.penghasilan_ayah || '-' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Ibu -->
                        <div class="rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
                            <div class="mb-5 flex items-center justify-between border-b border-gray-100 pb-3.5 dark:border-slate-800">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-rose-100 bg-rose-50 text-rose-600 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm sm:text-base font-bold text-gray-900 dark:text-slate-100">
                                            Data Ibu Kandung
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-slate-400">Informasi biodata & kontak ibu</p>
                                    </div>
                                </div>
                                <span
                                    class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider"
                                    :class="[
                                        (detailModalTarget.parent_data?.ibu?.status || detailModalTarget.parent_data?.status_ibu || 'Hidup').toLowerCase().includes('meninggal')
                                            ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                                            : 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-900/50'
                                    ]"
                                >
                                    {{ detailModalTarget.parent_data?.ibu?.status || detailModalTarget.parent_data?.status_ibu || 'Masih Hidup' }}
                                </span>
                            </div>

                            <div class="space-y-3">
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-rose-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Nama Ibu</p>
                                    <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ibu?.nama || detailModalTarget.parent_data?.nama_ibu || '-' }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-rose-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">NIK Ibu</p>
                                        <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ibu?.nik || detailModalTarget.parent_data?.nik_ibu || '-' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-rose-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">No. HP Ibu</p>
                                        <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ibu?.nomor_hp || detailModalTarget.parent_data?.nomor_hp_ibu || '-' }}</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-rose-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Pendidikan</p>
                                        <p class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ibu?.pendidikan || detailModalTarget.parent_data?.pendidikan_ibu || '-' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-rose-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Pekerjaan</p>
                                        <p class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ibu?.pekerjaan || detailModalTarget.parent_data?.pekerjaan_ibu || '-' }}</p>
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-rose-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Penghasilan Bulanan</p>
                                    <p class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.ibu?.penghasilan || detailModalTarget.parent_data?.penghasilan_ibu || '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Wali -->
                    <div
                        v-if="detailModalTarget.parent_data?.wali?.nama || detailModalTarget.parent_data?.nama_wali"
                        class="rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="mb-4 flex items-center justify-between border-b border-gray-100 pb-3.5 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-amber-100 bg-amber-50 text-amber-600 dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm sm:text-base font-bold text-gray-900 dark:text-slate-100">
                                        Data Wali Santri
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-slate-400">Informasi wali penanggung jawab</p>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-amber-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Nama Wali</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.wali?.nama || detailModalTarget.parent_data?.nama_wali || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-amber-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">NIK Wali</p>
                                <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.wali?.nik || detailModalTarget.parent_data?.nik_wali || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-amber-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">No. HP Wali</p>
                                <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.wali?.nomor_hp || detailModalTarget.parent_data?.nomor_hp_wali || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-amber-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Pekerjaan & Penghasilan</p>
                                <p class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-slate-100">{{ detailModalTarget.parent_data?.wali?.pekerjaan || detailModalTarget.parent_data?.pekerjaan_wali || '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: DATA ALAMAT & DOMISILI -->
                <div v-show="activeDetailStep === 3" class="space-y-4">
                    <!-- Catatan Khusus Textarea -->
                    <div class="rounded-2xl border border-rose-200 bg-rose-50/50 p-4 dark:border-rose-900/50 dark:bg-rose-950/30 space-y-2">
                        <label class="block text-xs font-black text-rose-800 dark:text-rose-300 uppercase tracking-wider">
                            Catatan Khusus Alamat & Domisili
                        </label>
                        <textarea
                            v-model="verifyForm.catatan_address"
                            rows="3"
                            placeholder="Tuliskan catatan perbaikan alamat (misal: Alamat RT/RW dan kelurahan belum lengkap)..."
                            class="w-full rounded-xl border border-rose-300 bg-white p-3 text-xs sm:text-sm text-gray-900 shadow-xs focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 focus:outline-none dark:border-rose-800 dark:bg-slate-900 dark:text-slate-100"
                        ></textarea>
                    </div>

                    <!-- Alamat Domisili Card (100% PERSIS RINCIAN MODAL) -->
                    <div
                        class="rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="mb-5 flex items-center justify-between border-b border-gray-100 pb-3.5 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-100 bg-emerald-50 text-emerald-600 dark:border-emerald-900/50 dark:bg-emerald-950/50 dark:text-emerald-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm sm:text-base font-bold text-gray-900 dark:text-slate-100">
                                        Alamat Domisili & Transportasi
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-slate-400">Informasi tempat tinggal dan rute ke pesantren</p>
                                </div>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-400 uppercase tracking-wider">Tahap 3</span>
                        </div>

                        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="sm:col-span-2 lg:col-span-3 rounded-2xl border border-gray-100/90 bg-gray-50/70 p-4 transition-colors hover:bg-emerald-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Alamat Lengkap</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100 leading-relaxed">{{ detailModalTarget.address_data?.alamat_lengkap || detailModalTarget.address_data?.alamat || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-emerald-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">RT / RW</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">RT {{ detailModalTarget.address_data?.rt || '-' }} / RW {{ detailModalTarget.address_data?.rw || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-emerald-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Kelurahan / Desa</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.address_data?.kelurahan_desa || detailModalTarget.address_data?.kelurahan || detailModalTarget.address_data?.desa || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-emerald-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Kecamatan</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.address_data?.kecamatan || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-emerald-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Kabupaten / Kota</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.address_data?.kabupaten_kota || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-emerald-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Provinsi</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.address_data?.provinsi || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-emerald-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Kode Pos</p>
                                <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.address_data?.kode_pos || '-' }}</p>
                            </div>
                            <div class="sm:col-span-2 lg:col-span-3 rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-emerald-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Jarak & Transportasi ke Pesantren</p>
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">
                                    {{ detailModalTarget.address_data?.jarak_ke_pondok || '-' }} &bull; Transportasi: {{ detailModalTarget.address_data?.transportasi || '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: DATA PENDIDIKAN & PILIHAN -->
                <div v-show="activeDetailStep === 4" class="space-y-4">
                    <!-- Catatan Khusus Textarea -->
                    <div class="rounded-2xl border border-rose-200 bg-rose-50/50 p-4 dark:border-rose-900/50 dark:bg-rose-950/30 space-y-2">
                        <label class="block text-xs font-black text-rose-800 dark:text-rose-300 uppercase tracking-wider">
                            Catatan Khusus Pilihan Pendidikan
                        </label>
                        <textarea
                            v-model="verifyForm.catatan_education"
                            rows="3"
                            placeholder="Tuliskan catatan perbaikan pilihan pendidikan (misal: NISN tidak valid atau nama sekolah asal belum sesuai)..."
                            class="w-full rounded-xl border border-rose-300 bg-white p-3 text-xs sm:text-sm text-gray-900 shadow-xs focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 focus:outline-none dark:border-rose-800 dark:bg-slate-900 dark:text-slate-100"
                        ></textarea>
                    </div>

                    <!-- Cards Pilihan Jenjang & Sekolah Asal (100% PERSIS RINCIAN MODAL) -->
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <!-- Pilihan Masuk / Program Jenjang Tujuan -->
                        <div class="rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
                            <div class="mb-5 flex items-center justify-between border-b border-gray-100 pb-3.5 dark:border-slate-800">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-primary/20 bg-primary/10 text-primary dark:border-blue-900/50 dark:bg-blue-950/50 dark:text-blue-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm sm:text-base font-bold text-gray-900 dark:text-slate-100">
                                            Pilihan Program Tujuan
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-slate-400">Jenjang & program yang dipilih</p>
                                    </div>
                                </div>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-400 uppercase tracking-wider">Tahap 4</span>
                            </div>

                            <!-- If Jenjang Selected -->
                            <div v-if="detailModalTarget.jenjang?.name || detailModalTarget.education_data?.jenjang" class="space-y-3">
                                <div class="flex items-center gap-3 rounded-2xl border border-primary/20 bg-primary/5 p-4 dark:border-blue-500/30 dark:bg-blue-950/30">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white p-1.5 shadow-2xs dark:bg-slate-800">
                                        <img
                                            :src="getJenjangLogo(detailModalTarget.jenjang?.code || detailModalTarget.education_data?.jenjang)"
                                            class="h-full w-full object-contain"
                                        />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold tracking-wider text-primary uppercase dark:text-blue-400">Jenjang Pendidikan Tujuan</p>
                                        <h5 class="text-base font-extrabold text-gray-900 dark:text-slate-100">{{ detailModalTarget.jenjang?.name || detailModalTarget.education_data?.jenjang }}</h5>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Jalur Pendaftaran</p>
                                        <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.tipe_pendaftaran || detailModalTarget.education_data?.tipe_pendaftaran || 'Reguler' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Kelas / Program</p>
                                        <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ getEducationSubText(detailModalTarget) || '-' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- If Jenjang NOT Selected -->
                            <div v-else class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 p-6 text-center dark:border-slate-800 dark:bg-slate-800/40">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 14l9-5-9-5-9 5 9 5z" />
                                    </svg>
                                </div>
                                <p class="mt-2 text-xs font-semibold text-slate-600 dark:text-slate-400">Jenjang Belum Dipilih</p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Calon santri baru berada di tahap 1-3 formulir.</p>
                            </div>
                        </div>

                        <!-- Sekolah Asal -->
                        <div class="rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
                            <div class="mb-5 flex items-center justify-between border-b border-gray-100 pb-3.5 dark:border-slate-800">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-amber-100 bg-amber-50 text-amber-600 dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm sm:text-base font-bold text-gray-900 dark:text-slate-100">
                                            Riwayat Sekolah Sebelumnya
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-slate-400">Sekolah asal calon santri</p>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-amber-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Nama Sekolah Asal</p>
                                    <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.education_data?.pendidikan_sebelumnya?.nama_sekolah || detailModalTarget.education_data?.asal_sekolah || '-' }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-amber-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">NISN</p>
                                        <p class="font-mono text-xs sm:text-sm font-bold text-primary dark:text-blue-400">{{ detailModalTarget.education_data?.pendidikan_sebelumnya?.nisn || detailModalTarget.education_data?.nisn || '-' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-amber-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">NPSN</p>
                                        <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ detailModalTarget.education_data?.pendidikan_sebelumnya?.npsn || detailModalTarget.education_data?.npsn || '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: DOKUMEN PERSYARATAN -->
                <div v-show="activeDetailStep === 5" class="space-y-4">
                    <!-- Summary Alert Banner (100% PERSIS RINCIAN MODAL) -->
                    <div
                        class="flex items-center justify-between rounded-2xl border p-4 shadow-2xs"
                        :class="[
                            getApplicantDocumentSummary(detailModalTarget).isComplete
                                ? 'border-emerald-200 bg-emerald-50/90 text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300'
                                : 'border-amber-200 bg-amber-50/90 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/60 dark:text-amber-300',
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl font-bold shadow-2xs"
                                :class="getApplicantDocumentSummary(detailModalTarget).isComplete ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-white'"
                            >
                                {{ getApplicantDocumentSummary(detailModalTarget).isComplete ? '✓' : '!' }}
                            </span>
                            <div>
                                <h5 class="text-xs sm:text-sm font-bold">
                                    {{
                                        getApplicantDocumentSummary(detailModalTarget).isComplete
                                            ? 'Dokumen Persyaratan Wajib Lengkap'
                                            : 'Dokumen Persyaratan Wajib Belum Lengkap'
                                    }}
                                </h5>
                                <p class="text-xs opacity-80">
                                    Disesuaikan untuk jenjang {{ detailModalTarget.jenjang?.name || '-' }} (Jalur {{ detailModalTarget.tipe_pendaftaran || 'Reguler' }}).
                                </p>
                            </div>
                        </div>
                        <span class="font-mono text-xs font-bold shrink-0">
                            {{ getApplicantDocumentSummary(detailModalTarget).uploadedRequiredCount }}/{{ getApplicantDocumentSummary(detailModalTarget).requiredCount }} Wajib
                        </span>
                    </div>

                    <!-- Document Items List (100% PERSIS RINCIAN MODAL + TEXTAREA CATATAN DI TIAP CARD) -->
                    <div class="space-y-3">
                        <div
                            v-if="getTargetApplicableDocuments.length === 0"
                            class="rounded-2xl border border-dashed border-gray-200 p-8 text-center text-xs text-gray-500 dark:border-slate-800 dark:text-slate-400"
                        >
                            Tidak ada persyaratan dokumen khusus untuk jenjang dan jalur ini.
                        </div>

                        <div
                            v-else
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
                        >
                            <div
                                v-for="doc in getTargetApplicableDocuments"
                                :key="doc.id"
                                class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-gray-200/90 bg-white p-3 shadow-2xs transition-all duration-200 hover:border-primary/40 hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                            >
                                <!-- Top Bar: Document Type & Required Status -->
                                <div class="flex items-center justify-between gap-1.5 mb-2.5">
                                    <span class="truncate text-xs font-black text-slate-800 dark:text-slate-100" :title="doc.name">
                                        {{ doc.name }}
                                    </span>
                                    <span
                                        v-if="doc.is_required"
                                        class="shrink-0 rounded-full bg-rose-50 border border-rose-200 px-2 py-0.2 text-[9px] font-black uppercase text-rose-700 dark:bg-rose-950/60 dark:border-rose-900/50 dark:text-rose-300"
                                    >
                                        Wajib
                                    </span>
                                    <span
                                        v-else
                                        class="shrink-0 rounded-full bg-slate-100 px-2 py-0.2 text-[9px] font-black uppercase text-slate-500 dark:bg-slate-800 dark:text-slate-400"
                                    >
                                        Opsional
                                    </span>
                                </div>

                                <!-- Visual Preview Container (Compact Thumbnail & Direct Open) -->
                                <div class="relative h-32 w-full overflow-hidden rounded-xl border border-gray-100 bg-gray-50 dark:border-slate-800 dark:bg-slate-800/60 flex items-center justify-center">
                                    <template v-if="getUploadedDocumentRecord(doc.id)">
                                        <!-- Image Preview -->
                                        <template v-if="isImageFile(getDocumentFileUrl(getUploadedDocumentRecord(doc.id)))">
                                            <img
                                                :src="getDocumentFileUrl(getUploadedDocumentRecord(doc.id))!"
                                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                                :alt="doc.name"
                                            />
                                        </template>

                                        <!-- PDF Preview -->
                                        <template v-else-if="isPdfFile(getDocumentFileUrl(getUploadedDocumentRecord(doc.id)))">
                                            <div class="relative h-full w-full bg-slate-100 dark:bg-slate-950 flex flex-col items-center justify-center p-2">
                                                <svg class="h-8 w-8 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                                <span class="mt-1 text-[10px] font-black uppercase text-rose-600">Dokumen PDF</span>
                                            </div>
                                        </template>

                                        <!-- Fallback File -->
                                        <template v-else>
                                            <div class="flex flex-col items-center justify-center p-2 text-slate-400">
                                                <svg class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span class="mt-1 text-[10px] font-bold">Berkas Terunggah</span>
                                            </div>
                                        </template>

                                        <!-- Hover Action Overlay -->
                                        <div class="absolute inset-0 bg-black/40 opacity-0 backdrop-blur-2xs transition-opacity group-hover:opacity-100 flex items-center justify-center gap-1.5 p-2">
                                            <a
                                                v-if="getDocumentFileUrl(getUploadedDocumentRecord(doc.id))"
                                                :href="getDocumentFileUrl(getUploadedDocumentRecord(doc.id))!"
                                                target="_blank"
                                                class="inline-flex items-center gap-1 rounded-lg bg-white px-2.5 py-1 text-[11px] font-bold text-slate-900 shadow-md transition-transform hover:scale-105"
                                            >
                                                <svg class="h-3.5 w-3.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span>Buka Tab</span>
                                            </a>
                                        </div>
                                    </template>

                                    <!-- Empty / Not uploaded yet -->
                                    <template v-else>
                                        <div class="flex flex-col items-center justify-center p-3 text-center">
                                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                                </svg>
                                            </div>
                                            <span class="mt-1.5 text-[10.5px] font-semibold text-slate-400 dark:text-slate-500">
                                                {{ doc.is_required ? 'Belum Diunggah (Wajib)' : 'Belum Diunggah' }}
                                            </span>
                                        </div>
                                    </template>
                                </div>

                                <!-- Card Footer: Document Details -->
                                <div class="mt-2.5 space-y-1">
                                    <div class="flex items-center justify-between text-[10.5px] text-slate-500 dark:text-slate-400">
                                        <span class="uppercase font-bold">{{ doc.type === 'gambar' ? 'Gambar' : doc.type === 'semua' ? 'PDF / Gambar' : 'PDF' }}</span>
                                        <span v-if="getUploadedDocumentRecord(doc.id)">{{ formatDateTime(getUploadedDocumentRecord(doc.id)?.created_at) }}</span>
                                        <span v-else class="text-rose-500 font-bold">Kosong</span>
                                    </div>
                                </div>

                                <!-- Specific Document Revision Note Input (Prominent Textarea) -->
                                <div class="mt-2.5 border-t border-gray-100 pt-2 dark:border-slate-800 space-y-1">
                                    <label class="block text-[10.5px] font-bold text-rose-700 dark:text-rose-400">
                                        Catatan Koreksi Berkas:
                                    </label>
                                    <textarea
                                        v-model="verifyForm.dokumen_catatan[doc.id]"
                                        rows="2"
                                        :placeholder="`Catatan koreksi untuk ${doc.name} (cth: file buram/terpotong)...`"
                                        class="w-full rounded-xl border border-rose-200 bg-white p-2 text-xs text-gray-900 shadow-2xs focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex w-full items-center justify-between gap-2">
                    <SecondaryButton @click="closePerbaikanModal" type="button">
                        Batal
                    </SecondaryButton>
                    <button
                        type="button"
                        @click="submitPerbaikan"
                        :disabled="verifyForm.processing"
                        class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-rose-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-rose-600/20 hover:bg-rose-700 transition-all disabled:opacity-50 sm:text-sm"
                    >
                        {{ verifyForm.processing ? 'Menyimpan Catatan...' : 'Kirim Catatan & Minta Perbaikan' }}
                    </button>
                </div>
            </template>
        </Modal>

        <!-- ======================================================= -->
        <!-- MODAL: VERIFIKASI MASSAL (BULK VERIFY) -->
        <!-- ======================================================= -->
        <Modal
            :show="isBulkVerifyModalOpen"
            @close="closeBulkVerifyModal"
            maxWidth="md"
            title="Verifikasi Massal Pendaftar"
            description="Proses verifikasi untuk seluruh pendaftar yang dipilih."
        >
            <template #icon>
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 sm:h-12 sm:w-12 dark:bg-emerald-950/50 dark:text-emerald-400"
                >
                    <svg
                        class="h-5 w-5 sm:h-6 sm:w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>
            </template>

            <div class="space-y-4">
                <div
                    class="text-xs text-gray-600 sm:text-sm dark:text-slate-300"
                >
                    Jumlah pendaftar terpilih:
                    <strong class="font-bold text-primary dark:text-blue-400"
                        >{{ selectedRows.length }} calon santri</strong
                    >
                </div>

                <div class="space-y-2">
                    <label
                        class="block text-xs font-bold text-gray-700 sm:text-sm dark:text-slate-200 dark:text-slate-300"
                        >Keputusan Verifikasi Massal</label
                    >
                    <div
                        class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 sm:gap-3"
                    >
                        <!-- Opsi Terima (Aktif) -->
                        <button
                            type="button"
                            @click="bulkVerifyForm.action = 'terima'"
                            class="flex cursor-pointer flex-row items-center justify-start gap-3 rounded-2xl border p-3.5 text-left transition-all sm:flex-col sm:justify-center sm:gap-1.5 sm:p-4 sm:text-center border-emerald-500 bg-emerald-50/80 text-emerald-800 shadow-xs ring-2 ring-emerald-500/20 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-500/30"
                        >
                            <span class="text-xs font-black sm:text-sm"
                                >TERIMA</span
                            >
                            <span
                                class="mt-0.5 text-[10px] text-emerald-600/90 sm:text-xs dark:text-emerald-400/90"
                                >Lanjut ke Tagihan</span
                            >
                        </button>

                        <!-- Opsi Tolak (Disabled dengan Keterangan) -->
                        <div
                            class="relative flex cursor-not-allowed opacity-60 flex-row items-center justify-start gap-3 rounded-2xl border border-dashed border-gray-200 bg-gray-50/70 p-3.5 text-left sm:flex-col sm:justify-center sm:gap-1.5 sm:p-4 sm:text-center dark:border-slate-800 dark:bg-slate-800/40"
                            title="Penolakan massal dinonaktifkan karena catatan perbaikan harus diisi spesifik per pendaftar pada modal aksi baris."
                        >
                            <span class="text-xs font-bold text-gray-400 sm:text-sm line-through"
                                >TOLAK / REVISI</span
                            >
                            <span
                                class="mt-0.5 text-[10px] text-gray-400 sm:text-[10px] leading-tight"
                                >Perlu Catatan Spesifik</span
                            >
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-3 text-xs leading-relaxed text-blue-900 dark:border-blue-900/50 dark:bg-blue-950/40 dark:text-blue-300">
                    <p>
                        Seluruh <strong class="font-bold">{{ selectedRows.length }} pendaftar terpilih</strong> akan diverifikasi dan dipindahkan ke status <strong>Tagihan</strong>.
                    </p>
                </div>
            </div>

            <template #footer>
                <div
                    class="flex w-full flex-col-reverse justify-end gap-2 sm:flex-row"
                >
                    <SecondaryButton
                        @click="closeBulkVerifyModal"
                        type="button"
                        class="w-full justify-center sm:w-auto"
                    >
                        Batal
                    </SecondaryButton>
                    <button
                        type="button"
                        @click="submitBulkVerify"
                        :disabled="bulkVerifyForm.processing"
                        class="inline-flex w-full cursor-pointer items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-emerald-600/20 hover:bg-emerald-700 transition-all sm:w-auto sm:text-sm disabled:opacity-50"
                    >
                        {{
                            bulkVerifyForm.processing
                                ? 'Memproses...'
                                : 'Terima Massal Terpilih'
                        }}
                    </button>
                </div>
            </template>
        </Modal>

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
                    class="rounded-xl border border-gray-100 bg-gray-50 p-3.5 text-xs text-gray-700 dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-800/70 dark:text-slate-300"
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
            title="Hapus Pendaftar Submit"
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
                Apakah Anda yakin ingin menghapus data pendaftar submit
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
                    >{{ selectedRows.length }} data pendaftar submit</strong
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
