<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import BackButton from '@/Components/BackButton.vue';
import PasswordInput from '@/Components/Form/PasswordInput.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { getBankLogo } from '@/lib/bank';
import { reset_password as resetPasswordRoute } from '@/routes/admin/pendaftar';
import { index as draftIndex } from '@/routes/admin/pendaftar/draft';
import { verify as verifyRoute } from '@/routes/admin/pendaftar/submit';
import { getPendaftarStatusBadge } from '@/types/enums';

defineOptions({ layout: AdminLayout });

const props = defineProps<{
    pendaftar: any;
    masterDokumens?: any[];
}>();

// Tab state with URL query parameter sync so refresh keeps active tab
const getInitialTab = ():
    'personal' | 'parent' | 'education' | 'document' | 'va' => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab');

        if (
            tab &&
            ['personal', 'parent', 'education', 'document', 'va'].includes(tab)
        ) {
            return tab as any;
        }
    }

    return 'personal';
};

const activeTab = ref<'personal' | 'parent' | 'education' | 'document' | 'va'>(
    getInitialTab(),
);

watch(activeTab, (newTab: string) => {
    if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', newTab);
        window.history.replaceState({}, '', url);
    }
});

const tabs = [
    { key: 'personal' as const, step: 1, name: 'Data Pribadi & Alamat' },
    { key: 'parent' as const, step: 2, name: 'Orang Tua & Wali' },
    { key: 'education' as const, step: 3, name: 'Jenjang & Pendidikan' },
    { key: 'document' as const, step: 4, name: 'Dokumen Lampiran' },
    { key: 'va' as const, step: 5, name: 'Virtual Account Bank' },
];

const jenjangCode = computed(() => {
    return (
        props.pendaftar.jenjang?.code ||
        props.pendaftar.education_data?.jenjang ||
        ''
    ).toUpperCase();
});

const tipePendaftaran = computed(() => {
    return (
        props.pendaftar.tipe_pendaftaran ||
        props.pendaftar.education_data?.tipe_pendaftaran ||
        'Reguler'
    );
});

const isPindahan = computed(() => {
    return tipePendaftaran.value.toLowerCase().includes('pindahan');
});

const isHigherEducation = computed(() => {
    return ['S1', 'S2', 'S3'].includes(jenjangCode.value);
});

const backUrl = computed(() => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        const from = params.get('from');

        if (from) {
            try {
                return decodeURIComponent(from);
            } catch {
                return from;
            }
        }
    }

    const status = (props.pendaftar.status || '').toUpperCase();
    const jenjangId = props.pendaftar.jenjang_id;
    const query = jenjangId ? `?jenjang_id=${jenjangId}` : '';

    if (status === 'SUBMITTED' || status === 'SUBMIT') {
        return `/admin/pendaftar/submit${query}`;
    }

    if (status === 'TAGIHAN') {
        return `/admin/pendaftar/tagihan${query}`;
    }

    if (status === 'INTERVIEW' || status === 'VERIFIED') {
        return `/admin/pendaftar/set-interview${query}`;
    }

    if (status === 'PENILAIAN') {
        return `/admin/pendaftar/penilaian-interview${query}`;
    }

    if (['LULUS', 'TIDAK_LULUS', 'PENGUMUMAN'].includes(status)) {
        return `/admin/pendaftar/pengumuman${query}`;
    }

    return `/admin/pendaftar/draft${query}`;
});

const formatDate = (dateString?: string) => {
    if (!dateString) {
return '-';
}

    const date = new Date(dateString);

    if (isNaN(date.getTime())) {
return dateString;
}

    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatDateTime = (dateString?: string) => {
    if (!dateString) {
return '-';
}

    const date = new Date(dateString);

    if (isNaN(date.getTime())) {
return dateString;
}

    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getJenjangLogo = (jenjangOrCode?: any) => {
    if (!jenjangOrCode) {
        jenjangOrCode = props.pendaftar.jenjang || jenjangCode.value;
    }

    if (typeof jenjangOrCode === 'object' && jenjangOrCode?.logo_path) {
        return jenjangOrCode.logo_path.startsWith('/')
            ? jenjangOrCode.logo_path
            : `/${jenjangOrCode.logo_path}`;
    }

    const code = (
        typeof jenjangOrCode === 'string'
            ? jenjangOrCode
            : jenjangOrCode?.code || jenjangOrCode?.singkatan || ''
    ).toUpperCase();

    if (code === 'MTS') {
        return '/image/logos/jenjang/logo-mts.png';
    }

    if (code === 'MA') {
        return '/image/logos/jenjang/logo-ma.png';
    }

    if (code === 'S1') {
        return '/image/logos/jenjang/logo-s1.png';
    }

    if (code === 'S2') {
        return '/image/logos/jenjang/logo-s2.png';
    }

    if (code === 'S3') {
        return '/image/logos/jenjang/logo-s3.png';
    }

    return '/image/logos/jenjang/logo-uii dalwa.png';
};

// Virtual Account helpers & active filtering


const activeVirtualAccounts = computed(() => {
    const vas =
        props.pendaftar?.virtualAccounts ||
        props.pendaftar?.virtual_accounts ||
        [];

    return vas.filter((va: any) => {
        if (!va) {
return false;
}

        // Filter out explicitly inactive VA record
        if (
            va.is_active === false ||
            va.is_active === 0 ||
            va.status === 'INACTIVE' ||
            va.status === 'TIDAK_AKTIF'
        ) {
            return false;
        }

        // Filter out if Bank is inactive (matching /admin/keuangan/va)
        if (va.bank) {
            if (
                va.bank.is_active === false ||
                va.bank.is_active === 0 ||
                va.bank.status === 'INACTIVE' ||
                va.bank.status === 'TIDAK_AKTIF'
            ) {
                return false;
            }
        }

        return true;
    });
});

const copiedVaId = ref<string | null>(null);
const copyToClipboard = (text: string, id: string) => {
    if (navigator.clipboard && text) {
        navigator.clipboard.writeText(text);
        copiedVaId.value = id;
        setTimeout(() => {
            if (copiedVaId.value === id) {
                copiedVaId.value = null;
            }
        }, 2000);
    }
};

// Document Preview Modal State & Helpers
const isImageFile = (pathOrUrl?: string | null) => {
    if (!pathOrUrl) {
        return false;
    }

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
    if (!pathOrUrl) {
        return false;
    }

    const clean = pathOrUrl.split('?')[0].toLowerCase();

    return clean.endsWith('.pdf');
};

const getDocFileName = (dok: any) => {
    if (!dok) {
        return '';
    }

    if (dok.file_name) {
        return dok.file_name;
    }

    if (dok.file_path) {
        const parts = dok.file_path.split('/');
        return parts[parts.length - 1];
    }

    return dok.dokumen?.name || dok.dokumen?.nama || 'Berkas Lampiran';
};

const getDocumentFileUrl = (dok: any) => {
    if (!dok) {
        return '';
    }

    if (dok.file_url) {
        return dok.file_url;
    }

    if (dok.file_path) {
        const path = dok.file_path.trim();

        if (path.startsWith('http://') || path.startsWith('https://')) {
            return path;
        }

        if (path.startsWith('/storage/') || path.startsWith('/image/')) {
            return path;
        }

        return `/storage/${path}`;
    }

    return '';
};


// Reset Password Modal
const isResetPasswordModalOpen = ref(false);
const resetPasswordForm = useForm({
    password: '',
    password_confirmation: '',
});

const openResetPasswordModal = () => {
    resetPasswordForm.reset();
    resetPasswordForm.clearErrors();
    const birthDate = props.pendaftar?.personal_data?.tanggal_lahir || '';

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
    resetPasswordForm.reset();
    resetPasswordForm.clearErrors();
};

const autofillBirthdatePassword = () => {
    const birthDate = props.pendaftar?.personal_data?.tanggal_lahir || '';

    if (birthDate) {
        const rawDate = birthDate.replace(/[^0-9]/g, '');
        resetPasswordForm.password = rawDate;
        resetPasswordForm.password_confirmation = rawDate;
    }
};

const submitResetPassword = () => {
    resetPasswordForm.post(
        resetPasswordRoute.url({ pendaftar: props.pendaftar.id }),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeResetPasswordModal();
            },
        },
    );
};

const isPhotoError = ref(false);

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

const openCetakKartu = () => {
    window.open(`/admin/pendaftar/${props.pendaftar.id}/cetak-kartu`, '_blank');
};

const isVerifyModalOpen = ref(false);
const isPerbaikanModalOpen = ref(false);
const activeDetailStep = ref(1);

const verifyForm = useForm({
    action: 'terima' as 'terima' | 'tolak',
    catatan_revisi: '',
    catatan_personal: '',
    catatan_parent: '',
    catatan_address: '',
    catatan_education: '',
    dokumen_catatan: {} as Record<string, string>,
});

const openVerifyModal = () => {
    verifyForm.reset();
    verifyForm.clearErrors();
    verifyForm.action = 'terima';
    verifyForm.catatan_revisi = '';
    isVerifyModalOpen.value = true;
};

const closeVerifyModal = () => {
    isVerifyModalOpen.value = false;
    verifyForm.reset();
    verifyForm.clearErrors();
};

const submitTerima = () => {
    verifyForm.action = 'terima';
    verifyForm.post(verifyRoute.url(props.pendaftar.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeVerifyModal();
        },
    });
};

const openPerbaikanModalFromVerify = () => {
    isVerifyModalOpen.value = false;

    // Populate perbaikan form
    verifyForm.reset();
    verifyForm.clearErrors();
    verifyForm.action = 'tolak';
    verifyForm.catatan_revisi = props.pendaftar.personal_data?.catatan_revisi || '';
    verifyForm.catatan_personal = props.pendaftar.personal_data?.catatan_personal || '';
    verifyForm.catatan_parent = props.pendaftar.parent_data?.catatan_parent || '';
    verifyForm.catatan_address = props.pendaftar.address_data?.catatan_address || '';
    verifyForm.catatan_education = props.pendaftar.education_data?.catatan_education || '';
    const docNotes: Record<string, string> = {};
    (props.pendaftar.dokumens || []).forEach((d: any) => {
        if (d.dokumen_id) {
            docNotes[d.dokumen_id] = d.catatan || '';
        }
    });
    verifyForm.dokumen_catatan = docNotes;

    activeDetailStep.value = 1;
    isPerbaikanModalOpen.value = true;
};

const closePerbaikanModal = () => {
    isPerbaikanModalOpen.value = false;
    verifyForm.reset();
    verifyForm.clearErrors();
};

const submitPerbaikan = () => {
    verifyForm.action = 'tolak';
    verifyForm.post(verifyRoute.url(props.pendaftar.id), {
        preserveScroll: true,
        onSuccess: () => {
            closePerbaikanModal();
        },
    });
};

