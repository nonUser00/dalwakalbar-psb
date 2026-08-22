<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AkademikWaveFilterCards from '@/Components/AkademikWaveFilterCards.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DataTable from '@/Components/DataTable.vue';
import ActionMenu from '@/Components/Form/ActionMenu.vue';
import CurrencyInput from '@/Components/Form/CurrencyInput.vue';
import CustomDatePicker from '@/Components/Form/CustomDatePicker.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import FilterModal from '@/Components/Form/FilterModal.vue';
import PasswordInput from '@/Components/Form/PasswordInput.vue';
import TextareaInput from '@/Components/Form/TextareaInput.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { getBankLogo, handleBankLogoError } from '@/lib/bank';
import { show } from '@/routes/admin/pendaftar';
import {
    index,
    show_detail,
    create,
    destroy_bill,
    add_payment,
    edit_payment,
    verify_payment,
    reset_password,
    destroy as destroyRoute,
    bulk_destroy,
    exportMethod,
} from '@/routes/admin/pendaftar/tagihan';

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

interface BankItem {
    id: string;
    name: string;
    kode_bank?: string;
    singkatan?: string;
}

const props = defineProps<{
    pendaftars: any;
    jenjangs: JenjangItem[];
    jenjangCounts: Record<string, any>;
    selectedJenjangId: string;
    cabangs: CabangItem[];
    activeTahunAkademik?: ActiveTahunAkademik | null;
    hasActiveTahunAkademik?: boolean;
    gelombangs?: any[];
    banks: BankItem[];
    kategoriBiayas: any[];
    filters: {
        search: string;
        limit: number;
        jenjang_id: string;
        cabang_id: string;
        gelombang_id: string;
        gender: string;
        status_pembuatan_tagihan: string;
        status_tagihan: string;
        status_pembayaran: string;
        start_date: string;
        end_date: string;
    };
}>();

// ==========================================
// JENJANG ORDER & LOGO HELPER
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

const activeJenjangId = ref(
    props.selectedJenjangId || (orderedJenjangs.value[0]?.id ?? ''),
);

const activeJenjang = computed(() => {
    return (
        orderedJenjangs.value.find((j) => j.id === activeJenjangId.value) ||
        orderedJenjangs.value[0]
    );
});

const isJenjangDrawerOpen = ref(false);

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

const getJenjangScopeText = (code?: string) => {
    const c = (code || '').toUpperCase();

    if (c === 'MTS') {
        return 'Tingkat Wustha / Menengah Pertama';
    }

    if (c === 'MA') {
        return 'Tingkat Ulya / Menengah Atas';
    }

    if (c === 'S1') {
        return 'Program Sarjana Terakreditasi';
    }

    if (c === 'S2') {
        return 'Program Magister Pascasarjana';
    }

    if (c === 'S3') {
        return 'Program Doktoral Pascasarjana';
    }

    return 'Program Pendidikan Santri';
};

const formatStatCount = (val?: any) => {
    if (val === undefined || val === null) {
return '0';
}

    if (typeof val === 'object') {
        val = val.total ?? 0;
    }

    const num = Number(val);

    if (isNaN(num)) {
return '0';
}

    return new Intl.NumberFormat('id-ID').format(num);
};

const getJenjangActionCount = (jenjangId: string) => {
    const counts = props.jenjangCounts?.[jenjangId];

    if (!counts) {
return 0;
}

    const belumDibuat = Number(counts.belum_dibuat || 0);
    const menungguVerifikasi = Number(counts.menunggu_verifikasi || 0);

    return belumDibuat + menungguVerifikasi;
};

const formatBadgeCount = (count: number) => {
    if (count > 99) {
return '99+';
}

    return String(count);
};

// ==========================================
// ==========================================
// TABLE CONFIGURATION & SELECTION
// ==========================================
const columns = [
    { key: 'nomor_pendaftaran', label: 'NO REGISTRASI', sortable: true },
    { key: 'pendaftar', label: 'CALON SANTRI', sortable: true },
    { key: 'gender', label: 'GENDER', sortable: true },
    { key: 'cabang', label: 'CABANG', sortable: false },
    { key: 'jenjang', label: 'JENJANG', sortable: false },
    {
        key: 'status_pembuatan_tagihan',
        label: 'STATUS PEMBUATAN',
        sortable: false,
    },
    { key: 'status_tagihan', label: 'STATUS BAYAR', sortable: false },
    { key: 'jumlah_tagihan', label: 'TOTAL & SISA', sortable: false },
    { key: 'status_pembayaran', label: 'VERIFIKASI BAYAR', sortable: false },
    { key: 'status', label: 'STATUS', sortable: true },
];

const selectedRows = ref<string[]>([]);
const handleSelection = (ids: string[]) => {
    selectedRows.value = ids;
};

const clearSelection = () => {
    selectedRows.value = [];
};

const selectedBelumDibuatRows = computed(() => {
    const list = selectedRows.value
        .map((id) => props.pendaftars.data.find((p) => p.id === id))
        .filter((item) => item && !getApplicantTagihan(item));

    if (list.length === 0) return [];

    // Check if there are standard pendaftar tagihans vs interview ulang
    const hasRegular = list.some((p) => !p.is_interview_ulang);
    const hasInterview = list.some((p) => p.is_interview_ulang);

    // Prioritaskan pendaftaran baru jika ada keduanya
    if (hasRegular && hasInterview) {
        return list.filter((p) => !p.is_interview_ulang).map((p) => p.id);
    }

    return list.map((p) => p.id);
});

const createTagihanUrl = computed(() => {
    const query: Record<string, any> = {
        jenjang_id: activeJenjangId.value,
    };

    if (selectedBelumDibuatRows.value.length > 0) {
        query.ids = selectedBelumDibuatRows.value.join(',');
    }

    return create.url({ query });
});

// Switch Jenjang Tab
const switchJenjang = (id: string) => {
    activeJenjangId.value = id;
    selectedRows.value = [];
    router.get(
        index.url(),
        {
            ...props.filters,
            jenjang_id: id,
            page: 1,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

// ==========================================
// DETAIL URL HELPERS
// ==========================================
const getDetailUrl = (id: string) => {
    let currentUrl = '/admin/pendaftar/tagihan';

    if (typeof window !== 'undefined') {
        const search = window.location.search;
        currentUrl +=
            search ||
            (activeJenjangId.value
                ? `?jenjang_id=${activeJenjangId.value}`
                : '');
    } else if (activeJenjangId.value) {
        currentUrl += `?jenjang_id=${activeJenjangId.value}`;
    }

    return `${show.url(id)}?from=${encodeURIComponent(currentUrl)}`;
};

const getTagihanDetailUrl = (id: string) => {
    let currentUrl = '/admin/pendaftar/tagihan';

    if (typeof window !== 'undefined') {
        const search = window.location.search;
        currentUrl +=
            search ||
            (activeJenjangId.value
                ? `?jenjang_id=${activeJenjangId.value}`
                : '');
    } else if (activeJenjangId.value) {
        currentUrl += `?jenjang_id=${activeJenjangId.value}`;
    }

    return `${show_detail.url(id)}?from=${encodeURIComponent(currentUrl)}`;
};

// ==========================================
// SEARCH & LIMIT DEBOUNCE
// ==========================================
const search = ref(props.filters.search || '');
let searchTimeout: any = null;

const onSearchInput = (val: string) => {
    search.value = val;
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            index.url(),
            {
                ...props.filters,
                search: val,
                jenjang_id: activeJenjangId.value,
                page: 1,
            },
            { preserveState: true, replace: true },
        );
    }, 300);
};

const onLimitChange = (newLimit: number) => {
    router.get(
        index.url(),
        {
            ...props.filters,
            limit: newLimit,
            jenjang_id: activeJenjangId.value,
            page: 1,
        },
        { preserveState: true, replace: true },
    );
};

// ==========================================
// FILTER MODAL & REACTIVE STATE
// ==========================================
const isFilterModalOpen = ref(false);

