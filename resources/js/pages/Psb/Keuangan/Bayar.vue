<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import BackButton from '@/Components/BackButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PsbLayout from '@/Layouts/PsbLayout.vue';
import { getBankLogo, handleBankLogoError } from '@/lib/bank';

defineOptions({ layout: PsbLayout });

const props = defineProps<{
    pendaftar: any;
    tagihan: any;
    virtualAccounts?: any[];
    pembayaran?: any;
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

const formatBytes = (bytes: number, decimals = 2) => {
    if (!bytes || bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
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

// Perhitungan Total Tagihan, Sudah Dibayar, dan Sisa Tagihan
const totalTagihan = computed(() => {
    return parseFloat(props.tagihan?.total_amount || props.tagihan?.amount || 0);
});

const totalPaid = computed(() => {
    return (props.tagihan.pembayarans || [])
        .filter((p: any) => p.status === 'APPROVED' || p.status === 'DITERIMA')
        .reduce((sum: number, p: any) => sum + parseFloat(p.amount || 0), 0);
});

const sisaTagihan = computed(() => {
    return Math.max(0, totalTagihan.value - totalPaid.value);
});

// Available active Virtual Accounts with active bank for candidate
const activeVirtualAccounts = computed(() => {
    const vas = props.virtualAccounts || props.pendaftar?.virtualAccounts || [];
    return vas.filter((va: any) => {
        if (!va || !va.bank) return false;
        // Pastikan bank aktif
        if (va.bank.is_active === false || va.bank.is_active === 0) {
            return false;
        }
        // Pastikan memiliki nomor VA yang valid
        const nomorVa = va.nomor_va || va.va_number;
        if (!nomorVa || String(nomorVa).trim() === '') {
            return false;
        }
        // Pastikan VA itu sendiri berstatus aktif jika ada field status
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

// Selected Bank VA state
const selectedVaId = ref<string>(
    props.pembayaran?.bank_id
        ? (activeVirtualAccounts.value.find((va: any) => va.bank_id === props.pembayaran.bank_id)?.id || activeVirtualAccounts.value[0]?.id || '')
        : (activeVirtualAccounts.value.length > 0 ? activeVirtualAccounts.value[0].id : '')
);

const selectedVa = computed(() => {
    return activeVirtualAccounts.value.find((va) => va.id === selectedVaId.value) || null;
});

// Rincian Biaya Admin Bank
const bankAdminFees = computed(() => {
    if (!selectedVa.value?.bank) return [];
    return selectedVa.value.bank.biaya_admins || selectedVa.value.bank.biayaAdmins || [];
});

const adminFee = computed(() => {
    if (bankAdminFees.value.length === 0) return 0;
    return bankAdminFees.value.reduce((sum: number, f: any) => sum + parseFloat(f.nominal || 0), 0);
});

// Tagihan Pokok & Grand Total
const tagihanPokok = computed(() => {
    return sisaTagihan.value > 0 ? sisaTagihan.value : totalTagihan.value;
});

const grandTotal = computed(() => {
    return tagihanPokok.value + adminFee.value;
});

// Back URL computation
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
    return `/psb/keuangan/tagihan/${props.tagihan?.id || ''}`;
});

// Drag & Drop / Upload State
const agreeDeclaration = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const previewUrl = ref<string | null>(
    props.pembayaran?.proof_path
        ? (props.pembayaran.proof_path.startsWith('http') ? props.pembayaran.proof_path : `/storage/${props.pembayaran.proof_path}`)
        : null
);
const isDragging = ref(false);
const uploadError = ref<string | null>(null);

const form = useForm({
    tagihan_id: props.tagihan?.id,
    bank_id: '',
    amount: 0,
    file: null as File | null,
    redirect_to: '',
});

const processFile = (file: File) => {
    uploadError.value = null;
    if (!file.type.match(/^image\/(jpeg|jpg|png|webp)$/i)) {
        uploadError.value = 'Format file tidak didukung. Harap pilih berkas gambar JPG, PNG, atau WEBP.';
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        uploadError.value = 'Ukuran file melebihi batas maksimal 5MB.';
        return;
    }
    selectedFile.value = file;
    form.file = file;
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
    previewUrl.value = URL.createObjectURL(file);
};

const handleFileSelect = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        processFile(target.files[0]);
    }
};

const handleDrop = (e: DragEvent) => {
    isDragging.value = false;
    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        processFile(e.dataTransfer.files[0]);
    }
};

const handleDragOver = (e: DragEvent) => {
    e.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = (e: DragEvent) => {
    e.preventDefault();
    isDragging.value = false;
};

const triggerFileInput = () => {
    fileInput.value?.click();
};

const removeSelectedFile = () => {
    selectedFile.value = null;
    form.file = null;
    uploadError.value = null;
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submitPayment = () => {
    if (!agreeDeclaration.value || (!selectedFile.value && !props.pembayaran) || !selectedVa.value) return;

    form.bank_id = selectedVa.value.bank_id || selectedVa.value.bank?.id;
    form.amount = tagihanPokok.value;
    form.redirect_to = backUrl.value;

    const endpoint = props.pembayaran?.id
        ? `/psb/keuangan/pembayaran/${props.pembayaran.id}/update`
        : '/psb/keuangan/bayar';

    form.post(endpoint, {
        preserveScroll: true,
        onSuccess: () => {
            // Handled by controller redirect
        },
    });
};

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
</script>

<template>
    <div class="w-full space-y-6">
        <Head :title="`Pembayaran Tagihan - ${props.tagihan.nomor_invoice}`" />

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    {{ props.pembayaran ? 'Edit Pembayaran Tagihan' : 'Formulir Pembayaran Tagihan' }}
                </h1>
                <p class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    {{ props.pembayaran ? 'Perbarui bank tujuan atau unggah ulang berkas bukti transfer Anda.' : 'Pilih rekening Virtual Account bank tujuan dan unggah bukti transfer pembayaran Anda.' }}
                </p>
            </div>
            <BackButton :href="backUrl">Kembali</BackButton>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- ========================================================= -->
            <!-- LEFT COLUMN: INFORMASI TAGIHAN & FORM TRANSFER (8 COLS)   -->
            <!-- ========================================================= -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Card 1: Informasi Tagihan & Rincian Biaya -->
                <div class="rounded-3xl border border-gray-200 bg-white p-6 sm:p-8 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-6">
                    <!-- Header with Title & Status Badge -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-100 pb-5 dark:border-slate-800">
                        <div class="flex items-center gap-3.5">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-primary/20 bg-primary/10 text-primary dark:bg-blue-950/50 dark:text-blue-400">
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

                    <!-- Data Grid -->
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

                    <!-- Rincian Item Tagihan Table -->
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
                                    <tr class="border-t-2 border-gray-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-800/40">
                                        <td class="py-3 px-4 text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                                            Total Tagihan
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono text-sm font-black text-slate-900 dark:text-slate-100">
                                            {{ formatRupiah(totalTagihan) }}
                                        </td>
                                    </tr>
                                    <tr v-if="totalPaid > 0" class="border-t border-gray-100 dark:border-slate-800 bg-emerald-50/30 dark:bg-emerald-950/20">
                                        <td class="py-2.5 px-4 text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">
                                            Sudah Dibayar
                                        </td>
                                        <td class="py-2.5 px-4 text-right font-mono text-sm font-bold text-emerald-600 dark:text-emerald-300">
                                            {{ formatRupiah(totalPaid) }}
                                        </td>
                                    </tr>
                                    <tr class="border-t border-gray-100 dark:border-slate-800 bg-amber-50/30 dark:bg-amber-950/20">
                                        <td class="py-3 px-4 text-xs font-black text-amber-800 dark:text-amber-300 uppercase tracking-wider">
                                            Sisa Tagihan yang Harus Dibayar
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono text-base font-black text-primary dark:text-blue-400">
                                            {{ formatRupiah(tagihanPokok) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Pilihan Bank Pembayaran (Layout Responsif & Rapi) -->
                <div class="rounded-3xl border border-gray-200 bg-white p-6 sm:p-8 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-extrabold tracking-tight text-slate-900 dark:text-slate-100">
                                Pilih Bank Pembayaran
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Pilih rekening Virtual Account bank tujuan transfer yang Anda inginkan
                            </p>
                        </div>
                    </div>

                    <div v-if="activeVirtualAccounts.length === 0" class="rounded-2xl border border-dashed border-gray-200 p-8 text-center text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        Belum ada rekening Virtual Account bank aktif yang diterbitkan untuk akun Anda.
                    </div>

                    <!-- Grid Card Bank -->
                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5">
                        <div
                            v-for="va in activeVirtualAccounts"
                            :key="va.id"
                            @click="selectedVaId = va.id"
                            class="group relative flex cursor-pointer flex-col justify-between rounded-2xl border p-4.5 transition-all duration-200 select-none"
                            :class="[
                                selectedVaId === va.id
                                    ? 'border-primary bg-primary/[0.04] ring-2 ring-primary/20 shadow-xs dark:border-blue-500 dark:bg-blue-950/40 dark:ring-blue-500/30'
                                    : 'border-gray-200/90 bg-white hover:border-slate-300 hover:bg-slate-50/60 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700 dark:hover:bg-slate-800/60'
                            ]"
                        >
                            <!-- Top: Radio Indicator & Logo -->
                            <div class="flex items-center justify-between gap-2">
                                <div
                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border transition-all"
                                    :class="[
                                        selectedVaId === va.id
                                            ? 'border-primary bg-primary text-white dark:border-blue-500 dark:bg-blue-600'
                                            : 'border-gray-300 bg-white dark:border-slate-700 dark:bg-slate-800'
                                    ]"
                                >
                                    <span v-if="selectedVaId === va.id" class="h-2 w-2 rounded-full bg-white"></span>
                                </div>

                                <div class="flex h-8 items-center justify-end">
                                    <img
                                        :src="getBankLogo(va.bank || va)"
                                        @error="handleBankLogoError($event)"
                                        :alt="va.bank?.name || 'Logo Bank'"
                                        class="h-7 w-auto max-w-[90px] object-contain drop-shadow-2xs transition-transform group-hover:scale-105"
                                    />
                                </div>
                            </div>

                            <!-- Bottom: Bank Name & Badge -->
                            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-slate-800">
                                <p class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate">
                                    {{ va.bank?.name || 'Bank Tujuan' }}
                                </p>
                                <span class="mt-0.5 inline-block text-[10.5px] font-semibold text-slate-400 dark:text-slate-500">
                                    Virtual Account (VA)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Informasi Nomor Virtual Account Bank Terpilih (Clean, Elegan & Centered) -->
                <div
                    v-if="selectedVa"
                    class="rounded-3xl border border-gray-200 bg-white p-6 sm:p-8 shadow-xs dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex flex-col items-center justify-center text-center space-y-4">
                        <!-- Bank Identity Header -->
                        <div class="inline-flex items-center gap-2.5 rounded-full border border-gray-200 bg-slate-50 px-4 py-1.5 dark:border-slate-700 dark:bg-slate-800">
                            <img
                                :src="getBankLogo(selectedVa.bank || selectedVa)"
                                @error="handleBankLogoError($event)"
                                :alt="selectedVa.bank?.name || 'Bank'"
                                class="h-4.5 w-auto max-w-[70px] object-contain"
                            />
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200">
                                {{ selectedVa.bank?.name || 'Bank Tujuan' }}
                            </span>
                        </div>

                        <!-- VA Label & Number Display -->
                        <div class="space-y-1.5">
                            <span class="block text-[11px] font-bold tracking-widest text-slate-400 uppercase dark:text-slate-500">
                                NOMOR VIRTUAL ACCOUNT
                            </span>
                            <div class="font-mono text-2xl sm:text-4xl font-black tracking-widest text-primary dark:text-blue-400 select-all">
                                {{ selectedVa.nomor_va || selectedVa.va_number || '-' }}
                            </div>
                        </div>

                        <!-- Copy Button -->
                        <div>
                            <button
                                type="button"
                                @click="copyToClipboard(selectedVa.nomor_va || selectedVa.va_number, selectedVa.id)"
                                class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-primary/30 bg-primary/10 px-5 py-2.5 text-xs font-bold text-primary transition-all hover:bg-primary hover:text-white active:scale-95 dark:border-blue-800 dark:bg-blue-950/50 dark:text-blue-300 dark:hover:bg-blue-600 dark:hover:text-white shadow-2xs"
                            >
                                <svg v-if="copiedVaId === selectedVa.id" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span>{{ copiedVaId === selectedVa.id ? 'Nomor VA Berhasil Disalin!' : 'Salin Nomor Virtual Account' }}</span>
                            </button>
                        </div>

                        <!-- Transfer Instruction Note Box -->
                        <div class="w-full max-w-lg rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/50 text-center">
                            <p class="text-xs text-slate-600 dark:text-slate-400">
                                Transfer tepat sebesar
                                <strong class="font-mono text-sm font-black text-slate-900 dark:text-slate-100">{{ formatRupiah(grandTotal) }}</strong>
                                ke nomor Virtual Account di atas.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Unggah Bukti Pembayaran (Drag & Drop + Preview) -->
                <div class="rounded-3xl border border-gray-200 bg-white p-6 sm:p-8 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-extrabold tracking-tight text-slate-900 dark:text-slate-100">
                                Unggah Bukti Transfer
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Unggah foto struk atau tangkapan layar bukti transfer resmi yang telah Anda lakukan
                            </p>
                        </div>
                    </div>

                    <!-- Hidden Input -->
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/jpeg,image/png,image/jpg,image/webp"
                        class="hidden"
                        @change="handleFileSelect"
                    />

                    <!-- Drag and Drop Dropzone (Empty State) -->
                    <div
                        v-if="!selectedFile && !previewUrl"
                        @dragenter.prevent="isDragging = true"
                        @dragover.prevent="handleDragOver"
                        @dragleave.prevent="handleDragLeave"
                        @drop.prevent="handleDrop"
                        @click="triggerFileInput"
                        class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed p-8 sm:p-10 text-center transition-all duration-200"
                        :class="[
                            isDragging
                                ? 'border-primary bg-primary/5 dark:border-blue-500 dark:bg-blue-500/10 scale-[0.99]'
                                : 'border-gray-200 bg-slate-50/50 hover:border-primary/50 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800/40 dark:hover:border-slate-600 dark:hover:bg-slate-800/70'
                        ]"
                    >
                        <div class="mb-3.5 flex h-14 w-14 items-center justify-center rounded-2xl border border-primary/20 bg-primary/10 text-primary dark:bg-blue-950/60 dark:text-blue-400 shadow-2xs">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </div>

                        <h4 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                            Tarik & Lepas File Bukti Transfer di Sini
                        </h4>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            atau <span class="font-bold text-primary dark:text-blue-400 underline">klik untuk memilih file</span> dari perangkat Anda
                        </p>
                        <div class="mt-3 inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1 text-[11px] font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                            Format JPG, JPEG, PNG, WEBP (Maksimal 5MB)
                        </div>
                    </div>

                    <!-- File Selected & Existing Preview State -->
                    <div
                        v-else
                        class="overflow-hidden rounded-2xl border border-gray-200 bg-slate-50/50 p-4 sm:p-5 dark:border-slate-800 dark:bg-slate-800/40 space-y-4"
                    >
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 border-b border-gray-100 pb-3 dark:border-slate-800">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-bold text-slate-900 dark:text-slate-100">
                                        {{ selectedFile ? selectedFile.name : 'Bukti Pembayaran Terunggah' }}
                                    </p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500">
                                        {{ selectedFile ? formatBytes(selectedFile.size) : 'Berkas bukti pembayaran yang tersimpan' }} &bull; Siap dikirim
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 self-end sm:self-auto">
                                <button
                                    type="button"
                                    @click="triggerFileInput"
                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 shadow-2xs"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span>Ganti Berkas</span>
                                </button>
                                <button
                                    type="button"
                                    @click="removeSelectedFile"
                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 transition-colors hover:bg-rose-100 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-300 shadow-2xs"
                                >
                                    <svg class="h-3.5 w-3.5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </div>

                        <!-- Image Preview Box -->
                        <div class="flex justify-center overflow-hidden rounded-xl border border-gray-200 bg-white p-2.5 dark:border-slate-800 dark:bg-slate-900">
                            <img
                                :src="previewUrl!"
                                class="max-h-72 w-auto rounded-lg object-contain drop-shadow-sm"
                                alt="Pratinjau Bukti Pembayaran"
                            />
                        </div>
                    </div>

                    <!-- Error Alert Message -->
                    <p v-if="uploadError" class="text-xs font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1.5">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ uploadError }}</span>
                    </p>
                </div>

                <!-- Card 5: Panduan & Petunjuk Singkat Pembayaran -->
                <div class="rounded-3xl border border-blue-100 bg-blue-50/50 p-5 sm:p-6 dark:border-blue-900/40 dark:bg-blue-950/20 space-y-3">
                    <div class="flex items-center gap-2.5 text-primary dark:text-blue-400">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h4 class="text-xs font-extrabold uppercase tracking-wider">
                            Petunjuk Pembayaran Virtual Account
                        </h4>
                    </div>
                    <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-1.5 list-disc list-inside font-medium leading-relaxed">
                        <li>Pastikan transfer dilakukan sebelum tanggal jatuh tempo tagihan berakhir.</li>
                        <li>Gunakan menu Pembayaran Virtual Account pada m-banking, ATM, atau teller bank Anda.</li>
                        <li>Pastikan nominal transfer sesuai persis hingga digit terakhir dengan Total Bayar.</li>
                        <li>Simpan bukti transfer dan unggah struk/screenshot resmi pada formulir di atas.</li>
                    </ul>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- RIGHT COLUMN: RINGKASAN TOTAL PEMBAYARAN & AKSI (4 COLS)  -->
            <!-- ========================================================= -->
            <div class="lg:col-span-4 space-y-6">
                <div class="sticky top-6 rounded-3xl border border-gray-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-5">
                    <!-- Title -->
                    <div class="border-b border-gray-100 pb-4 dark:border-slate-800">
                        <h3 class="text-base font-extrabold tracking-tight text-slate-900 dark:text-slate-100">
                            Ringkasan Pembayaran
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Rincian kalkulasi total tagihan dan biaya
                        </p>
                    </div>

                    <!-- Breakdown Tagihan & Biaya Admin -->
                    <div class="space-y-3.5 text-xs">
                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                            <span>Tagihan Pokok</span>
                            <span class="font-mono font-bold text-slate-900 dark:text-slate-100">{{ formatRupiah(tagihanPokok) }}</span>
                        </div>

                        <!-- Biaya Tambahan Bank Header -->
                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                            <span>Biaya Tambahan Bank</span>
                            <span class="font-mono font-bold" :class="adminFee > 0 ? 'text-slate-900 dark:text-slate-100' : 'text-emerald-600 dark:text-emerald-400'">
                                {{ adminFee > 0 ? formatRupiah(adminFee) : 'Gratis (Rp 0)' }}
                            </span>
                        </div>

                        <!-- Rincian Biaya Admin Bank (Jika ada rincian) -->
                        <div v-if="bankAdminFees.length > 0" class="pl-2.5 space-y-1.5 border-l-2 border-primary/30 dark:border-blue-500/40 py-1 my-1">
                            <div
                                v-for="(fee, idx) in bankAdminFees"
                                :key="idx"
                                class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400"
                            >
                                <span class="truncate pr-2 font-medium">{{ fee.name || 'Biaya Layanan Bank' }}</span>
                                <span class="font-mono font-semibold text-slate-700 dark:text-slate-300 shrink-0">{{ formatRupiah(fee.nominal) }}</span>
                            </div>
                        </div>

                        <!-- Bank Terpilih Card Box (Clean Stacked Layout) -->
                        <div v-if="selectedVa?.bank" class="rounded-2xl border border-gray-200/90 bg-slate-50/90 p-4 dark:border-slate-800 dark:bg-slate-800/60 space-y-3">
                            <div class="flex items-center justify-between border-b border-gray-200/60 pb-2.5 dark:border-slate-700/60">
                                <span class="text-[10px] uppercase font-black tracking-wider text-slate-400 dark:text-slate-500">Bank Terpilih</span>
                                <span class="inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-bold text-primary dark:bg-blue-950/60 dark:text-blue-400">
                                    Virtual Account
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-bold text-slate-900 dark:text-slate-100">
                                        {{ selectedVa.bank.name }}
                                    </p>
                                    <p class="font-mono text-sm font-black text-primary dark:text-blue-400 mt-0.5 select-all">
                                        {{ selectedVa.nomor_va || selectedVa.va_number }}
                                    </p>
                                </div>
                                <div class="flex h-9 w-16 shrink-0 items-center justify-end">
                                    <img
                                        :src="getBankLogo(selectedVa.bank || selectedVa)"
                                        @error="handleBankLogoError($event)"
                                        :alt="selectedVa.bank?.name || 'Bank'"
                                        class="h-7 w-auto max-w-[65px] object-contain drop-shadow-2xs"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Grand Total -->
                        <div class="pt-4 border-t-2 border-gray-100 dark:border-slate-800 flex items-center justify-between">
                            <span class="text-xs font-black text-slate-900 dark:text-slate-100 uppercase tracking-wider">Total Bayar</span>
                            <span class="text-xl font-black text-primary dark:text-blue-400 font-mono">
                                {{ formatRupiah(grandTotal) }}
                            </span>
                        </div>
                    </div>

                    <!-- Perhatian & Checkbox Persetujuan Konfirmasi Pembayaran -->
                    <div class="rounded-2xl border border-amber-200/80 bg-amber-50/70 p-3.5 dark:border-amber-900/50 dark:bg-amber-950/30 space-y-2.5">
                        <div class="flex items-center gap-1.5 text-amber-800 dark:text-amber-300">
                            <svg class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span class="text-[11px] font-black uppercase tracking-wider">Konfirmasi Pembayaran</span>
                        </div>
                        <p class="text-[11px] text-amber-900/90 dark:text-amber-300/90 leading-relaxed font-medium">
                            Pastikan data tagihan dan nominal transfer telah sesuai. Bukti pembayaran akan diverifikasi oleh admin PSB.
                        </p>

                        <label class="flex cursor-pointer items-start gap-2.5 pt-2 border-t border-amber-200/60 dark:border-amber-900/50 select-none">
                            <Checkbox
                                v-model:checked="agreeDeclaration"
                                shape="circle"
                                class="mt-0.5 shrink-0"
                            />
                            <span class="text-[11px] font-bold text-amber-950 dark:text-amber-200 leading-snug">
                                Saya menyatakan bahwa seluruh data dan bukti transfer yang diunggah adalah benar dan valid.
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <PrimaryButton
                        type="button"
                        class="w-full justify-center py-3.5 text-xs font-extrabold shadow-md shadow-primary/20"
                        @click="submitPayment"
                        :disabled="!agreeDeclaration || (!selectedFile && !props.pembayaran) || form.processing"
                    >
                        <span v-if="form.processing">Menyimpan Pembayaran...</span>
                        <span v-else-if="props.pembayaran">Simpan Perubahan Pembayaran</span>
                        <span v-else>Kirim Bukti Pembayaran</span>
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </div>
</template>