const getApplicantApplicableDocuments = (pendaftar: any): any[] => {
    if (!props.masterDokumens || props.masterDokumens.length === 0) {
        return [];
    }

    const jenjangId = pendaftar?.jenjang_id || pendaftar?.jenjang?.id;
    const rawTipe = pendaftar?.tipe_pendaftaran || pendaftar?.education_data?.tipe_pendaftaran || 'Reguler';
    const tipe = (typeof rawTipe === 'object' && rawTipe?.value ? rawTipe.value : String(rawTipe)).toLowerCase();

    return props.masterDokumens.filter((doc: any) => {
        const matchJenjang =
            !doc.jenjangs ||
            doc.jenjangs.length === 0 ||
            doc.jenjangs.some((j: any) => j.id === jenjangId || j === jenjangId);

        if (!matchJenjang) {
            return false;
        }

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

const getApplicantDocumentSummary = (pendaftar: any) => {
    const applicableDocs = getApplicantApplicableDocuments(pendaftar);
    const requiredDocs = applicableDocs.filter((d) => d.is_required);
    const uploadedDocs = pendaftar?.dokumens || [];

    const uploadedRequiredCount = requiredDocs.filter((reqDoc) =>
        uploadedDocs.some(
            (u: any) =>
                u.dokumen_id === reqDoc.id &&
                (u.file_path || u.file_url || u.path),
        ),
    ).length;

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

const getSubmitProgress = (row: any) => {
    const docSummary = getApplicantDocumentSummary(row);

    const steps = [
        {
            step: 1,
            name: 'Data Personal',
            isComplete: Boolean(
                row.personal_data?.nik &&
                row.personal_data?.nama &&
                row.personal_data?.tempat_lahir,
            ),
        },
        {
            step: 2,
            name: 'Orang Tua',
            isComplete: Boolean(
                row.parent_data?.nama_ayah ||
                row.parent_data?.nama_ibu,
            ),
        },
        {
            step: 3,
            name: 'Alamat',
            isComplete: Boolean(
                row.address_data?.alamat_lengkap ||
                row.address_data?.alamat,
            ),
        },
        {
            step: 4,
            name: 'Pendidikan',
            isComplete: Boolean(
                row.jenjang_id ||
                row.education_data?.jenjang,
            ),
        },
        {
            step: 5,
            name: 'Dokumen',
            isComplete: docSummary.isComplete,
        },
    ];

    const completed = steps.filter((s) => s.isComplete).length;
    const percentage = Math.round((completed / 5) * 100);

    return {
        steps,
        percentage,
        isComplete: completed === 5,
    };
};

const getTargetApplicableDocuments = computed(() => {
    return getApplicantApplicableDocuments(props.pendaftar);
});

const getUploadedDocumentRecord = (docId: string) => {
    if (!props.pendaftar?.dokumens) {
        return null;
    }

    return props.pendaftar.dokumens.find(
        (d: any) => d.dokumen_id === docId || d.dokumen?.id === docId,
    );
};

const getInitials = (nama?: string) => {
    if (!nama) return 'S';
    const parts = nama.trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return nama.substring(0, 2).toUpperCase();
};

const formatDateOnly = (dateString?: string) => {
    if (!dateString) return '-';
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return dateString;
    return d.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const getEducationSubText = (row: any) => {
    const ed = row.education_data || {};
    if (ed.tingkat_nama) return ed.tingkat_nama;
    if (ed.jurusan_nama) return ed.jurusan_nama;
    if (ed.prodi_utama_nama) return ed.prodi_utama_nama;
    return '';
};
</script>

<template>
    <div class="w-full space-y-6">
        <Head :title="`Profil Pendaftar - ${props.pendaftar.nama}`" />

        <!-- Top Header & Back Button matching Pegawai Show -->
        <div class="flex items-center justify-between">
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
                >
                    Profil Pendaftar
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Detail informasi pendaftar dan biodata calon santri.
                </p>
            </div>
            <BackButton :href="backUrl">Kembali</BackButton>
        </div>

        <div class="space-y-6">
            <!-- Hero Poster Background Card matching Pegawai Show -->
            <div
                class="relative mt-4 flex min-h-140 flex-col overflow-hidden border border-gray-100 bg-white shadow-sm sm:rounded-4xl dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="relative h-64 sm:h-72">
                    <img
                        src="/image/hero.jpg"
                        alt="Poster Background"
                        class="h-full w-full object-cover object-[center_30%]"
                    />
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-gray-900/20 to-gray-900/30 dark:from-slate-950/90 dark:via-slate-950/40 dark:to-slate-950/50"
                    ></div>

                    <!-- Action buttons on top of hero -->
                    <div
                        class="absolute top-4 right-4 left-4 z-20 flex items-center justify-end gap-2.5 px-4 py-2 md:top-6 md:right-8 md:left-8"
                    >
                        <button
                            v-if="props.pendaftar.status === 'SUBMITTED'"
                            type="button"
                            @click="openVerifyModal"
                            class="flex cursor-pointer items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-600 px-4 py-2 text-xs font-extrabold text-white shadow-md backdrop-blur-sm transition-all duration-300 hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                        >
                            <svg
                                class="h-4 w-4"
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
                            <span>Verifikasi Berkas</span>
                        </button>

                        <Link
                            v-if="props.pendaftar.status === 'INTERVIEW'"
                            :href="`/admin/pendaftar/set-interview/create?ids=${props.pendaftar.id}`"
                            class="flex cursor-pointer items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-600 px-4 py-2 text-xs font-extrabold text-white shadow-md backdrop-blur-sm transition-all duration-300 hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                        >
                            <svg
                                class="h-4 w-4"
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
                            <span>Jadwalkan Interview</span>
                        </Link>

                        <a
                            v-if="
                                props.pendaftar.nomor_hp ||
                                props.pendaftar.personal_data?.nomor_hp
                            "
                            :href="`https://wa.me/${(props.pendaftar.nomor_hp || props.pendaftar.personal_data?.nomor_hp).replace(/[^0-9]/g, '').replace(/^0/, '62')}`"
                            target="_blank"
                            class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full border border-white/30 bg-white/20 text-white shadow-md backdrop-blur-md transition-all duration-200 hover:scale-105 hover:border-emerald-400 hover:bg-emerald-600 hover:text-white hover:shadow-emerald-500/30 hover:shadow-lg dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-emerald-400 dark:hover:border-emerald-500 dark:hover:bg-emerald-600 dark:hover:text-white"
                            title="Hubungi via WhatsApp"
                        >
                            <svg
                                class="h-5 w-5 fill-current"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"
                                />
                            </svg>
                        </a>

                        <button
                            type="button"
                            @click="openCetakKartu"
                            class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full border border-white/30 bg-white/20 text-white shadow-md backdrop-blur-md transition-all duration-200 hover:scale-105 hover:border-blue-400 hover:bg-blue-600 hover:text-white hover:shadow-blue-500/30 hover:shadow-lg dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-blue-400 dark:hover:border-blue-500 dark:hover:bg-blue-600 dark:hover:text-white"
                            title="Cetak Kartu Registrasi"
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
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                />
                            </svg>
                        </button>

                        <button
                            type="button"
                            @click="openResetPasswordModal"
                            class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full border border-white/30 bg-white/20 text-white shadow-md backdrop-blur-md transition-all duration-200 hover:scale-105 hover:border-amber-400 hover:bg-amber-500 hover:text-white hover:shadow-amber-500/30 hover:shadow-lg dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-amber-400 dark:hover:border-amber-400 dark:hover:bg-amber-500 dark:hover:text-white"
                            title="Reset Kata Sandi"
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
                                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <div
                    class="relative z-10 -mt-16 flex flex-grow flex-col items-center px-8 md:items-start"
                >
                    <div
                        class="mb-6 h-32 w-32 shrink-0 overflow-hidden rounded-full border-4 border-white bg-white shadow-xl dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-900"
                    >
                        <img
                            v-if="getPendaftarPhoto(props.pendaftar) && !isPhotoError"
                            :src="getPendaftarPhoto(props.pendaftar)!"
                            @error="isPhotoError = true"
                            class="h-full w-full object-cover"
                            alt="Foto Profil"
                        />
                        <img
                            v-else
                            :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(props.pendaftar.nama)}&background=273b5e&color=fff&size=256`"
                            class="h-full w-full object-cover"
                            alt="Foto Profil"
                        />
                    </div>

                    <div class="mb-4 w-full text-center md:text-left">
                        <h2
                            class="text-3xl font-bold text-gray-900 dark:text-slate-100"
                        >
                            {{ props.pendaftar.nama }}
                        </h2>
                        <div
                            class="mt-1 flex flex-wrap items-center justify-center gap-3 text-sm font-medium text-gray-500 md:justify-start dark:text-slate-400"
                        >
                            <span
                                >No. Reg:
                                <strong
                                    class="font-mono font-bold text-gray-900 dark:text-slate-100 dark:text-slate-200"
                                    >{{
                                        props.pendaftar.nomor_pendaftaran || '-'
                                    }}</strong
                                ></span
                            >
                            <span>•</span>
                            <span
                                >NIK:
                                <strong
                                    class="font-mono font-bold text-gray-900 dark:text-slate-100 dark:text-slate-200"
                                    >{{ props.pendaftar.nik || '-' }}</strong
                                ></span
                            >
                            <span v-if="props.pendaftar.email">•</span>
                            <span v-if="props.pendaftar.email">{{
                                props.pendaftar.email
                            }}</span>
                        </div>
                    </div>

                    <div
                        class="mt-auto mb-8 flex w-full flex-wrap items-center justify-center gap-3 border-t border-gray-100 pt-6 md:justify-start dark:border-slate-800"
                    >
                        <!-- Status Badge -->
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border px-4 py-2 text-xs font-bold tracking-wider uppercase"
                            :class="
                                getPendaftarStatusBadge(props.pendaftar.status)
                                    .classes
                            "
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-current shadow-sm"
                            ></span>
                            {{
                                getPendaftarStatusBadge(props.pendaftar.status)
                                    .label
                            }}
                        </span>

                        <!-- Tipe Pendaftaran Badge (Reguler vs Pindahan) -->
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border px-4 py-2 text-xs font-bold"
                            :class="
                                isPindahan
                                    ? 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/60 dark:text-amber-300'
                                    : 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300'
                            "
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                                />
                            </svg>
                            Jalur {{ tipePendaftaran }}
                        </span>

                        <!-- Jenjang Badge -->
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 text-xs font-bold text-indigo-700 dark:border-indigo-900/50 dark:bg-indigo-950/60 dark:text-indigo-300"
                        >
                            <svg
                                class="h-4 w-4 text-indigo-500 dark:text-indigo-400"
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
                            {{ props.pendaftar.jenjang?.name || 'Jenjang -' }}
                        </span>

                        <!-- Gelombang Badge -->
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-sky-200 bg-sky-50 px-4 py-2 text-xs font-bold text-sky-700 dark:border-sky-900/50 dark:bg-sky-950/60 dark:text-sky-300"
                        >
                            <svg
                                class="h-4 w-4 text-sky-500 dark:text-sky-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                                />
                            </svg>
                            {{
                                props.pendaftar.gelombang?.name || 'Gelombang -'
                            }}
                        </span>

                        <!-- Periode Badge -->
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-700 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-300"
                        >
                            <svg
                                class="h-4 w-4 text-slate-500 dark:text-slate-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                            {{ props.pendaftar.periode?.name || 'Periode -' }}
                        </span>

                        <!-- Gender Badge -->
                        <span
                            v-if="props.pendaftar.personal_data?.jenis_kelamin"
                            class="inline-flex items-center gap-1.5 rounded-full border px-4 py-2 text-xs font-bold"
                            :class="
                                props.pendaftar.personal_data.jenis_kelamin
                                    .toLowerCase()
                                    .includes('laki')
                                    ? 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/60 dark:text-blue-300'
                                    : 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/60 dark:text-rose-300'
                            "
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                            {{ props.pendaftar.personal_data.jenis_kelamin }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- NAV TABS BAR (Number on top, Title underneath, centered) -->
            <div
                class="rounded-2xl border border-gray-200 bg-white p-2 sm:p-2.5 shadow-xs dark:border-slate-800 dark:bg-slate-900"
            >
                <div
                    class="flex overflow-x-auto no-scrollbar sm:grid sm:grid-cols-5 gap-2 pb-0.5 sm:pb-0"
                >
                    <button
                        v-for="t in tabs"
                        :key="t.key"
                        type="button"
                        @click="activeTab = t.key"
                        :class="[
                            activeTab === t.key
                                ? 'bg-primary text-white shadow-sm ring-1 ring-primary-600 dark:bg-blue-600 dark:ring-blue-500'
                                : 'border border-gray-100 bg-gray-50/80 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-300 dark:hover:bg-slate-800',
                            'flex shrink-0 min-w-[155px] sm:min-w-0 flex-1 cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl p-3 text-center transition-all duration-150',
                        ]"
                    >
                        <!-- Top: Step Number (Centered) -->
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-xs font-black transition-colors"
                            :class="
                                activeTab === t.key
                                    ? 'bg-white/20 text-white'
                                    : 'bg-white text-gray-700 shadow-2xs dark:bg-slate-900 dark:text-slate-300'
                            "
                        >
                            {{ t.step }}
                        </span>

                        <!-- Bottom: Tab Title (Centered) -->
                        <span class="text-xs font-bold leading-tight whitespace-nowrap sm:whitespace-normal">{{ t.name }}</span>
                    </button>
                </div>
            </div>

            <!-- TAB 1: DATA PRIBADI & ALAMAT -->
            <div v-show="activeTab === 'personal'" class="space-y-6">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Identitas Diri -->
                    <div
                        class="flex flex-col overflow-hidden border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-lg sm:rounded-4xl lg:col-span-2 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="flex-grow p-6 sm:p-8">
                            <div
                                class="mb-6 sm:mb-8 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-slate-800"
                            >
                                <div class="flex items-center gap-4">
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-2xl border border-indigo-100 bg-indigo-50 dark:border-indigo-900/50 dark:bg-indigo-950/50"
                                    >
                                        <svg
                                            class="h-6 w-6 text-indigo-500 dark:text-indigo-400"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3
                                            class="text-xl font-bold text-gray-900 dark:text-slate-100"
                                        >
                                            Identitas Diri
                                        </h3>
                                        <p
                                            class="mt-0.5 text-sm text-gray-500 dark:text-slate-400"
                                        >
                                            Informasi personal dan biodata calon santri
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Nama Lengkap Santri
                                    </p>
                                    <p
                                        class="text-sm font-bold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.nama || props.pendaftar.personal_data?.nama || '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Nomor Induk Kependudukan (NIK)
                                    </p>
                                    <p
                                        class="font-mono text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.nik || props.pendaftar.personal_data?.nik || '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        No. Kartu Keluarga (KK)
                                    </p>
                                    <p
                                        class="font-mono text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.personal_data?.no_kk || '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Jenis Kelamin
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{
                                            props.pendaftar.personal_data?.jenis_kelamin === 'L' || props.pendaftar.personal_data?.jenis_kelamin === 'Laki-laki'
                                                ? 'Laki-laki'
                                                : props.pendaftar.personal_data?.jenis_kelamin === 'P' || props.pendaftar.personal_data?.jenis_kelamin === 'Perempuan'
                                                  ? 'Perempuan'
                                                  : props.pendaftar.personal_data?.jenis_kelamin || props.pendaftar.gender || '-'
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Tempat Lahir
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.personal_data?.tempat_lahir || '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Tanggal Lahir
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ formatDate(props.pendaftar.personal_data?.tanggal_lahir) }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Nomor HP / WhatsApp
                                    </p>
                                    <p
                                        class="font-mono text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.nomor_hp || props.pendaftar.personal_data?.nomor_hp || '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Ukuran Seragam / Baju
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.personal_data?.ukuran_baju || '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Hobi & Cita-Cita
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.personal_data?.hobi || '-' }}
                                        <span class="text-gray-400 dark:text-slate-500">/</span>
                                        {{ props.pendaftar.personal_data?.cita_cita || '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Posisi & Jumlah Saudara
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        Anak ke-{{ props.pendaftar.personal_data?.anak_ke || '-' }}
                                        dari
                                        {{ props.pendaftar.personal_data?.jumlah_saudara || '-' }}
                                        bersaudara
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Jumlah Saudara di Dalwa
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.personal_data?.jumlah_saudara_di_dalwa ?? props.pendaftar.personal_data?.jumlah_saudara_dalwa ?? '0' }}
                                        Santri
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-indigo-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Cabang Pondok Terpilih
                                    </p>
                                    <p
                                        class="text-sm font-bold text-primary dark:text-blue-400"
                                    >
                                        {{ props.pendaftar.cabang?.name || props.pendaftar.personal_data?.cabang?.name || props.pendaftar.personal_data?.cabang_pendaftaran || '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ringkasan Status Kontak & Akun -->
                    <div
                        class="flex flex-col overflow-hidden border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-lg sm:rounded-4xl dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="flex-grow p-6 sm:p-8">
                            <div
                                class="mb-6 sm:mb-8 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-slate-800"
                            >
                                <div class="flex items-center gap-4">
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-2xl border border-sky-100 bg-sky-50 dark:border-sky-900/50 dark:bg-sky-950/50"
                                    >
                                        <svg
                                            class="h-6 w-6 text-sky-500 dark:text-sky-400"
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
                                    </div>
                                    <div>
                                        <h3
                                            class="text-xl font-bold text-gray-900 dark:text-slate-100"
                                        >
                                            Kontak & Akun
                                        </h3>
                                        <p
                                            class="mt-0.5 text-sm text-gray-500 dark:text-slate-400"
                                        >
                                            Akses login & aktivitas
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Alamat Email Akun
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.email || props.pendaftar.personal_data?.email || '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Nomor HP Pendaftar
                                    </p>
                                    <p
                                        class="font-mono text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.nomor_hp || props.pendaftar.personal_data?.nomor_hp || '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Tanggal Registrasi Akun
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ formatDateTime(props.pendaftar.created_at) }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Waktu Submit Final
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ formatDateTime(props.pendaftar.submitted_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Alamat Tempat Tinggal -->
                <div
                    class="flex flex-col overflow-hidden border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-lg sm:rounded-4xl dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="p-6 sm:p-8">
                        <div
                            class="mb-6 sm:mb-8 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-slate-800"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl border border-rose-100 bg-rose-50 dark:border-rose-900/50 dark:bg-rose-950/50"
                                >
                                    <svg
                                        class="h-6 w-6 text-rose-500 dark:text-rose-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h3
                                        class="text-xl font-bold text-gray-900 dark:text-slate-100"
                                    >
                                        Alamat Tempat Tinggal & Domisili
                                    </h3>
                                    <p
                                        class="mt-0.5 text-sm text-gray-500 dark:text-slate-400"
                                    >
                                        Informasi alamat lengkap dan domisili calon santri
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4"
                        >
                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-5 transition-colors hover:bg-rose-50/30 md:col-span-2 xl:col-span-4 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Jalan / Detail Alamat Lengkap
                                </p>
                                <p
                                    class="text-sm leading-relaxed font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{
                                        props.pendaftar.address_data?.alamat_lengkap ||
                                        props.pendaftar.address_data?.alamat ||
                                        '-'
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-rose-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    RT / RW
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    RT {{ props.pendaftar.address_data?.rt || '-' }} / RW {{ props.pendaftar.address_data?.rw || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-rose-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Kelurahan / Desa
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{
                                        props.pendaftar.address_data?.kelurahan_desa ||
                                        props.pendaftar.address_data?.desa ||
                                        '-'
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-rose-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Kecamatan
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.pendaftar.address_data?.kecamatan || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-rose-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Kabupaten / Kota
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{
                                        props.pendaftar.address_data?.kabupaten_kota ||
                                        props.pendaftar.address_data?.kota ||
                                        '-'
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-rose-50/30 md:col-span-1 xl:col-span-2 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Provinsi
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.pendaftar.address_data?.provinsi || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-rose-50/30 md:col-span-1 xl:col-span-2 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Negara & Kode Pos
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.pendaftar.address_data?.negara || 'Indonesia' }}
                                    <span v-if="props.pendaftar.address_data?.kode_pos">
                                        (Kode Pos: {{ props.pendaftar.address_data.kode_pos }})
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: ORANG TUA & WALI -->
            <div v-show="activeTab === 'parent'" class="space-y-6">
                <!-- Data Ayah & Ibu Grid -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Data Ayah Kandung Card -->
                    <div
                        class="flex flex-col overflow-hidden border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-lg sm:rounded-4xl dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="flex-grow p-6 sm:p-8">
                            <!-- Card Header -->
                            <div
                                class="mb-6 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-slate-800"
                            >
                                <div class="flex items-center gap-3.5">
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-2xl border border-blue-100 bg-blue-50 text-blue-600 dark:border-blue-900/50 dark:bg-blue-950/50 dark:text-blue-400"
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
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3
                                            class="text-lg font-bold text-gray-900 dark:text-slate-100"
                                        >
                                            Data Ayah Kandung
                                        </h3>
                                        <p
                                            class="text-xs text-gray-500 dark:text-slate-400"
                                        >
                                            Identitas ayah kandung calon santri
                                        </p>
                                    </div>
                                </div>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-bold"
                                    :class="
                                        (props.pendaftar.parent_data?.status_ayah || props.pendaftar.parent_data?.ayah?.status) === 'Meninggal'
                                            ? 'border border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/60 dark:text-rose-300'
                                            : 'border border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300'
                                    "
                                >
                                    {{ props.pendaftar.parent_data?.status_ayah || props.pendaftar.parent_data?.ayah?.status || 'Masih Hidup' }}
                                </span>
                            </div>

                            <!-- Ayah Key-Value Grid -->
                            <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Nama Lengkap
                                    </p>
                                    <p
                                        class="text-sm font-bold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.parent_data?.nama_ayah || props.pendaftar.parent_data?.ayah?.nama || '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        NIK Ayah
                                    </p>
                                    <p
                                        class="font-mono text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.parent_data?.nik_ayah || props.pendaftar.parent_data?.ayah?.nik || '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Tempat, Tanggal Lahir
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.parent_data?.tempat_lahir_ayah || props.pendaftar.parent_data?.ayah?.tempat_lahir || '-' }}<template v-if="props.pendaftar.parent_data?.tanggal_lahir_ayah || props.pendaftar.parent_data?.ayah?.tanggal_lahir">, {{ formatDate(props.pendaftar.parent_data?.tanggal_lahir_ayah || props.pendaftar.parent_data?.ayah?.tanggal_lahir) }}</template>
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Pendidikan Terakhir
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.parent_data?.pendidikan_ayah || props.pendaftar.parent_data?.ayah?.pendidikan || '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Pekerjaan
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.parent_data?.pekerjaan_ayah || props.pendaftar.parent_data?.ayah?.pekerjaan || '-' }}
                                        <span
                                            v-if="props.pendaftar.parent_data?.pekerjaan_ayah_lainnya"
                                            class="text-xs text-gray-500 dark:text-slate-400"
                                        >
                                            ({{ props.pendaftar.parent_data.pekerjaan_ayah_lainnya }})
                                        </span>
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Penghasilan Bulanan
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-emerald-700 dark:text-emerald-400"
                                    >
                                        {{ props.pendaftar.parent_data?.penghasilan_ayah || props.pendaftar.parent_data?.ayah?.penghasilan || '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Nomor WhatsApp / HP
                                    </p>
                                    <p
                                        class="font-mono text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.parent_data?.nomor_hp_ayah || props.pendaftar.parent_data?.ayah?.nomor_hp || '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Email
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-900 truncate dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.parent_data?.email_ayah || props.pendaftar.parent_data?.ayah?.email || '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Ibu Kandung Card -->
                    <div
                        class="flex flex-col overflow-hidden border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-lg sm:rounded-4xl dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="flex-grow p-6 sm:p-8">
                            <!-- Card Header -->
                            <div
                                class="mb-6 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-slate-800"
                            >
                                <div class="flex items-center gap-3.5">
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-2xl border border-rose-100 bg-rose-50 text-rose-600 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-400"
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
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3
                                            class="text-lg font-bold text-gray-900 dark:text-slate-100"
                                        >
                                            Data Ibu Kandung
                                        </h3>
                                        <p
                                            class="text-xs text-gray-500 dark:text-slate-400"
                                        >
                                            Identitas ibu kandung calon santri
                                        </p>
                                    </div>
                                </div>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-bold"
                                    :class="
                                        (props.pendaftar.parent_data?.status_ibu || props.pendaftar.parent_data?.ibu?.status) === 'Meninggal'
                                            ? 'border border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/60 dark:text-rose-300'
                                            : 'border border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300'
                                    "
                                >
                                    {{ props.pendaftar.parent_data?.status_ibu || props.pendaftar.parent_data?.ibu?.status || 'Masih Hidup' }}
                                </span>
                            </div>

                            <!-- Ibu Key-Value Grid -->
                            <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Nama Lengkap
                                    </p>
                                    <p
                                        class="text-sm font-bold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.parent_data?.nama_ibu || props.pendaftar.parent_data?.ibu?.nama || '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        NIK Ibu
                                    </p>
                                    <p
                                        class="font-mono text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.parent_data?.nik_ibu || props.pendaftar.parent_data?.ibu?.nik || '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Tempat, Tanggal Lahir
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.parent_data?.tempat_lahir_ibu || props.pendaftar.parent_data?.ibu?.tempat_lahir || '-' }}<template v-if="props.pendaftar.parent_data?.tanggal_lahir_ibu || props.pendaftar.parent_data?.ibu?.tanggal_lahir">, {{ formatDate(props.pendaftar.parent_data?.tanggal_lahir_ibu || props.pendaftar.parent_data?.ibu?.tanggal_lahir) }}</template>
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Pendidikan Terakhir
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.parent_data?.pendidikan_ibu || props.pendaftar.parent_data?.ibu?.pendidikan || '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Pekerjaan
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.parent_data?.pekerjaan_ibu || props.pendaftar.parent_data?.ibu?.pekerjaan || '-' }}
                                        <span
                                            v-if="props.pendaftar.parent_data?.pekerjaan_ibu_lainnya"
                                            class="text-xs text-gray-500 dark:text-slate-400"
                                        >
                                            ({{ props.pendaftar.parent_data.pekerjaan_ibu_lainnya }})
                                        </span>
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Penghasilan Bulanan
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-emerald-700 dark:text-emerald-400"
                                    >
                                        {{ props.pendaftar.parent_data?.penghasilan_ibu || props.pendaftar.parent_data?.ibu?.penghasilan || '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Nomor WhatsApp / HP
                                    </p>
                                    <p
                                        class="font-mono text-sm font-semibold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.parent_data?.nomor_hp_ibu || props.pendaftar.parent_data?.ibu?.nomor_hp || '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                                >
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Email
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-900 truncate dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.parent_data?.email_ibu || props.pendaftar.parent_data?.ibu?.email || '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Wali Santri Card -->
                <div
                    class="flex flex-col overflow-hidden border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-lg sm:rounded-4xl dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="p-6 sm:p-8">
                        <div
                            class="mb-6 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-slate-800"
                        >
                            <div class="flex items-center gap-3.5">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl border border-amber-100 bg-amber-50 text-amber-600 dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-400"
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
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h3
                                        class="text-lg font-bold text-gray-900 dark:text-slate-100"
                                    >
                                        Data Wali Santri
                                    </h3>
                                    <p
                                        class="text-xs text-gray-500 dark:text-slate-400"
                                    >
                                        Informasi pihak perwalian calon santri
                                    </p>
                                </div>
                            </div>
                            <span
                                class="rounded-full px-3 py-1 text-xs font-bold"
                                :class="
                                    props.pendaftar.parent_data?.has_wali || props.pendaftar.parent_data?.nama_wali || props.pendaftar.parent_data?.wali?.nama
                                        ? 'border border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/60 dark:text-amber-300'
                                        : 'border border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400'
                                "
                            >
                                {{
                                    props.pendaftar.parent_data?.has_wali || props.pendaftar.parent_data?.nama_wali || props.pendaftar.parent_data?.wali?.nama
                                        ? 'Wali Terdaftar'
                                        : 'Sesuai Orang Tua'
                                }}
                            </span>
                        </div>

                        <!-- If Guardian Exists -->
                        <div
                            v-if="props.pendaftar.parent_data?.has_wali || props.pendaftar.parent_data?.nama_wali || props.pendaftar.parent_data?.wali?.nama"
                            class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4"
                        >
                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                            >
                                <p
                                    class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Nama Lengkap Wali
                                </p>
                                <p
                                    class="text-sm font-bold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.pendaftar.parent_data?.nama_wali || props.pendaftar.parent_data?.wali?.nama || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                            >
                                <p
                                    class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    NIK Wali
                                </p>
                                <p
                                    class="font-mono text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.pendaftar.parent_data?.nik_wali || props.pendaftar.parent_data?.wali?.nik || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                            >
                                <p
                                    class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Tempat, Tanggal Lahir
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.pendaftar.parent_data?.tempat_lahir_wali || props.pendaftar.parent_data?.wali?.tempat_lahir || '-' }}<template v-if="props.pendaftar.parent_data?.tanggal_lahir_wali || props.pendaftar.parent_data?.wali?.tanggal_lahir">, {{ formatDate(props.pendaftar.parent_data?.tanggal_lahir_wali || props.pendaftar.parent_data?.wali?.tanggal_lahir) }}</template>
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                            >
                                <p
                                    class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Pendidikan Terakhir
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.pendaftar.parent_data?.pendidikan_wali || props.pendaftar.parent_data?.wali?.pendidikan || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                            >
                                <p
                                    class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Pekerjaan
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.pendaftar.parent_data?.pekerjaan_wali || props.pendaftar.parent_data?.wali?.pekerjaan || '-' }}
                                    <span
                                        v-if="props.pendaftar.parent_data?.pekerjaan_wali_lainnya"
                                        class="text-xs text-gray-500 dark:text-slate-400"
                                    >
                                        ({{ props.pendaftar.parent_data.pekerjaan_wali_lainnya }})
                                    </span>
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                            >
                                <p
                                    class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Penghasilan Bulanan
                                </p>
                                <p
                                    class="text-sm font-semibold text-emerald-700 dark:text-emerald-400"
                                >
                                    {{ props.pendaftar.parent_data?.penghasilan_wali || props.pendaftar.parent_data?.wali?.penghasilan || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                            >
                                <p
                                    class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Nomor WhatsApp / HP
                                </p>
                                <p
                                    class="font-mono text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.pendaftar.parent_data?.nomor_hp_wali || props.pendaftar.parent_data?.wali?.nomor_hp || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-800/50"
                            >
                                <p
                                    class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Email
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 truncate dark:text-slate-100"
                                >
                                    {{ props.pendaftar.parent_data?.email_wali || props.pendaftar.parent_data?.wali?.email || '-' }}
                                </p>
                            </div>
                        </div>

                        <!-- If No Guardian -->
                        <div
                            v-else
                            class="flex items-center gap-3.5 rounded-2xl border border-dashed border-gray-200 bg-gray-50/60 p-4.5 text-xs text-gray-500 dark:border-slate-800 dark:bg-slate-800/40 dark:text-slate-400"
                        >
                            <svg
                                class="h-5 w-5 text-gray-400 dark:text-slate-500 shrink-0"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            <span>
                                Calon santri tidak memiliki wali terpisah. Seluruh data perwalian dan tanggung jawab pengasuhan ditangani langsung oleh orang tua kandung.
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: JENJANG & PENDIDIKAN (Matching /psb/biodata Tab 4 Pilihan Pendidikan) -->
            <div v-show="activeTab === 'education'" class="space-y-6">
                <!-- CARD 1: PILIHAN PROGRAM PENDIDIKAN TUJUAN -->
                <div
                    class="flex flex-col overflow-hidden border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-lg sm:rounded-4xl dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="p-6 sm:p-8">
                        <!-- Header with Huge Jenjang Logo & Badges -->
                        <div
                            class="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-6 dark:border-slate-800"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border border-gray-100 bg-gray-50/90 p-2 shadow-2xs dark:border-slate-700/60 dark:bg-slate-800"
                                >
                                    <img
                                        :src="getJenjangLogo(props.pendaftar.jenjang || jenjangCode)"
                                        :alt="props.pendaftar.jenjang?.name || jenjangCode"
                                        class="h-full w-full object-contain"
                                    />
                                </div>
                                <div>
                                    <h3
                                        class="text-xl font-bold text-gray-900 dark:text-slate-100"
                                    >
                                        Pilihan Program Pendidikan Tujuan
                                    </h3>
                                    <p
                                        class="mt-0.5 text-sm text-gray-500 dark:text-slate-400"
                                    >
                                        Jalur dan jenjang pendidikan yang ditempuh di Pondok Pesantren Dalwa Kalbar
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-black tracking-wider uppercase border border-primary/20 bg-primary/10 text-primary dark:bg-primary/30 dark:text-blue-200 dark:border-primary/40"
                                >
                                    {{ props.pendaftar.jenjang?.code || jenjangCode }}
                                </span>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-bold border"
                                    :class="
                                        isPindahan
                                            ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/60 dark:text-amber-300'
                                            : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300'
                                    "
                                >
                                    {{ tipePendaftaran }}
                                </span>
                            </div>
                        </div>

                        <!-- Key Information Grid -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-sky-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Jalur Pendaftaran
                                </p>
                                <p
                                    class="text-sm font-bold text-gray-900 dark:text-slate-100"
                                >
                                    {{
                                        tipePendaftaran === 'Reguler'
                                            ? 'Jalur Reguler (Santri Baru)'
                                            : tipePendaftaran === 'Pindahan'
                                              ? 'Jalur Pindahan (Pindah Jenjang)'
                                              : tipePendaftaran
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-sky-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Jenjang Pendidikan
                                </p>
                                <p
                                    class="text-sm font-bold text-primary dark:text-blue-400"
                                >
                                    {{ props.pendaftar.jenjang?.name || jenjangCode }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-sky-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Cabang Pondok Tujuan
                                </p>
                                <p
                                    class="text-sm font-bold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.pendaftar.cabang?.name || props.pendaftar.personal_data?.cabang?.name || '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-sky-50/30 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Periode & Gelombang
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.pendaftar.periode?.name || '-' }}
                                    <span v-if="props.pendaftar.gelombang?.name" class="text-primary dark:text-blue-400 font-bold">
                                        • {{ props.pendaftar.gelombang.name }}
                                    </span>
                                </p>
                            </div>

                            <!-- Contextual Specific Fields per Jenjang -->
                            <!-- 1. MTs Field -->
                            <template v-if="jenjangCode === 'MTS'">
                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-sky-50/30 md:col-span-2 lg:col-span-4 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Kelas / Tingkat Masuk MTs Tujuan
                                    </p>
                                    <p
                                        class="text-sm font-bold text-primary dark:text-blue-400"
                                    >
                                        {{
                                            props.pendaftar.education_data?.tingkat_nama ||
                                            props.pendaftar.education_data?.kelas_tingkat ||
                                            props.pendaftar.education_data?.tingkat ||
                                            (isPindahan
                                                ? 'Tingkat Lanjutan (Pindahan)'
                                                : 'Kelas 7 (Tingkat Awal MTs - Default Reguler)')
                                        }}
                                    </p>
                                </div>
                            </template>

                            <!-- 2. MA Field -->
                            <template v-else-if="jenjangCode === 'MA'">
                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-sky-50/30 md:col-span-1 lg:col-span-2 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Jurusan Madrasah Aliyah (MA)
                                    </p>
                                    <p
                                        class="text-sm font-bold text-primary dark:text-blue-400"
                                    >
                                        {{
                                            props.pendaftar.education_data?.jurusan_nama ||
                                            props.pendaftar.education_data?.jurusan_ma ||
                                            props.pendaftar.education_data?.jurusan ||
                                            '-'
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-sky-50/30 md:col-span-1 lg:col-span-2 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Kelas / Tingkat Masuk MA Tujuan
                                    </p>
                                    <p
                                        class="text-sm font-bold text-gray-900 dark:text-slate-100"
                                    >
                                        {{
                                            props.pendaftar.education_data?.tingkat_nama ||
                                            props.pendaftar.education_data?.kelas_tingkat ||
                                            props.pendaftar.education_data?.tingkat ||
                                            (isPindahan
                                                ? 'Tingkat Lanjutan (Pindahan)'
                                                : 'Kelas 10 (Tingkat Awal MA - Default Reguler)')
                                        }}
                                    </p>
                                </div>
                            </template>

                            <!-- 3. Perguruan Tinggi (S1, S2, S3) -->
                            <template v-else-if="isHigherEducation">
                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-sky-50/30 md:col-span-2 lg:col-span-4 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Fakultas & Program Studi (Pilihan Utama)
                                    </p>
                                    <p
                                        class="text-sm font-bold text-primary dark:text-blue-400"
                                    >
                                        {{
                                            props.pendaftar.education_data?.fakultas_utama_nama
                                                ? props.pendaftar.education_data.fakultas_utama_nama + ' - ' + (props.pendaftar.education_data.prodi_utama_nama || '')
                                                : props.pendaftar.education_data?.fakultas_prodi_utama ||
                                                  props.pendaftar.education_data?.prodi_utama ||
                                                  props.pendaftar.education_data?.prodi ||
                                                  '-'
                                        }}
                                    </p>
                                </div>

                                <div
                                    v-if="
                                        props.pendaftar.education_data?.fakultas_alt1_nama ||
                                        props.pendaftar.education_data?.fakultas_prodi_alt1 ||
                                        props.pendaftar.education_data?.prodi_alt1
                                    "
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-sky-50/30 md:col-span-1 lg:col-span-2 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Fakultas & Prodi (Alternatif 1)
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-800 dark:text-slate-200"
                                    >
                                        {{
                                            props.pendaftar.education_data?.fakultas_alt1_nama
                                                ? props.pendaftar.education_data.fakultas_alt1_nama + ' - ' + (props.pendaftar.education_data.prodi_alt1_nama || '')
                                                : props.pendaftar.education_data?.fakultas_prodi_alt1 ||
                                                  props.pendaftar.education_data?.prodi_alt1 ||
                                                  '-'
                                        }}
                                    </p>
                                </div>

                                <div
                                    v-if="
                                        props.pendaftar.education_data?.fakultas_alt2_nama ||
                                        props.pendaftar.education_data?.fakultas_prodi_alt2 ||
                                        props.pendaftar.education_data?.prodi_alt2
                                    "
                                    class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors hover:bg-sky-50/30 md:col-span-1 lg:col-span-2 dark:border-slate-800 dark:bg-slate-800/50 dark:hover:bg-slate-800"
                                >
                                    <p
                                        class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Fakultas & Prodi (Alternatif 2)
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-800 dark:text-slate-200"
                                    >
                                        {{
                                            props.pendaftar.education_data?.fakultas_alt2_nama
                                                ? props.pendaftar.education_data.fakultas_alt2_nama + ' - ' + (props.pendaftar.education_data.prodi_alt2_nama || '')
                                                : props.pendaftar.education_data?.fakultas_prodi_alt2 ||
                                                  props.pendaftar.education_data?.prodi_alt2 ||
                                                  '-'
                                        }}
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- CARD 2: DATA RIWAYAT PENDIDIKAN SEBELUMNYA -->
                <div
                    class="flex flex-col overflow-hidden border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-lg sm:rounded-4xl dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="p-6 sm:p-8">
                        <div
                            class="mb-6 sm:mb-8 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-slate-800"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl border border-indigo-100 bg-indigo-50 dark:border-indigo-900/50 dark:bg-indigo-950/50"
                                >
                                    <svg
                                        class="h-6 w-6 text-indigo-500 dark:text-indigo-400"
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
                                </div>
                                <div>
                                    <h3
                                        class="text-xl font-bold text-gray-900 dark:text-slate-100"
                                    >
                                        Data Riwayat Pendidikan Sebelumnya
                                    </h3>
                                    <p
                                        class="mt-0.5 text-sm text-gray-500 dark:text-slate-400"
                                    >
                                        Riwayat jenjang dan institusi sekolah asal santri terintegrasi
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50 sm:col-span-2"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Nama Sekolah / Madrasah / PT Asal
                                </p>
                                <p
                                    class="text-sm font-bold text-gray-900 dark:text-slate-100"
                                >
                                    {{
                                        props.pendaftar.education_data?.nama_sekolah_asal ||
                                        props.pendaftar.education_data?.pendidikan_sebelumnya?.nama_sekolah ||
                                        props.pendaftar.education_data?.asal_sekolah ||
                                        '-'
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Nomor Induk Siswa Nasional (NISN)
                                </p>
                                <p
                                    class="font-mono text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{
                                        props.pendaftar.education_data?.nisn ||
                                        props.pendaftar.education_data?.pendidikan_sebelumnya?.nisn ||
                                        '-'
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Tipe Sekolah Asal
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{
                                        props.pendaftar.education_data?.tipe_sekolah_asal ||
                                        props.pendaftar.education_data?.pendidikan_sebelumnya?.tipe ||
                                        'Umum'
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Jenjang Sekolah Asal
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{
                                        props.pendaftar.education_data?.jenjang_sekolah_asal ||
                                        props.pendaftar.education_data?.pendidikan_sebelumnya?.jenjang ||
                                        '-'
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Tingkat / Kelas Terakhir Sebelumnya
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{
                                        props.pendaftar.education_data?.tingkat_sebelumnya ||
                                        props.pendaftar.education_data?.pendidikan_sebelumnya?.tingkat ||
                                        '-'
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    NPSN / NSM Sekolah Asal
                                </p>
                                <p
                                    class="font-mono text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{
                                        props.pendaftar.education_data?.npsn_sekolah_asal ||
                                        props.pendaftar.education_data?.pendidikan_sebelumnya?.npsn ||
                                        props.pendaftar.education_data?.npsn ||
                                        '-'
                                    }}
                                    <span
                                        v-if="
                                            props.pendaftar.education_data?.nsm_sekolah_asal ||
                                            props.pendaftar.education_data?.pendidikan_sebelumnya?.nsm ||
                                            props.pendaftar.education_data?.nsm
                                        "
                                        class="text-gray-500 dark:text-slate-400"
                                    >
                                        / NSM: {{ props.pendaftar.education_data?.nsm_sekolah_asal || props.pendaftar.education_data?.pendidikan_sebelumnya?.nsm || props.pendaftar.education_data?.nsm }}
                                    </span>
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Nomor Seri Ijazah / SKL
                                </p>
                                <p
                                    class="font-mono text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{
                                        props.pendaftar.education_data?.no_ijazah ||
                                        props.pendaftar.education_data?.pendidikan_sebelumnya?.no_ijazah ||
                                        '-'
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Tahun Kelulusan
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{
                                        props.pendaftar.education_data?.tahun_lulus ||
                                        props.pendaftar.education_data?.pendidikan_sebelumnya?.tahun_lulus ||
                                        '-'
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50 sm:col-span-2 lg:col-span-3"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Alamat Sekolah Asal
                                </p>
                                <p
                                    class="text-sm leading-relaxed font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{
                                        props.pendaftar.education_data?.alamat_sekolah_asal ||
                                        props.pendaftar.education_data?.pendidikan_sebelumnya?.alamat_sekolah ||
                                        '-'
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: DOKUMEN LAMPIRAN (With Interactive Previews) -->
            <div v-show="activeTab === 'document'" class="space-y-6">
                <div
                    class="flex flex-col overflow-hidden border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-lg sm:rounded-4xl dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="p-6 sm:p-8">
                        <div
                            class="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-4 dark:border-slate-800"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl border border-emerald-100 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-950/50"
                                >
                                    <svg
                                        class="h-6 w-6 text-emerald-500 dark:text-emerald-400"
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
                                <div>
                                    <h3
                                        class="text-xl font-bold text-gray-900 dark:text-slate-100"
                                    >
                                        Dokumen Lampiran Pendaftaran
                                    </h3>
                                    <p
                                        class="mt-0.5 text-sm text-gray-500 dark:text-slate-400"
                                    >
                                        Berkas persyaratan dan kelengkapan dokumen yang diunggah calon santri
                                    </p>
                                </div>
                            </div>

                            <span
                                class="inline-flex items-center gap-1.5 self-start sm:self-auto rounded-full border border-emerald-200 bg-emerald-50 px-3.5 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300"
                            >
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                {{ props.pendaftar.dokumens?.length || 0 }} Berkas Terunggah
                            </span>
                        </div>

                        <!-- Empty State -->
                        <div
                            v-if="!props.pendaftar.dokumens || props.pendaftar.dokumens.length === 0"
                            class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-gray-200 bg-gray-50/70 p-12 text-center dark:border-slate-800 dark:bg-slate-800/40"
                        >
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-slate-800 dark:text-slate-500"
                            >
                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                            </div>
                            <h4 class="mt-4 text-base font-bold text-gray-900 dark:text-slate-100">
                                Belum Ada Dokumen Lampiran
                            </h4>
                            <p class="mt-1 text-xs text-gray-500 max-w-sm dark:text-slate-400">
                                Calon santri belum mengunggah dokumen kelengkapan berkas persyaratan pendaftaran.
                            </p>
                        </div>

                        <!-- Grid Cards with Interactive Previews -->
                        <div
                            v-else
                            class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <div
                                v-for="dok in props.pendaftar.dokumens"
                                :key="dok.id"
                                class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-gray-200/90 bg-white p-4 shadow-2xs transition-all duration-200 hover:-translate-y-1 hover:border-primary/40 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900 dark:hover:border-blue-500/40"
                            >
                                <!-- Top Bar: Document Type & Status -->
                                <div class="flex items-center justify-between gap-2 mb-3">
                                    <span
                                        class="truncate rounded-xl border border-gray-200 bg-gray-50 px-2.5 py-1 text-[11px] font-bold text-gray-700 uppercase tracking-wider dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                    >
                                        {{ dok.dokumen?.name || dok.dokumen?.nama || 'Dokumen' }}
                                    </span>
                                    <span
                                        class="shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider border"
                                        :class="
                                            (dok.status || '').toLowerCase() === 'valid' || (dok.status || '').toLowerCase() === 'verified'
                                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300'
                                                : (dok.status || '').toLowerCase() === 'invalid' || (dok.status || '').toLowerCase() === 'rejected'
                                                  ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/60 dark:text-rose-300'
                                                  : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/60 dark:text-amber-300'
                                        "
                                    >
                                        {{ dok.status || 'Draft' }}
                                    </span>
                                </div>

                                <!-- Visual Preview Container (Direct Open New Tab) -->
                                <a
                                    :href="getDocumentFileUrl(dok) || '#'"
                                    target="_blank"
                                    class="relative h-48 w-full cursor-pointer overflow-hidden rounded-2xl border border-gray-100 bg-gray-50 transition-all hover:opacity-95 dark:border-slate-800 dark:bg-slate-800/60 block"
                                >
                                    <!-- Image Preview -->
                                    <template v-if="isImageFile(getDocumentFileUrl(dok))">
                                        <img
                                            :src="getDocumentFileUrl(dok)"
                                            :alt="getDocFileName(dok)"
                                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        />
                                        <!-- Overlay on Hover -->
                                        <div
                                            class="absolute inset-0 flex items-center justify-center bg-gray-900/40 opacity-0 backdrop-blur-xs transition-opacity duration-200 group-hover:opacity-100 z-10"
                                        >
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-xl bg-white/95 px-3 py-1.5 text-xs font-bold text-gray-900 shadow-md backdrop-blur-md dark:bg-slate-900/95 dark:text-white"
                                            >
                                                <svg class="h-4 w-4 text-primary dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                Buka di Tab Baru
                                            </span>
                                        </div>
                                    </template>

                                    <!-- PDF Document Preview -->
                                    <template v-else-if="isPdfFile(getDocumentFileUrl(dok))">
                                        <div class="relative h-full w-full overflow-hidden bg-slate-100 dark:bg-slate-950 flex items-center justify-center">
                                            <iframe
                                                :src="getDocumentFileUrl(dok) + '#page=1&view=FitH&toolbar=0&navpanes=0&scrollbar=0'"
                                                class="h-full w-full pointer-events-none border-0 select-none opacity-90 transition-transform duration-300 group-hover:scale-105"
                                                loading="lazy"
                                                title="Pratinjau PDF"
                                            ></iframe>
                                            <!-- Top PDF Badge on Card -->
                                            <div class="absolute top-2.5 left-2.5 z-10">
                                                <span class="inline-flex items-center gap-1 rounded-lg bg-rose-600/90 backdrop-blur-xs px-2 py-0.5 text-[10px] font-black text-white shadow-xs uppercase tracking-wider">
                                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                    PDF
                                                </span>
                                            </div>
                                            <!-- Overlay on Hover -->
                                            <div
                                                class="absolute inset-0 z-20 flex items-center justify-center bg-gray-900/50 opacity-0 backdrop-blur-xs transition-opacity duration-200 group-hover:opacity-100"
                                            >
                                                <span
                                                    class="inline-flex items-center gap-1.5 rounded-xl bg-white/95 px-3.5 py-2 text-xs font-bold text-gray-900 shadow-md backdrop-blur-md dark:bg-slate-900/95 dark:text-white"
                                                >
                                                    <svg class="h-4 w-4 text-primary dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    Buka Dokumen PDF
                                                </span>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Fallback for Non-image & Non-PDF -->
                                    <template v-else>
                                        <div
                                            class="flex h-full w-full flex-col items-center justify-center bg-gradient-to-br from-rose-50/70 via-orange-50/40 to-amber-50/50 p-4 text-center dark:from-rose-950/20 dark:via-slate-900 dark:to-orange-950/20"
                                        >
                                            <div
                                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-500/10 text-rose-600 shadow-2xs dark:bg-rose-500/20 dark:text-rose-400"
                                            >
                                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="1.8"
                                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                                    />
                                                </svg>
                                            </div>
                                            <span class="mt-2.5 block text-xs font-bold text-gray-800 truncate max-w-[210px] dark:text-slate-200">
                                                {{ getDocFileName(dok) }}
                                            </span>
                                            <span class="mt-1 text-[11px] font-semibold text-rose-600 dark:text-rose-400">
                                                Buka di Tab Baru
                                            </span>
                                        </div>
                                    </template>
                                </a>

                                <!-- File Meta & Action Buttons -->
                                <div class="mt-3.5 pt-3 border-t border-gray-100 flex items-center justify-between dark:border-slate-800">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-gray-900 truncate max-w-[140px] sm:max-w-[160px] dark:text-slate-100">
                                            {{ getDocFileName(dok) }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 dark:text-slate-500">
                                            {{ formatDateTime(dok.created_at) }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1.5">
                                        <a
                                            v-if="getDocumentFileUrl(dok)"
                                            :href="getDocumentFileUrl(dok)"
                                            target="_blank"
                                            class="inline-flex cursor-pointer items-center justify-center rounded-xl p-2 text-gray-600 hover:bg-gray-100 hover:text-primary transition-colors dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-blue-400"
                                            title="Buka Berkas di Tab Baru"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>

                                        <a
                                            v-if="getDocumentFileUrl(dok)"
                                            :href="getDocumentFileUrl(dok)"
                                            target="_blank"
                                            download
                                            class="inline-flex cursor-pointer items-center justify-center rounded-xl p-2 text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 transition-colors dark:text-emerald-400 dark:hover:bg-emerald-950/50"
                                            title="Unduh Berkas"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 5: VIRTUAL ACCOUNT BANK (Centered Logo, Name, & VA Number) -->
            <div v-show="activeTab === 'va'" class="space-y-6">
                <div
                    class="flex flex-col overflow-hidden border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-lg sm:rounded-4xl dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="p-6 sm:p-8">
                        <div
                            class="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-4 dark:border-slate-800"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl border border-indigo-100 bg-indigo-50 dark:border-indigo-900/50 dark:bg-indigo-950/50"
                                >
                                    <svg
                                        class="h-6 w-6 text-indigo-500 dark:text-indigo-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h3
                                        class="text-xl font-bold text-gray-900 dark:text-slate-100"
                                    >
                                        Virtual Account Bank Pendaftar
                                    </h3>
                                    <p
                                        class="mt-0.5 text-sm text-gray-500 dark:text-slate-400"
                                    >
                                        Nomor rekening pembayaran per bank resmi yang aktif
                                    </p>
                                </div>
                            </div>

                            <span
                                class="inline-flex items-center gap-1.5 self-start sm:self-auto rounded-full border border-indigo-200 bg-indigo-50 px-3.5 py-1 text-xs font-bold text-indigo-700 dark:border-indigo-900/50 dark:bg-indigo-950/60 dark:text-indigo-300"
                            >
                                <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                                {{ activeVirtualAccounts.length }} Channel Bank Aktif
                            </span>
                        </div>

                        <!-- Empty State -->
                        <div
                            v-if="activeVirtualAccounts.length === 0"
                            class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-gray-200 bg-gray-50/70 p-12 text-center dark:border-slate-800 dark:bg-slate-800/40"
                        >
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-slate-800 dark:text-slate-500"
                            >
                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                    />
                                </svg>
                            </div>
                            <h4 class="mt-4 text-base font-bold text-gray-900 dark:text-slate-100">
                                Belum Ada Virtual Account Aktif
                            </h4>
                            <p class="mt-1 text-xs text-gray-500 max-w-sm dark:text-slate-400">
                                Belum ada nomor Virtual Account aktif yang diterbitkan untuk pendaftar ini.
                            </p>
                        </div>

                        <!-- Centered Virtual Account Cards -->
                        <div
                            v-else
                            class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <div
                                v-for="va in activeVirtualAccounts"
                                :key="va.id"
                                class="group relative flex flex-col items-center justify-between rounded-3xl border border-gray-200/90 bg-white p-6 sm:p-7 text-center shadow-2xs transition-all duration-300 hover:-translate-y-1 hover:border-primary/40 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900 dark:hover:border-blue-500/40"
                            >
                                <!-- TOP SECTION: Bank Logo & Bank Name (Centered) -->
                                <div class="flex flex-col items-center w-full">
                                    <!-- Bank Logo Container -->
                                    <div
                                        class="flex h-16 w-36 items-center justify-center rounded-2xl border border-gray-100 bg-gray-50/80 p-2.5 shadow-2xs dark:border-slate-700/60 dark:bg-slate-800"
                                    >
                                        <img
                                            :src="getBankLogo(va.bank || va)"
                                            :alt="va.bank?.name || va.bank?.nama_bank || 'Bank'"
                                            class="h-full w-auto max-h-11 object-contain transition-transform duration-200 group-hover:scale-105"
                                        />
                                    </div>

                                    <!-- Bank Name -->
                                    <h4 class="mt-3.5 text-base font-extrabold text-gray-900 leading-snug dark:text-slate-100">
                                        {{
                                            va.bank?.name ||
                                            va.bank?.nama_bank ||
                                            va.bank?.singkatan ||
                                            'Bank Pembayaran'
                                        }}
                                    </h4>

                                    <!-- Bank Code & Status Pill -->
                                    <div class="mt-1 flex items-center justify-center gap-2">
                                        <span
                                            v-if="va.bank?.kode_bank"
                                            class="font-mono text-xs font-semibold text-gray-400 dark:text-slate-500"
                                        >
                                            Kode Bank: {{ va.bank.kode_bank }}
                                        </span>
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700 uppercase dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Aktif
                                        </span>
                                    </div>
                                </div>

                                <!-- MIDDLE SECTION: Nomor VA (Centered) -->
                                <div class="mt-6 w-full flex flex-col items-center">
                                    <span
                                        class="text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        Nomor Virtual Account
                                    </span>

                                    <!-- VA Highlight Box with Copy Button -->
                                    <div
                                        class="mt-2 flex w-full items-center justify-between gap-2 rounded-2xl border border-primary/20 bg-primary/5 px-4 py-3 dark:border-blue-500/30 dark:bg-blue-950/30"
                                    >
                                        <span
                                            class="font-mono text-lg sm:text-xl font-black tracking-wider text-primary dark:text-blue-300 select-all"
                                        >
                                            {{ va.nomor_va || va.va_number || '-' }}
                                        </span>

                                        <button
                                            type="button"
                                            @click="copyToClipboard(va.nomor_va || va.va_number, va.id)"
                                            class="flex h-9 w-9 shrink-0 cursor-pointer items-center justify-center rounded-xl bg-white text-primary shadow-2xs transition-all hover:scale-105 hover:bg-primary hover:text-white dark:bg-slate-800 dark:text-blue-400 dark:hover:bg-blue-600 dark:hover:text-white"
                                            :title="copiedVaId === va.id ? 'Tersalin!' : 'Salin Nomor VA'"
                                        >
                                            <svg
                                                v-if="copiedVaId === va.id"
                                                class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <svg
                                                v-else
                                                class="h-4 w-4"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                    <span v-if="copiedVaId === va.id" class="mt-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                                        Nomor VA berhasil disalin!
                                    </span>
                                </div>

                                <!-- BOTTOM SECTION: Meta Info (Centered) -->
                                <div class="mt-5 w-full border-t border-gray-100 pt-3.5 text-center dark:border-slate-800">
                                    <p class="text-xs font-bold text-gray-800 dark:text-slate-200">
                                        a.n. {{ props.pendaftar.nama || 'Calon Santri' }}
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-gray-400 dark:text-slate-500">
                                        Pembayaran akan diverifikasi oleh petugas panitia PSB.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <!-- ======================================================= -->
        <!-- MODAL: RESET PASSWORD -->
        <!-- ======================================================= -->
        <Modal
            :show="isResetPasswordModalOpen"
            @close="closeResetPasswordModal"
            maxWidth="md"
            title="Reset Password"
            description="Atur ulang kata sandi pendaftar ini ke password baru."
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
                    class="rounded-xl border border-gray-100 bg-gray-50 p-3.5 text-xs text-gray-700 dark:border-slate-800 dark:bg-slate-800 dark:bg-slate-800/70 dark:text-slate-200 dark:text-slate-300"
                >
                    <div>
                        Pendaftar:
                        <strong class="text-gray-900 dark:text-slate-100">{{
                            props.pendaftar.nama
                        }}</strong>
                    </div>
                    <div class="mt-0.5">
                        NIK:
                        <strong
                            class="font-mono text-gray-900 dark:text-slate-100"
                            >{{ props.pendaftar.nik || '-' }}</strong
                        >
                    </div>
                    <div
                        v-if="props.pendaftar.personal_data?.tanggal_lahir"
                        class="mt-2 flex items-center justify-between border-t border-gray-200 pt-2 dark:border-slate-700/60 dark:border-slate-800"
                    >
                        <span class="text-gray-500 dark:text-slate-400"
                            >Tgl Lahir:
                            {{
                                props.pendaftar.personal_data.tanggal_lahir
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
                    id="password"
                    v-model="resetPasswordForm.password"
                    label="Kata Sandi Baru"
                    placeholder="Minimal 6 karakter"
                    :error="resetPasswordForm.errors.password"
                    required
                />

                <PasswordInput
                    id="password_confirmation"
                    v-model="resetPasswordForm.password_confirmation"
                    label="Konfirmasi Kata Sandi Baru"
                    placeholder="Ulangi kata sandi baru"
                    :error="resetPasswordForm.errors.password_confirmation"
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
        <!-- MODAL 1: VERIFIKASI AWAL (2 OPSI: TERIMA & PERBAIKAN) -->
        <!-- ======================================================= -->
        <Modal
            :show="isVerifyModalOpen"
            @close="closeVerifyModal"
            maxWidth="md"
            title="Verifikasi Berkas Calon Santri"
            description="Tentukan keputusan verifikasi berkas pendaftaran calon santri."
        >
            <template #icon>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400"
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
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>
            </template>

            <div class="space-y-4">
                <div
                    class="rounded-xl border border-gray-100 bg-gray-50 p-3.5 text-xs text-gray-700 dark:border-slate-800 dark:bg-slate-800/70 dark:text-slate-300"
                >
                    <div>
                        Pendaftar:
                        <strong class="text-gray-900 dark:text-slate-100">{{ props.pendaftar.nama }}</strong>
                    </div>
                    <div class="mt-0.5">
                        No. Registrasi:
                        <strong class="font-mono text-gray-900 dark:text-slate-100">{{ props.pendaftar.nomor_pendaftaran || '-' }}</strong>
                    </div>
                    <div class="mt-0.5 flex items-center gap-2">
                        <span>Jenjang: <strong class="text-primary dark:text-blue-400 font-bold">{{ props.pendaftar.jenjang?.name || '-' }}</strong></span>
                        <span>•</span>
                        <span>Jalur: <strong>{{ props.pendaftar.tipe_pendaftaran || 'Reguler' }}</strong></span>
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
        <!-- MODAL 2: FORM CATATAN PERBAIKAN BERKAS LENGKAP -->
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

            <div class="space-y-4 sm:space-y-5">
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
                                v-if="getPendaftarPhoto(props.pendaftar) && !isPhotoError"
                                :src="getPendaftarPhoto(props.pendaftar)!"
                                @error="isPhotoError = true"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center bg-primary/10 text-base sm:text-lg font-black text-primary dark:bg-blue-500/20 dark:text-blue-300"
                            >
                                {{ getInitials(props.pendaftar.nama) }}
                            </div>
                        </div>

                        <!-- Candidate Info -->
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h4
                                    class="text-base sm:text-lg font-bold text-gray-900 leading-snug dark:text-slate-100"
                                >
                                    {{ props.pendaftar.nama }}
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
                                No. Reg: <strong class="text-gray-900 dark:text-slate-200">{{ props.pendaftar.nomor_pendaftaran || '-' }}</strong> &bull; NIK: <strong class="text-gray-900 dark:text-slate-200">{{ props.pendaftar.nik || '-' }}</strong>
                            </p>
                        </div>
                    </div>

                    <!-- Right Badges: Cabang & Jenjang -->
                    <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                        <!-- Cabang Badge -->
                        <span
                            v-if="props.pendaftar.cabang?.name || props.pendaftar.personal_data?.cabang_pendaftaran"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            {{ props.pendaftar.cabang?.name || props.pendaftar.personal_data?.cabang_pendaftaran }}
                        </span>

                        <!-- Jenjang Badge (With Logo) -->
                        <span
                            v-if="props.pendaftar.jenjang?.name || props.pendaftar.education_data?.jenjang"
                            class="inline-flex items-center gap-2 rounded-xl border border-primary/20 bg-primary/5 px-3 py-1.5 text-xs font-bold text-primary dark:border-blue-500/30 dark:bg-blue-950/40 dark:text-blue-300"
                        >
                            <img
                                :src="getJenjangLogo(props.pendaftar.jenjang?.code || props.pendaftar.education_data?.jenjang)"
                                class="h-4 w-4 object-contain"
                            />
                            {{ props.pendaftar.jenjang?.name || props.pendaftar.education_data?.jenjang }}
                        </span>

                        <span
                            class="rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 shadow-2xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >
                            {{ props.pendaftar.tipe_pendaftaran || props.pendaftar.education_data?.tipe_pendaftaran || 'Reguler' }}
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
                            v-for="st in getSubmitProgress(props.pendaftar).steps"
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
                            <span class="text-xs font-bold leading-tight whitespace-nowrap sm:whitespace-normal">{{ st.name }}</span>
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

                <!-- TAB CONTENT WITH PER-SECTION REVISION TEXTAREA -->
                <div class="space-y-4">
                    <!-- TAB 1: DATA PERSONAL -->
                    <div v-show="activeDetailStep === 1" class="space-y-4">
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

                        <div class="rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
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
                                    <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.nama || props.pendaftar.personal_data?.nama || '-' }}</p>
                                </div>
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">NIK (Nomor Induk Kependudukan)</p>
                                    <p class="font-mono text-xs sm:text-sm font-bold text-primary dark:text-blue-400">{{ props.pendaftar.nik || props.pendaftar.personal_data?.nik || '-' }}</p>
                                </div>
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">No. Kartu Keluarga (KK)</p>
                                    <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.personal_data?.no_kk || '-' }}</p>
                                </div>
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Jenis Kelamin</p>
                                    <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.personal_data?.jenis_kelamin || props.pendaftar.gender || '-' }}</p>
                                </div>
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Tempat, Tanggal Lahir</p>
                                    <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">
                                        {{ props.pendaftar.personal_data?.tempat_lahir || '-' }},
                                        {{ formatDateOnly(props.pendaftar.personal_data?.tanggal_lahir) }}
                                    </p>
                                </div>
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Ukuran Baju Seragam</p>
                                    <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.personal_data?.ukuran_baju || '-' }}</p>
                                </div>
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">No. WhatsApp / HP</p>
                                    <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.nomor_hp || props.pendaftar.personal_data?.nomor_hp || '-' }}</p>
                                </div>
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Email</p>
                                    <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.email || props.pendaftar.personal_data?.email || '-' }}</p>
                                </div>
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 transition-colors hover:bg-indigo-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Cabang Pendaftaran</p>
                                    <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.cabang?.name || props.pendaftar.personal_data?.cabang_pendaftaran || '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: DATA ORANG TUA & WALI -->
                    <div v-show="activeDetailStep === 2" class="space-y-4">
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
                                            (props.pendaftar.parent_data?.ayah?.status || props.pendaftar.parent_data?.status_ayah || 'Hidup').toLowerCase().includes('meninggal')
                                                ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                                                : 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-900/50'
                                        ]"
                                    >
                                        {{ props.pendaftar.parent_data?.ayah?.status || props.pendaftar.parent_data?.status_ayah || 'Masih Hidup' }}
                                    </span>
                                </div>

                                <div class="space-y-3">
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-blue-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Nama Ayah</p>
                                        <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.parent_data?.ayah?.nama || props.pendaftar.parent_data?.nama_ayah || '-' }}</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-blue-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                            <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">NIK Ayah</p>
                                            <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.parent_data?.ayah?.nik || props.pendaftar.parent_data?.nik_ayah || '-' }}</p>
                                        </div>
                                        <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-blue-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                            <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">No. HP Ayah</p>
                                            <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.parent_data?.ayah?.nomor_hp || props.pendaftar.parent_data?.nomor_hp_ayah || '-' }}</p>
                                        </div>
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
                                            (props.pendaftar.parent_data?.ibu?.status || props.pendaftar.parent_data?.status_ibu || 'Hidup').toLowerCase().includes('meninggal')
                                                ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                                                : 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-900/50'
                                        ]"
                                    >
                                        {{ props.pendaftar.parent_data?.ibu?.status || props.pendaftar.parent_data?.status_ibu || 'Masih Hidup' }}
                                    </span>
                                </div>

                                <div class="space-y-3">
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-rose-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Nama Ibu</p>
                                        <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.parent_data?.ibu?.nama || props.pendaftar.parent_data?.nama_ibu || '-' }}</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-rose-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                            <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">NIK Ibu</p>
                                            <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.parent_data?.ibu?.nik || props.pendaftar.parent_data?.nik_ibu || '-' }}</p>
                                        </div>
                                        <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 transition-colors hover:bg-rose-50/20 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800">
                                            <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">No. HP Ibu</p>
                                            <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.parent_data?.ibu?.nomor_hp || props.pendaftar.parent_data?.nomor_hp_ibu || '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: DATA ALAMAT & DOMISILI -->
                    <div v-show="activeDetailStep === 3" class="space-y-4">
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

                        <div
                            class="rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900"
                        >
                            <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-3">
                                <div class="sm:col-span-2 lg:col-span-3 rounded-2xl border border-gray-100/90 bg-gray-50/70 p-4 dark:border-slate-800 dark:bg-slate-800/40">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Alamat Lengkap</p>
                                    <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.address_data?.alamat_lengkap || props.pendaftar.address_data?.alamat || '-' }}</p>
                                </div>
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 dark:border-slate-800 dark:bg-slate-800/40">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">RT / RW</p>
                                    <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">RT {{ props.pendaftar.address_data?.rt || '-' }} / RW {{ props.pendaftar.address_data?.rw || '-' }}</p>
                                </div>
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 dark:border-slate-800 dark:bg-slate-800/40">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Kelurahan / Desa</p>
                                    <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.address_data?.kelurahan_desa || '-' }}</p>
                                </div>
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 dark:border-slate-800 dark:bg-slate-800/40">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Kecamatan</p>
                                    <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.address_data?.kecamatan || '-' }}</p>
                                </div>
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 dark:border-slate-800 dark:bg-slate-800/40">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Kabupaten / Kota</p>
                                    <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.address_data?.kabupaten_kota || '-' }}</p>
                                </div>
                                <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 sm:p-4 dark:border-slate-800 dark:bg-slate-800/40">
                                    <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Provinsi</p>
                                    <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.address_data?.provinsi || '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: PILIHAN PENDIDIKAN -->
                    <div v-show="activeDetailStep === 4" class="space-y-4">
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

                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <!-- Pilihan Program Tujuan -->
                            <div class="rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-3 rounded-2xl border border-primary/20 bg-primary/5 p-4 dark:border-blue-500/30 dark:bg-blue-950/30">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white p-1.5 shadow-2xs dark:bg-slate-800">
                                            <img
                                                :src="getJenjangLogo(props.pendaftar.jenjang?.code || props.pendaftar.education_data?.jenjang)"
                                                class="h-full w-full object-contain"
                                            />
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold tracking-wider text-primary uppercase dark:text-blue-400">Jenjang Pendidikan Tujuan</p>
                                            <h5 class="text-base font-extrabold text-gray-900 dark:text-slate-100">{{ props.pendaftar.jenjang?.name || props.pendaftar.education_data?.jenjang }}</h5>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                            <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Jalur Pendaftaran</p>
                                            <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.tipe_pendaftaran || props.pendaftar.education_data?.tipe_pendaftaran || 'Reguler' }}</p>
                                        </div>
                                        <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                            <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Kelas / Program</p>
                                            <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ getEducationSubText(props.pendaftar) || '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Riwayat Sekolah Sebelumnya -->
                            <div class="rounded-2xl sm:rounded-3xl border border-gray-100/90 bg-white p-5 sm:p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
                                <div class="space-y-3">
                                    <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                        <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">Nama Sekolah Asal</p>
                                        <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.education_data?.pendidikan_sebelumnya?.nama_sekolah || props.pendaftar.education_data?.asal_sekolah || '-' }}</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                            <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">NISN</p>
                                            <p class="font-mono text-xs sm:text-sm font-bold text-primary dark:text-blue-400">{{ props.pendaftar.education_data?.pendidikan_sebelumnya?.nisn || props.pendaftar.education_data?.nisn || '-' }}</p>
                                        </div>
                                        <div class="rounded-2xl border border-gray-100/90 bg-gray-50/70 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                                            <p class="mb-1 text-[10px] sm:text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">NPSN</p>
                                            <p class="font-mono text-xs sm:text-sm font-bold text-gray-900 dark:text-slate-100">{{ props.pendaftar.education_data?.pendidikan_sebelumnya?.npsn || props.pendaftar.education_data?.npsn || '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 5: DOKUMEN PERSYARATAN -->
                    <div v-show="activeDetailStep === 5" class="space-y-4">
                        <div
                            class="flex items-center justify-between rounded-2xl border p-4 shadow-2xs"
                            :class="[
                                getApplicantDocumentSummary(props.pendaftar).isComplete
                                    ? 'border-emerald-200 bg-emerald-50/90 text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300'
                                    : 'border-amber-200 bg-amber-50/90 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/60 dark:text-amber-300',
                            ]"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl font-bold shadow-2xs text-white"
                                    :class="getApplicantDocumentSummary(props.pendaftar).isComplete ? 'bg-emerald-500' : 'bg-amber-500'"
                                >
                                    {{ getApplicantDocumentSummary(props.pendaftar).isComplete ? '✓' : '!' }}
                                </span>
                                <div>
                                    <h5 class="text-xs sm:text-sm font-bold">
                                        {{
                                            getApplicantDocumentSummary(props.pendaftar).isComplete
                                                ? 'Dokumen Persyaratan Wajib Lengkap'
                                                : 'Dokumen Persyaratan Wajib Belum Lengkap'
                                        }}
                                    </h5>
                                    <p class="text-xs opacity-80">
                                        Disesuaikan untuk jenjang {{ props.pendaftar.jenjang?.name || '-' }} (Jalur {{ props.pendaftar.tipe_pendaftaran || 'Reguler' }}).
                                    </p>
                                </div>
                            </div>
                            <span class="font-mono text-xs font-bold shrink-0">
                                {{ getApplicantDocumentSummary(props.pendaftar).uploadedRequiredCount }}/{{ getApplicantDocumentSummary(props.pendaftar).requiredCount }} Wajib
                            </span>
                        </div>

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
                                    <!-- Top Bar -->
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

                                    <!-- Visual Preview Container -->
                                    <div class="relative h-32 w-full overflow-hidden rounded-xl border border-gray-100 bg-gray-50 dark:border-slate-800 dark:bg-slate-800/60 flex items-center justify-center">
                                        <template v-if="getUploadedDocumentRecord(doc.id)">
                                            <template v-if="isImageFile(getDocumentFileUrl(getUploadedDocumentRecord(doc.id)))">
                                                <img
                                                    :src="getDocumentFileUrl(getUploadedDocumentRecord(doc.id))!"
                                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                                    :alt="doc.name"
                                                />
                                            </template>
                                            <template v-else-if="isPdfFile(getDocumentFileUrl(getUploadedDocumentRecord(doc.id)))">
                                                <div class="relative h-full w-full bg-slate-100 dark:bg-slate-950 flex flex-col items-center justify-center p-2">
                                                    <svg class="h-8 w-8 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                    <span class="mt-1 text-[10px] font-black uppercase text-rose-600">Dokumen PDF</span>
                                                </div>
                                            </template>
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

                                    <!-- Card Footer & Note Input -->
                                    <div class="mt-2.5 space-y-1">
                                        <div class="flex items-center justify-between text-[10.5px] text-slate-500 dark:text-slate-400">
                                            <span class="uppercase font-bold">{{ doc.type === 'gambar' ? 'Gambar' : 'PDF' }}</span>
                                            <span v-if="getUploadedDocumentRecord(doc.id)">{{ formatDateTime(getUploadedDocumentRecord(doc.id)?.created_at) }}</span>
                                            <span v-else class="text-rose-500 font-bold">Kosong</span>
                                        </div>
                                    </div>

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
    </div>
</template>
