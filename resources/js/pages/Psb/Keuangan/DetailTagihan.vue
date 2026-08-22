<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import BackButton from '@/Components/BackButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ActionMenu from '@/Components/Form/ActionMenu.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PsbLayout from '@/Layouts/PsbLayout.vue';
import { getBankLogo, handleBankLogoError } from '@/lib/bank';

defineOptions({ layout: PsbLayout });

const props = defineProps<{
    pendaftar: any;
    tagihan: any;
    virtualAccounts?: any[];
    isRiwayat?: boolean;
}>();

const formatRupiah = (val: number | string | undefined | null) => {
    const num = Number(val) || 0;
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
};

const formatDate = (dateString?: string) => {
    if (!dateString) return '-';
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return dateString;
    return d.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const formatDateTime = (dateString?: string) => {
    if (!dateString) return '-';
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return dateString;
    return `${d.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    })} ${d.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    })}`;
};

// Cek overdue
const isOverdue = computed(() => {
    if (props.tagihan.status === 'PAID' || props.tagihan.status === 'LUNAS' || props.tagihan.status === 'SAMAHA') {
        return false;
    }
    if (!props.tagihan.due_date) return false;
    const due = new Date(props.tagihan.due_date);
    const now = new Date();
    return due < now;
});

// Perhitungan Total Bayar & Sisa Tagihan
const totalTagihan = computed(() => {
    return parseFloat(props.tagihan.total_amount || props.tagihan.amount || 0);
});

const totalPaid = computed(() => {
    return (props.tagihan.pembayarans || [])
        .filter((p: any) => p.status === 'APPROVED' || p.status === 'DITERIMA')
        .reduce((sum: number, p: any) => sum + parseFloat(p.amount || 0), 0);
});

const sisaTagihan = computed(() => {
    return Math.max(0, totalTagihan.value - totalPaid.value);
});

const isPhotoError = ref(false);

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
                d.file_path?.toLowerCase().includes('foto') ||
                d.file_path?.toLowerCase().includes('pas_foto'),
        )?.file_path ||
        null;

    if (!raw) return null;
    if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('data:image') || raw.startsWith('/storage/') || raw.startsWith('/')) {
        return raw;
    }
    if (raw.startsWith('storage/')) {
        return `/${raw}`;
    }
    return `/storage/${raw.replace(/^\/+/, '')}`;
};

const getVaNumber = (payment: any) => {
    if (!payment) return '-';
    if (payment.nomor_va) return payment.nomor_va;
    if (payment.va_number) return payment.va_number;
    const vas = props.virtualAccounts || props.pendaftar?.virtual_accounts || props.pendaftar?.virtualAccounts || [];
    if (payment.bank_id) {
        const found = vas.find((v: any) => v.bank_id === payment.bank_id || v.bank?.id === payment.bank_id);
        if (found) return found.nomor_va || found.va_number || '-';
    }
    return vas[0]?.nomor_va || vas[0]?.va_number || '-';
};

const getProofUrl = (payment: any) => {
    if (!payment) return null;
    if (payment.proof_path) {
        return payment.proof_path.startsWith('http')
            ? payment.proof_path
            : `/storage/${payment.proof_path}`;
    }
    if (payment.bukti_transfer_url) return payment.bukti_transfer_url;
    if (payment.bukti_transfer) return `/storage/${payment.bukti_transfer}`;
    return null;
};

const formatFileUrl = (path?: string) => {
    if (!path) return '';
    if (path.startsWith('http') || path.startsWith('/storage/')) return path;
    if (path.startsWith('storage/')) return `/${path}`;
    return `/storage/${path}`;
};

// ==========================================
// MODAL: DETAIL RINCIAN PEMBAYARAN
// ==========================================
const isDetailPaymentModalOpen = ref(false);
const detailTargetPayment = ref<any>(null);

const openDetailPaymentModal = (pembayaran: any) => {
    detailTargetPayment.value = pembayaran;
    isDetailPaymentModalOpen.value = true;
};

const closeDetailPaymentModal = () => {
    isDetailPaymentModalOpen.value = false;
    detailTargetPayment.value = null;
};

// ==========================================
// MODAL: PREVIEW BUKTI TRANSFER
// ==========================================
const isProofModalOpen = ref(false);
const proofUrl = ref('');

const openProofModal = (url: string) => {
    if (!url) return;
    proofUrl.value = url;
    isProofModalOpen.value = true;
};

