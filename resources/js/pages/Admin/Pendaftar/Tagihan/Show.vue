<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import BackButton from '@/Components/BackButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ActionMenu from '@/Components/Form/ActionMenu.vue';
import CurrencyInput from '@/Components/Form/CurrencyInput.vue';
import TextareaInput from '@/Components/Form/TextareaInput.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { getBankLogo, handleBankLogoError } from '@/lib/bank';
import {
    verify_payment,
    delete_payment,
    bulk_delete_payments,
} from '@/routes/admin/pendaftar/tagihan';

defineOptions({ layout: AdminLayout });

const props = defineProps<{
    pendaftar: any;
    tagihan?: any;
    banks: any[];
}>();

// Format Utilities
const formatRupiah = (amount?: number | string) => {
    const num = parseFloat(String(amount || 0));

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
};

const formatDate = (dateStr?: string) => {
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

        if (isNaN(d.getTime())) {
return dateStr;
}

        return `${d.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        })} ${d.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
        })}`;
    } catch {
        return dateStr;
    }
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
                d.dokumen?.name?.toLowerCase().includes('foto') ||
                d.file_path?.toLowerCase().includes('foto') ||
                d.file_path?.toLowerCase().includes('pas_foto'),
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

// Computeds
const totalPaid = computed(() => {
    if (!props.tagihan || !props.tagihan.pembayarans) {
return 0;
}

    return props.tagihan.pembayarans
        .filter((p: any) => p.status === 'DITERIMA')
        .reduce((sum: number, p: any) => sum + parseFloat(p.amount || 0), 0);
});

const remainingBalance = computed(() => {
    if (!props.tagihan) {
return 0;
}

    return Math.max(
        0,
        parseFloat(props.tagihan.total_amount || 0) - totalPaid.value,
    );
});

const isOverdue = computed(() => {
    if (
        !props.tagihan ||
        !props.tagihan.due_date ||
        remainingBalance.value === 0
    ) {
return false;
}

    const due = new Date(props.tagihan.due_date);

    return due.getTime() < new Date().getTime();
});

const pendingPayment = computed(() => {
    if (!props.tagihan || !props.tagihan.pembayarans) {
return null;
}

    return props.tagihan.pembayarans.find(
        (p: any) => p.status === 'MENUNGGU_VERIFIKASI',
    );
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

    const jenjangId = props.pendaftar.jenjang_id;

    return jenjangId
        ? `/admin/pendaftar/tagihan?jenjang_id=${jenjangId}`
        : '/admin/pendaftar/tagihan';
});

// ==========================================
// MODAL: DETAIL & VERIFIKASI PEMBAYARAN
// ==========================================
const isDetailPaymentModalOpen = ref(false);
const detailTargetPayment = ref<any>(null);

const verifyPaymentForm = useForm({
    action: 'terima' as 'terima' | 'tolak',
    amount_verified: 0,
    catatan: '',
    alasan_penolakan: '',
});

const openDetailPaymentModal = (pembayaran: any) => {
    detailTargetPayment.value = pembayaran;
    verifyPaymentForm.reset();
    verifyPaymentForm.clearErrors();
    verifyPaymentForm.action = 'terima';
    verifyPaymentForm.amount_verified = parseFloat(pembayaran?.amount || 0);
    verifyPaymentForm.catatan =
        pembayaran?.status === 'DITERIMA' && pembayaran?.catatan
            ? pembayaran.catatan
            : 'Pembayaran pendaftaran telah diverifikasi dan diterima';
    verifyPaymentForm.alasan_penolakan =
        pembayaran?.status === 'DITOLAK' && pembayaran?.catatan
            ? pembayaran.catatan
            : '';
    isDetailPaymentModalOpen.value = true;
};

const closeDetailPaymentModal = () => {
    isDetailPaymentModalOpen.value = false;
    detailTargetPayment.value = null;
    verifyPaymentForm.reset();
    verifyPaymentForm.clearErrors();
};

