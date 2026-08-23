<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DataTable from '@/Components/DataTable.vue';
import ActionMenu from '@/Components/Form/ActionMenu.vue';
import CustomDatePicker from '@/Components/Form/CustomDatePicker.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import FilterModal from '@/Components/Form/FilterModal.vue';
import PsbLayout from '@/Layouts/PsbLayout.vue';

defineOptions({ layout: PsbLayout });

const props = defineProps<{
    pendaftar: any;
    tagihans: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters: {
        search?: string;
        status?: string;
        kategori?: string;
        due_status?: string;
        start_date?: string;
        end_date?: string;
        limit?: number;
    };
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

// Hitung total bayar & sisa tagihan per row
const getTagihanPaid = (row: any) => {
    return (row.pembayarans || [])
        .filter((p: any) => p.status === 'APPROVED' || p.status === 'DITERIMA')
        .reduce((sum: number, p: any) => sum + parseFloat(p.amount || 0), 0);
};

const getSisaTagihan = (row: any) => {
    const total = parseFloat(row.total_amount || row.amount || 0);
    const paid = getTagihanPaid(row);
    return Math.max(0, total - paid);
};

// Cek apakah tagihan sudah lewat jatuh tempo (Tunggakan)
const isOverdue = (tagihan: any) => {
    if (tagihan.status === 'PAID' || tagihan.status === 'LUNAS' || tagihan.status === 'SAMAHA') {
        return false;
    }
    if (!tagihan.due_date) return false;
    const due = new Date(tagihan.due_date);
    const now = new Date();
    return due < now;
};

// Kolom DataTable
const columns = [
    { key: 'invoice', label: 'No. Invoice & Tagihan' },
    { key: 'due_date', label: 'Jatuh Tempo' },
    { key: 'status_tagihan', label: 'Status Tagihan' },
    { key: 'tunggakan', label: 'Tunggakan' },
    { key: 'total_amount', label: 'Total Tagihan' },
];

// Search & Pagination handlers
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
const onSearchInput = (searchQuery: string) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            '/psb/keuangan/tagihan',
            {
                ...props.filters,
                search: searchQuery,
                page: 1,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, 400);
};