// ==========================================
// MODAL: KONFIRMASI HAPUS PEMBAYARAN
// ==========================================
const isDeleteModalOpen = ref(false);
const paymentToDelete = ref<any>(null);
const isDeleting = ref(false);

const openDeletePaymentModal = (pembayaran: any) => {
    paymentToDelete.value = pembayaran;
    isDeleteModalOpen.value = true;
};

const closeDeletePaymentModal = () => {
    isDeleteModalOpen.value = false;
    paymentToDelete.value = null;
};

const confirmDeletePayment = () => {
    if (!paymentToDelete.value) return;

    isDeleting.value = true;
    router.delete(`/psb/keuangan/pembayaran/${paymentToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            closeDeletePaymentModal();
            closeDetailPaymentModal();
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};
</script>

<template>
    <div class="w-full space-y-6">
        <Head :title="`Detail Tagihan - ${props.tagihan.nomor_invoice}`" />

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    {{ props.isRiwayat ? 'Detail Riwayat Tagihan' : 'Detail Tagihan Pendaftar' }}
                </h1>
                <p class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    {{ props.isRiwayat ? 'Informasi arsip tagihan yang telah diselesaikan calon santri.' : 'Informasi lengkap tagihan, rincian komponen biaya, dan riwayat pembayaran calon santri.' }}
                </p>
            </div>
            <BackButton :href="props.isRiwayat ? '/psb/keuangan/riwayat' : '/psb/keuangan/tagihan'">Kembali</BackButton>
        </div>

        <!-- 2 Column Grid: Card Tagihan (2/3) & Card Informasi Pendaftar (1/3) -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left Card: Informasi Tagihan & Rincian Komponen Biaya (2/3 Ratio) -->
            <div class="flex flex-col justify-between rounded-3xl border border-gray-200 bg-white p-6 shadow-xs sm:p-8 lg:col-span-2 dark:border-slate-800 dark:bg-slate-900 space-y-6">
                <div class="space-y-6">
                    <!-- Header with Title & Status Badge -->
                    <div class="flex flex-col gap-4 border-b border-gray-100 pb-5 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                        <div class="flex items-center gap-3.5">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-primary/20 bg-primary/10 text-primary dark:bg-blue-950/50 dark:text-blue-400">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-extrabold tracking-tight text-gray-900 dark:text-slate-100">
                                    Informasi Tagihan & Rincian Biaya
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-slate-400">
                                    Data invoice tagihan resmi dan komponen rincian biaya pendaftaran
                                </p>
                            </div>
                        </div>

                        <!-- Status Badge Tagihan -->
                        <div>
                            <div
                                v-if="props.tagihan.status === 'LUNAS' || props.tagihan.status === 'PAID'"
                                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3.5 py-1 text-xs font-extrabold text-emerald-700 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300"
                            >
                                <svg class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Lunas
                            </div>
                            <div
                                v-else-if="props.tagihan.status === 'SAMAHA'"
                                class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-3.5 py-1 text-xs font-extrabold text-indigo-700 dark:border-indigo-800/60 dark:bg-indigo-950/40 dark:text-indigo-300"
                            >
                                Samaha (Keringanan)
                            </div>
                            <div
                                v-else-if="props.tagihan.status === 'BELUM_LUNAS' || totalPaid > 0"
                                class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3.5 py-1 text-xs font-extrabold text-amber-700 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-300"
                            >
                                <svg class="h-3.5 w-3.5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Belum Lunas
                            </div>
                            <div
                                v-else
                                class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-3.5 py-1 text-xs font-extrabold text-rose-700 dark:border-rose-800/60 dark:bg-rose-950/40 dark:text-rose-300"
                            >
                                <svg class="h-3.5 w-3.5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Belum Bayar
                            </div>
                        </div>
                    </div>

                    <!-- Data Grid (Invoice, Jenis Tagihan, Nama Tagihan, Tanggal Tagihan, Jatuh Tempo) -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <p class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">
                                INVOICE
                            </p>
                            <p class="font-mono text-base font-extrabold tracking-tight text-primary dark:text-blue-400">
                                {{ props.tagihan.nomor_invoice }}
                            </p>
                        </div>

                        <div>
                            <p class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">
                                JENIS TAGIHAN
                            </p>
                            <p class="text-sm font-bold text-gray-900 dark:text-slate-100">
                                {{ props.tagihan.kategori_biaya?.name || props.tagihan.items?.[0]?.name || 'Biaya Pendaftaran Santri Baru' }}
                            </p>
                        </div>

                        <div>
                            <p class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">
                                NAMA TAGIHAN
                            </p>
                            <p class="text-sm font-semibold text-gray-700 dark:text-slate-300">
                                {{ props.tagihan.nama_tagihan || 'Tagihan Biaya Pendaftaran Santri Baru' }}
                            </p>
                        </div>

                        <div>
                            <p class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">
                                TANGGAL TAGIHAN
                            </p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-slate-200">
                                {{ formatDate(props.tagihan.published_at || props.tagihan.created_at) }}
                            </p>
                        </div>

                        <div class="sm:col-span-2">
                            <p class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500">
                                JATUH TEMPO
                            </p>
                            <p
                                class="flex items-center gap-1.5 text-sm font-bold"
                                :class="isOverdue ? 'text-rose-600 dark:text-rose-400' : 'text-gray-800 dark:text-slate-200'"
                            >
                                <span>{{ formatDate(props.tagihan.due_date) }}</span>
                                <span
                                    v-if="isOverdue"
                                    class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-extrabold text-rose-700 dark:bg-rose-950/60 dark:text-rose-300"
                                >
                                    <svg class="h-3.5 w-3.5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Lewat Jatuh Tempo
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Rincian Komponen Biaya Table dengan Total, Sudah Dibayar, dan Sisa Tagihan -->
                    <div class="border-t border-gray-100 pt-6 dark:border-slate-800 space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                Rincian Komponen Biaya
                            </h4>
                            <span class="text-[11px] text-slate-400 font-semibold">
                                {{ props.tagihan.items?.length || 0 }} Komponen
                            </span>
                        </div>

                        <div class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-slate-800">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-gray-100 bg-slate-50/80 text-slate-400 uppercase font-bold text-[10.5px] dark:border-slate-800 dark:bg-slate-800/50">
                                        <th class="py-3 px-4">Deskripsi Komponen Biaya</th>
                                        <th class="py-3 px-4 text-right">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                                    <tr v-for="item in (props.tagihan.items || [])" :key="item.id">
                                        <td class="py-3 px-4 text-slate-700 dark:text-slate-300 font-medium">
                                            {{ item.description || item.name || 'Biaya Registrasi' }}
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-slate-100">
                                            {{ formatRupiah(item.amount) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <!-- Baris 1: Total Tagihan Keseluruhan -->
                                    <tr class="border-t-2 border-gray-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-800/40">
                                        <td class="py-3 px-4 text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                                            Total Tagihan
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono text-sm font-black text-slate-900 dark:text-slate-100">
                                            {{ formatRupiah(totalTagihan) }}
                                        </td>
                                    </tr>
                                    <!-- Baris 2: Sudah Dibayar -->
                                    <tr class="border-t border-gray-100 dark:border-slate-800 bg-emerald-50/30 dark:bg-emerald-950/20">
                                        <td class="py-2.5 px-4 text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">
                                            Sudah Dibayar
                                        </td>
                                        <td class="py-2.5 px-4 text-right font-mono text-sm font-bold text-emerald-600 dark:text-emerald-300">
                                            {{ formatRupiah(totalPaid) }}
                                        </td>
                                    </tr>
                                    <!-- Baris 3: Sisa Tagihan -->
                                    <tr class="border-t border-gray-100 dark:border-slate-800 bg-amber-50/30 dark:bg-amber-950/20">
                                        <td class="py-3 px-4 text-xs font-black text-amber-800 dark:text-amber-300 uppercase tracking-wider">
                                            Sisa Tagihan yang Harus Dibayar
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono text-base font-black text-primary dark:text-blue-400">
                                            {{ formatRupiah(sisaTagihan) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Card: Informasi Pendaftar (1/3 Ratio) -->
            <div class="flex flex-col justify-between rounded-3xl border border-gray-200 bg-white p-6 text-center shadow-xs sm:p-8 lg:col-span-1 dark:border-slate-800 dark:bg-slate-900">
                <div class="w-full">
                    <!-- Card Title -->
                    <div class="mb-6 flex items-center justify-between border-b border-gray-100 pb-5 dark:border-slate-800">
                        <h3 class="text-xl font-extrabold tracking-tight text-gray-900 dark:text-slate-100">
                            Informasi Pendaftar
                        </h3>
                        <div class="flex h-7 w-7 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Profile Avatar -->
                    <div class="mx-auto mb-4 h-24 w-24 overflow-hidden rounded-full border-2 border-gray-100 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-800">
                        <img
                            v-if="getPendaftarPhoto(props.pendaftar) && !isPhotoError"
                            :src="getPendaftarPhoto(props.pendaftar)!"
                            @error="isPhotoError = true"
                            class="h-full w-full object-cover"
                            alt="Foto Profil"
                        />
                        <img
                            v-else
                            :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(props.pendaftar?.nama || 'CS')}&background=1e293b&color=fff&size=256`"
                            class="h-full w-full object-cover"
                            alt="Foto Profil"
                        />
                    </div>

                    <!-- Student Name -->
                    <h4 class="text-base font-bold tracking-tight text-gray-900 sm:text-lg dark:text-slate-100">
                        {{ props.pendaftar.nama }}
                    </h4>

                    <!-- Structured Details Rows -->
                    <div class="mt-6 space-y-4 border-t border-gray-100 pt-5 text-xs dark:border-slate-800 text-center">
                        <div>
                            <p class="text-[10px] font-bold tracking-widest text-gray-400 uppercase dark:text-slate-500">
                                NO. REGISTRASI
                            </p>
                            <p class="mt-0.5 font-mono text-sm font-bold text-gray-900 dark:text-slate-100">
                                {{ props.pendaftar.nomor_pendaftaran || '-' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-[10px] font-bold tracking-widest text-gray-400 uppercase dark:text-slate-500">
                                NIK SANTRI
                            </p>
                            <p class="mt-0.5 font-mono text-sm font-bold text-gray-900 dark:text-slate-100">
                                {{ props.pendaftar.nik || '-' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-[10px] font-bold tracking-widest text-gray-400 uppercase dark:text-slate-500">
                                JENJANG - CABANG
                            </p>
                            <p class="mt-0.5 text-xs font-bold text-gray-900 dark:text-slate-100">
                                {{ props.pendaftar.jenjang?.name || '-' }} - {{ props.pendaftar.cabang?.name || 'Kalimantan Barat' }}
                            </p>
                            <p class="mt-0.5 text-[11px] text-gray-400 dark:text-slate-500">
                                Gelombang {{ props.pendaftar.gelombang?.name || '1' }} &bull; Periode {{ props.pendaftar.periode?.name || '-' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Direct Action: Bayar Sekarang (Hanya jika belum lunas / bukan riwayat) -->
                <div v-if="!props.isRiwayat && props.tagihan.status !== 'PAID' && props.tagihan.status !== 'LUNAS' && props.tagihan.status !== 'SAMAHA'" class="mt-6 w-full border-t border-gray-100 pt-4 dark:border-slate-800">
                    <Link
                        :href="`/psb/keuangan/tagihan/${props.tagihan.id}/bayar?from=${encodeURIComponent(`/psb/keuangan/tagihan/${props.tagihan.id}`)}`"
                        class="flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-xs font-bold text-white shadow-md shadow-emerald-600/20 hover:bg-emerald-700 transition-all"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span>Bayar Tagihan Sekarang</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- FULL WIDTH BOTTOM CARD: RIWAYAT PEMBAYARAN                -->
        <!-- ========================================================= -->
        <div class="flex flex-col overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <!-- Card Header -->
            <div class="flex flex-col gap-3 border-b border-gray-100 p-6 sm:flex-row sm:items-center sm:justify-between sm:px-8 sm:py-6 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold tracking-tight text-gray-900 dark:text-slate-100">
                            Riwayat Pembayaran & Bukti Transfer
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-slate-400">
                            Daftar seluruh transaksi pembayaran yang diajukan untuk invoice ini
                        </p>
                    </div>
                </div>
            </div>

            <!-- Empty State Riwayat -->
            <div v-if="!props.tagihan.pembayarans || props.tagihan.pembayarans.length === 0" class="p-10 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-slate-800 dark:text-slate-500">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <h4 class="mt-4 text-base font-bold text-gray-800 dark:text-slate-200">
                    Belum Ada Riwayat Pembayaran
                </h4>
                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                    Belum ada transaksi pembayaran atau bukti transfer yang diunggah untuk tagihan ini.
                </p>
            </div>

            <!-- Table Riwayat Pembayaran (Layout Standar Admin Show) -->
            <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-xs sm:text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:border-slate-800 dark:bg-slate-800/80 dark:text-slate-400">
                            <th class="px-6 py-3.5">TGL. BAYAR</th>
                            <th class="px-6 py-3.5">NOMINAL</th>
                            <th class="px-6 py-3.5">METODE</th>
                            <th class="px-6 py-3.5">STATUS</th>
                            <th class="px-6 py-3.5 text-right">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-medium text-slate-700 dark:divide-slate-800 dark:text-slate-300">
                        <tr v-for="p in props.tagihan.pembayarans" :key="p.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/60">
                            <!-- TGL. BAYAR -->
                            <td class="px-6 py-4 font-medium whitespace-nowrap text-gray-900 dark:text-slate-100">
                                {{ formatDateTime(p.payment_date || p.created_at) }}
                            </td>

                            <!-- NOMINAL -->
                            <td class="px-6 py-4 font-mono text-sm font-extrabold whitespace-nowrap text-gray-900 sm:text-base dark:text-slate-100">
                                {{ formatRupiah(p.amount) }}
                            </td>

                            <!-- METODE -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    v-if="p.payment_method === 'TRANSFER' || p.payment_method === 'VA' || p.bank"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-primary/20 bg-primary/10 px-3 py-1.5 text-xs font-bold text-primary dark:border-blue-900/40 dark:bg-blue-950/50 dark:text-blue-400"
                                >
                                    <svg class="h-4 w-4 shrink-0 text-primary dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    {{ p.bank?.name || 'Virtual Account (VA)' }}
                                </span>
                                <span
                                    v-else-if="p.payment_method === 'SAMAHA'"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-purple-200/60 bg-purple-50 px-3 py-1.5 text-xs font-bold text-purple-700 dark:border-purple-800/60 dark:bg-purple-950/40 dark:text-purple-300"
                                >
                                    <svg class="h-4 w-4 shrink-0 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                    </svg>
                                    Samaha
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-sky-200/60 bg-sky-50 px-3 py-1.5 text-xs font-bold text-sky-700 dark:border-sky-800/60 dark:bg-sky-950/40 dark:text-sky-300"
                                >
                                    <svg class="h-4 w-4 shrink-0 text-sky-600 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    Pembayaran Tunai
                                </span>
                            </td>

                            <!-- STATUS -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    v-if="p.status === 'APPROVED' || p.status === 'DITERIMA'"
                                    class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-100/70 px-3.5 py-1 text-xs font-extrabold text-emerald-700 uppercase dark:border-emerald-800/60 dark:bg-emerald-950/50 dark:text-emerald-300"
                                >
                                    DITERIMA
                                </span>
                                <span
                                    v-else-if="p.status === 'MENUNGGU_VERIFIKASI' || p.status === 'PENDING'"
                                    class="inline-flex animate-pulse items-center rounded-full border border-amber-200 bg-amber-100/70 px-3.5 py-1 text-xs font-extrabold text-amber-800 uppercase dark:border-amber-800/60 dark:bg-amber-950/50 dark:text-amber-300"
                                >
                                    MENUNGGU VERIFIKASI
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center rounded-full border border-rose-200 bg-rose-100/70 px-3.5 py-1 text-xs font-extrabold text-rose-700 uppercase dark:border-rose-800/60 dark:bg-rose-950/50 dark:text-rose-300"
                                >
                                    DITOLAK
                                </span>
                            </td>

                            <!-- AKSI -->
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end">
                                    <button
                                        @click="openDetailPaymentModal(p)"
                                        type="button"
                                        title="Lihat Detail Pembayaran"
                                        class="cursor-pointer rounded-full border border-transparent p-2 text-gray-500 transition-colors hover:border-gray-200 hover:bg-gray-100 hover:text-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:bg-slate-700 dark:hover:text-blue-400"
                                    >
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- MODAL 1: PREVIEW BUKTI TRANSFER FULL-SCREEN / BESAR     -->
        <!-- ======================================================= -->
        <Modal
            :show="isProofModalOpen"
            @close="isProofModalOpen = false"
            maxWidth="3xl"
            title="Bukti Transfer Pembayaran"
            description="Tinjau berkas foto atau struk transfer resmi calon santri."
            zIndexClass="z-[130]"
        >
            <template #icon>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sky-600 sm:h-12 sm:w-12 dark:bg-sky-950/50 dark:text-sky-400">
                    <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </template>

            <div class="p-2 text-center">
                <img
                    :src="proofUrl"
                    alt="Bukti Transfer"
                    class="mx-auto max-h-[70vh] rounded-2xl border border-gray-100 object-contain shadow-xl dark:border-slate-800"
                />
            </div>

            <template #footer>
                <div class="flex w-full flex-col-reverse justify-end gap-2.5 sm:flex-row">
                    <SecondaryButton
                        @click="isProofModalOpen = false"
                        type="button"
                        class="w-full justify-center text-xs font-bold sm:w-auto sm:text-sm"
                    >
                        Tutup
                    </SecondaryButton>
                    <a
                        :href="proofUrl"
                        target="_blank"
                        download
                        class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-4 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-200 sm:w-auto sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Download Berkas</span>
                    </a>
                </div>
            </template>
        </Modal>

        <!-- ======================================================= -->
        <!-- MODAL 2: DETAIL RINCIAN PEMBAYARAN                      -->
        <!-- ======================================================= -->
        <Modal
            :show="isDetailPaymentModalOpen"
            @close="closeDetailPaymentModal"
            maxWidth="xl"
            title="Rincian Transaksi Pembayaran"
            description="Detail informasi pembayaran dan bukti transfer yang diajukan."
            zIndexClass="z-[110]"
        >
            <template #icon>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 sm:h-11 sm:w-11 dark:bg-indigo-950/50 dark:text-indigo-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </template>

            <div v-if="detailTargetPayment" class="space-y-3.5">
                <!-- Card 1 (TOP): Invoice Pembayaran -->
                <div class="rounded-2xl border border-primary/20 bg-primary/5 p-4 sm:p-5 dark:border-blue-900/40 dark:bg-slate-800/60">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] font-bold tracking-wider text-primary uppercase dark:text-blue-400">Invoice Pembayaran</span>
                        <span class="max-w-[220px] truncate text-xs font-bold text-slate-700 dark:text-slate-300">
                            {{ props.pendaftar?.nama || detailTargetPayment.pendaftar?.nama }}
                        </span>
                    </div>
                    <div class="mt-1 font-mono text-xl font-black break-all text-primary sm:text-2xl dark:text-blue-400">
                        {{ props.tagihan?.nomor_invoice || detailTargetPayment.tagihan?.nomor_invoice || '-' }}
                    </div>
                </div>

                <!-- Row 2: Jumlah & Tanggal (2 Columns Grid) -->
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-3.5">
                    <!-- Jumlah -->
                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3.5 sm:p-4 dark:border-slate-700/80 dark:bg-slate-800/60">
                        <span class="block text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">Jumlah</span>
                        <div class="mt-1 font-mono text-lg font-black text-slate-900 sm:text-xl dark:text-slate-100">
                            {{ formatRupiah(detailTargetPayment.amount) }}
                        </div>
                    </div>

                    <!-- Tanggal -->
                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3.5 sm:p-4 dark:border-slate-700/80 dark:bg-slate-800/60">
                        <span class="block text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">Tanggal</span>
                        <div class="mt-1 text-sm font-bold text-slate-900 sm:text-base dark:text-slate-100">
                            {{ formatDateTime(detailTargetPayment.payment_date || detailTargetPayment.created_at) }}
                        </div>
                    </div>
                </div>

                <!-- Card 3: Informasi Virtual Account (Jika metode VA/Transfer) -->
                <div
                    v-if="detailTargetPayment.payment_method === 'TRANSFER' || detailTargetPayment.payment_method === 'VA' || detailTargetPayment.bank"
                    class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4 sm:p-5 dark:border-slate-700/80 dark:bg-slate-800/50"
                >
                    <!-- Header with Bank SVG Icon -->
                    <div class="flex items-center gap-2 text-sm font-bold text-primary dark:text-blue-400">
                        <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span>Informasi Virtual Account</span>
                    </div>

                    <!-- Bank Row -->
                    <div class="mt-3 flex items-center gap-3.5">
                        <img
                            :src="getBankLogo(detailTargetPayment.bank)"
                            @error="handleBankLogoError($event)"
                            :alt="detailTargetPayment.bank?.name || 'Logo Bank'"
                            class="h-8 max-w-[110px] shrink-0 object-contain drop-shadow-xs"
                        />
                        <div class="min-w-0">
                            <span class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase dark:text-slate-500">Bank</span>
                            <div class="truncate text-sm font-bold text-slate-800 dark:text-slate-100">
                                {{ detailTargetPayment.bank?.name || props.pendaftar?.virtual_accounts?.[0]?.bank?.name || 'Bank Syariah Indonesia' }}
                            </div>
                        </div>
                    </div>

                    <!-- Nomor Virtual Account Display -->
                    <div class="mt-3 border-t border-slate-200/80 pt-3 dark:border-slate-700/80">
                        <span class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase dark:text-slate-500">Nomor Virtual Account</span>
                        <div class="mt-0.5 font-mono text-base font-black tracking-wider text-slate-900 sm:text-lg select-all dark:text-slate-100">
                            {{ getVaNumber(detailTargetPayment) }}
                        </div>
                    </div>
                </div>

                <!-- Row 4: Jenis Bayar & Status (2 Columns Grid) -->
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-3.5">
                    <!-- Jenis Bayar -->
                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3.5 sm:p-4 dark:border-slate-700/80 dark:bg-slate-800/60">
                        <span class="block text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">Jenis Bayar</span>
                        <div class="mt-1">
                            <span
                                v-if="detailTargetPayment.payment_method === 'TRANSFER' || detailTargetPayment.payment_method === 'VA' || detailTargetPayment.bank"
                                class="inline-flex items-center gap-1.5 rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-xs font-bold text-primary dark:border-blue-900/40 dark:bg-blue-950/50 dark:text-blue-400"
                            >
                                <svg class="h-4 w-4 shrink-0 text-primary dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                Virtual Account
                            </span>
                            <span
                                v-else-if="detailTargetPayment.payment_method === 'SAMAHA'"
                                class="inline-flex items-center gap-1.5 rounded-full border border-purple-200 bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700 dark:border-purple-800/50 dark:bg-purple-950/40 dark:text-purple-300"
                            >
                                <svg class="h-4 w-4 shrink-0 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                </svg>
                                Samaha
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 dark:border-sky-800/50 dark:bg-sky-950/40 dark:text-sky-300"
                            >
                                <svg class="h-4 w-4 shrink-0 text-sky-600 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Tunai (Cash)
                            </span>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3.5 sm:p-4 dark:border-slate-700/80 dark:bg-slate-800/60">
                        <span class="block text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">Status</span>
                        <div class="mt-1">
                            <span
                                v-if="detailTargetPayment.status === 'MENUNGGU_VERIFIKASI' || detailTargetPayment.status === 'PENDING'"
                                class="inline-flex items-center gap-1.5 rounded-full border border-amber-200/80 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 dark:border-amber-800/50 dark:bg-amber-950/40 dark:text-amber-300"
                            >
                                <span class="h-2 w-2 animate-pulse rounded-full bg-amber-500"></span>
                                Menunggu Verifikasi
                            </span>
                            <span
                                v-else-if="detailTargetPayment.status === 'APPROVED' || detailTargetPayment.status === 'DITERIMA'"
                                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/80 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-300"
                            >
                                <svg class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Diterima
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 rounded-full border border-rose-200/80 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 dark:border-rose-800/50 dark:bg-rose-950/40 dark:text-rose-300"
                            >
                                <svg class="h-3.5 w-3.5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Ditolak
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Catatan Admin (Jika Ada) -->
                <div
                    v-if="detailTargetPayment.catatan"
                    class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3.5 sm:p-4 dark:border-slate-700/80 dark:bg-slate-800/60"
                >
                    <span class="block text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">Catatan Admin</span>
                    <p class="mt-1 text-xs sm:text-sm text-slate-700 dark:text-slate-300">
                        {{ detailTargetPayment.catatan }}
                    </p>
                </div>

                <!-- Card: Bukti Transfer Preview -->
                <div
                    v-if="getProofUrl(detailTargetPayment)"
                    class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="mb-2.5 flex items-center justify-between border-b border-gray-100 pb-2.5 dark:border-slate-800">
                        <span class="flex items-center gap-1.5 text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">
                            <svg class="h-4 w-4 text-primary dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Bukti Transfer
                        </span>
                        <a
                            :href="getProofUrl(detailTargetPayment)!"
                            target="_blank"
                            download
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>Download</span>
                        </a>
                    </div>

                    <div
                        @click="openProofModal(getProofUrl(detailTargetPayment)!)"
                        class="group relative flex max-h-56 cursor-pointer items-center justify-center overflow-hidden rounded-xl border border-gray-100 bg-gray-50 p-2 transition-all hover:border-primary/40 hover:shadow-xs dark:border-slate-800 dark:bg-slate-950/40"
                    >
                        <img
                            :src="getProofUrl(detailTargetPayment)!"
                            alt="Bukti Transfer"
                            class="max-h-52 w-full rounded-lg object-contain transition-transform duration-200 group-hover:scale-[1.01]"
                        />
                        <div class="backdrop-blur-2xs absolute inset-0 flex items-center justify-center gap-1.5 rounded-lg bg-black/40 text-xs font-bold text-white opacity-0 transition-opacity group-hover:opacity-100">
                            <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <span>Klik untuk Perbesar</span>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex w-full flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2.5">
                    <!-- Left: Action Edit & Hapus (Hanya jika Menunggu Verifikasi) -->
                    <div
                        v-if="detailTargetPayment.status === 'MENUNGGU_VERIFIKASI' || detailTargetPayment.status === 'PENDING'"
                        class="flex items-center gap-2 w-full sm:w-auto"
                    >
                        <Link
                            :href="`/psb/keuangan/pembayaran/${detailTargetPayment.id}/edit?from=${encodeURIComponent(`/psb/keuangan/tagihan/${props.tagihan.id}`)}`"
                            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-primary/30 bg-primary/10 px-3 py-2.5 sm:px-4 text-xs sm:text-sm font-bold text-primary transition-all hover:bg-primary hover:text-white dark:border-blue-800 dark:bg-blue-950/50 dark:text-blue-300 dark:hover:bg-blue-600 dark:hover:text-white flex-1 sm:flex-none shadow-sm"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <span>Edit</span>
                        </Link>
                        <button
                            type="button"
                            @click="openDeletePaymentModal(detailTargetPayment)"
                            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 sm:px-4 text-xs sm:text-sm font-bold text-rose-700 transition-all hover:bg-rose-100 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-300 dark:hover:bg-rose-900/60 flex-1 sm:flex-none shadow-sm"
                        >
                            <svg class="h-4 w-4 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Hapus</span>
                        </button>
                    </div>
                    <div v-else></div>

                    <!-- Right: Close Button -->
                    <SecondaryButton
                        @click="closeDetailPaymentModal"
                        type="button"
                        class="w-full justify-center text-xs font-bold sm:w-auto sm:text-sm"
                    >
                        Tutup
                    </SecondaryButton>
                </div>
            </template>
        </Modal>

        <!-- ======================================================= -->
        <!-- MODAL 3: KONFIRMASI HAPUS PEMBAYARAN                    -->
        <!-- ======================================================= -->
        <Modal
            :show="isDeleteModalOpen"
            @close="closeDeletePaymentModal"
            maxWidth="md"
            title="Batalkan Pembayaran"
            description="Konfirmasi pembatalan dan penghapusan transaksi pembayaran."
            zIndexClass="z-[130]"
        >
            <template #icon>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600 sm:h-12 sm:w-12 dark:bg-rose-950/50 dark:text-rose-400">
                    <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
            </template>

            <div class="space-y-3 p-1">
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300">
                    Apakah Anda yakin ingin membatalkan dan menghapus data pembayaran sebesar
                    <strong class="font-mono text-slate-900 dark:text-slate-100">{{ formatRupiah(paymentToDelete?.amount) }}</strong> ini?
                </p>
                <p class="text-[11px] text-rose-600 dark:text-rose-400">
                    * Berkas bukti transfer yang telah diunggah akan dihapus. Anda dapat mengajukan pembayaran baru setelahnya.
                </p>
            </div>

            <template #footer>
                <div class="flex w-full flex-col-reverse sm:flex-row justify-end gap-2.5">
                    <SecondaryButton
                        @click="closeDeletePaymentModal"
                        type="button"
                        class="w-full justify-center text-xs font-bold sm:w-auto"
                        :disabled="isDeleting"
                    >
                        Batal
                    </SecondaryButton>
                    <DangerButton
                        @click="confirmDeletePayment"
                        type="button"
                        class="w-full justify-center text-xs font-bold sm:w-auto"
                        :disabled="isDeleting"
                    >
                        <span v-if="isDeleting">Menghapus...</span>
                        <span v-else>Ya, Hapus Pembayaran</span>
                    </DangerButton>
                </div>
            </template>
        </Modal>
    </div>
</template>