const submitVerifyPayment = () => {
    if (!detailTargetPayment.value) {
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

        const targetTagihan = props.tagihan || detailTargetPayment.value.tagihan;

        if (targetTagihan) {
            const allPayments =
                targetTagihan.pembayarans ||
                props.pendaftar?.tagihans?.[0]?.pembayarans ||
                [];
            const otherPaid = allPayments
                .filter(
                    (p: any) =>
                        p.id !== detailTargetPayment.value.id &&
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

    verifyPaymentForm.post(verify_payment.url(detailTargetPayment.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeDetailPaymentModal();
        },
    });
};

// ==========================================
// MODAL: PREVIEW BUKTI TRANSFER
// ==========================================
const isProofModalOpen = ref(false);
const proofUrl = ref('');

const openProofModal = (url: string) => {
    if (!url) {
return;
}

    proofUrl.value = url;
    isProofModalOpen.value = true;
};

// ==========================================
// SELECTION & DELETION FOR PEMBAYARAN TABLE
// ==========================================
const selectedPaymentIds = ref<string[]>([]);
const isDeleteSinglePaymentModalOpen = ref(false);
const isDeleteBulkPaymentModalOpen = ref(false);
const paymentToDelete = ref<any>(null);

const deleteSinglePaymentForm = useForm({});
const deleteBulkPaymentForm = useForm<{ ids: string[] }>({
    ids: [],
});

const isDeletablePayment = (p: any) => Boolean(p && p.created_by);

const deletablePayments = computed(() => {
    if (!props.tagihan?.pembayarans) {
return [];
}

    return props.tagihan.pembayarans.filter((p: any) => isDeletablePayment(p));
});

const isAllPaymentsSelected = computed(() => {
    if (deletablePayments.value.length === 0) {
return false;
}

    return deletablePayments.value.every((p: any) =>
        selectedPaymentIds.value.includes(p.id),
    );
});

const toggleSelectAllPayments = () => {
    if (isAllPaymentsSelected.value) {
        selectedPaymentIds.value = [];
    } else {
        selectedPaymentIds.value = deletablePayments.value.map(
            (p: any) => p.id,
        );
    }
};

const openDeleteSinglePaymentModal = (pembayaran: any) => {
    if (!isDeletablePayment(pembayaran)) {
return;
}

    paymentToDelete.value = pembayaran;
    deleteSinglePaymentForm.clearErrors();
    isDeleteSinglePaymentModalOpen.value = true;
};

const closeDeleteSinglePaymentModal = () => {
    isDeleteSinglePaymentModalOpen.value = false;
    paymentToDelete.value = null;
    deleteSinglePaymentForm.reset();
    deleteSinglePaymentForm.clearErrors();
};

const submitDeleteSinglePayment = () => {
    if (!paymentToDelete.value) {
return;
}

    deleteSinglePaymentForm.delete(
        delete_payment.url(paymentToDelete.value.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteSinglePaymentModal();
                const idx = selectedPaymentIds.value.indexOf(
                    paymentToDelete.value.id,
                );

                if (idx > -1) {
selectedPaymentIds.value.splice(idx, 1);
}
            },
        },
    );
};

const openDeleteBulkPaymentModal = () => {
    if (selectedPaymentIds.value.length === 0) {
return;
}

    deleteBulkPaymentForm.ids = [...selectedPaymentIds.value];
    deleteBulkPaymentForm.clearErrors();
    isDeleteBulkPaymentModalOpen.value = true;
};

const closeDeleteBulkPaymentModal = () => {
    isDeleteBulkPaymentModalOpen.value = false;
    deleteBulkPaymentForm.reset();
    deleteBulkPaymentForm.clearErrors();
};

const submitDeleteBulkPayment = () => {
    deleteBulkPaymentForm.post(bulk_delete_payments.url(), {
        preserveScroll: true,
        onSuccess: () => {
            selectedPaymentIds.value = [];
            closeDeleteBulkPaymentModal();
        },
    });
};
</script>

<template>
    <div class="w-full space-y-6">
        <Head :title="`Detail Tagihan - ${props.pendaftar.nama}`" />

        <!-- Top Header (Strictly consistent typography & clean navigation) -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between print:hidden"
        >
            <div>
                <h1
                    class="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl dark:text-slate-100"
                >
                    Detail Tagihan Pendaftar
                </h1>
                <p
                    class="mt-1 text-xs text-gray-500 sm:text-sm dark:text-slate-400"
                >
                    Informasi tagihan, data pendaftar, dan riwayat pembayaran
                    santri.
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <BackButton :href="backUrl">Kembali</BackButton>
            </div>
        </div>

        <div class="space-y-6">
            <!-- Warning Banner if tagihan not created yet -->
            <div
                v-if="!props.tagihan"
                class="rounded-3xl border border-amber-200 bg-amber-50/80 p-8 text-center shadow-xs dark:border-amber-900/40 dark:bg-amber-950/30"
            >
                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 shadow-xs dark:bg-amber-900/50 dark:text-amber-300"
                >
                    <svg
                        class="h-8 w-8"
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
                <h3
                    class="mt-4 text-base font-bold text-amber-900 dark:text-amber-200"
                >
                    Tagihan Belum Diterbitkan
                </h3>
                <p
                    class="mx-auto mt-1 max-w-md text-xs text-amber-700 dark:text-amber-400"
                >
                    Pendaftar <strong>{{ props.pendaftar.nama }}</strong> belum
                    memiliki invoice tagihan pendaftaran.
                </p>
            </div>

            <template v-else>
                <!-- TOP ROW: 2 CARDS (Informasi Tagihan & Informasi Pendaftar - 2:1 Ratio) -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Left Card: Informasi Tagihan (2/3 Ratio) -->
                    <div
                        class="flex flex-col justify-between rounded-3xl border border-gray-200 bg-white p-6 shadow-xs sm:p-8 lg:col-span-2 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div>
                            <!-- Header Title & Status Badge -->
                            <div
                                class="mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-5 dark:border-slate-800"
                            >
                                <div class="flex items-center gap-3">
                                    <h3
                                        class="text-xl font-extrabold tracking-tight text-gray-900 dark:text-slate-100"
                                    >
                                        Informasi Tagihan
                                    </h3>

                                    <!-- Status Badge Pill -->
                                    <div
                                        v-if="props.tagihan.status === 'LUNAS'"
                                        class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3.5 py-1 text-xs font-extrabold text-emerald-700 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300"
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
                                        Lunas
                                    </div>
                                    <div
                                        v-else-if="
                                            props.tagihan.status === 'SAMAHA'
                                        "
                                        class="inline-flex items-center gap-1.5 rounded-full border border-purple-200 bg-purple-50 px-3.5 py-1 text-xs font-extrabold text-purple-700 dark:border-purple-800/60 dark:bg-purple-950/40 dark:text-purple-300"
                                    >
                                        <svg
                                            class="h-3.5 w-3.5 text-purple-600 dark:text-purple-400"
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
                                    </div>
                                    <div
                                        v-else-if="
                                            props.tagihan.status ===
                                            'BELUM_LUNAS'
                                        "
                                        class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3.5 py-1 text-xs font-extrabold text-amber-700 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-300"
                                    >
                                        <svg
                                            class="h-3.5 w-3.5 text-amber-600 dark:text-amber-400"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        Belum Lunas
                                    </div>
                                    <div
                                        v-else
                                        class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-3.5 py-1 text-xs font-extrabold text-rose-700 dark:border-rose-800/60 dark:bg-rose-950/40 dark:text-rose-300"
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
                                        Belum Bayar
                                    </div>
                                </div>
                            </div>

                            <!-- Data Grid (Invoice, Jenis Tagihan, Nama Tagihan, Tanggal Tagihan, Jatuh Tempo) -->
                            <div
                                class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2"
                            >
                                <div>
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        INVOICE
                                    </p>
                                    <p
                                        class="font-mono text-base font-extrabold tracking-tight text-primary dark:text-blue-400"
                                    >
                                        {{ props.tagihan.nomor_invoice }}
                                    </p>
                                </div>

                                <div>
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        JENIS TAGIHAN
                                    </p>
                                    <p
                                        class="text-sm font-bold text-gray-900 dark:text-slate-100"
                                    >
                                        {{
                                            props.tagihan.items?.[0]?.name ||
                                            'Biaya Pendaftaran Santri Baru'
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        NAMA TAGIHAN
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-700 dark:text-slate-300"
                                    >
                                        {{
                                            props.tagihan.nama_tagihan ||
                                            'Tagihan Biaya Pendaftaran Santri Baru'
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        TANGGAL TAGIHAN
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-gray-800 dark:text-slate-200"
                                    >
                                        {{
                                            formatDate(
                                                props.tagihan.published_at ||
                                                    props.tagihan.created_at,
                                            )
                                        }}
                                    </p>
                                </div>

                                <div class="sm:col-span-2">
                                    <p
                                        class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        JATUH TEMPO
                                    </p>
                                    <p
                                        class="flex items-center gap-1.5 text-sm font-bold"
                                        :class="
                                            isOverdue
                                                ? 'text-rose-600 dark:text-rose-400'
                                                : 'text-gray-800 dark:text-slate-200'
                                        "
                                    >
                                        <span>{{
                                            formatDate(props.tagihan.due_date)
                                        }}</span>
                                        <span
                                            v-if="isOverdue"
                                            class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-extrabold text-rose-700 dark:bg-rose-950/60 dark:text-rose-300"
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
                                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                            Lewat Jatuh Tempo
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom 3 Summary Nominal Pill Boxes (Matching Index Reference) -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <!-- Nominal Tagihan -->
                            <div
                                class="rounded-2xl border border-gray-100 bg-slate-50/80 p-4 transition-all duration-200 hover:border-gray-200 dark:border-slate-800 dark:bg-slate-800/60 dark:hover:border-slate-700"
                            >
                                <p
                                    class="mb-1 text-xs font-bold text-gray-500 dark:text-slate-400"
                                >
                                    Nominal Tagihan
                                </p>
                                <p
                                    class="font-mono text-lg font-black text-gray-900 sm:text-xl dark:text-slate-100"
                                >
                                    {{
                                        formatRupiah(props.tagihan.total_amount)
                                    }}
                                </p>
                            </div>

                            <!-- Sudah Dibayar -->
                            <div
                                class="rounded-2xl border border-emerald-200/60 bg-emerald-50/70 p-4 transition-all duration-200 hover:border-emerald-300 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:hover:border-emerald-800"
                            >
                                <p
                                    class="mb-1 text-xs font-bold text-emerald-700 dark:text-emerald-400"
                                >
                                    Sudah Dibayar
                                </p>
                                <p
                                    class="font-mono text-lg font-black text-emerald-600 sm:text-xl dark:text-emerald-300"
                                >
                                    {{ formatRupiah(totalPaid) }}
                                </p>
                            </div>

                            <!-- Sisa Tagihan -->
                            <div
                                class="rounded-2xl border border-amber-200/60 bg-amber-50/70 p-4 transition-all duration-200 hover:border-amber-300 dark:border-amber-900/50 dark:bg-amber-950/40 dark:hover:border-amber-800"
                            >
                                <p
                                    class="mb-1 text-xs font-bold text-amber-700 dark:text-amber-400"
                                >
                                    Sisa Tagihan
                                </p>
                                <p
                                    class="font-mono text-lg font-black text-amber-600 sm:text-xl dark:text-amber-300"
                                >
                                    {{ formatRupiah(remainingBalance) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Card: Informasi Pendaftar (1/3 Ratio) -->
                    <div
                        class="flex flex-col justify-between rounded-3xl border border-gray-200 bg-white p-6 text-center shadow-xs sm:p-8 lg:col-span-1 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="w-full">
                            <!-- Card Title -->
                            <div
                                class="mb-6 flex items-center justify-between border-b border-gray-100 pb-5 dark:border-slate-800"
                            >
                                <h3
                                    class="text-xl font-extrabold tracking-tight text-gray-900 dark:text-slate-100"
                                >
                                    Informasi Pendaftar
                                </h3>
                                <div
                                    class="flex h-7 w-7 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400"
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
                                </div>
                            </div>

                            <!-- Profile Avatar -->
                            <div
                                class="mx-auto mb-4 h-24 w-24 overflow-hidden rounded-full border-2 border-gray-100 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-800"
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
                                    :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(props.pendaftar?.nama || 'CS')}&background=1e293b&color=fff&size=256`"
                                    class="h-full w-full object-cover"
                                    alt="Foto Profil"
                                />
                            </div>

                            <!-- Student Name -->
                            <h4
                                class="text-base font-bold tracking-tight text-gray-900 sm:text-lg dark:text-slate-100"
                            >
                                {{ props.pendaftar.nama }}
                            </h4>

                            <!-- Structured Details Rows -->
                            <div
                                class="mt-6 space-y-4 border-t border-gray-100 pt-5 text-xs dark:border-slate-800"
                            >
                                <div>
                                    <p
                                        class="text-[10px] font-bold tracking-widest text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        NISL / NO. REGISTRASI
                                    </p>
                                    <p
                                        class="mt-0.5 font-mono text-sm font-bold text-gray-900 dark:text-slate-100"
                                    >
                                        {{
                                            props.pendaftar.nomor_pendaftaran ||
                                            '-'
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p
                                        class="text-[10px] font-bold tracking-widest text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        NISN / NIK
                                    </p>
                                    <p
                                        class="mt-0.5 font-mono text-sm font-bold text-gray-900 dark:text-slate-100"
                                    >
                                        {{ props.pendaftar.nik || '-' }}
                                    </p>
                                </div>

                                <div>
                                    <p
                                        class="text-[10px] font-bold tracking-widest text-gray-400 uppercase dark:text-slate-500"
                                    >
                                        JENJANG - CABANG
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs font-bold text-gray-900 dark:text-slate-100"
                                    >
                                        {{
                                            props.pendaftar.jenjang?.name || '-'
                                        }}
                                        -
                                        {{
                                            props.pendaftar.cabang?.name ||
                                            'Kalimantan Barat'
                                        }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-[11px] text-gray-400 dark:text-slate-500"
                                    >
                                        Gelombang
                                        {{
                                            props.pendaftar.gelombang?.name ||
                                            '1'
                                        }}
                                        • Periode
                                        {{
                                            props.pendaftar.periode?.name ||
                                            '2025/2026'
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Direct Actions: WhatsApp & Profil Santri -->
                        <div
                            class="mt-6 w-full space-y-2 border-t border-gray-100 pt-4 dark:border-slate-800"
                        >
                            <a
                                v-if="
                                    props.pendaftar.nomor_hp ||
                                    props.pendaftar.personal_data?.nomor_hp
                                "
                                :href="`https://wa.me/${(props.pendaftar.nomor_hp || props.pendaftar.personal_data?.nomor_hp).replace(/[^0-9]/g, '').replace(/^0/, '62')}`"
                                target="_blank"
                                class="flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-700 transition-colors hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                            >
                                <svg
                                    class="h-4 w-4 fill-emerald-600 dark:fill-emerald-400"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.892 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"
                                    />
                                </svg>
                                <span>Hubungi WhatsApp</span>
                            </a>

                            <Link
                                :href="`/admin/pendaftar/${props.pendaftar.id}`"
                                class="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
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
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                    />
                                </svg>
                                <span>Profil Lengkap Santri</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Pending Payment Verification Alert Banner (If Any Transfer Pending Verification) -->
                <div
                    v-if="pendingPayment"
                    class="flex flex-col gap-4 rounded-3xl border border-amber-300 bg-amber-50 p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-amber-800/60 dark:bg-amber-950/40 print:hidden"
                >
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-12 w-12 shrink-0 animate-pulse items-center justify-center rounded-2xl bg-amber-200 text-amber-800 shadow-xs dark:bg-amber-900/60 dark:text-amber-300"
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
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h4
                                class="text-base font-bold text-amber-900 dark:text-amber-200"
                            >
                                Menunggu Verifikasi Pembayaran Transfer
                            </h4>
                            <p
                                class="mt-0.5 text-xs text-amber-700 dark:text-amber-400"
                            >
                                Santri telah mengunggah bukti transfer sebesar
                                <strong
                                    class="font-mono font-bold text-amber-900 dark:text-amber-200"
                                    >{{
                                        formatRupiah(pendingPayment.amount)
                                    }}</strong
                                >
                                <span v-if="pendingPayment.bank">
                                    via {{ pendingPayment.bank.name }}</span
                                >
                                pada
                                {{
                                    formatDateTime(
                                        pendingPayment.payment_date ||
                                            pendingPayment.created_at,
                                    )
                                }}.
                            </p>
                        </div>
                    </div>

                    <!-- Tombol Verifikasi Pembayaran Ini (Ukuran & Font Menyesuaikan Standar Tombol Index / Filter) -->
                    <button
                        @click="openDetailPaymentModal(pendingPayment)"
                        type="button"
                        class="group inline-flex shrink-0 cursor-pointer items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-amber-700 focus:ring-2 focus:ring-amber-500/20 focus:outline-none sm:px-5 dark:bg-amber-600 dark:hover:bg-amber-500"
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
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <span>Verifikasi Pembayaran Ini</span>
                    </button>
                </div>

                <!-- BOTTOM CARD: RIWAYAT PEMBAYARAN -->
                <div
                    class="flex flex-col overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900"
                >
                    <!-- Card Header -->
                    <div
                        class="flex flex-col gap-3 border-b border-gray-100 p-6 sm:flex-row sm:items-center sm:justify-between sm:px-8 sm:py-6 dark:border-slate-800"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400"
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
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h3
                                    class="text-lg font-extrabold tracking-tight text-gray-900 dark:text-slate-100"
                                >
                                    Riwayat Pembayaran
                                </h3>
                                <p
                                    class="text-xs text-gray-500 dark:text-slate-400"
                                >
                                    Daftar transaksi pembayaran santri baik via
                                    transfer maupun manual
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="
                            !props.tagihan.pembayarans ||
                            props.tagihan.pembayarans.length === 0
                        "
                        class="p-10 text-center"
                    >
                        <div
                            class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-slate-800 dark:text-slate-500"
                        >
                            <svg
                                class="h-7 w-7"
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
                        <h4
                            class="mt-4 text-base font-bold text-gray-800 dark:text-slate-200"
                        >
                            Belum Ada Riwayat Pembayaran
                        </h4>
                        <p
                            class="mt-1 text-xs text-gray-500 dark:text-slate-400"
                        >
                            Belum ada transaksi pembayaran yang dicatat atau
                            diunggah untuk tagihan ini.
                        </p>
                    </div>

                    <!-- Payment History Table -->
                    <div v-else class="overflow-x-auto">
                        <!-- Bulk Action Alert Bar -->
                        <div
                            v-if="selectedPaymentIds.length > 0"
                            class="flex items-center justify-between border-b border-rose-100 bg-rose-50/90 px-6 py-3 text-xs shadow-2xs transition-all dark:border-rose-900/50 dark:bg-rose-950/60"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-rose-600 text-xs font-black text-white shadow-xs"
                                >
                                    {{ selectedPaymentIds.length }}
                                </span>
                                <span
                                    class="text-sm font-bold text-rose-900 dark:text-rose-200"
                                    >Pembayaran manual terpilih</span
                                >
                            </div>
                            <DangerButton
                                type="button"
                                @click="openDeleteBulkPaymentModal"
                                class="inline-flex cursor-pointer items-center gap-2 px-4 py-2 text-xs font-bold shadow-xs"
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
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                                <span>Hapus Terpilih</span>
                            </DangerButton>
                        </div>

                        <table class="w-full text-left text-xs sm:text-sm">
                            <thead>
                                <tr
                                    class="border-b border-gray-100 bg-gray-50 text-[11px] font-bold tracking-wider text-gray-400 uppercase dark:border-slate-800 dark:bg-slate-800/80 dark:text-slate-400"
                                >
                                    <th class="w-12 px-6 py-3.5 text-center">
                                        <div
                                            class="flex items-center justify-center"
                                        >
                                            <Checkbox
                                                :checked="isAllPaymentsSelected"
                                                :indeterminate="
                                                    selectedPaymentIds.length >
                                                        0 &&
                                                    !isAllPaymentsSelected
                                                "
                                                @change="
                                                    toggleSelectAllPayments
                                                "
                                                :disabled="
                                                    deletablePayments.length ===
                                                    0
                                                "
                                            />
                                        </div>
                                    </th>
                                    <th class="px-6 py-3.5">TGL. BAYAR</th>
                                    <th class="px-6 py-3.5">NOMINAL</th>
                                    <th class="px-6 py-3.5">METODE</th>
                                    <th class="px-6 py-3.5">STATUS</th>
                                    <th class="px-6 py-3.5 text-right">AKSI</th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-gray-100 font-medium text-slate-700 dark:divide-slate-800 dark:text-slate-300"
                            >
                                <tr
                                    v-for="p in props.tagihan.pembayarans"
                                    :key="p.id"
                                    class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/60"
                                >
                                    <!-- CHECKBOX -->
                                    <td
                                        class="w-12 px-6 py-4 text-center whitespace-nowrap"
                                    >
                                        <div
                                            class="flex items-center justify-center"
                                        >
                                            <Checkbox
                                                v-if="isDeletablePayment(p)"
                                                :value="p.id"
                                                v-model:checked="
                                                    selectedPaymentIds
                                                "
                                            />
                                            <div
                                                v-else
                                                title="Pembayaran oleh pendaftar tidak dapat dihapus manual"
                                                class="h-5 w-5 cursor-not-allowed rounded-full border-2 border-gray-200 bg-gray-100 opacity-40 dark:border-slate-700 dark:bg-slate-800/70"
                                            ></div>
                                        </div>
                                    </td>

                                    <!-- TGL. BAYAR -->
                                    <td
                                        class="px-6 py-4 font-medium whitespace-nowrap text-gray-900 dark:text-slate-100"
                                    >
                                        {{
                                            formatDateTime(
                                                p.payment_date || p.created_at,
                                            )
                                        }}
                                    </td>

                                    <!-- NOMINAL -->
                                    <td
                                        class="px-6 py-4 font-mono text-sm font-extrabold whitespace-nowrap text-gray-900 sm:text-base dark:text-slate-100"
                                    >
                                        {{ formatRupiah(p.amount) }}
                                    </td>

                                    <!-- METODE -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            v-if="
                                                p.payment_method ===
                                                    'TRANSFER' ||
                                                p.payment_method === 'VA' ||
                                                p.bank
                                            "
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-primary/20 bg-primary/10 px-3 py-1.5 text-xs font-bold text-primary dark:border-blue-900/40 dark:bg-blue-950/50 dark:text-blue-400"
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
                                            {{
                                                p.bank?.name ||
                                                'Virtual Account (VA)'
                                            }}
                                        </span>
                                        <span
                                            v-else-if="
                                                p.payment_method === 'SAMAHA'
                                            "
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-purple-200/60 bg-purple-50 px-3 py-1.5 text-xs font-bold text-purple-700 dark:border-purple-800/60 dark:bg-purple-950/40 dark:text-purple-300"
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
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-sky-200/60 bg-sky-50 px-3 py-1.5 text-xs font-bold text-sky-700 dark:border-sky-800/60 dark:bg-sky-950/40 dark:text-sky-300"
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
                                            Pembayaran Tunai
                                        </span>
                                    </td>

                                    <!-- STATUS -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            v-if="p.status === 'DITERIMA'"
                                            class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-100/70 px-3.5 py-1 text-xs font-extrabold text-emerald-700 uppercase dark:border-emerald-800/60 dark:bg-emerald-950/50 dark:text-emerald-300"
                                        >
                                            DITERIMA
                                        </span>
                                        <span
                                            v-else-if="
                                                p.status ===
                                                'MENUNGGU_VERIFIKASI'
                                            "
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

                                    <!-- AKSI (Standardized like Tagihan Index) -->
                                    <td
                                        class="px-6 py-4 text-right whitespace-nowrap"
                                    >
                                        <div
                                            class="flex items-center justify-end"
                                        >
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
                                                    <!-- Aksi 1: Verifikasi (jika pending) atau Rincian (jika sudah diproses) -->
                                                    <button
                                                        v-if="
                                                            p.status ===
                                                            'MENUNGGU_VERIFIKASI'
                                                        "
                                                        @click="
                                                            openDetailPaymentModal(
                                                                p,
                                                            )
                                                        "
                                                        type="button"
                                                        class="flex w-full cursor-pointer items-center bg-emerald-50/50 px-3 py-2.5 text-left text-sm font-bold text-emerald-700 transition-colors hover:bg-emerald-100 sm:px-4 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
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
                                                        <span>Verifikasi</span>
                                                    </button>
                                                    <button
                                                        v-else
                                                        @click="
                                                            openDetailPaymentModal(
                                                                p,
                                                            )
                                                        "
                                                        type="button"
                                                        class="flex w-full cursor-pointer items-center px-3 py-2.5 text-left text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
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
                                                        <span>Rincian</span>
                                                    </button>

                                                    <!-- Aksi 2: Hapus Pembayaran Manual -->
                                                    <button
                                                        v-if="
                                                            isDeletablePayment(
                                                                p,
                                                            )
                                                        "
                                                        @click="
                                                            openDeleteSinglePaymentModal(
                                                                p,
                                                            )
                                                        "
                                                        type="button"
                                                        class="flex w-full cursor-pointer items-center border-t border-gray-100 px-3 py-2.5 text-left text-sm font-bold text-rose-600 transition-colors hover:bg-rose-50 sm:px-4 dark:border-slate-800 dark:text-rose-400 dark:hover:bg-rose-950/40"
                                                    >
                                                        <svg
                                                            class="mr-3 h-4 w-4 text-rose-500"
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
                                                        <span
                                                            >Hapus Pembayaran</span
                                                        >
                                                    </button>
                                                </template>
                                            </ActionMenu>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>

        <!-- ========================================== -->
        <!-- MODAL 1: PREVIEW BUKTI TRANSFER -->
        <!-- ========================================== -->
        <Modal
            :show="isProofModalOpen"
            @close="isProofModalOpen = false"
            maxWidth="lg"
            title="Bukti Transfer Pembayaran"
            description="Pratinjau berkas bukti transfer yang diunggah santri."
            zIndexClass="z-[200]"
        >
            <template #icon>
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sky-600 sm:h-12 sm:w-12 dark:bg-sky-950/50 dark:text-sky-400"
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

            <div class="p-2 text-center">
                <img
                    :src="proofUrl"
                    alt="Bukti Transfer"
                    class="mx-auto max-h-[70vh] rounded-2xl border border-gray-100 object-contain shadow-xl dark:border-slate-800"
                />
            </div>

            <template #footer>
                <div
                    class="flex w-full flex-col-reverse justify-end gap-2.5 sm:flex-row"
                >
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
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                            />
                        </svg>
                        <span>Download Berkas</span>
                    </a>
                </div>
            </template>
        </Modal>

        <!-- ======================================================= -->
        <!-- MODAL 2: DETAIL RINCIAN PEMBAYARAN & VERIFIKASI -->
        <!-- ======================================================= -->
        <Modal
            :show="isDetailPaymentModalOpen"
            @close="closeDetailPaymentModal"
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

            <div v-if="detailTargetPayment" class="space-y-3.5">
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
                            class="max-w-[220px] truncate text-xs font-bold text-slate-700 dark:text-slate-300"
                        >
                            {{
                                props.pendaftar?.nama ||
                                detailTargetPayment.pendaftar?.nama
                            }}
                        </span>
                    </div>
                    <div
                        class="mt-1 font-mono text-xl font-black break-all text-primary sm:text-2xl dark:text-blue-400"
                    >
                        {{
                            props.tagihan?.nomor_invoice ||
                            detailTargetPayment.tagihan?.nomor_invoice ||
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
                            {{ formatRupiah(detailTargetPayment.amount) }}
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
                                formatDateTime(
                                    detailTargetPayment.payment_date ||
                                        detailTargetPayment.created_at,
                                )
                            }}
                        </div>
                    </div>
                </div>

                <!-- Card 3: Informasi Virtual Account -->
                <div
                    v-if="
                        detailTargetPayment.payment_method === 'TRANSFER' ||
                        detailTargetPayment.payment_method === 'VA' ||
                        detailTargetPayment.bank
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
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                            />
                        </svg>
                        <span>Informasi Virtual Account</span>
                    </div>

                    <!-- Bank Row (Tanpa Background pada Logo Bank) -->
                    <div class="mt-3 flex items-center gap-3.5">
                        <img
                            :src="getBankLogo(detailTargetPayment.bank)"
                            @error="handleBankLogoError($event)"
                            :alt="
                                detailTargetPayment.bank?.name ||
                                'Logo Bank'
                            "
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
                                    detailTargetPayment.bank?.name ||
                                    props.pendaftar?.virtual_accounts?.[0]?.bank
                                        ?.name ||
                                    'Bank Syariah Indonesia'
                                }}
                            </div>
                        </div>
                    </div>

                    <!-- Nomor Virtual Account Display -->
                    <div
                        class="mt-3 border-t border-slate-200/80 pt-3 dark:border-slate-700/80"
                    >
                        <span
                            class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase dark:text-slate-500"
                            >Nomor Virtual Account</span
                        >
                        <div
                            class="mt-0.5 font-mono text-base font-black tracking-wider text-slate-900 sm:text-lg select-all dark:text-slate-100"
                        >
                            {{
                                detailTargetPayment.va_number ||
                                props.pendaftar?.virtual_accounts?.[0]
                                    ?.va_number ||
                                '-'
                            }}
                        </div>
                    </div>
                </div>

                <!-- Row 4: Jenis Bayar & Status (2 Columns Grid) -->
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-3.5">
                    <!-- Jenis Bayar -->
                    <div
                        class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3.5 sm:p-4 dark:border-slate-700/80 dark:bg-slate-800/60"
                    >
                        <span
                            class="block text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >Jenis Bayar</span
                        >
                        <div class="mt-1">
                            <span
                                v-if="
                                    detailTargetPayment.payment_method ===
                                        'TRANSFER' ||
                                    detailTargetPayment.payment_method ===
                                        'VA' ||
                                    detailTargetPayment.bank
                                "
                                class="inline-flex items-center gap-1.5 rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-xs font-bold text-primary dark:border-blue-900/40 dark:bg-blue-950/50 dark:text-blue-400"
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
                                    detailTargetPayment.payment_method ===
                                    'SAMAHA'
                                "
                                class="inline-flex items-center gap-1.5 rounded-full border border-purple-200 bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700 dark:border-purple-800/50 dark:bg-purple-950/40 dark:text-purple-300"
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
                                class="inline-flex items-center gap-1.5 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 dark:border-sky-800/50 dark:bg-sky-950/40 dark:text-sky-300"
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
                    <div
                        class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3.5 sm:p-4 dark:border-slate-700/80 dark:bg-slate-800/60"
                    >
                        <span
                            class="block text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >Status</span
                        >
                        <div class="mt-1">
                            <span
                                v-if="
                                    detailTargetPayment.status ===
                                    'MENUNGGU_VERIFIKASI'
                                "
                                class="inline-flex items-center gap-1.5 rounded-full border border-amber-200/80 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 dark:border-amber-800/50 dark:bg-amber-950/40 dark:text-amber-300"
                            >
                                <span
                                    class="h-2 w-2 animate-pulse rounded-full bg-amber-500"
                                ></span>
                                Menunggu Verifikasi
                            </span>
                            <span
                                v-else-if="
                                    detailTargetPayment.status === 'DITERIMA'
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
                    v-if="getProofUrl(detailTargetPayment)"
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
                            :href="getProofUrl(detailTargetPayment)!"
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
                            openProofModal(getProofUrl(detailTargetPayment)!)
                        "
                        class="group relative flex max-h-56 cursor-pointer items-center justify-center overflow-hidden rounded-xl border border-gray-100 bg-gray-50 p-2 transition-all hover:border-primary/40 hover:shadow-xs dark:border-slate-800 dark:bg-slate-950/40"
                    >
                        <img
                            :src="getProofUrl(detailTargetPayment)!"
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
                                    d="M21 21l-6-6m2-5a7 7 0 11-18 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"
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
                                :error="verifyPaymentForm.errors.amount_verified"
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
                                        formatRupiah(detailTargetPayment.amount)
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
                            Alasan penolakan akan dapat dilihat oleh pendaftar pada dashboard pendaftaran mereka.
                        </p>
                    </div>
                </div>
            </div>

            <template #footer>
                <div
                    class="flex w-full flex-col-reverse justify-end gap-2.5 sm:flex-row"
                >
                    <SecondaryButton
                        @click="closeDetailPaymentModal"
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

        <!-- ========================================== -->
        <!-- MODAL 3: HAPUS PEMBAYARAN MANUAL (SINGLE) -->
        <!-- ========================================== -->
        <Modal
            :show="isDeleteSinglePaymentModalOpen"
            @close="closeDeleteSinglePaymentModal"
            maxWidth="md"
            title="Hapus Pembayaran Manual"
            description="Batalkan transaksi pembayaran manual yang telah dicatat."
        >
            <template #icon>
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600 sm:h-12 sm:w-12 dark:bg-rose-950/50 dark:text-rose-400"
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
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                    </svg>
                </div>
            </template>

            <form
                id="deleteSinglePaymentForm"
                @submit.prevent="submitDeleteSinglePayment"
                class="space-y-3 p-1"
                v-if="paymentToDelete"
            >
                <p class="text-xs text-slate-600 sm:text-sm dark:text-slate-300">
                    Apakah Anda yakin ingin menghapus pencatatan pembayaran
                    manual sebesar
                    <strong
                        class="font-mono font-bold text-slate-900 dark:text-slate-100"
                        >{{ formatRupiah(paymentToDelete.amount) }}</strong
                    >
                    ({{ paymentToDelete.payment_method }})?
                </p>
                <p class="text-xs font-medium text-rose-600 dark:text-rose-400">
                    Tindakan ini akan membatalkan pembayaran dan memperbarui
                    sisa tagihan secara otomatis.
                </p>
            </form>

            <template #footer>
                <div
                    class="flex w-full flex-col-reverse justify-end gap-2.5 sm:flex-row"
                >
                    <SecondaryButton
                        @click="closeDeleteSinglePaymentModal"
                        type="button"
                        class="w-full justify-center text-xs font-bold sm:w-auto sm:text-sm"
                    >
                        Batal
                    </SecondaryButton>
                    <DangerButton
                        form="deleteSinglePaymentForm"
                        type="submit"
                        :disabled="deleteSinglePaymentForm.processing"
                        class="w-full justify-center text-xs font-bold shadow-xs sm:w-auto sm:text-sm"
                    >
                        Hapus Pembayaran
                    </DangerButton>
                </div>
            </template>
        </Modal>

        <!-- ========================================== -->
        <!-- MODAL 4: HAPUS PEMBAYARAN MANUAL (BULK) -->
        <!-- ========================================== -->
        <Modal
            :show="isDeleteBulkPaymentModalOpen"
            @close="closeDeleteBulkPaymentModal"
            maxWidth="md"
            title="Hapus Pembayaran Terpilih"
            description="Batalkan beberapa transaksi pembayaran manual sekaligus."
        >
            <template #icon>
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600 sm:h-12 sm:w-12 dark:bg-rose-950/50 dark:text-rose-400"
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
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                    </svg>
                </div>
            </template>

            <form
                id="deleteBulkPaymentForm"
                @submit.prevent="submitDeleteBulkPayment"
                class="space-y-3 p-1"
            >
                <p class="text-xs text-slate-600 sm:text-sm dark:text-slate-300">
                    Apakah Anda yakin ingin menghapus
                    <strong>{{ selectedPaymentIds.length }}</strong> data
                    pembayaran manual yang dipilih?
                </p>
                <p class="text-xs font-medium text-rose-600 dark:text-rose-400">
                    Pembayaran yang dilakukan oleh pendaftar tidak ikut
                    terhapus. Sisa tagihan akan dihitung ulang secara otomatis.
                </p>
            </form>

            <template #footer>
                <div
                    class="flex w-full flex-col-reverse justify-end gap-2.5 sm:flex-row"
                >
                    <SecondaryButton
                        @click="closeDeleteBulkPaymentModal"
                        type="button"
                        class="w-full justify-center text-xs font-bold sm:w-auto sm:text-sm"
                    >
                        Batal
                    </SecondaryButton>
                    <DangerButton
                        form="deleteBulkPaymentForm"
                        type="submit"
                        :disabled="deleteBulkPaymentForm.processing"
                        class="w-full justify-center text-xs font-bold shadow-xs sm:w-auto sm:text-sm"
                    >
                        Hapus Terpilih
                    </DangerButton>
                </div>
            </template>
        </Modal>
    </div>
</template>