const onLimitChange = (newLimit: number) => {
    router.get(
        '/psb/keuangan/tagihan',
        {
            ...props.filters,
            limit: newLimit,
            page: 1,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

// Filter Modal State
const isFilterModalOpen = ref(false);
const filterForm = ref({
    status: props.filters.status || '',
    kategori: props.filters.kategori || '',
    due_status: props.filters.due_status || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const isFilterActive = computed(() => {
    return Boolean(
        props.filters.status ||
        props.filters.kategori ||
        props.filters.due_status ||
        props.filters.start_date ||
        props.filters.end_date,
    );
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (props.filters.status) count++;
    if (props.filters.kategori) count++;
    if (props.filters.due_status) count++;
    if (props.filters.start_date || props.filters.end_date) count++;
    return count;
});

const applyFilters = () => {
    isFilterModalOpen.value = false;
    router.get(
        '/psb/keuangan/tagihan',
        {
            ...props.filters,
            status: filterForm.value.status,
            kategori: filterForm.value.kategori,
            due_status: filterForm.value.due_status,
            start_date: filterForm.value.start_date,
            end_date: filterForm.value.end_date,
            page: 1,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    filterForm.value.status = '';
    filterForm.value.kategori = '';
    filterForm.value.due_status = '';
    filterForm.value.start_date = '';
    filterForm.value.end_date = '';
    applyFilters();
};
</script>

<template>
    <div class="w-full space-y-6">
        <Head title="Tagihan Biaya Pendaftaran - PSB Dalwa Kalbar" />

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    Tagihan Biaya Pendaftaran
                </h1>
                <p class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    Kelola dan pantau seluruh tagihan pendaftaran, status pelunasan, dan tunggakan biaya.
                </p>
            </div>
        </div>

        <!-- DataTable Wrapper -->
        <div class="mt-6">
            <DataTable
                :columns="columns"
                :data="props.tagihans.data"
                :pagination="props.tagihans"
                @search="onSearchInput"
                @limit="onLimitChange"
            >
                <template #filters>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="isFilterModalOpen = true"
                            class="group inline-flex cursor-pointer items-center rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-xs font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50 focus:ring-2 focus:ring-primary/20 focus:outline-none sm:px-4 sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            <svg
                                class="h-4 w-4 text-gray-400 transition-colors group-hover:text-primary dark:text-slate-400 dark:group-hover:text-blue-400"
                                :class="{ 'text-primary dark:text-blue-400': isFilterActive }"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            <span class="ml-2 hidden sm:inline">Filter</span>
                            <span
                                v-if="isFilterActive"
                                class="ml-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-white dark:bg-blue-600"
                            >
                                {{ activeFiltersCount }}
                            </span>
                        </button>
                    </div>

                    <!-- Filter Modal inside slot -->
                    <FilterModal
                        :show="isFilterModalOpen"
                        title="Filter Data Tagihan"
                        description="Saring data tagihan berdasarkan status, kategori biaya, jatuh tempo, atau rentang tanggal pembuatan"
                        max-width="md"
                        @close="isFilterModalOpen = false"
                        @reset="resetFilters"
                        @apply="applyFilters"
                    >
                        <div class="space-y-4">
                            <!-- Status Tagihan -->
                            <div>
                                <label class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400">
                                    Status Tagihan / Pelunasan
                                </label>
                                <CustomSelect
                                    v-model="filterForm.status"
                                    :options="[
                                        { value: '', label: 'Semua Status' },
                                        { value: 'BELUM_BAYAR', label: 'Belum Bayar' },
                                        { value: 'BELUM_LUNAS', label: 'Belum Lunas' },
                                        { value: 'overdue', label: 'Tunggakan (Lewat Jatuh Tempo)' },
                                    ]"
                                    placeholder="Pilih Status"
                                />
                            </div>

                            <!-- Kategori Tagihan -->
                            <div>
                                <label class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400">
                                    Kategori Biaya
                                </label>
                                <CustomSelect
                                    v-model="filterForm.kategori"
                                    :options="[
                                        { value: '', label: 'Semua Kategori' },
                                        { value: 'pendaftaran', label: 'Biaya Pendaftaran / Daftar Ulang' },
                                        { value: 'rombongan', label: 'Biaya Rombongan Keberangkatan' },
                                        { value: 'interview', label: 'Biaya Ujian / Seleksi Interview' },
                                        { value: 'lainnya', label: 'Biaya Lainnya / SPP' },
                                    ]"
                                    placeholder="Pilih Kategori"
                                />
                            </div>

                            <!-- Status Jatuh Tempo -->
                            <div>
                                <label class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400">
                                    Status Waktu Jatuh Tempo
                                </label>
                                <CustomSelect
                                    v-model="filterForm.due_status"
                                    :options="[
                                        { value: '', label: 'Semua Waktu' },
                                        { value: 'upcoming', label: 'Masa Berlaku (Belum Jatuh Tempo)' },
                                        { value: 'today', label: 'Jatuh Tempo Hari Ini' },
                                        { value: 'overdue', label: 'Lewat Jatuh Tempo (Tunggakan)' },
                                    ]"
                                    placeholder="Pilih Status Jatuh Tempo"
                                />
                            </div>

                            <!-- Rentang Tanggal Terbit Tagihan -->
                            <div>
                                <label class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-slate-400">
                                    Rentang Tanggal Pembuatan Tagihan
                                </label>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <span class="mb-1 block text-[11px] font-medium text-gray-400 dark:text-slate-500">Dari Tanggal</span>
                                        <CustomDatePicker v-model="filterForm.start_date" />
                                    </div>
                                    <div>
                                        <span class="mb-1 block text-[11px] font-medium text-gray-400 dark:text-slate-500">Sampai Tanggal</span>
                                        <CustomDatePicker v-model="filterForm.end_date" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </FilterModal>
                </template>

                <!-- Column: No. Invoice & Tagihan -->
                <template #cell-invoice="{ row }">
                    <div class="flex flex-col py-0.5">
                        <Link
                            :href="`/psb/keuangan/tagihan/${row.id}`"
                            class="font-bold text-[13.5px] text-slate-900 hover:text-primary transition-colors dark:text-slate-100 dark:hover:text-blue-400 leading-snug"
                        >
                            {{ row.nomor_invoice || 'Invoice Tagihan' }}
                        </Link>
                        <span class="text-[11.5px] text-slate-500 dark:text-slate-400">
                            {{ row.kategori_biaya?.name || (row.items?.[0]?.description ?? 'Biaya Pendaftaran') }}
                        </span>
                    </div>
                </template>

                <!-- Column: Jatuh Tempo -->
                <template #cell-due_date="{ row }">
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 whitespace-nowrap">
                        {{ formatDate(row.due_date) }}
                    </span>
                </template>

                <!-- Column: Status Tagihan -->
                <template #cell-status_tagihan="{ row }">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wider border whitespace-nowrap"
                        :class="[
                            row.status === 'PAID' || row.status === 'LUNAS'
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300'
                                : row.status === 'SAMAHA'
                                  ? 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-900/50 dark:bg-indigo-950/60 dark:text-indigo-300'
                                  : isOverdue(row)
                                    ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/60 dark:text-rose-300'
                                    : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/60 dark:text-amber-300'
                        ]"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full"
                            :class="[
                                row.status === 'PAID' || row.status === 'LUNAS'
                                    ? 'bg-emerald-500'
                                    : isOverdue(row)
                                      ? 'bg-rose-500'
                                      : 'bg-amber-500'
                            ]"
                        ></span>
                        {{
                            row.status === 'PAID' || row.status === 'LUNAS'
                                ? 'LUNAS'
                                : row.status === 'SAMAHA'
                                  ? 'SAMAHA'
                                  : isOverdue(row)
                                    ? 'TERLAMBAT'
                                    : 'BELUM LUNAS'
                        }}
                    </span>
                </template>

                <!-- Column: Tunggakan -->
                <template #cell-tunggakan="{ row }">
                    <span
                        v-if="isOverdue(row)"
                        class="inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-[11px] font-black uppercase text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-300 whitespace-nowrap"
                    >
                        Tunggakan
                    </span>
                    <span
                        v-else-if="row.status === 'PAID' || row.status === 'LUNAS'"
                        class="text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                    >
                        Tidak Ada
                    </span>
                    <span
                        v-else
                        class="text-xs text-slate-400 dark:text-slate-500"
                    >
                        Masa Berlaku
                    </span>
                </template>

                <!-- Column: Total Tagihan & Sisa Tagihan -->
                <template #cell-total_amount="{ row }">
                    <div class="flex flex-col py-0.5">
                        <span class="font-mono text-sm font-black text-slate-900 dark:text-slate-100 whitespace-nowrap">
                            {{ formatRupiah(row.total_amount || row.amount) }}
                        </span>
                        <span
                            v-if="row.status !== 'PAID' && row.status !== 'LUNAS' && row.status !== 'SAMAHA'"
                            class="text-[11px] font-semibold text-amber-600 dark:text-amber-400 whitespace-nowrap"
                        >
                            Sisa: {{ formatRupiah(getSisaTagihan(row)) }}
                        </span>
                        <span
                            v-else
                            class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400 whitespace-nowrap"
                        >
                            Lunas Terpenuhi
                        </span>
                    </div>
                </template>

                <!-- Column: Aksi Tunggal yang Rapi -->
                <template #row-actions="{ row }">
                    <div class="flex justify-end">
                        <ActionMenu width="48">
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
                                <!-- Action 1: Detail Tagihan (Halaman Penuh) -->
                                <Link
                                    :href="`/psb/keuangan/tagihan/${row.id}`"
                                    class="flex w-full items-center px-4 py-2.5 text-left text-sm font-bold text-slate-700 hover:bg-slate-50 hover:text-primary transition-colors dark:text-slate-200 dark:hover:bg-slate-800"
                                >
                                    <svg class="mr-3 h-4 w-4 text-primary dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Detail Tagihan
                                </Link>

                                <!-- Action 2: Bayar Sekarang -->
                                <Link
                                    v-if="row.status !== 'PAID' && row.status !== 'LUNAS'"
                                    :href="`/psb/keuangan/tagihan/${row.id}/bayar?from=${encodeURIComponent('/psb/keuangan/tagihan')}`"
                                    class="flex w-full items-center bg-emerald-50/50 px-4 py-2.5 text-left text-sm font-bold text-emerald-700 hover:bg-emerald-100 transition-colors dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                                >
                                    <svg class="mr-3 h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    Bayar Sekarang
                                </Link>
                            </template>
                        </ActionMenu>
                    </div>
                </template>
            </DataTable>
        </div>
    </div>
</template>
