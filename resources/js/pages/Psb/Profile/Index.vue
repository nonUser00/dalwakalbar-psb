<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PasswordInput from '@/Components/Form/PasswordInput.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PsbLayout from '@/Layouts/PsbLayout.vue';
import { getBankLogo } from '@/lib/bank';
import { getPendaftarStatusBadge } from '@/types/enums';

defineOptions({ layout: PsbLayout });

const props = defineProps<{
    pendaftar: any;
}>();

// Tab state
const activeTab = ref<'personal' | 'parent' | 'education' | 'document'>('personal');

const tabs = [
    { key: 'personal', name: 'Data Diri & Domisili', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
    { key: 'parent', name: 'Orang Tua & Wali', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    { key: 'education', name: 'Jenjang & Pendidikan', icon: 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z' },
    { key: 'document', name: 'Dokumen Persyaratan', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
];

const jenjangCode = computed(() => {
    return (
        props.pendaftar.jenjang?.code ||
        props.pendaftar.education_data?.jenjang ||
        ''
    ).toUpperCase();
});

const isHigherEducation = computed(() => {
    return ['S1', 'S2', 'S3', 'PERGURUAN TINGGI'].includes(jenjangCode.value);
});

const tipePendaftaran = computed(() => {
    return (
        props.pendaftar.tipe_pendaftaran ||
        props.pendaftar.education_data?.tipe_pendaftaran ||
        'Reguler'
    );
});

const isPindahan = computed(() => {
    return String(tipePendaftaran.value).toLowerCase().includes('pindahan');
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

// Virtual Account helpers
const activeVirtualAccounts = computed(() => {
    const vas = props.pendaftar?.virtualAccounts || props.pendaftar?.virtual_accounts || [];

    return vas.filter((va: any) => {
        if (!va) {
            return false;
        }

        if (
            va.is_active === false ||
            va.is_active === 0 ||
            va.status === 'INACTIVE' ||
            va.status === 'TIDAK_AKTIF'
        ) {
            return false;
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

// Change Password Modal
const isPasswordModalOpen = ref(false);
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const openPasswordModal = () => {
    passwordForm.reset();
    passwordForm.clearErrors();
    isPasswordModalOpen.value = true;
};

const closePasswordModal = () => {
    isPasswordModalOpen.value = false;
    passwordForm.reset();
    passwordForm.clearErrors();
};

const submitChangePassword = () => {
    passwordForm.put('/psb/profile/password', {
        preserveScroll: true,
        onSuccess: () => {
            closePasswordModal();
        },
    });
};

const openCetakKartu = () => {
    window.open('/psb/cetak-kartu', '_blank');
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
</script>

<template>
    <div class="w-full space-y-6">
        <Head :title="`Profil Saya - ${props.pendaftar.nama}`" />

        <!-- Top Header matching Admin Show -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100">
                    Profil Calon Santri
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Informasi lengkap biodata, riwayat pendidikan, berkas dokumen, dan akun pendaftar Anda.
                </p>
            </div>
        </div>

        <div class="space-y-6">
            <!-- Hero Poster Background Card matching Admin Show -->
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

                    <!-- Action buttons on top of hero banner -->
                    <div
                        class="absolute top-4 right-4 left-4 z-20 flex items-center justify-end gap-2.5 px-4 py-2 md:top-6 md:right-8 md:left-8"
                    >
                        <!-- Cetak Kartu Registrasi -->
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

                        <!-- Ubah Password Modal -->
                        <button
                            type="button"
                            @click="openPasswordModal"
                            class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full border border-white/30 bg-white/20 text-white shadow-md backdrop-blur-md transition-all duration-200 hover:scale-105 hover:border-amber-400 hover:bg-amber-500 hover:text-white hover:shadow-amber-500/30 hover:shadow-lg dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-amber-400 dark:hover:border-amber-400 dark:hover:bg-amber-500 dark:hover:text-white"
                            title="Ubah Kata Sandi"
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

                        <!-- Tipe Pendaftaran Badge -->
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border px-4 py-2 text-xs font-bold"
                            :class="
                                isPindahan
                                    ? 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/60 dark:text-amber-300'
                                    : 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300'
                            "
                        >
                            Jalur {{ tipePendaftaran }}
                        </span>

                        <!-- Jenjang Badge (With Logo) -->
                        <span
                            v-if="props.pendaftar.jenjang || jenjangCode"
                            class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 text-xs font-bold text-indigo-700 dark:border-indigo-900/50 dark:bg-indigo-950/60 dark:text-indigo-300"
                        >
                            <img
                                :src="getJenjangLogo(props.pendaftar.jenjang || jenjangCode)"
                                class="h-4 w-4 object-contain"
                            />
                            {{ props.pendaftar.jenjang?.name || jenjangCode }}
                        </span>

                        <!-- Gelombang Badge -->
                        <span
                            v-if="props.pendaftar.gelombang"
                            class="inline-flex items-center gap-1.5 rounded-full border border-sky-200 bg-sky-50 px-4 py-2 text-xs font-bold text-sky-700 dark:border-sky-900/50 dark:bg-sky-950/60 dark:text-sky-300"
                        >
                            {{
                                props.pendaftar.gelombang?.name || 'Gelombang -'
                            }}
                        </span>

                        <!-- Periode Badge -->
                        <span
                            v-if="props.pendaftar.periode"
                            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-700 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-300"
                        >
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
                            {{ props.pendaftar.personal_data.jenis_kelamin }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- NAV TABS BAR (Clean Layout matching Admin Show with icon on top, title underneath) -->
            <div
                class="rounded-2xl border border-gray-200/90 bg-white p-2 sm:p-2.5 shadow-2xs dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                    <button
                        v-for="t in tabs"
                        :key="t.key"
                        type="button"
                        @click="activeTab = (t.key as any)"
                        :class="[
                            activeTab === t.key
                                ? 'bg-primary text-white shadow-sm ring-1 ring-primary-600 dark:bg-blue-600 dark:ring-blue-500'
                                : 'border border-gray-100 bg-gray-50/80 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-300 dark:hover:bg-slate-800',
                            'flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl p-2.5 sm:p-3 text-center transition-all duration-150',
                        ]"
                    >
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded-lg transition-colors"
                            :class="
                                activeTab === t.key
                                    ? 'bg-white/20 text-white'
                                    : 'bg-white text-gray-700 shadow-2xs dark:bg-slate-900 dark:text-slate-300'
                            "
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="t.icon" />
                            </svg>
                        </div>
                        <span class="text-xs font-bold leading-tight whitespace-nowrap sm:whitespace-normal">{{ t.name }}</span>
                    </button>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 1: DATA PRIBADI & ALAMAT (100% PERSIS ADMIN SHOW)     -->
            <!-- ========================================================= -->
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

            <!-- ========================================================= -->
            <!-- TAB 2: DATA ORANG TUA & WALI (100% PERSIS ADMIN SHOW)     -->
            <!-- ========================================================= -->
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
                                        class="flex h-12 w-12 items-center justify-center rounded-2xl border border-pink-100 bg-pink-50 text-pink-600 dark:border-pink-900/50 dark:bg-pink-950/50 dark:text-pink-400"
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

                <!-- Data Wali Santri (Optional / Conditional) -->
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
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
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
                                        Wali penanggung jawab jika berbeda dari orang tua
                                    </p>
                                </div>
                            </div>

                            <span
                                v-if="props.pendaftar.parent_data?.nama_wali || props.pendaftar.parent_data?.wali?.nama"
                                class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/60 dark:text-amber-300"
                            >
                                Wali Terdaftar
                            </span>
                            <span
                                v-else
                                class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400"
                            >
                                Tidak Ada Wali
                            </span>
                        </div>

                        <!-- If Guardian Exists -->
                        <div
                            v-if="props.pendaftar.parent_data?.nama_wali || props.pendaftar.parent_data?.wali?.nama"
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
                                    Hubungan / Status
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.pendaftar.parent_data?.hubungan_wali || props.pendaftar.parent_data?.wali?.hubungan || 'Wali Santri' }}
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

            <!-- ========================================================= -->
            <!-- TAB 3: JENJANG & PENDIDIKAN (100% PERSIS ADMIN SHOW)      -->
            <!-- ========================================================= -->
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
                                    {{
                                        (props.pendaftar.education_data?.tipe_sekolah_asal === 'Perguruan Tinggi' || props.pendaftar.education_data?.pendidikan_sebelumnya?.tipe === 'Perguruan Tinggi')
                                            ? 'Nomor Induk Mahasiswa (NIM / NPM)'
                                            : 'Nomor Induk Siswa Nasional (NISN)'
                                    }}
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
                                v-if="props.pendaftar.education_data?.fakultas_sebelumnya || props.pendaftar.education_data?.fakultas_asal || props.pendaftar.education_data?.pendidikan_sebelumnya?.fakultas"
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Fakultas Sebelumnya
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.pendaftar.education_data?.fakultas_sebelumnya || props.pendaftar.education_data?.fakultas_asal || props.pendaftar.education_data?.pendidikan_sebelumnya?.fakultas }}
                                </p>
                            </div>

                            <div
                                v-if="props.pendaftar.education_data?.prodi_sebelumnya || props.pendaftar.education_data?.prodi_asal || props.pendaftar.education_data?.jurusan_sekolah_asal || props.pendaftar.education_data?.pendidikan_sebelumnya?.prodi"
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    Program Studi Sebelumnya
                                </p>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-slate-100"
                                >
                                    {{ props.pendaftar.education_data?.prodi_sebelumnya || props.pendaftar.education_data?.prodi_asal || props.pendaftar.education_data?.jurusan_sekolah_asal || props.pendaftar.education_data?.pendidikan_sebelumnya?.prodi }}
                                </p>
                            </div>

                            <div
                                v-if="props.pendaftar.tipe_pendaftaran === 'Pindahan' || props.pendaftar.education_data?.tingkat_sebelumnya || props.pendaftar.education_data?.pendidikan_sebelumnya?.tingkat"
                                class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50"
                            >
                                <p
                                    class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                >
                                    {{
                                        (props.pendaftar.education_data?.tipe_sekolah_asal === 'Perguruan Tinggi' || props.pendaftar.education_data?.pendidikan_sebelumnya?.tipe === 'Perguruan Tinggi')
                                            ? 'Semester / Tingkat Terakhir Sebelumnya'
                                            : 'Tingkat / Kelas Terakhir Sebelumnya'
                                    }}
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
                                    {{
                                        (props.pendaftar.education_data?.tipe_sekolah_asal === 'Perguruan Tinggi' || props.pendaftar.education_data?.pendidikan_sebelumnya?.tipe === 'Perguruan Tinggi')
                                            ? 'NPSN / Kode Perguruan Tinggi'
                                            : (props.pendaftar.education_data?.tipe_sekolah_asal === 'Madrasah' || props.pendaftar.education_data?.nsm_sekolah_asal)
                                              ? 'NPSN / NSM Madrasah'
                                              : 'NPSN Sekolah'
                                    }}
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
                                v-if="props.pendaftar.tipe_pendaftaran === 'Reguler' || props.pendaftar.education_data?.no_ijazah"
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
                                v-if="props.pendaftar.tipe_pendaftaran === 'Reguler' || props.pendaftar.education_data?.tahun_lulus"
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

            <!-- ========================================================= -->
            <!-- TAB 4: DOKUMEN PERSYARATAN (100% PERSIS ADMIN SHOW)       -->
            <!-- ========================================================= -->
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
                                        Berkas persyaratan dan kelengkapan dokumen yang diunggah
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
                                            (dok.status || '').toLowerCase() === 'valid' || (dok.status || '').toLowerCase() === 'verified' || (dok.status || '').toLowerCase() === 'approved'
                                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300'
                                                : (dok.status || '').toLowerCase() === 'invalid' || (dok.status || '').toLowerCase() === 'rejected' || (dok.status || '').toLowerCase() === 'ditolak'
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
        </div>

        <!-- MODAL UBAH KATA SANDI -->
        <Modal :show="isPasswordModalOpen" @close="closePasswordModal" max-width="md">
            <div class="p-6 space-y-5">
                <div class="flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-slate-800">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-slate-100">
                            Ubah Kata Sandi Akun
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-slate-400">
                            Masukkan kata sandi lama dan tentukan kata sandi baru.
                        </p>
                    </div>
                </div>

                <div class="space-y-4">
                    <PasswordInput
                        v-model="passwordForm.current_password"
                        label="Kata Sandi Saat Ini"
                        required
                        :error="passwordForm.errors.current_password"
                    />

                    <PasswordInput
                        v-model="passwordForm.password"
                        label="Kata Sandi Baru"
                        required
                        :error="passwordForm.errors.password"
                    />

                    <PasswordInput
                        v-model="passwordForm.password_confirmation"
                        label="Konfirmasi Kata Sandi Baru"
                        required
                        :error="passwordForm.errors.password_confirmation"
                    />
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-slate-800">
                    <SecondaryButton type="button" @click="closePasswordModal">
                        Batal
                    </SecondaryButton>
                    <PrimaryButton
                        type="button"
                        @click="submitChangePassword"
                        :disabled="passwordForm.processing"
                    >
                        {{ passwordForm.processing ? 'Menyimpan...' : 'Perbarui Kata Sandi' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </div>
</template>