const filterForm = useForm({
    cabang_id: props.filters.cabang_id || '',
    gelombang_id: props.filters.gelombang_id || '',
    gender: props.filters.gender || '',
    status_pembuatan_tagihan: props.filters.status_pembuatan_tagihan || '',
    status_tagihan: props.filters.status_tagihan || '',
    status_pembayaran: props.filters.status_pembayaran || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

// Keep filterForm values in sync with URL props
watch(
    () => props.filters,
    (newFilters) => {
        filterForm.cabang_id = newFilters.cabang_id || '';
        filterForm.gelombang_id = newFilters.gelombang_id || '';
        filterForm.gender = newFilters.gender || '';
        filterForm.status_pembuatan_tagihan = newFilters.status_pembuatan_tagihan || '';
        filterForm.status_tagihan = newFilters.status_tagihan || '';
        filterForm.status_pembayaran = newFilters.status_pembayaran || '';
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
        Boolean(props.filters.status_pembuatan_tagihan) ||
        Boolean(props.filters.status_tagihan) ||
        Boolean(props.filters.status_pembayaran) ||
        Boolean(props.filters.start_date) ||
        Boolean(props.filters.end_date)
    );
});

const applyFilters = () => {
    isFilterModalOpen.value = false;
    router.get(
        index.url(),
        {
            search: search.value,
            limit: props.filters.limit,
            jenjang_id: activeJenjangId.value,
            cabang_id: filterForm.cabang_id,
            gelombang_id: filterForm.gelombang_id,
            gender: filterForm.gender,
            status_pembuatan_tagihan: filterForm.status_pembuatan_tagihan,
            status_tagihan: filterForm.status_tagihan,
            status_pembayaran: filterForm.status_pembayaran,
            start_date: filterForm.start_date,
            end_date: filterForm.end_date,
            page: 1,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const resetFilters = () => {
    filterForm.cabang_id = '';
    filterForm.gender = '';
    filterForm.status_pembuatan_tagihan = '';
    filterForm.status_tagihan = '';
    filterForm.status_pembayaran = '';
    filterForm.start_date = '';
    filterForm.end_date = '';
    isFilterModalOpen.value = false;
    router.get(
        index.url(),
        {
            search: search.value,
            limit: props.filters.limit,
            jenjang_id: activeJenjangId.value,
            gelombang_id: filterForm.gelombang_id,
            page: 1,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

// ==========================================
// HELPERS: TAGIHAN & PEMBAYARAN DATA
// ==========================================
const getApplicantTagihan = (row: any) => {
    if (!row.tagihans || row.tagihans.length === 0) {
        return null;
    }

    if (row.is_interview_ulang && row.interview_ulang_at) {
        const targetTime = new Date(row.interview_ulang_at).getTime();
        const reinterviewTagihan = row.tagihans.find((t: any) => {
            return t.created_at && new Date(t.created_at).getTime() >= targetTime;
        });
        return reinterviewTagihan || null;
    }

    return row.tagihans[0];
};

const getApplicantTotalPaid = (tagihan: any) => {
    if (!tagihan || !tagihan.pembayarans) {
        return 0;
    }

    return tagihan.pembayarans
        .filter((p: any) => p.status === 'DITERIMA')
        .reduce((sum: number, p: any) => sum + parseFloat(p.amount || 0), 0);
};

const getApplicantPendingPayment = (tagihan: any) => {
    if (!tagihan || !tagihan.pembayarans) {
        return null;
    }

    return tagihan.pembayarans.find(
        (p: any) => p.status === 'MENUNGGU_VERIFIKASI',
    );
};

const formatRupiah = (amount?: number | string) => {
    const num = parseFloat(String(amount || 0));

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
};

// ==========================================
// MODAL: TAMBAH PEMBAYARAN MANUAL (TUNAI / SAMAHA / TRANSFER)
// ==========================================
const isAddPaymentModalOpen = ref(false);
const addPaymentTargetPendaftar = ref<any>(null);
const addPaymentTargetTagihan = ref<any>(null);

const addPaymentForm = useForm({
    payment_method: 'TUNAI' as 'TUNAI' | 'SAMAHA' | 'TRANSFER',
    amount: 0,
    bank_id: '',
    payment_date: new Date().toISOString().split('T')[0],
    catatan: '',
});

const openAddPaymentModal = (tagihan: any, optionalRow?: any) => {
    addPaymentTargetTagihan.value = tagihan;
    addPaymentTargetPendaftar.value = optionalRow || tagihan?.pendaftar || null;

    const sisa = Math.max(
        0,
        parseFloat(tagihan?.total_amount || 0) - getApplicantTotalPaid(tagihan),
    );

    addPaymentForm.reset();
    addPaymentForm.clearErrors();
    addPaymentForm.payment_method = 'TUNAI';
    addPaymentForm.amount = sisa;
    addPaymentForm.bank_id = '';
    addPaymentForm.payment_date = new Date().toISOString().split('T')[0];
    addPaymentForm.catatan = 'Pembayaran Tunai di Sekretariat';
    isAddPaymentModalOpen.value = true;
};

const closeAddPaymentModal = () => {
    isAddPaymentModalOpen.value = false;
    addPaymentTargetPendaftar.value = null;
    addPaymentTargetTagihan.value = null;
    addPaymentForm.reset();
    addPaymentForm.clearErrors();
};

const submitAddPayment = () => {
    if (!addPaymentTargetTagihan.value) {
        return;
    }

    addPaymentForm.post(add_payment.url(addPaymentTargetTagihan.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeAddPaymentModal();
        },
    });
};

// ==========================================
// MODAL: VERIFIKASI BUKTI TRANSFER (TERIMA / TOLAK)
// ==========================================
const isVerifyPaymentModalOpen = ref(false);
const verifyPaymentTarget = ref<any>(null);
const verifyPaymentTargetRow = ref<any>(null);

const verifyPaymentForm = useForm({
    action: 'terima' as 'terima' | 'tolak',
    amount_verified: 0,
    alasan_penolakan: '',
    catatan: '',
});

const openVerifyPaymentModal = (pembayaran: any, optionalRow?: any) => {
    verifyPaymentTarget.value = pembayaran;
    verifyPaymentTargetRow.value = optionalRow || null;
    verifyPaymentForm.reset();
    verifyPaymentForm.clearErrors();
    verifyPaymentForm.action = 'terima';
    verifyPaymentForm.amount_verified = parseFloat(pembayaran?.amount || 0);
    verifyPaymentForm.alasan_penolakan =
        pembayaran?.status === 'DITOLAK' && pembayaran?.catatan
            ? pembayaran.catatan
            : '';
    verifyPaymentForm.catatan =
        pembayaran?.status === 'DITERIMA' && pembayaran?.catatan
            ? pembayaran.catatan
            : 'Pembayaran pendaftaran telah diverifikasi dan diterima';
    isVerifyPaymentModalOpen.value = true;
};

const closeVerifyPaymentModal = () => {
    isVerifyPaymentModalOpen.value = false;
    verifyPaymentTarget.value = null;
    verifyPaymentTargetRow.value = null;
    verifyPaymentForm.reset();
    verifyPaymentForm.clearErrors();
};

const submitVerifyPayment = () => {
    if (!verifyPaymentTarget.value) {
return;
}

    verifyPaymentForm.clearErrors();

    const newAmt = parseFloat(String(verifyPaymentForm.amount_verified || 0));

    if (verifyPaymentForm.action === 'terima') {
        if (newAmt <= 0) {
            verifyPaymentForm.setError(
                'amount_verified',
                'Nominal pembayaran harus lebih besar dari Rp 0.',
            );

            return;
        }

        const targetTagihan =
            getApplicantTagihan(verifyPaymentTargetRow.value) ||
            verifyPaymentTarget.value.tagihan;

        if (targetTagihan) {
            const allPayments =
                targetTagihan.pembayarans ||
                verifyPaymentTargetRow.value?.tagihans?.[0]?.pembayarans ||
                [];
            const otherPaid = allPayments
                .filter(
                    (p: any) =>
                        p.id !== verifyPaymentTarget.value.id &&
                        p.status === 'DITERIMA',
                )
                .reduce(
                    (sum: number, p: any) => sum + parseFloat(p.amount || 0),
                    0,
                );
            const totalTagihanAmt = parseFloat(targetTagihan.total_amount || 0);
            const maxAllowed = Math.max(0, totalTagihanAmt - otherPaid);

            if (totalTagihanAmt > 0 && newAmt > maxAllowed) {
                verifyPaymentForm.setError(
                    'amount_verified',
                    `Nominal (${formatRupiah(newAmt)}) melebihi sisa tagihan yang harus dibayar (${formatRupiah(maxAllowed)}).`,
                );

                return;
            }
        }
    } else if (verifyPaymentForm.action === 'tolak') {
        if (!verifyPaymentForm.alasan_penolakan?.trim()) {
            verifyPaymentForm.setError(
                'alasan_penolakan',
                'Alasan penolakan pembayaran wajib diisi.',
            );

            return;
        }
    }

    verifyPaymentForm.post(verify_payment.url(verifyPaymentTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeVerifyPaymentModal();
        },
    });
};

const getProofUrl = (payment: any) => {
    if (!payment) {
return null;
}

    if (payment.proof_path) {
        return payment.proof_path.startsWith('http')
            ? payment.proof_path
            : `/storage/${payment.proof_path}`;
    }

    if (payment.bukti_transfer_url) {
return payment.bukti_transfer_url;
}

    if (payment.bukti_transfer) {
return `/storage/${payment.bukti_transfer}`;
}

    return null;
};

const isProofModalOpen = ref(false);
const proofModalUrl = ref('');
const openProofModal = (url: string) => {
    proofModalUrl.value = url;
    isProofModalOpen.value = true;
};

// ==========================================
// MODAL: EDIT PEMBAYARAN
// ==========================================
const isEditPaymentModalOpen = ref(false);
const editPaymentTarget = ref<any>(null);

const editPaymentForm = useForm({
    amount: 0,
    catatan: '',
});

const openEditPaymentModal = (pembayaran: any) => {
    editPaymentTarget.value = pembayaran;
    editPaymentForm.reset();
    editPaymentForm.clearErrors();
    editPaymentForm.amount = parseFloat(pembayaran.amount || 0);
    editPaymentForm.catatan = pembayaran.catatan || '';
    isEditPaymentModalOpen.value = true;
};

const closeEditPaymentModal = () => {
    isEditPaymentModalOpen.value = false;
    editPaymentTarget.value = null;
    editPaymentForm.reset();
    editPaymentForm.clearErrors();
};

const submitEditPayment = () => {
    if (!editPaymentTarget.value) {
        return;
    }

    editPaymentForm.put(edit_payment.url(editPaymentTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditPaymentModal();
        },
    });
};

// ==========================================
// MODAL: HAPUS TAGIHAN
// ==========================================
const isDeleteTagihanModalOpen = ref(false);
const deleteTagihanTarget = ref<any>(null);
const deleteTagihanTargetPendaftar = ref<any>(null);
const deleteTagihanForm = useForm({});

const openDeleteTagihanModal = (tagihan: any, optionalRow?: any) => {
    deleteTagihanTarget.value = tagihan;
    deleteTagihanTargetPendaftar.value =
        optionalRow || tagihan?.pendaftar || null;
    isDeleteTagihanModalOpen.value = true;
};

const closeDeleteTagihanModal = () => {
    isDeleteTagihanModalOpen.value = false;
    deleteTagihanTarget.value = null;
    deleteTagihanTargetPendaftar.value = null;
};

const submitDeleteTagihan = () => {
    if (!deleteTagihanTarget.value) {
        return;
    }

    deleteTagihanForm.delete(destroy_bill.url(deleteTagihanTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteTagihanModal();
        },
    });
};

// ==========================================
// MODAL: RESET PASSWORD
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

    const birthDate = row.personal_data?.tanggal_lahir || '';

    if (birthDate) {
        const rawDate = birthDate.replace(/[^0-9]/g, '');

        if (rawDate) {
            resetPasswordForm.password = rawDate;
            resetPasswordForm.password_confirmation = rawDate;
        }
    }

    isResetPasswordModalOpen.value = true;
};

const closeResetPasswordModal = () => {
    isResetPasswordModalOpen.value = false;
    resetPasswordTarget.value = null;
    resetPasswordForm.reset();
    resetPasswordForm.clearErrors();
};

const autofillBirthdatePassword = () => {
    const birthDate =
        resetPasswordTarget.value?.personal_data?.tanggal_lahir || '';

    if (birthDate) {
        const rawDate = birthDate.replace(/[^0-9]/g, '');
        resetPasswordForm.password = rawDate;
        resetPasswordForm.password_confirmation = rawDate;
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
// MODAL: DELETE PENDAFTAR (SINGLE & BULK)
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
            selectedRows.value = selectedRows.value.filter(
                (id) => id !== deleteTarget.value?.id,
            );
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

        if (filterForm.status_pembuatan_tagihan) {
            params.append(
                'status_pembuatan_tagihan',
                filterForm.status_pembuatan_tagihan,
            );
        }

        if (filterForm.status_tagihan) {
            params.append('status_tagihan', filterForm.status_tagihan);
        }

        if (filterForm.status_pembayaran) {
            params.append('status_pembayaran', filterForm.status_pembayaran);
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

// ==========================================
// CETAK KARTU MODAL
// ==========================================
const openPrintCard = (row: any) => {
    window.open(`/admin/pendaftar/${row.id}/cetak-kartu`, '_blank');
};

// Display helpers
const formatDate = (dateStr?: string) => {
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

const formatDateTime = (dateStr?: string) => {
    if (!dateStr) {
        return '-';
    }

    try {
        const d = new Date(dateStr);

        return d.toLocaleDateString('id-ID', {
            day: 'numeric',
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
        return '?';
    }

    return name
        .split(' ')
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase();
};
</script>

<template>
    <div class="relative min-h-screen w-full">
        <Head title="Pendaftar Tagihan" />

        <!-- RIGHT DRAWER: JENJANG SELECTOR -->
        <Teleport to="body">
            <div
                v-if="isJenjangDrawerOpen"
                class="fixed inset-0 z-50 overflow-hidden"
            >
                <div
                    @click="isJenjangDrawerOpen = false"
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-300"
                ></div>

                <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div
                        class="w-screen max-w-md bg-white shadow-2xl transition-all duration-300 dark:bg-slate-900"
                    >
                        <!-- Drawer Header -->
                        <div
                            class="flex items-center justify-between border-b border-gray-100 bg-gray-50 p-5 dark:border-slate-800 dark:bg-slate-800/80"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white shadow-md shadow-primary/20 dark:bg-blue-600"
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
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h2
                                        class="text-base font-extrabold text-gray-900 dark:text-slate-100"
                                    >
                                        Jenjang Pendidikan
                                    </h2>
                                    <p
                                        class="text-xs text-gray-500 dark:text-slate-400"
                                    >
                                        Pilih jenjang pendaftar tagihan
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="isJenjangDrawerOpen = false"
                                class="cursor-pointer rounded-xl p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200"
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
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>

                        <!-- Drawer Content: List of Jenjang Cards (MTs, MA, S1, S2, S3) -->
                        <div class="flex-1 space-y-3 overflow-y-auto p-4">
                            <button
                                v-for="j in orderedJenjangs"
                                :key="j.id"
                                @click="
                                    switchJenjang(j.id);
                                    isJenjangDrawerOpen = false;
                                "
                                class="group flex w-full items-center justify-between rounded-2xl border p-4 text-left transition-all duration-200"
                                :class="[
                                    activeJenjangId === j.id
                                        ? 'border-primary bg-primary text-white shadow-lg ring-2 shadow-primary/25 ring-primary/30 dark:bg-blue-600'
                                        : 'border-gray-100 bg-white hover:border-primary/40 hover:bg-primary/5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700 dark:hover:bg-slate-800/50',
                                ]"
                            >
                                <div class="flex items-center gap-3.5">
                                    <img
                                        :src="getJenjangLogo(j.code)"
                                        :alt="j.name"
                                        class="h-9 w-9 shrink-0 object-contain"
                                    />
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-xs font-black tracking-wide"
                                                :class="
                                                    activeJenjangId === j.id
                                                        ? 'text-white'
                                                        : 'text-gray-900 dark:text-slate-100'
                                                "
                                            >
                                                {{
                                                    j.code ||
                                                    j.name
                                                        .substring(0, 3)
                                                        .toUpperCase()
                                                }}
                                            </span>
                                            <span
                                                class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full px-1 text-[10px] font-black leading-none tracking-tight shadow-xs"
                                                :class="
                                                    activeJenjangId === j.id
                                                        ? 'bg-white text-primary dark:bg-white dark:text-blue-600'
                                                        : 'bg-rose-500 text-white'
                                                "
                                            >
                                                {{
                                                    formatBadgeCount(
                                                        props.jenjangCounts?.[
                                                            j.id
                                                        ]?.belum_dibuat ?? 0,
                                                    )
                                                }}
                                            </span>
                                        </div>
                                        <span
                                            class="mt-0.5 text-xs font-bold"
                                            :class="
                                                activeJenjangId === j.id
                                                    ? 'text-white/90'
                                                    : 'text-gray-700 dark:text-slate-300'
                                            "
                                        >
                                            {{ j.name }}
                                        </span>
                                        <span
                                            class="mt-0.5 text-[11px]"
                                            :class="
                                                activeJenjangId === j.id
                                                    ? 'text-white/75'
                                                    : 'text-gray-400 dark:text-slate-400'
                                            "
                                        >
                                            {{ getJenjangScopeText(j.code) }}
                                        </span>
                                    </div>
                                </div>
                                <svg
                                    class="h-5 w-5 shrink-0 transition-transform group-hover:translate-x-1"
                                    :class="
                                        activeJenjangId === j.id
                                            ? 'text-white'
                                            : 'text-gray-400 dark:text-slate-400'
                                    "
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

                        <div
                            class="border-t border-gray-100 bg-gray-50 p-4 text-center dark:border-slate-800 dark:bg-slate-800/50"
                        >
                            <span
                                class="text-[11px] font-medium text-gray-400 dark:text-slate-500"
                            >
                                Urutan Jenjang: MTs, MA, S1, S2, S3
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Main Page Header with Responsive Floating Right Tab Trigger Button -->
        <div
            class="relative mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="pr-24 sm:pr-0">
                <h1
                    class="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl dark:text-slate-100"
                >
                    Pendaftar Tagihan
                </h1>
                <p
                    class="mt-1 max-w-2xl text-xs leading-relaxed text-gray-500 sm:text-sm dark:text-slate-400"
                >
                    Kelola data pendaftar yang telah disetujui dan dalam tahap
                    penerbitan serta pelunasan tagihan pendaftaran per jenjang
                    pendidikan.
                </p>
            </div>

            <!-- Floating Right Tab Trigger Button -->
            <button
                @click="isJenjangDrawerOpen = true"
                class="group absolute top-0 right-0 -mt-2 -mr-4 flex shrink-0 cursor-pointer items-center gap-2.5 rounded-l-2xl bg-[#1a2e4a] px-3.5 py-2.5 text-white shadow-xl shadow-slate-900/20 transition-all duration-300 hover:bg-[#15253d] hover:pr-5 focus:outline-none sm:relative sm:top-auto sm:right-auto md:-mr-6 lg:-mr-8 dark:bg-slate-800 dark:hover:bg-slate-700"
                title="Klik untuk memilih Jenjang Pendidikan"
            >
                <img
                    :src="getJenjangLogo(activeJenjang?.code)"
                    :alt="activeJenjang?.name"
                    class="h-7 w-auto shrink-0 object-contain"
                />
                <div class="flex flex-col text-left">
                    <span
                        class="text-[9px] leading-none font-black tracking-wider text-slate-300 uppercase"
                        >JENJANG</span
                    >
                    <span
                        class="mt-1 text-xs leading-none font-black text-white uppercase"
                        >{{ activeJenjang?.code || 'MTs' }}</span
                    >
                </div>
            </button>
        </div>

        <!-- Academic Year & Wave Cards (With Inactive TA Overlay Blur) -->
        <AkademikWaveFilterCards
            :active-tahun-akademik="props.activeTahunAkademik"
            :has-active-tahun-akademik="props.hasActiveTahunAkademik"
            :gelombangs="props.gelombangs"
            :selected-gelombang-id="filterForm.gelombang_id"
            @select-gelombang="onSelectGelombang"
        />

        <!-- All Jenjang Statistics Overview Grid -->
        <div
            class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5"
        >
            <div
                v-for="j in orderedJenjangs"
                :key="j.id"
                @click="switchJenjang(j.id)"
                class="group relative flex cursor-pointer flex-col justify-between rounded-2xl border p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                :class="[
                    activeJenjangId === j.id
                        ? 'border-primary bg-gradient-to-b from-primary/[0.04] to-white shadow-md ring-2 shadow-primary/10 ring-primary/20 dark:border-blue-500/60 dark:from-blue-500/10 dark:to-slate-900/80 dark:ring-blue-500/20'
                        : 'border-gray-200 bg-white shadow-xs hover:border-slate-300 dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700',
                ]"
            >
                <!-- Action Required Badge (Belum Dibuat Tagihan + Menunggu Verifikasi) -->
                <span
                    v-if="getJenjangActionCount(j.id) > 0"
                    class="absolute -top-2 -right-2 z-10 inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-black leading-none tracking-tight text-white shadow-sm ring-2 ring-white dark:ring-slate-900"
                    :title="`${props.jenjangCounts?.[j.id]?.belum_dibuat || 0} pendaftar belum ada tagihan, ${props.jenjangCounts?.[j.id]?.menunggu_verifikasi || 0} pembayaran perlu diverifikasi`"
                >
                    {{ formatBadgeCount(getJenjangActionCount(j.id)) }}
                </span>

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
                                Tagihan
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
                        @click.stop="switchJenjang(j.id)"
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

        <!-- Active Jenjang Banner Card -->
        <div
            class="mb-6 flex flex-col gap-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-6 dark:border-slate-700/80 dark:border-slate-800 dark:bg-slate-900"
        >
            <div class="flex items-center gap-4 sm:gap-5">
                <img
                    :src="getJenjangLogo(activeJenjang?.code)"
                    :alt="activeJenjang?.name"
                    class="h-12 w-auto max-w-[70px] shrink-0 object-contain sm:h-16 sm:max-w-[80px]"
                />
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="text-xs font-black tracking-widest text-primary uppercase dark:text-blue-400"
                        >
                            KODE: {{ activeJenjang?.code || 'MTS' }}
                        </span>
                        <span
                            class="rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >
                            Urutan
                            {{
                                orderedJenjangs.findIndex(
                                    (j) => j.id === activeJenjang?.id,
                                ) + 1
                            }}
                            dari {{ orderedJenjangs.length }}
                        </span>
                    </div>
                    <h2
                        class="mt-1 text-xl font-black tracking-tight text-gray-900 sm:text-2xl dark:text-slate-100"
                    >
                        {{ activeJenjang?.name }}
                    </h2>
                    <p
                        class="mt-0.5 text-xs font-medium text-gray-500 dark:text-slate-400"
                    >
                        {{ getJenjangScopeText(activeJenjang?.code) }}
                    </p>
                </div>
            </div>

            <!-- Stats summary pills -->
            <div
                class="flex flex-wrap items-center gap-2.5 border-t border-gray-100 pt-3 sm:border-t-0 sm:pt-0 dark:border-slate-800"
            >
                <div
                    class="flex items-center gap-2 rounded-xl border border-emerald-100 bg-emerald-50 px-3.5 py-2 dark:border-emerald-900/50 dark:bg-emerald-950/40"
                >
                    <span
                        class="text-xs font-extrabold text-emerald-800 dark:text-emerald-300"
                        >Total Pendaftar Tagihan:</span
                    >
                    <span
                        class="font-mono text-sm font-black text-emerald-700 dark:text-emerald-400"
                        >{{
                            formatStatCount(
                                props.jenjangCounts[activeJenjang?.id],
                            )
                        }}</span
                    >
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
                        <!-- Unified Export Trigger Button (Exports Selected if Checked, otherwise Exports All) -->
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

                        <!-- Filter Trigger Button in Toolbar -->
                        <button
                            type="button"
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
                        title="Filter Data Pendaftar Tagihan"
                        description="Saring data pendaftar berdasarkan kriteria pendaftaran & tagihan"
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
                                    Status Pembuatan Tagihan
                                </label>
                                <CustomSelect
                                    v-model="filterForm.status_pembuatan_tagihan"
                                    :options="[
                                        {
                                            value: 'dibuat',
                                            label: 'Telah Dibuat Tagihannya',
                                        },
                                        {
                                            value: 'belum',
                                            label: 'Belum Dibuat Tagihannya',
                                        },
                                    ]"
                                    placeholder="Semua Status Pembuatan"
                                />
                            </div>

                            <div>
                                <label
                                    class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    Status Tagihan / Pelunasan
                                </label>
                                <CustomSelect
                                    v-model="filterForm.status_tagihan"
                                    :options="[
                                        {
                                            value: 'BELUM_BAYAR',
                                            label: 'Belum Bayar',
                                        },
                                        {
                                            value: 'BELUM_LUNAS',
                                            label: 'Belum Lunas',
                                        },
                                        {
                                            value: 'SAMAHA',
                                            label: 'Samaha (Potongan Penuh)',
                                        },
                                        { value: 'LUNAS', label: 'Lunas' },
                                    ]"
                                    placeholder="Semua Status Pelunasan"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <label
                                    class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    Status Verifikasi Transfer
                                </label>
                                <CustomSelect
                                    v-model="filterForm.status_pembayaran"
                                    :options="[
                                        {
                                            value: 'MENUNGGU_VERIFIKASI',
                                            label: 'Menunggu Verifikasi',
                                        },
                                        { value: 'DITERIMA', label: 'Diterima' },
                                        { value: 'DITOLAK', label: 'Ditolak' },
                                    ]"
                                    placeholder="Semua Status Verifikasi"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <label
                                    class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400"
                                >
                                    Rentang Tanggal Tagihan
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
                    <Link
                        :href="createTagihanUrl"
                        class="group inline-flex cursor-pointer items-center rounded-xl bg-primary px-3.5 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-primary-dark focus:ring-2 focus:ring-primary/20 focus:outline-none dark:bg-blue-600 dark:hover:bg-blue-500"
                        :title="
                            selectedRows.length > 0
                                ? `Buat tagihan untuk ${selectedRows.length} pendaftar terpilih`
                                : 'Buat tagihan baru'
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
                        <span>Buat Tagihan</span>
                    </Link>
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

                <!-- Column: PENDAFTAR (NIK & NAMA) -->
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
                            <div class="flex items-center gap-2">
                                <Link
                                    :href="getDetailUrl(row.id)"
                                    class="text-left text-[15px] font-bold text-slate-800 transition-colors hover:text-primary dark:text-slate-100 dark:hover:text-blue-400"
                                >
                                    {{ row.nama }}
                                </Link>
                                <span
                                    v-if="row.is_interview_ulang"
                                    class="inline-flex shrink-0 items-center gap-1 rounded-md border border-indigo-200 bg-indigo-50 px-1.5 py-0.5 text-[9px] font-black text-indigo-700 dark:border-indigo-900/60 dark:bg-indigo-950/40 dark:text-indigo-300"
                                    title="Calon santri mengikuti sesi interview ulang"
                                >
                                    <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Interview Ulang
                                </span>
                            </div>
                            <p
                                class="mt-0.5 font-mono text-[13px] text-slate-500 dark:text-slate-400"
                            >
                                NIK: {{ row.nik || '-' }}
                            </p>
                        </div>
                    </div>
                </template>

                <!-- Column: GENDER -->
                <template #cell-gender="{ row }">
                    <div
                        v-if="
                            (
                                row.personal_data?.jenis_kelamin ||
                                row.jenis_kelamin ||
                                row.gender
                            )
                                ?.toLowerCase()
                                .includes('laki') ||
                            (
                                row.personal_data?.jenis_kelamin ||
                                row.jenis_kelamin ||
                                row.gender
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
                            (
                                row.personal_data?.jenis_kelamin ||
                                row.jenis_kelamin ||
                                row.gender
                            )
                                ?.toLowerCase()
                                .includes('perempuan') ||
                            (
                                row.personal_data?.jenis_kelamin ||
                                row.jenis_kelamin ||
                                row.gender
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
                                class="text-[13px] font-bold text-slate-800 dark:text-slate-200"
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

                <!-- Column: STATUS PEMBUATAN TAGIHAN -->
                <template #cell-status_pembuatan_tagihan="{ row }">
                    <span
                        v-if="getApplicantTagihan(row)"
                        class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/50 dark:text-emerald-300"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                        ></span>
                        Telah Dibuat
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-500 dark:border-slate-800 dark:bg-slate-800/80 dark:text-slate-400"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full bg-slate-400"
                        ></span>
                        Belum Dibuat
                    </span>
                </template>

                <!-- Column: STATUS TAGIHAN (LUNAS / BELUM / SAMAHA) -->
                <template #cell-status_tagihan="{ row }">
                    <template v-if="getApplicantTagihan(row)">
                        <span
                            v-if="getApplicantTagihan(row).status === 'LUNAS'"
                            class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/50 dark:text-emerald-300"
                        >
                            LUNAS
                        </span>
                        <span
                            v-else-if="
                                getApplicantTagihan(row).status === 'SAMAHA'
                            "
                            class="inline-flex items-center rounded-full border border-purple-200 bg-purple-50 px-2.5 py-1 text-xs font-bold text-purple-700 dark:border-purple-900/50 dark:bg-purple-950/50 dark:text-purple-300"
                        >
                            SAMAHA
                        </span>
                        <span
                            v-else-if="
                                getApplicantTagihan(row).status ===
                                'BELUM_LUNAS'
                            "
                            class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-300"
                        >
                            BELUM LUNAS
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-300"
                        >
                            BELUM BAYAR
                        </span>
                    </template>
                    <span
                        v-else
                        class="text-[13px] text-gray-400 dark:text-slate-500"
                        >-</span
                    >
                </template>

                <!-- Column: JUMLAH & SISA TAGIHAN -->
                <template #cell-jumlah_tagihan="{ row }">
                    <template v-if="getApplicantTagihan(row)">
                        <div class="flex flex-col text-[13px]">
                            <span
                                class="font-bold text-slate-800 dark:text-slate-200"
                            >
                                {{
                                    formatRupiah(
                                        getApplicantTagihan(row).total_amount,
                                    )
                                }}
                            </span>
                            <span
                                class="text-[12px] text-slate-500 dark:text-slate-400"
                            >
                                Sisa:
                                <strong
                                    class="font-mono font-bold text-rose-600 dark:text-rose-400"
                                    >{{
                                        formatRupiah(
                                            Math.max(
                                                0,
                                                parseFloat(
                                                    getApplicantTagihan(row)
                                                        .total_amount,
                                                ) -
                                                    getApplicantTotalPaid(
                                                        getApplicantTagihan(
                                                            row,
                                                        ),
                                                    ),
                                            ),
                                        )
                                    }}</strong
                                >
                            </span>
                        </div>
                    </template>
                    <span
                        v-else
                        class="text-[13px] text-gray-400 dark:text-slate-500"
                        >-</span
                    >
                </template>

                <!-- Column: STATUS PEMBAYARAN & VERIFIKASI -->
                <template #cell-status_pembayaran="{ row }">
                    <template v-if="getApplicantTagihan(row)">
                        <span
                            v-if="
                                getApplicantPendingPayment(
                                    getApplicantTagihan(row),
                                )
                            "
                            class="inline-flex animate-pulse items-center gap-1.5 rounded-full border border-amber-300 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-300"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-amber-500"
                            ></span>
                            Menunggu Verifikasi
                        </span>
                        <span
                            v-else-if="
                                getApplicantTagihan(row).status === 'LUNAS'
                            "
                            class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/50 dark:text-emerald-300"
                        >
                            Diterima
                        </span>
                        <span
                            v-else-if="
                                getApplicantTagihan(row).pembayarans?.some(
                                    (p: any) => p.status === 'DITOLAK',
                                )
                            "
                            class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-300"
                        >
                            Ditolak
                        </span>
                        <span
                            v-else
                            class="text-[13px] text-gray-400 dark:text-slate-500"
                            >-</span
                        >
                    </template>
                    <span
                        v-else
                        class="text-[13px] text-gray-400 dark:text-slate-500"
                        >-</span
                    >
                </template>

                <!-- Column: STATUS -->
                <template #cell-status="{ row }">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-300"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full bg-amber-500"
                        ></span>
                        {{ row.status || 'TAGIHAN' }}
                    </span>
                </template>

                <!-- Column: AKSI (1 WORD & CLEAR ACTIONS) -->
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
                                <!-- Action 1: VERIFIKASI TRANSFER -->
                                <button
                                    v-if="
                                        getApplicantPendingPayment(
                                            getApplicantTagihan(row),
                                        )
                                    "
                                    @click="
                                        openVerifyPaymentModal(
                                            getApplicantPendingPayment(
                                                getApplicantTagihan(row),
                                            ),
                                            row,
                                        )
                                    "
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

                                <!-- Action 2: BUAT TAGIHAN (PAGE LINK) -->
                                <Link
                                    v-if="!getApplicantTagihan(row)"
                                    :href="
                                        create.url({
                                            query: {
                                                ids: row.id,
                                                jenjang_id:
                                                    row.jenjang_id ||
                                                    activeJenjangId,
                                            },
                                        })
                                    "
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold transition-colors sm:px-4"
                                    :class="
                                        row.is_interview_ulang
                                            ? 'bg-indigo-50/50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-900/50'
                                            : 'bg-primary/5 text-primary hover:bg-primary/10 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20'
                                    "
                                >
                                    <svg
                                        class="mr-3 h-4 w-4"
                                        :class="row.is_interview_ulang ? 'text-indigo-600 dark:text-indigo-400' : 'text-primary dark:text-blue-400'"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                        />
                                    </svg>
                                    {{ row.is_interview_ulang ? 'Tagihan Interview' : 'Buat Tagihan' }}
                                </Link>

                                <!-- Action 3: DETAIL TAGIHAN (PAGE LINK) -->
                                <Link
                                    v-if="getApplicantTagihan(row)"
                                    :href="getTagihanDetailUrl(row.id)"
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                >
                                    <svg
                                        class="mr-3 h-4 w-4 text-indigo-500 dark:text-indigo-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                                        />
                                    </svg>
                                    Detail Tagihan
                                </Link>

                                <!-- Action 4: TAMBAH BAYAR -->
                                <button
                                    v-if="
                                        getApplicantTagihan(row) &&
                                        getApplicantTagihan(row).status !==
                                            'LUNAS' &&
                                        getApplicantTagihan(row).status !==
                                            'SAMAHA'
                                    "
                                    @click="
                                        openAddPaymentModal(
                                            getApplicantTagihan(row),
                                            row,
                                        )
                                    "
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
                                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                                        />
                                    </svg>
                                    Tambah Bayar
                                </button>

                                <!-- Action 5: HAPUS TAGIHAN -->
                                <button
                                    v-if="
                                        getApplicantTagihan(row) &&
                                        getApplicantTotalPaid(
                                            getApplicantTagihan(row),
                                        ) === 0
                                    "
                                    @click="
                                        openDeleteTagihanModal(
                                            getApplicantTagihan(row),
                                            row,
                                        )
                                    "
                                    class="flex w-full items-center px-3 py-2.5 text-left text-sm font-bold text-amber-700 transition-colors hover:bg-amber-50 sm:px-4 dark:text-amber-400 dark:hover:bg-amber-950/40"
                                >
                                    <svg
                                        class="mr-3 h-4 w-4 text-amber-600 dark:text-amber-400"
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
                                    Hapus Tagihan
                                </button>

                                <div
                                    class="my-1 border-t border-gray-100 dark:border-slate-800"
                                ></div>

                                <!-- Action 6: DETAIL SANTRI (PROFILE) -->
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

                                <!-- Action 7: CETAK KARTU -->
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
                                    Cetak
                                </button>

                                <!-- Action 8: SANDI (RESET PASSWORD) -->
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
                                    Sandi
                                </button>

                                <div
                                    class="my-1 border-t border-gray-100 dark:border-slate-800"
                                ></div>

                                <!-- Action 9: HAPUS PENDAFTAR -->
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
        <!-- MODAL 2: TAMBAH PEMBAYARAN MANUAL (TUNAI / SAMAHA) -->
        <!-- ======================================================= -->
        <Modal
            :show="isAddPaymentModalOpen"
            @close="closeAddPaymentModal"
            maxWidth="md"
            title="Tambah Pembayaran Manual"
            description="Catat pembayaran tunai atau potongan Samaha langsung."
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
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                        />
                    </svg>
                </div>
            </template>

            <!-- Target Info Box -->
            <div
                v-if="addPaymentTargetPendaftar || addPaymentTargetTagihan"
                class="mb-4 rounded-2xl border border-gray-100 bg-gray-50 p-3.5 text-xs text-gray-700 sm:p-4 sm:text-sm dark:border-slate-800 dark:bg-slate-800/80 dark:text-slate-300"
            >
                <div
                    class="text-sm font-bold text-gray-900 sm:text-base dark:text-slate-100"
                >
                    {{
                        addPaymentTargetPendaftar?.nama ||
                        addPaymentTargetTagihan?.pendaftar?.nama ||
                        '-'
                    }}
                </div>
                <div
                    class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-600 dark:text-slate-400"
                >
                    <span
                        >No. Reg:
                        <strong
                            class="font-mono text-gray-900 dark:text-slate-100"
                            >{{
                                addPaymentTargetPendaftar?.nomor_pendaftaran ||
                                addPaymentTargetTagihan?.pendaftar
                                    ?.nomor_pendaftaran ||
                                '-'
                            }}</strong
                        ></span
                    >
                    <span
                        >Sisa Tagihan:
                        <strong
                            class="font-mono font-bold text-rose-600 dark:text-rose-400"
                            >{{
                                formatRupiah(
                                    Math.max(
                                        0,
                                        parseFloat(
                                            addPaymentTargetTagihan?.total_amount ||
                                                0,
                                        ) -
                                            getApplicantTotalPaid(
                                                addPaymentTargetTagihan,
                                            ),
                                    ),
                                )
                            }}</strong
                        ></span
                    >
                </div>
            </div>

            <form @submit.prevent="submitAddPayment" class="space-y-4">
                <CustomSelect
                    label="Metode Pembayaran"
                    v-model="addPaymentForm.payment_method"
                    :options="[
                        {
                            value: 'TUNAI',
                            label: 'Tunai (Langsung Diterima)',
                        },
                        {
                            value: 'SAMAHA',
                            label: 'Potongan Samaha / Beasiswa',
                        },
                        { value: 'TRANSFER', label: 'Transfer Bank' },
                    ]"
                />

                <div v-if="addPaymentForm.payment_method === 'TRANSFER'">
                    <CustomSelect
                        label="Bank Tujuan"
                        v-model="addPaymentForm.bank_id"
                        :options="
                            props.banks.map((b) => ({
                                value: b.id,
                                label: `${b.name} (${b.kode_bank || '-'})`,
                            }))
                        "
                    />
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-bold text-gray-700 dark:text-slate-300"
                    >
                        Nominal Pembayaran (Rp)
                        <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="number"
                        v-model.number="addPaymentForm.amount"
                        min="1"
                        step="10000"
                        class="w-full rounded-2xl border border-gray-200 bg-white p-2.5 text-xs text-gray-900 shadow-xs focus:border-primary focus:ring-primary focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        required
                    />
                    <p
                        v-if="addPaymentForm.errors.amount"
                        class="mt-1 text-[11px] text-rose-600 dark:text-rose-400"
                    >
                        {{ addPaymentForm.errors.amount }}
                    </p>
                </div>

                <CustomDatePicker
                    label="Tanggal Bayar"
                    v-model="addPaymentForm.payment_date"
                />

                <TextInput
                    label="Keterangan / Catatan"
                    v-model="addPaymentForm.catatan"
                    placeholder="Contoh: Pembayaran tunai via Panitia"
                />
            </form>

            <template #footer>
                <div
                    class="flex w-full flex-col-reverse justify-end gap-2 sm:flex-row"
                >
                    <SecondaryButton
                        @click="closeAddPaymentModal"
                        type="button"
                        class="w-full justify-center sm:w-auto"
                    >
                        Batal
                    </SecondaryButton>
                    <PrimaryButton
                        @click="submitAddPayment"
                        type="button"
                        :disabled="addPaymentForm.processing"
                        :loading="addPaymentForm.processing"
                        class="w-full justify-center sm:w-auto"
                    >
                        Simpan Pembayaran
                    </PrimaryButton>
                </div>
            </template>
        </Modal>

        <!-- ======================================================= -->
        <!-- MODAL 3: VERIFIKASI PEMBAYARAN -->
        <!-- ======================================================= -->
        <Modal
            :show="isVerifyPaymentModalOpen"
            @close="closeVerifyPaymentModal"
            maxWidth="xl"
            title="Verifikasi Pembayaran"
            description="Validasi rincian transaksi dan bukti transfer calon santri."
        >
            <template #icon>
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 sm:h-11 sm:w-11 dark:bg-emerald-950/50 dark:text-emerald-400"
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
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>
            </template>

            <div v-if="verifyPaymentTarget" class="space-y-3.5">
                <!-- Card 1 (TOP): Invoice Pembayaran -->
                <div
                    class="rounded-2xl border border-primary/20 bg-primary/5 p-4 sm:p-5 dark:border-blue-900/40 dark:bg-slate-800/60"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span
                            class="text-[11px] font-bold tracking-wider text-primary uppercase dark:text-blue-400"
                            >Invoice Pembayaran</span
                        >
                        <span
                            v-if="
                                verifyPaymentTargetRow?.nama ||
                                verifyPaymentTarget.pendaftar?.nama
                            "
                            class="max-w-[220px] truncate text-xs font-bold text-slate-700 dark:text-slate-300"
                        >
                            {{
                                verifyPaymentTargetRow?.nama ||
                                verifyPaymentTarget.pendaftar?.nama
                            }}
                        </span>
                    </div>
                    <div
                        class="mt-1 font-mono text-xl font-black break-all text-primary sm:text-2xl dark:text-blue-400"
                    >
                        {{
                            getApplicantTagihan(verifyPaymentTargetRow)
                                ?.nomor_invoice ||
                            verifyPaymentTarget.tagihan?.nomor_invoice ||
                            '-'
                        }}
                    </div>
                </div>

                <!-- Row 2: Jumlah & Tanggal (2 Columns Grid) -->
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-3.5">
                    <!-- Jumlah -->
                    <div
                        class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3.5 sm:p-4 dark:border-slate-700/80 dark:bg-slate-800/60"
                    >
                        <span
                            class="block text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >Jumlah</span
                        >
                        <div
                            class="mt-1 font-mono text-lg font-black text-slate-900 sm:text-xl dark:text-slate-100"
                        >
                            {{ formatRupiah(verifyPaymentTarget.amount) }}
                        </div>
                    </div>

                    <!-- Tanggal -->
                    <div
                        class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3.5 sm:p-4 dark:border-slate-700/80 dark:bg-slate-800/60"
                    >
                        <span
                            class="block text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >Tanggal</span
                        >
                        <div
                            class="mt-1 text-sm font-bold text-slate-900 sm:text-base dark:text-slate-100"
                        >
                            {{
                                formatDate(
                                    verifyPaymentTarget.payment_date ||
                                        verifyPaymentTarget.created_at,
                                )
                            }}
                        </div>
                    </div>
                </div>

                <!-- Card 3: Informasi Virtual Account -->
                <div
                    v-if="
                        verifyPaymentTarget.payment_method === 'TRANSFER' ||
                        verifyPaymentTarget.payment_method === 'VA' ||
                        verifyPaymentTarget.bank
                    "
                    class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4 sm:p-5 dark:border-slate-700/80 dark:bg-slate-800/50"
                >
                    <!-- Header with Bank SVG Icon -->
                    <div
                        class="flex items-center gap-2 text-sm font-bold text-primary dark:text-blue-400"
                    >
                        <svg
                            class="h-4.5 w-4.5 shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                            />
                        </svg>
                        <span>Informasi Virtual Account</span>
                    </div>

                    <!-- Bank Row (Tanpa Background pada Logo Bank) -->
                    <div class="mt-3 flex items-center gap-3.5">
                        <img
                            :src="getBankLogo(verifyPaymentTarget.bank)"
                            @error="handleBankLogoError($event)"
                            :alt="verifyPaymentTarget.bank?.name || 'Logo Bank'"
                            class="h-8 max-w-[110px] shrink-0 object-contain drop-shadow-xs"
                        />
                        <div class="min-w-0">
                            <span
                                class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase dark:text-slate-500"
                                >Bank</span
                            >
                            <div
                                class="truncate text-sm font-bold text-slate-800 dark:text-slate-100"
                            >
                                {{
                                    verifyPaymentTarget.bank?.name ||
                                    verifyPaymentTargetRow
                                        ?.virtual_accounts?.[0]?.bank?.name ||
                                    'Bank Syariah Indonesia'
                                }}
                            </div>
                        </div>
                    </div>

                    <div
                        class="my-3 border-t border-slate-200/60 dark:border-slate-700/60"
                    ></div>

                    <!-- Nomor Virtual Account -->
                    <div>
                        <span
                            class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase dark:text-slate-500"
                            >Nomor Virtual Account</span
                        >
                        <div
                            class="mt-0.5 font-mono text-base font-black tracking-wider text-slate-900 select-all sm:text-lg dark:text-slate-100"
                        >
                            {{
                                verifyPaymentTarget.nomor_va ||
                                verifyPaymentTargetRow?.virtual_accounts?.find(
                                    (v: any) =>
                                        v.bank_id ===
                                        verifyPaymentTarget.bank_id,
                                )?.nomor_va ||
                                verifyPaymentTargetRow?.virtual_accounts?.[0]
                                    ?.nomor_va ||
                                '-'
                            }}
                        </div>
                    </div>
                </div>

                <!-- Row 4: Jenis Bayar & Status (2 Columns Grid) -->
                <div class="grid grid-cols-2 gap-3 pt-0.5 sm:gap-3.5">
                    <!-- Jenis Bayar -->
                    <div>
                        <span
                            class="mb-1.5 block text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >Jenis Bayar</span
                        >
                        <div>
                            <span
                                v-if="
                                    verifyPaymentTarget.payment_method ===
                                        'TRANSFER' ||
                                    verifyPaymentTarget.payment_method ===
                                        'VA' ||
                                    verifyPaymentTarget.bank
                                "
                                class="inline-flex items-center gap-1.5 rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-xs font-bold text-primary dark:border-blue-900/40 dark:bg-blue-950/40 dark:text-blue-300"
                            >
                                <svg
                                    class="h-4 w-4 shrink-0 text-primary dark:text-blue-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                                    />
                                </svg>
                                Virtual Account
                            </span>
                            <span
                                v-else-if="
                                    verifyPaymentTarget.payment_method ===
                                    'SAMAHA'
                                "
                                class="inline-flex items-center gap-1.5 rounded-full border border-purple-200/80 bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700 dark:border-purple-800/50 dark:bg-purple-950/40 dark:text-purple-300"
                            >
                                <svg
                                    class="h-4 w-4 shrink-0 text-purple-600 dark:text-purple-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 14l9-5-9-5-9 5 9 5z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"
                                    />
                                </svg>
                                Samaha
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 rounded-full border border-sky-200/80 bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 dark:border-sky-800/50 dark:bg-sky-950/40 dark:text-sky-300"
                            >
                                <svg
                                    class="h-4 w-4 shrink-0 text-sky-600 dark:text-sky-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                                    />
                                </svg>
                                Tunai (Cash)
                            </span>
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <span
                            class="mb-1.5 block text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >Status</span
                        >
                        <div>
                            <span
                                v-if="
                                    verifyPaymentTarget.status ===
                                    'MENUNGGU_VERIFIKASI'
                                "
                                class="inline-flex items-center gap-1.5 rounded-full border border-amber-200/80 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 dark:border-amber-800/50 dark:bg-amber-950/40 dark:text-amber-300"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-amber-500"
                                ></span>
                                Menunggu Verifikasi
                            </span>
                            <span
                                v-else-if="
                                    verifyPaymentTarget.status === 'DITERIMA'
                                "
                                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/80 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-300"
                            >
                                <svg
                                    class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                Diterima
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 rounded-full border border-rose-200/80 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 dark:border-rose-800/50 dark:bg-rose-950/40 dark:text-rose-300"
                            >
                                <svg
                                    class="h-3.5 w-3.5 text-rose-600 dark:text-rose-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                                Ditolak
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Card: Bukti Transfer Preview -->
                <div
                    v-if="getProofUrl(verifyPaymentTarget)"
                    class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="mb-2.5 flex items-center justify-between border-b border-gray-100 pb-2.5 dark:border-slate-800"
                    >
                        <span
                            class="flex items-center gap-1.5 text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                        >
                            <svg
                                class="h-4 w-4 text-primary dark:text-blue-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                            Bukti Transfer
                        </span>
                        <a
                            :href="getProofUrl(verifyPaymentTarget)!"
                            target="_blank"
                            download
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            <svg
                                class="h-3.5 w-3.5"
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
                            <span>Download</span>
                        </a>
                    </div>

                    <div
                        @click="
                            openProofModal(getProofUrl(verifyPaymentTarget)!)
                        "
                        class="group relative flex max-h-56 cursor-pointer items-center justify-center overflow-hidden rounded-xl border border-gray-100 bg-gray-50 p-2 transition-all hover:border-primary/40 hover:shadow-xs dark:border-slate-800 dark:bg-slate-950/40"
                    >
                        <img
                            :src="getProofUrl(verifyPaymentTarget)!"
                            alt="Bukti Transfer"
                            class="max-h-52 w-full rounded-lg object-contain transition-transform duration-200 group-hover:scale-[1.01]"
                        />
                        <div
                            class="backdrop-blur-2xs absolute inset-0 flex items-center justify-center gap-1.5 rounded-lg bg-black/40 text-xs font-bold text-white opacity-0 transition-opacity group-hover:opacity-100"
                        >
                            <svg
                                class="h-4.5 w-4.5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"
                                />
                            </svg>
                            <span>Klik untuk Perbesar</span>
                        </div>
                    </div>
                </div>

                <!-- Section Keputusan Verifikasi Form -->
                <div
                    class="space-y-3.5 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900"
                >
                    <label
                        class="block text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                    >
                        Keputusan Verifikasi
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            @click="verifyPaymentForm.action = 'terima'"
                            class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border p-3 text-center transition-all"
                            :class="[
                                verifyPaymentForm.action === 'terima'
                                    ? 'border-emerald-500 bg-emerald-50 font-bold text-emerald-800 shadow-xs ring-2 ring-emerald-500/20 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-500/30'
                                    : 'border-gray-200 bg-white font-semibold text-gray-600 hover:border-gray-300 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-slate-700',
                            ]"
                        >
                            <svg
                                class="h-4.5 w-4.5 shrink-0 text-emerald-600 dark:text-emerald-400"
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
                            <span class="text-xs sm:text-sm">TERIMA</span>
                        </button>

                        <button
                            type="button"
                            @click="verifyPaymentForm.action = 'tolak'"
                            class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border p-3 text-center transition-all"
                            :class="[
                                verifyPaymentForm.action === 'tolak'
                                    ? 'border-rose-500 bg-rose-50 font-bold text-rose-800 shadow-xs ring-2 ring-rose-500/20 dark:bg-rose-950/60 dark:text-rose-300 dark:ring-rose-500/30'
                                    : 'border-gray-200 bg-white font-semibold text-gray-600 hover:border-gray-300 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-slate-700',
                            ]"
                        >
                            <svg
                                class="h-4.5 w-4.5 shrink-0 text-rose-600 dark:text-rose-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            <span class="text-xs sm:text-sm">TOLAK</span>
                        </button>
                    </div>

                    <!-- Mode Terima: CurrencyInput + Helper Note + Textarea Catatan -->
                    <div
                        v-if="verifyPaymentForm.action === 'terima'"
                        class="space-y-3 pt-1"
                    >
                        <div>
                            <CurrencyInput
                                label="Jumlah / Nominal Pembayaran Diterima"
                                v-model="verifyPaymentForm.amount_verified"
                                :error="
                                    verifyPaymentForm.errors.amount_verified
                                "
                                required
                                placeholder="Masukkan nominal pembayaran"
                            />
                            <p
                                class="mt-1 text-[11px] text-slate-400 dark:text-slate-500"
                            >
                                Nilai transfer awal:
                                <strong
                                    class="font-mono text-slate-600 dark:text-slate-300"
                                    >{{
                                        formatRupiah(verifyPaymentTarget.amount)
                                    }}</strong
                                >. Dapat disesuaikan jika ada koreksi nominal.
                            </p>
                        </div>

                        <div>
                            <TextareaInput
                                label="Catatan / Keterangan Pembayaran (Opsional)"
                                v-model="verifyPaymentForm.catatan"
                                :error="verifyPaymentForm.errors.catatan"
                                rows="2"
                                placeholder="Tuliskan catatan verifikasi pembayaran..."
                                class="w-full text-xs"
                            />
                        </div>
                    </div>

                    <!-- Mode Tolak: Textarea Alasan Penolakan -->
                    <div v-else class="space-y-1.5 pt-1">
                        <TextareaInput
                            label="Alasan Penolakan Pembayaran"
                            v-model="verifyPaymentForm.alasan_penolakan"
                            :error="verifyPaymentForm.errors.alasan_penolakan"
                            rows="3"
                            required
                            placeholder="Tuliskan alasan penolakan bukti pembayaran..."
                            class="w-full text-xs"
                        />
                        <p
                            class="text-[11px] text-slate-400 dark:text-slate-500"
                        >
                            Alasan penolakan akan dapat dilihat oleh pendaftar
                            pada dashboard pendaftaran mereka.
                        </p>
                    </div>
                </div>
            </div>

            <template #footer>
                <div
                    class="flex w-full flex-col-reverse justify-end gap-2.5 sm:flex-row"
                >
                    <SecondaryButton
                        @click="closeVerifyPaymentModal"
                        type="button"
                        class="w-full justify-center text-xs font-bold sm:w-auto sm:text-sm"
                    >
                        Batal
                    </SecondaryButton>
                    <button
                        type="button"
                        @click="submitVerifyPayment"
                        :disabled="verifyPaymentForm.processing"
                        class="inline-flex w-full cursor-pointer items-center justify-center rounded-xl px-5 py-2.5 text-xs font-bold text-white shadow-md transition-all sm:w-auto sm:text-sm"
                        :class="[
                            verifyPaymentForm.action === 'terima'
                                ? 'bg-emerald-600 shadow-emerald-600/20 hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500'
                                : 'bg-rose-600 shadow-rose-600/20 hover:bg-rose-700 dark:bg-rose-600 dark:hover:bg-rose-500',
                        ]"
                    >
                        {{
                            verifyPaymentForm.processing
                                ? 'Memproses...'
                                : verifyPaymentForm.action === 'terima'
                                  ? 'Terima Pembayaran'
                                  : 'Tolak Pembayaran'
                        }}
                    </button>
                </div>
            </template>
        </Modal>

        <!-- ======================================================= -->
        <!-- MODAL 4: ZOOM BUKTI TRANSFER -->
        <!-- ======================================================= -->
        <Modal
            :show="isProofModalOpen"
            @close="isProofModalOpen = false"
            maxWidth="lg"
            title="Bukti Transfer Pembayaran"
            description="Pratinjau berkas bukti transfer yang diunggah oleh calon santri."
        >
            <template #icon>
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 sm:h-12 sm:w-12 dark:bg-blue-950/50 dark:text-blue-400"
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
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                </div>
            </template>

            <div
                class="flex justify-center rounded-2xl border border-gray-100 bg-gray-50 p-2 dark:border-slate-800 dark:bg-slate-900"
            >
                <img
                    :src="proofModalUrl"
                    alt="Bukti Transfer Full"
                    class="max-h-[75vh] max-w-full rounded-xl object-contain shadow-xs"
                />
            </div>

            <template #footer>
                <div class="flex w-full justify-end">
                    <SecondaryButton
                        @click="isProofModalOpen = false"
                        type="button"
                        class="w-full justify-center sm:w-auto"
                    >
                        Tutup
                    </SecondaryButton>
                </div>
            </template>
        </Modal>

        <!-- ======================================================= -->
        <!-- MODAL 5: HAPUS TAGIHAN (SINGLE) -->
        <!-- ======================================================= -->
        <Modal
            :show="isDeleteTagihanModalOpen"
            @close="closeDeleteTagihanModal"
            maxWidth="sm"
            title="Hapus Invoice Tagihan"
            description="Konfirmasi pembatalan dan penghapusan invoice tagihan."
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
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                    </svg>
                </div>
            </template>

            <div class="text-center text-sm text-gray-600 dark:text-slate-300">
                Apakah Anda yakin ingin menghapus invoice tagihan
                <strong class="font-bold text-gray-900 dark:text-slate-100">{{
                    deleteTagihanTarget?.nomor_invoice ||
                    deleteTagihanTarget?.nama_tagihan ||
                    'ini'
                }}</strong>
                <span v-if="deleteTagihanTargetPendaftar">
                    untuk calon santri
                    <strong
                        class="font-bold text-gray-900 dark:text-slate-100"
                        >{{ deleteTagihanTargetPendaftar.nama }}</strong
                    > </span
                >?
                <p class="mt-2 text-xs text-gray-400 dark:text-slate-500">
                    Tagihan yang belum memiliki riwayat pembayaran dapat dibuat
                    ulang kembali nantinya.
                </p>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <SecondaryButton
                        @click="closeDeleteTagihanModal"
                        type="button"
                    >
                        Batal
                    </SecondaryButton>
                    <DangerButton
                        @click="submitDeleteTagihan"
                        type="button"
                        :disabled="deleteTagihanForm.processing"
                        :loading="deleteTagihanForm.processing"
                    >
                        Hapus Tagihan
                    </DangerButton>
                </div>
            </template>
        </Modal>

        <!-- ======================================================= -->
        <!-- MODAL 6: RESET PASSWORD -->
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
                            >{{ resetPasswordTarget.nik }}</strong
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
        <!-- MODAL 7: HAPUS PENDAFTAR (SINGLE) -->
        <!-- ======================================================= -->
        <Modal
            :show="isDeleteModalOpen"
            @close="closeDeleteModal"
            maxWidth="sm"
            title="Hapus Pendaftar Tagihan"
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
                Apakah Anda yakin ingin menghapus data pendaftar tagihan
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
        <!-- MODAL 8: HAPUS PENDAFTAR MASSAL (BULK DELETE) -->
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
                    >{{ selectedRows.length }} data pendaftar tagihan</strong
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
